<?php

namespace LevelCredit\Tradeline\Model;

class OrderResponse
{
    /**
     *
     * @var int will be encoded order ID returned by LevelCredit API
     */
    protected $id;

    /**
     * @var string payment processor transaction Id
     */
    protected $referenceId;

    /**
     * @var string PENDING|COMPLETE
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
