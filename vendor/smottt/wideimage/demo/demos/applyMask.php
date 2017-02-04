<?php
	/**
	 * @package Demos
	 */
	class Demo_applyMask extends Demo
	{
		public $order = 600;
		
		function init()
		{
			$this->addField(new FileSelectField('mask', 'masks'));
			$this->addField(new CoordinateField('left', 10));
			$this->addField(new CoordinateField('top', '30%'));
			
			if (!$this->request->get('mask'))
				$this->request->set('mask', 'mask-circle.gif');
		}
		
		function execute($image)
		{
			$mask = \WideImage\WideImage::load(DEMO_PATH . 'masks/' . $this->fields['mask']->value);
			$left = $this->fields['left']->value;
			$top = $this->fields['top']->value;
			
			return $image->applyMask($mask, $left, $top);
		}
		
		function getFormat()
		{
			return 'png';
		}
	}
