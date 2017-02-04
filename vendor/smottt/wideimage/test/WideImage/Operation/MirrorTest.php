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
use WideImage\PaletteImage;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class MirrorTest extends WideImage_TestCase
{
	public function testMirror()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.gif');
		
		$this->assertRGBEqual($img->getRGBAt(5, 5), 255, 255, 0);
		$this->assertRGBEqual($img->getRGBAt(95, 5), 0, 0, 255);
		$this->assertRGBEqual($img->getRGBAt(95, 95), 0, 255, 0);
		$this->assertRGBEqual($img->getRGBAt(5, 95), 255, 0, 0);
		
		$new = $img->mirror();
		
		$this->assertTrue($new instanceof PaletteImage);
		
		$this->assertEquals(100, $new->getWidth());
		$this->assertEquals(100, $new->getHeight());
		
		$this->assertRGBEqual($new->getRGBAt(95, 5), 255, 255, 0);
		$this->assertRGBEqual($new->getRGBAt(5, 5), 0, 0, 255);
		$this->assertRGBEqual($new->getRGBAt(5, 95), 0, 255, 0);
		$this->assertRGBEqual($new->getRGBAt(95, 95), 255, 0, 0);
		
		$this->assertTrue($new->isTransparent());
		$this->assertRGBEqual($new->getRGBAt(50, 50), $img->getTransparentColorRGB());
	}
}
