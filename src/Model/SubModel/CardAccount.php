<?php

namespace LevelCredit\Tradeline\Model\SubModel;

abstract class CardAccount extends PaymentAccount
{
    /**
     * @var \DateTimeInterface
     */
    protected $expirationDate;

    /**
     * @var string
     */
    protected $securityCode;

    /**
     * @return \DateTimeInterface
     */
    public function getExpirationDate(): \DateTimeInterface
    {
        return $this->expirationDate;
    }

    /**
     * @return string
     */
    public function getSecurityCode(): string
    {
        return $this->securityCode;
    }
}
