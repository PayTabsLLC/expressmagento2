<?php

namespace PayTabs\Express\Controller\Standard;

/**
 * @category   PayTabs Payment
 * @package    PayTabs_Expressmagento2
 * @author     Support <support@paytabs.com>
 * @website    https://www.paytabs.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Success extends \PayTabs\Express\Controller\Standard
{
    /** @todo move it to Payment Model */
    protected function _validateResponse($response)
    {
        $extraField     = $response['opt'];
        $orderNo        = $response['ord'];
        $email          = $response['ceml'];
        $order          = $this->getOrder();

        if ( !$order || !$order->getId()) {
            return false;
        }

        if (/* !$this->_checkoutSession->getPayTabsSecretKey()
            || $this->_checkoutSession->getPayTabsSecretKey() != $extraField
            || */$orderNo != $order->getIncrementId()
            || $email != $order->getCustomerEmail()
        ) {
            return false;
        }

        return true;
    }

    public function execute()
    {
        $response = $this->getRequest()->getPostValue();
        $this->_paymentHelper->log(__METHOD__, true);
        $this->_paymentHelper->log(print_r($response, true));
        $isValidResponse = $this->_validateResponse($response);
        if ($isValidResponse) {
            $this->_preProcessOrder($this->getOrder(), __('Customer successfully returned from PayTabs.'));
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->messageManager->addError(__('There is some error processing your request.'));
            $this->_checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}