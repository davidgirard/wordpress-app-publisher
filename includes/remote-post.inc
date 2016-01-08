<?php
/*
	Copyright © 2015 TCPartners <wp@tcpartners.fr> [http://www.tcpartners.fr]
	This file is part of VideoSpot App Publisher

	VideoSpot App Publisher is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License.

	VideoSpot App Publisher is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with VideoSpot App Publisher.  If not, see <http://www.gnu.org/licenses/>.
*/


//////// Catch Post ID and validate session ////////

// Get post ID, give up if none or non numerical
$postID = $_POST["post_ID"];
if ( empty($postID) || !ctype_digit($postID) ) {
	echo "Invalid or missing post ID, aborting";
	exit();
}

// Validate session token
$tokenToValidate = 'videospot_token_'.$postID;
if ($_GET["vstok"]!=get_option($tokenToValidate)) {
	echo "Invalid or missing session token, aborting";
	exit();
}

// Get user ID
$currentUsrID = $_GET["usrid"];
if ( empty($currentUsrID) || !ctype_digit($currentUsrID) ) {
	echo "Invalid or missing user ID, aborting";
	exit();
}

// Remove token
delete_option($tokenToValidate);


//////// Make sure the VideoSpot options are set in WP ////////

$vsUniverse = get_option("videospot_universe_".$currentUsrID);
$vsUniverseName = get_option("videospot_universename_".$currentUsrID);

$vsServer = get_option("videospot_server_".$currentUsrID);
if (empty($vsServer)) {
	echo "Missing or incorrect VideoSpot API server, redirecting to the VideoSpot config page in the WordPress admin area";
	echo '<script language="javascript">';
	echo 'setTimeout(function(){location.href="'.admin_url().'?page=videospotpage"}, 5000);';
	echo "</script>";
	exit();
}
$vsUsr = get_option("videospot_usr_".$currentUsrID);
if (empty($vsUsr)) {
	echo "Missing or incorrect VideoSpot login, redirecting to the VideoSpot config page in the WordPress admin area";
	echo '<script language="javascript">';
	echo 'setTimeout(function(){location.href="'.admin_url().'?page=videospotpage"}, 5000);';
	echo "</script>";
	exit();
}
$vsPasswd = get_option("videospot_passwd_".$currentUsrID);
if (empty($vsPasswd)) {
	echo "Missing or incorrect VideoSpot password, redirecting to the VideoSpot config page in the WordPress admin area";
	echo '<script language="javascript">';
	echo 'setTimeout(function(){location.href="'.admin_url().'?page=videospotpage"}, 5000);';
	echo "</script>";
	exit();
}
$vsUniverse = get_option("videospot_universe_".$currentUsrID);
if (empty($vsUniverse)) {
	echo "Missing or incorrect VideoSpot universe, redirecting to the VideoSpot config page in the WordPress admin area";
	echo '<script language="javascript">';
	echo 'setTimeout(function(){location.href="'.admin_url().'?page=videospotpage"}, 5000);';
	echo "</script>";
	exit();
}
$durationHours = get_option("videospot_durationhours_".$currentUsrID);
$durationMinutes = get_option("videospot_durationminutes_".$currentUsrID);
$durationSeconds = get_option("videospot_durationseconds_".$currentUsrID);
if ( (empty($durationHours)&&empty($durationMinutes)&&empty($durationSeconds)) || !ctype_digit($durationHours) || !ctype_digit($durationMinutes) || !ctype_digit($durationSeconds) ) {
	echo "Missing or incorrect clip duration, redirecting to the VideoSpot config page in the WordPress admin area";
	echo '<script language="javascript">';
	echo 'setTimeout(function(){location.href="'.admin_url().'?page=videospotpage"}, 5000);';
	echo "</script>";
	exit();
} else {
	$vsDuration = "PT".$durationHours."H".$durationMinutes."M".$durationSeconds."S";
}
$vsName = get_option("videospot_name_".$currentUsrID);
if (empty($vsName)) {
	echo "Missing or incorrect clip name, redirecting to the VideoSpot config page in the WordPress admin area";
	echo '<script language="javascript">';
	echo 'setTimeout(function(){location.href="'.admin_url().'?page=videospotpage"}, 5000);';
	echo "</script>";
	exit();
}


//////// Connection functions ////////

include(plugin_dir_path(__FILE__).'remote-connection.inc');


//////// Functions ////////

// Disable unnecessary xml parsing warnings
libxml_use_internal_errors(true);

// Disguise curl to avoid being blocked by some fancy plugin or htaccess
function disguise_curl($url) {
	$curl = curl_init();
	$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
	$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
	$header[] = "Cache-Control: max-age=0";
	$header[] = "Connection: keep-alive";
	$header[] = "Keep-Alive: 300";
	$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
	$header[] = "Accept-Language: en-us,en;q=0.5";
	$header[] = "Pragma: ";
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
	curl_setopt($curl, CURLOPT_AUTOREFERER, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$html = curl_exec($curl);
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	if ($httpCode==404) {
		$html='';
	}
	curl_close($curl); 
	return $html; 
} 

// Trim src content to make it simple to save and display
function trim_src($xyz) {
	$trimmedValue = end(explode('/', parse_url($xyz, PHP_URL_PATH)));
	$trimmedValue = trim(preg_replace('/[^0-9a-z.]+/i', '', $trimmedValue));
	return $trimmedValue;
}


//////// Establish session ////////

$sessionArray = session_videospot($vsServer, $vsUsr, $vsPasswd);
$sessionHash = $sessionArray['Hash'];


//////// Massage Post ////////

// Set temp dir for content archive
$randSeed = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
$pathToFiles = plugin_dir_path(__FILE__)."../temp/videospot-cache-".$randSeed;
$tempDir = plugin_dir_path(__FILE__)."../temp";
if (!is_dir($tempDir)){
	if (!mkdir($tempDir)) {
		echo "Could not create temp directory";
		exit();
	}
}
if (!is_dir($pathToFiles)){
	if (!mkdir($pathToFiles)) {
		echo "Could not create ".$pathToFiles." directory";
		exit();
	}
}

// Get html, massage and save
$originalPost = disguise_curl(get_site_url()."?p=".$postID);
$dom = new DOMDocument();
$dom -> loadHTML($originalPost);
$imgs = $dom -> getElementsByTagName("img");
$scripts = $dom -> getElementsByTagName("script");
$links = $dom -> getElementsByTagName("link");
foreach ($imgs as $img){ // massage img
	$src = $img -> getAttribute('src');
	if (!empty($src)) {
		$srcTemp = trim_src($src);
		if (strpos($src, '?')) {
			$getQuery = end(explode('?', $src));
			$src = "media/".$srcTemp."?".$getQuery;
		} else {
			$src = "media/".$srcTemp;
		}
		$img -> setAttribute('src', $src);
	}
}
foreach ($scripts as $script){
	$src = $script -> getAttribute('src');
	if (!empty($src)) { // massage js
		$srcTemp = trim_src($src);
		if (strpos($src, '?')) {
			$getQuery = end(explode('?', $src));
			$src = "media/".$srcTemp."?".$getQuery;
		} else {
			$src = "media/".$srcTemp;
		}
		$script -> setAttribute('src', $src);
	}
}
foreach ($links as $link){
	$src = $link -> getAttribute('href');
	if (!empty($src)) { // massage css
		$srcTemp = trim_src($src);
		if (strpos($src, '?')) {
			$getQuery = end(explode('?', $src));
			$src = "media/".$srcTemp."?".$getQuery;
		} else {
			$src = "media/".$srcTemp;
		}
		$link -> setAttribute('href', $src);
	}
}
$massagedPost = $dom -> saveHTML();
$massagedPost = file_get_contents(plugin_dir_path(__FILE__)."../static/index/index.xyz").$massagedPost;
$stringToInject = "<?php echo \$js; ?>";
$massagedPost = preg_replace("/<body(.*?)>/is", "<body$1>".$stringToInject, $massagedPost);
if (!file_put_contents($pathToFiles."/index.php", $massagedPost)) {
	echo "Could not save ".$pathToFiles."/index.php";
}

// Get all pics, css and js, massage and save
if (!is_dir($pathToFiles."/media")){
	if (!mkdir($pathToFiles."/media")) {
		echo "Could not create ".$path."/media"." directory";
	}
}
$dom = new DOMDocument();
$dom -> loadHTML($originalPost);
$imgs = $dom -> getElementsByTagName("img");
foreach ($imgs as $img){
	$src = $img -> getAttribute('src');
	if (substr($src, 0, 4)!="http") {
		if (substr($src, 0, 2)=="//") {
			if (substr(get_site_url(), 0, 5)=="https") {
				$src = "https:".$src;
			} else if (substr(get_site_url(), 0, 5)=="http:") {
				$src = "http:".$src;
			}
		} else {
			$src = get_site_url().$src;
		}
	}
	$mediaContent = disguise_curl($src);
	$mediaContentName = trim_src($src);
	if (!empty($mediaContent)) {
		if (!file_put_contents($pathToFiles."/media/".$mediaContentName, $mediaContent)) {
			echo "Could not save ".$pathToFiles."/media/".$mediaContentName;
		}
	}
}
$dom = new DOMDocument(); // CSS is a LOT more tricky, PLUS it can point to other img and css files
$dom -> loadHTML($originalPost);
$links = $dom -> getElementsByTagName("link");
foreach ($links as $link) {
	if (strtolower($link->getAttribute('rel'))=="stylesheet") {
		$actualHref = $link -> getAttribute("href");
		$mediaContent = disguise_curl($actualHref);
		$mediaContentName = trim_src($actualHref);
		preg_match_all('/url([^)]+)/', $mediaContent, $urlArray, PREG_PATTERN_ORDER); // Catch sub url params within css, massage and save
		foreach ($urlArray[1] as $value) {
			$urlInCss = explode('(', $value);
			$urlInCss = $urlInCss[1];
			$urlInCss = str_replace('"', '', $urlInCss);
			$urlInCss = str_replace("'", "", $urlInCss);
			$urlInCss = str_replace(' ', '', $urlInCss);
			$urlInCssUntouched = $urlInCss;
			if (substr($urlInCss, 0, 7)!='http://') {
				$leadingUri = explode(".css", $actualHref);
				$leadingUri = substr($leadingUri[0], 0, strrpos($leadingUri[0], "/"));
				$urlInCss = $leadingUri."/".$urlInCss;
			}
			$urlContent = disguise_curl($urlInCss);
			$urlContentName = trim_src($urlInCss);
			if (!empty($urlContent)) {
				if (!file_put_contents($pathToFiles."/media/".$urlContentName, $urlContent)) {
					echo "Could not save ".$pathToFiles."/media/".$urlContentName;
				}
			}
			$mediaContent = str_replace($urlInCssUntouched, $urlContentName, $mediaContent);
		}
		if (!empty($mediaContent)) { // Save actual css
			if (!file_put_contents($pathToFiles."/media/".$mediaContentName, $mediaContent)) {
				echo "Could not save ".$pathToFiles."/media/".$mediaContentName;
			}
		}
	}
}


//////// Package ////////

// Create zip object
$zip = new ZipArchive();
$zip -> open(plugin_dir_path(__FILE__).'../temp/post.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Fetch post and media
$rootPathFiles = realpath($pathToFiles);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPathFiles), RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($files as $name => $file) {
	if (!$file->isDir()) {
		$filePath = $file -> getRealPath();
		$relativePath = substr($filePath, strlen($rootPathFiles) + 1);
		$zip -> addFile($filePath, $relativePath);
	}
}

// Fetch static embeds
$rootPathEmbeds = realpath(plugin_dir_path(__FILE__)."../static/embeds/");
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPathEmbeds), RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($files as $name => $file) {
	if (!$file->isDir()) {
		$filePath = $file -> getRealPath();
		$relativePath = substr($filePath, strlen($rootPathEmbeds) + 1);
		if (substr($relativePath, -3)=="xyz") {
			$relativePath = substr($relativePath, 0, -3)."php";
		}
		$zip -> addFile($filePath, $relativePath);
	}
}

// Zip archive will be created after closing object
$zip -> close();


//////// Post Content to API ////////

$filePath = realpath(plugin_dir_path(__FILE__)."../temp/post.zip");
$postResult = create_clips($vsServer, $sessionHash, $vsUniverse, $folderID, $filePath, $vsDuration, $vsName, $postID);
if (empty($postResult)) {
	echo "Post to VideoSpot failed, some of your settings might be incorrect, redirecting to the VideoSpot config page in the WordPress admin area. Alternatively, please contact your VideoSpot administrator.";
	echo '<script language="javascript">';
	echo 'setTimeout(function(){location.href="'.admin_url().'?page=videospotpage"}, 5000);';
	echo "</script>";
} else {
	echo "Post to VideoSpot succeeded, clip ID #".$postResult." created. Sending you back to the Edit Post page.";
	echo '<script language="javascript">';
	echo 'setTimeout(function(){location.href="'.admin_url().'post.php?post='.$postID.'&action=edit"}, 5000);';
	echo "</script>";
	// Increment post counter
	$vsPostVersion = get_option('videospot_version_'.$postID);
	if (empty($vsPostVersion)) {
		add_option('videospot_version_'.$postID, '1');
	} else {
		$vsPostVersion++;
		update_option('videospot_version_'.$postID, $vsPostVersion);
	}
}

// Final clean-up
$it = new RecursiveDirectoryIterator($tempDir);
$it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
foreach($it as $file) {
	if ('.' === $file->getBasename() || '..' ===  $file->getBasename()) {
		continue;
	}
	if ($file->isDir()) {
		rmdir($file->getPathname());
	} else {
		unlink($file->getPathname());
	}
}
rmdir($tempDir);


?>
