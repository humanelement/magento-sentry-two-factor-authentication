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

class CheckTwoFactorActive implements ObserverInterface
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
    ) {
        $this->twoFactorAuthHelper = $twoFactorAuthHelper;
        $this->backendAuthSession = $backendAuthSession;
        $this->logger = $logger;
        $this->backendHelper = $backendHelper;
        $this->request = $request;
        $this->backendSession = $backendSession;
        $this->_shouldLog = $this->twoFactorAuthHelper->shouldLog();
    }

    /***
     * controller to check for valid 2fa
     * admin states
     *
     * @param $observer
     */

    public function execute(Observer $observer)
    {
        if ($this->twoFactorAuthHelper->isDisabled()) {
            return;
        }

        $request = $observer->getControllerAction()->getRequest();
        $tfaState = $this->backendAuthSession->get2faState();
        $action = $this->request->getActionName();

        switch ($tfaState) {
            case \HE\TwoFactorAuth\Model\Validate::TFA_STATE_NONE:
                if ($this->_shouldLog) {
                    $this->logger->log(\Monolog\Logger::EMERGENCY, "check_twofactor_active - tfa state none");
                }
                break;
            case \HE\TwoFactorAuth\Model\Validate::TFA_STATE_PROCESSING:
                if ($this->_shouldLog) {
                    $this->logger->log(\Monolog\Logger::EMERGENCY, "check_twofactor_active - tfa state processing");
                }
                break;
            case \HE\TwoFactorAuth\Model\Validate::TFA_STATE_ACTIVE:
                if ($this->_shouldLog) {
                    $this->logger->log(\Monolog\Logger::EMERGENCY, "check_twofactor_active - tfa state active");
                }
                break;
            default:
                if ($this->_shouldLog) {
                    $this->logger->log(\Monolog\Logger::EMERGENCY, "check_twofactor_active - tfa state unknown - ".$tfaState);
                }
        }

        if ($action == 'logout') {
            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "check_twofactor_active - logout");
            }
            $this->backendAuthSession->set2faState(\HE\TwoFactorAuth\Model\Validate::TFA_STATE_NONE);

            return $this;
        }

        if (in_array($action, $this->_allowedActions)) {
            return $this;
        }

        if ($request->getControllerName() == 'twofactor'
            || $tfaState == \HE\TwoFactorAuth\Model\Validate::TFA_STATE_ACTIVE
        ) {
            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "check_twofactor_active - return controller twofactor or is active");
            }

            return $this;
        }

        if ($this->backendAuthSession->get2faState() != \HE\TwoFactorAuth\Model\Validate::TFA_STATE_ACTIVE) {

            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "check_twofactor_active - not active, try again");
            }

            $msg = __(
                'You must complete Two Factor Authentication before accessing Magento administration'
            );
            $this->backendSession->addError($msg);

            // set we are processing 2f login
            $this->backendAuthSession->set2faState(\HE\TwoFactorAuth\Model\Validate::TFA_STATE_PROCESSING);

            $provider = $this->twoFactorAuthHelper->getProvider();
            $twoFactAuthPage = $this->backendHelper->getUrl("adminhtml/twofactor/$provider");

            //disable the dispatch for now
            $request = $this->request;
            $action = $request->getActionName();
            Mage::app()->getFrontController()
                ->getAction()
                ->setFlag($action, \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);

            $response = Mage::app()->getResponse();

            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "check_twofactor_active - redirect to $twoFactAuthPage");
            }

            $response->setRedirect($twoFactAuthPage)->sendResponse();
            exit();
        }
    }
}