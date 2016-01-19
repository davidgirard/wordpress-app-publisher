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
if ( ( realpath( __FILE__ ) === realpath( $_SERVER[ "SCRIPT_FILENAME" ] ) ) || ( ! defined( 'ABSPATH' ) ) ) {
	status_header( 404 );
	exit;
}


// Function to instantiate videospot session
function session_videospot($servicesServer, $videospotUsr, $videospotPasswd) {
	$url        = 'https://'.$servicesServer.'/461CBB694AC02F444F21A6BA3036B2F5/user.session.create.en?Columns=ID,User,Email,Roles,Badges,UniversesEnrolledIn,UniverseCodesEnrolledIn';
	$headers    = array("Content-Type:multipart/form-data");
	$postFields = array(
		'Username' => $videospotUsr,
		'Password' => $videospotPasswd
	);
	$options    = array(
		CURLOPT_URL            => $url,
		CURLOPT_POST           => 1,
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_POSTFIELDS     => $postFields,
		CURLOPT_RETURNTRANSFER => true,
		CURLINFO_HEADER_OUT    => true
	);
	$ch = @curl_init();
	@curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	$vsHash = substr($result, strpos($result, 'Hash="')+6);
	$vsHash = substr($vsHash, 0, strpos($vsHash, '"'));
	$vsUniverses = substr($result, strpos($result, 'UniverseCodesEnrolledIn="')+25);
	$vsUniverses = substr($vsUniverses, 0, strpos($vsUniverses, '"'));
	$vsUniversesNames = substr($result, strpos($result, 'UniversesEnrolledIn="')+21);
	$vsUniversesNames = substr($vsUniversesNames, 0, strpos($vsUniversesNames, '"'));
 	$returnArr = array(
		'Hash'           => $vsHash,
		'Universes'      => $vsUniverses,
		'UniversesNames' => $vsUniversesNames
	);
	return $returnArr;
}

// Function to obtain folder list
function list_folders_videospot($servicesServer, $videospotHash, $videospotUni) {
	$url     = 'https://'.$servicesServer.'/461CBB694AC02F444F21A6BA3036B2F5/'.$videospotHash.'/folders.en?uni='.$videospotUni.'&Columns=ID,Code,FullPath,Color,Tags';
	$headers = array("Content-Type:multipart/form-data");
	$options = array(
		CURLOPT_URL            => $url,
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_RETURNTRANSFER => true,
		CURLINFO_HEADER_OUT => true
	);
	$ch = @curl_init();
	@curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

// Function to create folder
function create_folder_videospot($servicesServer, $videospotHash, $videospotUni) {
	$url     = 'https://'.$servicesServer.'/461CBB694AC02F444F21A6BA3036B2F5/'.$videospotHash.'/folder.create.en?uni='.$videospotUni.'&Columns=ID,Code,FullPath,Color,Tags';
	$headers = array("Content-Type:multipart/form-data");
	$postFields = array(
		'Name'    => "WordPress ".utf8_encode(get_bloginfo('name'))
	);
	$options    = array(
		CURLOPT_URL            => $url,
		CURLOPT_POST           => 1,
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_POSTFIELDS     => $postFields,
		CURLOPT_RETURNTRANSFER => true,
		CURLINFO_HEADER_OUT => true
	);
	$ch = @curl_init();
	@curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

// Post to videospot
function create_clips($servicesServer, $videospotHash, $videospotUni, $folderID, $filePath, $duration, $name, $videospotPostID) {
	$vsPostVersion = get_option('videospot_version_'.$videospotPostID);
	if (empty($vsPostVersion)) {
		$vsPostVersion = 1;
	} else {
		$vsPostVersion++;
	}
	$url        = 'https://'.$servicesServer.'/461CBB694AC02F444F21A6BA3036B2F5/'.$videospotHash.'/clip.create.en.json?uni='.$videospotUni.'&Columns=ID,Code,Name,Type,Status,Folder,Duration,FileSize,FileHash';
	$headers    = array("Content-Type:multipart/form-data");
	$fileSize   = filesize($filePath);
	$postFields = array(
		'Name'        => utf8_encode($name.' '.sanitize_title(get_the_title($videospotPostID)).' v'.$vsPostVersion),
		'File'        => '@'.$filePath,
		'FolderID'    => $folderID,
		'StatusCode'  => 'N',
		'Description' => "Imported from WordPress\nFile path : $filePath\n",
		'Duration'    => $duration
	);
	$options = array(
		CURLOPT_URL            => $url,
		CURLOPT_POST           => 1,
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_POSTFIELDS     => $postFields,
		CURLOPT_INFILESIZE     => $fileSize,
		CURLOPT_RETURNTRANSFER => true,
		CURLINFO_HEADER_OUT => true
	);
	$ch = @curl_init();
	@curl_setopt_array($ch, $options);
	$result = @curl_exec($ch);
	$upload = json_decode($result, true);
	if (is_null($upload)) {
		return null;
	}
	curl_close($ch);
	return (int)@$upload['result'][0]['clip'][0]['@ID'];
}

?>
