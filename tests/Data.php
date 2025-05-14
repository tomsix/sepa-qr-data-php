<?php
namespace SepaQr\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SepaQr\Data;

/**
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 */
class DataTest extends TestCase
{
    /**
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public function testFormatMoney()
    {
        $this->assertEquals(
            'EUR100.00',
            Data::formatMoney('EUR', 100),
            'An amount should be formatted'
        );

        $this->assertEquals(
            'EUR',
            Data::formatMoney('EUR'),
            'A missing amount should only return the currency'
        );
    }

    /**
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public function testCreate()
    {
        $this->assertInstanceOf(
            Data::class,
            Data::create()
        );
    }

    public function testSetCharacterSet()
    {
        $sepaQrData = new Data();

        $sepaQrData->setCharacterSet(Data::ISO8859_1);

        $this->expectException(InvalidArgumentException::class);

        $sepaQrData->setCharacterSet('UTF8'); // @phpstan-ignore-line
    }

    public function testSetBic()
    {
        $sepaQrData = new Data();

        $sepaQrData->setBic('ABCDEFGH'); // 8 characters
        $sepaQrData->setBic('ABCDEFGHIJK'); // 11 characters

        $this->expectException(InvalidArgumentException::class);

        $sepaQrData->setBic('ABCDEFGHI'); // 9 characters
    }

    public function testSetCurrency()
    {
        $sepaQrData = new Data();

        $sepaQrData->setCurrency('USD');

        $this->expectException(InvalidArgumentException::class);

        $sepaQrData->setCurrency('ABCDEF');
    }

    public function testSetRemittance()
    {
        $sepaQrData = new Data();

        $this->expectException(InvalidArgumentException::class);

        $sepaQrData->setRemittanceReference('ABC')
            ->setRemittanceText('DEF');
    }

    public function testSetPurpose()
    {
        $sepaQrData = new Data();

        $sepaQrData->setPurpose('ACMT');

        $this->expectException(InvalidArgumentException::class);

        $sepaQrData->setPurpose('custom');
    }

    public function testEncodeMessage()
    {
        $sepaQrData = new Data();

        $sepaQrData->setName('Test')
            ->setIban('ABC')
            ->setAmount(1075.25)
            ->setRemittanceText('DEF');

        $message = (string)$sepaQrData;

        $this->assertTrue(
            stristr($message, '1075.25') !== false,
            'The amount should be formatted using only a dot (.) as the decimal separator'
        );

        $this->assertEquals(
            11,
            count(explode("\n", $message)),
            'The last populated element cannot be followed by any character or element separator'
        );

        $this->assertTrue(
            substr($message, strlen($message) - 3) === 'DEF',
            'The last populated element cannot be followed by any character or element separator'
        );

        $expectedString = <<<EOF
BCD
002
1
SCT

Test
ABC
EUR1075.25


DEF
EOF;

        $this->assertSame($expectedString, $message);
    }

    public function testToString()
    {
        $sepaQrData = (new Data())
            ->setName('Test')
            ->setIban('ABC');

        $this->assertInternalType('string', (string)$sepaQrData);
    }

    public function testSetVersionExceptionCase1()
    {
        $this->expectException(InvalidArgumentException::class);

        $sepaQrData = new Data();
        $sepaQrData->setVersion(3);
    }

    public function testSetVersionExceptionCase2()
    {
        $sepaQrData = new Data();

        $this->expectException(InvalidArgumentException::class);

        $sepaQrData->setVersion('v1'); // @phpstan-ignore-line
    }
}
