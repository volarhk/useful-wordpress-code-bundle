<?php
/**
 * Author: Volar Cheung
 * Description: Custom PHP script to match products by SKU in different languages (WPML + WooCommerce).
 */

// Load WordPress and WooCommerce functions.
require_once('wp-config.php');
require_once(ABSPATH . 'wp-load.php');
global $wpdb;

function fetch_products_translation_and_match($from, $to, $debug = false)
{

    if ($from == $to) {
        echo 'The source language and the target language cannot be the same.';
        return;
    }

    if (!$from || !$to) {
        echo 'Please specify the source language and the target language.';
        return;
    }

    // Check if WPML is active.
    if (function_exists('icl_object_id')) {

        // Get all products in the original language. order by date
        $original_language_products = $wpdb->get_results("
        SELECT p.ID AS original_id, m1.meta_value AS original_sku
        FROM {$wpdb->prefix}posts AS p
        LEFT JOIN {$wpdb->prefix}postmeta AS m1 ON p.ID = m1.post_id AND m1.meta_key = '_sku'
        LEFT JOIN {$wpdb->prefix}icl_translations AS t ON p.ID = t.element_id
        WHERE p.post_type = 'product' AND p.post_status = 'publish' AND t.language_code = {$from}
        ORDER BY p.post_date DESC
        ");

        if ($original_language_products) {

            echo 'Found ' . count($original_language_products) . ' products in the original language.<br>';

            foreach ($original_language_products as $original_product) {
                // Get the SKU of the original product.
                $original_sku = $original_product->original_sku;
                $original_product_id = $original_product->original_id;

                // Find the corresponding translated product by SKU. 

                $translated_product_id = $wpdb->get_var($wpdb->prepare("
                SELECT p.ID
                FROM {$wpdb->prefix}posts AS p
                LEFT JOIN {$wpdb->prefix}postmeta AS m1 ON p.ID = m1.post_id AND m1.meta_key = '_sku'
                LEFT JOIN {$wpdb->prefix}icl_translations AS t ON p.ID = t.element_id
                WHERE p.post_type = 'product' AND p.post_status = 'publish' AND t.language_code = {$to} AND m1.meta_value = %s
                ", $original_sku));

                if ($translated_product_id) {

                    // WPML link the translated product to the original product.

                    $original_post_language_info = apply_filters('wpml_element_language_details', null, array('element_id' => $original_product_id, 'element_type' => 'post_product'));

                    $set_language_args = array(
                        'element_id' => $translated_product_id,
                        'element_type' => 'post_product',
                        'trid' => $original_post_language_info->trid,
                        'language_code' => $to,
                        'source_language_code' => $original_post_language_info->language_code
                    );

                    do_action('wpml_set_element_language_details', $set_language_args);


                    // echo result
                    echo 'Matched product ' . $original_product_id . ' (' . $original_sku . ') with product ' . $translated_product_id . " in {$to}.<br>";

                } else {
                    echo "<div style='color: red'>No product found in {$to} with SKU " . $original_sku . ".</div>";
                }
            }
        } else {
            echo 'No products found in the original language.';
        }

    } else {
        echo 'WPML is not active.';
    }
}

// Run the script.
fetch_products_translation_and_match('zh-hant', 'en');