<?php

namespace PayTabs\Express\Controller;

/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class Standard extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \PayTabs\Express\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var \PayTabs\Express\Model\Payment\Standard
     */
    protected $_paymentMethod;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $_orderManagement;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \PayTabs\Express\Helper\Data $paymentHelper,
        \PayTabs\Express\Model\Payment\Standard $paymentMethod,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        $this->_customerSession     = $customerSession;
        $this->_checkoutSession     = $checkoutSession;
        $this->_quoteFactory        = $quoteFactory;
        $this->_orderFactory        = $orderFactory;
        $this->_paymentHelper       = $paymentHelper;
        $this->_paymentMethod       = $paymentMethod;
        $this->_orderManagement     = $orderManagement;
        $this->_logger              = $loggerInterface;
        $this->_invoiceService      = $invoiceService;
        $this->_transaction         = $transaction;

        parent::__construct($context);
    }

    /**
     * @param $orderId
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrderById($orderId)
    {
        return $this->_orderFactory->create()->loadByIncrementId(
            $orderId
        );
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }

    protected function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_checkoutSession->getQuote();
        }
        return $this->_quote;
    }

    protected function _preProcessOrder(\Magento\Sales\Model\Order $order, $message = '')
    {
        $order->addStatusHistoryComment(
            $message,
            \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
        );
        $order->save();
    }

    protected function _addStatusHistory(
        \Magento\Sales\Model\Order $order,
        $message,
        $status = false
    ) {
        $order->addStatusHistoryComment(
            $message,
            $status
        );
        $order->save();
    }

    protected function _cancelOrder(\Magento\Sales\Model\Order $order, $message = '')
    {
        if ($order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED) {
            $order->cancel();
            $order->addStatusHistoryComment($message);
            $order->save();
        }
    }

    protected function _failOrder(\Magento\Sales\Model\Order $order, $message = '')
    {
        if ($order->getState() != $this->_paymentHelper->getFailureStatus()) {
            $order->setStatus($this->_paymentHelper->getFailureStatus());
            $order->setState($this->_paymentHelper->getFailureStatus());
            $order->save();

            $order->addStatusToHistory($this->_paymentHelper->getFailureStatus(), $message, false);
            $order->save();
        }
    }

    protected function _processOrder(\Magento\Sales\Model\Order $order, $message = '')
    {
        if ($order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING) {
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->save();
            $customerNotified = $this->_sendOrderEmail($order);
            $order->addStatusToHistory(
                \Magento\Sales\Model\Order::STATE_PROCESSING ,
                $message,
                $customerNotified
            );
            $order->save();

            return true;
        }
        return false;
    }

    protected function _createInvoice(\Magento\Sales\Model\Order $order, $online = false)
    {
        if ($order->canInvoice()) {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(
                $online ? \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE : \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE
            );
            $invoice->register();
            $invoice->save();

            $transaction = $this->_transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transaction->save();
            $this->invoiceSender->send($invoice);

            //send notification code
            $order->addStatusHistoryComment(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )
            ->setIsCustomerNotified(true)
            ->save();
            return $invoice;
        }
        return false;
    }

    protected function _addPaymentTransaction(\Magento\Sales\Model\Order $order, array $details = [])
    {
        $transaction = $order->getPayment()->addTransaction(
            \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH,
            null,
            true
        );
        $transaction->setIsClosed(0);
        $transaction->setAdditionalInformation(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $details
        );
        $transaction->save();
        return $transaction;
    }

    protected function _sendOrderEmail(\Magento\Sales\Model\Order $order)
    {
        $result = true;
        try{
            if($order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING) {
                $orderCommentSender = $this->_objectManager
                    ->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
                $orderCommentSender->send($order, true, '');
            }
            else{
                $this->_orderManagement->notify($order->getEntityId());
            }
        } catch (\Exception $e) {
            $result = false;
            $this->_logger->critical($e);
        }

        return $result;
    }
}