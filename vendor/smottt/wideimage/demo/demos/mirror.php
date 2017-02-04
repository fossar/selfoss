<?php
	/**
	 * @package Demos
	 */
	class Demo_mirror extends Demo
	{
		public $order = 1150;
		
		function execute($image, $request)
		{
			return $image->mirror();
		}
	}
