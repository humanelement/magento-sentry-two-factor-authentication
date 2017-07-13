<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model\Sysconfig;

class Provider
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Options getter - creates a list of options from a list of providers in config.xml
     */
    public function toOptionArray()
    {
        // get the list of providers from the validator class
        $providersXML = $this->scopeConfig->getValue('he2faconfig/providers', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $providers = array();

        foreach($providersXML as $provider => $node) {
            $providers[] = array(
                'value' => $provider ,
                'label' => $node['title']
            );
        }

        return $providers;
    }
}
