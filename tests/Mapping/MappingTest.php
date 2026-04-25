<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Mapping;

use Biblioverse\TypesenseBundle\Mapper\Fields\FieldMapping;
use Biblioverse\TypesenseBundle\Mapper\Mapping\Mapping;
use Biblioverse\TypesenseBundle\Mapper\Metadata\MetadataMapping;
use Biblioverse\TypesenseBundle\Mapper\Options\CollectionOptions;
use PHPUnit\Framework\TestCase;

class MappingTest extends TestCase
{
    public function testMapping(): void
    {
        $mapping = new Mapping();
        $mapping->add(name: 'name', type: 'string', facet: true, optional: false);
        $mapping->addField(new FieldMapping(name: 'lastname', type: 'string', facet: true, optional: false));

        $this->assertCount(2, $mapping->getFields(), 'Mapping::getFields has some missing fields');
        $this->assertNull($mapping->getCollectionOptions(), 'Mapping::getCollectionOptions should return null');
        $this->assertNull($mapping->getMetadata(), 'Mapping::getMetadata should return null');
    }

    public function testMappingWithMetadata(): void
    {
        $mapping = new Mapping(metadataMapping: new MetadataMapping(['hello' => 1]));
        $mapping->add(name: 'name', type: 'string', facet: true, optional: false);

        $this->assertNotNull($mapping->getMetadata(), 'Mapping::getMetadata should not return null');
        $this->assertSame(['hello' => 1], $mapping->getMetadata()->toArray());
    }

    public function testMappingWithOptions(): void
    {
        $mapping = new Mapping(collectionOptions: new CollectionOptions(tokenSeparators: [' ', ',']));
        $mapping->add(name: 'name', type: 'string', facet: true, optional: false);

        $this->assertNotNull($mapping->getCollectionOptions(), 'Mapping::getMetadata should not return null');
        $this->assertSame([
            'token_separators' => [' ', ','],
        ], $mapping->getCollectionOptions()->toArray());
    }
}
