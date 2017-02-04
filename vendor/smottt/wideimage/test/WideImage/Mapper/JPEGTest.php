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

namespace Test\WideImage\Mapper;

use WideImage\MapperFactory;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class JPEGTest extends WideImage_TestCase
{
	protected $mapper;
	
	public function setup()
	{
		$this->mapper = MapperFactory::selectMapper(null, 'jpg');
	}
	
	public function teardown()
	{
		$this->mapper = null;
		
		if (file_exists(IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'test.jpg')) {
			unlink(IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'test.jpg');
		}
	}
	
	public function testLoad()
	{
		$handle = $this->mapper->load(IMG_PATH . 'fgnl.jpg');
		$this->assertTrue(is_resource($handle));
		$this->assertEquals(174, imagesx($handle));
		$this->assertEquals(287, imagesy($handle));
		imagedestroy($handle);
	}
	
	public function testSaveToString()
	{
		$handle = imagecreatefromjpeg(IMG_PATH . 'fgnl.jpg');
		ob_start();
		$this->mapper->save($handle);
		$string = ob_get_clean();
		$this->assertTrue(strlen($string) > 0);
		imagedestroy($handle);
		
		// string contains valid image data
		$handle = imagecreatefromstring($string);
		$this->assertTrue(is_resource($handle));
		imagedestroy($handle);
	}
	
	public function testSaveToFile()
	{
		$handle = imagecreatefromjpeg(IMG_PATH . 'fgnl.jpg');
		$this->mapper->save($handle, IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'test.jpg');
		$this->assertTrue(filesize(IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'test.jpg') > 0);
		imagedestroy($handle);
		
		// file is a valid image
		$handle = imagecreatefromjpeg(IMG_PATH . 'temp' . DIRECTORY_SEPARATOR . 'test.jpg');
		$this->assertTrue(is_resource($handle));
		imagedestroy($handle);
	}
	
	public function testQuality()
	{
		$handle = imagecreatefromjpeg(IMG_PATH . 'fgnl.jpg');
		
		ob_start();
		$this->mapper->save($handle, null, 100);
		$hq = ob_get_clean();
		
		ob_start();
		$this->mapper->save($handle, null, 10);
		$lq = ob_get_clean();
		
		$this->assertTrue(strlen($hq) > 0);
		$this->assertTrue(strlen($lq) > 0);
		$this->assertTrue(strlen($hq) > strlen($lq));
		imagedestroy($handle);
	}
}
