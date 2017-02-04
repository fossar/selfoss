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

use WideImage\WideImage;
use WideImage\PaletteImage;
use WideImage\TrueColorImage;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class TrueColorImageTest extends WideImage_TestCase
{
	public function testCreate()
	{
		$img = TrueColorImage::create(10, 10);
		$this->assertTrue($img instanceof TrueColorImage);
		$this->assertTrue($img->isValid());
		$this->assertTrue($img->isTrueColor());
	}
	
	public function testCopy()
	{
		$img = WideImage::load(IMG_PATH . '100x100-rgbyg.png');
		$this->assertTrue($img instanceof TrueColorImage);
		$this->assertTrue($img->isValid());
		$this->assertTrue($img->isTrueColor());
		$this->assertRGBEqual($img->getRGBAt(15, 15), 0, 0, 255);
		$this->assertRGBEqual($img->getRGBAt(85, 15), 255, 0, 0);
		$this->assertRGBEqual($img->getRGBAt(85, 85), 255, 255, 0);
		$this->assertRGBEqual($img->getRGBAt(15, 85), 0, 255, 0);
		$this->assertRGBEqual($img->getRGBAt(50, 50), 127, 127, 127);
		
		$copy = $img->copy();
		$this->assertFalse($img->getHandle() === $copy->getHandle());
		
		$this->assertTrue($copy instanceof TrueColorImage);
		$this->assertTrue($copy->isValid());
		$this->assertTrue($copy->isTrueColor());
		$this->assertRGBEqual($copy->getRGBAt(15, 15), 0, 0, 255);
		$this->assertRGBEqual($copy->getRGBAt(85, 15), 255, 0, 0);
		$this->assertRGBEqual($copy->getRGBAt(85, 85), 255, 255, 0);
		$this->assertRGBEqual($copy->getRGBAt(15, 85), 0, 255, 0);
		$this->assertRGBEqual($copy->getRGBAt(50, 50), 127, 127, 127);
	}
	
	public function testCopyNoAlpha()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		$this->assertRGBEqual($img->getRGBAt(85, 85), 0, 0, 255, 96);
		$copy = $img->copyNoAlpha();
		$this->assertFalse($img->getHandle() === $copy->getHandle());
		$this->assertTrue($copy instanceof TrueColorImage);
		$this->assertTrue($copy->isValid());
		$this->assertTrue($copy->isTrueColor());
		$this->assertRGBEqual($copy->getRGBAt(85, 85), 0, 0, 255, 0);
	}
	
	public function testCopyAlphaGetsCopied()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		$this->assertTrue($img instanceof TrueColorImage);
		$this->assertTrue($img->isValid());
		$this->assertTrue($img->isTrueColor());
		$this->assertRGBNear($img->getRGBAt(25, 25), 0, 0, 255, 0.25 * 127);
		$this->assertRGBNear($img->getRGBAt(75, 25), 0, 0, 255, 0.5 * 127);
		$this->assertRGBNear($img->getRGBAt(75, 75), 0, 0, 255, 0.75 * 127);
		$this->assertRGBNear($img->getRGBAt(25, 75), 0, 0, 0, 127);
		
		$copy = $img->copy();
		$this->assertFalse($img->getHandle() === $copy->getHandle());
		
		$this->assertTrue($copy instanceof TrueColorImage);
		$this->assertTrue($copy->isValid());
		$this->assertTrue($copy->isTrueColor());
		$this->assertRGBNear($copy->getRGBAt(25, 25), 0, 0, 255, 0.25 * 127);
		$this->assertRGBNear($copy->getRGBAt(75, 25), 0, 0, 255, 0.5 * 127);
		$this->assertRGBNear($copy->getRGBAt(75, 75), 0, 0, 255, 0.75 * 127);
		$this->assertRGBNear($copy->getRGBAt(25, 75), 0, 0, 0, 127);
	}
	
	public function testAsPalette()
	{
		if (function_exists('imagecolormatch')) {
			$img = WideImage::load(IMG_PATH . '100x100-rgbyg.png');
			$this->assertTrue($img instanceof TrueColorImage);
			$this->assertTrue($img->isValid());
			$this->assertTrue($img->isTrueColor());
			
			$copy = $img->asPalette();
			$this->assertFalse($img->getHandle() === $copy->getHandle());
			
			$this->assertTrue($copy instanceof PaletteImage);
			$this->assertTrue($copy->isValid());
			$this->assertFalse($copy->isTrueColor());
			$this->assertRGBEqual($copy->getRGBAt(15, 15), 0, 0, 255);
			$this->assertRGBEqual($copy->getRGBAt(85, 15), 255, 0, 0);
			$this->assertRGBEqual($copy->getRGBAt(85, 85), 255, 255, 0);
			$this->assertRGBEqual($copy->getRGBAt(15, 85), 0, 255, 0);
			$this->assertRGBEqual($copy->getRGBAt(50, 50), 127, 127, 127);
		}
	}
	
	public function testPreserveTransparency()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.gif');
		$this->assertTrue($img->isTransparent());
		$this->assertRGBEqual($img->getTransparentColorRGB(), 255, 255, 255);
		
		$tc = $img->asTrueColor();
		$this->assertTrue($tc->isTransparent());
		$this->assertRGBEqual($tc->getTransparentColorRGB(), 255, 255, 255);
		
		$img = $tc->asPalette();
		$this->assertTrue($img->isTransparent());
		$this->assertRGBEqual($img->getTransparentColorRGB(), 255, 255, 255);
	}
}
