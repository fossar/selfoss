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

use WideImage\Exception\GDFunctionResultException;

/**
 * AsGrayscale operation class
 * 
 * @package Internal/Operations
 */
class AsGrayscale
{
	/**
	 * Returns a greyscale copy of an image
	 *
	 * @param \WideImage\Image $image
	 * @return \WideImage\Image
	 */
	public function execute($image)
	{
		$new = $image->asTrueColor();
		
		if (!imagefilter($new->getHandle(), IMG_FILTER_GRAYSCALE)) {
			throw new GDFunctionResultException("imagefilter() returned false");
		}
		
		if (!$image->isTrueColor()) {
			$new = $new->asPalette();
		}
		
		return $new;
	}
}
