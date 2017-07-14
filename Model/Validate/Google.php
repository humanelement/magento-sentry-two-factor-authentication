<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 * @author Aric Watson <https://www.nexcess.net>
 * @link https://github.com/PHPGangsta/GoogleAuthenticator Some code based on previous work by Michael Kliewe/PHPGangsta
 * @link http://www.phpgangsta.de/ Some code based on previous work by Michael Kliewe/PHPGangsta
 * @link https://github.com/google/google-authenticator/wiki For more information on Google Authenticator
 * @license GPL
 * @license https://www.gnu.org/copyleft/gpl.html
 */

namespace HE\TwoFactorAuth\Model\Validate;

class Google extends \HE\TwoFactorAuth\Model\AbstractValidate
{

    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $userFactory;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * @var \PHPGangsta_GoogleAuthenticatorFactory
     */
    protected $googleAuthenticatorFactory;

    /**
     * Google constructor.
     * @param \HE\TwoFactorAuth\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\User\Model\UserFactory $userFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \PHPGangsta_GoogleAuthenticatorFactory $googleAuthenticatorFactory
     * @param array $data
     */
    public function __construct(
        \HE\TwoFactorAuth\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \PHPGangsta_GoogleAuthenticatorFactory $googleAuthenticatorFactory,
        array $data = []
    )
    {
        $this->userFactory = $userFactory;
        $this->backendAuthSession = $backendAuthSession;
        $this->googleAuthenticatorFactory = $googleAuthenticatorFactory;

        parent::__construct($helper, $logger, $data);
    }

    /**
     * HOTP - counter based
     * TOTP - time based
     *
     * @param $username
     * @param string $tokenType
     */
    public function getToken($username, $tokenType = "TOTP")
    {
        $token = $this->setUser($username, $tokenType);
        if ($this->helper->shouldLog()) {
            $this->logger->debug("token = " . var_export($token, true));
        }

        $user = $this->userFactory->create()->loadByUsername($username);
        $user->setTwofactorauthToken($token);
        //$user->save(); //password gets messed up after saving?!
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return true;
    }

    /**
     * generates and returns a new shared secret
     */
    public function generateSecret()
    {
        /** @var \PHPGangsta_GoogleAuthenticator $ga */
        $ga = $this->googleAuthenticatorFactory->create();
        $secret = $ga->createSecret();

        return $secret;
    }

    /**
     * generates and returns QR code URL from google
     */
    public function generateQRCodeUrl($secret, $username)
    {
        if ((empty($secret)) || (empty($username))) {
            return '';
        }

        /** @var \PHPGangsta_GoogleAuthenticator $ga */
        $ga = $this->googleAuthenticatorFactory->create();
        $url = $ga->getQRCodeGoogleUrl($username, $secret);

        return $url;
    }

    /**
     * verifies the code using TOTP
     */
    public function validateCode($code)
    {
        if (empty($code)) {
            return;
        }
        $this->logger->log(\Monolog\Logger::EMERGENCY, "Google - validateCode: ".$code);

        // get user's shared secret
        $user = $this->backendAuthSession->getUser();
        $admin_user = $this->userFactory->create()->load($user->getId());

        $ga = $this->googleAuthenticatorFactory->create();
        $secret = Mage::helper('core')->decrypt($admin_user->getTwofactorGoogleSecret());

        return $ga->verifyCode($secret, $code, 1);
    }

    /**
     * abstract function in GoogleAuthenticator, needs to be defined here TODO
     */
    function getDataBad($username, $index = null) // this was causing problems, not sure why...
    {
        $user = $this->userFactory->create()->loadByUsername($username);

        return $user->getTwofactorauthToken() == null ? false : $user->getTwofactorauthToken();
    }

    /**
     * abstract function in GoogleAuthenticator, needs to be defined here
     */
    function putData($username, $data)
    {
        $user = $this->userFactory->create()->loadByUsername($username);
        $user->setTwofactorauthToken("test");
        $user->save();
    }

    /**
     * abstract function in GoogleAuthenticator, needs to be defined here
     */
    function getUsers()
    {
    }

    /**
     * @param $user
     */
    public function signRequest($user)
    {
        // TODO: Implement signRequest() method.
    }

    /**
     * @param $response
     */
    public function verifyResponse($response)
    {
        // TODO: Implement verifyResponse() method.
    }
}
