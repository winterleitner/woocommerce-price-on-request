jQuery(function($) {
    // Attach an event to the 'Add to Cart' button
    $('body').on('click', '.single_add_to_cart_button, .button.product_type_simple', function(e) {
        const button = $(this);
        const buttonText = button.text().trim();

        // Check if the button text is "Anfrage"
        if (buttonText === 'Anfrage') {
            e.preventDefault(); // Prevent the default action

            const productId = button.val() || button.attr('data-product_id');

            // Perform the AJAX request to add the product to the cart
            $.post(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'), {
                product_id: productId
            }, function(response) {
                if (!response) {
                    return;
                }
                if (response.error && response.product_url) {
                    window.location = response.product_url;
                    return;
                }

                // Redirect to the cart page after adding to cart
                window.location.href = redirect_to_cart_params.cart_url;
            });
        }
    });
});