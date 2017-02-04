<?php
	/**
	 * @package Demos
	 */
	class IntField extends Field
	{
		function __construct($name, $default, $hint = 'Integer')
		{
			parent::__construct($name, $default, $hint);
		}
		
		function init($request)
		{
			$this->value = $request->getInt($this->name, $this->default);
		}
	}
