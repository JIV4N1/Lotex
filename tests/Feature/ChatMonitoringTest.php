<?php

namespace Tests\Feature;

use App\Filament\Resources\ChatInteractionResource\Pages\ListChatInteractions;
use App\Models\ChatInteraction;
use App\Models\User;
use App\Services\ChatCatalog;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\PostgresTestCase;

class ChatMonitoringTest extends PostgresTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['chat.key' => 'private-test-key', 'chat.daily_limit' => 100]);
        Http::preventStrayRequests();
    }

    private function record(array $extra = []): ChatInteraction
    {
        return ChatInteraction::create(array_merge(['question' => '¿Cuánto mide?', 'answer' => '400 m²', 'model' => 'test', 'status' => 'success', 'duration_ms' => 100], $extra));
    }

    public function test_usage_and_text_are_saved_without_history_and_encrypted_at_rest(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"intent":"help"}']]]]],
            'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 20, 'totalTokenCount' => 150]])]);
        $this->mock(ChatCatalog::class)->shouldReceive('answer')->once()->andReturn(['message' => 'Hola, puedo orientarte.', 'properties' => []]);
        $this->postJson('/chat', ['message' => 'Mi pregunta actual', 'history' => ['historial privado'], 'property_slug' => 'ficha'])->assertOk();
        $record = ChatInteraction::firstOrFail();
        $this->assertSame('Mi pregunta actual', $record->question);
        $this->assertSame('Hola, puedo orientarte.', $record->answer);
        $this->assertSame(150, $record->total_tokens);
        $this->assertSame(100, $record->input_tokens);
        $this->assertSame(20, $record->output_tokens);
        $raw = json_encode((array) DB::table('chat_interactions')->first());
        foreach (['Mi pregunta actual', 'historial privado', 'private-test-key', 'Hola, puedo orientarte.'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
    }

    public function test_errors_have_unknown_usage_and_never_store_provider_secrets(): void
    {
        Http::fake(['*' => Http::response(['error' => 'sensitive-provider-payload'], 429)]);
        $this->postJson('/chat', ['message' => 'Hola'])->assertStatus(503)->assertDontSee('sensitive-provider-payload');
        $record = ChatInteraction::firstOrFail();
        $this->assertSame('error', $record->status);
        $this->assertSame(429, $record->http_status);
        $this->assertNull($record->total_tokens);
        $this->assertNull($record->answer);
        $this->assertStringNotContainsString('sensitive-provider-payload', $record->toJson());
    }

    public function test_admin_read_only_monitor_filters_and_escapes_messages(): void
    {
        $good = $this->record(['question' => '<script>alert(1)</script>', 'total_tokens' => 12]);
        $bad = $this->record(['status' => 'error']);
        $old = $this->record(['created_at' => now()->subDays(40)]);
        $this->get('/admin/chatbot')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/chatbot')->assertForbidden();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/chatbot')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ListChatInteractions::class)->assertCanSeeTableRecords([$good, $bad])->assertCanNotSeeTableRecords([$old])
            ->filterTable('status', 'error')->assertCanSeeTableRecords([$bad])->assertCanNotSeeTableRecords([$good]);
        $page = Livewire::test(ListChatInteractions::class)->mountTableAction('view', $good);
        $this->assertCount(1, $page->instance()->getMountedActions());
        // Modal content is a Livewire partial; assert the mounted record independently.
        $action = $page->instance()->getMountedActions()[0];
        $this->assertSame($good->id, $action->getRecord()->id);
        $this->assertSame('<script>alert(1)</script>', $action->getRecord()->question);
        $this->assertFalse($admin->can('create', ChatInteraction::class));
        $this->assertFalse($admin->can('update', $good));
        $this->assertFalse($admin->can('delete', $good));
        $this->assertSame(2, ChatInteraction::retained()->count());
        $this->assertSame([$old->id], (new ChatInteraction)->prunable()->pluck('id')->all());
    }
}
