<?php
	/**
	 * @package Demos
	 */
	class Demo_getCanvas extends Demo
	{
		public $order = 1300;
		
		function init()
		{
			$this->addField(new Field('text', 'Hello world!'));
			$this->addField(new CoordinateField('x', 'middle'));
			$this->addField(new CoordinateField('y', 'bottom-5'));
			$this->addField(new IntField('angle', 5));
			$this->addField(new FileSelectField('font', 'fonts', array('show' => false, 'pattern' => '/(.*)\.ttf$/', 'default' => 'VeraSe.ttf')));
			$this->addField(new IntField('size', 18));
		}
		
		function execute($image, $request)
		{
			$text = $this->fields['text']->value;
			$x = $this->fields['x']->value;
			$y = $this->fields['y']->value;
			$angle = $this->fields['angle']->value;
			$font = $this->fields['font']->value;
			$font_size = $this->fields['size']->value;
			
			$canvas = $image->getCanvas();
			
			$canvas->filledRectangle(10, 10, 80, 40, $image->allocateColor(255, 127, 255));
			$canvas->line(60, 80, 30, 100, $image->allocateColor(255, 0, 0));
			
			$font_file = DEMO_PATH . 'fonts/' . $font;
			
			$canvas->useFont($font_file, $font_size, $image->allocateColor(0, 0, 0));
			$canvas->writeText("$x+1", "$y+1", $text, $angle);
			
			$canvas->useFont($font_file, $font_size, $image->allocateColor(200, 220, 255));
			$canvas->writeText($x, $y, $text, $angle);
			
			return $image;
		}
		
		function et($name)
		{
			return htmlentities($this->fval($name));
		}
		
		function text()
		{
			echo "This demo executes:
<pre>
	\$canvas->filledRectangle(10, 10, 80, 40, \$img->allocateColor(255, 127, 255));
	\$canvas->line(60, 80, 30, 100, \$img->allocateColor(255, 0, 0));
	
	\$canvas->useFont('{$this->et('font')}', '{$this->et('size')}', \$image->allocateColor(0, 0, 0));
	\$canvas->writeText('{$this->et('x')}+1', '{$this->et('y')}+1', '{$this->et('text')}', {$this->et('angle')});
	
	\$canvas->useFont('{$this->et('font')}', '{$this->et('size')}', \$image->allocateColor(200, 220, 255));
	\$canvas->writeText('{$this->et('x')}', '{$this->et('y')}', '{$this->et('text')}', {$this->et('angle')});
	</pre>";
		}
	}
