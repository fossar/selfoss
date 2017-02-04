<?php
	/**
	 * @package Demos
	 */
	class FloatField extends Field
	{
		function __construct($name, $default, $hint = 'Float')
		{
			parent::__construct($name, $default, $hint);
		}
		
		function init($request)
		{
			$this->value = $request->getFloat($this->name, $this->default);
		}
	}
