<?php

declare(strict_types=1);

namespace App\Services\Gemini\Enums;

enum Role: string
{
    case User = 'user';
    case Model = 'model';
}
