<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Type;

enum InfixEnum: string
{
    case OFF = 'off';
    case ALWAYS = 'always';
    case FALLBACK = 'fallback';
}
