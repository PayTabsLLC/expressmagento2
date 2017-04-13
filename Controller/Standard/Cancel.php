<?php

namespace PayTabs\Express\Controller\Standard;

/**
 * @category   PayTabs Payment
 * @package    PayTabs_Expressmagento2
 * @author     Support <support@paytabs.com>
 * @website    https://www.paytabs.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Cancel extends \PayTabs\Express\Controller\Standard
{
    public function execute()
    {
        #var_dump($_REQUEST); exit;
        $this->_paymentHelper->log(__METHOD__, true);
        $this->_paymentHelper->log(print_r($_REQUEST, true));
        $message = __('There is some error processing your request.');
        $this->_cancelOrder($this->getOrder(), $message);

        $this->messageManager->addError($message);
        $this->_checkoutSession->restoreQuote();
        $this->_redirect('checkout/cart');
    }
}