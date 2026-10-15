<?php

namespace App\Enums;

enum VisitStatus: string
{
    case New = 'nuevo';
    case Contacted = 'contactado';
    case Appointment = 'cita_confirmada';
    case Closed = 'cerrado';

    public static function options(): array
    {
        return ['nuevo' => 'Nuevo', 'contactado' => 'Contactado', 'cita_confirmada' => 'Cita confirmada', 'cerrado' => 'Cerrado'];
    }
}
