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
