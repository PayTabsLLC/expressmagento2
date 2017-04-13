<?php
namespace PayTabs\Express\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * @category   PayTabs Payment
 * @package    PayTabs_Expressmagento2
 * @author     Support <support@paytabs.com>
 * @website    https://www.paytabs.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    const EXTENSION_URL = 'https://www.paytabs.com/';

    /**
     * @var \PayTabs\Express\Helper\Data $helper
     */
    protected $_helper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \PayTabs\Express\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \PayTabs\Express\Helper\Data $helper
    ) {
        $this->_helper = $helper;
        parent::__construct($context);
    }


    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $extensionVersion = $this->_helper->getExtensionVersion();
        $extensionTitle   = 'PayTabs';
        $versionLabel     = sprintf(
                '<a href="%s" title="%s" target="_blank">%s</a>',
                self::EXTENSION_URL,
                $extensionTitle,
                $extensionVersion
        );
        $element->setValue($versionLabel);

        return $element->getValue();
    }
}