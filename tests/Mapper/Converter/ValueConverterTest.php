<?php

namespace Biblioverse\TypesenseBundle\Tests\Mapper\Converter;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueConversionException;
use Biblioverse\TypesenseBundle\Mapper\Converter\ToTypesenseInterface;
use Biblioverse\TypesenseBundle\Mapper\Converter\ValueConverter;
use Biblioverse\TypesenseBundle\Type\DataTypeEnum;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ValueConverter::class)]
class ValueConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws ValueConversionException
     */
    public function testSimple(): void
    {
        $valueConverter = new ValueConverter();

        $this->assertSame('12', $valueConverter->convert(12, DataTypeEnum::STRING->value));
        $this->assertSame(['12', '13'], $valueConverter->convert([12, 13], DataTypeEnum::STRING_ARRAY->value));
        $this->assertSame([12, 13], $valueConverter->convert([12, 13], DataTypeEnum::INT32_ARRAY->value));
        $this->assertSame([12.5, 13.3], $valueConverter->convert([12.5, 13.3], DataTypeEnum::FLOAT_ARRAY->value));
        $this->assertTrue($valueConverter->convert(1, DataTypeEnum::BOOL->value));
        $this->assertSame([true, false, true, false], $valueConverter->convert([1, 0, 'true', '0'], DataTypeEnum::BOOL_ARRAY->value));
        $this->assertSame([51.4874004, -0.016055], $valueConverter->convert([51.4874004, -0.016055], DataTypeEnum::GEOPOINT->value));
        $this->assertSame([[51.4874004, -0.016055], null], $valueConverter->convert([[51.4874004, -0.016055], null], DataTypeEnum::GEOPOINT_ARRAY->value));

        foreach (DataTypeEnum::cases() as $type) {
            $this->assertNull($valueConverter->convert(null, $type->value));
        }

        foreach ([
            DataTypeEnum::STRING->value => '',
            DataTypeEnum::INT32->value => 0,
            DataTypeEnum::BOOL->value => false,
            DataTypeEnum::OBJECT->value => [],
        ] as $type => $expectedValue) {
            $this->assertSame($expectedValue, $valueConverter->convert(null, $type, false), 'invalid conversion of null for type: '.$type);
        }
    }

    /**
     * @throws ValueConversionException
     */
    public function testDate(): void
    {
        $valueConverter = new ValueConverter();

        $date = new \DateTimeImmutable('now');
        $timeStamp = $date->getTimestamp();
        $this->assertSame($timeStamp, $valueConverter->convert($date, DataTypeEnum::INT64->value));
    }

    public function testImage(): void
    {
        $valueConverter = new ValueConverter();

        $expected = base64_encode('test');
        $this->assertSame($expected, $valueConverter->convert($expected, DataTypeEnum::IMAGE->value));
    }

    public function testImageFile(): void
    {
        $valueConverter = new ValueConverter();
        $file = new \SplFileInfo(__DIR__.'/test.txt');
        $this->assertSame('QmFzZTY0IEVuY29kZSBtZS4=', $valueConverter->convert($file, DataTypeEnum::IMAGE->value));
    }

    public function testEnum(): void
    {
        $valueConverter = new ValueConverter();

        $this->assertSame(TestBackedEnum::TEST->value, $valueConverter->convert(TestBackedEnum::TEST, DataTypeEnum::STRING->value));
        $this->assertSame('TEST', $valueConverter->convert(TestUnitEnum::TEST, DataTypeEnum::STRING->value));
    }

    public function testBadUnitEnum(): void
    {
        $valueConverter = new ValueConverter();

        try {
            $this->assertSame('TEST', $valueConverter->convert(TestUnitEnum::TEST, DataTypeEnum::BOOL->value));
            $this->fail('Exception ValueConversionException not thrown');
        } catch (ValueConversionException $e) {
            $this->assertStringContainsString(TestUnitEnum::class, $e->getMessage());
        }
    }

    /**
     * @throws ValueConversionException
     */
    public function testObjects(): void
    {
        $valueConverter = new ValueConverter();

        $object = new class implements \JsonSerializable {
            /**
             * @return string[]
             */
            public function jsonSerialize(): array
            {
                return ['key' => 'value'];
            }
        };
        $this->assertSame(['key' => 'value'], $valueConverter->convert($object, DataTypeEnum::OBJECT->value));
        $this->assertSame(['key' => 'value'], $valueConverter->convert(['key' => 'value'], DataTypeEnum::OBJECT->value));

        $object = new class implements ToTypesenseInterface {
            public function toTypesense(): array
            {
                return ['key' => 'value'];
            }
        };
        $this->assertSame(['key' => 'value'], $valueConverter->convert($object, DataTypeEnum::OBJECT->value));

        $object = new \ArrayIterator(['key' => 'value']);
        $this->assertSame(['key' => 'value'], $valueConverter->convert($object, DataTypeEnum::OBJECT->value));

        try {
            $object = new \stdClass();
            $this->assertSame(['key' => 'value'], $valueConverter->convert($object, DataTypeEnum::OBJECT->value));
            $failed = true;
        } catch (ValueConversionException) {
            $failed = false;
        }
        $this->assertFalse($failed, 'Exception ValueConversionException not thrown');
    }
}
