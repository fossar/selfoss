<?php
	/**
	 * @package Demos
	 */
	class FileSelectField extends Field
	{
		public $request;
		public $files = array();
		public $options;
		
		function __construct($name, $path, $options = array())
		{
			$this->name = $name;
			$this->path = $path;
			$this->options = $options;
			
			if (!isset($options['show']))
				$this->options['show'] = true;
			
			if (!isset($options['pattern']))
				$this->options['pattern'] = '/(.*)/';
		}
		
		function init($request)
		{
			$this->value = null;
			$di = new DirectoryIterator(DEMO_PATH . $this->path);
			foreach ($di as $file)
				if (!$file->isDot() && strpos($file->getFilename(), '.') !== 0 && preg_match($this->options['pattern'], $file->getFilename()))
				{
					$this->files[] = $file->getFilename();
					if ($this->value === null && isset($this->options['default']) && $this->options['default'] == $file->getFilename())
						$this->value = $this->options['default'];
					
					if ($this->request->get($this->name) == $file->getFilename())
						$this->value = $file->getFilename();
				}
			
			sort($this->files);
			
			if (!$this->value && count($this->files) > 0)
				$this->value = $this->files[0];
		}
		
		function renderBody($name, $id)
		{
			if ($this->options['show'])
			{
				$onch = "document.getElementById('sel_{$id}').src = '{$this->path}/' + this.options[this.selectedIndex].value;";
			}
			else
				$onch = '';
			
			echo '<select id="' . $id . '" name="' . $name . '" onchange="' . $onch . '">';
			$selected_file = null;
			foreach ($this->files as $file)
			{
				if ($this->value == $file)
				{
					$sel = 'selected="selected"';
					$selected_file = $file;
				}
				else
					$sel = '';
				
				echo '<option ' . $sel . ' value="' . $file . '">' . $file . '</option>' . PHP_EOL;
			}
			echo '</select>';
			
			if ($this->options['show'] && $selected_file)
			{
				echo '<div style="display: inline; min-width: 50px; min-height: 50px">';
				echo '<img style="position: absolute" id="sel_' . $id . '" width="50" src="' . $this->path . '/' . $selected_file . '" /> ';
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				echo '</div>';
			}
		}
		
		function getURLValue()
		{
			return $this->name . '=' . $this->value;
		}
	}
