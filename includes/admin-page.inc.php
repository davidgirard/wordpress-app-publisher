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


///////// Check if universal account first ////////

$currentUsrID = wp_get_current_user()->ID;
$currentUsrName = wp_get_current_user()->display_name;
$vsUniversalAccount = get_option("videospot_universalaccount");
$vsUniversalAccountName = get_user_by('ID', $vsUniversalAccount)->display_name;
if (!empty($vsUniversalAccount)) {
	if ($vsUniversalAccount!=$currentUsrID) {
		echo ("<h1>VideoSpot options disabled for ".$currentUsrName."</h1>The user ".$vsUniversalAccountName." declared his/her VideoSpot account universal for this blog, all wordpress users are using this account. To change this setting, please contact ".$vsUniversalAccountName."</h2>");
		exit();
	}
}


///////// Set variables ////////

$vsServer = get_option("videospot_server_".$currentUsrID);
$vsUsr = get_option("videospot_usr_".$currentUsrID);
$vsPasswd = get_option("videospot_passwd_".$currentUsrID);
$vsUniverse = get_option("videospot_universe_".$currentUsrID);
$vsUniverseName = get_option("videospot_universename_".$currentUsrID);
$videospotLogin = array (
	array("name" => "VideoSpot server", "desc" => "Enter your VideoSpot server (default server <em><strong>services.videospot.com</strong></em>)", "id" => "videospot_server_".$currentUsrID, "type" => "text", "std" => "services.videospot.com",  "autocomplete" => "off"),
	array("name" => "VideoSpot login", "desc" => "Enter your VideoSpot login", "id" => "videospot_usr_".$currentUsrID, "type" => "text", "std" => "",  "autocomplete" => "off"),
	array("name" => "VideoSpot password", "desc" => "Enter your VideoSpot password", "id" => "videospot_passwd_".$currentUsrID, "type" => "password", "std" => "",  "autocomplete" => "off"),
);
$videospotUniverse = array (
	array("name" => "VideoSpot universe ID", "desc" => "VideoSpot universe ID", "id" => "videospot_universe_".$currentUsrID, "type" => "text", "std" => "",  "autocomplete" => "off"),
	array("name" => "VideoSpot universe name", "desc" => "VideoSpot universe name", "id" => "videospot_universename_".$currentUsrID, "type" => "text", "std" => "",  "autocomplete" => "off"),
);
$videospotOptions = array (
	array("name" => "Duration (hours)", "desc" => "Duration of your clip (hours)", "id" => "videospot_durationhours_".$currentUsrID, "type" => "text", "std" => "0",  "autocomplete" => "off"),
	array("name" => "Duration (minutes)", "desc" => "Duration of your clip (minutes)", "id" => "videospot_durationminutes_".$currentUsrID, "type" => "text", "std" => "1",  "autocomplete" => "off"),
	array("name" => "Duration (seconds)", "desc" => "Duration of your clip (seconds)", "id" => "videospot_durationseconds_".$currentUsrID, "type" => "text", "std" => "0",  "autocomplete" => "off"),
	array("name" => "Name", "desc" => "Clip name", "id" => "videospot_name_".$currentUsrID, "type" => "text", "std" => ""),
);
$videospotUniversalAccount = array (
	array("name" => "Universal", "desc" => "Universal account", "id" => "videospot_universalaccount", "type" => "text", "std" => "",  "autocomplete" => "off"),
);

//////// Connection functions ////////

include(plugin_dir_path(__FILE__).'remote-connection.inc.php');

//////// Updates notification ////////

// Login options
$message = '';
if ('save_login'==$_REQUEST['action']) {
	foreach ($videospotLogin as $value) {
		update_option( $value['id'], $_REQUEST[ $value['id'] ] );
	}
	foreach ($videospotLogin as $value) {
		if( isset( $_REQUEST[ $value['id'] ] ) ) {
			update_option( $value['id'], $_REQUEST[ $value['id'] ]  );
		} else {
			delete_option( $value['id'] );
		}
	}
	$message='login saved';
	echo '<script language="javascript">';
	echo "setTimeout(function(){window.location.reload()}, 1000);";
	echo "</script>";
} elseif ('reset_login'==$_REQUEST['action']) {
	foreach ($videospotLogin as $value) {
		delete_option( $value['id'] );
	}
	$message='login reset';
	echo '<script language="javascript">';
	echo "setTimeout(function(){window.location.reload()}, 1000);";
	echo "</script>";
}
if ( $message=='login saved' ) echo '<div class="updated settings-error" id="setting-error-settings_updated"> <p>Login settings saved, please wait for the page to reload</strong></p></div>';
if ( $message=='login reset' ) echo '<div class="updated settings-error" id="setting-error-settings_updated"> <p>Login settings reset, please wait for the page to reload</strong></p></div>';

// Universe options
$message = '';
if ('save_universe'==$_REQUEST['action']) {
	foreach ($videospotUniverse as $value) {
		update_option( $value['id'], $_REQUEST[ $value['id'] ] );
	}
	foreach ($videospotUniverse as $value) {
		if( isset( $_REQUEST[ $value['id'] ] ) ) {
			update_option( $value['id'], $_REQUEST[ $value['id'] ]  );
		} else {
			delete_option( $value['id'] );
		}
	}
	$message='universe saved';
	echo '<script language="javascript">';
	echo "setTimeout(function(){window.location.reload()}, 1000);";
	echo "</script>";
}
if ( $message=='universe saved' ) echo '<div class="updated settings-error" id="setting-error-settings_updated"> <p>Universe settings saved, please wait for the page to reload</strong></p></div>';


// Clip options
if ('save'==$_REQUEST['action']) {
	foreach ($videospotOptions as $value) {
		update_option( $value['id'], $_REQUEST[ $value['id'] ] );
	}
	foreach ($videospotOptions as $value) {
		if( isset( $_REQUEST[ $value['id'] ] ) ) {
			update_option( $value['id'], $_REQUEST[ $value['id'] ]  );
		} else {
			delete_option( $value['id'] );
		}
	}
	$message='saved';
} elseif ('reset'==$_REQUEST['action']) {
	foreach ($videospotOptions as $value) {
		delete_option( $value['id'] );
	}
	$message='reset';
}
if ( $message=='saved' ) echo '<div class="updated settings-error" id="setting-error-settings_updated"> <p>Clip settings saved</strong></p></div>';
if ( $message=='reset' ) echo '<div class="updated settings-error" id="setting-error-settings_updated"> <p>Clip settings reset</strong></p></div>';

// Universal account
if ('save_universal'==$_REQUEST['action']) {
	foreach ($videospotUniversalAccount as $value) {
		update_option( $value['id'], $_REQUEST[ $value['id'] ] );
	}
	foreach ($videospotUniversalAccount as $value) {
		if( isset( $_REQUEST[ $value['id'] ] ) ) {
			update_option( $value['id'], $_REQUEST[ $value['id'] ]  );
		} else {
			delete_option( $value['id'] );
		}
	}
	$message='universal account saved';
	echo '<script language="javascript">';
	echo "setTimeout(function(){window.location.reload()}, 1000);";
	echo "</script>";
}
if ( $message=='universal account saved' ) echo '<div class="updated settings-error" id="setting-error-settings_updated"> <p>Universal account settings saved, please wait for the page to reload</strong></p></div>';

//////// Login portion of the admin page ////////

?>
<div class="wrap options_wrap">
	<div id="icon-options-general"></div>
	<h1>VideoSpot Configuration</h1>
	<h2>Login Info</h2>
	<div class="content_options">
		<form method="post" autocomplete="off">
			<input name="blockautocomplete" style="display: none;" type="password" />
			<?php foreach ($videospotLogin as $value) { ?>
					<div class="input_section">
						<div class="input_title">
							<h4><?php echo $value['name']; ?></h4>
							<div class="clearfix"></div>
						</div>
						<div class="option_input">
							<input type="<?php echo $value['type']; ?>" name="<?php echo $value['id']; ?>" value="<?php if ( get_settings( $value['id'] ) != "") { echo stripslashes(get_settings( $value['id'])  ); } else { echo $value['std']; } ?>" autocomplete="<?php echo $value['autocomplete']; ?>" />
							<small><?php echo $value['desc']; ?></small>
							<div class="clearfix"></div>
						</div>
					</div>
					<?php
			}?>
			<p style="padding: 0px !important; margin: 20px 0px !important;" class="submit"><input name="save" type="submit" class="button-primary" value="Save changes" /></p>
			<input type="hidden" name="action" value="save_login" />
		</form>
		<form method="post">
			<p style="padding: 0px !important; margin: 20px 0px 30px !important;" class="submit">
				<input name="reset" type="submit" class="button-secondary" value="Reset login options" />
				<input type="hidden" name="action" value="reset_login" />
			</p>
		</form>
	</div>

<?php


//////// Establish session ////////

// Abort if login stuff not set
if ( empty($vsServer) ||  empty($vsUsr) ||  empty($vsPasswd) ) {
	exit();
}

// Establish session
$sessionArray = session_videospot($vsServer, $vsUsr, $vsPasswd);
$sessionHash = $sessionArray['Hash'];

if (empty($sessionHash)) {
	echo "<h4>Error: could not establish session with server, please check your login and password information</h4>";
	exit();
}


//////// Deal with universe(s) ////////

?>
	<h2>VideoSpot Universe</h2>
<?php

// Pick universe
$allUniverses = $sessionArray['Universes'];
$allUniverses = explode(",", $allUniverses);
$allUniversesNames = $sessionArray['UniversesNames'];
$allUniversesNames = explode(",", $allUniversesNames);
$usrUniverse = "";
$i = 0;
$numUsrUniverse = count($allUniverses);
if ( ($numUsrUniverse==1) && empty($allUniverses[0]) ) {
	$numUsrUniverse = 0; // Count array will always return 1 even if empty, fixing
}
if ($numUsrUniverse==0) {
	echo "No universes available for your account, please contact your VideoSpot administrator";
	exit();
} elseif ($numUsrUniverse==1) {
	$usrUniverse = $allUniverses[0];
	update_option('videospot_universe_'.$currentUsrID, $usrUniverse);
	update_option('videospot_universename_'.$currentUsrID, $allUniversesNames[0]);
	echo "You are currently using your default VideoSpot universe: <em><strong>".$allUniversesNames[0]."</strong></em><br />";
} elseif ($numUsrUniverse>1) {
	echo '<form id="universe" style="display:none;" method="post">';
	echo '﻿﻿<input type="hidden" id="videospot_universe" name="videospot_universe_'.$currentUsrID.'" value="" autocomplete="off" />';
	echo '﻿﻿<input type="hidden" id="videospot_universename" name="videospot_universename_'.$currentUsrID.'" value="" autocomplete="off" />';
	echo '<input type="hidden" name="action" value="save_universe" />';
	echo '</form>';
	echo '<script language="javascript">';
	echo "function linkToUni(optUni) {";
	echo 'if (optUni=="") return false;';
	echo 'values = optUni.split(/,/);';
	echo "document.getElementById('videospot_universe').value = values[0];";
	echo "document.getElementById('videospot_universename').value = values[1];";
	echo 'document.forms["universe"].submit();';
	echo "}";
	echo "</script>";
	if (empty($vsUniverse)) {
		echo "You are registered in multiple VideoSpot universes, please choose your working universe below<br />";
	} else {
		echo "You are currently using the VideoSpot universe <em><strong>".$vsUniverseName."</strong></em>, you can change your working universe below<br />";
	}
	echo '<select autocomplete="off" name="universe" size="1" onchange="linkToUni(this.options[this.selectedIndex].value);">';
	echo '<option style="font-weight: bold;" value="">Universe...</option>';
	foreach ($allUniverses as $universe) {
		echo '<option value="'.$universe.','.$allUniversesNames[$i].'"';
			if ($universe==$vsUniverse) {
				echo ' selected="selected"';
			}
		echo '>»&nbsp;'.$allUniversesNames[$i].'</option>';
		$i++;
	}
	echo "</select>";
	if (empty($vsUniverse)) {
		exit(); // Can't do anything until Universe set
	} else {
		$usrUniverse = $vsUniverse;
	}

}


//////// Deal with folders ////////

?>
	<h2>VideoSpot Folder</h2>
<?php

// Pick or create folder
$folderList = list_folders_videospot($vsServer, $sessionHash, $usrUniverse);
$folderList = substr($folderList, strpos($folderList, '<folders>')+9);
$folderList = substr($folderList, 0, strpos($folderList, '</folders>'));
$folderList = trim($folderList);
$folderName = "WordPress ".utf8_encode(get_bloginfo('name'));
if ( empty($folderList) || (strpos($folderList, 'FullPath="'.$folderName.'"')===false) ) {
	$newFolder = create_folder_videospot($vsServer, $sessionHash, $usrUniverse);
	if (strpos($newFolder, 'FullPath="'.$folderName.'"')!==false) {
		echo "Folder <em><strong>".$folderName."</strong></em> successfully created<br />";
	} else {
		echo "Could not create <em><strong>".$folderName."</strong></em>, please contact your VideoSpot administrator";
		exit();
	}
	$folderList = $newFolder ;
}
echo "Using VideoSpot folder <em><strong>".$folderName;
$arrayFolders = explode("<", $folderList);
$folderID="";
foreach ($arrayFolders as $folder) {
	if (strpos($folder, 'FullPath="'.$folderName.'"')!==false) {
		$folderID = substr($folder, strpos($folder, 'ID="')+4);
		$folderID = substr($folderID, 0, strpos($folderID, '"'));
	}
}
echo " (Folder ID ".$folderID.")</strong></em>";
$optionName = 'videospot_folder';
if (!add_option($optionName, $folderID)) {
	update_option($optionName, $folderID);
}


//////// More admin page stuff ////////
?>
	<h2>Clip Options</h2>
	<div class="content_options">
		<form method="post" autocomplete="off">
			<input name="blockautocomplete" style="display: none;" type="password" />
			<?php foreach ($videospotOptions as $value) { ?>
					<div class="input_section">
						<div class="input_title">
							<h4><?php echo $value['name']; ?></h4>
							<div class="clearfix"></div>
						</div>
						<div class="option_input">
							<input type="<?php echo $value['type']; ?>" name="<?php echo $value['id']; ?>" value="<?php if ( get_settings( $value['id'] ) != "") { echo stripslashes(get_settings( $value['id'])  ); } else { echo $value['std']; } ?>" autocomplete="<?php echo $value['autocomplete']; ?>" />
							<small><?php echo $value['desc']; ?></small>
							<div class="clearfix"></div>
						</div>
					</div>
					<?php
			}?>
			<p style="padding: 0px !important; margin: 20px 0px !important;" class="submit"><input name="save" type="submit" class="button-primary" value="Save changes" /></p>
			<input type="hidden" name="action" value="save" />
		</form>
		<form method="post">
			<p style="padding: 0px !important; margin: 20px 0px 30px !important;" class="submit">
				<input name="reset" type="submit" class="button-secondary" value="Reset clip options" />
				<input type="hidden" name="action" value="reset" />
			</p>
		</form>
	</div>
<?php


//////// Universal account ////////
?>
	<h2>Universal Account</h2>
	<div class="content_options">
		<form method="post" autocomplete="off">
			<input autocomplete="off" type="checkbox" name="videospot_universalaccount" value="<?php echo $currentUsrID; ?>" <?php if ($vsUniversalAccount==$currentUsrID) echo 'checked="checked"' ?> /> This setting will make your VideoSpot account universal and will disable all the other VideoSpot accounts setup on this platform
			<p style="padding: 0px !important; margin: 20px 0px 30px !important;" class="submit"><input name="save" type="submit" value="Save changes" class="button-primary" /></p>
			<input type="hidden" name="action" value="save_universal" />
		</form>
	</div>

	<div class="footer-credit">
		<p>Copyright <a title="TC Partners - VideoSpot" href="http://www.videospot.com" target="_blank" >TC Partners / VideoSpot</a></p>
	</div>
</div>
