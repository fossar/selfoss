<?php
	/**
	 * @package Demos
	 */
	class Demo_resize extends Demo
	{
		public $order = 900;
		
		function init()
		{
			$this->addField(new CoordinateField('width', 120));
			$this->addField(new CoordinateField('height', null));
			$this->addField(new SelectField('fit', array('inside', 'fill', 'outside')));
			$this->addField(new SelectField('scale', array('any', 'down', 'up')));
		}
		
		function execute($image, $request)
		{
			$width = $this->fields['width']->value;
			$height = $this->fields['height']->value;
			$fit = $this->fields['fit']->value;
			$scale = $this->fields['scale']->value;
			
			return $image->resize($width, $height, $fit, $scale);
		}
	}
