<?php

namespace PayTabs\Express\Helper;

use Magento\Framework\Exception\LocalizedException;

/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAMESPACE_ALIAS    = 'payment';

    const XML_PATH_ENABLED              = 'paytabs_standard/active';
    const XML_PATH_DEBUG                = 'paytabs_standard/debug';
    const XML_PATH_FAILURE_STATUS       = 'paytabs_standard/order_status_failed';
    const XML_PATH_PAY_BUTTON_IMG       = 'paytabs_standard/pay_button_img';
    const XML_PATH_CHECKOUT_BUTTON_IMG  = 'paytabs_standard/checkout_button_img';
    const XML_PATH_GATEWAY_CURRENCY     = 'paytabs_standard/gateway_currency';
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_customLogger;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $customLogger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->_customLogger            = $customLogger;
        $this->_moduleList              = $moduleList;
        $this->_countryFactory          = $countryFactory;
        $this->_assetRepository         = $assetRepository;
        $this->_storeManager            = $storeManager;
        $this->_localeResolver          = $localeResolver;

        parent::__construct($context);
    }
    
    public function getMerchantId()
    {
        return $this->getConfigValue('paytabs_standard/merchant_id');
    }
    
    public function getSecretKey()
    {
        return $this->getConfigValue('paytabs_standard/secret_key');
    }

    public function getSecureHashKey()
    {
        return $this->getConfigValue('paytabs_standard/secure_hash_key');
    }

    public function isValid()
    {
        return true;
    }

    public function isFxnSkipped()
    {
        if (($this->isActive() && !$this->isValid()) || !$this->isActive()) {
            return true;
        }
        return false;
    }

    public function getDomainFromSystemConfig()
    {
        $websiteCode = $this->_getRequest()->getParam('website');
        $storeCode   = $this->_getRequest()->getParam('store');
        $xmlPath     = 'web/unsecure/base_url';
        if (!empty($storeCode)) {
            $domain = $this->scopeConfig->getValue(
                $xmlPath,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeCode
            );
        } else if (!empty($websiteCode)) {
            $domain = $this->scopeConfig->getValue(
                $xmlPath,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteCode
            );
        } else {
            $domain = $this->scopeConfig->getValue(
                $xmlPath
            );
        }
        return $domain;
    }

    /**
     * Get Config value
     *
     * @param $xmlPath
     * @param null $storeId
     * @return mixed
     */
    public function getConfigValue($xmlPath, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::MODULE_NAMESPACE_ALIAS . '/' . $xmlPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return $this->isEnabled($storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getDebugStatus($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_DEBUG, $storeId);
    }

    public function getFailureStatus($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FAILURE_STATUS, $storeId);
    }

    public function getPayButtonImgUrl($storeId = null)
    {
        if (!($payButtonImgUrl = $this->getConfigValue(self::XML_PATH_PAY_BUTTON_IMG, $storeId))) {
            $payButtonImgUrl = $this->getViewFileUrl('PayTabs_Express::images/button_pay-now.png');
        }
        return $payButtonImgUrl;
    }

    public function getCheckoutButtonImgUrl($storeId = null)
    {
        if (!($checkoutButtonImgUrl = $this->getConfigValue(self::XML_PATH_CHECKOUT_BUTTON_IMG, $storeId))) {
            $checkoutButtonImgUrl = $this->getViewFileUrl('PayTabs_Express::images/button_pay-now.png');
        }
        return $checkoutButtonImgUrl;
    }

    public function getGatewayCurrency($baseCurrency, $storeCurrency, $storeId = null)
    {
        $configCurrency  = $this->getConfigValue(self::XML_PATH_GATEWAY_CURRENCY, $storeId);
        $gatewayCurrency = $storeCurrency;
        if ($configCurrency == \PayTabs\Express\Model\Config\Source\Currency::TYPE_BASE) {
            $gatewayCurrency = $baseCurrency;
        }
        return $gatewayCurrency;
    }

    public function getGatewayAmount($baseAmount, $storeAmount, $storeId = null)
    {
        $configCurrency  = $this->getConfigValue(self::XML_PATH_GATEWAY_CURRENCY, $storeId);
        $gatewayAmount = $storeAmount;
        if ($configCurrency == \PayTabs\Express\Model\Config\Source\Currency::TYPE_BASE) {
            $gatewayAmount = $baseAmount;
        }
        return $gatewayAmount;
    }

    public function getExtensionVersion()
    {
        $moduleCode = 'PayTabs_Express';
        $moduleInfo = $this->_moduleList->getOne($moduleCode);
        return $moduleInfo['setup_version'];
    }

    /**
     * Logging Utility
     *
     * @param $message
     * @param bool|false $useSeparator
     */
    public function log($message, $useSeparator = false)
    {
        if ($this->getDebugStatus()) {
            if ($useSeparator) {
                $this->_customLogger->addDebug(str_repeat('=', 100));
            }

            $this->_customLogger->addDebug($message);
        }
    }

    //Get 3 Digit ISO Code for Country
    public function getISO3CountryCode($iso2Code)
    {
        if ($country = $this->_countryFactory->create()->loadByCode($iso2Code)) {
            return $country->getData('iso3_code');
        }
        return $iso2Code;
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->_request->isSecure()], $params);
            return $this->_assetRepository->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->_customLogger->critical($e);
            return $this->_urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }
    
    public function getStoreLocale()
    {
        return $this->_localeResolver->getLocale();
    }
    
    public function getCurrentUrl()
    {
        //return $this->_storeManager->getStore()->getCurrentUrl();
        return $this->_urlBuilder->getCurrentUrl();
    }
    
    public function getBaseUrl()
    {
        return $this->_urlBuilder->getBaseUrl();
    }

    public function getBaseCurrency()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    public function createSecureHash($params)
    {
        $shaInPhrase = $this->getSecureHashKey(); //'secure@paytabs#@aaes11%%';
        ksort($params);

        $digest = '';
        foreach ($params as $key => $value) {
            $digest .= strtoupper($key) . '=' . $value . $shaInPhrase;
        }

        return sha1($digest);
    }
}