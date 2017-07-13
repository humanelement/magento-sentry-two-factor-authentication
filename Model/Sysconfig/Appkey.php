<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model\Sysconfig;

class Appkey extends \Magento\Config\Model\Config\Backend\Encrypted
{
    /**
     * @return mixed
     */
    public function save()
    {
        // TODO - check to see if Duo is enabled
        $appkey = $this->getValue(); //get the value from our config

        if(strlen($appkey) < 40)   //exit if we're less than 50 characters
        {
            throw new \Magento\Framework\Exception\LocalizedException("The Duo application key needs to be at least 40 characters long.");
        }
        return parent::save();  //call original save method so whatever happened
    }

    /**
     *
     */
    protected function _afterLoad()
    {
        $value = (string)$this->getValue();
        if (empty($value)) {
            $key = $this->generateKey(40);
            $this->setValue($key);
        }
    }

    /**
     * @param int $length
     * @return string
     */
    public function generateKey($length=40)
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