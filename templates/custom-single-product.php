<?php


if (!defined('ABSPATH')) {
    exit;
}
// Sicherheit

global $product;

if (!$product || !is_a($product, 'WC_Product')) {
    $product = wc_get_product(get_the_ID());
}

if (!$product->is_purchasable()) {
    return;
}


$product_id = $product ? $product->get_id() : 0;
get_header('shop');
do_action( 'woocommerce_before_main_content' );
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>


<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>
<form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>

    <div id="ajvinyl-designer-wrapper">
        <input type="hidden" id="product-id" value="<?php echo $product_id; ?>">
        <!-- Linker Bereich -->
        <div id="ajpreview-section-and-table">
            <div id="ajpreview-section">
                <div id="ajvinyl-canvas-container">
                    <canvas id="ajvinyl-canvas"></canvas>
                </div>

                <div class="ajvinyl-can-work-box">
                    <div id="ayvinyl-can-svg"></div>
                    <div id="ajvinyl-canvas-container">
                        <canvas id="ajvinyl-canvas-work"></canvas>
                    </div>

                </div>

            </div>
            <div id="aj-error-message" style="color: red; display: none; text-align: center;"></div>
            <div id="ajpreview-dimensions" style="text-align: center; margin: 10px; font-size: 16px; font-weight: bold;">0 cm Breite x 0 cm Höhe</div>
            <p class="ajpreview-info" style="text-align: center;">Die Vorschau dient lediglich der visuellen Darstellung und entspricht nicht exakt den tatsächlichen Farben und Schriften.</p>
            <p class="ajpreview-info" style="text-align: center;">Die Höhenangaben für jede Zeile sind Schätzwerte und können je nach Umständen leicht variieren.</p>
            <table id="output-table">
                <thead>
                    <tr>
                        <th>Zeile</th>
                        <th>Höhe</th>
                        <th>Text</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- JavaScript wird hier Zeilen hinzufügen -->
                </tbody>
            </table>



        </div>


        <!-- Rechter Bereich -->
        <div id="ajsettings-sidebar">
            <details class="ajaccordion-item" open>
                <summary>Beschriftung<span class="ajtoggle-icon">&#9660;</span></summary>
                <div class="ajcontent-beschriftung">
                    <textarea type="text" placeholder="Dein Text" id="ajvinyl-text" name="aj_text" oninput="generateLineSettings()"></textarea>
                    <div id="ajline-controls"></div>
                </div>
            </details>

            <details class="ajaccordion-item" open>
                <summary>Aufkleber Größe<span class="ajtoggle-icon">&#9660;</span></summary>
                <div class="ajcontent">
                    <div class="ajsize-input">

                        <div id="ajvinyl-widthSection">
                            <label for="ajvinyl-widthInput">Breite (cm):</label>
                            <input type="number" id="ajvinyl-widthInput" value="15" name="aj_width" step=".01" oninput="ajvinyl_updateDesigner()">
                        </div>
                        <div class="ajtoggle-container">

                            <label class="ajtoggle-switch">
                                <input type="checkbox" id="ajdimensionToggle" onclick="ajvinyl_toggleInput()">
                                <span class="ajslider"></span>
                            </label>


                        </div>
                        <div id="ajvinyl-heightSection">
                            <label for="ajvinyl-heightInput">Höhe (cm):</label>
                            <input type="number" id="ajvinyl-heightInput" name="aj_height" value="2" step=".01" oninput="ajvinyl_updateDesigner()">

                        </div>


                    </div>

                </div>
            </details>

            <details class="ajaccordion-item" open>
                <summary>Folienfarbe<span class="ajtoggle-icon">&#9660;</span></summary>
                <div class="ajcontent">
                    <div class="ajcolor-selection">
                        <label for="ajfinish-select">Oberfläche:</label>
                        <div id="ajfinish-container"></div>

                        <label for="ajcolor-select">Farbe:</label>
                        <div id="ajcolor-container">
                            <select id="ajcolor-select" name="color-select" disabled>
                                <option value="">Bitte wählen</option>
                            </select>
                        </div>
                    </div>
                </div>
            </details>



            <div id="ajprice-calculation-box">
                <h2>Preisberechnung</h2>
                <table>
                    <tr>
                        <td>Abmessung:</td>
                        <td id="ajsizedisplay">0 cm x 0 cm = 0 m²</td>
                    </tr>
                    <tr>
                        <td>Variante:</td>
                        <td id="ajfinish-text">Bitte Variante auswählen</td>
                    </tr>
                    <tr>
                        <td>Farbe:</td>
                        <td id="ajcolor-text">Bitte Farbe auswählen</td>
                    </tr>


                    <tr>
                        <td><strong>Ihr Preis:</strong></td>
                        <td class="final-price"><span class="ajprice"></span>
                            <p><span>pro stk.</span></p><span><?= do_action('woocommerce_after_shop_loop_item'); ?></span>
                        </td>
                    </tr>
                </table>
            </div>


            <?php do_action('woocommerce_before_add_to_cart_button'); ?>


            <div id="add-to-cart-section">
                <?php

                do_action('woocommerce_before_add_to_cart_quantity');

                woocommerce_quantity_input(
                    array(
                        'min_value' => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
                        'max_value' => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
                        'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
                    )
                );

                do_action('woocommerce_after_add_to_cart_quantity');
                ?>
                <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

                <?php do_action('woocommerce_after_add_to_cart_button'); ?>
                <div class="aj-discount-table">
                    <h3 class="aj-table-title">Rabatt-Tabelle</h3>
                    <div id="discountRows">
                        <!-- Dynamische Zeilen werden hier eingefügt -->
                    </div>
                  
                    <button id="toggleButton" class="aj-toggle-btn button" style="margin-top: 12px;">Mehr anzeigen</button>

                </div>

                </div>
                </div>
            </div>

</form>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>
<?php
do_action('woocommerce_after_single_product_summary');
do_action('woocommerce_after_main_content');
?>
</div>
<?php
do_action('woocommerce_after_single_product');
do_action('woocommerce_before_footer');

get_footer();
