<?php

namespace App\Console\Commands;

use App\Services\LotificationTransfer;
use Illuminate\Console\Command;

class ExportLotification extends Command
{
    protected $signature = 'lotification:export {code} {file}';

    protected $description = 'Exporta una lotificación y sus archivos privados, sin usuarios ni secretos';

    public function handle(LotificationTransfer $transfer): int
    {
        try {
            $pack = $transfer->export($this->argument('code'));
            $transfer->validate($pack);
            $handle = fopen($this->argument('file'), 'x');
            if (! $handle) {
                throw new \RuntimeException('No se puede crear el archivo o ya existe.');
            }
            try {
                $json = json_encode($pack, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                if (fwrite($handle, $json) !== strlen($json)) {
                    throw new \RuntimeException('Escritura incompleta.');
                }
            } finally {
                fclose($handle);
            }
            $this->info('Exportados '.(count($pack['records']) - 1).' lotes. SHA256: '.hash_file('sha256', $this->argument('file')));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Exportación cancelada. Revisa el archivo, los registros y sus archivos adjuntos.');

            return self::FAILURE;
        }
    }
}
