<?php
	/**
	 * @package Demos
	 */
	class Demo_roundCorners extends Demo
	{
		public $order = 1075;
		
		function init()
		{
			$this->addField(new IntField('radius', 30));
			$this->addField(new ColorField('color', 'ffffff'));
			$this->addField(new IntField('smoothness', 2));
			
			$this->addField(new CheckboxField('top-left', true));
			$this->addField(new CheckboxField('top-right', true));
			$this->addField(new CheckboxField('bottom-right', true));
			$this->addField(new CheckboxField('bottom-left', true));
		}
		
		function execute($image, $request)
		{
			$color = $this->fields['color']->value;
			$radius = $this->fields['radius']->value;
			$smoothness = $this->fields['smoothness']->value;
			
			$corners = 0;
			if ($this->fval('top-left'))
				$corners += \WideImage\WideImage::SIDE_TOP_LEFT;
			
			if ($this->fval('top-right'))
				$corners += \WideImage\WideImage::SIDE_TOP_RIGHT;
			
			if ($this->fval('bottom-right'))
				$corners += \WideImage\WideImage::SIDE_BOTTOM_RIGHT;
			
			if ($this->fval('bottom-left'))
				$corners += \WideImage\WideImage::SIDE_BOTTOM_LEFT;
			
			return $image->roundCorners($radius, $color ? hexdec($color) : null, $smoothness, $corners);
		}
	}
