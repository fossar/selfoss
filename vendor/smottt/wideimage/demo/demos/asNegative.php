<?php
	/**
	 * @package Demos
	 */
	class Demo_asNegative extends Demo
	{
		public $order = 300;
		
		function execute($img, $request)
		{
			return $img->asNegative();
		}
	}
