( function( $ ) {
    'use strict';

    var zelleGateways = zelleGateway.gatewayIdsList;

    $( document.body ).bind( 'init_checkout', function( data ) {
        let payment_method_selected = function ( data ) {

            var selectedGateway = $("input:radio[name=payment_method]:checked").val()

            if( zelleGateways.includes( selectedGateway ) ) {

                $('#place_order').addClass('zelle-personal-checkout');

            } else {
                $('#place_order').removeClass('zelle-personal-checkout');
            }
        };

        // Only fires on payment method changed
        $( document.body ).bind( 'payment_method_selected', payment_method_selected );

        // This is needed for setting on page load.
        $( 'form.checkout' ).on( 'click', 'input[name="payment_method"]', function( data ) {
            payment_method_selected(data);
            // TODO: unhook this
        } );

    });

})( jQuery );