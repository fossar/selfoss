<?php


namespace Test;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use WideImage\WideImage;

define('TEST_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('IMG_PATH', TEST_PATH . 'images' . DIRECTORY_SEPARATOR);

// check for xdebug scream
if (ini_get('xdebug.scream') == 1) {
	ini_set('xdebug.scream', 0);
}

abstract class WideImage_TestCase extends \PHPUnit_Framework_TestCase
{
	public function load($file)
	{
		return WideImage::load(IMG_PATH . $file);
	}
	
	public function assertValidImage($image)
	{
		$this->assertInstanceOf('WideImage\\Image', $image);
		$this->assertTrue($image->isValid());
	}
	
	public function assertDimensions($image, $width, $height)
	{
		$this->assertEquals($width, $image->getWidth());
		$this->assertEquals($height, $image->getHeight());
	}
	
	public function assertTransparentColorMatch($img1, $img2)
	{
		$tc1 = $img1->getTransparentColorRGB();
		$tc2 = $img2->getTransparentColorRGB();
		$this->assertEquals($tc1, $tc2);
	}
	
	public function assertTransparentColorAt($img, $x, $y)
	{
		$this->assertEquals($img->getTransparentColor(), $img->getColorAt($x, $y));
	}
	
	public function assertRGBWithinMargin($rec, $r, $g, $b, $a, $margin)
	{
		if (is_array($r)) {
			$a = $r['alpha'];
			$b = $r['blue'];
			$g = $r['green'];
			$r = $r['red'];
		}
		
		$result = 
			abs($rec['red'] - $r) <= $margin && 
			abs($rec['green'] - $g) <= $margin && 
			abs($rec['blue'] - $b) <= $margin;
		
		$result = $result && ($a === null || abs($rec['alpha'] - $a) <= $margin);
		
		$this->assertTrue($result, 
			"RGBA [{$rec['red']}, {$rec['green']}, {$rec['blue']}, {$rec['alpha']}] " . 
			"doesn't match RGBA [$r, $g, $b, $a] within margin [$margin].");
	}
	
	public function assertRGBAt($img, $x, $y, $rgba)
	{
		if (is_array($rgba)) {
			$cmp = $img->getRGBAt($x, $y);
		} else {
			$cmp = $img->getColorAt($x, $y);
		}
		
		$this->assertSame($cmp, $rgba);
	}
	
	public function assertRGBNear($rec, $r, $g = null, $b = null, $a = null)
	{
		$this->assertRGBWithinMargin($rec, $r, $g, $b, $a, 2);
	}
	
	public function assertRGBEqual($rec, $r, $g = null, $b = null, $a = null)
	{
		$this->assertRGBWithinMargin($rec, $r, $g, $b, $a, 0);
	}
}
