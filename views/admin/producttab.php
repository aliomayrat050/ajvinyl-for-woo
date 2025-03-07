<?php


if (!defined('ABSPATH')) {
    exit;
}


echo '<div id="fields_aj_vinyl" class="panel woocommerce_options_panel">';

// Nonce-Feld für Sicherheit
wp_nonce_field('aj_vinyl_nonce_action', 'aj_vinyl_nonce');

// Checkbox Feld
woocommerce_wp_checkbox(
    array(
        'id'            => '_aj_vinyl_enable',
        'label'         => __('Enable Vinyl Option', 'aj-vinyl-for-woo'),
        'description'   => __('Enable or disable the vinyl option for this product.', 'aj-vinyl-for-woo'),
        'value'         => $enable_vinyl,
        'name'            => '_aj_vinyl_enable',
    )
);

woocommerce_wp_text_input(
    array(
        'id'            => '_aj_vinyl_price_per_sq_m',
        'label'         => __('Price per SqM', 'aj-vinyl-for-woo'),
        'description'   => __('Enter the price per square meter for vinyl printing.', 'aj-vinyl-for-woo'),
        'desc_tip'      => true,
        'type'          => 'text',
        'data_type'     => 'price',
        'value'         => $price_per_sq_m,
        // 'name'            => '_aj_vinyl_price_per_sq_m',
    )
);

woocommerce_wp_text_input(
    array(
        'id'            => '_aj_vinyl_extra_price',
        'label'         => __('Extra kosten für unter 1 qm', 'aj-vinyl-for-woo'),
        'description'   => __('Extra Kosten um cent preise zu vermeiden unter 1qm', 'aj-vinyl-for-woo'),
        'desc_tip'      => true,
        'type'          => 'text',
        'data_type'     => 'price',
        'value'         => $extra_price,
    )
);

?>




<?php




echo '</div>';
