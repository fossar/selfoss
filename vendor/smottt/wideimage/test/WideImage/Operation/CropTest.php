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
use WideImage\TrueColorImage;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class CropTest extends WideImage_TestCase
{
	public function testCropTransparentGif()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.gif');
		
		$cropped = $img->crop('10%', 15, 50, '40%');
		
		$this->assertTrue($cropped instanceof PaletteImage);
		$this->assertTrue($cropped->isTransparent());
		$this->assertEquals(50, $cropped->getWidth());
		$this->assertEquals(40, $cropped->getHeight());
		
		$this->assertRGBNear($cropped->getRGBAt(39, 9), 255, 255, 0);
		$this->assertRGBNear($cropped->getRGBAt(40, 9), 0, 0, 255);
		$this->assertRGBNear($cropped->getRGBAt(14, 35), 255, 0, 0);
		$this->assertRGBNear($cropped->getRGBAt(16, 11), $cropped->getTransparentColorRGB());
	}
	
	public function testCropPNGAlpha()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		
		$cropped = $img->crop(10, 10, 50, 50);
		
		$this->assertTrue($cropped instanceof TrueColorImage);
		$this->assertFalse($cropped->isTransparent());
		$this->assertEquals(50, $cropped->getWidth());
		$this->assertEquals(50, $cropped->getHeight());
		
		$this->assertRGBNear($cropped->getRGBAt(39, 39), 0, 0, 255, 32);
		$this->assertRGBNear($cropped->getRGBAt(40, 40), 0, 0, 255, 96);
	}
	
	public function testCropHasCorrectSize()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		
		$cropped = $img->crop(10, 10, 10, 10);
		$this->assertEquals(10, $cropped->getWidth());
		$this->assertEquals(10, $cropped->getHeight());
		
		$cropped = $img->crop(10, 20, 100, 200);
		$this->assertEquals(90, $cropped->getWidth());
		$this->assertEquals(80, $cropped->getHeight());
	}
	
	public function testCropIsNormalized()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		
		$cropped = $img->crop(-10, -20, 100, 100);
		$this->assertEquals(90, $cropped->getWidth());
		$this->assertEquals(80, $cropped->getHeight());
		
		$cropped = $img->crop(10, 20, 140, 170);
		$this->assertEquals(90, $cropped->getWidth());
		$this->assertEquals(80, $cropped->getHeight());
		
		$cropped = $img->crop(-10, -20, 140, 170);
		$this->assertEquals(100, $cropped->getWidth());
		$this->assertEquals(100, $cropped->getHeight());
	}
	
	/**
	 * @expectedException WideImage\Exception\Exception
	 */
	public function testCropCutsAreaOutsideBoundaries()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		$cropped = $img->crop(120, 100, 1, 2);
	}
	
	/**
	 * @expectedException WideImage\Exception\Exception
	 */
	public function testCropCutsAreaNegativePosition()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		$cropped = $img->crop(-150, -200, 50, 50);
	}
}
