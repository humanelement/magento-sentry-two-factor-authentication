<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model\Sysconfig;

use Magento\Framework\Exception\LocalizedException;

class Appkey extends \Magento\Config\Model\Config\Backend\Encrypted
{
    const MINIMUM_LENGTH = 40;

    /**
     * Check app key length before saving
     *
     * @TODO Check to see if Duo is enabled
     * @return mixed
     * @throws LocalizedException
     */
    public function save()
    {
        $appKey = $this->getValue();

        if (strlen($appKey) < self::MINIMUM_LENGTH) {
            throw new LocalizedException("The Duo application key needs to be at least 40 characters long.");
        }

        return parent::save();
    }

    /**
     * Generate an app key if one doesn't initially exist
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = (string)$this->getValue();

        if (empty($value)) {
            $key = $this->generateKey(self::MINIMUM_LENGTH);
            $this->setValue($key);
        }
    }

    /**
     * Generates a pseudo random key of specified length
     *
     * @param int $length
     * @return string
     */
    public function generateKey($length = self::MINIMUM_LENGTH)
    {
        $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $count = strlen($charset);
        $str = '';

        while ($length--) {
            $str .= $charset[mt_rand(0, $count-1)];
        }

        return $str;
    }
}