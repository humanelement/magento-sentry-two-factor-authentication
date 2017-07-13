<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Helper;

use Magento\Store\Model\ScopeInterface;
use Monolog\Logger;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \HE\TwoFactorAuth\Model\Validate\
     */
    protected $twoFactorAuthValidate;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \HE\TwoFactorAuth\Model\Validate $twoFactorAuthValidate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \HE\TwoFactorAuth\Model\Validate $twoFactorAuthValidate,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->twoFactorAuthValidate = $twoFactorAuthValidate;
        $this->storeManager = $storeManager;

        parent::__construct($context);

        $this->_ipWhitelist = $this->getIPWhitelist();
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        $tfaFlag = Mage::getBaseDir('base') . '/tfaoff.flag';
        $provider = $this->getProvider();

        if (file_exists($tfaFlag)) {
            if ($this->shouldLog()) {
                $this->_logger->log(Logger::EMERGENCY, "isDisabled - Found tfaoff.flag, TFA disabled.");
            }

            return true;
        }

        if (!$provider || $provider == 'disabled') {
            return true;
        }

        $method = $this->twoFactorAuthValidate;

        if (!$method) {
            return true;
        }

        return !$method->isValid();
    }

    /**
     * @return mixed
     */
    public function getProvider()
    {
        return $this->scopeConfig->getValue('he2faconfig/control/provider', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function shouldLog()
    {
        return $this->scopeConfig->getValue('he2faconfig/control/logging', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function shouldLogAccess()
    {
        return $this->scopeConfig->getValue('he2faconfig/control/logaccess', ScopeInterface::SCOPE_STORE);
    }

    /**
     *
     */
    public function disable2FA()
    {
        Mage::getModel('core/config')->saveConfig('he2faconfig/control/provider', 'disabled');
        $this->storeManager->getStore()->resetConfig();
    }

    /**
     * @return array
     */
    private function getIPWhitelist()
    {
        $return = [];
        $whitelist = $this->scopeConfig->getValue('he2faconfig/control/ipwhitelist', ScopeInterface::SCOPE_STORE);
        $ips = preg_split("/\r\n|\n|\r/", trim($whitelist));
        foreach ($ips as $ip) { 
            if (filter_var($ip, FILTER_VALIDATE_IP)) { 
                $return[] = trim($ip);
            }           
        }
        return $return;
    }

    /**
     * @param $ip
     * @return bool
     */
    public function inWhitelist($ip) 
    {
        if (count($this->_ipWhitelist) == 0) {
            return false;
        }

        if (in_array( $ip, $this->_ipWhitelist )) { 
            if ( $this->shouldLogAccess() ) {
                $this->_logger->log(Logger::EMERGENCY, "TFA bypassed for IP $ip - whitelisted");
            }
            return true;
        }
        else { 
            return false; 
        }
    }
}