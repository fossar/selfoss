<?php

/**
 ##DOC-SIGNATURE##

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

 * @package WideImage
 **/

namespace WideImage;

use WideImage\Mapper;
use WideImage\Exception\UnsupportedFormatException;

/**
 * Mapper factory
 * 
 * @package Internals
 **/
abstract class MapperFactory
{
	static protected $mappers       = array();
	static protected $customMappers = array();
	
	static protected $mimeTable = array(
		'image/jpg'   => 'JPEG', 
		'image/jpeg'  => 'JPEG', 
		'image/pjpeg' => 'JPEG', 
		'image/gif'   => 'GIF', 
		'image/png'   => 'PNG',
		'image/webp'  => 'WEBP'
	);
	
	/**
	 * Returns a mapper, based on the $uri and $format
	 * 
	 * @param string $uri File URI
	 * @param string $format File format (extension or mime-type) or null
	 * @return mixed
	 **/
	public static function selectMapper($uri, $format = null)
	{
		$format = self::determineFormat($uri, $format);

		if (empty($format)) {
			return false;
		}
		
		if (array_key_exists($format, self::$mappers)) {
			return self::$mappers[$format];
		}
		
		$mapperClassName = '\\WideImage\\Mapper\\' . $format;
		
		// why not use autoloading?
		// if (!class_exists($mapperClassName, false)) {
		if (!class_exists($mapperClassName)) {
			throw new UnsupportedFormatException("Format '{$format}' is not supported.");
		}
		
		if (class_exists($mapperClassName)) {
			self::$mappers[$format] = new $mapperClassName();
			return self::$mappers[$format];
		}
	}
	
	public static function registerMapper($mapper_class_name, $mime_type, $extension)
	{
		self::$customMappers[$mime_type] = $mapper_class_name;
		self::$mimeTable[$mime_type] = $extension;
	}
	
	public static function getCustomMappers()
	{
		return self::$customMappers;
	}
	
	public static function determineFormat($uri, $format = null)
	{
		if ($format == null) {
			$format = self::extractExtension($uri);
		}
		
		// mime-type match
		if (preg_match('~[a-z]*/[a-z-]*~i', $format)) {
			if (isset(self::$mimeTable[strtolower($format)])) {
				return self::$mimeTable[strtolower($format)];
			}
		}
		
		// clean the string
		$format = strtoupper(preg_replace('/[^a-z0-9_-]/i', '', $format));
		if ($format == 'JPG') {
			$format = 'JPEG';
		}
		
		return $format;
	}
	
	public static function mimeType($format)
	{
		$format = strtoupper($format);
		
		if ($format == 'JPG') {
			$format = 'JPEG';
		}
		
		return array_search($format, self::$mimeTable);
	}
	
	public static function extractExtension($uri)
	{
		$p = strrpos($uri, '.');
		
		if ($p === false) {
			return '';
		}
		
		return substr($uri, $p + 1);
	}
}
