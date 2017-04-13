<?php

namespace PayTabs\Express\Controller\Standard;

use Magento\Framework\Controller\ResultFactory;

/**
 * @category   PayTabs Payment
 * @package    PayTabs_Expressmagento2
 * @author     Support <support@paytabs.com>
 * @website    https://www.paytabs.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Redirect extends \PayTabs\Express\Controller\Standard
{
    public function execute()
    {
        $order = $this->getOrder();
        if (!$order || !$order->getId()) {
            $this->_checkoutSession->restoreQuote();
            $this->messageManager->addError(__('No order was found for processing.'));
            $this->_redirect('checkout/cart');
            return;
        }

        /* (Valid & Redirect Case)
        // Validate the data before redirecting
        $paymentMethod  = $order->getPayment()->getMethodInstance();
        $redirectUrl    = $paymentMethod->validateAndGetReturnUrl($order);

        if (!$redirectUrl) {
            $this->session->restoreQuote();
            $this->messageManager->addError(__('Something went wrong. Please Contact the Website Administrator for more information.'));
            $this->_redirect('checkout/cart');
            return;
        }
        */

        // Add Comment to Order
        $order->addStatusToHistory(
            $order->getStatus(),
            'Customer was redirected to PayTabs.'
        );
        $order->save();

        /* (Simply Redirection Case) */
        $redirectBlock = $this->_view->getLayout()
            ->createBlock('PayTabs\Express\Block\Standard\Redirect')
            ->setOrder($order)
            ->toHtml();
        $this->getResponse()->setBody($redirectBlock);

        /* (Valid & Redirect Case)
        // Redirect to Gateway after validation
        return $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
        )->setUrl(
            $redirectUrl
        );
        */
    }
}