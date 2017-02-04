<?php
include 'helpers/common.php';

$img = \WideImage\WideImage::createTrueColorImage(400, 200);
$canvas = $img->getCanvas();
$canvas->useFont('fonts/Vera.ttf', 36, $img->allocateColor(255, 0, 0));
$canvas->writeText('left', 'top', 'abc', 0);
$canvas->writeText('right', 'top', 'def', 15);
$canvas->writeText('right', 'bottom', 'ghi', 30);
$canvas->writeText('left', 'bottom', 'jkl', 45);
$canvas->writeText('center', 'center', 'mno', 60);
$img->output('png');
exit;

// Create a 300x150 image
$im = imagecreatetruecolor(600, 350);
$black = imagecolorallocate($im, 0, 0, 0);
$bgcolor = imagecolorallocate($im, 255, 255, 0);

// Set the background to be white
imagefilledrectangle($im, 0, 0, imagesx($im), imagesy($im), $bgcolor);

// Path to our font file
$font = './fonts/Vera.ttf';

$angle = 340;
$font_size = 20;
$text = 'jW| asdkasdlk alk,.,wedwer|w[r=?';
$text = '#j';

// First we create our bounding box
$bbox = imageftbbox($font_size, $angle, $font, $text);


function normalize_bbox($bbox)
{
	return array(
			'up-left' => array('x' => $bbox[6], 'y' => $bbox[7]),
			'up-right' => array('x' => $bbox[4], 'y' => $bbox[5]),
			'down-left' => array('x' => $bbox[0], 'y' => $bbox[1]),
			'down-right' => array('x' => $bbox[2], 'y' => $bbox[3]),
		);
}

function outer_box($box)
{
	return array(
		'left' => min($box['up-left']['x'], $box['up-right']['x'], $box['down-left']['x'], $box['down-right']['x']),
		'top' => min($box['up-left']['y'], $box['up-right']['y'], $box['down-left']['y'], $box['down-right']['y']),
		'right' => max($box['up-left']['x'], $box['up-right']['x'], $box['down-left']['x'], $box['down-right']['x']),
		'bottom' => max($box['up-left']['y'], $box['up-right']['y'], $box['down-left']['y'], $box['down-right']['y'])
	);
}

$box = normalize_bbox($bbox);

// This is our cordinates for X and Y
#$x = $bbox[0] + (imagesx($im) / 2) - ($bbox[4] / 2) - 5;
#$y = $bbox[1] + (imagesy($im) / 2) - ($bbox[5] / 2) - 5;
#$x = 300;
#$y = 175;

$obox = outer_box(normalize_bbox(imageftbbox($font_size, $angle, $font, '')));
$obox = outer_box(normalize_bbox(imageftbbox($font_size, $angle, $font, $text)));

#$x = imagesx($im) - $obox['right'] - 1;
#$y = imagesy($im) - $obox['bottom'] - 1;
$x = 0;
$y = 0;

$gc = imagecolorallocate($im, 255, 200, 200);
imageline($im, imagesx($im) / 2, 0, imagesx($im) / 2, imagesy($im), $gc);
imageline($im, 0, imagesy($im) / 2, imagesx($im), imagesy($im) / 2, $gc);


imagefttext($im, $font_size, $angle, $x, $y, $black, $font, $text);
#imagefttext($im, $font_size, $angle, $x, $y, $black, $font, 'aj');

$c = imagecolorallocate($im, 0, 255, 0);
imageline($im, $box['up-left']['x'] + $x, $box['up-left']['y'] + $y, $box['up-right']['x'] + $x, $box['up-right']['y'] + $y, $c);
imageline($im, $box['up-right']['x'] + $x, $box['up-right']['y'] + $y, $box['down-right']['x'] + $x, $box['down-right']['y'] + $y, $c);
imageline($im, $box['down-right']['x'] + $x, $box['down-right']['y'] + $y, $box['down-left']['x'] + $x, $box['down-left']['y'] + $y, $c);
imageline($im, $box['down-left']['x'] + $x, $box['down-left']['y'] + $y, $box['up-left']['x'] + $x, $box['up-left']['y'] + $y, $c);

$c = imagecolorallocate($im, 0, 127, 255);
$obox = outer_box($box);
imageline($im, $obox['left'] + $x, $obox['top'] + $y, $obox['right'] + $x, $obox['top'] + $y, $c);
imageline($im, $obox['right'] + $x, $obox['top'] + $y, $obox['right'] + $x, $obox['bottom'] + $y, $c);
imageline($im, $obox['right'] + $x, $obox['bottom'] + $y, $obox['left'] + $x, $obox['bottom'] + $y, $c);
imageline($im, $obox['left'] + $x, $obox['bottom'] + $y, $obox['left'] + $x, $obox['top'] + $y, $c);

imagefilledellipse($im, $x, $y, 3, 3, imagecolorallocate($im, 255, 0, 0));


// Output to browser
header('Content-type: image/png');

imagepng($im);
imagedestroy($im);
