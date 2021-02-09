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

namespace LevelCredit\Tradeline\Tests\Unit\Mapping;

use LevelCredit\LevelCreditApi\Model\Response\OrderResourceResponse;
use LevelCredit\LevelCreditApi\Model\Response\Resource\Order;
use LevelCredit\Tradeline\Enum\OrderStatus;
use LevelCredit\Tradeline\Mapping\OrderResponseMapper;
use LevelCredit\Tradeline\Model\OrderResponse;
use PHPUnit\Framework\TestCase;

class OrderResponseMapperTest extends TestCase
{
    /**
     * @test
     */
    public function shouldMapApiOrderResourceResponseToOrderResponse(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(12345);
        $orderMock
            ->expects($this->once())
            ->method('getReferenceId')
            ->willReturn('transaction_id_12345');
        $orderMock
            ->expects($this->once())
            ->method('getTotal')
            ->willReturn(12.01);
        $orderMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(OrderStatus::COMPLETE);
        $orderMock
            ->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($createdAt = new \DateTime());

        $orderResourceResponseMock = $this->createMock(OrderResourceResponse::class);
        $orderResourceResponseMock
            ->expects($this->exactly(5))
            ->method('getResource')
            ->willReturn($orderMock);

        $result = OrderResponseMapper::map($orderResourceResponseMock);

        $this->assertInstanceOf(OrderResponse::class, $result);
        $this->assertEquals(12345, $result->getId());
        $this->assertEquals('transaction_id_12345', $result->getReferenceId());
        $this->assertEquals(OrderStatus::COMPLETE, $result->getStatus());
        $this->assertEquals(12.01, $result->getAmount());
        $this->assertEquals($createdAt, $result->getCreatedAt());
    }
}
