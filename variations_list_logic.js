jQuery(document).ready(function($) {
    $(document).on('click', '.sh8der_variations_list_item__add_cart_btn', function(event) {
        event.preventDefault();
        var $thisButton = $(this);
        var thisProductData = {
            action: 'woocommerce_add_to_cart_variable_rc',
            product_id: $thisButton.data('product-id'),
            variation_id: $thisButton.data('variation-id'),
            variation: $thisButton.data('variation-att-val'),
            quantity: $thisButton.data('quantity'),
        }
        $('body').trigger('adding_to_cart', [$thisButton, thisProductData]);
        $thisButton.removeClass('added');
        $thisButton.addClass('loading');
        $.post(wc_add_to_cart_params.ajax_url, thisProductData, function(response) {
            if (!response)
                return;
            if ( response.fragments ) {
                    $.each(response.fragments, function(key, value) {
                        $(key).replaceWith(value);
                    });
                }
            $thisButton.removeClass('loading');
            $('body').trigger( 'added_to_cart', [ response.fragments, response.cart_hash ] );
        });
    });
});
