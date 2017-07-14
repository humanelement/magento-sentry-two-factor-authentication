<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model;

use HE\TwoFactorAuth\Model\Validate\ValidateInterface;
use Magento\Framework\DataObject;

abstract class AbstractValidate extends DataObject implements ValidateInterface
{
    /**
     * @var \HE\TwoFactorAuth\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * AbstractValidate constructor.
     * @param \HE\TwoFactorAuth\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        \HE\TwoFactorAuth\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    )
    {
        $this->helper = $helper;
        $this->logger = $logger;

        parent::__construct($data);
    }

    /**
     * @return int
     */
    public function isValid()
    {
        return self::TFA_CHECK_FAIL;
    }
}