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

include_once __DIR__ . '/Mapper/FOO.php';
include_once __DIR__ . '/Mapper/FOO2.php';

use WideImage\WideImage;
use WideImage\PaletteImage;
use WideImage\TrueColorImage;
use WideImage\Mapper\FOO;
use WideImage\Mapper\FOO2;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class WideImageTest extends WideImage_TestCase
{
	protected $_FILES;
	
	public function setup()
	{
		$this->_FILES = $_FILES;
		$_FILES = array();
	}
	
	public function teardown()
	{
		$_FILES = $this->_FILES;
		
		if (PHP_OS == 'WINNT') {
			chdir(IMG_PATH . "temp");
			
			foreach (new \DirectoryIterator(IMG_PATH . "temp") as $file) {
				if (!$file->isDot()) {
					if ($file->isDir()) {
						exec("rd /S /Q {$file->getFilename()}\n");
					} else {
						unlink($file->getFilename());
					}
				}
			}
		} else {
			exec("rm -rf " . IMG_PATH . 'temp/*');
		}
	}
	
	public function testLoadFromFile()
	{
		$img = WideImage::load(IMG_PATH . '100x100-red-transparent.gif');
		$this->assertTrue($img instanceof PaletteImage);
		$this->assertValidImage($img);
		$this->assertFalse($img->isTrueColor());
		$this->assertEquals(100, $img->getWidth());
		$this->assertEquals(100, $img->getHeight());
		
		$img = WideImage::load(IMG_PATH . '100x100-rainbow.png');
		$this->assertTrue($img instanceof TrueColorImage);
		$this->assertValidImage($img);
		$this->assertTrue($img->isTrueColor());
		$this->assertEquals(100, $img->getWidth());
		$this->assertEquals(100, $img->getHeight());
	}
	
	public function testLoadFromString()
	{
		$img = WideImage::load(file_get_contents(IMG_PATH . '100x100-rainbow.png'));
		$this->assertTrue($img instanceof TrueColorImage);
		$this->assertValidImage($img);
		$this->assertTrue($img->isTrueColor());
		$this->assertEquals(100, $img->getWidth());
		$this->assertEquals(100, $img->getHeight());
	}
	
	public function testLoadFromHandle()
	{
		$handle = imagecreatefrompng(IMG_PATH . '100x100-rainbow.png');
		$img = WideImage::loadFromHandle($handle);
		$this->assertValidImage($img);
		$this->assertTrue($img->isTrueColor());
		$this->assertSame($handle, $img->getHandle());
		$this->assertEquals(100, $img->getWidth());
		$this->assertEquals(100, $img->getHeight());
		unset($img);
		$this->assertFalse(WideImage::isValidImageHandle($handle));
	}
	
	public function testLoadFromUpload()
	{
		copy(IMG_PATH . '100x100-rainbow.png', IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg');
		$_FILES = array(
			'testupl' => array(
				'name' => '100x100-rainbow.png',
				'type' => 'image/png',
				'size' => strlen(file_get_contents(IMG_PATH . '100x100-rainbow.png')),
				'tmp_name' => IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg',
				'error' => false,
			)
		);
		
		$img = WideImage::loadFromUpload('testupl');
		$this->assertValidImage($img);
	}
	
	public function testLoadFromMultipleUploads()
	{
		copy(IMG_PATH . '100x100-rainbow.png', IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg1');
		copy(IMG_PATH . 'splat.tga', IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg2');
		$_FILES = array(
			'testupl' => array(
				'name' => array('100x100-rainbow.png', 'splat.tga'),
				'type' => array('image/png', 'image/tga'),
				'size' => array(
						strlen(file_get_contents(IMG_PATH . '100x100-rainbow.png')), 
						strlen(file_get_contents(IMG_PATH . 'splat.tga'))
					),
				'tmp_name' => array(
						IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg1',
						IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg2'
					),
				'error' => array(false, false),
			)
		);
		
		$images = WideImage::loadFromUpload('testupl');
		$this->assertInternalType("array", $images);
		$this->assertValidImage($images[0]);
		$this->assertValidImage($images[1]);
		
		$img = WideImage::loadFromUpload('testupl', 1);
		$this->assertValidImage($img);
	}
	
	public function testLoadMagicalFromHandle()
	{
		$img = WideImage::load(imagecreatefrompng(IMG_PATH . '100x100-rainbow.png'));
		$this->assertValidImage($img);
	}
	
	
	public function testLoadMagicalFromBinaryString()
	{
		$img = WideImage::load(file_get_contents(IMG_PATH . '100x100-rainbow.png'));
		$this->assertValidImage($img);
	}
	
	public function testLoadMagicalFromFile()
	{
		$img = WideImage::load(IMG_PATH . '100x100-rainbow.png');
		$this->assertValidImage($img);
		copy(IMG_PATH . '100x100-rainbow.png', IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg');
		$_FILES = array(
			'testupl' => array(
				'name' => 'fgnl.bmp',
				'type' => 'image/bmp',
				'size' => strlen(file_get_contents(IMG_PATH . 'fgnl.bmp')),
				'tmp_name' => IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg',
				'error' => false,
			)
		);
		$img = WideImage::load('testupl');
		$this->assertValidImage($img);
	}
	
	public function testLoadFromStringWithCustomMapper()
	{
		$img = WideImage::loadFromString(file_get_contents(IMG_PATH . 'splat.tga'));
		$this->assertValidImage($img);
	}
	
	public function testLoadFromFileWithInvalidExtension()
	{
		$img = WideImage::load(IMG_PATH . 'actually-a-png.jpg');
		$this->assertValidImage($img);
	}
	
	public function testLoadFromFileWithInvalidExtensionWithCustomMapper()
	{
		if (PHP_OS == 'WINNT')
			$this->markTestSkipped("For some reason, this test kills PHP my 32-bit Vista + PHP 5.3.1.");
		
		$img = WideImage::loadFromFile(IMG_PATH . 'fgnl-bmp.jpg');
		$this->assertValidImage($img);
	}
	
	/**
	 * @expectedException WideImage\Exception\InvalidImageSourceException
	 */
	public function testLoadFromStringEmpty()
	{
		WideImage::loadFromString('');
	}
	
	public function testLoadBMPMagicalFromUpload()
	{
		copy(IMG_PATH . 'fgnl.bmp', IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg');
		$_FILES = array(
			'testupl' => array(
				'name' => 'fgnl.bmp',
				'type' => 'image/bmp',
				'size' => strlen(file_get_contents(IMG_PATH . 'fgnl.bmp')),
				'tmp_name' => IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'upltmpimg',
				'error' => false,
			)
		);
		$img = WideImage::load('testupl');
		$this->assertValidImage($img);
	}
	
	public function testMapperLoad()
	{
		FOO::$handle = imagecreate(10, 10);
		$filename = IMG_PATH . 'image.foo';
		WideImage::registerCustomMapper(__NAMESPACE__ . '\\FOO', 'image/foo', 'foo');
		$img = WideImage::load($filename);
		$this->assertEquals(FOO::$calls['load'], array($filename));
		imagedestroy(FOO::$handle);
	}
	
	public function testLoadFromFileFallbackToLoadFromString()
	{
		FOO::$handle = imagecreate(10, 10);
		$filename = IMG_PATH . 'image-actually-foo.foo2';
		WideImage::registerCustomMapper('FOO', 'image/foo', 'foo');
		WideImage::registerCustomMapper('FOO2', 'image/foo2', 'foo2');
		$img = WideImage::load($filename);
		$this->assertEquals(FOO2::$calls['load'], array($filename));
		$this->assertEquals(FOO::$calls['loadFromString'], array(file_get_contents($filename)));
		imagedestroy(FOO::$handle);
	}
	
	public function testMapperSaveToFile()
	{
		$img = WideImage::load(IMG_PATH . 'fgnl.jpg');
		$img->saveToFile('test.foo', '123', 789);
		$this->assertEquals(FOO::$calls['save'], array($img->getHandle(), 'test.foo', '123', 789));
	}
	
	public function testMapperAsString()
	{
		$img = WideImage::load(IMG_PATH . 'fgnl.jpg');
		$str = $img->asString('foo', '123', 789);
		$this->assertEquals(FOO::$calls['save'], array($img->getHandle(), null, '123', 789));
		$this->assertEquals('out', $str);
	}
	
	/**
	 * @expectedException WideImage\Exception\InvalidImageSourceException
	 */
	public function testInvalidImageFile()
	{
		WideImage::loadFromFile(IMG_PATH . 'fakeimage.png');
	}
	
	/**
	 * @expectedException WideImage\Exception\InvalidImageSourceException
	 */
	public function testEmptyString()
	{
		WideImage::load('');
	}
	
	/**
	 * @expectedException WideImage\Exception\InvalidImageSourceException
	 */
	public function testInvalidImageStringData()
	{
		WideImage::loadFromString('asdf');
	}
	
	/**
	 * @expectedException WideImage\Exception\InvalidImageSourceException
	 */
	public function testInvalidImageHandle()
	{
		WideImage::loadFromHandle(0);
	}
	
	/**
	 * @expectedException WideImage\Exception\InvalidImageSourceException
	 */
	public function testInvalidImageUploadField()
	{
		WideImage::loadFromUpload('xyz');
	}
}
