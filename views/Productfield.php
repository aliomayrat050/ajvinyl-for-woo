<div id="ajvinyl-designer">
    <input type="hidden" id="product-id" value="<?php echo $product_id; ?>">


    <!-- 1. Beschriftung -->
    <details class="accordion-item" open>
        <summary data-number="1.">
            Beschriftung
            <span class="toggle-icon">&#9660;</span>
        </summary>
        <div class="content-beschriftung">

            <div><textarea type="text" placeholder="Dein Text" id="ajvinyl-text" name="aj_text" oninput="generateLineSettings()"></textarea></div>
            <div id="line-controls"></div>
        </div>
    </details>



    <details class="accordion-item" open>
        <summary data-number="2.">
            Aufkleber Größe
            <span class="toggle-icon">&#9660;</span>
        </summary>
        <div class="ajcontent">
            <div class="form-group" id="ajvinyl-widthSection">
                <label for="ajvinyl-widthInput">Breite (cm):</label>
                <input type="number" id="ajvinyl-widthInput" value="15" name="aj_width" step=".01" oninput="ajvinyl_updateDesigner()">
                <div class="min-max">min = 1, max = 220</div>
            </div>
            <div class="toggle-container">

                <label class="toggle-switch">
                    <input type="checkbox" id="dimensionToggle" onclick="ajvinyl_toggleInput()">
                    <span class="slider"></span>
                </label>


            </div>
            <div class="form-group" id="ajvinyl-heightSection">
                <label for="ajvinyl-heightInput">Höhe (cm):</label>
                <input type="number" id="ajvinyl-heightInput" name="aj_height" value="2" step=".01" oninput="ajvinyl_updateDesigner()">
                <div class="min-max">min = 1, max = 55</div>
            </div>
        </div>
    </details>

    <details class="accordion-item">
        <summary data-number="3.">
            Folienfarbe
            <span class="toggle-icon">&#9660;</span>
        </summary>
        <div class="ajcontent">
            <div class="color-selection">
                <label for="finish-select">Finish:</label>
                <div id="finish-container"></div>

                <label for="color-select">Farbe:</label>
                <div id="color-container">
                    <select id="color-select" name="color-select" disabled>
                        <option value="">Bitte wählen</option>
                    </select>
                </div>
            </div>
        </div>
    </details>











    <div id="ajvinyl-error" style="color: red;"></div>
    <div id="ajvinyl-heightDisplay">Gesamthöhe: 0 cm</div>
    <div id="ajvinyl-widthDisplay">Gesamtbreite: 0 cm</div>
    <div id="ajvinyl-qmDisplay">Maße: 0 m²</div>
    <div id="ajvinyl-priceDisplay">Gesamtpreis: 0 €</div>


</div>