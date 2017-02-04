<?php
	/**
	 * @package Demos
	 */
	class Demo_crop extends Demo
	{
		public $order = 1000;
		
		function init()
		{
			$this->addField(new CoordinateField('left', 10));
			$this->addField(new CoordinateField('top', 20));
			$this->addField(new CoordinateField('width', 120));
			$this->addField(new CoordinateField('height', 60));
		}
		
		function execute($image, $request)
		{
			$left = $this->fields['left']->value;
			$top = $this->fields['top']->value;
			$width = $this->fields['width']->value;
			$height = $this->fields['height']->value;
			
			return $image->crop($left, $top, $width, $height);
		}
	}
