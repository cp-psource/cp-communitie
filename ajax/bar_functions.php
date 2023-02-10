<?php

include_once('../../../../wp-config.php');

global $wpdb, $current_user;
wp_get_current_user();

// Change online status
if ($_POST['action'] == 'cpcommunitie_status') {

	global $wpdb, $current_user;
   	$status = $_POST['status'];
   	
   	if ($status == 'true') {

		__cpc__update_meta($current_user->ID, 'status', 'offline');

   	} else {

		__cpc__update_meta($current_user->ID, 'status', '');

   	}
   	
   	echo "OK";
   	exit;
	
}


// Get friends online
if ($_POST['action'] == 'cpcommunitie_getfriendsonline') {
	
	global $wpdb, $current_user;

	if (is_user_logged_in()) {

	   	$inactive = $_POST['inactive'];
	   	$offline = $_POST['offline'];
	   	$me = $current_user->ID;
		$time_now = time();
		$use_chat = $_POST['use_chat'];
		$friends_online = 0;
		$plugin = CPC_PLUGIN_URL;
   	
	   	$return = '';
		
		$get_all = !get_option(CPC_OPTIONS_PREFIX.'_cpc_panel_all');

		if ($get_all) {
			$sql = "SELECT u.ID, u.display_name
				FROM ".$wpdb->base_prefix."users u
				LEFT JOIN ".$wpdb->base_prefix."cpcommunitie_friends f ON u.ID = f.friend_to WHERE
				   f.friend_accepted = 'on' AND f.friend_from = ".$me;
		} else {
			$sql = "SELECT u.ID, u.display_name
				FROM ".$wpdb->base_prefix."users u
				WHERE u.ID != ".$me;
		}	

		$friends_list = $wpdb->get_results($sql);

		if ($friends_list) {
			$friends_array = array();
			foreach ($friends_list as $friend) {

				$add = array (	
					'ID' => $friend->ID,
					'display_name' => $friend->display_name,
					'last_activity' => __cpc__get_meta($friend->ID, 'last_activity'),
					'status' => __cpc__get_meta($friend->ID, 'status')
				);
				
				array_push($friends_array, $add);
			}
			$friends = __cpc__sub_val_sort($friends_array, 'last_activity', false);
			
		} else {
			
			$friends = false;
		}

		if ($friends) {			
			foreach ($friends as $friend) {
			
				$time_now = time();
				if ($friend['last_activity'] && $friend['status'] != 'offline') {
					$last_active_minutes = __cpc__convert_datetime($friend['last_activity']);
					$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
				} else {
					$last_active_minutes = 999999999;
				}
	
				if (!get_option(CPC_OPTIONS_PREFIX.'cpc_panel_offline') && ($last_active_minutes >= $offline)) {
					// Don't include offline members, and this member is offline
				} else {

					$return .= "<div style='clear:both; margin-top:4px; overflow: auto;overflow-y:hidden;'>";		
						$return .= "<div style='float: left; width:15px; padding-left:4px;'>";
							if ($last_active_minutes >= $offline) {
								$return .= "<img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/loggedout.gif' alt='Logged Out'>";
							} else {
								$friends_online++;
								if ($last_active_minutes >= $inactive) {
									$return .= "<img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/inactive.gif' alt='Inactive'>";
								} else {
									$return .= "<img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/online.gif' alt='Online'>";
								}
							}
						$return .= "</div>";
						$return .= "<div>";
							if ( $use_chat != 'on' || get_option(CPC_OPTIONS_PREFIX.'_cpc_lite') ) {
								if (function_exists('__cpc__profile')) {	
									$return .= "<a class='__cpc__offline_name' href='".__cpc__get_url('profile')."?uid=".$friend['ID']."'>";
									$return .= "<span title='".$friend['ID']."'>".$friend['display_name']."</span>";
									$return .= "</a>";
								}
							} else {
								$return .= "<a href='javascript:void(0);' alt='".$friend['ID']."|".$friend['display_name']."' class='__cpc__online_name __cpc__chat_user' title='".$friend['ID']."'>".$friend['display_name']."</a>";
							}
						$return .= "</div>";
					$return .= "</div>";
				}
			}
		}

		echo $friends_online."[split]".$return;
		
	}
	
	exit;
	
}

// Get friend requests
if ($_POST['action'] == 'cpcommunitie_friendrequests') {

   	global $wpdb, $current_user;	
   	$me = $current_user->ID;

	if (is_user_logged_in()) {

		$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."cpcommunitie_friends f WHERE f.friend_to = %d AND f.friend_accepted != 'on'";
		$pending = $wpdb->get_var($wpdb->prepare($sql, $me));
	
		echo $pending;
		
	}
	
	exit;

}

// Get count of unread mail
if ($_POST['action'] == 'cpcommunitie_getunreadmail') {

   	global $wpdb, $current_user;	

	if (is_user_logged_in()) {

	   	$me = $current_user->ID;
	   	$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix.'cpcommunitie_mail'." WHERE mail_to = %d AND mail_in_deleted != 'on' AND mail_read != 'on'";
		$unread_in = $wpdb->get_var($wpdb->prepare($sql, $me));
	
		echo $unread_in;
		
	}
	
	exit;
}



?>
