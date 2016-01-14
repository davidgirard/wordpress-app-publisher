<?php
/*
Plugin Name: VideoSpot App Publisher
Plugin URI: https://github.com/videospot/wordpress-app-publisher
Description: Publish your Wordpress posts and pages to VideoSpot
Version: 0.0.2
Date: 2016-01-07
Author: TCPartners
Author URI: http://www.tcpartners.fr
Licence:
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

// Declare globals
global $videospotLogin, $videospotUniverse, $videospotOptions;

// Main function to push content to VideoSpot via WP
function videospot_endpoint() {
	add_rewrite_endpoint('videospot-post', EP_ROOT);
}
add_action('init', 'videospot_endpoint');
function videospot_parse_query($query) {
	if (isset($query->query_vars['videospot-post'])) {
		include(plugin_dir_path(__FILE__).'includes/remote-post.inc');
		exit;
	}
}
add_action('parse_query','videospot_parse_query');

// Display VideoSpot options page for admins
function videospot_menu_page(){
	include(plugin_dir_path(__FILE__).'includes/admin-page.inc');
}

// Register admin options page with WP
function register_videospot_menu_page(){
	add_menu_page('VideoSpot Options', 'VideoSpot', 'manage_options', 'videospotpage', 'videospot_menu_page', plugin_dir_url( __FILE__ ).'static/media/logo.png', 90);
}
add_action('admin_menu', 'register_videospot_menu_page');

// Create remote post button on edit page
function videospot_button() {
	$currentUsrID = wp_get_current_user()->ID;
	$vsUniversalAccount = get_option("videospot_universalaccount"); // Override user ID if universal account is setup
	if (!empty($vsUniversalAccount)) {
		$currentUsrID = $vsUniversalAccount;
	}
	$vsServer = get_option("videospot_server_".$currentUsrID);
	$vsUsr = get_option("videospot_usr_".$currentUsrID);
	$vsPasswd = get_option("videospot_passwd_".$currentUsrID);
	$vsUniverse = get_option("videospot_universe_".$currentUsrID);
	$vsUniverseName = get_option("videospot_universename_".$currentUsrID);
	$vsDurationHours = get_option("videospot_durationhours_".$currentUsrID);
	$vsDurationMinutes = get_option("videospot_durationminutes_".$currentUsrID);
	$vsDurationSeconds = get_option("videospot_durationseconds_".$currentUsrID);
	$sessionTok = wp_generate_password(24, false); // Set or update session token
	$optionName = 'videospot_token_'.get_the_ID();
	if (!add_option($optionName, $sessionTok)) {
		update_option($optionName, $sessionTok);
	}
	$vsName = get_option("videospot_name_".$currentUsrID);
	$buttonShow = '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#post").submit(function(event) {  
						jQuery("#printArea").empty().append("<img src=\'/wp-admin/images/wpspin_light-2x.gif\' height=\'16\' width=\'16\' /> Sending data to VideoSpot, please wait");
						event.preventDefault();
						jQuery.ajax({
							type     : "POST",
							cache    : false,
							url: "'.get_site_url().'?videospot-post&vstok='.$sessionTok.'&usrid='.$currentUsrID.'",
							data     : jQuery(this).serializeArray(),
							success  : function(data) {
								jQuery("#printArea").empty().append(data);
							}
						});
					});
				});   
			</script>';
	if ( !empty($currentUsrID) && !empty($vsServer) && !empty($vsUsr) && !empty($vsPasswd) && !empty($vsUniverse) && !empty($vsName) && ( !empty($vsDurationHours) ||  !empty($vsDurationMinutes) ||  !empty($vsDurationSeconds) ) ) {
		$buttonShow .= '<div id="major-publishing-actions" style="overflow:hidden">';
		$buttonShow .= '<div id="publishing-action" style="text-align:center">';
		$buttonShow .= '<input class="button-primary" value="Send to VideoSpot" name="publish" type="submit" method="post" formaction="'.get_site_url().'?videospot-post&vstok='.$sessionTok.'&usrid='.$currentUsrID.'">';
		$buttonShow .= '<div id="printArea" style="margin-top:5px;"><strong>Note:</strong> Publish/update your post in WordPress before publishing to VideoSpot</div>';
		$buttonShow .= '</div>';
		$buttonShow .= '</div>';
		echo $buttonShow;
	} else {
		$buttonShow .= '<div id="major-publishing-actions" style="overflow:hidden">';
		$buttonShow .= '<div id="publishing-action" style="text-align:left"><em><strong>Warning:</strong></em> Please configure your VideoSpot parameters <a href="'.admin_url().'?page=videospotpage">here</a> to be able to post to VideoSpot';
		$buttonShow .= '</div>';
		$buttonShow .= '</div>';
		echo $buttonShow;
	}
}
add_action('post_submitbox_misc_actions', 'videospot_button');

?>
