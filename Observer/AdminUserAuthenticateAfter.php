<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AdminUserAuthenticateAfter implements ObserverInterface
{
    /**
     * @var array
     */
    protected $_allowedActions = array('login', 'forgotpassword');

    /**
     * @var \HE\TwoFactorAuth\Helper\Data
     */
    protected $twoFactorAuthHelper;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @param \HE\TwoFactorAuth\Helper\Data $twoFactorAuthHelper
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Backend\Model\Session $backendSession
     */
    public function __construct(
        \HE\TwoFactorAuth\Helper\Data $twoFactorAuthHelper,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Backend\Model\Session $backendSession
    )
    {
        $this->twoFactorAuthHelper = $twoFactorAuthHelper;
        $this->backendAuthSession = $backendAuthSession;
        $this->logger = $logger;
        $this->backendHelper = $backendHelper;
        $this->request = $request;
        $this->backendSession = $backendSession;
        $this->_shouldLog = $this->twoFactorAuthHelper->shouldLog();
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->twoFactorAuthHelper->isDisabled()) {
            return;
        }

        // check ip-whitelist
        if ($this->twoFactorAuthHelper->inWhitelist( Mage::helper('core/http')->getRemoteAddr() )) {
            $this->backendAuthSession->set2faState(\HE\TwoFactorAuth\Model\Validate::TFA_STATE_ACTIVE);
        }

        if ($this->backendAuthSession->get2faState() != \HE\TwoFactorAuth\Model\Validate::TFA_STATE_ACTIVE) {

            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "authenticate_after - get2faState is not active");
            }

            // set we are processing 2f login
            $this->backendAuthSession->set2faState(\HE\TwoFactorAuth\Model\Validate::TFA_STATE_PROCESSING);

            $provider = $this->twoFactorAuthHelper->getProvider();

            //redirect to the 2f login page
            $twoFactAuthPage = $this->backendHelper->getUrl("adminhtml/twofactor/$provider");

            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "authenticate_after - redirect to $twoFactAuthPage");
            }

            Mage::app()->getResponse()
                ->setRedirect($twoFactAuthPage)
                ->sendResponse();
            exit();
        } else {
            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "authenticate_after - getValid2Fa is true");
            }
        }
    }
}