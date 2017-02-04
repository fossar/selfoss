<?php
	/**
    This file is part of WideImage.

    WideImage is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 2.1 of the License, or
    (at your option) any later version.

    WideImage is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with WideImage; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

	* @package Demos
  **/

	include 'helpers/common.php';

	$demos = array();
	$di = new DirectoryIterator(dirname(__FILE__) . '/demos/');
	foreach ($di as $file)
		if (substr($file->getFilename(), -4) == '.php')
			$demos[] = Demo::create(substr($file->getFilename(), 0, -4));

	usort($demos, 'cmp_demos');

	function cmp_demos($d1, $d2)
	{
		if ($d1->order === $d2->order)
			return 0;

		return ($d1->order < $d2->order ? -1 : 1);
	}

	if (isset($_GET['demo']))
		$activeDemoName = $_GET['demo'];
	else
		$activeDemoName = null;

	$activeDemo = null;
	foreach ($demos as $demo)
		if ($demo->name == $activeDemoName)
		{
			$activeDemo = $demo;
			break;
		}

	if (!$activeDemo)
		$activeDemoName = null;

?>
<html>
	<head>
			<title>WideImage -<?php if ($activeDemo) echo " " . $activeDemo->name; ?> demo</title>

<style>
	body
	{
		background-color: #f0f0f0;
		background-image: url("bg.gif");
		background-attachment: fixed;
	}

	div.images {
		float: left;
		margin: 20px;
	}

	div.images img {
		border-width: 0px;
		vertical-align: middle;
	}

	div.images a img
	{
		border: 1px dashed red;
	}

	td { border-bottom: 1px solid grey; padding: 10px; }
	td.left { text-align: left }
	td.right { text-align: right }

	.navbar {
		width: 150px;
		background-color: white;
		float: left;
	}

	.navbar a
	{
		color: black;
	}

	.navbar a:visited
	{
		color: gray;
	}

	a.active_demo {
		color: green;
	}

	a.active_demo:visited {
		color: green;
	}
</style>

	</head>

	<body>
		<div class="navbar">
			<strong><a href="index.php">WideImage demos</a></strong>

			<ul>
<?php
	$top_form = array();

	$top_form['output'] = new FormatSelectField('output');
	$top_form['output']->init(Request::getInstance());

	$top_form['ncolors'] = new IntField('colors', 255);
	$top_form['ncolors']->init(Request::getInstance());

	$top_form['dither'] = new CheckboxField('dither', true);
	$top_form['dither']->init(Request::getInstance());
	$top_form['match_palette'] = new CheckboxField('match_palette', true);
	$top_form['match_palette']->init(Request::getInstance());

	foreach ($demos as $demo)
	{
		if ($activeDemo !== null && $demo->name == $activeDemo->name)
			$css = 'active_demo';
		else
			$css = '';

		echo "<li><a class=\"$css\" href=\"?demo={$demo->name}&output={$top_form['output']->value}&colors={$top_form['ncolors']->value}&dither={$top_form['dither']->value}&match_palette={$top_form['match_palette']->value}\">{$demo->name}</a></li>\n";
	}
?>
			</ul>

			<span style="font-family: Verdana, Tahoma; font-size: 11px">
			This demo is primarily intended to easily try some of the features.
			There may be some bugs that don't actually occur with WideImage if used properly.
			<br />
			<br />

			Version: <?php echo \WideImage\WideImage::version(); ?>

			<br />
			<br />
			&copy; 2007-2016
			<br />
			<a href="http://kozak.si/widethoughts/">Gasper Kozak</a><br />
			<br />
			Read more about WideImage on the
			<a href="http://wideimage.sourceforge.net">project page</a>.
			</span>
		</div>
		<div style="margin-left: 200px">
<?php
	if ($activeDemo)
	{
		include 'demo_screen.php';
	}
?>
		</div>
	</body>
</html>
