<?php
	/**
	 * @package Demos
	 */
	class Demo_getChannels extends Demo
	{
		public $order = 500;
		
		protected $channels = array('red', 'green', 'blue', 'alpha');
		
		function init()
		{
			$this->addField(new CheckboxField('red', true));
			$this->addField(new CheckboxField('green', false));
			$this->addField(new CheckboxField('blue', true));
			$this->addField(new CheckboxField('alpha', false));
		}
		
		function execute($img, $request)
		{
			$on = array();
			foreach ($this->channels as $name)
				if ($this->fields[$name]->value)
					$on[] = $name;
			
			return $img->getChannels($on);
		}
	}
