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

class GoogleSaveClear implements ObserverInterface
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
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @param \HE\TwoFactorAuth\Helper\Data $twoFactorAuthHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \HE\TwoFactorAuth\Helper\Data $twoFactorAuthHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->twoFactorAuthHelper = $twoFactorAuthHelper;
        $this->logger = $logger;
        $this->request = $request;
        $this->_shouldLog = $this->twoFactorAuthHelper->shouldLog();
    }

    /**
     * Clear a user's google secret field if request
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // check that a user record has been saved

        // if google is turned and 2fa active...
        if ($this->twoFactorAuthHelper->getProvider() == 'google' && !$this->twoFactorAuthHelper->isDisabled()) {
            $params = $this->request->getParams();
            if (isset($params['clear_google_secret'])) {
                if ($params['clear_google_secret'] == 1) {
                    $object = $observer->getEvent()->getObject();
                    $object->setTwofactorGoogleSecret(''); // just clear the secret

                    $this->logger->log(\Monolog\Logger::EMERGENCY, "Clearing google secret for admin user (".$object->getUsername().")");
                }
            }
        }
    }
}