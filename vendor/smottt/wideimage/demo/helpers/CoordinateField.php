<?php
	/**
	 * @package Demos
	 */
	class CoordinateField extends Field
	{
		function __construct($name, $default, $hint = 'Smart coordinate')
		{
			parent::__construct($name, $default, $hint);
		}
		
		function init($request)
		{
			$this->value = $request->getCoordinate($this->name, $this->default);
			if ($this->value > 1000)
				$this->value = 1000;
		}
	}
