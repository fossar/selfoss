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

namespace Test\WideImage\Operation;

use WideImage\WideImage;
use WideImage\TrueColorImage;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class GetMaskTest extends WideImage_TestCase
{
	public function testGetMaskTransparentGif()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.gif');
		
		$mask = $img->getMask();
		$this->assertTrue($mask instanceof TrueColorImage);
		
		$this->assertFalse($mask->isTransparent());
		$this->assertEquals(100, $mask->getWidth());
		$this->assertEquals(100, $mask->getHeight());
		
		$this->assertRGBNear($mask->getRGBAt(10, 10), 255, 255, 255);
		$this->assertRGBNear($mask->getRGBAt(90, 90), 255, 255, 255);
		$this->assertRGBNear($mask->getRGBAt(50, 50), 0, 0, 0);
	}
	
	public function testGetMaskPNGAlpha()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		
		$mask = $img->getMask();
		$this->assertTrue($mask instanceof TrueColorImage);
		
		$this->assertFalse($mask->isTransparent());
		$this->assertEquals(100, $mask->getWidth());
		$this->assertEquals(100, $mask->getHeight());
		
		$this->assertRGBNear($mask->getRGBAt(25, 25), 192, 192, 192);
		$this->assertRGBNear($mask->getRGBAt(75, 25), 128, 128, 128);
		$this->assertRGBNear($mask->getRGBAt(75, 75), 64, 64, 64);
		$this->assertRGBNear($mask->getRGBAt(25, 75), 0, 0, 0);
	}
}
