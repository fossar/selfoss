<?php
	/**
	 * @package Demos
	 */
	class Demo_getMask extends Demo
	{
		public $order = 550;
		
		function execute($img, $request)
		{
			return $img->getMask();
		}
	}
