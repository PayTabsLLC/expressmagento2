<?php

namespace PayTabs\Express\Model\Payment;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * @category   PayTabs Payment
 * @package    PayTabs_Expressmagento2
 * @author     Support <support@paytabs.com>
 * @website    https://www.paytabs.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        Standard::CODE
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment']['redirectUrl'][$code]  = $this->getRedirectUrl($code);
                $config['payment']['logoClass'][$code]    = $this->getDisplayLogoClass($code);
                $config['payment']['iframeUrl'][$code]    = $this->getIFrameUrl($code);
                $config['payment']['ajaxUrl'][$code]      = $this->getPaymentDataUrl($code);
            }
        }
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    /**
     * Get display logo type from config
     *
     * @param string $code
     * @return string
     */
    protected function getDisplayLogoClass($code)
    {
        $logoDisplayType = $this->methods[$code]->getDisplayLogoType();
        switch ($logoDisplayType) {
            case \PayTabs\Express\Model\Config\Source\Logotype::LOGO_TYPE_PAYTABS;
                $logoClass = 'logo-paytabs';
                break;
            default:
                $logoClass = 'logo-none';
                break;
        }
        return $logoClass;
    }

    protected function getRedirectUrl($code)
    {
        return $this->methods[$code]->getRedirectUrl();
    }

    protected function getPaymentDataUrl($code)
    {
        return $this->methods[$code]->getPaymentDataUrl();
    }

    protected function getIframeUrl($code)
    {
        return 'https://www.paytabs.com/expressv3/authentication/' . $this->getAuthenticationKey($code);
    }

    protected function getAuthenticationKey($code)
    {
        return 'XXX';
    }
}