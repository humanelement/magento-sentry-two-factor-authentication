<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 * @license GPL (https://www.gnu.org/copyleft/gpl.html)
 */

namespace HE\TwoFactorAuth\Controller\Adminhtml;

class Verify extends \Magento\Backend\App\Action
{
    /**
     * @var \HE\TwoFactorAuth\Helper\Data
     */
    protected $twoFactorAuthHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \HE\TwoFactorAuth\Model\Validate\DuoFactory
     */
    protected $twoFactorAuthValidateDuoFactory;

    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $userUserFactory;

    /**
     * @var \HE\TwoFactorAuth\Model\Validate\GoogleFactory
     */
    protected $twoFactorAuthValidateGoogleFactory;

    /**
     * @var \HE\TwoFactorAuth\Model\Validate\Duo\RequestFactory
     */
    protected $twoFactorAuthValidateDuoRequestFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \HE\TwoFactorAuth\Helper\Data $twoFactorAuthHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\App\Request\Http $request,
        \HE\TwoFactorAuth\Model\Validate\DuoFactory $twoFactorAuthValidateDuoFactory,
        \Magento\User\Model\UserFactory $userUserFactory,
        \HE\TwoFactorAuth\Model\Validate\GoogleFactory $twoFactorAuthValidateGoogleFactory,
        \HE\TwoFactorAuth\Model\Validate\Duo\RequestFactory $twoFactorAuthValidateDuoRequestFactory
    ) {
        $this->twoFactorAuthHelper = $twoFactorAuthHelper;
        $this->logger = $logger;
        $this->backendSession = $backendSession;
        $this->backendAuthSession = $backendAuthSession;
        $this->request = $request;
        $this->twoFactorAuthValidateDuoFactory = $twoFactorAuthValidateDuoFactory;
        $this->userUserFactory = $userUserFactory;
        $this->twoFactorAuthValidateGoogleFactory = $twoFactorAuthValidateGoogleFactory;
        $this->twoFactorAuthValidateDuoRequestFactory = $twoFactorAuthValidateDuoRequestFactory;

        $this->_shouldLog = $this->twoFactorAuthHelper->shouldLog();
        $this->_shouldLogAccess = $this->twoFactorAuthHelper->shouldLogAccess();
        parent::__construct($context);
    }

    /***
     * verify is a generic action, looks at the current config to get provider, then dispatches correct verify method
     *
     * @return $this
     */
    public function execute()
    {
        if ($this->_shouldLog) {
            $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyAction start");
        }

        if ($this->_shouldLogAccess) {
            $ipAddress = Mage::helper('core/http')->getRemoteAddr();
            $adminName = $this->backendAuthSession->getUser()->getUsername();

            $this->logger->log(\Monolog\Logger::EMERGENCY, "TFA Verify attempt for admin account $adminName from IP $ipAddress");
        }

        $provider = $this->twoFactorAuthHelper->getProvider();

        $verifyProcess = '_verify' . ucfirst($provider);

        if (method_exists($this, $verifyProcess)) {
            $this->$verifyProcess();
        } else {
            $this->twoFactorAuthHelper->disable2FA();
            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyAction - Unsupported provider $provider. Two factor Authentication is disabled");
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function _verifyDuo()
    {
        $duoSigResp = $this->request->getPost('sig_response', null);

        $validate = $this->twoFactorAuthValidateDuoFactory->create();

        if ($validate->verifyResponse($duoSigResp) === false) {
            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyDuo - failed verify");
            }

            if ($this->_shouldLogAccess) {
                $ipAddress = Mage::helper('core/http')->getRemoteAddr();
                $adminName = $this->backendAuthSession->getUser()->getUsername();

                $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyDuo - TFA Verify attempt FAILED for admin account $adminName from IP $ipAddress");
            }

            //TODO - make status message area on template
            $msg = __(
                'verifyDuo - Two Factor Authentication has failed. Please try again or contact an administrator.'
            );
            $this->backendSession->addError($msg);

            $this->_redirect('adminhtml/twofactor/duo');

            return $this;
        }

        if ($this->_shouldLog) {
            $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyDuo - Duo Validated");
        }
        if ($this->_shouldLogAccess) {
            $ipAddress = Mage::helper('core/http')->getRemoteAddr();
            $adminName = $this->backendAuthSession->getUser()->getUsername();

            $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyDuo - TFA Verify attempt SUCCEEDED for admin account $adminName from IP $ipAddress");
        }


        $this->backendAuthSession
            ->set2faState(\HE\TwoFactorAuth\Model\Validate\ValidateInterface::TFA_STATE_ACTIVE);
        $this->_redirect('*');

        return $this;
    }

    /**
     * @return $this
     */
    private function _verifyGoogle()
    {
        if ($this->_shouldLog) {
            $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyAction - start Google validate");
        }
        $params = $this->getRequest()->getParams();

        $ipAddress = Mage::helper('core/http')->getRemoteAddr();
        $adminName = $this->backendAuthSession->getUser()->getUsername();

        // save the user's shared secret 
        if ((!empty($params['google_secret'])) && (strlen($params['google_secret']) == 16)) {
            $user = $this->backendAuthSession->getUser();
            $admin_user = $this->userUserFactory->create()->load($user->getId());
            $admin_user->setTwofactorGoogleSecret(Mage::helper('core')->encrypt($params['google_secret']));
            $admin_user->save();
            if (($this->_shouldLog) || ($this->_shouldLogAccess)) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyGoogle - new google secret saved for admin account $adminName from IP $ipAddress");
            }

            // redirect back to login, now they'll need to enter the code.
            $msg = __("Please enter your input code.");
            $this->backendSession->addError($msg);
            $this->_redirect('adminhtml/twofactor/google');

            return $this;
        } else {
            // check the key
            // Test to make sure the parameter exists and remove any spaces
            if (array_key_exists('input_code', $params)) {
                $gcode = str_replace(' ', '', $params['input_code']);
            } else {
                $gcode = '';
            }

            // TODO add better error checking and flow!
            if ((strlen($gcode) == 6) && (is_numeric($gcode))) {
                if ($this->_shouldLog) {
                    $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyGoogle - checking input code '".$gcode."'");
                }
                $g2fa = $this->twoFactorAuthValidateGoogleFactory->create();
                $goodCode = $g2fa->validateCode($gcode);
                if ($goodCode) {
                    if ($this->_shouldLogAccess) {

                        $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyGoogle - TFA Verify attempt SUCCESSFUL for admin account $adminName from IP $ipAddress");
                    }

                    $msg = __("Valid code entered");
                    $this->backendSession->addSuccess($msg);
                    $this->backendAuthSession->set2faState(\HE\TwoFactorAuth\Model\Validate\ValidateInterface::TFA_STATE_ACTIVE);
                    $this->_redirect('*');

                    return $this;
                } else {
                    if ($this->_shouldLogAccess) {
                        $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyGoogle - TFA Verify attempt FAILED for admin account $adminName from IP $ipAddress");
                    }
                    $msg = __("Invalid code entered");
                    $this->backendSession->addError($msg);
                    $this->_redirect('adminhtml/twofactor/google');

                    return $this;
                }
            } else {
                if ($this->_shouldLogAccess) {
                    $this->logger->log(\Monolog\Logger::EMERGENCY, "verifyGoogle - TFA Verify attempt FAILED for admin account $adminName from IP $ipAddress");
                }
                $msg = __("Invalid code entered");
                $this->backendSession->addError($msg);
                $this->_redirect('adminhtml/twofactor/google');

                return $this;
            }
        }
    }

    /***
     * verify is a generic action, looks at the current config to get provider, then dispatches correct verify method
     *
     * @return $this
     */
    public function validateAction()
    {
        if ($this->_shouldLog) {
            $this->logger->log(\Monolog\Logger::EMERGENCY, "validateAction start");
        }
        $provider = $this->twoFactorAuthHelper->getProvider();

        $validateProcess = '_validate' . ucfirst($provider);

        if (method_exists($this, $validateProcess)) {
            $this->$validateProcess();
        } else {
            $this->twoFactorAuthHelper->disable2FA();
            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "validateAction - Unsupported provider $provider. Two factor Authentication is disabled");
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function _validateDuo()
    {
        if ($this->_shouldLog) {
            $this->logger->log(\Monolog\Logger::EMERGENCY, "validateAction starting");
        }

        $validate = $this->twoFactorAuthValidateDuoRequestFactory->create();

        if ($validate->ping() == false) {
            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "validateDuo - ValidateAction ping fail - can not communicate with Duo auth server");
            }

            $msg = __(
                'Can not connect to authentication server. Two Factor Authentication has been disabled.'
            );
            $this->backendSession->addError($msg);

        } elseif ($validate->check() == false) {
            if ($this->_shouldLog) {
                $this->logger->log(\Monolog\Logger::EMERGENCY, "validateDuo - ValidateAction check fail - can not communicate with Duo auth server");
            }

            $msg = __(
                'Can not connect to authentication server. Two Factor Authentication has been disabled.'
            );
            $this->backendSession->addError($msg);
        }

        $this->_redirect('*');

        return $this;
    }
}