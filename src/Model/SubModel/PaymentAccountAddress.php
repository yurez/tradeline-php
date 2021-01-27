<?php

namespace LevelCredit\Tradeline\Model\SubModel;

class PaymentAccountAddress extends Address
{
    /**
     * @param string $street
     * @param string $city
     * @param string $state
     * @param string|int $zip
     */
    public function __construct(string $street, string $city, string $state, $zip)
    {
        $this->street = $street;
        $this->city = $city;
        $this->state = $state;
        $this->zip = $zip;
    }

    /**
     * @param string $street
     * @param string $city
     * @param string $state
     * @param string|int $zip
     * @return static
     */
    public static function create(string $street, string $city, string $state, $zip): self
    {
        return new static($street, $city, $state, $zip);
    }
}
