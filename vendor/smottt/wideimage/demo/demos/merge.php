<?php
	/**
	 * @package Demos
	 */
	class Demo_merge extends Demo
	{
		public $order = 800;
		
		function init()
		{
			$this->addField(new FileSelectField('overlay', 'images', array('default' => '6-logo.gif')));
			$this->addField(new CoordinateField('left', 'right-10'));
			$this->addField(new CoordinateField('top', 'bottom-15%'));
			$this->addField(new IntField('opacity', 50));
		}
		
		function execute($image, $request)
		{
			$overlay = \WideImage\WideImage::load(DEMO_PATH . 'images/' . $this->fields['overlay']->value);
			$left = $this->fields['left']->value;
			$top = $this->fields['top']->value;
			$opacity = $this->fields['opacity']->value;
			
			return $image->merge($overlay, $left, $top, $opacity);
		}
		
		function text()
		{
			echo "For alpha images, set opacity=100, otherwise alpha channel won't work.";
		}
	}
