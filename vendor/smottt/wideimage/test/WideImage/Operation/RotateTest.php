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
 */
class RotateTest extends WideImage_TestCase
{
	public function skip()
	{
		$this->skipUnless(function_exists('imagerotate'));
	}

	public function testRotateAlphaSafe()
	{
		$img = WideImage::load(IMG_PATH . '100x100-blue-alpha.png');
		$this->assertRGBEqual($img->getRGBAt(25, 25), 0, 0, 255, round(128 / 4));
		$this->assertRGBEqual($img->getRGBAt(75, 25), 0, 0, 255, round(2 * 128 / 4));
		$this->assertRGBEqual($img->getRGBAt(75, 75), 0, 0, 255, round(3 * 128 / 4));
		$this->assertRGBEqual($img->getRGBAt(25, 75), 0, 0, 0, 127);
		$new = $img->rotate(90, null);
		$this->assertEquals(100, $new->getWidth());
		$this->assertEquals(100, $new->getHeight());
	}

	public function testRotateCounterClockwise90()
	{
		$img = WideImage::load(IMG_PATH . 'fgnl.jpg');
		$new = $img->rotate(-90);
		$this->assertEquals(287, $new->getWidth());
		$this->assertEquals(174, $new->getHeight());
	}

	public function testRotate45()
	{
		$img = WideImage::load(IMG_PATH . '100x100-rainbow.png');
		$new = $img->rotate(45);
		$this->assertEquals(141, $new->getWidth());
		$this->assertEquals(141, $new->getHeight());
	}
}
