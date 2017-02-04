<?php
	/**
	 * @package Demos
	 */
	class AngleField extends IntField
	{
		function __construct($name, $default, $hint = 'In degrees clockwise, negative values accepted')
		{
			parent::__construct($name, $default, $hint);
		}
	}
