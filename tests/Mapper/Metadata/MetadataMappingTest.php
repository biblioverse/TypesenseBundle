<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Mapper\Metadata;

use Biblioverse\TypesenseBundle\Mapper\Metadata\MetadataMapping;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MetadataMapping::class)]
class MetadataMappingTest extends \PHPUnit\Framework\TestCase
{
    public function testArrayAccess(): void
    {
        $metadataMapping = new MetadataMapping([
            'key' => 'value',
            'key2' => 'value2',
        ]);
        $this->assertSame(iterator_to_array($metadataMapping->getIterator()), [
            'key' => 'value',
            'key2' => 'value2',
        ]);

        $this->assertTrue($metadataMapping->offsetExists('key'));
        $this->assertFalse($metadataMapping->offsetExists('key3'));
        $this->assertSame('value', $metadataMapping->offsetGet('key'));

        $metadataMapping->offsetSet('key42', 'value42');
        $this->assertSame('value42', $metadataMapping['key42']);
        $metadataMapping->offsetUnset('key42');
        $this->assertFalse($metadataMapping->offsetExists('key42'));
    }
}
