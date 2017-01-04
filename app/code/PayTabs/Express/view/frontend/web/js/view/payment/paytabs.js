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
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'paytabs_standard',
                component: 'PayTabs_Express/js/view/payment/method-renderer/paytabs-standard'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);