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

class GoogleClearSecretCheck implements ObserverInterface
{
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
     * Add a fieldset and field to the admin edit user form
     * in order to allow selective clearing of a users shared secret (google)
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (!isset($block)) {
            return $this;
        }

        if ($block->getType() == 'adminhtml/permissions_user_edit_form') {

            // check that google is set for twofactor authentication
            if ($this->twoFactorAuthHelper->getProvider() == 'google') {
                //create new custom fieldset 'website'
                $form = $block->getForm();
                $fieldset = $form->addFieldset(
                    'website_field', array(
                        'legend' => 'Google Authenticator',
                        'class'  => 'fieldset-wide'
                    )
                );

                $fieldset->addField(
                    'checkbox', 'checkbox', array(
                        'label'              => __(
                            'Reset Google Authenticator'
                        ),
                        'name'               => 'clear_google_secret',
                        'checked'            => false,
                        'onclick'            => "",
                        'onchange'           => "",
                        'value'              => '1',
                        'disabled'           => false,
                        'after_element_html' => '<small>Check this and save to reset this user\'s Google Authenticator.<br />They will need to use the QR code to reconnect their device after their next successful login.</small>',
                        'tabindex'           => 1
                    )
                );
            }
        }
    }
}