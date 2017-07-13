<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Model\Sysconfig;

use Magento\Store\Model\ScopeInterface;

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
     * Creates a list of two factor providers
     *
     * @todo Should refactor providers in a way so that other extensions can add to the providers selection
     * @return array
     */
    public function toOptionArray()
    {
        // Get the list of providers from the validator class
        $providersXML = $this->scopeConfig->getValue('he2faconfig/providers', ScopeInterface::SCOPE_STORE);
        $providers = [];

        foreach ($providersXML as $provider => $node) {
            $providers[] = [
                'value' => $provider ,
                'label' => $node['title']
            ];
        }

        return $providers;
    }
}
