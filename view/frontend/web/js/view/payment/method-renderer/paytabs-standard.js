/**
 * @category   MagePsycho
 * @package    PayTabs_Express
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    http://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (Component, $, validator, messageList, fullScreenLoader) {

        'use strict';

        return Component.extend({
            defaults: {
                template: 'PayTabs_Express/payment/paytabs-standard',
                redirectAfterPlaceOrder: false //Compatible with CE 2.1.0
            },

            /** Returns payment method instructions */
            getInstructions: function() {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },

            getDisplayLogoClass: function() {
                return window.checkoutConfig.payment.logoClass[this.item.method];
            },

            getIframeUrl: function() {
                return window.checkoutConfig.payment.iframeUrl[this.item.method];
            },

            redirectAfterPlaceOrder: false,

            getCode: function() {
                return 'paytabs_standard';
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            },

            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                console.log('PayTabs After Place Order');
                console.log(window.checkoutConfig.payment.ajaxUrl[this.item.method]);
                $.ajax({
                    url:        window.checkoutConfig.payment.ajaxUrl[this.item.method],
                    type:       'get',
                    context:    this,
                    dataType:   'json',
                    success:    function(response) {
                        console.log(response);
                        var ValidJSON = JSON.stringify(response.params);
                        var b = window.btoa(unescape(encodeURIComponent(ValidJSON)));
                        var src = "https://www.paytabs.com/expressv3/authentication/" + encodeURIComponent(b);
                        console.log('Start loading iframe...');
                        $('.actions-toolbar.mp-paytabs').hide();
                        $('#paytabs-iframe-container').show();
                        $('.payment-method-content.mp-paytabs').css('min-height', '350px');
                        $('#PT_express_checkout_wrap').attr('src', src);
                        console.log('Done! loading iframe...');
                        $('.payment-method-billing-address.mp-paytabs').hide();
                        $('.loading-mask').hide();
                        return false;
                    }
                });
                return false;
            }
        });
    }
);