<?php
/*
CP Community Notification Alerts
Description: Updates a menu item (or DIV) with alerts/notifications for the logged in member.
*/


/* ====================================================================== MAIN =========================================================================== */

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

function __cpc__news_main() {
	// This function is used to information Wordpress that it is activated.
	// Ties in with __cpc__add_news_to_admin_menu() function below.		
}

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

function __cpc__news_add($author, $subject, $news) {

	global $wpdb,$current_user;

	if (	$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."cpcommunitie_news
			( 	author,
				subject, 
				added,
				news
			)
			VALUES ( %d, %d, %s, %s )", 
	        array(
	        	$author,
				$subject, 
	        	date("Y-m-d H:i:s"),
	        	$news
	        	) 
        	) ) 
	) {
		return "OK";
	} else { 
		return $wpdb->last_query;
	}

}


/* ===================================================================== ADMIN =========================================================================== */


// ----------------------------------------------------------------------------------------------------------------------------------------------------------

function __cpc__news_init()
{
	if (!is_admin()) {
	}
}
function __cpc__add_news_footer() {
	echo '<div id="__cpc__news_polling" style="display:none">'.get_option(CPC_OPTIONS_PREFIX."_news_polling").'</div>';
}
add_action('init', '__cpc__news_init');
add_action('wp_footer', '__cpc__add_news_footer');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

/* ====================================================== HOOKS/FILTERS INTO WORDPRESS/CP Community ====================================================== */

// Add "Alerts" to admin menu via hook
function __cpc__add_news_to_admin_menu()
{
	$hidden = get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('cpcommunitie_debug'.$hidden, __('Alerts', 'cp-communitie'), __('Alerts', 'cp-communitie'), 'manage_options', CPC_DIR.'/news_admin.php');
}
add_action('__cpc__admin_menu_hook', '__cpc__add_news_to_admin_menu');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

function __cpc__news_offsets() {
	// Place Alerts offset settings in DOM so accessible via Javascript
 	echo "<div id='__cpc__news_x_offset' style='display:none'>".get_option(CPC_OPTIONS_PREFIX."_news_x_offset")."</div>";
	echo "<div id='__cpc__news_y_offset' style='display:none'>".get_option(CPC_OPTIONS_PREFIX."_news_y_offset")."</div>";
}
add_action('wp_footer', '__cpc__news_offsets');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add [cpcommunitie-alerts] shortcode for history of news items
function __cpc__alerts_history($attr) {	

	global $wpdb, $current_user;
	$html = "";
	
	if (is_user_logged_in()) {

		// Get link to profile page
		$profile_url = __cpc__get_url('profile');
		if (strpos($profile_url, '?') !== FALSE) {
			$q = "&";
		} else {
			$q = "?";
		}
		
		$limit = isset($attr['count']) ? $attr['count'] : 50;

	
		// Wrapper
		$html .= "<div class='__cpc__wrapper'>";

		$sql = "SELECT n.*, u.display_name FROM ".$wpdb->base_prefix."cpcommunitie_news n 
			LEFT JOIN ".$wpdb->base_prefix."users u ON n.author = u.ID 
			WHERE subject = %d 
			ORDER BY added DESC LIMIT 0,%d";
		$news = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $limit));

		$shown_heading_today = $shown_heading_yesterday = $shown_heading_recent = $shown_heading_lastweek = $shown_heading_thismonth = $shown_heading_lastmonth = $shown_heading_old = false;
		
		if ($news) {
			foreach ($news as $item) {

				$date = strtotime($item->added);
				$difference = (time() - $date) + 1;
				$days = floor($difference/86400);
				$months = floor($difference/2628000);
	
				$heading = '';
				if (!$shown_heading_today && $days == 0) {
					$heading = __('Heute', 'cp-communitie');
					$shown_heading_today = true;
				}
				if (!$shown_heading_yesterday && $days == 1) {
					$heading = __('Gestern', 'cp-communitie');
					$shown_heading_yesterday = true;
				}
				if (!$shown_heading_recent && $days >= 2 && $days <= 6) {
					$heading = __('Kürzlich', 'cp-communitie');
					$shown_heading_recent = true;
				}
				if (!$shown_heading_lastweek && $days >= 7 && $days <= 13) {
					$heading = __('Letzte Woche', 'cp-communitie');
					$shown_heading_lastweek = true;
				}
				if (!$shown_heading_thismonth && $days >= 14 && $months == 0) {
					$heading = __('Diesen Monat', 'cp-communitie');
					$shown_heading_thismonth = true;
				}
				if (!$shown_heading_lastmonth && $months == 1) {
					$heading = __('Letzten Monat', 'cp-communitie');
					$shown_heading_lastmonth = true;
				}
				if (!$shown_heading_old && $months > 1) {
					$heading = __('Alt', 'cp-communitie');
					$shown_heading_old = true;
				}
					
				if ($heading) {
					$html .= "<div class='topic-post-header' style='margin-bottom:10px'>";
						$html .= $heading;
					$html .= "</div>";
				}
				
				$html .= "<div class='__cpc__news_history_row'>";
					$html .= "<div class='__cpc__news_history_avatar'>";
					$html .= '<a href="'.$profile_url.$q.'uid='.$item->author.'">'.get_avatar($item->author, 40).'</a>';
					$html .= '</div>';
					$html .= "<div class='__cpc__news_history_avatar'>";
					$html .= $item->news;
					$html .= "<br /><span class='__cpc__news_history_ago'>".__cpc__time_ago($item->added)."</span>";
					$html .= ' '.__('von', 'cp-communitie').' <a href="'.$profile_url.$q.'uid='.$item->author.'">'.stripslashes($item->display_name).'</a>';
					$html .= "</div>";
				$html .= "</div>";
			}
		} else {

			$html .= __("Noch nichts zu zeigen.", 'cp-communitie');

		}
		$html .= "</div>";
		// End Wrapper
	
		$html .= "<div style='clear: both'></div>";
	
		// Clear read news items
		$wpdb->query("UPDATE ".$wpdb->base_prefix."cpcommunitie_news SET new_item = '' WHERE subject = ".$current_user->ID);

	} else {
		
		$html .= __("Bitte einloggen, danke.", 'cp-communitie');
		
	}

	// Send HTML
	return $html;

}
if (!is_admin()) {
	add_shortcode(CPC_SHORTCODE_PREFIX.'-alerts', '__cpc__alerts_history');  
}

/* ====================================================== ALERTS (if available) ====================================================== */


// Add news item that a poke was sent
function __cpc__send_poke($message_to, $message_from, $from_name, $poke, $cid) {
	$url = __cpc__get_url('profile');
	$message = $from_name.__(' hat dir geschickt ', 'cp-communitie').$poke;
	__cpc__news_add($message_from, $message_to, "<a href='".$url.__cpc__string_query($url)."uid=".$message_from."&post=".$cid."'>".$message."</a>");
}
add_filter('__cpc__send_poke_filter', '__cpc__send_poke', 10, 5);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that mail was sent
function __cpc__news_add_message($message_to, $message_from, $from_name, $mail_id) {
	$url = __cpc__get_url('mail');
	__cpc__news_add($message_from, $message_to, "<a href='".$url.__cpc__string_query($url)."mid=".$mail_id."'>".__("Du hast eine neue Nachricht von", 'cp-communitie')." ".$from_name."</a>");
}
add_filter('__cpc__sendmessage_filter', '__cpc__news_add_message', 10, 4);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that friend request was made
function __cpc__news_add_friendrequest($message_to, $message_from, $from_name) {
	$url = __cpc__get_url('profile');
	__cpc__news_add($message_from, $message_to, "<a href='".$url.__cpc__string_query($url)."view=friends'>".sprintf(__("Neue %s Anfrage von", 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friend'))." ".$from_name."</a>");
}
add_filter('__cpc__friendrequest_filter', '__cpc__news_add_friendrequest', 10, 3);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that friend request was accepted
function __cpc__news_add_friendaccepted($message_to, $message_from, $from_name) {
	$url = __cpc__get_url('profile');
	__cpc__news_add($message_from, $message_to, "<a href='".$url.__cpc__string_query($url)."view=friends'>".sprintf(__("%s Anfrage akzeptiert von", 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friend'))." ".$from_name."</a>");
}
add_filter('__cpc__friendaccepted_filter', '__cpc__news_add_friendaccepted', 10, 3);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new forum topic posted (when subscribed)
function __cpc__news_add_newtopic($message_to, $from_id, $from_name, $url) {
	__cpc__news_add($from_id, $message_to, "<a href='".$url."'>".__("Abonniertes Forumsthema von", 'cp-communitie')." ".$from_name."</a>");
}
add_filter('__cpc__forum_newtopic_filter', '__cpc__news_add_newtopic', 10, 4);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new forum reply posted (when subscribed)
function __cpc__news_add_newreply($message_to, $message_from, $from_name, $url) {
	if ($message_to != $message_from) {
		__cpc__news_add($message_from, $message_to, "<a href='".$url."'>".__("Abonnierte Forumantwort von", 'cp-communitie')." ".$from_name."</a>");
	}
}
add_filter('__cpc__forum_newreply_filter', '__cpc__news_add_newreply', 10, 4);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new forum reply comment added (when subscribed)
function __cpc__news_add_newreplycomment($message_to, $message_from, $from_name, $url) {
	if ($message_to != $message_from) {
		__cpc__news_add($message_from, $message_to, "<a href='".$url."'>".__("Abonnierter Forumskommentar von", 'cp-communitie')." ".$from_name."</a>");
	}
}
add_filter('__cpc__forum_newreplycomment_filter', '__cpc__news_add_newreplycomment', 10, 4);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new post has been posted on member's profile
function __cpc__news_add_wall_newpost($post_to, $post_from, $from_name) {
	if ($post_to != $post_from) {
		__cpc__news_add($post_from, $post_to, "<a href='".__cpc__get_url('profile')."'>".$from_name." ".__("auf Deinem Profil gepostet hat.", 'cp-communitie')."</a>");
	}
}
add_filter('__cpc__wall_newpost_filter', '__cpc__news_add_wall_newpost', 10, 3);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new comment has been added as a reply to a post on member's profile
function __cpc__news_add_wall_reply($first_post_subject, $first_post_author, $from_id, $from_name, $url) {
	global $current_user;

	if ($first_post_subject != $current_user->ID) {
		__cpc__news_add($from_id, $first_post_subject, "<a href='".$url."'>".$from_name." ".__("hat auf einen Beitrag in Deinem Profil geantwortet", 'cp-communitie')."</a>");
	} else {
		if ($first_post_author != $current_user->ID) {
			__cpc__news_add($from_id, $first_post_author, "<a href='".$url."'>".$from_name." ".__("hat auf einen von Dir gestarteten Beitrag geantwortet", 'cp-communitie')."</a>");
		}
	}

}
add_filter('__cpc__wall_postreply_filter', '__cpc__news_add_wall_reply', 10, 5);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new comment has been added as a reply to a post this member is involved in
function __cpc__news_add_wall_reply_involved_in($post_to, $post_from, $from_name, $url) {
	if ($post_to != $post_from) {
		__cpc__news_add($post_from, $post_to, "<a href='".$url."'>".$from_name." ".__("hat auf einen Beitrag geantwortet, an dem Du beteiligt bist", 'cp-communitie')."</a>");
	}
}
add_filter('__cpc__wall_postreply_involved_filter', '__cpc__news_add_wall_reply_involved_in', 10, 4);



?>
