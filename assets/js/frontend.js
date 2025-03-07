var textbox;
var workcanvas = new fabric.Canvas("ajvinyl-canvas-work");
var canvas = new fabric.Canvas("ajvinyl-canvas");
var finalArea;
var calc_count = 0;
var timeout;
var heightArray = [];



function ajvinyl_toggleInput() {
  var toggle = document.getElementById("ajdimensionToggle");
  var heightInput = document.getElementById("ajvinyl-heightInput");
  var widthInput = document.getElementById("ajvinyl-widthInput");

  if (toggle.checked) {
    // Höhe aktiv, Breite ausgegraut
    heightInput.disabled = false;
    widthInput.disabled = true;
  } else {
    // Breite aktiv, Höhe ausgegraut
    heightInput.disabled = true;
    widthInput.disabled = false;
  }
}

var pricedata;
var finalprice;



function getPrice() {
  const nonce = ajvinylforwoo.nonce;
  const productId = document.getElementById("product-id").value;

  // document.getElementById('ajvinyl-loader').style.display = 'block';
  if ((pricedata && pricedata.productid != productId) || !pricedata) {
    fetch(ajvinylforwoo.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        nonce: nonce,
        productId: productId,
      }),
    })
      .then((response) => {
        // document.getElementById('ajvinyl-loader').style.display = 'none';
        return response.json();
      })
      .then((data) => {
        
        pricedata = data;

       
        //return data.price;
      })
      .catch((error) => console.error("Error:", error));
  }

}

function calcprice(area) {
  if (!pricedata && calc_count < 30) {
    setTimeout(function () {
     
      calcprice(finalArea);
      calc_count++;
    }, 60);
    return;
  }

  if (!pricedata && calc_count >= 30) {
    setTimeout(function () {
      calc_count = 0;
     
      getPrice();
      calcprice(finalArea);
    }, 60);

    return;
  }

  if (area == 0){
    area = 0.001;
  }

  let normalprice;
  let price;

  normalprice = ajround(pricedata.priceqm * area + pricedata.extraprice, 2);
  const discountData = getDiscountedPrices(normalprice, area);

  finalprice = normalprice;

  // Runden auf 2 Dezimalstellen
  calc_count = 0;
  var priceElement = document.querySelector(".ajprice");


  // Setze den neuen Preis mit der gewünschten Struktur
  setTimeout(function () {
  const quantityInput = document.querySelector("input.qty");
  const quantity = parseInt(quantityInput.value) || 1; // Eingabemenge, Standard 1
  let newPriceQm = normalprice; // Standardpreis

  // Durchlaufe das Rabatt-Array
  discountData.forEach((discount) => {
    if (quantity >= discount.quantity) {
      newPriceQm = discount.endPrice; // Aktualisiere den Preis pro qm
    } else {
      return; // Breche die Schleife ab
    }
  });

  // Rabattierter Preis berechnen
  const price = ajround(newPriceQm * area, 2);

  // Anzeige des Preises
  if (quantity === 1) {
    priceElement.innerHTML = `<bdi>${price
      .toFixed(2)
      .replace(".", ",")}&nbsp;<span class="woocommerce-Price-currencySymbol">€</span></bdi>`;
  } else {
    priceElement.innerHTML = `
      <span style="text-decoration: line-through; color: #888;">
          ${normalprice.toFixed(2).replace(".", ",")}&nbsp;€
      </span> 
      <span style="color: red; font-weight: bold; padding-left: 10px;">
          ${price.toFixed(2).replace(".", ",")}&nbsp;€
      </span>
    `;
  }

  createDiscountTable(normalprice, area);
}, 10);


  // document.querySelector('.amount').textContent = `${(Math.round(price * 100) / 100).toString().replace('.', ',')} €`;
  return Math.round(price * 100) / 100;
}

function getDiscountedPrices(originalPrice, area) {
  const minPreis = 28; // Mindestpreis
  const productPrice = originalPrice / area; // Preis pro Quadratmeter
  const targetPrice = Math.max(productPrice * (1 - 94 / 100), minPreis);

  // Gesamtpreisreduktion
  const priceReduction = productPrice - targetPrice;

  // Definierte Stückzahlen
  const quantities = [1, 2, 5, 10, 20, 30, 50, 100, 250, 500, 1000];

  // Anzahl der Schritte
  const steps = quantities.length;

  // Rabatt pro Schritt
  const discountPerStep = priceReduction / (steps - 1);

  // Array für das Ergebnis
  const result = [];

  quantities.forEach((quantity, index) => {
    let discount = 0;
    let endPrice = productPrice;

    if (quantity >= 2) {
      // Berechnung des Rabatts für die aktuelle Menge
      discount = discountPerStep * index;
      endPrice = productPrice - discount;

      // Sicherstellen, dass der Rabatt den maximalen Rabatt nicht überschreitet
      if (endPrice < targetPrice) {
        endPrice = targetPrice;
        discount = productPrice - targetPrice;
      }
    }

    // Für Menge 1 gibt es keinen Rabatt
    if (quantity === 1) {
      discount = 0;
      endPrice = productPrice;
    }

    // Werte zum Ergebnis-Array hinzufügen
    result.push({
      quantity: quantity,
      discount: Math.round(discount * 100) / 100, // Auf 2 Dezimalstellen runden
      endPrice: endPrice,
    });
  });

  return result;
}

function createDiscountTable(originalPrice, area) {
  const rowsContainer = document.getElementById("discountRows");
  const toggleButton = document.getElementById("toggleButton");

  rowsContainer.innerHTML = "";

  // Hole die berechneten Preise aus der getDiscountedPrices-Funktion
  const discountsArray = getDiscountedPrices(originalPrice, area);
  const discounts = discountsArray.slice(1);

  // Anzahl der sichtbaren Zeilen
  const visibleRowsCount = 5;

  // Tabelle dynamisch erstellen
  discounts.forEach((discount, index) => {
    const row = document.createElement("div");
    row.classList.add("aj-row");

    // Sichtbare oder versteckte Zeilen markieren
    if (index < visibleRowsCount) {
      row.classList.add("aj-visible");
    } else {
      row.classList.add("aj-hidden");
    }

    // Zellen erstellen
    const quantityCell = document.createElement("div");
    quantityCell.classList.add("aj-cell");
    quantityCell.textContent = `Ab ${discount.quantity} Stück`;

    const priceCell = document.createElement("div");
    priceCell.classList.add("aj-cell");
    priceCell.textContent = `${ajround(discount.endPrice * area, 2).toFixed(2).replace(".", ",")} €`;

    // Zellen in die Zeile einfügen
    row.appendChild(quantityCell);
    row.appendChild(priceCell);

    // Zeile in den Container einfügen
    rowsContainer.appendChild(row);

    // Separator hinzufügen
    if (index < discounts.length - 1) {
      const separator = document.createElement("hr");
      separator.classList.add("aj-separator");
      if (index >= visibleRowsCount - 1) {
        separator.classList.add("aj-hidden");
      }
      rowsContainer.appendChild(separator);
    }
  });

  // Button-Logik für Ein- und Ausblenden
  let isExpanded = false;
  toggleButton.addEventListener("click", (event) => {
    event.preventDefault();
    isExpanded = !isExpanded;
    const rows = document.querySelectorAll(".aj-row");
    const separators = document.querySelectorAll(".aj-separator");

    rows.forEach((row, index) => {
      if (index >= visibleRowsCount) {
        row.classList.toggle("aj-hidden", !isExpanded);
      }
    });

    separators.forEach((separator, index) => {
      if (index >= visibleRowsCount - 1) {
        separator.classList.toggle("aj-hidden", !isExpanded);
      }
    });

    toggleButton.textContent = isExpanded ? "Weniger anzeigen" : "Mehr anzeigen";
  });
}



document.addEventListener("DOMContentLoaded", function () {
  const quantityInput = document.querySelector("input.qty"); // Eingabefeld für Menge
  const priceElement = document.querySelector(".ajprice"); // Preiselement auf der Produktseite

  if (priceElement) {
    quantityInput.addEventListener("input", function () {
      
      
      
      setTimeout(function () {
        calcprice(finalArea);
        
      }, 10);
    });

    const quantityButtons = document.querySelectorAll(
      ".quantity .plus, .quantity .minus"
    );
    quantityButtons.forEach((button) =>
      button.addEventListener("click", function () {
        
        setTimeout(function () {
          calcprice(finalArea);
        }, 10);
      })
    );
  }
});



let fonts = [];
let aligns = [];
let bolds = [];
let kursivs = [];
let spacings = [];
let fontsizes = [];

function loadFontsinCss() {
  fontsData.forEach((font) => {
    const fontPath = `${ajpluginFontsUrl}${font.name}.woff`;

    // @font-face Regel erstellen
    const fontFaceRule = `
      @font-face {
        font-family: '${font.name}';
        src: url('${fontPath}') format('woff');
        font-weight: normal;
        font-style: normal;
      }
    `;

    // Neuen <style>-Tag erstellen
    const styleTag = document.createElement("style");

    // Regel sicher einfügen
    try {
      styleTag.appendChild(document.createTextNode(fontFaceRule));
    } catch (e) {
      // Fallback für ältere Browser
      styleTag.styleSheet.cssText = fontFaceRule;
    }

    // Den Style-Tag in den Head einfügen
    document.head.appendChild(styleTag);

    console.log(`Schriftart "${font.name}" hinzugefügt mit Pfad: ${fontPath}`);
  });
}


function generateLineSettings() {
  const textInput = document.getElementById("ajvinyl-text").value;
  const lines = textInput.split("\n");
  const lineControlsDiv = document.getElementById("ajline-controls");
  lineControlsDiv.innerHTML = ""; // Reset previous controls

  while (fonts.length < lines.length) {
    fonts.push("Arial");
    aligns.push("left");
    bolds.push(false);
    kursivs.push(false);
    spacings.push(0);
    fontsizes.push(100);
  }
  if (fonts.length > lines.length) {
    fonts.splice(lines.length);
    aligns.splice(lines.length);
    bolds.splice(lines.length);
    kursivs.splice(lines.length);
    spacings.splice(lines.length);
    fontsizes.splice(lines.length);
  }

  lines.forEach((line, index) => {
    const div = document.createElement("div");
    div.className = "ajline-settings";

    const lineNumber = document.createElement("div");
    lineNumber.className = "ajline-number";
    lineNumber.textContent = `#${index + 1} Zeile: `;

    const fontSelect = createFontDropdown(
      `ajfont-select-${index}`,
      fonts[index]
    );
    fontSelect.name = `font-${index}`;
    fontSelect.onchange = (event) => {
      event.preventDefault();
      fonts[index] = fontSelect.value;
   
      updateStyleButtons(index, fontSelect.value);
      ajvinyl_updateDesigner();
      generateLineSettings(); // To prevent button default actions
    };

    const alignButtons = createAlignButtons(index);

    const selectedFontData = fontsData.find(
      (font) => font.name === fontSelect.value
    );
    const boldButton = createStyleButton(
      "B",
      "bold",
      index,
      bolds[index],
      selectedFontData
    );
    boldButton.id = `ajbold-button-${index}`;
    const kursivButton = createStyleButton(
      "I",
      "italic",
      index,
      kursivs[index],
      selectedFontData
    );
    kursivButton.id = `ajitalic-button-${index}`; // Eindeutige ID für Kursiv-Button

    const spaceLabel = document.createElement("label");
    spaceLabel.htmlFor = `ajspacing-input-${index}`; // Verknüpft das Label mit dem Eingabefeld
    spaceLabel.innerText = "Abstand";

    const spacingInput = document.createElement("input");
    spacingInput.type = "number";
    spacingInput.id = `ajspacing-input-${index}`;
    spacingInput.name = `spacing-${index}`;
    spacingInput.value = spacings[index] || 0; // Setze den Standardwert auf 0
    spacingInput.oninput = () => {
      spacings[index] = parseInt(spacingInput.value, 10);
      ajvinyl_updateDesigner();
    };

    const sizeLabel = document.createElement("label");
    sizeLabel.htmlFor = `ajsize-input-${index}`; // Verknüpft das Label mit dem Eingabefeld
    sizeLabel.innerText = "Schriftgröße";

    const sizeInput = document.createElement("input");
    sizeInput.type = "number";
    sizeInput.id = `ajsize-input-${index}`;
    sizeInput.name = `size-${index}`;
    sizeInput.value = fontsizes[index] || 100; // Setze den Standardwert auf 100
    sizeInput.oninput = () => {
      fontsizes[index] = parseInt(sizeInput.value, 10);
      ajvinyl_updateDesigner();
    };

    const spacesizeDiv = document.createElement("div");
    spacesizeDiv.className = "ajspace-input";

    const labelrow = document.createElement("div");
    labelrow.className = "ajlabel-row";

    const inputrow = document.createElement("div");
    inputrow.className = "ajinput-row";

    const styleDiv = document.createElement("div");
    styleDiv.className = "ajstyle-buttons";

    styleDiv.appendChild(alignButtons);
    styleDiv.appendChild(boldButton);
    styleDiv.appendChild(kursivButton);
    labelrow.appendChild(spaceLabel);
    labelrow.appendChild(sizeLabel);
    inputrow.appendChild(spacingInput);
    inputrow.appendChild(sizeInput);
    spacesizeDiv.appendChild(labelrow);
    spacesizeDiv.appendChild(inputrow);

    div.appendChild(lineNumber);
    div.appendChild(fontSelect);
    div.appendChild(styleDiv);
    div.appendChild(spacesizeDiv);

    lineControlsDiv.appendChild(div);

    updateStyleButtons(index, fonts[index]);
  });
  ajvinyl_updateDesigner();
}

function createFontDropdown(id, selectedValue) {
  const fontNames = fontsData.map((font) => font.name); // Extrahiert alle Schriftartnamen aus fontsData
  return createDropdown(id, fontNames, selectedValue);
}

function createDropdown(id, options, selectedValue) {
  const selectElement = document.createElement("select");
  selectElement.id = id;
  selectElement.className = "ajstyled-select";
  options.forEach((option) => {
    const optionElement = document.createElement("option");
    optionElement.value = option;
    optionElement.text = option;
    optionElement.selected = option === selectedValue;
    optionElement.style.fontFamily = option;
    selectElement.appendChild(optionElement);
  });
  return selectElement;
}

function createAlignButtons(index) {
  const alignDiv = document.createElement("div");
  alignDiv.className = "ajalign-buttons";

  // Hidden-Input für die aktuelle Ausrichtung erstellen
  const alignHiddenInput = document.createElement("input");
  alignHiddenInput.type = "hidden";
  alignHiddenInput.name = `align-${index}`;
  alignHiddenInput.value = aligns[index];
  alignDiv.appendChild(alignHiddenInput);

  ["left", "center", "right"].forEach((align) => {
    const button = document.createElement("button");
    button.className = "ajalign-button";
    button.type = "button"; // Verhindert Submit
    button.innerHTML = `<img src="https://img.icons8.com/ios-filled/20/000000/align-${align}.png" alt="${align} align" />`;

    // Set active class if the saved alignment matches
    if (aligns[index] === align) {
      button.classList.add("active");
    }

    button.onclick = (event) => {
      event.preventDefault();

      // Entfernt die "active"-Klasse von allen anderen Ausrichtungsbuttons für diese Zeile
      document
        .querySelectorAll(`.ajalign-button[data-index="${index}"]`)
        .forEach((btn) => btn.classList.remove("active"));

      // Setzt den aktuellen Button auf "active"
      button.classList.add("active");

      // Aktualisiert die Ausrichtung im aligns-Array und im Hidden-Input
      aligns[index] = align;
      alignHiddenInput.value = align;

      ajvinyl_updateDesigner();
    };

    button.setAttribute("data-index", index);
    alignDiv.appendChild(button);
  });

  return alignDiv;
}

function createStyleButton(text, styleType, index, isActive, fontData) {
  const button = document.createElement("button");
  button.className = "ajstyle-button";
  button.textContent = text;
  button.type = "button"; // Setze den Typ des Buttons auf "button"

  // Prüft, ob der gewünschte Stil (bold oder italic) für die Schriftart verfügbar ist
  const isAvailable = fontData ? fontData[styleType] : false;

  // Button deaktivieren und Klasse `.disabled` hinzufügen, wenn der Stil nicht verfügbar ist
  button.disabled = !isAvailable;
  if (!isAvailable) {
    button.classList.add("disabled");
  } else if (isActive) {
    button.classList.add("active");
  }

  // Erstelle das versteckte Eingabefeld für den Stilstatus
  const hiddenInput = document.createElement("input");
  hiddenInput.type = "hidden";
  hiddenInput.name = `${styleType}-${index}`; // Name als 'styleType-index'
  hiddenInput.value = isActive ? "1" : "0"; // Wert '1' für aktiv, '0' für nicht aktiv

  // Event-Handler hinzufügen, wenn der Button aktiv ist
  if (isAvailable) {
    button.onclick = (event) => {
      event.preventDefault(); // Verhindert das Neuladen der Seite
      button.classList.toggle("active");
      isActive = !isActive; // Toggle den aktiven Status
      hiddenInput.value = isActive ? "1" : "0"; // Aktualisiere den Wert des versteckten Inputs

      // Aktualisiere die entsprechenden Arrays
      if (styleType === "bold") {
        bolds[index] = isActive;
      } else if (styleType === "italic") {
        kursivs[index] = isActive;
      }
      ajvinyl_updateDesigner();
    };
  }

  // Füge den Button und das versteckte Eingabefeld in das übergeordnete Element ein
  const container = document.createElement("div"); // Container für Button und Input
  container.appendChild(button);
  container.appendChild(hiddenInput);

  return container; // Rückgabe des Containers
}

function updateStyleButtons(index, fontName) {
  const selectedFontData = fontsData.find((font) => font.name === fontName);

  // Die Buttons für Bold und Italic holen und aktualisieren
  const boldButton = document.getElementById(`ajbold-button-${index}`);
  const italicButton = document.getElementById(`ajitalic-button-${index}`);

  // Bold-Button aktualisieren
  if (selectedFontData && selectedFontData.bold) {
    boldButton.disabled = false;
    boldButton.classList.remove("disabled");
  } else {
    boldButton.disabled = true;
    boldButton.classList.add("disabled");
    bolds[index] = false; // Falls Bold ausgeschaltet wird, Zustand zurücksetzen
    boldButton.classList.remove("active"); // Deaktiviere den aktiven Zustand
  }

  // Italic-Button aktualisieren
  if (selectedFontData && selectedFontData.italic) {
    italicButton.disabled = false;
    italicButton.classList.remove("disabled");
  } else {
    italicButton.disabled = true;
    italicButton.classList.add("disabled");
    kursivs[index] = false; // Falls Italic ausgeschaltet wird, Zustand zurücksetzen
    italicButton.classList.remove("active"); // Deaktiviere den aktiven Zustand
  }
}



function ajvinyl_updateDesigner() {
  const nonce = ajvinylforwoo.nonce;
  var text = document.getElementById("ajvinyl-text").value || "Dein Text";
  //const selectedDimension = document.getElementById("dimensionToggle").checked ? 'height' : 'width';
  var height = document.getElementById("ajvinyl-heightInput").value;
  var width = document.getElementById("ajvinyl-widthInput").value;
  const productId = document.getElementById("product-id").value;

  if (timeout) {
    clearTimeout(timeout);
  }
  timeout = setTimeout(function () {
    ajvinyl_createSVG(text, width, height);
  }, 150);
}

function ajvinyl_createSVG(text, widthCM, heightCM) {
  var textToSVGArray = [];

  var lines_count = 0,
    lines_loaded = 0;

  let widthPx = convertUnitToPixel(widthCM);
  let heightPx = convertUnitToPixel(heightCM);

  var svgDiv = document.getElementById("ayvinyl-can-svg");
  svgDiv.innerHTML = "";

 
  var maxWidth = 0,
    totalHeight = 0,
    svgcontent = "";

  var lines = text.split("\n");

  for (var i in lines) {
    var line = lines[i];
    let fontName = fonts[i];
    let fontWeight = bolds[i] ? "-bold" : "";
    let fontStyle = kursivs[i] ? "-italic" : "";
    const fontPath = `${ajpluginFontsUrl}${fontName}${fontWeight}${fontStyle}.woff`;
    opentype.load(
      fontPath,
      (function (index, line, i) {
        return function (err, font) {
          if (err !== null) {
            return;
          }
          textToSVGArray[index] = {
            textToSVG: new TextToSVG(font),
            line: line,
            index: i,
          };
          lines_loaded++;
        };
      })(lines_count, line, i)
    );
    lines_count++;
  }

  ajvinyl_load();

  function ajvinyl_load() {
    if (lines_count !== lines_loaded) {
      setTimeout(function () {
        ajvinyl_load();
      }, 10);
      return;
    }

    for (var i in textToSVGArray) {
      const align = aligns[i];
      var textToSVG = textToSVGArray[i].textToSVG;
      var line = textToSVGArray[i].line;
      var options = {
        x: 0,
        y: totalHeight,
        fontSize: fontsizes[i] || 100,
        anchor: "top " + (align || "left"),
        attributes: { fill: "black" },
      };
      var metrics = textToSVG.getMetrics(line, options);
      maxWidth = Math.max(maxWidth, metrics.width);
    }

    for (var i in textToSVGArray) {
      const align = aligns[i];
      var textToSVG = textToSVGArray[i].textToSVG;
      var line = textToSVGArray[i].line;
      if (i > 0) {
        totalHeight += spacings[i] ? parseInt(spacings[i]) : 0;
      }
      //totalHeight += -70;
      var x = 1;
      switch (align) {
        case "center":
          x += maxWidth / 2;
          break;
        case "right":
          x += maxWidth;
          break;
        default:
          break;
      }
      var options = {
        x: x,
        y: totalHeight,
        fontSize: fontsizes[i] || 100,
        anchor: "top " + (align || "left"),
        attributes: { fill: "black" },
      };
      var metrics = textToSVG.getMetrics(line, options);
      svgcontent += textToSVG.getPath(line, options);
      totalHeight += metrics.height;
      heightArray[i] = { font_size: fontsizes[i] };
    }

    var svg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${maxWidth}" height="${totalHeight}">`;
    svg += svgcontent;
    svg += "</svg>";
    svgDiv.innerHTML = svg;
 

    if (textbox) {
      workcanvas.remove(textbox);
      delete textbox;
    }
    fabric.loadSVGFromString(svg, function (objects, options) {
      for (var i in objects) {
        heightArray[i] = objects[i].getBoundingRect().height;
      }
      var loadedObject = fabric.util.groupSVGElements(objects);
      loadedObject.set({ left: 0, top: 0, padding: 0 });

      // Füge das Objekt zum unsichtbaren Canvas hinzu
      workcanvas.clear();
      workcanvas.add(loadedObject);
      workcanvas.renderAll();
      textbox = loadedObject;

      // Berechne das SVG auf dem sichtbaren Canvas basierend auf der Höhe (heightPx)
      setTimeout(() => {
        ajvinyl_calcSize(widthPx, heightPx, widthCM, heightCM, svg, lines);
      }, 50);
    });
  }
}

function ajvinyl_calcSize(widthPx, heightPx, widthCM, heightCM, svg, lines) {
  const selectedDimension = document.getElementById("ajdimensionToggle").checked
    ? "height"
    : "width";
  var heightInCm;
  var widthInCm;
  var zoom;

  // Bounding Box des SVG-Objekts vom unsichtbaren Canvas (textbox)
  var svgBoundingBox = textbox.getBoundingRect();

  // Wenn die Höhe leer ist, berechne sie aus der Breite
  if (selectedDimension !== "height") {
    heightPx = (widthPx / svgBoundingBox.width) * svgBoundingBox.height;
  }

  // Wenn die Breite leer ist, berechne sie aus der Höhe
  if (selectedDimension !== "width") {
    widthPx = (heightPx / svgBoundingBox.height) * svgBoundingBox.width;
  }

  // Setze die Höhe basierend auf der gewünschten Höhe (heightPx)
  if (svgBoundingBox.height !== heightPx) {
    // Berechne den Zoom-Faktor für die Skalierung
    zoom = heightPx / svgBoundingBox.height;
    textbox.scaleToHeight(heightPx, true);

    // Aktualisiere die neue Höhe und Breite basierend auf der Skalierung
    svgBoundingBox.height = heightPx;
    svgBoundingBox.width = textbox.getBoundingRect().width;
  }

  var canvas_new_width, canvas_new_height;
  var canvas_new_width = document.getElementById(
    "ajvinyl-canvas-container"
  ).offsetWidth;
  canvas_new_height = (canvas_new_width / widthPx) * heightPx;
  if (canvas_new_height > 500) {
    canvas_new_width = (500 / canvas_new_height) * canvas_new_width;
    canvas_new_height = 500;
  }

  // Setze die Breite des sichtbaren Canvas basierend auf der neuen Breite des SVG
  canvas.setWidth(canvas_new_width + 10);
  canvas.setHeight(canvas_new_height);

  // Lade das vorbereitete SVG in das sichtbare Canvas
  fabric.loadSVGFromString(svg, function (objects, options) {
    var visibleObject = fabric.util.groupSVGElements(objects);

    // Skalierung des SVG auf die Höhe des sichtbaren Canvas
    visibleObject.scaleToHeight(canvas.height, false);
    visibleObject.setCoords(); // Aktualisiere die Koordinaten nach der Skalierung

    // Zentriere das SVG innerhalb des sichtbaren Canvas
    visibleObject.set({
      left: (canvas.getWidth() - visibleObject.getScaledWidth()) / 2,
      top: (canvas.getHeight() - visibleObject.getScaledHeight()) / 2,
      originX: "left",
      originY: "top",
      hasControls: false,
      hasBorders: false,
      hasRotatingPoint: false,
      lockMovementX: true,
      lockMovementY: true,
      lockRotation: true,
    });

    // Füge das skalierte SVG zum sichtbaren Canvas hinzu
    canvas.clear();

    canvas.add(visibleObject);

    ajvinyl_addKaroBackground(canvas, 10);
    canvas.renderAll();
  });

  // Optional: Aktualisiere die Höhe in einem Eingabefeld (falls benötigt)
  heightInCm = ajround(
    ((widthPx / svgBoundingBox.width) * svgBoundingBox.height * 2.54) / 96,
    2
  );
  widthInCm = ajround(
    ((heightPx / svgBoundingBox.height) * svgBoundingBox.width * 2.54) / 96,
    2
  );


    let dimensionsheight = validateRowheight(heightArray, zoom);
    displayValidationError(dimensionsheight.error, "error-messagee");
    

  

  const tableBody = document
    .getElementById("output-table")
    .querySelector("tbody");

  // Tabelle zurücksetzen
  tableBody.innerHTML = "";

  // Neue Zeilen für jede Zeile im Text hinzufügen
  lines.forEach((line, index) => {
    const row = document.createElement("tr");
    

    const cellIndex = document.createElement("td");
    cellIndex.textContent = `#${index + 1}`;
    cellIndex.className = "line-number"; // Klasse für Zeilennummer
    row.appendChild(cellIndex);

    const cellHeight = document.createElement("td");
    cellHeight.textContent =
      lines.length <= 1 && selectedDimension == "width"
        ? ajround(heightInCm, 1).toString().replace(".", ",") + " cm"
        : convertPixelToUnit(ajround(heightArray[index] * zoom, 1))
            .toString()
            .replace(".", ",") + " cm"; // Festgelegte Zeilenhöhe
    cellHeight.className = "line-height"; // Klasse für Zeilenhöhe
    row.appendChild(cellHeight);

    const cellContent = document.createElement("td");
    cellContent.textContent = line;
    row.appendChild(cellContent);

    tableBody.appendChild(row);
  });

  if (selectedDimension == "width") {
    finalArea = ajround((heightInCm / 100) * (widthCM / 100), 4);
    calcprice(finalArea);

    dimensions = validateDimensions(widthCM, heightInCm);
    displayValidationError(dimensions.error, "error-message");
    
    document.getElementById("ajvinyl-heightInput").value = heightInCm;
    document.getElementById("ajsizedisplay").textContent = `${widthCM
      .toString()
      .replace(".", ",")} cm x ${heightInCm
      .toString()
      .replace(".", ",")} cm = ${finalArea.toString().replace(".", ",")} m²`;
    document.getElementById("ajpreview-dimensions").textContent = `${widthCM
      .toString()
      .replace(".", ",")} cm Breite x ${heightInCm
      .toString()
      .replace(".", ",")} cm Höhe`;
  }
  if (selectedDimension == "height") {
    finalArea = ajround((widthInCm / 100) * (heightCM / 100), 4);
    calcprice(finalArea);
    dimensions = validateDimensions(widthInCm, heightCM);
    displayValidationError(dimensions.error, "error-message");
    document.getElementById("ajvinyl-widthInput").value = widthInCm; // Aktualisiere die Breite
    document.getElementById("ajsizedisplay").textContent = `${widthInCm
      .toString()
      .replace(".", ",")} cm x ${heightCM
      .toString()
      .replace(".", ",")} cm = ${finalArea.toString().replace(".", ",")} m²`;
    document.getElementById("ajpreview-dimensions").textContent = `${widthInCm
      .toString()
      .replace(".", ",")} cm Breite x ${heightCM
      .toString()
      .replace(".", ",")} cm Höhe`;
  }
}

function ajvinyl_addKaroBackground(canvas, squareSize = 10) {
  // Erstelle ein neues Canvas-Element, das als Muster verwendet wird
  const patternCanvas = document.createElement("canvas");
  const patternContext = patternCanvas.getContext("2d");

  // Setze die Größe des Musters
  patternCanvas.width = squareSize;
  patternCanvas.height = squareSize;

  // Zeichne das Karomuster (Horizontale und vertikale Linien)
  patternContext.strokeStyle = "#e0e0e0"; // Farbe der Karo-Linien
  patternContext.lineWidth = 1; // Breite der Karo-Linien

  // Horizontale Linie
  patternContext.beginPath();
  patternContext.moveTo(0, 0);
  patternContext.lineTo(squareSize, 0);
  patternContext.stroke();

  // Vertikale Linie
  patternContext.beginPath();
  patternContext.moveTo(0, 0);
  patternContext.lineTo(0, squareSize);
  patternContext.stroke();

  // Verwende das Canvas als Muster für den Hintergrund
  const pattern = new fabric.Pattern({
    source: patternCanvas,
    repeat: "repeat", // Das Muster wird wiederholt
  });

  // Setze das Muster als Hintergrund des Canvas
  canvas.setBackgroundColor(
    { source: patternCanvas, repeat: "repeat" },
    canvas.renderAll.bind(canvas)
  );
}

function ajround(value, precision = 0) {
  const factor = Math.pow(10, precision + 1); // Eine Stufe höher für präzisere Zwischenberechnung
  const tempValue = Math.round(value * factor) / 10; // Zwischenwert auf exakt eine Dezimalstelle mehr runden
  return Math.round(tempValue) / Math.pow(10, precision); // Endwert runden und zurückgeben
}

function convertUnitToPixel(value) {
  var dpi = 96;
  var unitFac = 2.54;
  return ajround((value * dpi) / unitFac, 0);
}
function convertPixelToUnit(value, aufrund = 1) {
  var dpi = 96;
  var unitFac = 2.54;
  return ajround((value * unitFac) / dpi, aufrund);
}

function populateFinishDropdown() {
  const finishCounts = {};

  // Zähle die Farben pro Finish
  colorsData.forEach((item) => {
    finishCounts[item.finish] = (finishCounts[item.finish] || 0) + 1;
  });

  const finishNames = Object.keys(finishCounts);
  const finishDropdown = colorcreateDropdown("ajfinish-select", finishNames);

  // Füge den Dropdown zum Container hinzu
  document.getElementById("ajfinish-container").appendChild(finishDropdown);

  // Event-Listener für das Finish-Dropdown
  finishDropdown.addEventListener("change", function () {
    const selectedFinish = this.value;
    populateColorDropdown(selectedFinish);
    document.getElementById("ajfinish-text").textContent = selectedFinish
      ? selectedFinish
      : "Bitte Variante auswählen";
  });
}

// Funktion zum Befüllen des Farbdropdowns basierend auf dem ausgewählten Finish
function populateColorDropdown(selectedFinish) {
  const colorSelect = document.getElementById("ajcolor-select");
  colorSelect.innerHTML = '<option value="">Bitte wählen</option>'; // Leere vorherige Optionen
  colorSelect.disabled = false; // Aktiviere das Farbdropdown

  // Füge die Farben basierend auf dem Finish hinzu
  colorsData.forEach((item) => {
    if (item.finish === selectedFinish) {
      const option = document.createElement("option");
      option.value = item.color;
      option.textContent = item.color;
      colorSelect.appendChild(option);
    }
  });

  // Wenn keine Farben vorhanden sind, deaktiviere das Farbdropdown
  if (colorSelect.options.length <= 1) {
    colorSelect.disabled = true; // Wenn nur die Platzhalter-Option vorhanden ist
  }

  // Event-Listener für die Farbwahl
  colorSelect.addEventListener("change", function () {
    const selectedColor = this.value;

    // Gib den Farbwert aus, z.B. im Textfeld
    document.getElementById("ajcolor-text").textContent = selectedColor
      ? selectedColor
      : "Bitte Farbe auswählen";
  });
}

// Funktion zum Erstellen des Dropdowns
function colorcreateDropdown(id, options) {
  const selectElement = document.createElement("select");
  selectElement.id = id;
  selectElement.className = "ajcolor-select";
  selectElement.name = "aj_finish";
  const defaultOption = document.createElement("option");
  defaultOption.value = "";
  defaultOption.text = "Bitte wählen";
  selectElement.appendChild(defaultOption);

  options.forEach((option) => {
    const optionElement = document.createElement("option");
    optionElement.value = option;
    optionElement.text = option;
    selectElement.appendChild(optionElement);
  });

  return selectElement;
}

document.addEventListener("submit", function () {
  // Prüfen, ob das Feld existiert
  var feld = document.querySelector("#ajdimensionToggle"); // Ersetze '#dein-feld-selector' mit dem tatsächlichen Selektor
  if (feld) {
    var heightInput = document.getElementById("ajvinyl-heightInput");
    var widthInput = document.getElementById("ajvinyl-widthInput");
    // Beide Felder aktivieren, damit sie gesendet werden
    heightInput.disabled = false;
    widthInput.disabled = false;
  }
});

// Initiales Setup
document.addEventListener("DOMContentLoaded", function () {
  // Prüfen, ob das Feld existiert
  var feld = document.querySelector("#ajdimensionToggle"); // Ersetze '#dein-feld-selector' mit dem tatsächlichen Selektor
  if (feld) {
    // Deine Funktion ausführen, wenn das Feld vorhanden ist
    loadFontsinCss();
    getPrice();
    ajvinyl_toggleInput(); // Initiale Sichtbarkeit der Eingabefelder
    generateLineSettings();
    populateFinishDropdown();
    //ajvinyl_updateDesigner();
  }
});

window.addEventListener("resize", function () {
  var feld = document.querySelector("#ajdimensionToggle"); // Ersetze '#dein-feld-selector' mit dem tatsächlichen Selektor
  if (feld) {
    // Deine Funktion ausführen, wenn das Feld vorhanden ist
    generateLineSettings();
  }
});




function validateDimensions(width, height) {
    let error = ""; // Fehlernachricht

    // Wenn beide Werte zu groß sind (Breite > 70 und Höhe > 70)
    if (width > 70 && height > 70) {
        error = "Entweder die Breite darf maximal 70 cm betragen oder die Höhe maximal 70 cm.";
    }
    // Wenn die Breite mehr als 70 cm ist, dann darf die Höhe maximal 200 cm betragen
    else if (width > 70 && height > 200) {
        error = "Wenn die Breite mehr als 70 cm beträgt, darf die Höhe maximal 200 cm betragen.";
    }
    // Wenn die Höhe mehr als 70 cm ist, dann darf die Breite maximal 200 cm betragen
    else if (height > 70 && width > 200) {
        error = "Wenn die Höhe mehr als 70 cm beträgt, darf die Breite maximal 200 cm betragen.";
    }
    else if (height > 200){
        error = "Die Höhe liegt über 200 cm.";

    }
    else if (width > 200){
        error = "Die Breite liegt über 200 cm.";

    }

    // Rückgabe der validierten Werte und der Fehlernachricht
    return { width, height, error };
}

function validateRowheight(arr, zoom){
    let error = ""; // Fehlernachricht

    

      // Überprüfen, ob ein Wert im Array kleiner als 2 ist

      const hasValueUnderTwo = arr.some(value => {
        const convertedValue = convertPixelToUnit(value * zoom, 2);
      
        return convertedValue < 2;
    });

      if (hasValueUnderTwo) {
        
        error = "Mindestens eine Zeile unterschreitet die Mindesthöhe von 2 cm.";
      } 
    // Rückgabe der validierten Werte und der Fehlernachricht
    return { error };

}



// Fehler-Array, um Fehler mit ihren IDs zu verwalten
var validationErrors = []; // Fehler-Array zur Verwaltung der Fehler

// Funktion zur Anzeige von Fehlern und Deaktivierung des Buttons
function displayValidationError(error, id) {
  const errorContainer = document.getElementById("aj-error-message"); // Dein gemeinsames Fehler-Container-Element
  
  if (error) {
    // Füge den Fehler dem Array hinzu, falls er noch nicht existiert
    if (!validationErrors.some(e => e.id === id)) {
      validationErrors.push({ id, error });
    }

    // Alle Fehler anzeigen
    errorContainer.innerHTML = validationErrors.map(e => `<p>${e.error}</p>`).join('');
    errorContainer.style.display = "block"; // Zeige den Fehler-Container an
  } else {
    // Entferne den Fehler aus dem Array, wenn der Fehler nicht mehr existiert
    validationErrors = validationErrors.filter(e => e.id !== id);

    // Wenn keine Fehler mehr vorhanden sind, verstecke den Fehler-Container
    if (validationErrors.length === 0) {
      errorContainer.style.display = "none";
    } else {
      // Wenn noch Fehler vorhanden sind, zeige sie im Container
      errorContainer.innerHTML = validationErrors.map(e => `<p>${e.error}</p>`).join('');
    }
  }

  // Überprüfe, ob der Button aktiviert werden kann
  checkIfButtonCanBeEnabled();
}

// Funktion zur Überprüfung, ob der Button aktiviert werden kann
function checkIfButtonCanBeEnabled() {
  const addToCartButton = document.querySelector('button.single_add_to_cart_button');
  
  // Wenn mindestens ein Fehler vorhanden ist, wird der Button deaktiviert
  addToCartButton.disabled = validationErrors.length > 0;
}



