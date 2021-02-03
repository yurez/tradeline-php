<?php

namespace LevelCredit\Tradeline\Mapping;

use LevelCredit\LevelCreditApi\Model\Response\OrderResourceResponse;
use LevelCredit\Tradeline\Model\OrderResponse;

class OrderResponseMapper
{
    /**
     * @param OrderResourceResponse $orderResourceResponse
     * @return OrderResponse
     */
    public static function map(OrderResourceResponse $orderResourceResponse): OrderResponse
    {
        return new OrderResponse(
            $orderResourceResponse->getResource()->getId(),
            $orderResourceResponse->getResource()->getReferenceId(),
            $orderResourceResponse->getResource()->getStatus(),
            $orderResourceResponse->getResource()->getTotal(),
            $orderResourceResponse->getResource()->getCreatedAt()
        );
    }
}
