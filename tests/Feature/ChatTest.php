<?php

namespace Tests\Feature;

use App\Services\ChatCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['chat.key' => 'test-key', 'chat.daily_limit' => 100]);
        Http::preventStrayRequests();
    }

    private function payload(array $extra = []): array
    {
        return array_merge(['message' => 'Terrenos en Tehuacán'], $extra);
    }

    public function test_missing_key_and_invalid_input_never_call_google(): void
    {
        $this->postJson('/chat', $this->payload(['message' => '']))->assertUnprocessable();
        $this->postJson('/chat', $this->payload(['message' => str_repeat('x', 501)]))->assertUnprocessable();
        $this->postJson('/chat', $this->payload(['history' => array_fill(0, 7, 'hola')]))->assertUnprocessable();
        config(['chat.key' => null]);
        $this->postJson('/chat', $this->payload())->assertStatus(503)->assertSee('configurado');
        Http::assertNothingSent();
    }

    public function test_valid_interpretation_calls_catalog_and_sends_only_questions(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(['intent' => 'search', 'location' => 'Tehuacán', 'max_price' => 1000000])]]]]]])]);
        $this->mock(ChatCatalog::class)->shouldReceive('answer')->once()->with(['intent' => 'search', 'location' => 'Tehuacán', 'max_price' => 1000000], 'terreno-publico')->andReturn(['message' => 'Encontrado', 'properties' => []]);
        $this->postJson('/chat', $this->payload(['history' => ['Busco un terreno'], 'property_slug' => 'terreno-publico']))->assertOk()->assertJsonPath('message', 'Encontrado');
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key') && str_contains($request['contents'][0]['parts'][0]['text'], 'Busco un terreno') && ! str_contains($request['contents'][0]['parts'][0]['text'], 'terreno-publico'));
    }

    public function test_provider_errors_and_invalid_output_are_not_exposed(): void
    {
        foreach ([Http::response(['secret' => 'provider-secret'], 429), Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"intent":"delete_database"}']]]]]]), Http::response(['candidates' => [['finishReason' => 'MAX_TOKENS']]])] as $response) {
            Http::fake(['*' => $response]);
            $this->postJson('/chat', $this->payload())->assertStatus(503)->assertDontSee('provider-secret')->assertDontSee('test-key');
        }
    }

    public function test_daily_budget_blocks_before_provider_call(): void
    {
        config(['chat.daily_limit' => 1]);
        Cache::put('chat-budget-'.now()->format('Y-m-d'), 1, 60);
        $this->postJson('/chat', $this->payload())->assertStatus(429);
        Http::assertNothingSent();
    }

    public function test_per_visitor_rate_limit(): void
    {
        config(['chat.key' => null]);
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/chat', $this->payload())->assertStatus(503);
        }
        $this->postJson('/chat', $this->payload())->assertStatus(429);
        Http::assertNothingSent();
    }
}
