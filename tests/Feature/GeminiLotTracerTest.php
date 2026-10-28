<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Services\GeminiLotTracer;
use Illuminate\Support\Facades\Http;

class GeminiLotTracerTest extends TestCase
{
    public function test_rejects_non_image_without_contacting_provider(): void
    {
        config(['chat.key'=>'test-key']);
        Http::fake();
        try { app(GeminiLotTracer::class)->detect(base64_encode('not an image'), []); $this->fail('Must reject image'); }
        catch (\RuntimeException $e) { $this->assertSame('Imagen de análisis inválida.', $e->getMessage()); }
        Http::assertNothingSent();
    }
    public function test_valid_response_is_parsed_and_out_of_bounds_response_is_rejected(): void
    {
        config(['chat.key'=>'test-key','chat.tracing_model'=>'test-model']);
        $bytes = file_get_contents(__DIR__.'/../Fixtures/tracer.jpg');
        $shape=['block'=>'1','number'=>'1','points'=>[[0,0],[1000,0],[500,1000]]];
        $invalid = $shape; $invalid['points'][0][0] = -1;
        $body = fn($item) => ['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['lots'=>[$item]])]]]]]];
        Http::fake(['*'=>Http::sequence()->push($body($shape))->push($body($invalid))]);
        $this->assertSame([$shape], app(GeminiLotTracer::class)->detect(base64_encode($bytes), []));
        $this->expectException(\RuntimeException::class);
        app(GeminiLotTracer::class)->detect(base64_encode($bytes), []);
    }
}
