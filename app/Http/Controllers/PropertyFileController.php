<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\PropertyDocument;
use App\Models\PropertyImage;
use Illuminate\Support\Facades\Storage;

class PropertyFileController extends Controller
{
    public function advisorPhoto(Advisor $advisor)
    {
        abort_unless($advisor->is_active && $advisor->photo_path, 404);
        $this->checkPath($advisor->photo_path, 'advisor-photos/');
        $disk = Storage::disk('local');
        abort_unless(in_array($disk->mimeType($advisor->photo_path), ['image/jpeg', 'image/png', 'image/webp']), 404);

        return $disk->response($advisor->photo_path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function image(PropertyImage $image)
    {
        abort_unless($image->property && $image->property->publication_status->value === 'publicado', 404);
        $this->checkPath($image->path, 'property-images/');
        $disk = Storage::disk('local');
        abort_unless(in_array($disk->mimeType($image->path), ['image/jpeg', 'image/png', 'image/webp']), 404);

        return $disk->response($image->path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function document(PropertyDocument $document)
    {
        abort_unless($document->is_public && $document->property && $document->property->publication_status->value === 'publicado', 404);
        $this->checkPath($document->path, 'property-documents/');
        abort_unless(Storage::disk('local')->mimeType($document->path) === 'application/pdf', 404);

        return Storage::disk('local')->download($document->path, 'documento-'.$document->id.'.pdf', ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    private function checkPath(string $path, string $prefix): void
    {
        abort_unless(str_starts_with($path, $prefix) && ! str_contains($path, '..') && ! str_contains($path, '\\') && Storage::disk('local')->exists($path), 404);
    }
}
