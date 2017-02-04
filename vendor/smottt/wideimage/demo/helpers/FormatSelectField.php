<?php
	/**
	 * @package Demos
	 */
	class FormatSelectField extends SelectField
	{
		function __construct($name)
		{
			parent::__construct($name, array('preset for demo', 'as input', 'png8', 'png24', 'jpeg', 'gif', 'bmp'), null, 'Image format');
		}
	}
