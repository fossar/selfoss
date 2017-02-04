<?php
	/**
	 * @package Demos
	 */
	class Demo_flip extends Demo
	{
		public $order = 1200;
		
		function execute($image, $request)
		{
			return $image->flip();
		}
	}
