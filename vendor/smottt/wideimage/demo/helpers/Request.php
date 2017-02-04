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
	
	/**
	 * @package Demos
	 */
	class Request
	{
		protected $vars = array();
		
		protected static $instance;
		static function getInstance()
		{
			if (self::$instance === null)
				self::$instance = new Request;
			return self::$instance;
		}
		
		protected function __construct()
		{
			$this->vars = $_GET;
			
			/*
			// have to rely on parsing QUERY_STRING, thanks to PHP
			// http://bugs.php.net/bug.php?id=39078
			// http://bugs.php.net/bug.php?id=45149
			$all_vars = explode('&', $_SERVER['QUERY_STRING']);
			foreach ($all_vars as $keyval)
			{
				if (strlen($keyval) == 0)
					continue;
				
				if (strpos($keyval, '=') === false)
				{
					$key = $keyval;
					$value = true;
				}
				else
				{
					list($key, $value) = explode('=', $keyval);
					#$value = str_replace('%2B', '[[PLUS]]', $value);
					$value = urldecode($value);
					#$value = str_replace('[[PLUS]]', '+', $value);
				}
				$this->vars[$key] = $value;
			}
			*/
		}
		
		function get($key, $default = null)
		{
			if (isset($this->vars[$key]))
				return $this->vars[$key];
			else
				return $default;
		}
		
		function set($key, $value)
		{
			$this->vars[$key] = $value;
		}
		
		function getInt($key, $default = 0)
		{
			$value = self::get($key);
			if (strlen($value) > 0)
				return intval($value);
			else
				return $default;
		}
		
		function getFloat($key, $default = 0)
		{
			$value = self::get($key);
			if (strlen($value) > 0)
				return floatval($value);
			else
				return $default;
		}
		
		function getCoordinate($key, $default = 0)
		{
			$v = self::get($key);
			if (strlen($v) > 0 && \WideImage\Coordinate::parse($v) !== null)
				return self::get($key);
			else
				return $default;
		}
		
		function getOption($key, $valid = array(), $default = null)
		{
			$value = self::get($key);
			if ($value !== null && in_array($value, $valid))
				return strval($value);
			else
				return $default;
		}
		
		function getColor($key, $default = '000000')
		{
			$value = self::get($key);
			if (substr($value, 0, 1) == '#')
				$value = substr($value, 1);
			
			if ($value === '' || preg_match('~^[0-9a-f]{1,6}$~i', $value))
				return $value;
			else
				return $default;
		}
		
		function getRegex($key, $regex, $default = null)
		{
			$value = self::get($key);
			if ($value !== null && preg_match($regex, $value))
				return $value;
			else
				return $default;
		}
	}
