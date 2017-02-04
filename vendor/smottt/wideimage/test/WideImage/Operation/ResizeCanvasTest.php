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
use Test\WideImage_TestCase;

// WideImage_OperationFactory::get('ResizeCanvas');

/**
 * @package Tests
 */
class ResizeCanvasTest extends WideImage_TestCase
{
	public function testResizeCanvasUp()
	{
		$img = WideImage::createTrueColorImage(160, 120);
		$resized = $img->resizeCanvas(180, 180, 0, 0);
		$this->assertDimensions($resized, 180, 180);
	}
	
	public function testResizeCanvasDown()
	{
		$img = WideImage::createTrueColorImage(160, 120);
		$resized = $img->resizeCanvas(30, 100, 0, 0);
		$this->assertDimensions($resized, 30, 100);
	}
	
	public function testResizeCanvasPositionsCenter()
	{
		$img = WideImage::createTrueColorImage(20, 20);
		$black = $img->allocateColor(0, 0, 0);
		$white = $img->allocateColor(255, 255, 255);
		$img->fill(0, 0, $black);
		
		$res = $img->resizeCanvas(40, 40, 'center', 'center', $white);
		$this->assertRGBAt($res, 5, 5, $white);
		$this->assertRGBAt($res, 35, 35, $white);
		$this->assertRGBAt($res, 5, 35, $white);
		$this->assertRGBAt($res, 35, 5, $white);
		$this->assertRGBAt($res, 20, 20, $black);
	}
	
	public function testResizeCanvasPositionsCorner()
	{
		$img = WideImage::createTrueColorImage(20, 20);
		$black = $img->allocateColor(0, 0, 0);
		$white = $img->allocateColor(255, 255, 255);
		$img->fill(0, 0, $black);
		
		$res = $img->resizeCanvas(40, 40, 'bottom', 'right', $white);
		$this->assertRGBAt($res, 5, 5, $white);
		$this->assertRGBAt($res, 35, 35, $black);
		$this->assertRGBAt($res, 5, 35, $white);
		$this->assertRGBAt($res, 35, 5, $white);
	}
}
