<?php
	/**
	 * @package Demos
	 */
	class Demo_resizeCanvas extends Demo
	{
		public $order = 910;
		
		function init()
		{
			$this->addField(new CoordinateField('width', '100%+30'));
			$this->addField(new CoordinateField('height', 200));
			$this->addField(new CoordinateField('left', '2'));
			$this->addField(new CoordinateField('top', 'bottom-10'));
			$this->addField(new ColorField('color', 'ffffff'));
			$this->addField(new SelectField('scale', array('any', 'down', 'up'), 'any'));
			$this->addField(new CheckboxField('merge', false, "Merge or copy over"));
		}
		
		function execute($image, $request)
		{
			$width = $this->fields['width']->value;
			$height = $this->fields['height']->value;
			$left = $this->fields['left']->value;
			$top = $this->fields['top']->value;
			$color = $this->fields['color']->value;
			$scale = $this->fields['scale']->value;
			$merge = $this->fields['merge']->value;
			
			return $image->resizeCanvas($width, $height, $left, $top, $color ? hexdec($color) : null, $scale, $merge);
		}
	}
