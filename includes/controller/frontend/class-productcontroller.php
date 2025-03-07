<?php

namespace AJ_VINYL\Includes\Controller\Frontend;

use AJ_VINYL\Includes\Classes\Helper;
use AJ_VINYL\Includes\Classes\HTML;

if (!defined('ABSPATH')) {
    exit;
}

class ProductController
{

    private $required_fields = [
        'aj_width',
        'aj_height',
        'aj_text',
        'color-select',
        'aj_finish',
    ];

    public function __construct()
    {
        add_action('woocommerce_before_calculate_totals', [$this, 'adjust_cart_item_pricing']);
        add_filter('woocommerce_cart_item_price', [$this, 'display_original_price_in_cart'], 10, 3);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_custom_fields_to_cart'], 10, 2);
        add_filter('woocommerce_get_item_data', [$this, 'display_custom_fields_in_cart'], 10, 2);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_custom_fields'], 20, 3);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_custom_fields_in_order_meta'], 20, 4);
        add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hide_aj_vinyl_designdata_meta_field']);
        add_filter('template_include', [$this, 'ajvinyl_single_product_template'], 11);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'adjust_cart_item_pricing_on_session'], 10, 3);
        add_filter('ppom_price_tale_html', [$this, 'aj_vinyl_legalinfo']);
        add_filter('aj_vinyl_legal_info', [$this, 'aj_vinyl_legal_info_plugin']);
        add_filter('woocommerce_get_price_html', [$this, 'aj_vinyl_custom_price_display'], 20, 2);
    }



    // For AJ SOCIAL AND AJ UPLOAD!
    public function aj_vinyl_legal_info_plugin()
    {
        echo '<div class="legal-price-info">
            <p class="wc-gzd-additional-info">
                <span class="wc-gzd-additional-info tax-info">inkl. 19 % MwSt.</span>
                <span class="wc-gzd-additional-info shipping-costs-info">zzgl. <a href="' . esc_url(get_site_url()) . '/versandarten/" target="_blank">Versandkosten</a></span>
            </p>
        </div>';
    }

    //Germanized function for PPOM!
    public function aj_vinyl_legalinfo($html)
    {
        return $html . '<div class="legal-price-info" style="text-align: right;">
        <p class="wc-gzd-additional-info">
            <span class="wc-gzd-additional-info tax-info">inkl. 19 % MwSt.</span>
            <span class="wc-gzd-additional-info shipping-costs-info">zzgl. <a href="' . esc_url(get_site_url()) . '/versandarten/" target="_blank">Versandkosten</a></span>
        </p>
    </div>';
    }


    public function ajvinyl_single_product_template($template)
    {
        global $post;
        global $ajvinyl;
        // Nur für Produktseiten anwenden
        if (is_singular('product') && Helper::is_plugin_active_for_product($post->ID)) {
            // Pfad zur neuen Template-Datei in deinem Plugin
            $new_template = $ajvinyl->get_setting('path') . 'templates/custom-single-product.php';

            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }


    private function are_required_fields_set($data)
    {
        foreach ($this->required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false; // Ein erforderliches Feld fehlt
            }
        }
        return true; // Alle erforderlichen Felder sind vorhanden
    }

    public function hide_aj_vinyl_designdata_meta_field($hidden_meta_keys)
    {
        $hidden_meta_keys[] = '_aj_vinyl_designdata';
        return $hidden_meta_keys;
    }





    public function save_custom_fields_in_order_meta($item, $cart_item_key, $values, $order)
    {
        $product_id = $values['data']->get_id();
        if (!Helper::is_plugin_active_for_product($product_id)) {
            return; // Produkt nicht aktiv, daher keine Validierung nötig
        }

        // Füge alle relevanten Daten in ein einziges Array ein
        $design_data = [
            'aj_text' => $values['aj_text'] ?? '',
            'aj_width' => $values['aj_width'] ?? '',
            'aj_height' => $values['aj_height'] ?? '',
            'color' => $values['color-select'] ?? '',
            'finish' => $values['aj_finish'] ?? '',
            'price' => $values['aj_price'] ?? 0,
            'style_status' => $values['style_status'] ?? []
        ];

        // Speicher das gesamte Design-Array als JSON- oder serialisiertes Format
        $item->add_meta_data('_aj_vinyl_designdata', serialize($design_data));
    }





    public function validate_custom_fields($passed, $product_id, $quantity)
    {
        // Überprüfen, ob das Produkt für das Plugin aktiv ist
        if (!Helper::is_plugin_active_for_product($product_id)) {
            return $passed; // Produkt nicht aktiv, daher keine Validierung nötig
        }

        // Überprüfen, ob alle erforderlichen Felder ausgefüllt sind
        if (!$this->are_required_fields_set($_POST)) {
            wc_add_notice(__('Bitte füllen Sie alle erforderlichen Felder aus.', 'aj-vinyl-for-woo'), 'error');
            return false; // Verhindert das Hinzufügen zum Warenkorb
        }

        // Höhe validieren
        $ajheight = $_POST['aj_height'] ?? 0;
        $ajwidth = $_POST['aj_width'] ?? 0;

        if ($ajheight < 2) {
            wc_add_notice(__('Höhe muss mindestens 2 cm sein.', 'aj-vinyl-for-woo'), 'error');
            return false;
        }

        // Einschränkungen prüfen
        $dimension_errors = $this->validate_dimensions($ajwidth, $ajheight);

        if (!empty($dimension_errors)) {
            wc_add_notice($dimension_errors, 'error');
            return false;
        }

        // Farben und Finish-Daten abrufen (serialisiert, daher mit unserialize decodieren)
        $serialized_colors = get_option('aj_vinyl_colorsdata', ''); // Abrufen der serialisierten Farbdaten
        $colors = unserialize($serialized_colors); // Deserialisieren

        // Überprüfen, ob die unserialisierten Farbdaten gültig sind
        if ($colors === false) {
            wc_add_notice(__('Fehler beim Laden der Farbdaten.', 'aj-vinyl-for-woo'), 'error');
            return false;
        }

        // Holen der ausgewählten Farbe und Finish
        $selected_color = $_POST['color-select'] ?? '';
        $selected_finish = $_POST['aj_finish'] ?? '';

        // Überprüfen, ob die ausgewählte Farbe und Finish in der Liste der verfügbaren Farben und Finishes vorhanden sind
        $valid_color = false;
        foreach ($colors as $color) {
            if ($color['color'] === $selected_color && $color['finish'] === $selected_finish) {
                $valid_color = true;
                break;
            }
        }

        // Wenn die ausgewählte Farbe und Finish nicht gültig sind, eine Fehlermeldung ausgeben
        if (!$valid_color) {
            wc_add_notice(__('Die ausgewählte Farbe oder das Finish ist nicht gültig.', 'aj-vinyl-for-woo'), 'error');
            return false;
        }

        return $passed;
    }

    /**
     * Validiert Breite und Höhe und gibt einen Fehler zurück, falls ungültig.
     */
    private function validate_dimensions($width, $height)
    {
        if ($width > 70 && $height > 70) {
            return __('Entweder die Breite darf maximal 70 cm betragen oder die Höhe maximal 70 cm.', 'aj-vinyl-for-woo');
        }

        if ($width > 70 && $height > 200) {
            return __('Wenn die Breite mehr als 70 cm beträgt, darf die Höhe maximal 200 cm betragen.', 'aj-vinyl-for-woo');
        }

        if ($height > 70 && $width > 200) {
            return __('Wenn die Höhe mehr als 70 cm beträgt, darf die Breite maximal 200 cm betragen.', 'aj-vinyl-for-woo');
        }

        if ($height > 200) {
            return __('Die Höhe liegt über 200 cm.', 'aj-vinyl-for-woo');
        }

        if ($width > 200) {
            return __('Die Breite liegt über 200 cm.', 'aj-vinyl-for-woo');
        }

        return ''; // Keine Fehler
    }


    public function add_custom_fields_to_cart($cart_item_data, $product_id)
    {
        // Überprüfen, ob alle erforderlichen POST-Felder vorhanden sind
        if (!$this->are_required_fields_set($_POST)) {
            return $cart_item_data; // Rückgabe, wenn nicht alle erforderlichen Felder vorhanden sind
        }

        if (!Helper::is_plugin_active_for_product($product_id)) {
            return $cart_item_data; // Produkt nicht aktiv, daher keine Validierung nötig
        }

        // Bereinigung und Zuweisung der Eingabewerte
        $aj_text_raw = sanitize_textarea_field($_POST['aj_text']); // Benutze sanitize_textarea_field für Textarea
        $cart_item_data['aj_text'] = str_replace("\n", '| ', $aj_text_raw); // Ersetze Zeilenumbrüche durch '|'
        $cart_item_data['aj_width'] = sanitize_text_field($_POST['aj_width']);
        $cart_item_data['aj_height'] = sanitize_text_field($_POST['aj_height']);
        $cart_item_data['color-select'] = sanitize_text_field($_POST['color-select']); // 'color-select' in 'color' geändert
        $cart_item_data['aj_finish'] = sanitize_text_field($_POST['aj_finish']);

        // Berechnung des Preises mit einer Helper-Methode
        $cart_item_data['aj_price'] = Helper::priceCalc($cart_item_data['aj_height'], $cart_item_data['aj_width'], $product_id);
        $cart_item_data['discountedPrice'] = 0;


        // Initialisierung des style_status Arrays
        $cart_item_data['style_status'] = [];

        // Aufteilen des Texts in einzelne Zeilen, basierend auf Zeilenumbrüchen
        $lines = explode("\n", $aj_text_raw);
        foreach ($lines as $index => $line) {
            // Dynamische Zuordnung der Werte
            $cart_item_data['style_status'][$index] = [
                'text' => trim($line), // Text der Zeile aus der POST-Anfrage
                'font' => isset($_POST["font-$index"]) ? sanitize_text_field($_POST["font-$index"]) : 'Arial', // Schriftart aus POST, Standard 'Arial'
                'align' => isset($_POST["align-$index"]) ? sanitize_text_field($_POST["align-$index"]) : 'center', // Ausrichtung aus POST, Standard 'center'
                'bold' => isset($_POST["bold-$index"]) ? ($_POST["bold-$index"] === '1') : false, // Fett aus POST, Standard false
                'italic' => isset($_POST["italic-$index"]) ? ($_POST["italic-$index"] === '1') : false, // Kursiv aus POST, Standard false
                'spacing' => isset($_POST["spacing-$index"]) ? intval($_POST["spacing-$index"]) : 0, // Abstand aus POST, Standard 0
                'size' => isset($_POST["size-$index"]) ? intval($_POST["size-$index"]) : 100, // Größe aus POST, Standard 100
            ];
        }

        // Setze einen einzigartigen Schlüssel für das Cart Item
        $cart_item_data['unique_key'] = uniqid('', true);

        return $cart_item_data; // Rückgabe der aktualisierten Cart Item Daten
    }



    public function adjust_cart_item_pricing($cart)
    {

        if (is_admin() && !defined('DOING_AJAX')) {
            return; // Verhindert Ausführung im Admin-Bereich
        }

        if (empty($cart->get_cart())) {
            return; // Keine Artikel im Warenkorb, keine Aktionen notwendig
        }

        // Warenkorb durchlaufen und Preise anpassen
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];

            // Prüfen, ob der angepasste Preis (`aj_price`) vorhanden ist
            if (isset($cart_item['aj_price'])) {
                // Ursprünglichen Preis speichern, falls er noch nicht gesetzt ist
                if (!isset($cart_item['original_price'])) {
                    $cart_item['original_price'] = $cart_item['aj_price'];
                }

                $discounted_price = Helper::calcDiscountPrice($cart_item['aj_height'], $cart_item['aj_width'], $quantity, $cart_item['product_id'], $cart_item['aj_price']);

                $product->set_price($discounted_price); // Rabattierten Preis setzen
                return $product;
            }
        }
    }


    public function adjust_cart_item_pricing_on_session($cart_item, $values, $key)
    {
        // Hier kannst du die gleiche Logik wie in adjust_cart_item_pricing anwenden
        $product = $cart_item['data'];
        $quantity = $cart_item['quantity'];
        if (isset($cart_item['aj_price'])) {


            $discounted_price = Helper::calcDiscountPrice($cart_item['aj_height'], $cart_item['aj_width'], $quantity, $cart_item['product_id'], $cart_item['aj_price']);
            $product->set_price($discounted_price);

            return $cart_item;
        }
        return $cart_item;
    }

    // Originalpreis im Warenkorb anzeigen

    public function display_original_price_in_cart($price_html, $cart_item, $cart_item_key)
    {
        // Prüfen, ob der Originalpreis vorhanden ist und größer als der aktuelle (rabattierte) Preis
        if (isset($cart_item['aj_price']) && $cart_item['aj_price'] > $cart_item['data']->get_price()) {
            // Formatieren des Originalpreises und des rabattierten Preises
            $original_price = wc_price($cart_item['aj_price']); // Preis formatieren
            $discounted_price = wc_price($cart_item['data']->get_price());

            // Preis HTML mit durchgestrichenem Originalpreis und Rabattpreis erstellen
            $price_html = "<span class='original-price' style='text-decoration: line-through; color: #888;'>$original_price</span> <span class='discounted-price'>$discounted_price</span>";
        }

        return $price_html;
    }



    public function display_custom_fields_in_cart($item_data, $cart_item)
    {
        // Überprüfen, ob die benötigten Felder vorhanden sind
        if (isset($cart_item['aj_width'], $cart_item['aj_height'], $cart_item['aj_text'], $cart_item['color-select'], $cart_item['aj_finish'])) {

            // Array mit den Feldnamen und Werten
            $custom_fields = [
                'Wunschtext' => esc_html($cart_item['aj_text']),
                'Oberfläche' => esc_html($cart_item['aj_finish']),
                'Farbe' => esc_html($cart_item['color-select']),
                'Breite' => esc_html($cart_item['aj_width'] . ' cm'),
                'Höhe' => esc_html($cart_item['aj_height'] . ' cm'),
            ];

            // Füge die benutzerdefinierten Felder zum Item-Daten-Array hinzu
            foreach ($custom_fields as $name => $value) {
                $item_data[] = array(
                    'name' => $name,
                    'value' => $value,
                );
            }
        }

        return $item_data;
    }

    public function aj_vinyl_custom_price_display($price, $product)
    {
        // Auf der Produktseite Preis entfernen
        if (is_product()) {
            return '<span class="from-price">ab ' . wc_price($product->get_price()) . '</span>';
        }

        // Auf Kategorie- und Shop-Seiten "Ab"-Preis anzeigen
        if (is_shop() || is_product_category()) {
            if ($product->is_type('variable')) {
                // Preisbereich für variable Produkte
                $prices = $product->get_variation_prices(true);
                $min_price = current($prices['price']);
                return '<span class="from-price">ab ' . wc_price($min_price) . '</span>';
            } elseif ($product->is_type('simple')) {
                // Einfaches Produkt
                return '<span class="from-price">ab ' . wc_price($product->get_price()) . '</span>';
            }
        }
    }
}
