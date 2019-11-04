define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list' ], function (Component, rendererList) {
    'use strict';
    rendererList.push(
        {
            type: 'imagineer_shippingcorreosdecostarica',
            component: 'Imagineer_ShippingCorreosDeCostaRica/js/view/method-renderer/calculoEnvio'
        }
    );
    /** Add view logic here if needed */
    return Component.extend({});
});
