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

require_once(Mage::getBaseDir('lib') . DS . 'GoogleAuthenticator' . DS . 'PHPGangsta' . DS . 'GoogleAuthenticator.php');

class Google extends \HE\TwoFactorAuth\Model\Validate
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
     * @var \Magento\User\Model\UserFactory
     */
    protected $userUserFactory;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * @var 
     */
    protected $;

    public function __construct(
        \HE\TwoFactorAuth\Helper\Data $twoFactorAuthHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\User\Model\UserFactory $userUserFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
         $
    )
    {
        $this-> = $;
        $this->twoFactorAuthHelper = $twoFactorAuthHelper;
        $this->logger = $logger;
        $this->userUserFactory = $userUserFactory;
        $this->backendAuthSession = $backendAuthSession;
        $this->_shouldLog = $this->twoFactorAuthHelper->shouldLog();
    }

    /**
     * HOTP - counter based
     * TOTP - time based
     */
    public function getToken($username, $tokentype = "TOTP")
    {
        $token = $this->setUser($username, $tokentype);
        if ($this->_shouldLog) {
            $this->logger->debug("token = " . var_export($token, true));
        }

        $user = $this->userUserFactory->create()->loadByUsername($username);
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
        $ga = $this->->create();
        $secret = $ga->createSecret();

        return $secret;
    }

    /**
     * generates and returns QR code URL from google
     */
    public function generateQRCodeUrl($secret, $username)
    {
        if ((empty($secret)) || (empty($username))) {
            return;
        }

        $ga = $this->->create();
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
        $admin_user = $this->userUserFactory->create()->load($user->getId());

        $ga = $this->->create();
        $secret = Mage::helper('core')->decrypt($admin_user->getTwofactorGoogleSecret());

        return $ga->verifyCode($secret, $code, 1);
    }

    /**
     * abstract function in GoogleAuthenticator, needs to be defined here TODO
     */
    function getDataBad($username, $index = null) // this was causing problems, not sure why...
    {
        $user = $this->userUserFactory->create()->loadByUsername($username);

        return $user->getTwofactorauthToken() == null ? false : $user->getTwofactorauthToken();
    }

    /**
     * abstract function in GoogleAuthenticator, needs to be defined here
     */
    function putData($username, $data)
    {
        $user = $this->userUserFactory->create()->loadByUsername($username);
        $user->setTwofactorauthToken("test");
        $user->save();
    }

    /**
     * abstract function in GoogleAuthenticator, needs to be defined here
     */
    function getUsers()
    {
    }
}
