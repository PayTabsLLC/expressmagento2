<?php

namespace PayTabs\Express\Controller\Standard;
class PaymentData extends \PayTabs\Express\Controller\Standard
{
    public function execute()
    {
        /*$testIncrementId    = $this->getRequest()->getParam('id');
        $order              = $this->_orderFactory->create()->loadByIncrementId($testIncrementId);*/

        $order              = $this->getOrder();

        /** @var \PayTabs\Express\Model\Payment\Standard $payment */
        $payment            = $order->getPayment()->getMethodInstance();
        $billingAddress     = $order->getBillingAddress();
        $shippingAddress    = ($order->getShippingAddress()) ? $order->getShippingAddress() : $billingAddress;

        $merchantId         = $this->_paymentHelper->getMerchantId();
        $secretKey          = $this->_paymentHelper->getSecretKey();

        // Get amount & currency based on settings (base or store)
        $baseCurrency       = $this->_paymentHelper->getBaseCurrency();
        $storeCurrency      = $order->getOrderCurrency()->getCurrencyCode();
        $currency           = $this->_paymentHelper->getGatewayCurrency($baseCurrency, $storeCurrency);

        $baseAmount         = $order->getBaseGrandTotal();
        $storeAmount        = $order->getGrandTotal();
        $amount             = $this->_paymentHelper->getGatewayAmount($baseAmount, $storeAmount);

        $title              = $billingAddress->getName();
        $productNamesArray  = [];
        $items              = $order->getAllVisibleItems();
        foreach($items as $item) {
            $productNamesArray[] = $item->getName();
        }
        $productNames = implode(',', $productNamesArray);
        $incrementId    = $order->getIncrementId();
        $returnUrl      = $payment->getReturnUrl();

        $arabicLocales  = ["ar_AE", "ar_BH", "ar_DZ", "ar_EG", "ar_IQ", "ar_JO", "ar_KW", "ar_LB", "ar_LY", "ar_MA", "ar_OM", "ar_QA", "ar_SA", "ar_SD", "ar_SY", "ar_TN", "ar_YE"];
        $language       = "en";
        $storeLocale    = $this->_paymentHelper->getStoreLocale();
        if (in_array($storeLocale, $arabicLocales)) {
            $language = "ar";
        }

        // Billing Details
        $firstname      = $billingAddress->getFirstname();
        $lastname       = $billingAddress->getLastname();
        $phoneNumber    = $billingAddress->getTelephone();
        $emailAddress   = $billingAddress->getEmail();
        $countryCode    = $billingAddress->getCountryId();
        $countryCallingCode = $this->_paymentHelper->getCallingCode($countryCode);
        $addresses      = $billingAddress->getStreet();
        $fullAddress    = implode(', ', $addresses);
        $city           = $billingAddress->getCity();
        $state          = $billingAddress->getRegion() ?: 'N/A';
        $country        = $this->_paymentHelper->getISO3CountryCode($billingAddress->getCountryId()); //ISO3
        $postalCode     = $billingAddress->getPostcode();

        // Shipping Details
        $shippingFirstname      = $shippingAddress->getFirstname();
        $shippingLastname       = $shippingAddress->getLastname();
        $shippingPhoneNumber    = $shippingAddress->getTelephone();
        $shippingEmailAddress   = $shippingAddress->getEmail();
        $shippingCountryCode    = $shippingAddress->getCountryId();
        $shippingAddresses      = $shippingAddress->getStreet();
        $shippingFullAddress    = implode(', ', $shippingAddresses);
        $shippingCity           = $shippingAddress->getCity();
        $shippingState          = $shippingAddress->getRegion() ?: 'N/A';
        $shippingCountry        = $this->_paymentHelper->getISO3CountryCode($shippingAddress->getCountryId()); //ISO3
        $shippingPostalCode     = $shippingAddress->getPostcode();

        // Button settings
        $btnWidth       = 127;
        $btnHeight      = 40;
        $linkToCss      = $this->_paymentHelper->getViewFileUrl('PayTabs_Express::css/payment/styles.css');
        $checkoutBtnImgUrl = $this->_paymentHelper->getPayButtonImgUrl();
        $payBtnImgUrl      = $this->_paymentHelper->getCheckoutButtonImgUrl();

        // Parse url
        $originUrl  = rtrim($this->_paymentHelper->getBaseUrl(), '/');
        $currentUrl = $this->_paymentHelper->getCurrentUrl();
        $urlParts   = parse_url($currentUrl);
        $scheme     = $urlParts['scheme'] ?: '';
        $host       = $urlParts['host'] ?: '';
        $path       = $urlParts['path'] ?: '';

        $paytabsData = [
            'settings' => [
                'merchant_id'       => $merchantId,
                'secret_key'        => $secretKey,
                'amount'            => $amount,
                'currency'          => $currency,
                'title'             => $title,
                'product_names'     => $productNames,
                'order_id'          => $incrementId,
                'url_redirect'      => rawurlencode($returnUrl), //rawurlencode
                //@todo move it to system settings
                'display_customer_info'     =>  0,
                'display_billing_fields'    =>  0,
                'display_shipping_fields'   =>  0,
                'language'                  =>  $language,
                'redirect_on_reject'        => 1,
                'style' => [
                    'css'       => 'custom',
                    'linktocss' => $linkToCss
                ],
                'is_iframe' => [
                    'load' => 'onbodyload',
                    'show' => 1
                ]
            ],
            'customer_info' => [
                'first_name'    => $firstname,
                'last_name'     => $lastname,
                'phone_number'  => $phoneNumber,
                'email_address' => $emailAddress,
                'country_code'  => $countryCallingCode,
            ],
            'billing_address' => [
                'full_address'  => $fullAddress,
                'city'          => $city,
                'state'         => $state,
                'country'       => $country,
                'postal_code'   => $postalCode
            ],
            'shipping_address' => [
                'full_address_shipping' => $shippingFullAddress,
                'city_shipping'         => $shippingCity,
                'state_shipping'        => $shippingState,
                'country_shipping'      => $shippingCountry,
                'postal_code_shipping'  => $shippingPostalCode
            ],
            'checkout_button' => [
                'width'     => $btnWidth,
                'height'    => $btnHeight,
                'img_url'   => $checkoutBtnImgUrl
            ],
            'pay_button' => [
                'width'     => $btnWidth,
                'height'    => $btnHeight,
                'img_url'   => $payBtnImgUrl
            ],
            'location' => [
                'host'      => $host,
                'hostname'  => $host,
                'href'      => rawurlencode($currentUrl), //rawurlencode
                'origin'    => $originUrl,
                'pathname'  => $path, //'/paytabs/standard/iframe',
                'protocol'  => $scheme . ':',
                'referrer'  => ''
            ]
        ];

        $this->_paymentHelper->log(__METHOD__, true);
        $this->_paymentHelper->log(var_export($paytabsData, true));

        $result = [
            'success' => true,
            'params' => $paytabsData
        ];
        echo json_encode($result);
        exit;
    }
}