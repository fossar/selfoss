<?php

namespace WideImage\Mapper;

/**
 * @package Tests
 */
class FOO
{
	public static $calls = array();
	public static $handle = null;

	public static function reset()
	{
		static::$calls  = array();
		static::$handle = null;
	}

	public function load()
	{
		static::$calls['load'] = func_get_args();

		return static::$handle;
	}

	public function loadFromString($data)
	{
		static::$calls['loadFromString'] = func_get_args();

		return static::$handle;
	}

	public function save($image, $uri = null)
	{
		static::$calls['save'] = func_get_args();

		if ($uri == null) {
			echo 'out';
		}

		return true;
	}
}