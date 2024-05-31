/**
 * Get dark OR bright color depending the color contrast.
 *
 * @param string hexColor color (hex) value
 * @param string darkColor dark color value
 * @param string brightColor bright color value
 *
 * @return string dark OR bright color value
 *
 * @see https://24ways.org/2010/calculating-color-contrast/
 */
export function colorByBrightness(
    hexColor,
    darkColor = '#555',
    brightColor = '#EEE',
) {
    // Strip hash sign.
    const color = hexColor.substr(1);
    const r = parseInt(color.substr(0, 2), 16);
    const g = parseInt(color.substr(2, 2), 16);
    const b = parseInt(color.substr(4, 2), 16);
    const yiq = (r * 299 + g * 587 + b * 114) / 1000;
    return yiq >= 128 ? darkColor : brightColor;
}
