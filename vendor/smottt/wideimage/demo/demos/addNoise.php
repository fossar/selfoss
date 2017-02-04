<?php
	/**
	 * @package Demos
	 */
	class Demo_addNoise extends Demo
	{
		public $order = 9350;

		function init()
		{
			$this->addField(new IntField('amount', 300));
			$this->addField(new SelectField('type', array('salt&pepper','mono','color'), 'mono'));
		}
		
		function execute($image, $request)
		{
			return $image->addNoise($this->fields['amount']->value, $this->fields['type']->value);
		}
	}
