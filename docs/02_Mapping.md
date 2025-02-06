To use Entities mapping, you will need to configure the mapping between your entities and the Typesense collections.

You can do that in the configuration. See the example below.

```yaml
biblioverse_typesense:
  typesense:
    uri: '%env(TYPESENSE_URL)%'
    key: '%env(TYPESENSE_KEY)%'
  collections:
    houses:
      entity: 'App\Entity\House'
      mapping:
        fields:
          - name: name
            type: string
            sort: true
            facet: true
          - name: owners
            type: string[]
```

Configuring the mapping involves the following functionalities:
* Your entity class is indexed on every insertion/update/deletion.
* You can automatically populate your entities into typesense.
* A service is automatically available to interact with the Typesense API and hydrate the result with your entities.

Limitation:
* Only entities with a Single Identifier are supported. PR are welcome to support composite keys.


# Types

The type such as `string` or `string[]` are the same as the Typesense types. You can see the full list of types in the [Typesense documentation](https://typesense.org/docs/0.21.0/api/collections.html#schema-fields).

You can also use the following enum: `Biblioverse\TypesenseBundle\Type\DataTypeEnum`.

# Entity attribute
If the field name in the entity is not matching the one on the Typesense collection, you can use the `entity_attribute` configuration to map the entity field to the collection field.

```php
class House
{
  // ...
  public function getTypesenseAddress(): string
  {
    return implode(', ', [$this->street, $this->city, $this->country]);
  }
}
```

Config
```yaml
    ...
    mapping:
      fields:
        - name: address
          type: string
          entity_attribute: typesenseAddress
```
