<?php
/**
 * Human Element Inc.
 *
 * @package HE_TwoFactorAuth
 * @copyright Copyright (c) 2017 Human Element Inc. (https://www.human-element.com)
 */

namespace HE\TwoFactorAuth\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Renderer for Hint Banner in System Configuration
 */
class Hint extends Template implements RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'HE_TwoFactorAuth::system/config/fieldset/hint.phtml';

    /**
     * @var \HE\TwoFactorAuth\Helper\Data
     */
    protected $twoFactorAuthHelper;

    /**
     * Hint constructor.
     *
     * @param Template\Context $context
     * @param \HE\TwoFactorAuth\Helper\Data $twoFactorAuthHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \HE\TwoFactorAuth\Helper\Data $twoFactorAuthHelper,
        array $data = []
    )
    {
        $this->twoFactorAuthHelper = $twoFactorAuthHelper;
        parent::__construct($context, $data);
    }

    /**
     * Render fieldset html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * Remove http from start of link so it can be used as a label in the paragraph
     *
     * @param string $link
     * @return mixed
     */
    public function simplifyLinkLabel($link)
    {
        $link = str_replace('https://www.', '', $link);
        $link = str_replace('http://www.', '', $link);
        return $link;
    }
}