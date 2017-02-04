<?php
	/**
	 * @package Demos
	 */
	class Demo_asGrayscale extends Demo
	{
		public $order = 300;
		
		function execute($img, $request)
		{
			return $img->asGrayscale();
		}
	}
