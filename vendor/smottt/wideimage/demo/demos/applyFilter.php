<?php
	/**
	 * @package Demos
	 */
	class Demo_applyFilter extends Demo
	{
		public $order = 2000;

		function init()
		{
			$this->addField(new SelectField('filter', array(
					'IMG_FILTER_NEGATE', 
					'IMG_FILTER_GRAYSCALE', 
					'IMG_FILTER_BRIGHTNESS', 
					'IMG_FILTER_CONTRAST',
					'IMG_FILTER_COLORIZE',
					'IMG_FILTER_EDGEDETECT',
					'IMG_FILTER_EMBOSS',
					'IMG_FILTER_GAUSSIAN_BLUR',
					'IMG_FILTER_SELECTIVE_BLUR',
					'IMG_FILTER_MEAN_REMOVAL',
					'IMG_FILTER_SMOOTH'
					))
				);
			$this->addField(new IntField('arg1', null));
			$this->addField(new IntField('arg2', null));
			$this->addField(new IntField('arg3', null));
		}
		
		function execute($image)
		{
			$filter = constant($this->fields['filter']->value);
			$arg1 = $this->fields['arg1']->value;
			$arg2 = $this->fields['arg2']->value;
			$arg3 = $this->fields['arg3']->value;
			
			return $image->applyFilter($filter, $arg1, $arg2, $arg3);
		}
	}
