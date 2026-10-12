<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A relationship repeater validates again after dehydration has stored uploads.
 * Trust only files this field just stored in this request, in addition to its
 * record's existing path. This state is server-side and never client-hydrated.
 */
class PropertyFileUpload extends FileUpload
{
    public function saveUploadedFile(TemporaryUploadedFile $file): ?string
    {
        $path = parent::saveUploadedFile($file);
        if ($path !== null) {
            $paths = request()->attributes->get($this->uploadKey(), []);
            $paths[] = $path;
            request()->attributes->set($this->uploadKey(), $paths);
        }

        return $path;
    }

    public function isFilePathAuthorized(string $file, ?array $originalPaths = null): bool
    {
        return in_array($file, request()->attributes->get($this->uploadKey(), []), true)
            || parent::isFilePathAuthorized($file, $originalPaths);
    }

    private function uploadKey(): string
    {
        return 'property_uploads:'.$this->getLivewire()->getId().':'.$this->getDiskName().':'.$this->getStatePath();
    }
}
