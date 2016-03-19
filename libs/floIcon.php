<?php
/***************************************************************************
 *  Original floIcon copyright (C) 2007 by Joshua Hatfield.                *
 *                                                                         *
 *  In order to use any part of this floIcon Class, you must comply with   *
 *  the license in 'license.doc'.  In particular, you may not remove this  *
 *  copyright notice.                                                      *
 *                                                                         *
 *  Much time and thought has gone into this software and you are          *
 *  benefitting.  We hope that you share your changes too.  What goes      *
 *  around, comes around.                                                  *
 ***************************************************************************

Version 1.1.1:
Date: 2009-03-16

Changes:
I was a little hasty on that last update.  A couple new bugs from 1.1.0 have
been fixed.  

Version 1.1.0:
Date: 2009-03-16

Changes:
Added Vista support.  
Fixed a number of minor bugs.  Many thanks to Dvir Berebi for pointing
	them out.

Version 1.0.5:
Date: 2009-03-15

Changes:
Fixed bug when operating on low bit count images (1 or 4) with odd dimensions.

Version 1.0.4:
Date: 2007-05-25

Changes:
Made function not break quite so bad when reading a PNG file on a Vista icon.
	Now, you shouldn't be loading Vista icons anyways, but since I'm trying to
	upgrade to Vista compatible and I need a comparison, I've got to.

Version 1.0.3:
Date: 2007-05-25

Changes:
Okay, this one was just stupid.  When adding large image support, I messed up
	and when reading, it doubled the image size.  Now, it's fixed.
I took the opportunity to also add a dummy AND map for 32 images on those
	readers who might be looking for it (though it's not supposed to be used.)

Version 1.0.2:
Date: 2007-05-24

Sorry about two versions so quickly back to back, but something needed to be
done with speed...things were getting too slow.  I'm sure you'll be okay.

Changes:
Told palette determination to stop at 257 colors or is 32 bit because the
	palette is not used at that point and gets really slow when it starts
	getting into the high numbers, for instance, in photographs or gradient
	truecolor images with lots of unique colors.
After experimenting, it appears that Windows XP does in fact support 256x256
	images and larger by setting the entry value to 0 in the 1 byte that holds
	that value and storing the true dimentions in the image header later.  Of
	course, it still doesn't use these images in normal operation.  XP will
	resize these and use them if no other images are available.
Wrapped main documentation (this) to the 80th column for easier reading.

Version 1.0.1:
Date: 2007-05-23

Thank you everyone for actively using the implementation on my site and
illuminating me very quickly to a number of glaring bugs in this class.

Changes:
Added version history.
Fixed bug with non-standard sizes in AND map reading AND writing.
Fixed bug with palette images using non-black color in backgrounds.
Fixed bug with height/width reversal reading files.


Version 1.0.0:
Date: 2007-05-17
Original release date.


Foreword:
If you are simply in the effort of making an ICO file, may I recommend visiting
my site, http://www.flobi.com/ , and clicking on floIcon.  I have a fully
functional implementation (on which the sample.php is based) where you can also
see recent icons submitted by other visitors.  No registration required, no
intrusive ads.  (As of this writing, there aren't actually any ads at all, I
might add google ads at some point.)

If you are trying to get an idea of how ICO files, work, may I recommend the
page I used, http://www.daubnet.com/formats/ICO.html .  It does not fully cover
icon files, but it does a very good job of what it does.  Any additional
information, I will try to post at
http://www.flobi.com/test/floIcon/more_on_icons.php for your convenience.

If you are trying to get an idea of how image resource files work, I recommend
ANY other class that deals with images.  This class essentially plots points on
the image, and that's not perticularly advanced.

For any purpose, I wish you luck and feel free to contact me with any bugs,
comments, questions, etc.  - Flobi

Summary:
This class parses ICO files.  It reads directly from the ICO file, headers
first, so that if you are only retrieving 1 image, the entire ICO file need not
be parsed.  It supports merging ICO files.  It supports adding PHP image
resources as new images to the file.  It has an export capability that can
easily be written to a new (or the same) ICO file for saving ICO files.  All
sizes from 1x1 to 255x255 pixels and 1, 4, 8, 24 (plus transparency) and 32 bit
images are supported.  Image retrieval by size is supported.

Included is a fully functional sample that allows users to upload ICO, GIF,
JPEG and PNG files into a session icon file as well as remove images from the
file and download the completed file (sample.php).

Known Limitations: Does not support Vista icons.  Does not support inversion
palette method (because of the limitations of the PHP image resource).

Addendum on Limitations:
Windows Vista has added support for 256x256 size icons and now stores files as
PNG's.  This class is for older ICO files.  A new class is currently under
development that supports the new Windows Vista format.

Palette inversion (my name for this technique) is the technique of using a black
pixel (0, 0, 0) with a 1 "AND" pixel.  White pixels with a 1 "AND" show
transparent (or "don't" show).  Black pixels with a 1 "AND" show inverted
(sometimes).  Because this method isn't uniformly supported or documented and
the PHP image resource doesn't support it, I have decided to not as well.  This
does not apply to 32 bit images which include alpha transparency and no AND map.

Though other functions exist, these are the only ones I believe offer usefulness
to be public.
floIcon public functions:
	readICO($file, $offset = 0)
		Loads the icon from the specified filepath ($file) starting at the
		specified file offset ($offset).  This function MERGES the loaded icon
		images into the floIcon object.

	formatICO($offset = 0)
		Returns the current floIcon object formatted as an .ICO file with the
		file offset altered by $offset.  If there are too many or too large
		images, causing any images saved past the 4,294,967,296th byte, this
		will return false.  (This is well outside PHP's default memory
		allocation.)

	addImage($imageResource, $desiredBitCount = 1, $pngIfWidthExceeds = 48)
		Adds an icon image to the icon file based on the passed image resource
		($imageResource).  It will automatically determine the bit count, but
		can be persuaded to increase that to $desiredBitCount if that value is
		greater than the determined bit count.

		NOTE: The image resource is saved by REFERRENCE.  So, if you edit it
		then call getImage, the resource returned will be the same, editions and
		all.  Destruction of the resource will cause a new resource to be
		created in getImage().

	getImage($offset)
		Returns the php image resource either assigned by addImage or created
		dynamically at calltime by the data loaded with readICO().  The offset
		specified here ($offset) is the array offset starting at 0 and ending
		at countImages().

	getBestImage($height = 32, $width = 32)
		Returns the php images resource of the highest quality image closest to
		the size specified.  This could be useful when you only want to display
		the icon in an icon list with a single representative icon.  A resized
		copy of the highest quality available image will be returned if there is
		no 32 or 24 bit icon present at the speficied dimentions.

	sortImagesBySize()
		Sorts the $this->images array by order of size, smallest to largest.
		This is the optimal sorting for icon files.

	countImages()
		Returns a count of how many images are present in the current floIcon
		object.

floIcon public variables:
	$images
		Contains a numerically indexed array of floIconImage objects.
	$updated
		True if an image has been added since load or last formatICO, otherwise
		false.

floIconImage public functions:
	getHeader()
		Returns an associative array containing the information from the ICO
		image header.

	getEntry()
		Returns an associative array containing the information from the ICO
		entry header.

		NOTE: If this icon image was created by php image resource, this may not
		have accurate information until saving from floIcon with the formatICO()
		function.  Specifically, offset information will be inaccurate.

	getImageResource()
		Returns a php image resource.  Same as floIcon's getImage() function.

	setImageResource($imageResource, $desiredBitCount = 1, $pngIfWidthExceeds = 48)
		Changes this icon image based on the passed image resource
		($imageResource). It will automatically determine the bit count, but can
		be persuaded to increase that to $desiredBitCount if that value is
		greater than the determined bit count.

		NOTE: The image resource is saved by REFERRENCE.  So, if you edit it
		then call getImageResource, the resource returned will be the same,
		editions and all.  Destruction of the resource will cause a new resource
		to be created in getImageResource().

	dealocateResource()
		This destroys the image resource variable, freeing up memory.  The image
		will automatically be recreated	when getImageResource is executed.
*/
class floIcon {
	/*
	 * $images is an associative array of offset integer => floIconImage object
	 */
	var $images; // Array of floIconImage objects.
	var $updated = false;
	function __construct() {
		$this->images = array();
	}
	function countImages() {
		return count($this->images);
	}
	function getBestImage($height = 32, $width = 32) {
		$best = false;
		$bestEntry = array();
		$secondBest = false;
		$secondBestEntry = array();
		foreach ($this->images as $key => $image) {
			$entry = $image->getEntry();
			$header = $image->getHeader();
			if (!@$entry["BitCount"]) {
				$entry["BitCount"] = $header["BitCount"];
			}
			if ($entry["Height"] == $height && $entry["Width"] == $width && $entry["BitCount"] == 32) {
				return $image->getImageResource();
			} elseif ($entry["Height"] == $height && $entry["Width"] == $width && $entry["BitCount"] > min(4, @$bestEntry["BitCount"])) {
				$best = $image;
				$bestEntry = $entry;
			} elseif (
				!$secondBest or
				$entry["Height"] >= $secondBestEntry["Height"] &&
				$entry["Width"] >= $secondBestEntry["Width"] &&
				$secondBestEntry["BitCount"] >= $secondBestEntry["BitCount"] and
				(
					$entry["Height"] <= 64 && $entry["Height"] > $secondBestEntry["Height"] and
					$entry["Height"] > 64 && $entry["Height"] < $secondBestEntry["Height"]
				) ||
				(
					$entry["Width"] <= 64 && $entry["Width"] > $secondBestEntry["Width"] and
					$entry["Width"] > 64 && $entry["Width"] < $secondBestEntry["Width"]
				) ||
				$secondBestEntry["BitCount"] > $secondBestEntry["BitCount"]
				) {
				$secondBest = $image;
				$secondBestEntry = $entry;
			}
		}
		if ($best) {
			return $best->getImageResource();
		} elseif ($secondBest) {
			if ($secondBestEntry["Width"] != $width || $secondBestEntry["Height"] != $height) {
				$imageResource = $secondBest->getImageResource();
				$newImageResource = imagecreatetruecolor($width, $height);
				imagesavealpha($newImageResource, true);
				imagealphablending($newImageResource, false);
				imagecopyresampled($newImageResource, $imageResource, 0, 0, 0, 0, $width, $height, $secondBestEntry["Width"], $secondBestEntry["Height"]);
				$this->addImage($newImageResource, 32);
				return $newImageResource;
			} else {
				return $secondBest->getImageResource();
			}
		}
	}
	/*
	 * readICO merges the icon images from the file to the current list
	 */
	function readICO($file, $offset = 0) {
		if (file_exists($file) && filesize($file) > 0 && $filePointer = fopen($file, "r")) {
			fseek($filePointer, $offset);
			$header = unpack("vReserved/vType/vCount", fread($filePointer, 6));
			for ($t = 0; $t < $header["Count"]; $t++) {
				$newImage = new floIconImage();
				$newImage->readImageFromICO($filePointer, 6 + ($t * 16));
				$this->images[] = $newImage;
			}
			fclose($filePointer);
		}
	}
	function sortImagesBySize() {
		usort($this->images, array("floIcon", "_cmpObj"));
	}
	function formatICO($offset = 0) {
		$this->updated = false;
		$output = "";
		$output .= pack("SSS", 0, 1, count($this->images));
		$output_images = "";
		foreach ($this->images as $image) {
			$newImageOffset = $offset + // Whatever offset we've been given.
				6 // Header.
				+ (count($this->images) * 16) // Entries.
				+ strlen($output_images);
			if ($newImageOffset > pow(256, 4) /* 4 bytes available for position */ ) {
				return false;
			}
			$output .= $image->formatEntryForIco($newImageOffset); // The images already in there.
			$output_images .= $image->formatImageForIco();
		}
		return $output.$output_images;
	}
	function _cmpObj($a, $b) {
		$aSize = $a->getSize();
		$bSize = $b->getSize();
		if ($aSize == $bSize) {
			return 0;
		}
		return ($aSize > $bSize)?1:-1;
	}

	function addImage($imageResource, $desiredBitCount = 1, $pngIfWidthExceeds = 48) {
		$this->updated = true;
		$newImage = new floIconImage();
		$newImage->setImageResource($imageResource, $desiredBitCount, $pngIfWidthExceeds);
		$this->images[] = $newImage;
	}
	function getImage($offset) {
		if (isset($this->images[$offset])) {
			return $this->images[$offset]->getImageResource();
		} else {
			return false;
		}
	}
	/*
	 * getSize computes the
	 */
	function getSize() {
		// Compute headers.
		$computedSize = 6; // Always 6 bytes.
		// Add image entry headers
		$computedSize += count($this->images) * 16; // Entry headers are always 16 bytes.
		foreach ($this->images as $image) {
			$computedSize += $image->getSize() + $image->getHeaderSize(); // getSize does not include the header.
		}
	}
}
class floIconImage {
	var $_imageResource = null;
	var $_entry = "";
	var $_entryIconFormat = "";
	var $_header = "";
	var $_headerIconFormat = "";
	var $_imageIconFormat = ""; // Includes palette and mask.
	function formatEntryForIco($offset) {
		// Format the entry, this has to be done here because we need the offset to get the full information.
		$this->_entry["FileOffset"] = $offset;
		$this->_entryIconFormat = pack("CCCCSSLL",
			$this->_entry["Width"]>=256?0:$this->_entry["Width"],
			$this->_entry["Height"]>=256?0:$this->_entry["Height"],
			$this->_entry["ColorCount"],
			$this->_entry["Reserved"],
			$this->_entry["Planes"],
			$this->_entry["BitCount"],
			$this->_entry["SizeInBytes"],
			$this->_entry["FileOffset"]
		);
		return $this->_entryIconFormat;
	}
	function formatImageForIco() {
		// Format the entry, this has to be done here because we need the offset to get the full information.
		return ($this->_headerIconFormat.$this->_imageIconFormat);
	}

	// Will move $bitCount UP to $desiredBitCount if $bitCount is found to be less than it.
	function setImageResource($imageResource, $desiredBitCount = 1, $pngIfWidthExceeds = 48) {
		imagesavealpha($imageResource, true);
		imagealphablending($imageResource, false);
		$height = imagesy($imageResource);
		$width = imagesx($imageResource);
		
		// Parse resource to determine header and icon format

		// Find Palette information
		$is_32bit = false; // Start with an assumption and get proven wrong.
		$hasTransparency = 0;
		$blackColor = false;
		$bitCount = 0;
		$realPalette = array();
		$realIndexPalette = array();
		for ($x = 0; $x < $width && !$is_32bit; $x++) {
			for ($y = 0; $y < $height && !$is_32bit; $y++) {
				$colorIndex = imagecolorat($imageResource, $x, $y);
				$color = imagecolorsforindex($imageResource, $colorIndex);
				if ($color["alpha"] == 0) {
					// No point continuing if there's more than 256 colors or it's 32bit.
					if (count($realPalette) < 257 && !$is_32bit) {
						$inRealPalette = false;
						foreach($realPalette as $realPaletteKey => $realPaletteColor) {
							if (
								$color["red"] == $realPaletteColor["red"] and
								$color["green"] == $realPaletteColor["green"] and
								$color["blue"] == $realPaletteColor["blue"]
							) {
								$inRealPalette = $realPaletteKey;
								break;
							}
						}
						if ($inRealPalette === false) {
							$realIndexPalette[$colorIndex] = count($realPalette);
							if (
								$blackColor === false and
								$color["red"] == 0 and
								$color["green"] == 0 and
								$color["blue"] == 0
							) {
								$blackColor = count($realPalette);
							}
							$realPalette[] = $color;
						} else {
							$realIndexPalette[$colorIndex] = $inRealPalette;
						}
					}
				} else {
					$hasTransparency = 1;
				}
				if ($color["alpha"] != 0 && $color["alpha"] != 127) {
					$is_32bit = true;
				}
			}
		}
		if ($is_32bit) {
			$colorCount = 0;
			$bitCount = 32;
		} else {
			if ($hasTransparency && $blackColor === false) {
				// We need a black color to facilitate transparency.  Unfortunately, this can
				// increase the palette size by 1 if there's no other black color.
				$blackColor = count($realPalette);
				$color = array(
					"red" => 0,
					"blue" => 0,
					"green" => 0,
					"alpha" => 0
				);
				$realPalette[] = $color;
			}
			$colorCount = count($realPalette);
			if ($colorCount > 256 || $colorCount == 0) {
				$bitCount = 24;
			} elseif ($colorCount > 16) {
				$bitCount = 8;
				// 8 bit
			} elseif ($colorCount > 2) {
				$bitCount = 4;
				// 4 bit
			} else {
				$bitCount = 1;
				// 1 bit
			}
			if ($desiredBitCount > $bitCount) {
				$bitCount = $desiredBitCount;
			}
			switch ($bitCount) {
				case 24:
					$colorCount = 0;
					break;
				case 8:
					$colorCount = 256;
					break;
				case 4:
					$colorCount = 16;
					break;
				case 1:
					$colorCount = 2;
					break;
			}
		}
		// Create $this->_imageIconFormat...
		$this->_imageIconFormat = "";
		if ($bitCount < 24) {
			$iconPalette = array();
			// Save Palette
			foreach ($realIndexPalette as $colorIndex => $paletteIndex) {
				$color = $realPalette[$paletteIndex];
				$this->_imageIconFormat .= pack("CCCC", $color["blue"], $color["green"], $color["red"], 0);
			}
			while (strlen($this->_imageIconFormat) < $colorCount * 4) {
				$this->_imageIconFormat .= pack("CCCC", 0, 0, 0, 0);
			}
			// Save Each Pixel as Palette Entry
			$byte = 0; // For $bitCount < 8 math
			$bitPosition = 0; // For $bitCount < 8 math
			for ($y = 0; $y < $height; $y++) {
				for ($x = 0; $x < $width; $x++) {
					$color = imagecolorat($imageResource, $x, $height-$y-1);
					if (isset($realIndexPalette[$color])) {
						$color = $realIndexPalette[$color];
					} else {
						$color = $blackColor;
					}

					if ($bitCount < 8) {
						$bitPosition += $bitCount;
						$colorAdjusted = $color * pow(2, 8 - $bitPosition);
						$byte += $colorAdjusted;
						if ($bitPosition == 8) {
							$this->_imageIconFormat .= chr($byte);
							$bitPosition = 0;
							$byte = 0;
						}
					} else {
						$this->_imageIconFormat .= chr($color);
					}
				}
				// Each row ends with dumping the remaining bits and filling up to the 32bit line with 0's.
				if ($bitPosition) {
					$this->_imageIconFormat .= chr($byte);
					$bitPosition = 0;
					$byte = 0;
				}
				if (strlen($this->_imageIconFormat)%4) $this->_imageIconFormat .= str_repeat(chr(0), 4-(strlen($this->_imageIconFormat)%4));
			}
		} else {
			// Save each pixel.
			for ($y = 0; $y < $height; $y++) {
				for ($x = 0; $x < $width; $x++) {
					$color = imagecolorat($imageResource, $x, $height-$y-1);
					$color = imagecolorsforindex($imageResource, $color);
					if ($bitCount == 24) {
						if ($color["alpha"]) {
							$this->_imageIconFormat .= pack("CCC", 0, 0, 0);
						} else {
							$this->_imageIconFormat .= pack("CCC", $color["blue"], $color["green"], $color["red"]);
						}
					} else {
						$color["alpha"] = round((127-$color["alpha"]) / 127 * 255);
						$this->_imageIconFormat .= pack("CCCC", $color["blue"], $color["green"], $color["red"], $color["alpha"]);
					}
				}
				if (strlen($this->_imageIconFormat)%4) $this->_imageIconFormat .= str_repeat(chr(0), 4-(strlen($this->_imageIconFormat)%4));
			}
		}
		// save AND map (transparency)
		$byte = 0; // For $bitCount < 8 math
		$bitPosition = 0; // For $bitCount < 8 math
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				if ($bitCount < 32) {
					$color = imagecolorat($imageResource, $x, $height-$y-1);
					$color = imagecolorsforindex($imageResource, $color);
					$color = $color["alpha"] == 127?1:0;
				} else {
					$color = 0;
				}

				$bitPosition += 1;
				$colorAdjusted = $color * pow(2, 8 - $bitPosition);
				$byte += $colorAdjusted;
				if ($bitPosition == 8) {
					$this->_imageIconFormat .= chr($byte);
					$bitPosition = 0;
					$byte = 0;
				}
			}
			// Each row ends with dumping the remaining bits and filling up to the 32bit line with 0's.
			if ($bitPosition) {
				$this->_imageIconFormat .= chr($byte);
				$bitPosition = 0; // For $bitCount < 8 math
				$byte = 0;
			}
			while (strlen($this->_imageIconFormat)%4) {
				$this->_imageIconFormat .= chr(0);
			}
		}
		if ($colorCount >= 256) {
			$colorCount = 0;
		}
		// Create header information...
		$this->_header = array(
			"Size" => 40,
			"Width" => $width,
			"Height" => $height*2,
			"Planes" => 1,
			"BitCount" => $bitCount,
			"Compression" => 0,
			"ImageSize" => strlen($this->_imageIconFormat),
			"XpixelsPerM" => 0,
			"YpixelsPerM" => 0,
			"ColorsUsed" => $colorCount,
			"ColorsImportant" => 0,
		);
		$this->_headerIconFormat = pack("LLLSSLLLLLL",
			$this->_header["Size"],
			$this->_header["Width"],
			$this->_header["Height"],

			$this->_header["Planes"],
			$this->_header["BitCount"],

			$this->_header["Compression"],
			$this->_header["ImageSize"],
			$this->_header["XpixelsPerM"],
			$this->_header["YpixelsPerM"],
			$this->_header["ColorsUsed"],
			$this->_header["ColorsImportant"]
		);
		$this->_entry = array(
			"Width" => $width,
			"Height" => $height,
			"ColorCount" => $colorCount,
			"Reserved" => 0,
			"Planes" => 1,
			"BitCount" => $bitCount,
			"SizeInBytes" => $this->_header["Size"] + $this->_header["ImageSize"],
			"FileOffset" => -1,
		);
		$this->_entryIconFormat = ""; // This won't get set until it's needed with the offset.
		$this->_imageResource = $imageResource;

		// Make png if width exceeds limit for old ico style
		if ($width > $pngIfWidthExceeds) {
			// I wish there were a better way to get the info than this.  If anyone needs a version that doesn't use OB, I can have one that creates a TMP file.
			ob_start();
			imagepng($imageResource);
			$imageAsPng = ob_get_contents();
			ob_end_clean();
			$this->_headerIconFormat = "";
			$this->_imageIconFormat = $imageAsPng;
		}

	
	}
	function _createImageResource() {
		if ($newImage = @imagecreatefromstring($this->_headerIconFormat.$this->_imageIconFormat)) {
			// Vista supports PNG.
			$this->_headerIconFormat = "";
			$this->_imageIconFormat = $this->_headerIconFormat.$this->_imageIconFormat;
			imagesavealpha($newImage, true);
			imagealphablending($newImage, false);
			$this->_imageResource = $newImage;
		} elseif ($this->_entry["Height"] <= 1024 && $this->_entry["Width"] <= 1024) {
			$newImage = imagecreatetruecolor($this->_entry["Width"], $this->_entry["Height"]);
			imagesavealpha($newImage, true);
			imagealphablending($newImage, false);
			$readPosition = 0;
			$palette = array();
			if ($this->_header["BitCount"] < 24) {
				// Read Palette for low bitcounts
				$colorsInPalette = $this->_header["ColorsUsed"]?$this->_header["ColorsUsed"]:$this->_entry["ColorCount"];
				for ($t = 0; $t < pow(2, $this->_header["BitCount"]); $t++) {
					$blue = ord($this->_imageIconFormat[$readPosition++]);
					$green = ord($this->_imageIconFormat[$readPosition++]);
					$red = ord($this->_imageIconFormat[$readPosition++]);
					$readPosition++; // Unused "Reserved" value.
						$existingPaletteEntry = imagecolorexactalpha($newImage, $red, $green, $blue, 0);
						if ($existingPaletteEntry >= 0) {
							$palette[] = $existingPaletteEntry;
						} else {
							$palette[] = imagecolorallocatealpha($newImage, $red, $green, $blue, 0);
						}
				}
				// XOR
				for ($y = 0; $y < $this->_entry["Height"]; $y++) {
					$colors = array();
					for ($x = 0; $x < $this->_entry["Width"]; $x++) {
						if ($this->_header["BitCount"] < 8) {
							$color = array_shift($colors);
							if (is_null($color)) {
								$byte = ord($this->_imageIconFormat[$readPosition++]);
								$tmp_color = 0;
								for ($t = 7; $t >= 0; $t--) {
									$bit_value = pow(2, $t);
									$bit = floor($byte / $bit_value);
									$byte = $byte - ($bit * $bit_value);
									$tmp_color += $bit * pow(2, $t%$this->_header["BitCount"]);
									if ($t%$this->_header["BitCount"] == 0) {
										array_push($colors, $tmp_color);
										$tmp_color = 0;
									}
								}
								$color = array_shift($colors);
							}
						} else {
							$color = ord($this->_imageIconFormat[$readPosition++]);
						}
						imagesetpixel($newImage, $x, $this->_entry["Height"]-$y-1, $palette[$color]) or die("can't set pixel");
					}
					// All rows end on the 32 bit
					if ($readPosition%4) $readPosition += 4-($readPosition%4);
				}
			} else {
				// BitCount >= 24, No Palette.
				// marking position because some icons mark all pixels transparent when using an AND map.
				$markPosition = $readPosition;
				$retry = true;
				$ignoreAlpha = false;
				while ($retry) {
					$alphas = array();
					$retry = false;
					for ($y = 0; $y < $this->_entry["Height"] and !$retry; $y++) {
						for ($x = 0; $x < $this->_entry["Width"] and !$retry; $x++) {
							$blue = ord($this->_imageIconFormat[$readPosition++]);
							$green = ord($this->_imageIconFormat[$readPosition++]);
							$red = ord($this->_imageIconFormat[$readPosition++]);
							if ($this->_header["BitCount"] < 32) {
								$alpha = 0;
							} elseif($ignoreAlpha) {
								$alpha = 0;
								$readPosition++;
							} else {
								$alpha = ord($this->_imageIconFormat[$readPosition++]);
								$alphas[$alpha] = $alpha;
								$alpha = 127-round($alpha/255*127);
							}
							$paletteEntry = imagecolorexactalpha($newImage, $red, $green, $blue, $alpha);
							if ($paletteEntry < 0) {
								$paletteEntry = imagecolorallocatealpha($newImage, $red, $green, $blue, $alpha);
							}
							imagesetpixel($newImage, $x, $this->_entry["Height"]-$y-1, $paletteEntry) or die("can't set pixel");
						}
						if ($readPosition%4) $readPosition += 4-($readPosition%4);
					}
					if ($this->_header["BitCount"] == 32 && isset($alphas[0]) && count($alphas) == 1) {
						$retry = true;
						$readPosition = $markPosition;
						$ignoreAlpha = true;
					}
				}

			}
			// AND map
			if ($this->_header["BitCount"] < 32 || $ignoreAlpha) {
				// Bitcount == 32, No AND (if using alpha).
				$palette[-1] = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
				imagecolortransparent($newImage, $palette[-1]);
				for ($y = 0; $y < $this->_entry["Height"]; $y++) {
					$colors = array();
					for ($x = 0; $x < $this->_entry["Width"]; $x++) {
						$color = array_shift($colors);
						if (is_null($color)) {
							$byte = ord($this->_imageIconFormat[$readPosition++]);
							$tmp_color = 0;
							for ($t = 7; $t >= 0; $t--) {
								$bit_value = pow(2, $t);
								$bit = floor($byte / $bit_value);
								$byte = $byte - ($bit * $bit_value);
								array_push($colors, $bit);
							}
							$color = array_shift($colors);
						}
						if ($color) {
							imagesetpixel($newImage, $x, $this->_entry["Height"]-$y-1, $palette[-1]) or die("can't set pixel");
						}
					}
					// All rows end on the 32 bit.
					if ($readPosition%4) $readPosition += 4-($readPosition%4);
				}
			}
			if ($this->_header["BitCount"] < 24) {
				imagetruecolortopalette($newImage, true, pow(2, $this->_header["BitCount"]));
			}
		}
		$this->_imageResource = $newImage;
	}
	// this function expects that $_entry, $_header and $_imageIconFormat have already been read, specifically from readImageFromICO.
	// Don't call this function except from there.
	function readImageFromICO($filePointer, $entryOffset) {
		$tmpPosition = ftell($filePointer); // So any other applications won't loose their position.
		// Get the entry.
		fseek($filePointer, $entryOffset);
		$this->_entryIconFormat = fread($filePointer, 16);
		$this->_entry = unpack("CWidth/CHeight/CColorCount/CReserved/vPlanes/vBitCount/VSizeInBytes/VFileOffset", $this->_entryIconFormat);

		// Position the file pointer.
		fseek($filePointer, $this->_entry["FileOffset"]);

		// Get the header.
		$this->_headerIconFormat = fread($filePointer, 40);
        $this->_header = unpack("VSize/VWidth/VHeight/vPlanes/vBitCount/VCompression/VImageSize/VXpixelsPerM/VYpixelsPerM/VColorsUsed/VColorsImportant", $this->_headerIconFormat);

		// Get the image.
		$this->_imageIconFormat = @fread($filePointer, $this->_entry["SizeInBytes"] - strlen($this->_headerIconFormat));
		fseek($filePointer, $tmpPosition); // So any other applications won't loose their position.

		if ($newImage = @imagecreatefromstring($this->_headerIconFormat.$this->_imageIconFormat)) {
			// This is a PNG, the supposed header information is useless.
			$this->_header = array (
				"Size" => 0,
				"Width" => imagesx($newImage),
				"Height" => imagesy($newImage) * 2,
				"Planes" => 0,
				"BitCount" => 32,
				"Compression" => 0,
				"ImageSize" => strlen($this->_imageIconFormat),
				"XpixelsPerM" => 0,
				"YpixelsPerM" => 0,
				"ColorsUsed" => 0,
				"ColorsImportant" => 0,
			);
			imagedestroy($newImage);
		}

		// Support for larger images requires entry marked as 0.
		if ($this->_entry["Width"] == 0) {
			$this->_entry["Width"] = $this->_header["Width"];
		}
		if ($this->_entry["Height"] == 0) {
			$this->_entry["Height"] = $this->_header["Height"]/2;
		}
	}
	function getHeader() {
		return $this->_header;
	}
	function getEntry() {
		return $this->_entry;
	}
	function __construct() {
	}
	function getHeaderSize() {
		return strlen($this->_headerIconFormat);
	}
	function getSize() {
		return strlen($this->_imageIconFormat);
	}
	function getImageResource() {
		if (!$this->_imageResource) $this->_createImageResource();
		return $this->_imageResource;
	}
	function dealocateResource() {
		@imagedestroy($this->_imageResource);
		$this->_imageResource = null;
	}
}
?>