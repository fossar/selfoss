<?php
	/**
	 * @package Demos
	 */
	class CheckboxField extends Field
	{
		function init($request)
		{
			$this->value = $request->get($this->name, $this->default ? '1' : null) === '1';
		}
		
		function renderBody($name, $id)
		{
			if ($this->value)
				$chk = 'checked="checked"';
			else
				$chk = '';
			
			echo '<input type="hidden" name="' . $name . '" id="' . $id . '_val" value="' . ($this->value ? '1' : '') . '" />';
			echo '<input type="checkbox" ' . $chk . ' name="' . $name . '_cb" id="' . $id . '" value="1" onclick="document.getElementById(\'' . $id . '_val\').value = Number(this.checked);" />';
		}
	}
