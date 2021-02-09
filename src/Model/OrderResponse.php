<?php

/* Copyright(c) 2021 by RentTrack, Inc.  All rights reserved.
 *
 * This software contains proprietary and confidential information of
 * RentTrack Inc., and its suppliers.  Except as may be set forth
 * in the license agreement under which this software is supplied, use,
 * disclosure, or  reproduction is prohibited without the prior express
 * written consent of RentTrack, Inc.
 *
 * The license terms of service are hosted at https://github.com/levelcredit/tradeline-php/blob/master/LICENSE
 */

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
