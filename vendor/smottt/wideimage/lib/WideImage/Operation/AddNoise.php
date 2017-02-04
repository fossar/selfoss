<?php
	/**
	* @author Tomasz Kapusta
	* @copyright 2010
	
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
	
	* @package Internal/Operations
	**/

namespace WideImage\Operation;

/**
 * Noise filter
 * 
 * @package Internal/Operations
 */
class AddNoise
{
	/**
	 * Returns image with noise added
	 *
	 * @param \WideImage\Image $image
	 * @param float $amount
	 * @param const $type
	 * @param float $threshold
	 * @return \WideImage\Image
	 */
	public function execute($image, $amount, $type) {
		switch ($type) {
			case 'salt&pepper':
				$fun = 'saltPepperNoise_fun'; 
				break;
			case 'color':
				$fun = 'colorNoise_fun';
				break;	
			default	:
				$fun = 'monoNoise_fun';
				break; 			
		}
		
		return static::filter($image->asTrueColor(), $fun, $amount);			
	}
	
	/**
	 * Returns image with every pixel changed by specififed function
	 *
	 * @param \WideImage\Image $image
	 * @param str $function
	 * @param int $value
	 * @return \WideImage\Image
	 */
	public function filter($image, $function, $value)
	{
		for ($y = 0; $y < $image->getHeight(); $y++) {
	    	for ($x = 0; $x< $image->getWidth(); $x++) {	     
				$rgb = imagecolorat($image->getHandle(), $x, $y);				
				
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				
				static::$function($r, $g, $b, $value);
				
				$color = imagecolorallocatealpha($image->getHandle(), $r, $g, $b, $a);				
				imagesetpixel($image->getHandle(), $x, $y, $color); 
	      	}
	    }
	    
	    return $image;
	}
	
	/**
	 * Adds color noise by altering given R,G,B values using specififed amount
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param int $value		 
	 * @return void
	 */
	public function colorNoise_fun(&$r, &$g, &$b, $amount)
	{				
		$r = static::byte($r + mt_rand(0, $amount) - ($amount >> 1) );
		$g = static::byte($g + mt_rand(0, $amount) - ($amount >> 1) );
		$b = static::byte($b + mt_rand(0, $amount) - ($amount >> 1) );
	}
	
	/**
	 * Adds mono noise by altering given R,G,B values using specififed amount
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param int $value		 
	 * @return void
	 */
	public function monoNoise_fun(&$r, &$g, &$b, $amount)
	{				
		$rand = mt_rand(0, $amount) - ($amount >> 1);
		
		$r = static::byte($r + $rand);
		$g = static::byte($g + $rand);
		$b = static::byte($b + $rand);
	}
	
	/**
	 * Adds salt&pepper noise by altering given R,G,B values using specififed amount
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param int $value		 
	 * @return void
	 */
	public function saltPepperNoise_fun(&$r, &$g, &$b, $amount)
	{
		if (mt_rand(0, 255 - $amount) != 0) {
			return;
		}
		
		$rand = mt_rand(0, 1);
		
		switch ($rand) {
			case 0:
				$r = $g = $b = 0;
				break;
			case 1:
				$r = $g = $b = 255;
				break;
		}
	}
	
	/**
	 * Returns value within (0,255)
	 *
	 * @param int $b		 
	 * @return int
	 */		
	public function byte($b)
	{
		if ($b > 255) {
			return 255;
		}
		
		if ($b < 0) {
			return 0;
		}
		
		return (int) $b;
	}
}
