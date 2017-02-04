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

include_once __DIR__ . '/Operation/MyOperation.php';

use WideImage\OperationFactory;
use WideImage\Operation\MyOperation;
use Test\WideImage_TestCase;

/**
 * @package Tests
 */
class OperationFactoryTest extends WideImage_TestCase
{
	public function testFactoryReturnsCached()
	{
		$op1 = OperationFactory::get('Mirror');
		$op2 = OperationFactory::get('Mirror');
		$this->assertSame($op1, $op2);
	}
	
	/**
	 * @expectedException WideImage\Exception\UnknownImageOperationException
	 */
	public function testNoOperation()
	{
		$op = OperationFactory::get('NoSuchOp');
	}
	
	public function testUserDefinedOp()
	{
		$op = OperationFactory::get('MyOperation');
		$this->assertTrue($op instanceof MyOperation);
	}
}
