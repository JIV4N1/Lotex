<?php

namespace App\Console\Commands;

class CreateSuperadministrator extends CreateAdministrator
{
    protected $signature = 'superadmin:create';

    protected $description = 'Crea una cuenta superadmin independiente sin modificar usuarios existentes';

    protected function createsSuperadmin(): bool
    {
        return true;
    }
}
