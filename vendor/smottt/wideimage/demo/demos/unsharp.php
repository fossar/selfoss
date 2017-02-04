<?php
	/**
	 * @package Demos
	 */
	class Demo_unsharp extends Demo
	{
		public $order = 1350;

		function init()
		{
			$this->addField(new IntField('amount', 300));
			$this->addField(new IntField('radius', 3));
			$this->addField(new IntField('threshold', 2));
		}
		
		function execute($image, $request)
		{
			return $image->unsharp($this->fields['amount']->value, $this->fields['radius']->value, $this->fields['threshold']->value);
		}
	}
