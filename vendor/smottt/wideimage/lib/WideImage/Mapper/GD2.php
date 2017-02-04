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

/**
 * Mapper class for GD2 files
 * 
 * @package Internal/Mappers
 */
class GD2
{
	public function load($uri)
	{
		return @imagecreatefromgd2($uri);
	}
	
	public function save($handle, $uri = null, $chunk_size = null, $type = null)
	{
		return imagegd2($handle, $uri, $chunk_size, $type);
	}
}
