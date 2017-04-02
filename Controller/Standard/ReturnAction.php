<?php

namespace PayTabs\Express\Controller\Standard;

/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ReturnAction extends \PayTabs\Express\Controller\Standard
{
    const RESPONSE_CODE_SUCCESS = 100;

    // Generic Return Url
    public function execute()
    {
        // Test:
        // ?transaction_id=49751&order_id=000000010&response_code=100&customer_name=Raj Bhtt&transaction_currency=USD&last_4_digits=1111&customer_email=magepsycho@gmail.com&secure_sign=fefadacfafc597fa8c0d86d6898e8bd7eb47b592

        $request = $this->getRequest();

        $this->_paymentHelper->log(__METHOD__, true);
        $this->_paymentHelper->log(var_export($request->getParams(), true));

        $transactionId          = $request->getParam('transaction_id');
        $orderId                = $request->getParam('order_id');
        $responseCode           = $request->getParam('response_code');
        $responseMessage        = $request->getParam('response_message');
        $customerName           = $request->getParam('customer_name');
        $customerEmail          = $request->getParam('customer_email');
        $transactionAmount      = $request->getParam('transaction_amount');
        $transactionCurrency    = $request->getParam('transaction_currency');
        $customerPhone          = $request->getParam('customer_phone');
        $last4Digits            = $request->getParam('last_4_digits');
        $first4Digits           = $request->getParam('first_4_digits');
        $cardBrand              = $request->getParam('card_brand');
        $transDate              = $request->getParam('trans_date');
        $secureSign             = $request->getParam('secure_sign');

        if (empty($orderId)) {
            $this->messageManager->addError(__('PayTabs response doesn\'t not contain order id.'));
            $this->_checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
            return;
        }

        if (! ($order = $this->_orderFactory->create()->loadByIncrementId($orderId)) ) {
            $this->messageManager->addError(__('Order id: %s doesn\'t exist.', $orderId));
            $this->_checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
            return;
        }

        $apiUrl         = 'https://www.paytabs.com/apiv2/verify_payment_transaction';
        $dataString     = $this->_prepareCurlPostData($transactionId);

        /*$this->_paymentHelper->log(var_export($curlResult, true));
        $this->_paymentHelper->log(var_export($curlError, true));*/

        $result = $this->_paymentHelper->curlPost($apiUrl, $dataString);
        if (
            $transactionId
            && isset($result['response_code'])
            && $result['response_code'] == self::RESPONSE_CODE_SUCCESS
        ) {
            // @todo wrap in a method @see Callback::execute()
            $this->_processOrder($order, 'Order has been successfully paid via PayTabs.');
            // Create Sales Payment
            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId)
                    ->setLastTransId($transactionId)
                    ->setAdditionalData(serialize($request->getParams()))
                    ->setAdditionalInformation(
                        [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $request->getParams()]
                    )
                //->setStatus()
            ;
            $payment->save();

            // Create Payment Transaction
            $this->_createTransaction($order, $request->getParams());

            // Create Sales Invoice
            $invoice = null;
            if (!$order->hasInvoices()) {
                $invoice = $order->prepareInvoice();
                $invoice->register();

                $invoice->setTransactionId($transactionId);
                $order->addRelatedObject($invoice);
                $invoice->save();
            }

            // Update Order status & comment and send email
            $order->setStatus($order::STATE_PROCESSING);
            $order->setState($order::STATE_PROCESSING);
            $order->save();

            $customerNotified = $this->_sendOrderEmail($order);
            $order->addStatusToHistory($order::STATE_PROCESSING , 'Payment has been captured via PayTabs.', $customerNotified);
            $order->save();

            // Send invoice email
            if ($invoice) {
                $this->_sendInvoiceEmail($invoice);
            }

            $this->_redirect('checkout/onepage/success');
            return;
        } else {
            $message = $result['result'] ?: 'Unknown exception occured.';
            $this->_failAndRedirect($order, $message);
            return;
        }
    }

    protected function _prepareCurlPostData($transactionId)
    {
        $fields = [
            'merchant_email'    => $this->_paymentHelper->getMerchantEmail(),
            'secret_key'        => $this->_paymentHelper->getSecretKey(),
            'transaction_id'    => $transactionId
        ];
        $fieldsString = '';
        foreach ($fields as $key => $value) {
            $fieldsString .= $key . '=' . $value . '&';
        }
        return rtrim($fieldsString, '&');
    }

    protected function _failAndRedirect($order, $message)
    {
        $this->_failOrder($order, $message);

        $this->_paymentHelper->log('Transaction failed for order::' . $order->getId());
        $this->_paymentHelper->log('Reason => ' . $message);

        $this->messageManager->addError(__($message));
        $this->_checkoutSession->restoreQuote();
        $this->_redirect('checkout/cart');
        return;
    }
}