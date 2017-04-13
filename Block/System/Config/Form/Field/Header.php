<?php

namespace PayTabs\Express\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * @category   PayTabs Payment
 * @package    PayTabs_Expressmagento2
 * @author     Support <support@paytabs.com>
 * @website    https://www.paytabs.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Header extends \Magento\Backend\Block\Template implements RendererInterface
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return sprintf('<tr id="row_%s"><td colspan="5"><div class="section-config"><div class="entry-edit-head admin__collapsible-block">%s</div></div></td></tr>',
            $element->getHtmlId(), $element->getLabel()
        );
    }
}