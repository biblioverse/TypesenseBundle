You can also populate additional collection if you inject a service implementing `StandaloneMapperInterface`.
You will need to convert the data on the fly, and also declare the mapping configuration yourself.

You will not be able to use the `hydrated` result out of the box.

Example of a custom mapper, it uses `AbstractEntityDataGenerator` that will fetch the entities from the database, but it's not mandatory.

```php
<?php

namespace App\Mapper;

use App\Entity\Book;
use Biblioverse\TypesenseBundle\Mapper\Entity\AbstractEntityDataGenerator;
use Biblioverse\TypesenseBundle\Mapper\Mapping\Mapping;
use Biblioverse\TypesenseBundle\Mapper\MappingGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\StandaloneCollectionManagerInterface;
use Biblioverse\TypesenseBundle\Type\DataTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends AbstractEntityDataGenerator<Book>
 */
class BookDataGenerator extends AbstractEntityDataGenerator
{
    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager, Book::class);
    }

    public static function getName(): string
    {
        return 'book';
    }
    
    public function getMapping(): Mapping
    {
        $mapping = new Mapping(collectionOptions: new CollectionOptions(
            tokenSeparators: [' ', '-', "'"],
            symbolsToIndex: ['+', '#', '@', '_'],
            defaultSortingField: 'sortable_id'
        ));

        return $mapping->
            add(
                name: 'id',
                type: DataTypeEnum::STRING
            )
            ->add(
                name: 'title',
                type: DataTypeEnum::STRING
            )
            ->add(
                name: 'sortable_id',
                type: DataTypeEnum::INT32
            )
            ->add(
                name: 'serie',
                type: DataTypeEnum::STRING,
                facet: true,
                optional: true
            )
            ->add(
                name: 'summary',
                type: DataTypeEnum::STRING,
                optional: true
            )
            ->add(
                name: 'serieIndex',
                type: DataTypeEnum::STRING,
                optional: true
            )
            ->add(
                name: 'extension',
                type: DataTypeEnum::STRING,
                facet: true
            )
            ->add(
                name: 'authors',
                type: DataTypeEnum::STRING_ARRAY,
                facet: true
            )
            ->add(
                name: 'tags',
                type: DataTypeEnum::STRING_ARRAY,
                facet: true,
                optional: true
            )
        ;
    }

    public function transform(object $data): array
    {
        return [
            'id' => (string) $data->getId(),
            'title' => $data->getTitle(),
            'sortable_id' => $data->getId(),
            'serie' => (string) $data->getSerie(),
            'summary' => (string) $data->getSummary(),
            'serieIndex' => (string) $data->getSerieIndex(),
            'extension' => $data->getExtension(),
            'authors' => $data->getAuthors(),
            'tags' => $data->getTags(),
        ];
    }

    public function support(object $entity): bool
    {
        return $entity::class === Book::class;
    }
}
```

## Advanced setFieldConverter usage

You can convert the entity's attribute using a service.


You can also setup your mapping manually using the `Mapping` class.

```php
    use Biblioverse\TypesenseBundle\Mapper\Fields\FieldMapping;
    use Biblioverse\TypesenseBundle\Mapper\Mapping;
    use Biblioverse\TypesenseBundle\Mapper\Converter\Field\FieldConverterInterface;
    // ..
    
    public function getMapping(): Mapping{
        $mapping = new Mapping();
        
        $field = new FieldMapping('phone', 'string');
        $field->setFieldConverter(new class(\libphonenumber\PhoneNumberUtil::getInstance()) implements FieldConverterInterface{  
            public function __construct(private \libphonenumber\PhoneNumberUtil $libPhoneNumber)
            {}
            
            public function convert(object $entity, mixed $value, FieldMappingInterface $fieldMapping): mixed;
            {
                return $this->libPhoneNumber->format($value, \libphonenumber\PhoneNumberFormat::E164);
            });
        });
        
        return $mapping;
    }
   // ..
```
