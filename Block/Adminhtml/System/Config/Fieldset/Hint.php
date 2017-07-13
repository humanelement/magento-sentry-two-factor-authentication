<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Backend\Block\AbstractBlock;

/**
 * Renderer for Hint Banner in System Configuration
 */
class Hint extends AbstractBlock implements RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'he_twofactor/system/config/fieldset/hint.phtml';

    /**
     * Render fieldset html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * @return string
     */
    public function getTwoFactorAuthVersion()
    {
        return (string)Mage::getConfig()->getNode('modules/HE_TwoFactorAuth/version');
    }
}