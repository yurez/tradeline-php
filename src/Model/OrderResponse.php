<?php

namespace LevelCredit\Tradeline\Model;

use LevelCredit\Tradeline\Enum\OrderStatus;

class OrderResponse
{
    /**
     * @var int will be encoded order ID returned by LevelCredit API
     */
    protected $id;

    /**
     * @var string payment processor transaction Id
     */
    protected $referenceId;

    /**
     * @var string
     * @see OrderStatus
     */
    protected $status;

    /**
     * @var string on money format XXX.XX
     */
    protected $amount;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @param int $id
     * @param string $referenceId
     * @param string $status
     * @param string $amount
     * @param \DateTime $createdAt
     */
    public function __construct(int $id, string $referenceId, string $status, string $amount, \DateTime $createdAt)
    {
        $this->id = $id;
        $this->referenceId = $referenceId;
        $this->status = $status;
        $this->amount = $amount;
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    /**
     * @return string
     * @see OrderStatus
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
