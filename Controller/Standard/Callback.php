<?php

namespace PayTabs\Express\Controller\Standard;

/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Callback extends \PayTabs\Express\Controller\Standard
{
    public function execute()
    {
        $this->_paymentHelper->log(__METHOD__, true);
        $this->_paymentHelper->log(print_r($_REQUEST, true));

        $request = $this->getRequest();

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
            $this->_paymentHelper->log(
                __('PayTabs response doesn\'t not contain order id.')
            );
            return;
        }

        if (! ($order = $this->_orderFactory->create()->loadByIncrementId($orderId)) ) {
            $this->_paymentHelper->log(
                __('Order id: %s doesn\'t exist.', $orderId)
            );
            return;
        }

        // Create signature and check with secure_sign
        $secureParams = [
            'order_id'              => $orderId,
            'response_code'         => $responseCode,
            'customer_name'         => $customerName,
            'transaction_currency'  => $transactionCurrency,
            'last_4_digits'         => $last4Digits,
            'customer_email'        => $customerEmail
        ];
        $compareDigest = $this->_paymentHelper->createSecureHash($secureParams);

        if ($compareDigest != $secureSign) {
            $message = 'Signature doesn\'t match.';
            $this->_failOrder($order, $message);

            $this->_paymentHelper->log('Transaction failed for order::' . $orderId);
            $this->_paymentHelper->log('Reason => ' . $message);

            return;
        }

        $this->_paymentHelper->log('Successfull transaction for order::' . $orderId);
        $this->_paymentHelper->log('Order has been successfully paid via PayTabs.');

        $this->_processOrder($order, 'Order has been successfully paid via PayTabs.');

        // Register Transaction
        $order->getPayment()->setTransactionId($transactionId);

        $transaction = $this->_addPaymentTransaction($order, $request->getParams());

        // Set Last Transaction ID
        $order->getPayment()->setLastTransId($transactionId)->save();

        if ($invoice = $this->_createInvoice($order, false)) {
            $invoice->setTransactionId($transactionId);
            $invoice->save();
        }

        return true;
    }
}