<?php
	/**
	 * @package Demos
	 */
	class Demo
	{
		public $name;
		public $format = null;
		public $fields = array();
		public $order = 1000;
		
		function __construct($name)
		{
			$this->name = $name;
		}
		
		function init()
		{
		}
		
		static function create($name)
		{
			$file = DEMO_PATH . '/demos/' . $name . '.php';
			if (!file_exists($file))
				throw new Exception("Invalid demo: {$name}");
			include $file;
			$className = 'Demo_' . $name;
			$demo = new $className($name);
			$demo->request = Request::getInstance();
			$demo->init();
			foreach ($demo->fields as $field)
			{
				$field->request = Request::getInstance();
				$field->init(Request::getInstance());
			}
			return $demo;
		}
		
		function getFormat()
		{
			return 'as input';
		}
		
		function addField($field)
		{
			$this->fields[$field->name] = $field;
		}
		
		function __toString()
		{
			return $this->name;
		}
		
		function text()
		{
		}
		
		function fval($name)
		{
			return $this->fields[$name]->value;
		}
	}
	