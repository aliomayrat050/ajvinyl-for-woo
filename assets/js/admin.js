jQuery(document).ready(function ($) {
    // Event-Handler für den SVG-Download-Button
    $('.svg-create-btn').on('click', function (e) {
        e.preventDefault();
        // Design-Daten aus dem Button-Element extrahieren
        var designData = $(this).data('design-data');
        var orderNumber = $(this).data('order-number'); // Bestellnummer abrufen
        var orderItemPosition = $(this).data('order-item-position'); // Bestellposition abrufen

        // Hier kannst du den SVG-Download-Code wie in deinem vorherigen Beispiel integrieren
        // z.B. width und height aus designData holen, bevor du das SVG generierst
        var widthCM = designData.aj_width; // Beispiel, hier musst du die richtigen Daten verwenden
        var heightCM = designData.aj_height;

        if (designData) {

            var textToSVGArray = [];
            var lines_count = 0,
                lines_loaded = 0;

            var maxWidth = 0, totalHeight = 0, svgcontent = "";
            // Durchlaufe die Zeilen
            $.each(designData.style_status, function (i, line) {
                let fontName = line.font;
                let fontWeight = line.bold ? '-bold' : '';
                let fontStyle = line.italic ? '-italic' : '';
                const fontPath = `${ajpluginFontsUrl}${fontName}${fontWeight}${fontStyle}.woff`;

                opentype.load(
                    fontPath,
                    (function (index, line, i) {
                        return function (err, font) {
                            if (err !== null) {
                                return;
                            }
                            textToSVGArray[index] = { textToSVG: new TextToSVG(font), line: line, index: i };
                            lines_loaded++;
                        };
                    })(lines_count, line, i)
                );
                lines_count++;
            });

            ajvinyl_load();

            function ajvinyl_load() {
                if (lines_count !== lines_loaded) {
                    setTimeout(ajvinyl_load, 10);
                    return;
                }

                // Berechne die maximale Breite und die Höhe
                for (var i in textToSVGArray) {
                    var textToSVG = textToSVGArray[i].textToSVG;
                    var line = textToSVGArray[i].line;
                    var options = { x: 0, y: totalHeight, fontSize: line.size, anchor: "top " + (line.align || "left"), attributes: { fill: 'black' } };
                    var metrics = textToSVG.getMetrics(line.text, options);
                    maxWidth = Math.max(maxWidth, metrics.width);
                }


                for (var i in textToSVGArray) {
                    var textToSVG = textToSVGArray[i].textToSVG;
                    var line = textToSVGArray[i].line;
                    if (i > 0) {
                        totalHeight += textToSVGArray[i].line.spacing ? parseInt(textToSVGArray[i].line.spacing) : 0;
                    }
                    var x = 1;
                    switch (line.align) {
                        case "center":
                            x += maxWidth / 2;
                            break;
                        case "right":
                            x += maxWidth;
                            break;
                        default:
                            break;
                    }
                    var options = { x: x, y: totalHeight, fontSize: line.size, anchor: "top " + (line.align || "left"), attributes: { fill: 'black' } };
                    var metrics = textToSVG.getMetrics(line.text, options);
                    svgcontent += textToSVG.getPath(line.text, options);
                    totalHeight += metrics.height;

                }

                // Erstelle das SVG-Element
                var svg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${maxWidth}" height="${totalHeight}">`;
                svg += svgcontent;
                svg += "</svg>";

                // SVG-Blob erstellen
                var blob = new Blob([svg], { type: "image/svg+xml;charset=utf-8" });

                var filename = `${orderNumber}-${orderItemPosition}-${widthCM}x${heightCM}.svg`;

                // Temporären Download-Link erstellen
                var downloadLink = document.createElement("a");
                downloadLink.href = URL.createObjectURL(blob);
                downloadLink.download = filename; // Dateiname für den Download

                // Simulierten Klick auf den Download-Link auslösen
                document.body.appendChild(downloadLink); // Link zum DOM hinzufügen
                downloadLink.click(); // Klick simulieren
                document.body.removeChild(downloadLink); // Link wieder entfernen
            }
        } else {
            alert("Design-Daten fehlen.");
        }
    });

    $('.svg-create-btn-aj-social').on('click', function (e) {
        e.preventDefault();

        // Design-Daten aus dem Button-Element extrahieren
        var aj_social_designData = $(this).data('aj-social-design-data');
        var orderNumber = $(this).data('order-number'); // Bestellnummer abrufen
        var orderItemPosition = $(this).data('order-item-position'); // Bestellposition abrufen
        var ajsocial_icon_settings = $(this).data('ajsocial-icon-settings');
        var ajsocial_icon_path = $(this).data('icon-path');
        var ajsocial_fontsData = $(this).data('ajsocial-fonts-data');


        if (aj_social_designData) {
            let text = aj_social_designData.text;
            let widthCM = aj_social_designData.width;
            let heightCM = aj_social_designData.height;

            let textToSVGObject;
            let ajFontLoaded = false;



            const fontSize = ajsocial_icon_settings.fontsize ?? 100; // Schriftgröße
            const iconPath = `/wp-content/plugins/aj-social-for-woo/assets/social-svg/${ajsocial_icon_path}.svg`; // Pfad zum Icon
            const iconWidth = ajsocial_icon_settings.iconwidth ?? 115; // Breite des Icons in px
            const iconHeight = ajsocial_icon_settings.iconheight ?? 115; // Höhe des Icons in px
            const spacing = ajsocial_icon_settings.spacing ?? 10; // Abstand zwischen Icon und Text

            let maxWidth = 0,
                totalHeight = 0,
                svgcontent = "";

            // Annahme: Der Text ist eine einfache Zeichenkette ohne Zeilenumbrüche

            var selectedFontName = aj_social_designData.font;

            // Suche nach der Schriftart im ajsocial_fontsData Array
            var selectedFont = ajsocial_fontsData.find(function (font) {
                return font.name === selectedFontName;
            });

            if (selectedFont) {
                // Prüfen, ob die Schriftart fett (bold) ist
                var ajsocial_fontPath = `/wp-content/plugins/aj-vinyl-for-woo/assets/fonts/${selectedFont.name}`;

                // Wenn die Schriftart fett ist, füge '-bold' zum Dateinamen hinzu
                if (selectedFont.bold) {
                    ajsocial_fontPath += ""; // -bold an den Pfad anhängen
                }

                ajsocial_fontPath += ".woff"; // Füge das Dateiformat hinzu

                // Lade die Schriftart mit TextToSVG
                TextToSVG.load(ajsocial_fontPath, function (err, textToSVG) {
                    if (err) {
                        console.error("Fehler beim Laden der Schriftart:", err);
                        return;
                    }




                    // Optional: Speichere das TextToSVG-Objekt für spätere Verwendung
                    textToSVGObject = {
                        textToSVG: textToSVG,
                        text: text,
                    };

                    // Setze einen Indikator, dass die Schriftart erfolgreich geladen wurde
                    ajFontLoaded = true;
                });
            }



            ajsocial_load();

            function ajsocial_load() {
                if (!ajFontLoaded) {
                    setTimeout(function () {
                        ajsocial_load();
                    }, 10);
                    return;
                }

                // Lade das Icon (falls benötigt)
                fabric.loadSVGFromURL(iconPath, function (iconObjects, iconOptions) {
                    const icon = fabric.util.groupSVGElements(iconObjects, iconOptions);

                    // Berechne die Breite aller Textzeilen

                    let textToSVG = textToSVGObject.textToSVG;
                    let line = textToSVGObject.text;
                    let options = {
                        x: 0,
                        y: totalHeight,
                        fontSize: fontSize || 100,
                        anchor: "top left",
                        attributes: { fill: "black" },
                    };
                    let metrics = textToSVG.getMetrics(line, options);
                    maxWidth = Math.max(maxWidth, metrics.width);


                    // Erstelle das SVG für den Text

                    textToSVG = textToSVGObject.textToSVG;
                    line = textToSVGObject.text;



                    options = {
                        x: iconWidth + spacing, // Text beginnt rechts neben dem Icon
                        y: totalHeight,
                        fontSize: fontSize || 100,
                        anchor: "top left",
                        attributes: { fill: "black" },
                    };

                    metrics = textToSVG.getMetrics(line, options);
                    svgcontent += textToSVG.getPath(line, options);
                    totalHeight += metrics.height;


                    // Zentriere den Text vertikal zum Icon
                    const iconCenterY = iconHeight / 2;
                    const textCenterY = totalHeight / 2;
                    const verticalOffset = iconCenterY - textCenterY;

                    svgcontent = `<g transform="translate(0, ${verticalOffset})">${svgcontent}</g>`;

                    // Füge das Icon hinzu
                    fabric.loadSVGFromURL(iconPath, function (iconObjects, iconOptions) {
                        let iconGroup = fabric.util.groupSVGElements(iconObjects, iconOptions);
                        iconGroup.set({ left: 0, top: 0, padding: 0 });
                        iconGroup.scaleToWidth(iconWidth);
                        iconGroup.scaleToHeight(iconHeight);

                        // SVG finalisieren
                        let svg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${maxWidth + iconWidth + spacing}" height="${Math.max(totalHeight, iconHeight)}">`;
                        svg += `<g>${iconGroup.toSVG()}${svgcontent}</g>`;
                        svg += "</svg>";


                        // SVG-Blob erstellen
                        var blob = new Blob([svg], { type: "image/svg+xml;charset=utf-8" });

                        var filename = `${orderNumber}-${orderItemPosition}-${widthCM}x${heightCM}.svg`;

                        // Temporären Download-Link erstellen
                        var downloadLink = document.createElement("a");
                        downloadLink.href = URL.createObjectURL(blob);
                        downloadLink.download = filename; // Dateiname für den Download

                        // Simulierten Klick auf den Download-Link auslösen
                        document.body.appendChild(downloadLink); // Link zum DOM hinzufügen
                        downloadLink.click(); // Klick simulieren
                        document.body.removeChild(downloadLink); // Link wieder entfernen

                    });
                });
            }



        } else {
            alert("Design-Daten fehlen.");
        }



    });






});





