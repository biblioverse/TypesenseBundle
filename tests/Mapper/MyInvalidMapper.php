<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Mapper;

class MyInvalidMapper
{
    public static function getName(): string
    {
        return 'myInvalidMapper';
    }
}
