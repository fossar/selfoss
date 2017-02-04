<?php
	/**
	 * @package Demos
	 */
	class Demo_rotate extends Demo
	{
		public $order = 1100;

		function init()
		{
			$this->addField(new AngleField('angle', 25));
			$this->addField(new ColorField('color', ''));
		}
		
		function execute($image, $request)
		{
			$angle = $this->fields['angle']->value;
			$color = $this->fields['color']->value;
			
			return $image->rotate($angle, $color ? hexdec($color) : null);
		}
	}
