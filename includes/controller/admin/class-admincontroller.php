<?php

namespace AJ_VINYL\Includes\Controller\Admin;

use AJ_VINYL\Includes\Classes\Helper;
use AJ_VINYL\Includes\Classes\HTML;

if (!defined('ABSPATH')) {
    exit;
}


class AdminController
{

    public function __construct()
    {
        add_action('admin_init', [$this, 'aj_vinyl_register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'aj_vinyl_register_assets']);
        add_filter('woocommerce_product_data_tabs', [$this, 'aj_vinyl_add_product_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'aj_vinyl_add_custom_tab_content']);
        add_action('woocommerce_process_product_meta', [$this, 'aj_vinyl_save_custom_tab_content']);
        add_action('woocommerce_admin_order_item_headers', [$this, 'add_custom_fields_order_item_headers'], 10, 1);
        add_action('woocommerce_admin_order_item_values', [$this, 'add_custom_fields_order_item_values'], 10, 3);
        add_action('admin_post_save_aj_vinyl_fonts', [$this, 'aj_vinyl_save_fonts']);
        add_action('admin_post_delete_aj_vinyl_font', [$this, 'aj_vinyl_delete_font']);
        add_action('admin_post_update_aj_vinyl_font', [$this, 'aj_vinyl_update_font']);
        add_action('admin_menu', [$this, 'ajvinyl_admin_menu']);
        add_action('admin_menu', [$this, 'aj_vinyl_colors_submenu']);
        add_action('admin_post_save_aj_vinyl_colors', [$this, 'aj_vinyl_save_colors']);
        add_action('admin_post_delete_aj_vinyl_color', [$this, 'aj_vinyl_delete_color']);
        add_action('admin_post_update_aj_vinyl_color', [$this, 'aj_vinyl_update_color']);
    }





    public function aj_vinyl_register_assets()
    {
        global $ajvinyl;
        $url =  trailingslashit(ajvinyl_get_setting('url')) . 'assets/';
        $version = ajvinyl_get_setting('version');

        // Überprüfe, ob wir auf der Produktbearbeitungsseite sind

        wp_enqueue_script('fabricjs', $url . 'js/vendor/fabric.min.js', array(), '5.3.1', true);
        wp_enqueue_script('openjs', $url . 'js/vendor/opentype.min.js', array(), '1.3.4', true);
        wp_enqueue_script('texttosvg', $url . 'js/vendor/texttosvg.js', array(), $version, true);

        wp_enqueue_script('aj-viny-for-woo-admin-js', $url . 'js/admin.js',  ['jquery', 'fabricjs', 'openjs', 'texttosvg'], $version, true);


        $fonts_dir = $ajvinyl->get_setting('url') . 'assets/fonts/';

        wp_add_inline_script('aj-viny-for-woo-admin-js', 'var ajpluginFontsUrl = "' . esc_js($fonts_dir) . '";', 'before');
    }



    public function add_custom_fields_order_item_values($product, $item, $item_id)
    {
        // Vinyl-Design-Daten aus den Bestell-Item-Metadaten holen
        $vinyl_data = wc_get_order_item_meta($item_id, '_aj_vinyl_designdata', true);
        $ajsocial_data = wc_get_order_item_meta($item_id, '_aj_social_design_data', true);

        $order_id = method_exists($item, 'get_order_id') ? $item->get_order_id() : $item->get_parent_id();
        $order = wc_get_order($order_id);

        if (!$order) {
            return; // Rückgabe, falls die Bestellung nicht existiert
        }

        // Bestellnummer und Position ermitteln
        $order_number = $order->get_order_number(); // Bestellnummer
        $order_item_position = $item_id;

        // Daten in der neuen Spalte anzeigen, wenn vorhanden
        echo '<td class="vinyl-design-data">';

        if ($vinyl_data) {
            $design_data = maybe_unserialize($vinyl_data);

            if (is_array($design_data)) {
                echo '<strong>Text:</strong> ' . esc_html($design_data['aj_text']) . '<br>';
                echo '<strong>Breite:</strong> ' . esc_html($design_data['aj_width']) . ' cm<br>';
                echo '<strong>Höhe:</strong> ' . esc_html($design_data['aj_height']) . ' cm<br>';
                echo '<strong>Farbe:</strong> ' . esc_html($design_data['color']) . '<br>';
                echo '<strong>Finish:</strong> ' . esc_html($design_data['finish']) . '<br>';
                echo '<strong>Preis:</strong> ' . esc_html($design_data['price']) . ' €<br>';
            }
        }

        echo '</td>';

        // SVG-Button mit Design-Daten im data-Attribut
        echo '<td class="vinyl-design-svg-button">';

        if ($vinyl_data) {
            $design_data_json = htmlspecialchars(json_encode($design_data), ENT_QUOTES, 'UTF-8');
            echo '<button class="button button-primary svg-create-btn" 
                     data-design-data="' . $design_data_json . '" 
                     data-order-number="' . esc_attr($order_number) . '" 
                     data-order-item-position="' . esc_attr($order_item_position) . '">SVG Download</button>';
        } else if ($ajsocial_data) {
            $product_id = $product->get_id();
            $ajsocial_settings = get_post_meta($product_id, '_aj_social_icon_settings', true);
            $ajsocial_path = get_post_meta($product_id, '_aj_social_icons_path', true);
            $ajsocial_data_serialized = maybe_unserialize($ajsocial_data);
            $ajsocial_data_json = htmlspecialchars(json_encode($ajsocial_data_serialized), ENT_QUOTES, 'UTF-8');
            $fonts = get_option('aj_vinyl_fontsdata', serialize([]));
            $fonts = unserialize($fonts);
            $fonts_json = htmlspecialchars(json_encode($fonts), ENT_QUOTES, 'UTF-8');
            echo '<button class="button button-primary svg-create-btn-aj-social" 
                     data-aj-social-design-data="' . $ajsocial_data_json . '" 
                     data-order-number="' . esc_attr($order_number) . '" 
                     data-order-item-position="' . esc_attr($order_item_position) . '" 
                     data-icon-path="' . esc_attr($ajsocial_path) . '" 
                     data-ajsocial-icon-settings="' . htmlspecialchars(json_encode($ajsocial_settings), ENT_QUOTES, 'UTF-8') . '"
                     data-ajsocial-fonts-data="' . $fonts_json .
                '">SVG Download</button>';
        } else {
            echo 'Kein SVG verfügbar';
        }

        echo '</td>';
    }

    public function add_custom_fields_order_item_headers($order)
    {
        echo '<th class="vinyl-design-header">Vinyl Design</th>';
        echo '<th class="vinyl-design-svg-header">SVG Download</th>';
    }


    public function aj_vinyl_add_product_tab($tabs)
    {
        $tabs['ajfields'] = [
            'label'        => __('AJ Vinyl Fields', 'aj-vinyl-for-woo'),
            'target'    => 'fields_aj_vinyl',
            'class'        => ['show_if_simple', 'show_if_variable'],
        ];
        return $tabs;
    }


    public function aj_vinyl_add_custom_tab_content()
    {
        global $post;

        // Hole die gespeicherten JSON-Daten aus der Datenbank
        $vinyl_data = Helper::getData($post->ID);

        // Standardwerte setzen, falls die Felder leer sind
        $enable_vinyl = isset($vinyl_data['enable_vinyl']) ? $vinyl_data['enable_vinyl'] : 'no';
        $price_per_sq_m = isset($vinyl_data['price_per_sq_m']) ? $vinyl_data['price_per_sq_m'] : '';
        $extra_price = isset($vinyl_data['extra_price']) ? $vinyl_data['extra_price'] : '';



        echo HTML::view('admin/producttab', [
            'enable_vinyl' => $enable_vinyl,
            'price_per_sq_m' => $price_per_sq_m,
            'extra_price' => $extra_price,


        ]);
    }


    public function aj_vinyl_save_custom_tab_content($post_id)
    {
        // Sicherheitsüberprüfung: Stellen Sie sicher, dass der Benutzer berechtigt ist, den Beitrag zu bearbeiten
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }


        // Nonce-Überprüfung
        if (!isset($_POST['aj_vinyl_nonce']) || !wp_verify_nonce($_POST['aj_vinyl_nonce'], 'aj_vinyl_nonce_action')) {
            return; // Nonce ungültig, keine weiteren Daten verarbeiten
        }

        // Speichern der Checkbox-Einstellung
        $enable_vinyl = isset($_POST['_aj_vinyl_enable']) ? 'yes' : 'no';

        // Validierung und Speicherung des Preises pro Quadratmeter
        $price_per_sq_m = isset($_POST['_aj_vinyl_price_per_sq_m']) ? floatval(sanitize_text_field($_POST['_aj_vinyl_price_per_sq_m'])) : 0;

        // Validierung und Speicherung des Extra-Preises
        $extra_price = isset($_POST['_aj_vinyl_extra_price']) ? floatval(sanitize_text_field($_POST['_aj_vinyl_extra_price'])) : 0;



        // Alle Daten in ein Array packen
        $vinyl_data = array(
            'enable_vinyl' => $enable_vinyl,
            'price_per_sq_m' => $price_per_sq_m,
            'extra_price' => $extra_price,
        );

        // Array in JSON umwandeln
        $vinyl_data_json = wp_json_encode($vinyl_data);

        // JSON in die Datenbank speichern (entweder als Post Meta oder in einer eigenen Tabelle)
        update_post_meta($post_id, '_aj_vinyl_data', $vinyl_data_json);
    }

    public function ajvinyl_admin_menu()
    {
        add_menu_page(
            'AJ Vinyl for Woo', // Seitentitel
            'AJ Vinyl', // Menü-Titel
            'manage_options', // Berechtigung
            'aj-vinyl-for-woo', // Menü-Slug
            [$this, 'ajvinyl_fonts_menu_page'], // Callback-Funktion, die die Seite rendert
            'dashicons-admin-generic', // Icon für das Menü
        );
    }

    public function ajvinyl_fonts_menu_page()
    {

        $fonts = get_option('aj_vinyl_fontsdata', serialize([]));
        $fonts = unserialize($fonts);

        echo HTML::view('admin/menupage', [
            'fonts' => $fonts,
        ]);
    }




    public function aj_vinyl_save_fonts()
    {
        // Berechtigung prüfen
        if (!current_user_can('manage_options')) {
            wp_die(__('Du hast keine Berechtigung, diese Einstellungen zu speichern.', 'aj-vinyl-for-woo'));
        }

        // Nonce verifizieren
        if (!isset($_POST['aj_vinyl_fonts_nonce']) || !wp_verify_nonce($_POST['aj_vinyl_fonts_nonce'], 'aj_vinyl_save_fonts')) {
            wp_die(__('Ungültige Sicherheitsüberprüfung.', 'aj_vinyl-for-woo'));
        }

        // Bestehende Schriftarten abrufen
        $existing_fonts = get_option('aj_vinyl_fontsdata', []);
        $existing_fonts = !empty($existing_fonts) ? unserialize($existing_fonts) : [];

        // Neue Schriftart hinzufügen
        if (isset($_POST['new_font']) && !empty($_POST['new_font']['name'])) {
            $new_font = $_POST['new_font'];
            $existing_fonts[] = [
                'name' => sanitize_text_field($new_font['name']),
                'bold' => isset($new_font['bold']) && $new_font['bold'] === '1',
                'italic' => isset($new_font['italic']) && $new_font['italic'] === '1'
            ];
        }

        // Speichern in der Datenbank
        update_option('aj_vinyl_fontsdata', serialize($existing_fonts));

        // Weiterleitung nach dem Speichern
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }

    public function aj_vinyl_delete_font()
    {
        // Berechtigung prüfen
        if (!current_user_can('manage_options')) {
            wp_die(__('Du hast keine Berechtigung, diese Einstellungen zu speichern.', 'aj-vinyl-for-woo'));
        }

        // Nonce verifizieren
        if (!isset($_POST['aj_vinyl_fonts_nonce']) || !wp_verify_nonce($_POST['aj_vinyl_fonts_nonce'], 'aj_vinyl_delete_fonts')) {
            wp_die(__('Ungültige Sicherheitsüberprüfung.', 'aj-vinyl-for-woo'));
        }

        // Bestehende Schriftarten abrufen
        $existing_fonts = get_option('aj_vinyl_fontsdata', []);
        $existing_fonts = !empty($existing_fonts) ? unserialize($existing_fonts) : [];

        // Schriftart löschen
        if (isset($_POST['index'])) {
            $index = intval($_POST['index']);
            if (isset($existing_fonts[$index])) {
                unset($existing_fonts[$index]);
            }
        }

        // Neuindizierung des Arrays
        $existing_fonts = array_values($existing_fonts);

        // Speichern in der Datenbank
        update_option('aj_vinyl_fontsdata', serialize($existing_fonts));

        // Weiterleitung nach dem Löschen
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }

    public function aj_vinyl_update_font()
    {
        // Berechtigung prüfen
        if (!current_user_can('manage_options')) {
            wp_die(__('Du hast keine Berechtigung, diese Einstellungen zu speichern.', 'aj-vinyl-for-woo'));
        }

        // Nonce verifizieren
        if (!isset($_POST['aj_vinyl_fonts_nonce']) || !wp_verify_nonce($_POST['aj_vinyl_fonts_nonce'], 'aj_vinyl_update_fonts')) {
            wp_die(__('Ungültige Sicherheitsüberprüfung.', 'aj-vinyl-for-woo'));
        }

        // Bestehende Schriftarten abrufen
        $existing_fonts = get_option('aj_vinyl_fontsdata', []);
        $existing_fonts = !empty($existing_fonts) ? unserialize($existing_fonts) : [];

        // Schriftart aktualisieren
        if (isset($_POST['index'])) {
            $index = intval($_POST['index']);
            if (isset($existing_fonts[$index])) {
                $existing_fonts[$index] = [
                    'name' => sanitize_text_field($_POST['font']['name']),
                    'bold' => isset($_POST['font']['bold']) && $_POST['font']['bold'] === '1',
                    'italic' => isset($_POST['font']['italic']) && $_POST['font']['italic'] === '1'
                ];
            }
        }

        // Speichern in der Datenbank
        update_option('aj_vinyl_fontsdata', serialize($existing_fonts));

        // Weiterleitung nach dem Aktualisieren
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }



    public function aj_vinyl_register_settings()
    {
        // Option für die Speicherung registrieren
        register_setting('aj_vinyl_fonts_group', 'aj_vinyl_fontsdata');
        register_setting('aj_vinyl_colors_group', 'aj_vinyl_colorsdata');
    }

    public function aj_vinyl_colors_submenu()
    {
        add_submenu_page(
            'aj-vinyl-for-woo', // Slug des übergeordneten Menüs
            'Farben verwalten', // Seitentitel
            'Farben', // Menü-Titel
            'manage_options', // Berechtigung
            'farben', // Menü-Slug
            [$this, 'aj_vinyl_colors_submenu_page'] // Callback-Funktion, die die Unterseite rendert
        );
    }

    public function aj_vinyl_colors_submenu_page()
    {

        $colors = get_option('aj_vinyl_colorsdata', serialize([]));
        $colors = unserialize($colors);

        echo HTML::view('admin/colors_submenu', [
            'colors' => $colors,
        ]);
    }

    public function aj_vinyl_save_colors()
    {
        // Berechtigung prüfen
        if (!current_user_can('manage_options')) {
            wp_die(__('Du hast keine Berechtigung, diese Einstellungen zu speichern.', 'aj-vinyl-for-woo'));
        }

        // Nonce verifizieren
        if (!isset($_POST['aj_vinyl_colors_nonce']) || !wp_verify_nonce($_POST['aj_vinyl_colors_nonce'], 'aj_vinyl_save_colors')) {
            wp_die(__('Ungültige Sicherheitsüberprüfung.', 'aj-vinyl-for-woo'));
        }

        // Bestehende Farben abrufen
        $existing_colors = get_option('aj_vinyl_colorsdata', []);
        $existing_colors = !empty($existing_colors) ? unserialize($existing_colors) : [];

        // Neue Farbe hinzufügen
        if (isset($_POST['new_color']) && !empty($_POST['new_color']['color'])) {
            $new_color = $_POST['new_color'];
            $existing_colors[] = [
                'color' => sanitize_text_field($new_color['color']),
                'finish' => sanitize_text_field($new_color['finish']) // 'glossy' oder 'matte'
            ];
        }

        // Speichern in der Datenbank
        update_option('aj_vinyl_colorsdata', serialize($existing_colors));

        // Weiterleitung nach dem Speichern
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }


    public function aj_vinyl_delete_color()
    {
        // Berechtigungen prüfen
        if (!current_user_can('manage_options')) {
            wp_die(__('Du hast keine Berechtigung, diese Einstellungen zu speichern.', 'aj-vinyl-for-woo'));
        }

        // Nonce verifizieren
        if (!isset($_POST['aj_vinyl_colors_nonce']) || !wp_verify_nonce($_POST['aj_vinyl_colors_nonce'], 'aj_vinyl_delete_colors')) {
            wp_die(__('Ungültige Sicherheitsüberprüfung.', 'aj-vinyl-for-woo'));
        }

        // Fehlerlog: Start der Funktion
        error_log('aj_vinyl_delete_color wird ausgeführt');

        // Bestehende Farben abrufen
        $existing_colors = get_option('aj_vinyl_colorsdata', []);
        $existing_colors = !empty($existing_colors) ? unserialize($existing_colors) : [];

        // Fehlerlog: Überprüfen der vorhandenen Farben
        error_log('Vorhandene Farben: ' . print_r($existing_colors, true));

        // Prüfen, ob der Index gesetzt und gültig ist
        if (isset($_POST['index'])) {
            $index = intval($_POST['index']);

            // Fehlerlog: Überprüfen des Indexwertes
            error_log('Übergebener Index: ' . $index);

            // Prüfen, ob der Index im Array existiert
            if (isset($existing_colors[$index])) {
                unset($existing_colors[$index]);

                // Neuindizierung des Arrays nach dem Löschen
                $existing_colors = array_values($existing_colors);

                // Speichern in der Datenbank
                update_option('aj_vinyl_colorsdata', serialize($existing_colors));

                // Fehlerlog: Farben nach dem Löschen
                error_log('Farben nach dem Löschen: ' . print_r($existing_colors, true));
            } else {
                wp_die(__('Der ausgewählte Eintrag existiert nicht.', 'aj-vinyl-for-woo'));
            }
        } else {
            wp_die(__('Kein gültiger Index zum Löschen übergeben.', 'aj-vinyl-for-woo'));
        }

        // Weiterleitung nach dem Löschen
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }



    public function aj_vinyl_update_color()
    {
        // Berechtigung prüfen
        if (!current_user_can('manage_options')) {
            wp_die(__('Du hast keine Berechtigung, diese Einstellungen zu speichern.', 'aj-vinyl-for-woo'));
        }

        // Nonce verifizieren
        if (!isset($_POST['aj_vinyl_colors_nonce']) || !wp_verify_nonce($_POST['aj_vinyl_colors_nonce'], 'aj_vinyl_update_colors')) {
            wp_die(__('Ungültige Sicherheitsüberprüfung.', 'aj-vinyl-for-woo'));
        }

        // Bestehende Farben abrufen
        $existing_colors = get_option('aj_vinyl_colorsdata', []);
        $existing_colors = !empty($existing_colors) ? unserialize($existing_colors) : [];

        // Farbe aktualisieren
        if (isset($_POST['index'])) {
            $index = intval($_POST['index']);
            if (isset($existing_colors[$index])) {
                $existing_colors[$index] = [
                    'color' => sanitize_text_field($_POST['color']['color']),
                    'finish' => sanitize_text_field($_POST['color']['finish']) // 'glossy' oder 'matte'
                ];
            }
        }

        // Speichern in der Datenbank
        update_option('aj_vinyl_colorsdata', serialize($existing_colors));

        // Weiterleitung nach dem Aktualisieren
        wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        exit;
    }
}
