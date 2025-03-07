function parseAnchorOption(anchor) {
    var obj = {};
    obj.horizontal = anchor.match(/left|center|right/gi) || [];
    obj.horizontal = obj.horizontal.length === 0 ? "left" : obj.horizontal[0];
    obj.vertical = anchor.match(/baseline|top|bottom|middle/gi) || [];
    obj.vertical = obj.vertical.length === 0 ? "baseline" : obj.vertical[0];
    return obj;
}
class TextToSVG {
    constructor(font) {
        this.font = font;
    }
    static load(url, cb) {
        opentype.load(url, function (err, font) {
            if (err !== null) {
                return cb(err, null);
            }
            return cb(null, new TextToSVG(font));
        });
    }
    getWidth(text, options) {
        var fontSize = options.fontSize || 72;
        var kerning = "kerning" in options ? options.kerning : true;
        var fontScale = (1 / this.font.unitsPerEm) * fontSize;
        var width = 0;
        var glyphs = this.font.stringToGlyphs(text);
        for (var i = 0; i < glyphs.length; i++) {
            var glyph = glyphs[i];
            if (glyph.advanceWidth) {
                width += glyph.advanceWidth * fontScale;
            }
            if (kerning && i < glyphs.length - 1) {
                var kerningValue = this.font.getKerningValue(glyph, glyphs[i + 1]);
                width += kerningValue * fontScale;
            }
            if (options.letterSpacing) {
                width += options.letterSpacing * fontSize;
            } else if (options.tracking) {
                width += (options.tracking / 1000) * fontSize;
            }
        }
        return width;
    }
    getHeight(fontSize) {
        var fontScale = (1 / this.font.unitsPerEm) * fontSize;
        return (this.font.ascender - this.font.descender) * fontScale;
    }
    getMetrics(text, options) {
        var fontSize = options.fontSize || 72;
        var anchor = parseAnchorOption(options.anchor || "");
        var width = this.getWidth(text, options);
        var height = this.getHeight(fontSize);
        var fontScale = (1 / this.font.unitsPerEm) * fontSize;
        var ascender = this.font.ascender * fontScale;
        var descender = this.font.descender * fontScale;
        var x = options.x || 0;
        switch (anchor.horizontal) {
            case "left":
                x -= 0;
                break;
            case "center":
                x -= width / 2;
                break;
            case "right":
                x -= width;
                break;
            default:
                throw new Error(`Unknown anchor option:${anchor.horizontal}`);
        }
        var y = options.y || 0;
        switch (anchor.vertical) {
            case "baseline":
                y -= ascender;
                break;
            case "top":
                y -= 0;
                break;
            case "middle":
                y -= height / 2;
                break;
            case "bottom":
                y -= height;
                break;
            default:
                throw new Error(`Unknown anchor option:${anchor.vertical}`);
        }
        var baseline = y + ascender;
        return { x, y, baseline, width, height, ascender, descender };
    }
    getD(text, options) {
        var fontSize = options.fontSize || 72;
        var kerning = "kerning" in options ? options.kerning : true;
        var letterSpacing = "letterSpacing" in options ? options.letterSpacing : false;
        var tracking = "tracking" in options ? options.tracking : false;
        var metrics = this.getMetrics(text, options);
        var path = this.font.getPath(text, metrics.x, metrics.baseline, fontSize, { kerning, letterSpacing, tracking });
        return path.toPathData();
    }
    getPath(text, options) {
        var attributesArr = Object.keys(options.attributes || {});
        var length = attributesArr.length;
        var attributes = "";
        for (var i = 0; i < length; i += 1) {
            var key = attributesArr[i];
            var value = options.attributes[key];
            attributes += key + '="' + value + '"' + " ";
        }
        var d = this.getD(text, options);
        if (attributes) {
            return `<path ${attributes} d="${d}" />`;
        }
        return `<path d="${d}" />`;
    }
    getSVG(text, options) {
        var metrics = this.getMetrics(text, options);
        var svg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${metrics.width}" height="${metrics.height}" >`;
        svg += this.getPath(text, options);
        svg += "</svg>";
        return svg;
    }
    getDebugSVG(text, options) {
        options = JSON.parse(JSON.stringify(options));
        options.x = options.x || 0;
        options.y = options.y || 0;
        var metrics = this.getMetrics(text, options);
        var box = { width: Math.max(metrics.x + metrics.width, 0) - Math.min(metrics.x, 0), height: Math.max(metrics.y + metrics.height, 0) - Math.min(metrics.y, 0) };
        var origin = { x: box.width - Math.max(metrics.x + metrics.width, 0), y: box.height - Math.max(metrics.y + metrics.height, 0) };
        options.x += origin.x;
        options.y += origin.y;
        var svg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${box.width}" height="${box.height}" >`;
        svg += `<path fill="none" stroke="red" stroke-width="1" d="M0,${origin.y}L${box.width},${origin.y}" />`;
        svg += `<path fill="none" stroke="red" stroke-width="1" d="M${origin.x},0L${origin.x},${box.height}" />`;
        svg += this.getPath(text, options);
        svg += "</svg>";
        return svg;
    }
}