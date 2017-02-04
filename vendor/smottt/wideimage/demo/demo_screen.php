<?php
	/**
	 * @package Demos
	 */
?>
<form style="font-family: Verdana, Tahoma; font-size: 11px">
	<input type="hidden" name="demo" value="<?php echo $activeDemo->name; ?>" />
	<div style="background-color: #f0f0f0; padding: 5px;">
		<input type="submit" value="refresh" />
<?php
	foreach ($activeDemo->fields as $field)
		$field->render();
?>
		<br />
		<span style="font-family: Verdana, Tahoma; font-size: 11px">
			Read the <a href="../doc/WideImage/WideImage_Image.html#method<?php echo $activeDemo->name; ?>">API documentation</a> for this operation.
		</span>
	
	<div style="background-color: #d0d0d0; padding: 5px; text-align: right; float: right; width: 300px;">
<?php
	$top_form['output']->render();
	echo "<br />\n";
	echo ' Palette options (only for <em>png8</em> and <em>gif</em> output):<br />';
	$top_form['ncolors']->render();
	echo "<br />\n";
	$top_form['dither']->render();
	$top_form['match_palette']->render();
?>
	</div>
	</div>
</form>

<?php
	$activeDemo->text();
?>


<?php
	$images_in_row = 2;
	$in_row = 0;
	$images = array();
	$di = new DirectoryIterator(dirname(__FILE__) . '/images/');
	foreach ($di as $file)
		if (!$file->isDot() && strpos($file->getFilename(), '.') !== 0)
			$images[] = $file->getFilename();
	
	asort($images);
	foreach ($images as $image_file)
		{
			echo '<div class="images">';
			echo '<img src="images/' . $image_file . '" />';
			$img_url = 'image.php?image=' . $image_file . '&output=' . $top_form['output']->value . 
				'&colors=' . $top_form['ncolors']->value . '&dither=' . $top_form['dither']->value . 
				'&match_palette=' . $top_form['match_palette']->value . '&demo=' . $activeDemo->name;
			foreach ($activeDemo->fields as $field)
				$img_url .= '&' . $field->getURLValue();
			
			echo '&nbsp;';
			echo '<a href="' . $img_url . '">';
			echo '<img src="' . $img_url . '" />';
			echo '</a>';
			echo "</div>\n";
		}
?>
<div style="clear: both"></div>

