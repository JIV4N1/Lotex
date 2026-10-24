<?php

namespace App\Console\Commands;

use App\Services\LotificationTransfer;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ImportLotification extends Command
{
    protected $signature = 'lotification:import {file} {--apply : Aplicar la importación validada} {--sha256= : SHA256 esperado del archivo}';

    protected $description = 'Valida o importa una lotificación sin sobrescribir registros existentes';

    public function handle(LotificationTransfer $transfer): int
    {
        try {
            $file = $this->argument('file');
            if (! is_file($file) || filesize($file) > 256 * 1024 * 1024) {
                throw new \RuntimeException('Archivo inexistente o mayor de 256 MB.');
            }
            if (! $this->option('sha256') || ! hash_equals(hash_file('sha256', $file), (string) $this->option('sha256'))) {
                throw new \RuntimeException('SHA256 requerido o incorrecto.');
            }
            $pack = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $transfer->validate($pack);
            $rows = collect($pack['records'])->pluck('attributes');
            $this->line($rows->first()['title'].' · '.($rows->count() - 1).' lotes');
            $this->line('Ubicación: '.json_encode($pack['location'], JSON_UNESCAPED_UNICODE));
            $this->line('Disponibilidad: '.json_encode($rows->skip(1)->countBy('availability_status')));
            $this->line('Publicación: '.json_encode($rows->skip(1)->countBy('publication_status')));
            $parent = $transfer->import($pack, (bool) $this->option('apply'));
            $this->info($parent ? 'Importación completa. Ficha: /terrenos/'.$parent->slug : 'Vista previa válida. No se modificó la base. Usa --apply para importar.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Importación cancelada. No se aplicaron cambios.');
            if ($e instanceof ValidationException) {
                $this->error('El paquete tiene campos inválidos.');
            } elseif ($e instanceof \RuntimeException && ! $e instanceof QueryException) {
                $this->error($e->getMessage());
            }

            return self::FAILURE;
        }
    }
}
