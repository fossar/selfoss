<?php
	/**
	 * @package Demos
	 */
	
	require_once dirname(__FILE__) . '/helpers/common.php';
	
	$request = Request::getInstance();
	$demo = Demo::create($request->get('demo'));
	$image = \WideImage\WideImage::load('images/' . $request->get('image'));
	
	$result = $demo->execute($image, $request);
	
	$output = new FormatSelectField('output');
	$output->init(Request::getInstance());
	
	if ($output->value == 'preset for demo')
		$format = $demo->getFormat();
	else
		$format = $output->value;
	
	if ($format === 'as input')
		$format = substr($request->get('image'), -3);
	
	$output = 24;
	if ($format == 'png8')
	{
		$output = 8;
		$format = 'png';
	}
	elseif ($format == 'png24')
		$format = 'png';
	elseif ($format == 'gif')
		$output = 8;
	
	if ($output == 8)
	{
		$ncolors = new IntField('colors', 255);
		$ncolors->init(Request::getInstance());
		
		$dither = new CheckboxField('dither', true);
		$dither->init(Request::getInstance());
		
		$match_palette = new CheckboxField('match_palette', true);
		$match_palette->init(Request::getInstance());
		
		$result = $result->asPalette($ncolors->value, $dither->value, $match_palette->value);
	}
	
	$result->output($format);
	