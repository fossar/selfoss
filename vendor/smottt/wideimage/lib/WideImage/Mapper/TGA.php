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

    * @package Internal/Mappers
  **/

namespace WideImage\Mapper;

use WideImage\vendor\de77;
use WideImage\Exception\Exception;

/**
 * Mapper support for TGA
 * 
 * @package Internal/Mappers
 */
class TGA
{
	public function load($uri)
	{
		return de77\TGA::imagecreatefromtga($uri);
	}
	
	public function loadFromString($data)
	{
		return de77\TGA::imagecreatefromstring($data);
	}
	
	public function save($handle, $uri = null)
	{
		throw new Exception("Saving to TGA isn't supported.");
	}
}
