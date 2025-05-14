<?php

namespace SepaQr;

use InvalidArgumentException;

/**
 * @implements \Stringable
 */
class Data
{
    const UTF_8 = 1;
    const ISO8859_1 = 2;
    const ISO8859_2 = 3;
    const ISO8859_4 = 4;
    const ISO8859_5 = 5;
    const ISO8859_7 = 6;
    const ISO8859_10 = 7;
    const ISO8859_15 = 8;

    /**
     * @var array<string, int|float|string>
     */
    private $sepaValues = [
        'serviceTag' => 'BCD',
        'version' => 2,
        'characterSet' => 1,
        'identification' => 'SCT'
    ];

    /**
     * @param string $currency
     * @param float $value
     * @return string
     */
    public static function formatMoney($currency = 'EUR', $value = 0)
    {
        return sprintf(
            '%s%s',
            strtoupper($currency),
            $value > 0 ? number_format($value, 2, '.', '') : ''
        );
    }

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @param string $serviceTag
     * @return static
     */
    public function setServiceTag($serviceTag = 'BCD')
    {
        if ($serviceTag !== 'BCD') {
            throw new InvalidArgumentException('Invalid service tag');
        }

        $this->sepaValues['serviceTag'] = $serviceTag;

        return $this;
    }

    /**
     * @param int $version
     * @return static
     */
    public function setVersion($version = 2)
    {
        if (!in_array($version, range(1, 2))) {
            throw new InvalidArgumentException('Invalid version');
        }

        $this->sepaValues['version'] = $version;

        return $this;
    }

    /**
     * @param int $characterSet
     * @return static
     */
    public function setCharacterSet($characterSet = self::UTF_8)
    {
        if (!in_array($characterSet, range(1, 8))) {
            throw new InvalidArgumentException('Invalid character set');
        }

        $this->sepaValues['characterSet'] = $characterSet;

        return $this;
    }

    /**
     * @param string $identification
     * @return static
     */
    public function setIdentification($identification = 'SCT')
    {
        if ($identification !== 'SCT') {
            throw new InvalidArgumentException('Invalid identification code');
        }

        $this->sepaValues['identification'] = $identification;

        return $this;
    }

    /**
     * @param string $bic
     * @return static
     */
    public function setBic($bic)
    {
        if (strlen($bic) !== 8 && strlen($bic) !== 11) {
            throw new InvalidArgumentException('BIC of the beneficiary can only be 8 or 11 characters');
        }

        $this->sepaValues['bic'] = $bic;

        return $this;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName($name)
    {
        if (strlen($name) > 70) {
            throw new InvalidArgumentException('Name of the beneficiary cannot be longer than 70 characters');
        }

        $this->sepaValues['name'] = $name;

        return $this;
    }

    /**
     * @param string $iban
     * @return static
     */
    public function setIban($iban)
    {
        if (strlen($iban) > 34) {
            throw new InvalidArgumentException('Account number of the beneficiary cannot be longer than 34 characters');
        }

        $this->sepaValues['iban'] = $iban;

        return $this;
    }

    /**
     * @param string $currency
     * @return static
     */
    public function setCurrency($currency)
    {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency of the credit transfer can only be a valid ISO 4217 code');
        }

        $this->sepaValues['currency'] = $currency;

        return $this;
    }

    /**
     * @param float $amount
     * @return static
     */
    public function setAmount($amount)
    {
        if ($amount < 0.01) {
            throw new InvalidArgumentException('Amount of the credit transfer cannot be smaller than 0.01 Euro');
        }

        if ($amount > 999999999.99) {
            throw new InvalidArgumentException('Amount of the credit transfer cannot be higher than 999999999.99 Euro');
        }

        $this->sepaValues['amount'] = $amount;

        return $this;
    }

    /**
     * @param string $purpose
     * @return static
     */
    public function setPurpose($purpose)
    {
        if (strlen($purpose) !== 4) {
            throw new InvalidArgumentException('Purpose code can only be 4 characters');
        }

        $this->sepaValues['purpose'] = $purpose;

        return $this;
    }

    /**
     * @param string $remittanceReference
     * @return static
     */
    public function setRemittanceReference($remittanceReference)
    {
        if (strlen($remittanceReference) > 35) {
            throw new InvalidArgumentException('Structured remittance information cannot be longer than 35 characters');
        }

        if (isset($this->sepaValues['remittanceText'])) {
            throw new InvalidArgumentException('Use either structured or unstructured remittance information');
        }

        $this->sepaValues['remittanceReference'] = (string)$remittanceReference;

        return $this;
    }

    /**
     * @param string $remittanceText
     * @return static
     */
    public function setRemittanceText($remittanceText)
    {
        if (strlen($remittanceText) > 140) {
            throw new InvalidArgumentException('Unstructured remittance information cannot be longer than 140 characters');
        }

        if (isset($this->sepaValues['remittanceReference'])) {
            throw new InvalidArgumentException('Use either structured or unstructured remittance information');
        }

        $this->sepaValues['remittanceText'] = $remittanceText;

        return $this;
    }

    /**
     * @param string $information
     * @return static
     */
    public function setInformation($information)
    {
        if (strlen($information) > 70) {
            throw new InvalidArgumentException('Beneficiary to originator information cannot be longer than 70 characters');
        }

        $this->sepaValues['information'] = $information;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $defaults = array(
            'bic' => '',
            'name' => '',
            'iban' => '',
            'currency' => 'EUR',
            'amount' => 0,
            'purpose' => '',
            'remittanceReference' => '',
            'remittanceText' => '',
            'information' => ''
        );

        $values = array_merge($defaults, $this->sepaValues);

        if ($values['version'] === 1 && !$values['bic']) {
            throw new InvalidArgumentException('Missing BIC of the beneficiary bank');
        }

        if (!$values['name']) {
            throw new InvalidArgumentException('Missing name of the beneficiary');
        }

        if (!$values['iban']) {
            throw new InvalidArgumentException('Missing account number of the beneficiary');
        }

        return rtrim(implode("\n", array(
            $values['serviceTag'],
            sprintf('%03d', $values['version']),
            $values['characterSet'],
            $values['identification'],
            $values['bic'],
            $values['name'],
            $values['iban'],
            static::formatMoney((string)$values['currency'], (float)$values['amount']),
            $values['purpose'],
            $values['remittanceReference'],
            $values['remittanceText'],
            $values['information']
        )), "\n");
    }
}
