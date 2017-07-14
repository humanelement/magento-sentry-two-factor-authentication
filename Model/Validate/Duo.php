<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 * @author Greg Croasdill <info@human-element.com>
 * @link https://www.duosecurity.com or more information on Duo security's API
 * @license GPL
 * @license https://www.gnu.org/copyleft/gpl.html
 */

namespace HE\TwoFactorAuth\Model\Validate;

use Duo\Web;
use Magento\Store\Model\ScopeInterface;

class Duo extends \HE\TwoFactorAuth\Model\AbstractValidate
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \HE\TwoFactorAuth\Model\Validate\Duo\RequestFactory
     */
    protected $duoRequestFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * Duo constructor.
     *
     * @param \HE\TwoFactorAuth\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Backend\Model\Session $backendSession
     * @param Duo\RequestFactory $duoRequestFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param array $data
     */
    public function __construct(
        \HE\TwoFactorAuth\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\Session $backendSession,
        \HE\TwoFactorAuth\Model\Validate\Duo\RequestFactory $duoRequestFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        array $data = []
    )
    {
        $this->backendSession = $backendSession;
        $this->duoRequestFactory = $duoRequestFactory;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;

        parent::__construct($helper, $logger, $data);

        if (!($this->getHost() && $this->helper->getIKey() && $this->helper->getSKey() && $this->helper->getAKey())) {
            $this->helper->disable2FA();
            $msg = __('Duo Twofactor Authentication is missing one or more settings. Please configure HE Two Factor Authentication.');
            $this->backendSession->addError($msg);
        }
    }

    /**
     * @return mixed
     */
    public function isValid()
    {
        $status = self::TFA_CHECK_FAIL;

        //TODO - Use provider based checks instead of hardcoding for Duo
        if (!$this->duoRequestFactory->create()->ping()) {
            $msg = __('Can not connect to specified Duo API server - TFA settings not validated');
        } elseif (!$this->duoRequestFactory->create()->check()) {
            $msg = __('Credentials for Duo API server not accepted, please check - TFA settings not validated');
        } else {
            $status = self::TFA_CHECK_SUCCESS;
            $msg = __('Credentials for Duo API server accepted - TFA settings validated');
        }

        //let the user know the status
        if ($status == self::TFA_CHECK_SUCCESS) {
            //Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            if ($this->helper->shouldLog()) {
                $this->logger->log(\Monolog\Logger::ERROR, "isValid - $msg.");
            }
            $newMode = __('VALID');
        } else {
            $this->backendSession->addError($msg);
            if ($this->helper->shouldLog()) {
                $this->logger->log(\Monolog\Logger::INFO, "isValid - $msg.");
            }
            $newMode = __('NOT VALID');
        }

        //if mode changed, update config
        if ($newMode <> $this->helper->getIsDuoValidated()) {
            Mage::getModel('core/config')->saveConfig('he2faconfig/duo/validated', $newMode);
            $this->storeManager->getStore()->resetConfig();
        }

        return $status;
    }

    public function signRequest($user)
    {
        if ($this->helper->shouldLog()) {
            $this->logger->log(\Monolog\Logger::EMERGENCY, "in signRequest with $user");
        }

        $sig_request = Web::signRequest(
            $this->helper->getIKey(),
            $this->helper->getSKey(),
            $this->helper->getAKey(),
            $user
        );

        return $sig_request;
    }

    /**
     * @param $response
     * @return bool
     */
    public function verifyResponse($response)
    {
        $verified = Web::verifyResponse(
            $this->helper->getIKey(),
            $this->helper->getSKey(),
            $this->helper->getAKey(),
            $response
        );

        return ($verified != null);
    }
}