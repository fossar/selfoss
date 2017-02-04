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

use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class AsNegativeTest extends WideImage_TestCase
{
	public function skip()
	{
		$this->skipUnless(function_exists('imagefilter'));
	}
	
	public function testTransparentGIF()
	{
		$img = $this->load('100x100-color-hole.gif');
		
		$res = $img->asNegative();
		
		$this->assertDimensions($res, 100, 100);
		$this->assertInstanceOf("WideImage\\PaletteImage", $res);
		
		$this->assertRGBNear($res->getRGBAt(10, 10), 0, 0, 255);
		$this->assertRGBNear($res->getRGBAt(90, 10), 255, 255, 0);
		$this->assertRGBNear($res->getRGBAt(90, 90), 255, 0, 255);
		$this->assertRGBNear($res->getRGBAt(10, 90), 0, 255, 255);
		
		// preserves transparency
		$this->assertTrue($res->isTransparent());
		$this->assertTransparentColorAt($res, 50, 50);
	}
	
	public function testTransparentLogoGIF()
	{
		$img = $this->load('logo.gif');
		$this->assertTransparentColorAt($img, 1, 1);
		
		$res = $img->asNegative();
		$this->assertDimensions($res, 150, 23);
		$this->assertInstanceOf("WideImage\\PaletteImage", $res);
		
		// preserves transparency
		$this->assertTrue($res->isTransparent());
		$this->assertTransparentColorAt($res, 1, 1);
	}
	
	public function testPNGAlpha()
	{
		$img = $this->load('100x100-blue-alpha.png');
		
		$res = $img->asNegative();
		
		$this->assertDimensions($res, 100, 100);
		$this->assertInstanceOf("WideImage\\TrueColorImage", $res);
		
		$this->assertRGBNear($res->getRGBAt(25, 25), 255, 255, 0, 32);
		$this->assertRGBNear($res->getRGBAt(75, 25), 255, 255, 0, 64);
		$this->assertRGBNear($res->getRGBAt(75, 75), 255, 255, 0, 96);
		$this->assertRGBNear($res->getRGBAt(25, 75), 255, 255, 255, 127);
		
		$this->assertFalse($res->isTransparent());
	}
}
