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

include_once __DIR__ . '/Operation/CustomOp.php';

use WideImage\WideImage;
use WideImage\Canvas;
use WideImage\Image;
use WideImage\TrueColorImage;
use WideImage\PaletteImage;
use WideImage\Operation\CustomOp;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class ImageForOutput extends TrueColorImage
{
	public $headers = array();

	public function writeHeader($name, $data)
	{
		$this->headers[$name] = $data;
	}
}

/**
 * @package Tests
 */
class TestableImage extends TrueColorImage
{
	public $headers = array();

	public function __destruct() {}

	public function writeHeader($name, $data)
	{
		$this->headers[$name] = $data;
	}
}

/**
 * @package Tests
 */
class ImageTest extends WideImage_TestCase
{
	public function testFactories()
	{
		$this->assertTrue(WideImage::createTrueColorImage(100, 100) instanceof TrueColorImage);
		$this->assertTrue(WideImage::createPaletteImage(100, 100) instanceof PaletteImage);
	}

	public function testDestructorUponUnset()
	{
		$img = TrueColorImage::create(10, 10);
		$handle = $img->getHandle();

		$this->assertTrue(WideImage::isValidImageHandle($handle));

		unset($img);

		$this->assertFalse(WideImage::isValidImageHandle($handle));
	}

	public function testDestructorUponNull()
	{
		$img = TrueColorImage::create(10, 10);
		$handle = $img->getHandle();

		$this->assertTrue(WideImage::isValidImageHandle($handle));

		$img = null;

		$this->assertFalse(WideImage::isValidImageHandle($handle));
	}

	public function testAutoDestruct()
	{
		$img = TrueColorImage::create(10, 10);
		$handle = $img->getHandle();

		unset($img);

		$this->assertFalse(WideImage::isValidImageHandle($handle));
	}

	public function testAutoDestructWithRelease()
	{
		$img = TrueColorImage::create(10, 10);
		$handle = $img->getHandle();

		$img->releaseHandle();
		unset($img);

		$this->assertTrue(WideImage::isValidImageHandle($handle));
		imagedestroy($handle);
	}

	public function testCustomOpMagic()
	{
		$img = TrueColorImage::create(10, 10);
		$result = $img->customOp(123, 'abc');
		$this->assertTrue($result instanceof Image);
		$this->assertSame(CustomOp::$args[0], $img);
		$this->assertSame(CustomOp::$args[1], 123);
		$this->assertSame(CustomOp::$args[2], 'abc');
	}

	public function testCustomOpCaseInsensitive()
	{
		$img = TrueColorImage::create(10, 10);
		$result = $img->CUSTomOP(123, 'abc');
		$this->assertTrue($result instanceof Image);
		$this->assertSame(CustomOp::$args[0], $img);
		$this->assertSame(CustomOp::$args[1], 123);
		$this->assertSame(CustomOp::$args[2], 'abc');
	}

	public function testInternalOpCaseInsensitive()
	{
		$img = TrueColorImage::create(10, 10);
		$result = $img->AUTOcrop();
		$this->assertTrue($result instanceof Image);
	}

	public function testOutput()
	{
		$tmp = WideImage::load(IMG_PATH . 'fgnl.jpg');
		$img = new ImageForOutput($tmp->getHandle());

		ob_start();
		$img->output('png');
		$data = ob_get_clean();

		$this->assertEquals(array('Content-length' => strlen($data), 'Content-type' => 'image/png'), $img->headers);
	}

	/**
	 * @group bug
	 */
	public function testOutputJPG()
	{
		$tmp = WideImage::load(IMG_PATH . 'fgnl.jpg');
		$img = new ImageForOutput($tmp->getHandle());
		ob_start();
		$img->output('jpg');
		$data = ob_get_clean();
		$this->assertEquals(array('Content-length' => strlen($data), 'Content-type' => 'image/jpg'), $img->headers);

		$tmp = WideImage::load(IMG_PATH . 'fgnl.jpg');
		$img = new ImageForOutput($tmp->getHandle());
		ob_start();
		$img->output('jpeg');
		$data = ob_get_clean();
		$this->assertEquals(array('Content-length' => strlen($data), 'Content-type' => 'image/jpg'), $img->headers);
	}

	public function testCanvasInstance()
	{
		$img = WideImage::load(IMG_PATH . 'fgnl.jpg');
		$canvas1 = $img->getCanvas();
		$this->assertTrue($canvas1 instanceof Canvas);
		$canvas2 = $img->getCanvas();
		$this->assertTrue($canvas1 === $canvas2);
	}

	public function testSerializeTrueColorImage()
	{
		$img = WideImage::load(IMG_PATH . 'fgnl.jpg');
		$img2 = unserialize(serialize($img));
		$this->assertEquals(get_class($img2), get_class($img));
		$this->assertTrue($img2->isTrueColor());
		$this->assertTrue($img2->isValid());
		$this->assertFalse($img2->isTransparent());
		$this->assertEquals($img->getWidth(), $img2->getWidth());
		$this->assertEquals($img->getHeight(), $img2->getHeight());
	}

	public function testSerializePaletteImage()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.gif');
		$img2 = unserialize(serialize($img));
		$this->assertEquals(get_class($img2), get_class($img));
		$this->assertFalse($img2->isTrueColor());
		$this->assertTrue($img2->isValid());
		$this->assertTrue($img2->isTransparent());
		$this->assertEquals($img->getWidth(), $img2->getWidth());
		$this->assertEquals($img->getHeight(), $img2->getHeight());
	}
}
