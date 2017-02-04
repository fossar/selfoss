<?php
	/**
##DOC-SIGNATURE##

    This file is part of WideImage.
		
    WideImage is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 2.1 of the License, or
    (at your option) any later version.
		
    WideImage is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.
		
    You should have received a copy of the GNU Lesser General Public License
    along with WideImage; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  **/

namespace WideImage;

use WideImage\Exception\InvalidImageDimensionException;

/**
 * A class for truecolor image objects
 * 
 * @package WideImage
 */
class TrueColorImage extends Image
{
	/**
	 * Creates the object
	 *
	 * @param resource $handle
	 */
	public function __construct($handle)
	{
		parent::__construct($handle);
		
		$this->alphaBlending(false);
		$this->saveAlpha(true);
	}
	
	/**
	 * Factory method that creates a true-color image object
	 *
	 * @param int $width
	 * @param int $height
	 * @return \WideImage\TrueColorImage
	 */
	public static function create($width, $height)
	{
		if ($width * $height <= 0 || $width < 0) {
			throw new InvalidImageDimensionException("Can't create an image with dimensions [$width, $height].");
		}
		
		return new TrueColorImage(imagecreatetruecolor($width, $height));
	}
	
	public function doCreate($width, $height)
	{
		return static::create($width, $height);
	}
	
	public function isTrueColor()
	{
		return true;
	}
	
	/**
	 * Sets alpha blending mode via imagealphablending()
	 *
	 * @param bool $mode
	 * @return bool
	 */
	public function alphaBlending($mode)
	{
		return imagealphablending($this->handle, $mode);
	}
	
	/**
	 * Toggle if alpha channel should be saved with the image via imagesavealpha()
	 *
	 * @param bool $on
	 * @return bool
	 */
	public function saveAlpha($on)
	{
		return imagesavealpha($this->handle, $on);
	}
	
	/**
	 * Allocates a color and returns its index
	 * 
	 * This method accepts either each component as an integer value,
	 * or an associative array that holds the color's components in keys
	 * 'red', 'green', 'blue', 'alpha'.
	 *
	 * @param mixed $R
	 * @param int $G
	 * @param int $B
	 * @param int $A
	 * @return int
	 */
	public function allocateColorAlpha($R, $G = null, $B = null, $A = null)
	{
		if (is_array($R)) {
			return imageColorAllocateAlpha($this->handle, $R['red'], $R['green'], $R['blue'], $R['alpha']);
		}
		
		return imageColorAllocateAlpha($this->handle, $R, $G, $B, $A);
	}
	
	/**
	 * @see \WideImage\Image#asPalette($nColors, $dither, $matchPalette)
	 */
	public function asPalette($nColors = 255, $dither = null, $matchPalette = true)
	{
		$nColors = intval($nColors);
		
		if ($nColors < 1) {
			$nColors = 1;
		} elseif ($nColors > 255) {
			$nColors = 255;
		}
		
		if ($dither === null) {
			$dither = $this->isTransparent();
		}
		
		$temp = $this->copy();
		
		imagetruecolortopalette($temp->handle, $dither, $nColors);
		
		if ($matchPalette == true && function_exists('imagecolormatch')) {
			imagecolormatch($this->handle, $temp->handle);
		}
		
		// The code below isn't working properly; it corrupts transparency on some palette->tc->palette conversions.
		// Why is this code here?
		/*
		if ($this->isTransparent())
		{
			$trgb = $this->getTransparentColorRGB();
			$tci = $temp->getClosestColor($trgb);
			$temp->setTransparentColor($tci);
		}
		/**/
		
		$temp->releaseHandle();
		
		return new PaletteImage($temp->handle);
	}
	
	/**
	 * Returns the index of the color that best match the given color components
	 *
	 * This method accepts either each component as an integer value,
	 * or an associative array that holds the color's components in keys
	 * 'red', 'green', 'blue', 'alpha'.
	 *
	 * @param mixed $R Red component value or an associative array
	 * @param int $G Green component
	 * @param int $B Blue component
	 * @param int $A Alpha component
	 * @return int The color index
	 */
	public function getClosestColorAlpha($R, $G = null, $B = null, $A = null)
	{
		if (is_array($R)) {
			return imagecolorclosestalpha($this->handle, $R['red'], $R['green'], $R['blue'], $R['alpha']);
		}
		
		return imagecolorclosestalpha($this->handle, $R, $G, $B, $A);
	}
	
	/**
	 * Returns the index of the color that exactly match the given color components
	 *
	 * This method accepts either each component as an integer value,
	 * or an associative array that holds the color's components in keys
	 * 'red', 'green', 'blue', 'alpha'.
	 *
	 * @param mixed $R Red component value or an associative array
	 * @param int $G Green component
	 * @param int $B Blue component
	 * @param int $A Alpha component
	 * @return int The color index
	 */
	public function getExactColorAlpha($R, $G = null, $B = null, $A = null)
	{
		if (is_array($R)) {
			return imagecolorexactalpha($this->handle, $R['red'], $R['green'], $R['blue'], $R['alpha']);
		}
		
		return imagecolorexactalpha($this->handle, $R, $G, $B, $A);
	}
	
	/**
	 * @see \WideImage\Image#getChannels()
	 */
	public function getChannels()
	{
		$args = func_get_args();
		
		if (count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		
		return OperationFactory::get('CopyChannelsTrueColor')->execute($this, $args);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \WideImage\Image#copyNoAlpha()
	 */
	public function copyNoAlpha()
	{
		$prev   = $this->saveAlpha(false);
		$result = WideImage::loadFromString($this->asString('png'));
		$this->saveAlpha($prev);
		//$result->releaseHandle();
		return $result;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \WideImage\Image#asTrueColor()
         * @return TrueColorImage
	 */
	public function asTrueColor()
	{
		return $this->copy();
	}
        
	/**
	 * Calls imageinterlace() using the current handler
	 * @see \WideImage\Image#asTrueColor()
         * @return TrueColorImage A copy of the image, with imageinterlace() applied
	 */
        public function asProgressive()
        {
            $dest = $this->asTrueColor();

            imageinterlace($dest->getHandle(), true);

            return $dest;
        }

        /**
         * Resizes the image proportionally inside the given width and height.
         * The returned image will have always the specified width and height, and any space will be filled
         * with the given $fillCollor
         * 
         * @param int $width Exact width in pixels
         * @param int $height Exact height in pixels
         * @param string $fit 'inside' (default), 'outside' or 'fill'
         * @param string $scale 'down' (default), 'up' or 'any'
         * @param mixed $alignLeft Left position of the image over the fill space, smart coordinate
         * @param mixed $alignTop Top position of the image over the fill space, smart coordinate
         * @param int $mergeOpacity The opacity of the image over the fill space
         * @param int|array $fillColor RGB color index or array. Background color to fill the resulting image with if it's smaller
         * than the given size. By default (if null), the top left pixel color will be used.
         * 
         * @return TrueColorImage A new, resized image
         */
        public function resizeInsideRect($width, $height, $fit = 'inside', $scale = 'down', $alignLeft = 'center', 
                $alignTop = 'center', $mergeOpacity = 100,  $fillColor = null)
        {

            if ($fillColor) {
                if (is_numeric($fillColor)) {
                    $fillColor = $this->getColorRGB($fillColor);
                }
            } else {
                $fillColor = $this->getColorRGB($this->getColorAt(0, 0));
            }
            
            $rect = \WideImage::createTrueColorImage($width, $height);
            $rect->fill(0, 0, $rect->allocateColor($fillColor));

            $img = $this;

            for ($i = 0; $i < 4; $i++) { //4 times
                $img = $img->resize($width, $height, $fit, $scale);
            }

            return $rect->merge($img, $alignLeft, $alignTop, $mergeOpacity);
        }
}

