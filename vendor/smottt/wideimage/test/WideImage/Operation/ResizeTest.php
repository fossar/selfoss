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
use WideImage\Image;
use WideImage\TrueColorImage;
use WideImage\Operation\Resize;
use Test\WideImage_TestCase;

// WideImage_OperationFactory::get('Resize');

class ResizeTestable extends Resize
{
	public function prepareDimensions($img, $width, $height, $fit)
	{
		return parent::prepareDimensions($img, $width, $height, $fit);
	}
}

/**
 * @package Tests
 */
class ResizeTest extends WideImage_TestCase
{
	public function testProxyMethod()
	{
		$op = $this->getMock('WideImage\\Operation\\Resize', array('execute'));
		$img = $this->getMock('WideImage\\PaletteImage', array('getOperation'), array(imagecreate(10, 10)));
		
		$img->expects($this->exactly(2))->
			method('getOperation')->with('Resize')->
			will($this->returnValue($op));
		
		$op->expects($this->at(0))->
			method('execute')->with($img, 'WIDTH', 'HEIGHT', 'FIT', 'SCALE');
		
		$op->expects($this->at(1))->
			method('execute')->with($img, null, null, 'inside', 'any');
		
		$img->resize('WIDTH', 'HEIGHT', 'FIT', 'SCALE');
		$img->resize();
	}
	
	public function testResultTypeIsSameAsInput()
	{
		$this->assertInstanceOf("WideImage\\PaletteImage", WideImage::createPaletteImage(20, 20)->resize(10, 10));
		$this->assertInstanceOf("WideImage\\TrueColorImage", WideImage::createTrueColorImage(20, 20)->resize(10, 10));
	}
	
	public function testResizeWithoutParametersDoesNothing()
	{
		$img = WideImage::createTrueColorImage(70, 20);
		$res = $img->resize();
		$this->assertDimensions($res, $img->getWidth(), $img->getHeight());
	}
	
	public function testPreservesTransparency()
	{
		$img = $this->load('100x100-color-hole.gif');
		$this->assertTrue($img->isTransparent());
		$res = $img->resize(50, 50);
		$this->assertTrue($res->isTransparent());
		$this->assertTransparentColorMatch($img, $res);
		$this->assertEquals($res->getColorAt(25, 25), $res->getTransparentColor());
	}
	
	public function testFitFill()
	{
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'fill'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 30, 'fill'), 120, 30);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(20, 130, 'fill'), 20, 130);
	}
	
	public function testFitOutside()
	{
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'outside'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 30, 'outside'), 120, 60);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(20, 30, 'outside'), 60, 30);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(20, 100, 'outside'), 200, 100);
	}
	
	public function testFitInside()
	{
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'inside'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 30, 'inside'), 60, 30);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(20, 30, 'inside'), 20, 10);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(20, 100, 'inside'), 20, 10);
		$this->assertDimensions(WideImage::createPaletteImage(950, 266)->resize(256, null, 'inside'), 256, 72);
	}
	
	public function testScaleDown()
	{
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'fill', 'down'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 60, 'fill', 'down'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 40, 'fill', 'down'), 120, 40);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 60, 'fill', 'down'), 90, 60);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 30, 'fill', 'down'), 90, 30);
		
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'inside', 'down'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 60, 'inside', 'down'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 40, 'inside', 'down'), 80, 40);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 60, 'inside', 'down'), 90, 45);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 30, 'inside', 'down'), 60, 30);
		
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'outside', 'down'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 60, 'outside', 'down'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 40, 'outside', 'down'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 60, 'outside', 'down'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 30, 'outside', 'down'), 90, 45);
	}
	
	public function testScaleUp()
	{
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'fill', 'up'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 60, 'fill', 'up'), 120, 60);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 40, 'fill', 'up'), 120, 40);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 60, 'fill', 'up'), 90, 60);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 30, 'fill', 'up'), 100, 50);
		
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'inside', 'up'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 60, 'inside', 'up'), 120, 60);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 40, 'inside', 'up'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 60, 'inside', 'up'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 30, 'inside', 'up'), 100, 50);
		
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(100, 50, 'outside', 'up'), 100, 50);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 60, 'outside', 'up'), 120, 60);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(120, 40, 'outside', 'up'), 120, 60);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 60, 'outside', 'up'), 120, 60);
		$this->assertDimensions(WideImage::createPaletteImage(100, 50)->resize(90, 30, 'outside', 'up'), 100, 50);
	}
	
	public function testResizeFill()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.gif');
		$resized = $img->resize(50, 20, 'fill');
		$this->assertTrue($resized instanceof Image);
		$this->assertTrue($resized->isTransparent());
		$this->assertEquals(50, $resized->getWidth());
		$this->assertEquals(20, $resized->getHeight());
		$this->assertRGBEqual($resized->getRGBAt(5, 5), 255, 255, 0);
		$this->assertRGBEqual($resized->getRGBAt(45, 5), 0, 0, 255);
		$this->assertRGBEqual($resized->getRGBAt(45, 15), 0, 255, 0);
		$this->assertRGBEqual($resized->getRGBAt(5, 15), 255, 0, 0);
		
		$this->assertRGBEqual($resized->getRGBAt(25, 10), 255, 255, 255);
		$this->assertRGBEqual($img->getTransparentColorRGB(), 255, 255, 255);
	}
	
	public function testNullDimensionsAreCalculatedForFill()
	{
		$img = TrueColorImage::create(100, 50);
		$resized = $img->resize(30, null, 'fill');
		$this->assertEquals(30, $resized->getWidth());
		$this->assertEquals(15, $resized->getHeight());
		
		$img = TrueColorImage::create(100, 50);
		$resized = $img->resize(null, 30, 'fill');
		$this->assertEquals(60, $resized->getWidth());
		$this->assertEquals(30, $resized->getHeight());
		
		$img = TrueColorImage::create(100, 50);
		$resized = $img->resize(30, 30, 'fill');
		$this->assertEquals(30, $resized->getWidth());
		$this->assertEquals(30, $resized->getHeight());
		
		$img = TrueColorImage::create(100, 50);
		$resized = $img->resize(30, 40, 'fill');
		$this->assertEquals(30, $resized->getWidth());
		$this->assertEquals(40, $resized->getHeight());
	}
	
	public function testResizeInside()
	{
		$img = WideImage::load(IMG_PATH . '100x100-color-hole.gif');
		$resized = $img->resize(50, 20, 'inside');
		$this->assertTrue($resized instanceof Image);
		$this->assertTrue($resized->isTransparent());
		$this->assertEquals(20, $resized->getWidth());
		$this->assertEquals(20, $resized->getHeight());
		/*
		$this->assertRGBEqual($resized->getRGBAt(5, 5), 255, 255, 0);
		$this->assertRGBEqual($resized->getRGBAt(45, 5), 0, 0, 255);
		$this->assertRGBEqual($resized->getRGBAt(45, 15), 0, 255, 0);
		$this->assertRGBEqual($resized->getRGBAt(5, 15), 255, 0, 0);
		$this->assertRGBEqual($resized->getRGBAt(25, 10), 255, 255, 255);
		$this->assertRGBEqual($img->getTransparentColorRGB(), 255, 255, 255);
		*/
	}
	
	public function testResizeDown()
	{
		$img = TrueColorImage::create(100, 100);
		$resized = $img->resizeDown(30);
		$this->assertEquals(30, $resized->getWidth());
		$this->assertEquals(30, $resized->getHeight());
		
		$img = TrueColorImage::create(200, 100);
		$resized = $img->resizeDown(100);
		$this->assertEquals(100, $resized->getWidth());
		$this->assertEquals(50, $resized->getHeight());
		
		$img = TrueColorImage::create(200, 100);
		$resized = $img->resizeDown(null, 30);
		$this->assertEquals(60, $resized->getWidth());
		$this->assertEquals(30, $resized->getHeight());
		
		$img = TrueColorImage::create(200, 100);
		$resized = $img->resizeDown(201);
		$this->assertEquals($img->getWidth(), $resized->getWidth());
		$this->assertEquals($img->getHeight(), $resized->getHeight());
		
		$img = TrueColorImage::create(200, 100);
		$resized = $img->resizeDown(null, 300);
		$this->assertEquals($img->getWidth(), $resized->getWidth());
		$this->assertEquals($img->getHeight(), $resized->getHeight());
	}
	
	public function testResizeUp()
	{
		$img = TrueColorImage::create(100, 100);
		$resized = $img->resizeUp(300);
		$this->assertEquals(300, $resized->getWidth());
		$this->assertEquals(300, $resized->getHeight());
		
		$img = TrueColorImage::create(200, 100);
		$resized = $img->resizeUp(300);
		$this->assertEquals(300, $resized->getWidth());
		$this->assertEquals(150, $resized->getHeight());
		
		$img = TrueColorImage::create(20, 10);
		$resized = $img->resizeUp(null, 30);
		$this->assertEquals(60, $resized->getWidth());
		$this->assertEquals(30, $resized->getHeight());
		
		$img = TrueColorImage::create(200, 100);
		$resized = $img->resizeUp(199);
		$this->assertEquals($img->getWidth(), $resized->getWidth());
		$this->assertEquals($img->getHeight(), $resized->getHeight());
		
		$img = TrueColorImage::create(200, 100);
		$resized = $img->resizeUp(null, 10);
		$this->assertEquals($img->getWidth(), $resized->getWidth());
		$this->assertEquals($img->getHeight(), $resized->getHeight());
	}
	
	/**
	 * @group bugs
	 */
	public function testResizeBug214()
	{
		$img = TrueColorImage::create(1600, 1200);
		$op = new ResizeTestable();
		$dim = $op->prepareDimensions($img, 214, null, 'outside');
		$this->assertEquals(214, $dim['width']);
		$this->assertEquals(161, $dim['height']);
	}
	
	/**
	 * https://sourceforge.net/tracker/?func=detail&aid=3312764&group_id=190526&atid=933712
	 * @group bugs
	 */
	public function testResizeBug950to256()
	{
		$img = TrueColorImage::create(950, 266);
		$op = new ResizeTestable();
		$dim = $op->prepareDimensions($img, 256, null, 'inside');
		$this->assertEquals(256, $dim['width']);
		$this->assertEquals(72, $dim['height']);
	}
}
