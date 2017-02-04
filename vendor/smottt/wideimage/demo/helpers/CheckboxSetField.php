<?php
	/**
	 * @package Demos
	 */
	class CheckboxSetField extends Field
	{
		public $options;
		public $request;
		
		function __construct($name, $options)
		{
			$this->name = $name;
			$this->options = $options;
		}
		
		function init($request)
		{
			$this->value = array();
			if (is_array($request->get($this->name)))
				foreach ($request->get($this->name) as $val)
					if (in_array($val, $this->options))
						$this->value[] = $val;
		}
		
		function render()
		{
			$request = $this->request;
			foreach ($this->options as $option)
			{
				if (is_array($request->get($this->name)) && in_array($option, $request->get($this->name)))
					$chk = 'checked="checked"';
				else
					$chk = '';

				$name = $this->name . '[]';
				$id = $this->name . '_' . $option;
				echo '<input type="checkbox" ' . $chk . ' name="' . $name . '" id="' . $id . '" value="' . $option . '" />';
				echo '<label for="' . $id . '">' . $option . '</label> ';
			}
		}
		
		function getURLValue()
		{
			$v = '';
			foreach ($this->value as $value)
				$v .= $this->name . '[]=' . $value . '&';
			return $v;
		}
	}
	