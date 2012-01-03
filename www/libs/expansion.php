<?php

/**
	Expansion pack for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3 Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Expansion
		@version 1.4.3
**/

//! Expansion pack
class Expansion extends Core {

	//! Minimum framework version required to run
	const F3_Minimum='1.4.2';

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_Minify='Unable to minify {@CONTEXT}',
		TEXT_Timeout='Connection timed out',
		TEXT_NotArray='{@CONTEXT} is not an array';
	//@}

	//! Carriage return/line feed sequence
	const EOL="\r\n";

	/**
		Return translation table for Latin diacritics and 7-bit equivalents
			@return array
			@public
	**/
	public static function diacritics() {
		return array(
			'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Å'=>'A','Ä'=>'AE','Æ'=>'AE',
			'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','å'=>'a','ä'=>'ae','æ'=>'ae',
			'Þ'=>'B','þ'=>'b','Č'=>'C','Ć'=>'C','Ç'=>'C','č'=>'c','ć'=>'c',
			'ç'=>'c','ð'=>'d','Đ'=>'Dj','đ'=>'dj','È'=>'E','É'=>'E','Ê'=>'E',
			'Ë'=>'E','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','Ì'=>'I','Í'=>'I',
			'Î'=>'I','Ï'=>'I','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','Ñ'=>'N',
			'ñ'=>'n','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ø'=>'O','Ö'=>'OE',
			'Œ'=>'OE','ð'=>'o','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'oe',
			'œ'=>'oe','ø'=>'o','Ŕ'=>'R','ŕ'=>'r','Š'=>'S','š'=>'s','ß'=>'ss',
			'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'UE','ù'=>'u','ú'=>'u','û'=>'u',
			'ü'=>'ue','Ý'=>'Y','ý'=>'y','ý'=>'y','ÿ'=>'yu','Ž'=>'Z','ž'=>'z'
		);
	}

	/**
		Return an RFC 1738-compliant URL-friendly version of string
			@return string
			@param $text string
			@param $maxlen integer
	**/
	public static function slug($text,$maxlen=-1) {
		$text=preg_replace(
			'/[^\w\.!~*\'"(),]+/','-',
			trim(strtr($text,self::diacritics()))
		);
		return $maxlen>-1?substr($text,0,$maxlen):$text;
	}

	/**
		Strip Javascript/CSS files of extraneous whitespaces and comments;
		Return combined output as a minified string
			@param $base string
			@param $files array
			@public
	**/
	public static function minify($base,array $files) {
		preg_match('/\.(js|css)$/',$files[0],$ext);
		if (!$ext[1]) {
			// Not a JavaSript/CSS file
			F3::http404();
			return;
		}
		$mime=array(
			'js'=>'application/x-javascript',
			'css'=>'text/css'
		);
		$path=self::$global['GUI'].F3::resolve($base);
		foreach ($files as $file)
			if (!is_file($path.$file)) {
				self::$global['CONTEXT']=$file;
				trigger_error(self::TEXT_Minify);
				return;
			}
		$src='';
		if (PHP_SAPI!='cli')
			header(F3::HTTP_Content.': '.$mime[$ext[1]].'; '.
				'charset='.self::$global['ENCODING']);
		foreach ($files as $file) {
			self::$stats['FILES']['minified']
				[basename($file)]=filesize($path.$file);
			// Rewrite relative URLs in CSS
			$src.=preg_replace_callback(
				'/\b(?<=url)\(([\"\'])*([^\1]+?)\1*\)/',
				function($url) use($path,$file) {
					$fdir=dirname($file);
					$rewrite=explode(
						'/',$path.($fdir!='.'?$fdir.'/':'').$url[2]
					);
					$i=0;
					while ($i<count($rewrite))
						// Analyze each URL segment
						if ($i>0 &&
							$rewrite[$i]=='..' &&
							$rewrite[$i-1]!='..') {
							// Simplify URL
							unset($rewrite[$i],$rewrite[$i-1]);
							$rewrite=array_values($rewrite);
							$i--;
						}
						else
							$i++;
					// Reconstruct simplified URL
					return
						'('.implode('/',array_merge($rewrite,array())).')';
				},
				// Retrieve CSS/Javascript file
				file_get_contents($path.$file)
			);
		}
		$ptr=0;
		$dst='';
		while ($ptr<strlen($src)) {
			if ($src[$ptr]=='/') {
				// Presume it's a regex pattern
				$regex=TRUE;
				if ($ptr>0) {
					// Backtrack and validate
					$ofs=$ptr;
					while ($ofs>0) {
						$ofs--;
					// Pattern should be preceded by parenthesis,
					// colon or assignment operator
					if ($src[$ofs]=='(' || $src[$ofs]==':' ||
						$src[$ofs]=='=') {
							while ($ptr<strlen($src)) {
								$str=strstr(substr($src,$ptr+1),'/',TRUE);
								if (!strlen($str) && $src[$ptr-1]!='/' ||
									strpos($str,"\n")!==FALSE) {
									// Not a regex pattern
									$regex=FALSE;
									break;
								}
								$dst.='/'.$str;
								$ptr+=strlen($str)+1;
								if ($src[$ptr-1]!='\\' ||
									$src[$ptr-2]=='\\') {
										$dst.='/';
										$ptr++;
										break;
								}
							}
							break;
						}
						elseif ($src[$ofs]!="\t" && $src[$ofs]!=' ') {
							// Not a regex pattern
							$regex=FALSE;
							break;
						}
					}
					if ($regex && $ofs<1)
						$regex=FALSE;
				}
				if (!$regex || $ptr<1) {
					if (substr($src,$ptr+1,2)=='*@') {
						// Conditional block
						$str=strstr(substr($src,$ptr+3),'@*/',TRUE);
						$dst.='/*@'.$str.$src[$ptr].'@*/';
						$ptr+=strlen($str)+6;
					}
					elseif ($src[$ptr+1]=='*') {
						// Multiline comment
						$str=strstr(substr($src,$ptr+2),'*/',TRUE);
						$ptr+=strlen($str)+4;
					}
					elseif ($src[$ptr+1]=='/') {
						// Single-line comment
						$str=strstr(substr($src,$ptr+2),"\n",TRUE);
						$ptr+=strlen($str)+2;
					}
					else {
						// Division operator
						$dst.=$src[$ptr];
						$ptr++;
					}
				}
				continue;
			}
			if ($src[$ptr]=='\'' || $src[$ptr]=='"') {
				$match=$src[$ptr];
				// String literal
				while ($ptr<strlen($src)) {
					$str=strstr(substr($src,$ptr+1),$src[$ptr],TRUE);
					$dst.=$match.$str;
					$ptr+=strlen($str)+1;
					if ($src[$ptr-1]!='\\' || $src[$ptr-2]=='\\') {
						$dst.=$match;
						$ptr++;
						break;
					}
				}
				continue;
			}
			if (ctype_space($src[$ptr])) {
				$last=substr($dst,-1);
				$ofs=$ptr+1;
				if ($ofs+1<strlen($src)) {
					while (ctype_space($src[$ofs]))
						$ofs++;
					if (preg_match('/\w[\w'.
						// IE is sensitive about certain spaces in CSS
						($ext[1]=='css'?'#*\.':'').'$]/',$last.$src[$ofs]))
							$dst.=$src[$ptr];
				}
				$ptr=$ofs;
			}
			else {
				$dst.=$src[$ptr];
				$ptr++;
			}
		}
		echo $dst;
	}

	/**
		Convert seconds to frequency (in words)
			@return integer
			@param $secs string
			@public
	**/
	public static function frequency($secs) {
		$freq['hourly']=3600;
		$freq['daily']=86400;
		$freq['weekly']=604800;
		$freq['monthly']=2592000;
		foreach ($freq as $key=>$val)
			if ($secs<=$val)
				return $key;
		return 'yearly';
	}

	/**
		Parse each URL recursively and generate sitemap
			@param $url string
			@public
	**/
	public static function sitemap($url='/') {
		$map=&self::$global['SITEMAP'];
		if (array_key_exists($url,$map) && $map[$url]['status']!==NULL)
			// Already crawled
			return;
		preg_match('/^http[s]*:\/\/([^\/$]+)/',$url,$host);
		if (!empty($host) && $host[1]!=$_SERVER['SERVER_NAME']) {
			// Remote URL
			$map[$url]['status']=FALSE;
			return;
		}
		$state=self::$global['QUIET'];
		self::$global['QUIET']=TRUE;
		F3::mock('GET '.$url);
		F3::run();
		// Check if an error occurred or no HTTP response
		if (self::$global['ERROR'] || !self::$global['RESPONSE']) {
			$map[$url]['status']=FALSE;
			// Reset error flag for next page
			unset(self::$global['ERROR']);
			return;
		}
		$doc=new XMLTree('1.0',self::$global['ENCODING']);
		// Suppress errors caused by invalid HTML structures
		libxml_use_internal_errors(TRUE);
		if ($doc->loadHTML(self::$global['RESPONSE'])) {
			// Valid HTML; add to sitemap
			if (!$map[$url]['level'])
				// Web root
				$map[$url]['level']=0;
			$map[$url]['status']=TRUE;
			$map[$url]['mod']=time();
			$map[$url]['freq']=0;
			// Cached page
			$hash='url.'.F3::hashCode('GET '.$url);
			$cached=Cache::cached($hash);
			if ($cached) {
				$map[$url]['mod']=$cached['time'];
				$map[$url]['freq']=$_SERVER['REQUEST_TTL'];
			}
			// Parse all links
			$links=$doc->getElementsByTagName('a');
			foreach ($links as $link) {
				$ref=$link->getAttribute('href');
				$rel=$link->getAttribute('rel');
				if (!$ref || $rel && preg_match('/nofollow/',$rel))
					// Don't crawl this link!
					continue;
				if (!array_key_exists($ref,$map))
					$map[$ref]=array(
						'level'=>$map[$url]['level']+1,
						'status'=>NULL
					);
			}
			// Parse each link
			array_walk(array_keys($map),'self::sitemap');
		}
		unset($doc);
		if (!$map[$url]['level']) {
			// Finalize sitemap
			$depth=1;
			while ($ref=current($map))
				// Find deepest level while iterating
				if (!$ref['status'])
					// Remove remote URLs and pages with errors
					unset($map[key($map)]);
				else {
					$depth=max($depth,$ref['level']+1);
					next($map);
				}
			// Create XML document
			$xml=simplexml_load_string(
				'<?xml version="1.0" encoding="'.
					self::$global['ENCODING'].'"?>'.
				'<urlset xmlns="'.
					'http://www.sitemaps.org/schemas/sitemap/0.9'.
				'"/>'
			);
			$host='http://'.$_SERVER['SERVER_NAME'];
			foreach ($map as $key=>$ref) {
				// Add new URL
				$item=$xml->addChild('url');
				// Add URL elements
				$item->addChild('loc',$host.$key);
				$item->addChild('lastMod',gmdate('c',$ref['mod']));
				$item->addChild('changefreq',
					self::frequency($ref['freq']));
				$item->addChild('priority',
					sprintf('%1.1f',1-$ref['level']/$depth));
			}
			// Send output
			self::$global['QUIET']=$state;
			if (PHP_SAPI!='cli')
				header(F3::HTTP_Content.': application/xml; '.
					'charset='.self::$global['ENCODING']);
			$xml=dom_import_simplexml($xml)->ownerDocument;
			$xml->formatOutput=TRUE;
			echo $xml->saveXML();
		}
	}

	/**
		Send HTTP/S request to another host; Forward headers received (if
		QUIET variable is FALSE) and return content; Respect HTTP 30x
		redirects if last argument is TRUE
			@return mixed
			@param $pattern string
			@param $query string
			@param $reqhdrs array
			@param $follow boolean
			@param $flag false
			@public
	**/
	public static function http(
		$pattern,$query='',$reqhdrs=array(),$follow=TRUE,$flag=FALSE) {
		// Check if valid route pattern
		list($method,$route)=F3::checkRoute($pattern);
		// Content divider
		$div=chr(0);
		$url=parse_url($route);
		if (!$url['path'])
			// Set to Web root
			$url['path']='/';
		if ($method!='GET') {
			if ($url['query']) {
				// Non-GET method; Query is distinct from URI
				$query=$url['query'];
				$url['query']='';
			}
		}
		else {
			if ($query) {
				// GET method; Query is integral part of URI
				$url['query']=$query;
				$query='';
			}
		}
		// Set up host name and TCP port for socket connection
		if (preg_match('/https/',$url['scheme'])) {
			if (!isset($url['port']))
				$url['port']=443;
			$target='ssl://'.$url['host'].':'.$url['port'];
		}
		else {
			if (!isset($url['port']))
				$url['port']=80;
			$target=$url['host'].':'.$url['port'];
		}
		$socket=@fsockopen($target,$url['port'],$errno,$text);
		if (!$socket) {
			// Can't establish connection
			trigger_error($text);
			return FALSE;
		}
		// Set connection timeout parameters
		stream_set_blocking($socket,TRUE);
		stream_set_timeout($socket,ini_get('default_socket_timeout'));
		// Send HTTP request
		fputs($socket,
			$method.' '.$url['path'].
				($url['query']?('?'.$url['query']):'').' '.
					'HTTP/1.0'.self::EOL.
				F3::HTTP_Host.': '.$url['host'].self::EOL.
				F3::HTTP_Agent.': Mozilla/5.0 (compatible;'.
					strtr($_ENV['OS'],'_',' ').')'.self::EOL.
				($reqhdrs?
					(implode(self::EOL,$reqhdrs).self::EOL):'').
				($method!='GET'?(
					'Content-Type: '.
						'application/x-www-form-urlencoded'.self::EOL.
					'Content-Length: '.strlen($query).self::EOL):'').
				F3::HTTP_AcceptEnc.': gzip'.self::EOL.
				F3::HTTP_Connect.': close'.self::EOL.self::EOL.
			$query.self::EOL.self::EOL
		);
		$found=FALSE;
		$expires=FALSE;
		$gzip=FALSE;
		$rcvhdrs='';
		$info=stream_get_meta_data($socket);
		// Get headers and response
		$response='';
		while (!feof($socket) && !$info['timed_out']) {
			$response.=fgets($socket,4096); // MDFK97
			$info=stream_get_meta_data($socket);
			if (!$found && is_int(strpos($response,self::EOL.self::EOL))) {
				$found=TRUE;
				$rcvhdrs=strstr($response,self::EOL.self::EOL,TRUE);
				ob_start();
				if ($follow &&
					preg_match('/HTTP\/1\.\d\s30\d/',$rcvhdrs)) {
					// Redirection
					preg_match('/'.F3::HTTP_Location.
						':\s*(.+?)/',$rcvhdrs,$loc);
					return self::http(
						$method.' '.$loc[1],$query,$reqhdrs
					);
				}
				foreach (explode(self::EOL,$rcvhdrs) as $hdr) {
					self::$global['HEADERS'][]=$hdr;
					if (PHP_SAPI!='cli' &&
						preg_match('/'.F3::HTTP_Content.'/',$hdr))
							// Forward HTTP header
							header($hdr);
					elseif (preg_match('/^'.F3::HTTP_Encoding.
						':\s*.*gzip/',$hdr))
						// Uncompress content
						$gzip=TRUE;
				}
				ob_end_flush();
				// Split content from HTTP response headers
				$response=substr(strstr($response,self::EOL.self::EOL),4);
			}
		}
		fclose($socket);
		if ($info['timed_out']) {
			trigger_error(self::TEXT_Timeout);
			return FALSE;
		}
		if (PHP_SAPI!='cli') {
			if ($gzip)
				$response=gzinflate(substr($response,10));
		}
		// Return content
		return $response;
	}

	/**
		Transmit a file for downloading by HTTP client; If kilobytes per
		second is specified, output is throttled (bandwidth will not be
		controlled by default); Return TRUE if successful, FALSE otherwise;
		Support for partial downloads is indicated by third argument
			@param $file string
			@param $kbps integer
			@param $partial
			@public
	**/
	public static function send($file,$kbps=0,$partial=TRUE) {
		$file=F3::resolve($file);
		if (!is_file($file)) {
			F3::http404();
			return FALSE;
		}
		if (PHP_SAPI!='cli') {
			header(F3::HTTP_Content.': application/octet-stream');
			header(F3::HTTP_Partial.': '.($partial?'bytes':'none'));
			header(F3::HTTP_Disposition.': '.
				'attachment; filename='.basename($file));
			header(F3::HTTP_Length.': '.filesize($file));
			F3::httpCache(0);
			ob_end_flush();
		}
		$max=ini_get('max_execution_time');
		$ctr=1;
		$handle=fopen($file,'r');
		$time=time();
		while (!feof($handle) && !connection_aborted()) {
			if ($kbps>0) {
				// Throttle bandwidth
				$ctr++;
				$elapsed=microtime(TRUE)-$time;
				if (($ctr/$kbps)>$elapsed)
					usleep(1e6*($ctr/$kbps-$elapsed));
			}
			// Send 1KiB and reset timer
			echo fread($handle,1024);
			set_time_limit($max);
		}
		fclose($handle);
		return TRUE;
	}

	/**
		Retrieve values from a specified column of a multi-dimensional
		framework array variable
			@return array
			@param $name string
			@param $col mixed
			@public
	**/
	public static function pick($name,$col) {
		$rows=F3::get($name);
		if (!is_array($rows)) {
			self::$global['CONTEXT']=$name;
			trigger_error(self::TEXT_NotArray);
			return FALSE;
		}
		return array_map(
			function($row) use($col) {
				return $row[$col];
			},
			$rows
		);
	}

	/**
		Sort a multi-dimensional framework array variable on a specified
		column; Replace contents of framework variable if flag is TRUE
		(default), otherwise, return sorted result
			@return array
			@param $name string
			@param $col mixed
			@param $order integer
			@param $flag boolean
			@public
	**/
	public static function sort($name,$col,$order=F3::SORT_Asc,$flag=TRUE) {
		$var=F3::get($name);
		if (!is_array($var)) {
			self::$global['CONTEXT']=$name;
			trigger_error(self::TEXT_NotArray);
			return FALSE;
		}
		usort(
			$var,
			function($val1,$val2) use($col,$order) {
				list($v1,$v2)=array($val1[$col],$val2[$col]);
				return $order*
					(((is_int($v1) || is_float($v1)) &&
						(is_int($v2) || is_float($v2)))?
					Expansion::sign($v1-$v2):strcmp($v1,$v2));
			}
		);
		if (!$flag)
			return $var;
		F3::set($name,$var);
	}

	/**
		Rotate a two-dimensional framework array variable; Replace contents
		of framework variable if flag is TRUE (default), otherwise, return
		transposed result
			@return array
			@param $name string
			@param $flag boolean
			@public
	**/
	public static function transpose($name,$flag=TRUE) {
		$rows=F3::get($name);
		if (!is_array($rows)) {
			self::$global['CONTEXT']=$name;
			trigger_error(self::TEXT_NotArray);
			return FALSE;
		}
		foreach ($rows as $keyx=>$cols)
			foreach ($cols as $keyy=>$valy)
				$result[$keyy][$keyx]=$valy;
		if (!$flag)
			return $result;
		F3::set($name,$result);
	}

	/**
		Perform test and append result to TEST global variable
			@return string
			@param $cond boolean
			@param $pass string
			@param $fail string
			@public
	**/
	public static function expect($cond,$pass=NULL,$fail=NULL) {
		if (is_string($cond))
			$cond=F3::resolve($cond);
		$text=$cond?$pass:$fail;
		self::$global['TEST'][]=array(
			'result'=>(int)(boolean)$cond,
			'text'=>is_string($text)?
				F3::resolve($text):var_export($text,TRUE)
		);
		return $text;
	}

	/**
		Returns -1 if the specified number is negative, 0 if zero, or 1 if
		the number is positive
			@return integer
			@param $num mixed
			@public
	**/
	public static function sign($num) {
		return $num?$num/abs($num):0;
	}

	/**
		Convert hexadecimal to binary-packed data
			@return string
			@param $hex string
			@public
	**/
	public static function hexbin($hex) {
		return pack('H*',$hex);
	}

	/**
		Convert binary-packed data to hexadecimal
			@return string
			@param $bin string
			@public
	**/
	public static function binhex($bin) {
		return implode('',unpack('H*',$bin));
	}

	/**
		Return TRUE if HTTP request origin is AJAX
			@return boolean
			@public
	**/
	public static function isAjax() {
		return $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest';
	}

}
