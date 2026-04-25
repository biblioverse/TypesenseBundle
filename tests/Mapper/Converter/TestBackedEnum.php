<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Mapper\Converter;

enum TestBackedEnum: string
{
    case TEST = 'this is a value';
}
