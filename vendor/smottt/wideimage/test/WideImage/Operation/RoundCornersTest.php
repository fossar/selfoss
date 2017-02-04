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

/**
 * @package Tests
 * @group operation
 */
class RoundCornersTest extends WideImage_TestCase
{
	public function testWhiteCorner()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.png');
		$res = $img->roundCorners(30, $img->allocateColor(255, 255, 255), WideImage::SIDE_ALL);
		
		$this->assertEquals(100, $res->getWidth());
		$this->assertEquals(100, $res->getHeight());
		
		$this->assertRGBAt($res, 5, 5, array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0));
		$this->assertRGBAt($res, 95, 5, array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0));
		$this->assertRGBAt($res, 95, 95, array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0));
		$this->assertRGBAt($res, 5, 95, array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0));
	}
	
	public function testTransparentCorner()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		$res = $img->roundCorners(30, null, WideImage::SIDE_ALL);
		
		$this->assertEquals(100, $res->getWidth());
		$this->assertEquals(100, $res->getHeight());
		
		$this->assertRGBAt($res, 5, 5, array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127));
		$this->assertRGBAt($res, 95, 5, array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127));
		$this->assertRGBAt($res, 95, 95, array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127));
		$this->assertRGBAt($res, 5, 95, array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127));
	}
}
