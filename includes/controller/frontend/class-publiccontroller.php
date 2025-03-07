<?php

namespace AJ_VINYL\Includes\Controller\Frontend;

use AJ_VINYL\Includes\Classes\Helper;

if (!defined('ABSPATH')) {
    exit;
}

class PublicController
{

    public function __construct()
    {
        if (!$this->is_woocommerce_active())
            return;
        add_filter('query_vars', [$this, 'aj_query_vars']);
        add_action('init', [$this, 'aj_register_rewrite']);
        add_action('template_redirect', [$this, 'aj_process_request']);
        add_action('wp_enqueue_scripts', [$this, 'aj_register_assets'], 999);
        add_action('woocommerce_order_item_meta_end', [$this, 'aj_display_custom_fields_in_order_table'], 10, 4);
        new ProductController();
    }

    public function aj_display_custom_fields_in_order_table($item_id, $item, $order, $plain_text)
    {
        // Hole die benutzerdefinierten Daten
        $custom_data = $item->get_meta('_aj_vinyl_designdata', true);

        // Überprüfen, ob die Daten existieren
        if ($custom_data) {
            $custom_data = maybe_unserialize($custom_data); // Daten ent-serialisieren

            if (is_array($custom_data)) {
                // Daten formatieren und anzeigen
                echo '<br><strong>Text:</strong> ' . esc_html($custom_data['aj_text']) . '<br>';
                echo '<strong>Breite:</strong> ' . esc_html($custom_data['aj_width']) . ' cm<br>';
                echo '<strong>Höhe:</strong> ' . esc_html($custom_data['aj_height']) . ' cm<br>';
                echo '<strong>Farbe:</strong> ' . esc_html($custom_data['color']) . '<br>';
                echo '<strong>Finish:</strong> ' . esc_html($custom_data['finish']) . '<br>';
            }
        }
    }


    public function aj_register_assets()
    {
        global $ajvinyl;
        $url =  trailingslashit(ajvinyl_get_setting('url')) . 'assets/';
        $version = ajvinyl_get_setting('version');
        wp_enqueue_script('jquery');

        wp_enqueue_style(
            'aj-vinyl-for-woo-style', // Handle des Styles
            $url . 'css/frontend.css', // Pfad zur CSS-Datei
            array(), // Abhängigkeiten
            $version // Version des Styles
        );


        wp_enqueue_script('fabricjs', $url . 'js/vendor/fabric.min.js', array(), '5.3.1', true);
        wp_enqueue_script('openjs', $url . 'js/vendor/opentype.min.js', array(), '1.3.4', true);
        wp_enqueue_script('texttosvg', $url . 'js/vendor/texttosvg.js', array(), $version, true);
        wp_enqueue_script(
            'aj-vinyl-for-woo-script', // Handle des Skripts
            $url . 'js/frontend.js', // Pfad zur JS-Datei
            array('jquery', 'fabricjs', 'openjs', 'texttosvg'), // Abhängigkeiten (z.B. jQuery)
            $version, // Version des Skripts
            true // Das Skript wird im Footer geladen
        );
        wp_localize_script('aj-vinyl-for-woo-script', 'ajvinylforwoo', array(
            'ajaxUrl' => home_url('/aj-vinyl-for-woo/'),
            'nonce'   => wp_create_nonce('ajvinyl_nonce'),
        ));

        $fonts = get_option('aj_vinyl_fontsdata', serialize([]));
        $fonts = unserialize($fonts);

        // JSON-Daten erstellen
        $fonts_json = json_encode($fonts);
        $fonts_dir = $ajvinyl->get_setting('url') . 'assets/fonts/';

        wp_add_inline_script('aj-vinyl-for-woo-script', 'var ajpluginFontsUrl = "' . esc_js($fonts_dir) . '";', 'before');

        // Inline-Skript ausgeben
        wp_add_inline_script('aj-vinyl-for-woo-script', 'var fontsData = ' . $fonts_json . ';', 'before');

        $colors = get_option('aj_vinyl_colorsdata', serialize([]));
        $colors = unserialize($colors);

        // JSON-Daten erstellen
        $colors_json = json_encode($colors);
        wp_add_inline_script('aj-vinyl-for-woo-script', 'var colorsData = ' . $colors_json . ';', 'before');
    }

    public function aj_register_rewrite()
    {
        add_rewrite_rule('^aj-vinyl-for-woo/?$', 'index.php?aj-vinyl-for-woo=1', 'top');
        flush_rewrite_rules();
    }

    public function aj_query_vars($vars)
    {
        $vars[] = 'aj-vinyl-for-woo';
        return $vars;
    }

    function aj_process_request()
    {

        if (get_query_var('aj-vinyl-for-woo') == 1) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $data = json_decode(file_get_contents('php://input'), true);

                // Sicherheitscheck über einen Nonce (optional)
                if (!isset($data['nonce']) || !wp_verify_nonce($data['nonce'], 'ajvinyl_nonce')) {
                    wp_send_json_error(array('message' => 'Ungültige Anforderung.'));
                    exit;
                }

                // Validierung der Daten
                if (!isset($data['productId'])) {
                    echo json_encode(['error' => 'Fehlende Parameter']);
                    exit;
                }

                $productID = isset($data['productId']) ? intval($data['productId']) : '';
                $response = [];
                $data = Helper::getPriceData($productID);

                if (!empty($productID)) {
                    $response = [
                        'productid' => $productID,
                        'priceqm' => $data['priceqm'],
                        'extraprice' => $data['extraprice'],
                    ];
                } else {
                    $response = [
                        'pricedata' => null,
                    ];
                }

                // JSON-Antwort zurückgeben
                wp_send_json($response);
            }

            exit;
        }
    }



    public function is_woocommerce_active()
    {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return true;
        }
        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins['woocommerce/woocommerce.php']))
                return true;
        }
        return false;
    }
}
