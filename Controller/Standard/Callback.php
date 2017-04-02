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
    // For Testing purpose only
    /*protected function getParam($key)
    {
        $params = array (
            'transaction_id' => '58624',
            'order_id' => '000000037',
            'response_code' => '100',
            'response_message' => 'Approved',
            'customer_name' => 'Raj KB',
            'customer_email' => 'magepsychostore@gmail.com',
            'transaction_amount' => '27.00',
            'transaction_currency' => 'USD',
            'customer_phone' => 'AE +97122334455',
            'last_4_digits' => '1111',
            'first_4_digits' => '4111',
            'card_brand' => 'Visa',
            'trans_date' => '24-02-2017 04:30:54 PM',
            'secure_sign' => '8e13f293c67688eda3d2c2d641bce7a17d13522d',
        );
        return isset($params[$key]) ? $params[$key] : '';
    }*/

    public function execute()
    {
        if (!$this->_paymentHelper->isIpnActive()) {
            $this->_paymentHelper->log(__METHOD__, true);
            $this->_paymentHelper->log('IPN::DISABLED');
            return $this;
        }
        $this->_paymentHelper->log(__METHOD__, true);
        $this->_paymentHelper->log(print_r($_REQUEST, true));

        $request = $this->getRequest();

        $transactionId        = $request->getParam('transaction_id');
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
            return $this;
        }

        if (! ($order = $this->_orderFactory->create()->loadByIncrementId($orderId)) ) {
            $this->_paymentHelper->log(
                __('Order id: %s doesn\'t exist.', $orderId)
            );
            return $this;
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

            return $this;
        }

        // @todo wrap in a method @see ReturnAction::execute()
        $this->_paymentHelper->log('Order has been successfully paid via PayTabs.');
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

        $subject = sprintf('IPN transaction details for order #: %s', $order->getIncrementId());
        $body    = sprintf('Dear Admin, <br />IPN transaction response for order #: <br />', $order->getIncrementId());
        $body   .= $this->_paymentHelper->tabularize($request->getParams());
        $this->notificationHelper->sendNotificationEmail($subject, $body);

        return true;
    }
}