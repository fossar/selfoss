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
  **/

namespace Test\WideImage;

use WideImage\Coordinate;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class CoordinateTest extends WideImage_TestCase
{
	public function testEvaluate()
	{
		$this->assertSame(400, Coordinate::evaluate('+200%', 200));
		$this->assertSame(-1, Coordinate::evaluate('-1', 200));
		$this->assertSame(10, Coordinate::evaluate('+10', 200));
		$this->assertSame(40, Coordinate::evaluate('+20%', 200));
		$this->assertSame(-11, Coordinate::evaluate('-11.23', 200));
		$this->assertSame(-30, Coordinate::evaluate('-15%', 200));
	}
	
	public function testFix()
	{
		$this->assertSame(10, Coordinate::fix('10%', 100));
		$this->assertSame(10, Coordinate::fix('10', 100));
		
		$this->assertSame(-10, Coordinate::fix('-10%', 100));
		$this->assertSame(-1, Coordinate::fix('-1', 100));
		$this->assertSame(-50, Coordinate::fix('-50%', 100));
		$this->assertSame(-100, Coordinate::fix('-100%', 100));
		$this->assertSame(-1, Coordinate::fix('-5%', 20));
		
		$this->assertSame(300, Coordinate::fix('150.12%', 200));
		$this->assertSame(150, Coordinate::fix('150', 200));
		
		$this->assertSame(100, Coordinate::fix('100%-50%', 200));
		$this->assertSame(200, Coordinate::fix('100%', 200));
		
		$this->assertSame(130, Coordinate::fix('50%     -20', 300));
		$this->assertSame(12, Coordinate::fix(' 12 - 0', 300));
		
		$this->assertSame(15, Coordinate::fix('50%', 30));
		$this->assertSame(15, Coordinate::fix('50%-0', 30));
		$this->assertSame(15, Coordinate::fix('50%+0', 30));
		$this->assertSame(0, Coordinate::fix(' -  50%  +   50%', 30));
		$this->assertSame(30, Coordinate::fix(' 50%  + 49.6666%', 30));
	}
	
	public function testAlign()
	{
		$this->assertSame(0, Coordinate::fix('left', 300, 120));
		$this->assertSame(90, Coordinate::fix('center', 300, 120));
		$this->assertSame(180, Coordinate::fix('right', 300, 120));
		$this->assertSame(0, Coordinate::fix('top', 300, 120));
		$this->assertSame(90, Coordinate::fix('middle', 300, 120));
		$this->assertSame(180, Coordinate::fix('bottom', 300, 120));
		
		$this->assertSame(200, Coordinate::fix('bottom+20', 300, 120));
		$this->assertSame(178, Coordinate::fix('-2 + right', 300, 120));
		$this->assertSame(90, Coordinate::fix('right - center', 300, 120));
	}
	
	public function testAlignWithoutSecondaryCoordinate()
	{
		$this->assertSame(0, Coordinate::fix('left', 300));
		$this->assertSame(150, Coordinate::fix('center', 300));
		$this->assertSame(300, Coordinate::fix('right', 300));
		$this->assertSame(0, Coordinate::fix('top', 300));
		$this->assertSame(150, Coordinate::fix('middle', 300));
		$this->assertSame(300, Coordinate::fix('bottom', 300));
		
		$this->assertSame(320, Coordinate::fix('bottom+20', 300));
		$this->assertSame(280, Coordinate::fix('-20 + right', 300));
		$this->assertSame(150, Coordinate::fix('right - center', 300));
	}
			
	public function testMultipleOperands()
	{
		$this->assertSame(6, Coordinate::fix('100%-100+1     + 5', 100));
		$this->assertSame(1, Coordinate::fix('right      +1-   100     - 50%', 200));
		$this->assertSame(200, Coordinate::fix('-right+right +100%', 200));
		$this->assertSame(90, Coordinate::fix('100--++++-10', 200));
	}
	
	/**
	 * @expectedException WideImage\Exception\InvalidCoordinateException
	 */
	public function testInvalidSyntaxEndsWithOperator()
	{
		Coordinate::fix('5+2+', 10);
	}
}
