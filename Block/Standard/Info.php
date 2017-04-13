<?php

namespace PayTabs\Express\Block\Standard;

/**
 * @category   PayTabs Payment
 * @package    PayTabs_Expressmagento2
 * @author     Support <support@paytabs.com>
 * @website    https://www.paytabs.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'PayTabs_Express::standard/info.phtml';

}