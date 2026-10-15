<?php

namespace Tests\Feature;

use App\Services\ChatCatalog;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class ChatCatalogTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_filters_only_return_published_non_deleted_properties(): void
    {
        $p = $this->makeProperty();
        $this->makeProperty(['code' => 'DRAFT', 'slug' => 'draft', 'publication_status' => 'borrador']);
        $this->makeProperty(['code' => 'DELETED', 'slug' => 'deleted'])->delete();
        $this->makeProperty(['code' => 'EXPENSIVE', 'slug' => 'expensive', 'price_mxn' => 2000000]);
        $catalog = app(ChatCatalog::class);
        $result = $catalog->answer(['intent' => 'search', 'location' => 'Tehuacán', 'max_price' => 1000000, 'min_area' => 500, 'max_area' => 700], null);
        $this->assertCount(1, $result['properties']);
        $this->assertSame(route('properties.show', $p->slug), $result['properties'][0]['url']);
        $this->assertStringContainsString('780,000.00', $result['properties'][0]['summary']);
        $this->assertEmpty($catalog->answer(['intent' => 'search', 'location' => "' OR 1=1 --"], null)['properties']);
        $this->assertEmpty($catalog->answer(['intent' => 'search', 'max_price' => 1], null)['properties']);
    }

    public function test_detail_context_cannot_expose_drafts_or_private_documents(): void
    {
        $p = $this->makeProperty();
        $catalog = app(ChatCatalog::class);
        $result = $catalog->answer(['intent' => 'details', 'topics' => ['price_per_m2', 'services']], $p->slug);
        $this->assertStringContainsString('1,300.00', $result['message']);
        $this->assertStringContainsString('Agua', $result['message']);
        $p->documents()->create(['path' => 'property-documents/secret.pdf', 'title' => 'Secreto privado', 'is_public' => false]);
        $this->assertStringNotContainsString('Secreto privado', json_encode($catalog->answer(['intent' => 'documents'], $p->slug)));
        $p->update(['publication_status' => 'borrador']);
        $this->assertEmpty($catalog->answer(['intent' => 'details', 'topics' => ['price_per_m2', 'services']], $p->slug)['properties']);
    }

    public function test_context_answers_only_requested_measurements_despite_stale_search_filters(): void
    {
        $p = $this->makeProperty();
        $catalog = app(ChatCatalog::class);
        $result = $catalog->answer(['intent' => 'details', 'topics' => ['measurements'], 'max_price' => 1, 'location' => 'Otra ciudad'], $p->slug);
        $this->assertEmpty($result['properties']);
        $this->assertStringContainsString('600.00 m²', $result['message']);
        $this->assertStringContainsString('20.00 m', $result['message']);
        $this->assertStringContainsString('30.00 m', $result['message']);
        $this->assertStringNotContainsString('Agua', $result['message']);
        $this->assertStringNotContainsString('Precio', $result['message']);
        $p->update(['frontage_m' => null]);
        $this->assertSame('Frente: Por confirmar', $catalog->answer(['intent' => 'details', 'topics' => ['frontage']], $p->slug)['message']);
        $this->assertStringContainsString('Abre la ficha', $catalog->answer(['intent' => 'details'], null)['message']);
        $this->assertStringContainsString('no está disponible', $catalog->answer(['intent' => 'details'], 'inexistente')['message']);
    }
}
