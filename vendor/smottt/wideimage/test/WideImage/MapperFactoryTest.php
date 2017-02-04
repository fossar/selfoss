<?php
	/**
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
    
    * @package Tests
  **/

namespace Test\WideImage;

use WideImage\MapperFactory;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class MapperFactoryTest extends WideImage_TestCase
{
	public function testMapperPNGByURI()
	{
		$mapper = MapperFactory::selectMapper('uri.png');
		$this->assertInstanceOf("WideImage\\Mapper\\PNG", $mapper);
	}
	
	public function testMapperGIFByURI()
	{
		$mapper = MapperFactory::selectMapper('uri.gif');
		$this->assertInstanceOf("WideImage\\Mapper\\GIF", $mapper);
	}
	
	public function testMapperJPGByURI()
	{
		$mapper = MapperFactory::selectMapper('uri.jpg');
		$this->assertInstanceOf("WideImage\\Mapper\\JPEG", $mapper);
	}
	
	public function testMapperBMPByURI()
	{
		$mapper = MapperFactory::selectMapper('uri.bmp');
		$this->assertInstanceOf("WideImage\\Mapper\\BMP", $mapper);
	}
}
