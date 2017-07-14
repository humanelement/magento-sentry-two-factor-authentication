<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;
use Monolog\Logger;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * TwoFactor disable flag file name
     *
     */
    const FLAG_FILENAME = '.tfaoff.flag';

    /**
     * TwoFactor disable flag dir
     */
    const FLAG_DIR = DirectoryList::VAR_DIR;

    /**
     * Path to store files
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $flagDir;

    /**
     * @var \HE\TwoFactorAuth\Model\Validate\Factory
     */
    protected $validateFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\Module\ModuleList
     */
    protected $moduleList;

    /**
     * @var string
     */
    protected $version;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \HE\TwoFactorAuth\Model\Validate\Factory $validateFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \HE\TwoFactorAuth\Model\Validate\Factory $validateFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    )
    {
        $this->validateFactory = $validateFactory;
        $this->storeManager = $storeManager;
        $this->flagDir = $filesystem->getDirectoryWrite(self::FLAG_DIR);
        $this->encryptor = $encryptor;
        $this->moduleList = $moduleList;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        if ($this->flagDir->isExist(self::FLAG_FILENAME)) {
            if ($this->shouldLog()) {
                $this->_logger->log(Logger::EMERGENCY, "isDisabled - Found tfaoff.flag, TFA disabled.");
            }

            return true;
        }

        $provider = $this->getProvider();

        if (!$provider || $provider == 'disabled') {
            return true;
        }

        $method = $this->validateFactory->create($provider);

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
        $ipWhiteList = $this->getIPWhitelist();

        if (in_array($ip, $ipWhiteList)) {
            if ($this->shouldLogAccess()) {
                $this->_logger->log(Logger::EMERGENCY, "TFA bypassed for IP $ip - whitelisted");
            }

            return true;
        }

        return false;
    }

    /**
     * Get Duo API Hostname
     *
     * @return string
     */
    public function getHost()
    {
        return $this->scopeConfig->getValue(
            'he2faconfig/duo/host',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the Duo Integration key
     *
     * @return string
     */
    public function getIKey()
    {
        return $this->encryptor->decrypt(
            $this->scopeConfig->getValue(
                'he2faconfig/duo/ikey',
                ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Get the Duo Application key
     *
     * @return string
     */
    public function getAKey()
    {
        return $this->encryptor->decrypt(
            $this->scopeConfig->getValue(
                'he2faconfig/duo/akey',
                ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Get the Duo Secret key
     *
     * @return string
     */
    public function getSKey()
    {
        return $this->encryptor->decrypt(
            $this->scopeConfig->getValue(
                'he2faconfig/duo/skey',
                ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Get whether duo is validated
     *
     * @return string
     */
    public function getIsDuoValidated()
    {
        return $this->scopeConfig->getValue(
            'he2faconfig/duo/validated',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get module version
     *
     * @return string
     */
    public function getVersion()
    {
        if (!$this->version) {
            $moduleName = $this->_getModuleName();

            if ($this->moduleList->has($moduleName)) {
                $moduleData = $this->moduleList->getOne($moduleName);

                if ($moduleData) {
                    $this->version = $moduleData['setup_version'];
                }
            }
        }

        return $this->version;
    }

    /**
     * Get a link
     *
     * @return string
     */
    public function getHeLink()
    {
        return $this->scopeConfig->getValue(
            'he2falinks/human-element-link',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get a link
     *
     * @return string
     */
    public function getNexcessLink()
    {
        return $this->scopeConfig->getValue(
            'he2falinks/nexcess-link',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get a link
     *
     * @return string
     */
    public function getDocsLink()
    {
        return $this->scopeConfig->getValue(
            'he2falinks/docs-link',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get a link
     *
     * @return string
     */
    public function getSubmitBugLink()
    {
        return $this->scopeConfig->getValue(
            'he2falinks/submit-bug-link',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get a link
     *
     * @return string
     */
    public function getMultiAuthLink()
    {
        return $this->scopeConfig->getValue(
            'he2falinks/multi-auth-link',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get a link
     *
     * @return string
     */
    public function getMageSupportLink()
    {
        return $this->scopeConfig->getValue(
            'he2falinks/mage-support-link',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get a link
     *
     * @return string
     */
    public function getContactLink()
    {
        return $this->scopeConfig->getValue(
            'he2falinks/contact-link',
            ScopeInterface::SCOPE_STORE
        );
    }
}