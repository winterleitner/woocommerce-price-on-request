<?php
/**
 * @package Woocommerce_Price_On_Request
 * @version 1.0
 */
/*
Plugin Name: Woocommerce Price On Request
Plugin URI: https://github.com/winterleitner/woocommerce-price-on-request
Description: A Wordpress plugin that allows you to hide the price of a product and display a "Price on request" message instead.
Version: 1.0
Author: Felix Winterleitner
Author URI: https://winterleitner.github.com
License: MIT
*/

function is_anfrage_product($product = null)
{
    if ($product == null) global $product;
    // Only apply to products with a price of 0 or null
    if (!$product || ($product->get_price() != 0)) return false;
    try {
        // Get tag IDs
        $tag_ids = $product->get_tag_ids();

        foreach ($tag_ids as $tag_id) {
            $term = get_term($tag_id, 'product_tag');
            if (!is_wp_error($term) && $term) {
                if ($term->name == 'Anfrage') return true;
            }
        }
    } catch (Exception $e) {
        // ignore
    }
    return false;
}

# Product Price
add_filter('woocommerce_get_price_html', 'change_product_price_label');
add_filter('woocommerce_cart_item_price', 'change_product_price_label');
function change_product_price_label($price)
{
    if (is_anfrage_product()) return __('Preis auf Anfrage', 'woocommerce'); // 'Price on request
    return $price;
}


# Add To Cart
add_filter('woocommerce_product_single_add_to_cart_text', 'change_add_to_cart_text');
add_filter('woocommerce_product_add_to_cart_text', 'change_add_to_cart_text');
function change_add_to_cart_text($text)
{
    if (is_anfrage_product()) return __('Anfrage', 'woocommerce');
    return $text;
}


# Cart
add_filter('woocommerce_cart_item_price', 'conditional_cart_item_price_display', 10, 3);
add_filter('woocommerce_cart_item_subtotal', 'conditional_cart_item_price_display', 10, 3);
function conditional_cart_item_price_display($price, $cart_item, $cart_item_key)
{
    $product = $cart_item['data'];
    if (is_anfrage_product($product)) {
        //if ( has_term( 'special-category', 'product_cat', $product->get_id() ) ) {
        $price = '<span class="special-price">' . __('Preis auf Anfrage', 'woocommerce') . '</span>';
    }

    return $price;
}


# cart totals
add_filter('woocommerce_cart_totals_order_total_html', 'replace_cart_total_with_on_request', 10, 1);

function replace_cart_total_with_on_request($total_html)
{
    if (array_any(WC()->cart->get_cart(), function ($cart_item) {
        return is_anfrage_product($cart_item['data']);
    })) {
        $total_html = '<strong>' . __('Preis auf Anfrage', 'woocommerce') . '</strong>';
    }

    return $total_html;
}

add_filter('woocommerce_cart_subtotal', 'hide_cart_subtotal', 10, 3);
function hide_cart_subtotal($cart_subtotal, $c, $cart)
{
    if (array_any($cart->get_cart(), function ($cart_item) {
        return is_anfrage_product($cart_item['data']);
    }))
        return false;
    return $cart_subtotal;
}

add_action('wp_head', 'hide_cart_subtotal_css');
function hide_cart_subtotal_css()
{
    if (array_any(WC()->cart->get_cart(), function ($cart_item) {
        return is_anfrage_product($cart_item['data']);
    })) {
        echo "
        <style type='text/css'>
        tr.cart-subtotal {
                display: none;
        }
        </style>
        ";
    }
}

add_filter('woocommerce_order_button_text', 'wc_custom_order_button_text');

function wc_custom_order_button_text($text)
{
    if (array_any(WC()->cart->get_cart(), function ($cart_item) {
        return is_anfrage_product($cart_item['data']);
    })) {
        return __('Anfragen', 'woocommerce');
    }
    return $text;
}

add_filter('gettext', function ($translated_text, $original_text, $domain) {
    if ('woocommerce' === $domain && 'Proceed to checkout' === $original_text) {
        // Check if the WooCommerce cart is loaded and not empty
        if (function_exists('WC') && WC()->cart && !WC()->cart->is_empty()) {
            // Check if any product in the cart meets the 'anfrage' condition
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (is_anfrage_product($cart_item['data'])) {
                    return __('Anfragen', 'woocommerce');
                }
            }
        }
    }

    return $translated_text;
}, 10, 3);

function redirect_to_cart_script() {
    // Only enqueue the script on single product pages
    if (is_product() || is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
        wp_enqueue_script('redirect-to-cart', plugin_dir_url(__FILE__) . 'js/redirect-to-cart.js', array('jquery'), null, true);

        // Localize script to add URL to the cart page
        wp_localize_script('redirect-to-cart', 'redirect_to_cart_params', array(
            'cart_url' => wc_get_cart_url()
        ));
    }
}
add_action('wp_enqueue_scripts', 'redirect_to_cart_script');

function customize_mini_cart_text_script()
{
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            // Change "Checkout" button text
            var checkoutButtons = document.querySelectorAll('div.widget_shopping_cart_content > div.elementor-menu-cart__footer-buttons > a');
            console.log(checkoutButtons)
            checkoutButtons.forEach(function (button) {
                if (button.textContent.trim() === 'Kasse') {
                    button.textContent = 'Anfragen';
                }
            });
        });
    </script>
    <?php
}

add_action('wp_footer', 'customize_mini_cart_text_script');