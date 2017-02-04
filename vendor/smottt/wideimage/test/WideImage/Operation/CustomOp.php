<?php

namespace WideImage\Operation;

/**
 * @package Tests
 */
class CustomOp
{
	public static $args = null;

	public function execute()
	{
		static::$args = func_get_args();

		return static::$args[0]->copy();
	}
}
