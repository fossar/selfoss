/**
 * Get dark OR bright color depending the color contrast.
 *
 * @param hexColor color (hex) value
 * @param darkColor dark color value
 * @param brightColor bright color value
 *
 * @return dark OR bright color value
 *
 * @see https://24ways.org/2010/calculating-color-contrast/
 */
export function colorByBrightness(
    hexColor: string,
    darkColor: string = '#555',
    brightColor: string = '#EEE',
): string {
    // Strip hash sign.
    const color = hexColor.substr(1);
    const r = parseInt(color.substr(0, 2), 16);
    const g = parseInt(color.substr(2, 2), 16);
    const b = parseInt(color.substr(4, 2), 16);
    const yiq = (r * 299 + g * 587 + b * 114) / 1000;
    return yiq >= 128 ? darkColor : brightColor;
}
