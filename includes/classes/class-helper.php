<?php

namespace AJ_VINYL\Includes\Classes;

if (!defined('ABSPATH')) {
    exit;
}


class Helper
{
    public function __construct() {}

    public static function get_product_base_price($product)
    {

        return floatval($product->get_price());
    }

    public static function qmCalc($height, $width)
    {
        $qm = round(($height / 100) * ($width / 100), 4);
        if ($qm === 0) {
            $qm = 0.001;
        }
        return $qm;
    }

    public static function getData($product_id)
    {
        if (!empty(get_post_meta($product_id, '_aj_vinyl_data', true))) {
            $vinyl_data_json = get_post_meta($product_id, '_aj_vinyl_data', true);
            return json_decode($vinyl_data_json, true);
        }

        return null;
    }

    public static function is_plugin_active_for_product($product_id)
    {
        $vinyl_data = self::getData($product_id);
        return $vinyl_data !== null && $vinyl_data['enable_vinyl'] === 'yes';
    }

    public static function getDiscountRule()
    {
        return [
            ['quantity' => 2, 'discount' => 0.05],
            ['quantity' => 5, 'discount' => 0.1],
            ['quantity' => 10, 'discount' => 0.15],
            ['quantity' => 20, 'discount' => 0.2],
            ['quantity' => 30, 'discount' => 0.25],
            ['quantity' => 50, 'discount' => 0.35],
            ['quantity' => 60, 'discount' => 0.4],
            ['quantity' => 100, 'discount' => 0.5],
            ['quantity' => 250, 'discount' => 0.6],
            ['quantity' => 500, 'discount' => 0.7],
        ];
    }

    public static function getDiscount($originalPrice, $area)
    {

        $product_price = $originalPrice / $area;
        $minPreis = 28;
        $target_price = max($product_price * (1 - 94 / 100), $minPreis);


        // Gesamtpreisreduktion, die auf den Rabatt angewendet werden muss
        $price_reduction = $product_price - $target_price;

        // Die angegebenen Stückzahlen, jetzt mit 1 Stück als symbolische Menge
        $quantities = [1, 2, 5, 10, 20, 30, 50, 100, 250, 500, 1000];

        // Berechnung der Anzahl der Schritte - Rabatt beginnt ab Menge 2 bis 1000
        $steps = count($quantities);

        // Berechnung des Rabatts pro Schritt
        $discount_per_step = $price_reduction / ($steps - 1); // Rabatt pro Schritt

        // Array für das Ergebnis
        $result = [];

        foreach ($quantities as $index => $quantity) {
            // Rabatt nur für Stückzahl >= 2 berechnen
            if ($quantity >= 2) {
                // Berechnung des Rabatts für die aktuelle Menge
                $discount = $discount_per_step * $index; // Rabatt beginnt bei Menge 2
                $end_price = $product_price - $discount;

                // Sicherstellen, dass der Rabatt den maximalen Rabatt nicht überschreitet
                if ($end_price < $target_price) {
                    $end_price = $target_price;
                    $discount = $product_price - $target_price;
                }
            } else {
                // Für Menge 1 gibt es keinen Rabatt, nur den vollen Preis
                $discount = 0;
                $end_price = $product_price;
            }

            // Werte zum Ergebnis-Array hinzufügen
            $result[] = [
                'quantity' => $quantity,
                'discount' => round($discount, 2),
                'end_price' => $end_price
            ];
        }

        // Array zurückgeben
        return $result;
    }

    public static function calcDiscountPrice($height, $width, $quantity, $product_id, $originalPrice)
    {
        $area = self::qmCalc($height, $width);
        $discountArray = self::getDiscount($originalPrice, $area);

        // Passenden Rabatt anhand der Menge aus der Rabatt-Tabelle finden
        $newPriceQm = 0; // Standardmäßig kein Rabatt

        foreach ($discountArray as $discount) {
            if ($quantity >= $discount['quantity']) {
                $newPriceQm = $discount['end_price'];
            } else {
                break; // Überspringe die verbleibenden Werte, da die Tabelle aufsteigend ist
            }
        }

        return round($newPriceQm * $area, 2);
    }



    public static function priceCalc($height, $width, $product_id)
    {
        $vinyl_data = self::getData($product_id);

        if ($vinyl_data != null) {
            $priceqm = $vinyl_data['price_per_sq_m'];
            $extra_price = $vinyl_data['extra_price'];
            $basePricePerSquareMeter = $priceqm;
            $additionalCostFactor = $extra_price;
        }
        $area = self::qmCalc($height, $width);


        return round(($basePricePerSquareMeter * $area) + ($additionalCostFactor), 2);
    }

    public static function getPriceData($product_id)
    {
        $vinyl_data = self::getData($product_id);


        if ($vinyl_data != null) {
            $priceqm = $vinyl_data['price_per_sq_m'];
            $extra_price = $vinyl_data['extra_price'];
            $priceqm = $vinyl_data['price_per_sq_m'];
            $extra_price = $vinyl_data['extra_price'];

            return [
                'priceqm' => $priceqm,
                'extraprice' => $extra_price,
            ];
        }

        return;
    }
}
