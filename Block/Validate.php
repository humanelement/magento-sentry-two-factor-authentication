<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Block;

use Magento\Framework\View\Element\Template\Context;

class Validate extends \Magento\Framework\View\Element\Template
{
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function getSaveUrl()
    {
        return $this->getUrl('twofactorauth/interstitial/verify');
    }
}
