<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case Available = 'disponible';
    case Reserved = 'apartado';
    case Sold = 'vendido';
}
