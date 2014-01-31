<?php
// wtf-js-merger for WhatTheFramework JavaScript segments
// copyright 2013 Martin Kaestel Nielsen, think.dk and hvadhedderde under MIT-License
// http://whattheframework.org

ini_set("auto_detect_line_endings", true);
error_reporting(E_ALL);

$access_item = array();
$access_default = "page,list";

$access_item = false;

if(isset($read_access) && $read_access) {
	return;
}


// merge-path info required from Apache conf
if(isset($_SERVER["JS_PATH"])) {
	$path = $_SERVER["JS_PATH"];
}
else {
	print "No JS_PATH?";
	exit();
}


if(isset($_SERVER["JS_INPUT_PATH"])) {
	$input_path = $_SERVER["JS_INPUT_PATH"];
}
else {
	$input_path = $path;
}


// INCLUDE LICENSE TEXT???
$license = $path."/lib/license.txt";


// DEFAULT SEGMENT INCLUDES
$file_include[] = $input_path."/lib/seg_basic_include.js";
$file_output[] = $path."/seg_basic.js";

$file_include[] = $input_path."/lib/seg_mobile_light_include.js";
$file_output[] = $path."/seg_mobile_light.js";

$file_include[] = $input_path."/lib/seg_mobile_include.js";
$file_output[] = $path."/seg_mobile.js";

$file_include[] = $input_path."/lib/seg_mobile_touch_include.js";
$file_output[] = $path."/seg_mobile_touch.js";

$file_include[] = $input_path."/lib/seg_tablet_include.js";
$file_output[] = $path."/seg_tablet.js";

$file_include[] = $input_path."/lib/seg_desktop_include.js";
$file_output[] = $path."/seg_desktop.js";

$file_include[] = $input_path."/lib/seg_desktop_light_include.js";
$file_output[] = $path."/seg_desktop_light.js";

$file_include[] = $input_path."/lib/seg_desktop_ie_include.js";
$file_output[] = $path."/seg_desktop_ie.js";

$file_include[] = $input_path."/lib/seg_tv_include.js";
$file_output[] = $path."/seg_tv.js";

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<!-- All material protected by copyrightlaws (as if you didnt know) //-->
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>-- parse javascript --</title>
	<style type="text/css">
		* {font-family: monaco; font-size: 10px;}
		h3 {margin: 0; padding: 5px 0 0;}
		.good {color: green;}
		.bad {color: red;}
		.notminified {color: green; font-weight: normal; padding: 0 0 0 0px; display: inline-block;}
		.minified {color: green; font-weight: normal; padding: 0 0 0 0px; display: inline-block;}
		.file {color: black; font-weight: bold;}
		.file .file {padding: 0 0 5px 20px; background: #dedede;}
		.file div {display: none;}
		.open > div {display: block;}
	</style>
</head>
<body>

<?php

function parseFile($file) {
	global $fp;
	global $include_size;
	global $path;


	$file_size = strlen(file_get_contents($file));
	$include_size += $file_size ? $file_size : 0;
	$minisize = 0;

	// file header
	print '<div class="file">'."\n";
	print '<h3 onclick="this.parentNode.className = this.parentNode.className.match(/open/) ? \'file\' : \'file open\'">'.$file."</h3>\n";

	// get lines from file
	$lines = file($file);

	// if file has content
	if(count($lines)) {

		fwrite($fp, "\n");
		fwrite($fp, "/*".basename($file)."*/\n");


//		print $file_size;

		$comment_switch = false;

		foreach($lines as $linenumber => $line) {

			// adjustment string - modify this string, for reference in matches
			$work_line = $line;
			$include_line = false;

			if($work_line) {

				// replace one-liner /**/ comments, even if nested inside other string
				if(!$comment_switch && preg_match("/\/\*[^$]+\*\//", $work_line)) {
					$work_line = preg_replace("/\/\*[^$]+\*\//", "", $work_line);
				}

				// found for /* comment start
				if(!$comment_switch && strpos($work_line, "/*") !== false) {

					$com_s_pos = strpos($line, "/*");
					$comment_switch = true;

					// get line content before comment starts (if any)
					$work_line = substr($line, 0, $com_s_pos);
				}

				// comment switch is on, look for */ comment end
				if($comment_switch && strpos($work_line, "*/") !== false) {

					$com_e_pos = strpos($line, "*/");
					$comment_switch = false;

					// get line content after comment is ended
					$work_line = substr($line, $com_e_pos+2);

					$com_s_pos = 0;
					$com_e_pos = 0;
				}
				// comment switch is on, remove all content
				else if($comment_switch) {
					
					$work_line = "";
				}

				// check for // comment starts the line
				if(!$comment_switch && preg_match("/^\/\//", $work_line)) {

					$work_line = "";
				}

				// check for // comment start position within line
				// ignore if // is inside quoted string or in regular expression 
				if(!$comment_switch && preg_match_all("/[^:\\\]{1}\/\//", $work_line, $matches)) {

					// multiple matches to be investigated
					if(count($matches[0]) > 1) {

//						print "multiple occurences<br>";

						for($i = 0; $i < count($matches); $i++) {

							// start with last occurence
							$pos = strrpos($work_line, "//");

							// add new newline, because we are get substring from begining only
							$additional_test = substr($work_line, 0, $pos)."\n";

							// check if removal breaks quoted string or quoted string was already broken
							if(
								(substr_count($additional_test, '"')%2 === 0 || substr_count($work_line, '"')%2 === 1) 
								&& 
								(substr_count($additional_test, "'")%2 === 0 || substr_count($work_line, "'")%2 === 1)
							) {
								$work_line = $additional_test;
							}

						}

					}
					// only one occurence
					else {
						// remove from occurence to end
						$additional_test = preg_replace("/\/\/.*/", "", $work_line);

						// check if removal breaks quoted string or quoted string was already broken
						if(
							(substr_count($additional_test, '"')%2 === 0 || substr_count($work_line, '"')%2 === 1) 
							&& 
							(substr_count($additional_test, "'")%2 === 0 || substr_count($work_line, "'")%2 === 1)
						) {
							$work_line = $additional_test;
						}
					}


				}

				// not comment and not empty line - nothing should be done
				// else if(!$comment_switch && trim($work_line)) {
				// 
				// 	$work_line = $line;
				// }

				// TODO: make sure it is a script tag
				// check if line contains new include ()
				if(!$comment_switch && preg_match("/document.write[^$]+script[^$]+src=\"([a-zA-Z0-9\.\/_\:\-\=\?]+)\"/i", $work_line, $matches)) {
//					print "matched include:".$matches[1]."<br>";

					$work_line = "";
					$include_line = true;

					// external include
					if(preg_match("/http[s]?:\/\//i", $matches[1])) {
						$filepath = $matches[1];
					}
					// local, absolute include
					else if(strpos($matches[1], "/") === 0) {
						$filepath = "http://".$_SERVER["HTTP_HOST"].$matches[1];
					}
					// relative include
					// JS include can only be relative if they are always included from same level dir
					// if relative path is found here, expect included file to be located in $path/lib
					else {
					
						$filepath = $path."/lib/".basename($matches[1]);
					}

					// parse new include file
					parseFile($filepath);

					// add whitespace
					fwrite($fp, "\n");
				}

				if(trim($work_line) && !$comment_switch) {
					fwrite($fp, $work_line);
					$minisize += strlen($work_line);
				
				}
			}

			// output result of parsing
			if(!$comment_switch && (trim($work_line) && trim($line) == trim($work_line))) {
				print "\t".'<div class="notminified"><code>'.$linenumber.':'.htmlentities($line).'</code></div>';
			}
			else if(!$include_line) {
				print "\t".'<div class="minified"><span class="bad">'.$linenumber.':'.htmlentities(trim($line)).'</span><span class="good">'.htmlentities(trim($work_line)).'</span></div>';
			}

		}

	}
	// empty files
	else {
		print "\t".'<div class="minified"><span class="bad">Empty file</span></div>'."\n";
	}

//	print "<div class=\"size\">($include_size bytes) -> ($minisize bytes)</div>";


	print "</div>";

}


// loop through segment includes
foreach($file_include as $index => $source) {

//	print $source."<br>";
	// is segment include available
	if(!file_exists($source)) {

		print $source . " -> " . $file_output[$index] . "<br />";
		print "No include file<br /><br /><hr />";


	}
	else {

		// create output file
		$fp = @fopen($file_output[$index], "w+");

		// could not create, exit with error
		if(!$fp) {
			print "make files writable first";
			exit;
		}

		// include license
		if(file_exists($license)) {
			fwrite($fp, "/*\n");
			fwrite($fp, file_get_contents($license)."\n");
			fwrite($fp, "wtf-js-merged @ ".date("Y-m-d h:i:s")."\n");
			fwrite($fp, "*/\n");
		}


		// keep track of file size
		$include_size = 0;


		// write compiled js
		parseFile($source);

	}
}

?>
</body>
</html>
