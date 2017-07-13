<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model;

class Validate extends \Magento\Framework\Model\AbstractModel
{
    const TFA_STATE_NONE = 0;
    const TFA_STATE_PROCESSING = 1;
    const TFA_STATE_ACTIVE = 2;

    const TFA_CHECK_FAIL = 0;
    const TFA_CHECK_SUCCESS = 1;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @param $user
     */
    public function signRequest($user)
    {
    }

    /**
     * @param $response
     */
    public function verifyResponse($response)
    {
    }

    /**
     * @return int
     */
    public function isValid()
    {
        return $this::TFA_CHECK_FAIL;
    }
}