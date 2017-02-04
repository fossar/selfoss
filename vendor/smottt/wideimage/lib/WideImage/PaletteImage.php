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
    
    * @package WideImage
  **/

namespace WideImage;

use WideImage\Exception\InvalidImageDimensionException;
use WideImage\Exception\GDFunctionResultException;

/**
 * @package WideImage
 */
class PaletteImage extends Image
{
	/**
	 * Create a palette image
	 *
	 * @param int $width
	 * @param int $height
	 * @return \WideImage\PaletteImage
	 */
	public static function create($width, $height)
	{
		if ($width * $height <= 0 || $width < 0) {
			throw new InvalidImageDimensionException("Can't create an image with dimensions [$width, $height].");
		}
		
		return new PaletteImage(imagecreate($width, $height));
	}
	
	public function doCreate($width, $height)
	{
		return static::create($width, $height);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \WideImage\Image#isTrueColor()
	 */
	public function isTrueColor()
	{
		return false;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \WideImage\Image#asPalette($nColors, $dither, $matchPalette)
	 */
	public function asPalette($nColors = 255, $dither = null, $matchPalette = true)
	{
		return $this->copy();
	}
	
	/**
	 * Returns a copy of the image
	 * 
	 * @param $trueColor True if the new image should be truecolor
	 * @return \WideImage\Image
	 */
	protected function copyAsNew($trueColor = false)
	{
		$width = $this->getWidth();
		$height = $this->getHeight();
		
		if ($trueColor) {
			$new = TrueColorImage::create($width, $height);
		} else {
			$new = PaletteImage::create($width, $height);
		}
		
		// copy transparency of source to target
		if ($this->isTransparent()) {
			$rgb = $this->getTransparentColorRGB();
			
			if (is_array($rgb)) {
				$tci = $new->allocateColor($rgb['red'], $rgb['green'], $rgb['blue']);
				$new->fill(0, 0, $tci);
				$new->setTransparentColor($tci);
			}
		}
		
		imageCopy($new->getHandle(), $this->handle, 0, 0, 0, 0, $width, $height);
		
		return $new;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \WideImage\Image#asTrueColor()
	 */
	public function asTrueColor()
	{
		$width  = $this->getWidth();
		$height = $this->getHeight();
		$new    = WideImage::createTrueColorImage($width, $height);
		
		if ($this->isTransparent()) {
			$new->copyTransparencyFrom($this);
		}
		
		if (!imageCopy($new->getHandle(), $this->handle, 0, 0, 0, 0, $width, $height)) {
			throw new GDFunctionResultException("imagecopy() returned false");
		}
		
		return $new;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \WideImage\Image#getChannels()
	 */
	public function getChannels()
	{
		$args = func_get_args();
		
		if (count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		
		return OperationFactory::get('CopyChannelsPalette')->execute($this, $args);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \WideImage\Image#copyNoAlpha()
	 */
	public function copyNoAlpha()
	{
		return WideImage::loadFromString($this->asString('png'));
	}
}
