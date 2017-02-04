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
class ApplyMaskTest extends WideImage_TestCase
{
	public function testApplyMask()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.gif');
		$mask = WideImage::load(IMG_PATH . '75x25-gray.png');
		
		$result = $img->applyMask($mask);
		$this->assertTrue($result instanceof TrueColorImage);
		$this->assertTrue($result->isTransparent());
		
		$this->assertEquals(100, $result->getWidth());
		$this->assertEquals(100, $result->getHeight());
		
		$this->assertRGBNear($result->getRGBAt(10, 10), 255, 255, 255);
		$this->assertRGBNear($result->getRGBAt(30, 10), 255, 255, 0, 64);
		$this->assertRGBNear($result->getRGBAt(60, 10), 0, 0, 255, 0);
	}
}
