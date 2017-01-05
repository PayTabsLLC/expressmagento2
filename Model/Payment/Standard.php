<?php

namespace PayTabs\Express\Model\Payment;

/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Standard extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'paytabs_standard';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var string
     */
    protected $_formBlockType = 'PayTabs\Express\Block\Standard\Form';

    /**
     * @var string
     */
    protected $_infoBlockType = 'PayTabs\Express\Block\Standard\Info';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * Allowed Currencies
     * @var array
     */
    protected $_supportedCurrencyCodes = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Exception\LocalizedExceptionFactory
     */
    protected $_exception;
    
    /**
     * @var \PayTabs\Express\Helper\Data
     */
    protected $_paymentHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Exception\LocalizedExceptionFactory $exception,
        \PayTabs\Express\Helper\Data $paymentHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_storeManager     = $storeManager;
        $this->_urlBuilder       = $urlBuilder;
        $this->_checkoutSession  = $checkoutSession;
        $this->_exception        = $exception;
        $this->_paymentHelper    = $paymentHelper;

        $this->_initialize();
    }

    /**
     * Init required parameters
     */
    protected function _initialize()
    {
        $this->_supportedCurrencyCodes = ['USD', 'AUD'];
    }

    /**
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($this->_paymentHelper->isFxnSkipped()) {
            return false;
        }
        return parent::isAvailable($quote);
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    /**
     * Get logo display settings
     *
     * @return string
     */
    public function getDisplayLogoType()
    {
        return $this->getConfigData('display_logo_type');
    }

    public function buildRequest(\Magento\Sales\Model\Order $order)
    {
        $formFields = array();

        //prepare variables hidden form fields
        $formFields['ord'] = $order->getIncrementId();
        $formFields['des'] = 'Online Order from ' . $order->getStore()->getName();
        $formFields['amt'] = number_format($order->getBaseGrandTotal(), 2);

        $currency = $order->getOrderCurrency();
        if (is_object($currency)) {
            $currency = $currency->getCurrencyCode();
        }
        $formFields['cur']  = $currency;
        $formFields['frq']  = 'Once Only';
        $this->_checkoutSession->setPayTabsSecretKey(md5(time()));
        $formFields['opt']  = $this->_checkoutSession->getPayTabsSecretKey();

        $customerEmail = $order->getCustomerEmail();
        if (empty($customerEmail)) {
            $customerEmail = $order->getBillingAddress()->getEmail();
        }
        $formFields['ceml'] = $customerEmail;
        $formFields['ret']  = $this->getSuccessUrl();

        $this->_paymentHelper->log(print_r($formFields, true));

        return $formFields;
    }

    public function getRedirectUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->getConfigData('redirect_url'),
            ['_secure' => true]
        );
    }

    public function getSuccessUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->getConfigData('success_url'),
            ['_secure' => true]
        );
    }

    public function getFailureUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->getConfigData('failure_url'),
            ['_secure' => true]
        );
    }

    public function getCancelUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->getConfigData('cancel_url'),
            ['_secure' => true]
        );
    }

    public function getReturnUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->getConfigData('return_url'),
            ['_secure' => true]
        );
    }

    public function getCallbackUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->getConfigData('callback_url'),
            ['_secure' => true]
        );
    }

    public function getPaymentDataUrl()
    {
        return $this->_urlBuilder->getUrl(
            'paytabs/standard/paymentData',
            ['_secure' => true]
        );
    }

    public function getGatewayUrl()
    {
        if ($this->getConfigData('sandbox')) {
            return $this->_urlBuilder->getUrl(
                $this->getConfigData('gateway_url_sandbox')
            );
        } else {
            return $this->_urlBuilder->getUrl(
                $this->getConfigData('gateway_url')
            );
        }
    }

}