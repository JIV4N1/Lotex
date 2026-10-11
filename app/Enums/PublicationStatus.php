<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'borrador';
    case Published = 'publicado';
}
