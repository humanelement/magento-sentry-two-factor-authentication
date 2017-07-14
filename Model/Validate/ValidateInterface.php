<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model\Validate;

interface ValidateInterface
{
    const TFA_STATE_NONE = 0;

    const TFA_STATE_PROCESSING = 1;

    const TFA_STATE_ACTIVE = 2;

    const TFA_CHECK_FAIL = 0;

    const TFA_CHECK_SUCCESS = 1;

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