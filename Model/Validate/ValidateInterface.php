<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model;

interface ValidateInterface
{
    /**
     * @param $user
     */
    public function signRequest($user);

    /**
     * @param $response
     * @return bool
     */
    public function verifyResponse($response);

    /**
     * @return int
     */
    public function isValid();
}