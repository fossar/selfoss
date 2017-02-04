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

    * @package Internal/Operations
  **/

namespace WideImage\Operation;

use WideImage\Coordinate;
use WideImage\PaletteImage;
use WideImage\TrueColorImage;
use WideImage\Exception\GDFunctionResultException;
use WideImage\Operation\Exception\InvalidFitMethodException;
use WideImage\Operation\Exception\InvalidResizeDimensionException;

/**
 * Resize operation class
 * 
 * @package Internal/Operations
 */
class Resize
{
	/**
	 * Prepares and corrects smart coordinates
	 *
	 * @param \WideImage\Image $img
	 * @param smart_coordinate $width
	 * @param smart_coordinate $height
	 * @param string $fit
	 * @return array
	 */
	protected function prepareDimensions($img, $width, $height, $fit)
	{
		if ($width === null && $height === null) {
			return array('width' => $img->getWidth(), 'height' => $img->getHeight());
		}
		
		if ($width !== null) {
			$width = Coordinate::fix($width, $img->getWidth());
			$rx    = $img->getWidth() / $width;
		} else {
			$rx = null;
		}
		
		if ($height !== null) {
			$height = Coordinate::fix($height, $img->getHeight());
			$ry     = $img->getHeight() / $height;
		} else {
			$ry = null;
		}
		
		if ($rx === null && $ry !== null) {
			$rx    = $ry;
			$width = round($img->getWidth() / $rx);
		}
		
		if ($ry === null && $rx !== null) {
			$ry     = $rx;
			$height = round($img->getHeight() / $ry);
		}
		
		if ($width === 0 || $height === 0) {
			return array('width' => 0, 'height' => 0);
		}
		
		if ($fit == null) {
			$fit = 'inside';
		}
		
		$dim = array();
		
		if ($fit == 'fill') {
			$dim['width']  = $width;
			$dim['height'] = $height;
		} elseif ($fit == 'inside' || $fit == 'outside') {
			if ($fit == 'inside') {
				$ratio = ($rx > $ry) ? $rx : $ry;
			} else {
				$ratio = ($rx < $ry) ? $rx : $ry;
			}
			
			$dim['width']  = round($img->getWidth() / $ratio);
			$dim['height'] = round($img->getHeight() / $ratio);
		} else {
			throw new InvalidFitMethodException("{$fit} is not a valid resize-fit method.");
		}
		
		return $dim;
	}
	
	/**
	 * Returns a resized image
	 *
	 * @param \WideImage\Image $img
	 * @param smart_coordinate $width
	 * @param smart_coordinate $height
	 * @param string $fit
	 * @param string $scale
	 * @return \WideImage\Image
	 */
	public function execute($img, $width, $height, $fit, $scale)
	{
		$dim = $this->prepareDimensions($img, $width, $height, $fit);
		
		if (($scale === 'down' && ($dim['width'] >= $img->getWidth() && $dim['height'] >= $img->getHeight())) ||
			($scale === 'up' && ($dim['width'] <= $img->getWidth() && $dim['height'] <= $img->getHeight()))) {
			$dim = array('width' => $img->getWidth(), 'height' => $img->getHeight());
		}
		
		if ($dim['width'] <= 0 || $dim['height'] <= 0) {
			throw new InvalidResizeDimensionException("Both dimensions must be larger than 0.");
		}
		
		if ($img->isTransparent() || $img instanceof PaletteImage) {
			$new = PaletteImage::create($dim['width'], $dim['height']);
			$new->copyTransparencyFrom($img);
			
			if (!imagecopyresized(
					$new->getHandle(), 
					$img->getHandle(), 
					0, 0, 0, 0, 
					$new->getWidth(), 
					$new->getHeight(), 
					$img->getWidth(), 
					$img->getHeight())) {
						throw new GDFunctionResultException("imagecopyresized() returned false");
			}
		} else {
			$new = TrueColorImage::create($dim['width'], $dim['height']);
			$new->alphaBlending(false);
			$new->saveAlpha(true);
			
			if (!imagecopyresampled(
					$new->getHandle(), 
					$img->getHandle(), 
					0, 0, 0, 0, 
					$new->getWidth(), 
					$new->getHeight(), 
					$img->getWidth(), 
					$img->getHeight())) {
						throw new GDFunctionResultException("imagecopyresampled() returned false");
			}
			
			$new->alphaBlending(true);
		}
		
		return $new;
	}
}
