<?php
	/**
	 * @package Demos
	 */
	class Demo_applyConvolution extends Demo
	{
		public $order = 2025;
		
		protected $base_matrix = array(array(2, 0, 0), array(0, -1, 0), array(0, 0, -1));
		
		function init()
		{
			$this->addField(new Field('matrix', '2 0 0, 0 -1 0, 0 0 -1', '3x3 float matrix; separate rows with a comma, and columns with a space'));
			$this->addField(new FloatField('div', 1));
			$this->addField(new FloatField('offset', 220));
		}
		
		function execute($image, $request)
		{
			$mstr = $this->fval('matrix');
			$rows = explode(',', $mstr);
			
			$matrix = array();
			foreach ($this->base_matrix as $idx => $base_row)
			{
				$build_row = array();
				if (isset($rows[$idx]))
				{
					$row = trim($rows[$idx]);
					$cols = explode(' ', $row);
					for ($c = 0; $c < 3; $c++)
						if (isset($cols[$c]))
							$build_row[] = floatval(trim($cols[$c]));
						else
							$build_row[] = $base_row[$c];
				}
				else
					$build_row = $base_row;
				
				$matrix[] = $build_row;
			}
			
			return $image->applyConvolution($matrix, $this->fval('div'), $this->fval('offset'));
		}
	}
