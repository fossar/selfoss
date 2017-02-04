<?php

namespace WideImage\Mapper;

class FOO2
{
	public static $calls = array();

	public static function reset()
	{
		static::$calls = array();
	}

	public function load()
	{
		static::$calls['load'] = func_get_args();

		return false;
	}

	public function loadFromString($data)
	{
		static::$calls['loadFromString'] = func_get_args();
	}

	public function save($image, $uri = null)
	{
		static::$calls['save'] = func_get_args();

		if ($uri == null) {
			echo 'out';
		}
	}
}