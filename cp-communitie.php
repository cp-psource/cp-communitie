<?php
/*
Plugin Name: CP Community
Plugin URI: https://n3rds.work
Description: Turn your ClassicPress site into a social network. Activate features on the Installation page.
Version: 1.5.9
Author: DerN3rd
Author URI: https://n2rds.work
Domain Path: /languages
Text Domain: cp-communitie
License: GPL3
*/

/* Please see licence.txt for End User Licence Agreement */
 
/* ====================================================== SETUP ====================================================== */


// Get constants
require_once(dirname(__FILE__).'/default-constants.php');
include_once(dirname(__FILE__).'/functions.php');
include_once(dirname(__FILE__).'/hooks_filters.php');


global $wpdb, $current_user;

// Set version
define('CPC_VER', '1.5.9');

// Load activated sub-plugins
require_once(dirname(__FILE__).'/widgets.php');
require_once(dirname(__FILE__).'/yesno.php');
	
// Load optionally activated sub-plugins (via Installation page)
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__forum_activated') 				|| get_option(CPC_OPTIONS_PREFIX.'__cpc__forum_network_activated')) 				&& file_exists(dirname(__FILE__).'/forum.php')) 		require_once(dirname(__FILE__).'/forum.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__profile_activated') 				|| get_option(CPC_OPTIONS_PREFIX.'__cpc__profile_network_activated'))				&& file_exists(dirname(__FILE__).'/profile.php')) 		require_once(dirname(__FILE__).'/profile.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__mail_activated')					|| get_option(CPC_OPTIONS_PREFIX.'__cpc__mail_network_activated'))					&& file_exists(dirname(__FILE__).'/mail.php')) 			require_once(dirname(__FILE__).'/mail.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__members_activated') 				|| get_option(CPC_OPTIONS_PREFIX.'__cpc__members_network_activated'))				&& file_exists(dirname(__FILE__).'/members.php')) 		require_once(dirname(__FILE__).'/members.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__add_notification_bar_activated') || get_option(CPC_OPTIONS_PREFIX.'__cpc__add_notification_bar_network_activated'))	&& file_exists(dirname(__FILE__).'/panel.php')) 		require_once(dirname(__FILE__).'/panel.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__events_main_activated') 			|| get_option(CPC_OPTIONS_PREFIX.'__cpc__events_main_network_activated'))			&& file_exists(dirname(__FILE__).'/events.php')) 		require_once(dirname(__FILE__).'/events.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__facebook_activated')				|| get_option(CPC_OPTIONS_PREFIX.'__cpc__facebook_network_activated'))				&& file_exists(dirname(__FILE__).'/facebook.php')) 		require_once(dirname(__FILE__).'/facebook.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__gallery_activated') 				|| get_option(CPC_OPTIONS_PREFIX.'__cpc__gallery_network_activated'))				&& file_exists(dirname(__FILE__).'/gallery.php')) 		require_once(dirname(__FILE__).'/gallery.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__groups_activated') 				|| get_option(CPC_OPTIONS_PREFIX.'__cpc__groups_network_activated'))				&& file_exists(dirname(__FILE__).'/groups.php')) 		require_once(dirname(__FILE__).'/groups.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__lounge_main_activated') 			|| get_option(CPC_OPTIONS_PREFIX.'__cpc__lounge_main_network_activated')) 			&& file_exists(dirname(__FILE__).'/lounge.php')) 		require_once(dirname(__FILE__).'/lounge.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__news_main_activated') 			|| get_option(CPC_OPTIONS_PREFIX.'__cpc__news_main_network_activated'))				&& file_exists(dirname(__FILE__).'/news.php')) 			require_once(dirname(__FILE__).'/news.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__profile_plus_activated') 		|| get_option(CPC_OPTIONS_PREFIX.'__cpc__profile_plus_network_activated'))			&& file_exists(dirname(__FILE__).'/plus.php')) 			require_once(dirname(__FILE__).'/plus.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__rss_main_activated') 			|| get_option(CPC_OPTIONS_PREFIX.'__cpc__rss_main_network_activated'))				&& file_exists(dirname(__FILE__).'/rss.php')) 			require_once(dirname(__FILE__).'/rss.php');
if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__mailinglist_activated') 			|| get_option(CPC_OPTIONS_PREFIX.'__cpc__mailinglist_network_activated'))			&& file_exists(dirname(__FILE__).'/mailinglist.php'))	require_once(dirname(__FILE__).'/mailinglist.php');

// Actions that are loaded before ClassicPress can check on page content
add_action('init', '__cpc__scriptsAction');
add_action('init', '__cpc__languages');
add_action('init', '__cpc__js_init');

// Front end actions (includes check if required)
add_action('wp_head', '__cpc__header', 10);
add_action('wp_footer', '__cpc__concealed_avatar', 10);
add_action('template_redirect', '__cpc__replace');
add_action('wp_head', '__cpc__add_stylesheet');

// Following required whether features on the page or not
add_action('wp_login', '__cpc__login');
add_action('init', '__cpc__notification_setoptions');
add_action('wp_footer', '__cpc__lastactivity', 10);
add_action('wp_footer', '__cpc__dialogs', 10);

function __cpc__dialogs() {

	// Dialog
	echo "<div id='dialog' style='display:none'></div>";	
	echo "<div class='__cpc__notice' style='display:none; z-index:999999;'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/busy.gif' /> ".__('Saving...', 'cp-communitie')."</div>";
	echo "<div class='__cpc__pleasewait' style='display:none; z-index:999999;'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/busy.gif' /> ".__('Bitte warte...', 'cp-communitie')."</div>";	
	echo "<div class='__cpc__sending' style='display:none; z-index:999999;'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/busy.gif' /> ".__('Sending...', 'cp-communitie')."</div>";	

	// Make a note of the "nobody" user (for access from Javascript)
	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info ? $user_info->ID : 0;
	echo "<div id='nobody_user' style='display:none'>".$nobody_id."</div>";
	
}
// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Used in ClassicPress admin
if (is_admin()) {
	include(dirname(__FILE__).'/menu.php');
	add_filter('admin_footer_text', '__cpc__footer_admin');
	add_action('admin_notices', '__cpc__admin_warnings');
	if (!CPC_HIDE_DASHBOARAD_W) add_action('wp_dashboard_setup', '__cpc__dashboard_widget');	
	add_action('init', '__cpc__admin_init');
	// deactivation
	register_deactivation_hook(__FILE__, '__cpc__deactivate');

}

/* ===================================================== ADMIN ====================================================== */	

// Check for updates
if ( ( get_option(CPC_OPTIONS_PREFIX."_version") != CPC_VER && is_admin()) || (isset($_GET['force_create_cpc']) && $_GET['force_create_cpc'] == 'yes' && is_admin())) {

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	// Create initial versions of tables *************************************************************************************

	$wpdb->show_errors();
	
	include('create_tables.php');
	include('create_options.php');


	
	// Update motd flags
	update_option(CPC_OPTIONS_PREFIX.'_motd', '');
	update_option(CPC_OPTIONS_PREFIX.'_reminder', '');
	update_option(CPC_OPTIONS_PREFIX."_install_assist", false);

	// Setup Notifications
	__cpc__notification_setoptions();
	
	// ***********************************************************************************************
 	// Update Versions *******************************************************************************
	update_option(CPC_OPTIONS_PREFIX."_version", CPC_VER);

		
}

// Does the current page feature CPC?
function __cpc__required() {
	
	// Using panel?
	if (function_exists('__cpc__add_notification_bar'))
		return true;

	// Page/post contains shortcode?
	global $post;
	if ($post) {
		$content = $post->post_content;	
		if (strpos($content, '[cpcommunitie-') !== FALSE)
			return true;
		
		if (get_option(CPC_OPTIONS_PREFIX.'_always_load')) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// Any admin warnings
function __cpc__admin_warnings() {

   	global $wpdb; 	

	// CSS check
    $myStyleFile = CPC_PLUGIN_DIR . '/css/'.get_option(CPC_OPTIONS_PREFIX.'_cpc_css_file');
    if ( !file_exists($myStyleFile) ) {
		echo "<div class='error'><p>".CPC_WL.": ";
		_e( sprintf('Stylesheet (%s) not found.', $myStyleFile), 'cp-communitie');
		echo "</p></div>";
    }

	// JS check
    $myJSfile = CPC_PLUGIN_DIR . '/js/'.get_option(CPC_OPTIONS_PREFIX.'_cpc_js_file');
    if ( !file_exists($myJSfile) ) {
		echo "<div class='error'><p>".CPC_WL.": ";
		_e( sprintf('Javascript file (%s) not found, please check <a href="admin.php?page=cpcommunitie_debug"></a>the installation page</a>.', $myJSfile), 'cp-communitie');
		echo "</p></div>";
    }

    // MOTDs
    if (get_option(CPC_OPTIONS_PREFIX.'_motd') != 'on' && (!(isset($_GET['page']) && $_GET['page'] == 'cpcommunitie_welcome'))) {

		if ( current_user_can( 'edit_theme_options' ) ) {   
			if (isset($_POST['cpcommunitie_hide_motd']) && $_POST['cpcommunitie_hide_motd'] == 'Y') {
				if (!isset($_POST['cpcommunitie_hide_motd_nonce']) || wp_verify_nonce($_POST['cpcommunitie_hide_motd_nonce'],'cpcommunitie_hide_motd_nonce'))
					update_option(CPC_OPTIONS_PREFIX.'_motd', 'on');
			} else {
				__cpc__plugin_welcome();
			}
		}
    }

    if (get_option(CPC_OPTIONS_PREFIX.'_reminder') != 'on' && (!(isset($_GET['page']) && $_GET['page'] == 'cpcommunitie_welcome'))) {

		if ( current_user_can( 'edit_theme_options' ) ) {   
			if (isset($_POST['cpcommunitie_hide_reminder']) && $_POST['cpcommunitie_hide_reminder'] == 'Y') {
				if (wp_verify_nonce($_POST['cpcommunitie_hide_reminder_nonce'],'cpcommunitie_hide_reminder_nonce'))
					update_option(CPC_OPTIONS_PREFIX.'_reminder', 'on');
			} else {
				__cpc__plugin_reminder();
			}
		}
    }
    		
	// Check for legacy plugin folders	    
	$list = '';
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-alerts')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-alerts<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-events')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-events<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-facebook')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-facebook<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-gallery')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-gallery<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-groups')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-groups<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-lounge')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-lounge<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-plus')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-plus<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-mailinglist')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-mailinglist<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-rss')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-rss<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/cp-communitie-yesno')) { $list .= WP_PLUGIN_DIR.'/cp-communitie-yesno<br />'; }
	if ($list != '') {
		echo '<div class="updated" style="margin-top:15px">';
		echo "<strong>".CPC_WL."</strong><br /><div style='padding:4px;'>";
		echo __('Bitte entferne die folgenden Ordner per FTP.<br />Entferne sie <strong>NICHT</strong> über die Admin-Seite des Plugins, da dies Daten aus Deiner Datenbank löschen könnte:', 'cp-communitie').'<br /><br />';
		echo $list;
		echo '</div></div>';
	}
    
}

// Dashboard Widget
function __cpc__dashboard_widget(){
	wp_add_dashboard_widget('cpcommunitie_id', CPC_WL, '__cpc__widget');
}
function __cpc__widget() {
	
	global $wpdb, $current_user;
	
	echo '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/logo_small.png" alt="Logo" style="float:right; width:120px;height:120px;" />';

	echo '<table><tr><td valign="top">';
	
		echo '<table>';
		echo '<tr><td colspan="2" style="padding:4px"><strong>'.__('Forum', 'cp-communitie').'</strong></td></tr>';
		echo '<tr><td style="padding:4px"><a href="admin.php?page=cpcommunitie_categories">'.__('Kategorien', 'cp-communitie').'</a></td>';
		echo '<td style="padding:4px">'.$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'cpcommunitie_cats').'</td></tr>';
		echo '<tr><td style="padding:4px">'.__('Topics', 'cp-communitie').'</td>';
		echo '<td style="padding:4px">'.$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'cpcommunitie_topics'." WHERE topic_parent = 0").'</td></tr>';
		echo '<tr><td style="padding:4px">'.__('Replies', 'cp-communitie').'</td>';
		echo '<td style="padding:4px">'.$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'cpcommunitie_topics'." WHERE topic_parent > 0").'</td></tr>';
		echo '<tr><td style="padding:4px">'.__('Views', 'cp-communitie').'</td>';
		echo '<td style="padding:4px">'.$wpdb->get_var("SELECT SUM(topic_views) FROM ".$wpdb->prefix.'cpcommunitie_topics'." WHERE topic_parent = 0").'</td></tr>';
		echo '<tr><td style="padding:4px">'.__('Mail', 'cp-communitie').'</td>';
		$mailcount = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix.'cpcommunitie_mail');
		$unread = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix.'cpcommunitie_mail'." WHERE mail_read != 'on'");
		echo '<td style="padding:4px">'.$mailcount.' ';
		printf (__('(%s ungelesen)', 'cp-communitie'), $unread);
		echo '</td></tr>';
		echo '</table>';
		
	echo "</td><td valign='top'>";

		echo '<table>';
			echo '<tr><td colspan="2" style="padding:4px"><strong>'.__('Plugins', 'cp-communitie').'</strong></td></tr>';
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__cpc__forum')) {
				echo '<a href="'.__cpc__get_url('forum').'">'.__('Gehe zum Forum', 'cp-communitie').'</a>';
			} else {
				echo __('Forum not activated', 'cp-communitie');
			}
			echo "</td></tr>";
			
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__cpc__profile')) {
				$url = __cpc__get_url('profile');
				echo '<a href="'.$url.__cpc__string_query($url).'uid='.$current_user->ID.'">'.__('Gehe zu Profil', 'cp-communitie').'</a>';
			} else {
				echo __('Profile not activated', 'cp-communitie');
			}
			echo "</td></tr>";
	
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__cpc__mail')) {
				echo '<a href="'.__cpc__get_url('mail').'">'.__('Gehe zu Mail', 'cp-communitie').'</a>';
			} else {
				echo __('Mail not activated', 'cp-communitie');
			}
			echo "</td></tr>";
			
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__cpc__members')) {
				echo '<a href="'.__cpc__get_url('members').'">'.__('Gehe zum Mitgliederverzeichnis', 'cp-communitie').'</a>';
			} else {
				echo __('Mitgliederverzeichnis nicht aktiviert', 'cp-communitie');
			}
			echo "</td></tr>";
			
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__cpc__group')) {
				echo '<a href="'.__cpc__get_url('groups').'">'.__('Gehe zum Gruppenverzeichnis', 'cp-communitie').'</a><br />';
			} else {
				echo __('Gruppen nicht aktiviert', 'cp-communitie');
			}
			echo "</td></tr>";
			
		echo "</table>";

	echo "</td></tr></table>";

}

function __cpc__deactivate() {

	wp_clear_scheduled_hook('cpcommunitie_notification_hook');
	delete_option('cpcommunitie_debug_mode');
	delete_option(CPC_OPTIONS_PREFIX."_version");

}

/* ====================================================== NOTIFICATIONS ====================================================== */

function __cpc__notification_setoptions() {
	update_option(CPC_OPTIONS_PREFIX."_notification_inseconds",86400);
	// 60 = 1 minute, 3600 = 1 hour, 10800 = 3 hours, 21600 = 6 hours, 43200 = 12 hours, 86400 = Daily, 604800 = Weekly
	/* This is where the actual recurring event is scheduled */
	if (!wp_next_scheduled('cpcommunitie_notification_hook')) {
		$dt=explode(':',date('d:m:Y',time()));
		$schedule=mktime(0,1,0,$dt[1],$dt[0],$dt[2])+86400;
		// set for 00:01 from tomorrow
		wp_schedule_event($schedule, "cpcommunitie_notification_recc", "cpcommunitie_notification_hook");
	}
}

/* a reccurence has to be added to the cron_schedules array */
add_filter('cron_schedules', '__cpc__notification_more_reccurences');
function __cpc__notification_more_reccurences($recc) {
	$recc['cpcommunitie_notification_recc'] = array('interval' => get_option(CPC_OPTIONS_PREFIX."_notification_inseconds"), 'display' => CPC_WL_SHORT.' Notification Schedule');
	return $recc;
}
	
/* This is the scheduling hook for our plugin that is triggered by cron */
function __cpc__notification_trigger_schedule() {
	__cpc__notification_do_jobs('cron');
}

/* This is called by the scheduled cron job, and by Health Check Daily Digest check */
function __cpc__notification_do_jobs($mode) {
	
	global $wpdb;
	$summary_email = __("Webseitentitel", 'cp-communitie').": ".get_bloginfo('name')."<br />";
	$summary_email .= __("Webadresse", 'cp-communitie').": ".get_bloginfo('wpurl')."<br />";
	$summary_email .= __("Admin-E-Mail", 'cp-communitie').": ".get_bloginfo('admin_email')."<br />";
	$summary_email .= __("ClassicPress-Version", 'cp-communitie').": ".get_bloginfo('version')."<br />";
	$summary_email .= sprintf(__("%s Version", 'cp-communitie'), CPC_WL).": ".CPC_VER."<br />";
	$summary_email .= __("Tägliche Zusammenfassung-Modus", 'cp-communitie').": ".$mode."<br /><br />";
	$topics_count = 0;
	$user_count = 0;
	$success = "INCOMPLETE. ";
		

	$users_sent_to_success = '';
	$users_sent_to_failed = '';
				
	// ******************************************* Daily Digest ******************************************
	$send_summary = get_option(CPC_OPTIONS_PREFIX.'_send_summary');
	if ($send_summary == "on" || $mode == 'cron' || $mode == 'cpcommunitie_dailydigest_admin' || $mode == 'send_admin_summary_and_to_users') {
		
		// Calculate yesterday			
		$startTime = mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
		$endTime = mktime(23, 59, 59, date('m'), date('d')-1, date('Y'));
		
		// Get all new topics from previous period
		$topics_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix.'cpcommunitie_topics'." WHERE topic_parent = %d AND UNIX_TIMESTAMP(topic_date) >= ".$startTime." AND UNIX_TIMESTAMP(topic_date) <= ".$endTime, 0));

		if ($topics_count > 0 || $mode == 'cpcommunitie_dailydigest_admin') {

			// Get Forum URL 
			$forum_url = __cpc__get_url('forum');
			// Decide on query suffix on whether a permalink or not
			if (strpos($forum_url, '?') !== FALSE) {
				$q = "&";
			} else {
				$q = "?";
			}

			$body = "";
			
			$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'cpcommunitie_cats'." ORDER BY listorder"); 
			if ($categories) {
				foreach ($categories as $category) {
					
					$shown_category = false;
					$topics = $wpdb->get_results("
						SELECT tid, topic_subject, topic_parent, topic_post, topic_date, display_name, topic_category 
						FROM ".$wpdb->prefix.'cpcommunitie_topics'." INNER JOIN ".$wpdb->base_prefix.'users'." ON ".$wpdb->prefix.'cpcommunitie_topics'.".topic_owner = ".$wpdb->base_prefix.'users'.".ID 
						WHERE topic_parent = 0 AND topic_category = ".$category->cid." AND UNIX_TIMESTAMP(topic_date) >= ".$startTime." AND UNIX_TIMESTAMP(topic_date) <= ".$endTime." 
						ORDER BY tid"); 
					if ($topics) {
						if (!$shown_category) {
							$shown_category = true;
							$body .= "<h1>".stripslashes($category->title)."</h1>";
						}
						$body .= "<h2>".__('Neue Themen', 'cp-communitie')."</h2>";
						$body .= "<ol>";
						foreach ($topics as $topic) {
							$body .= "<li><strong><a href='".$forum_url.$q."cid=".$category->cid."&show=".$topic->tid."'>".stripslashes($topic->topic_subject)."</a></strong>";
							$body .= " started by ".$topic->display_name.":<br />";																
							$body .= stripslashes($topic->topic_post);
							$body .= "</li>";
						}
						$body .= "</ol>";
					}

					$replies = $wpdb->get_results("
						SELECT tid, topic_subject, topic_parent, topic_post, topic_date, display_name, topic_category 
						FROM ".$wpdb->prefix.'cpcommunitie_topics'." INNER JOIN ".$wpdb->base_prefix.'users'." ON ".$wpdb->prefix.'cpcommunitie_topics'.".topic_owner = ".$wpdb->base_prefix.'users'.".ID 
						WHERE topic_parent > 0 AND topic_category = ".$category->cid." AND UNIX_TIMESTAMP(topic_date) >= ".$startTime." AND UNIX_TIMESTAMP(topic_date) <= ".$endTime."
						ORDER BY topic_parent, tid"); 
					if ($replies) {
						if (!$shown_category) {
							$shown_category = true;
							$body .= "<h1>".$category->title."</h1>";
						}
						$body .= "<h2>".__('Antworten ein', 'cp-communitie')." ".$category->title."</h2>";
						$current_parent = '';
						foreach ($replies as $reply) {
							$parent = $wpdb->get_var($wpdb->prepare("SELECT topic_subject FROM ".$wpdb->prefix.'cpcommunitie_topics'." WHERE tid = %d", $reply->topic_parent));
							if ($parent != $current_parent) {
								$body .= "<h3>".$parent."</h3>";
								$current_parent = $parent;
							}
							$body .= "<em>".$reply->display_name." wrote:</em> ";
							$post = __cpc__clean_html(stripslashes($reply->topic_post));							
							if (strlen($post) > 100) { $post = substr($post, 0, 100)."..."; }
							if (strpos($reply->topic_post, '<iframe src=\"https://www.youtube.com') !== FALSE)
								$post .= " (".__('video', 'cp-communitie').")";
							$body .= $post;
							$body .= " <a href='".$forum_url.$q."cid=".$category->cid."&show=".$topic->tid."'>".__('Thema anzeigen', 'cp-communitie')."...</a>";
							$body .= "<br />";
							$body .= "<br />";
						}						
					}	
				}
			}
			
			$body .= "<p>".__("Du kannst den Erhalt dieser E-Mails beenden unter", 'cp-communitie')." <a href='".$forum_url."'>".$forum_url."</a>.</p>";
			
			// Send the mail
			if (($mode == 'cron' && get_option(CPC_OPTIONS_PREFIX.'_send_summary') == "on") || $mode == 'send_admin_summary_and_to_users') {
				// send to all users
				$users = $wpdb->get_results("SELECT DISTINCT user_email 
				FROM ".$wpdb->base_prefix.'users'." u 
				INNER JOIN ".$wpdb->base_prefix."usermeta m ON u.ID = m.user_id 
				WHERE meta_key = 'cpcommunitie_forum_digest' and m.meta_value = 'on'"); 
				
				if ($users) {
					foreach ($users as $user) {
						$user_count++;
						$email = $user->user_email;
						if(__cpc__sendmail($email, __('Tägliche Forumszusammenfassung', 'cp-communitie'), $body)) {
							$users_sent_to_success .= $user->user_email.'<br />';
							update_option(CPC_OPTIONS_PREFIX."_notification_triggercount",get_option(CPC_OPTIONS_PREFIX."_notification_triggercount")+1);
						} else {
							$users_sent_to_failed .= $user->user_email.'<br />';
						}						
					}
				} else {
					$users_sent_to_success = __('Kein Benutzer hat ausgewählt, die Forumszusammenfassung zu erhalten.', 'cp-communitie').'<br />';
				}
			}
			if ($mode == 'cpcommunitie_dailydigest_admin') {
				// send to admin only
				if(__cpc__sendmail(get_bloginfo('admin_email'), __('Tägliche Forumszusammenfassung (nur Administrator)', 'cp-communitie'), $body)) {
					$users_sent_to_success .= get_bloginfo('admin_email').'<br />';
				} else {
					$users_sent_to_failed .= get_bloginfo('admin_email').'<br />';
				}										
			}

		}
	}
	
	// Send admin summary
	$summary_email .= __("Anzahl der Forenthemen für den Vortag (Mitternacht bis Mitternacht)", 'cp-communitie').": ".$topics_count."<br />";
	$summary_email .= __("Anzahl der gesendeten Täglichen Forumszusammenfassungen", 'cp-communitie').": ".$user_count."<br /><br />";
	$summary_email .= "<b>".__("Liste der Empfänger gesendet an:", 'cp-communitie')."</b><br />";
	if ($users_sent_to_success != '') {
	$summary_email .= $users_sent_to_success;
	} else {
		$summary_email .= 'None.';
	}
	$summary_email .= "<br /><br /><b>List of sent failures:</b><br />";
	if ($users_sent_to_failed != '') {
		$summary_email .= $users_sent_to_failed;
	} else {
		$summary_email .= 'None.';
	}
	$email = get_bloginfo('admin_email');
	if (__cpc__sendmail($email, __('Tägliche Forumszusammenfassung', 'cp-communitie'), $summary_email)) {
		$success = "OK<br />(summary sent to ".get_bloginfo('admin_email').")<br />";
	} else {
		$success = "FAILED sending to ".get_bloginfo('admin_email').". ";
	}
	
	return $success;
	
}

// Record last logged in and previously logged in 
function __cpc__login($user_login) {

	global $wpdb, $current_user;

	// Get ID for this user
	$sql = "SELECT ID from ".$wpdb->base_prefix."users WHERE user_login = %s";
	$id = $wpdb->get_var($wpdb->prepare($sql, $user_login));

	if (__cpc__get_meta($id, 'status') != 'offline') {
		// Get last time logged in
		$last_login = __cpc__get_meta($id, 'last_login');
		// And previous login
		$previous_login = __cpc__get_meta($id, 'previous_login');

		// Store as previous time last logged in
		if ($previous_login == NULL) {
			__cpc__update_meta($id, 'previous_login', "'".date("Y-m-d H:i:s")."'");
		} else {
			__cpc__update_meta($id, 'previous_login', "'".$last_login."'");
		}
		// Store this log in as the last time logged in
		__cpc__update_meta($id, 'last_login', "'".date("Y-m-d H:i:s")."'");

	}	
}

// Replace get_avatar 
if ( (get_option(CPC_OPTIONS_PREFIX.'_profile_avatars') == "on") && ( !function_exists('get_avatar') ) ) {

	function get_avatar( $id_or_email, $size = '96', $default = '', $alt = false, $link = true ) {

		global $wpdb, $current_user;
							
		if ( false === $alt)
			$safe_alt = '';
		else
			$safe_alt = esc_attr( $alt );
	
		if ( !is_numeric($size) )
			$size = '96';
	
		$email = '';
		$display_name = '';
		if ( is_numeric($id_or_email) ) {
			$id = (int) $id_or_email;
			$user = get_userdata($id);
			if ( $user )
				$email = $user->user_email;
		} elseif ( is_object($id_or_email) ) {
			// No avatar for pingbacks or trackbacks
			$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
			if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) )
				return false;
	
			if ( !empty($id_or_email->user_id) ) {
				$id = (int) $id_or_email->user_id;
				$user = get_userdata($id);
				if ( $user)
					$email = $user->user_email;
			} elseif ( !empty($id_or_email->comment_author_email) ) {
				$email = $id_or_email->comment_author_email;
			}
		} else {
			$id = $wpdb->get_var("select ID from ".$wpdb->base_prefix."users where user_email = '".$id_or_email."'");
		}
	
		if ( empty($default) ) {
			$avatar_default = get_option('avatar_default');
			if ( empty($avatar_default) )
				$default = 'mystery';
			else
				$default = $avatar_default;
		}
	
		if ( !empty($email) )
			$email_hash = md5( strtolower( $email ) );
	
		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com';
		} else {
			if ( !empty($email) )
				$host = sprintf( "https://%d.gravatar.com", ( hexdec( $email_hash[0] ) % 2 ) );
			else
				$host = 'https://0.gravatar.com';
		}
	
		if ( 'mystery' == $default )
			$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
		elseif ( 'blank' == $default )
			$default = includes_url('images/blank.gif');
		elseif ( !empty($email) && 'gravatar_default' == $default )
			$default = '';
		elseif ( 'gravatar_default' == $default )
			$default = "$host/avatar/s={$size}";
		elseif ( empty($email) )
			$default = "$host/avatar/?d=$default&amp;s={$size}";
		elseif ( strpos($default, 'http://') === 0 )
			$default = add_query_arg( 's', $size, $default );
			
		if ( !empty($email) ) {
			$out = "$host/avatar/";
			$out .= $email_hash;
			$out .= '?s='.$size;
			$out .= '&amp;d=' . urlencode( $default );
	
			$rating = get_option('avatar_rating');
			if ( !empty( $rating ) )
				$out .= "&amp;r={$rating}";
	
			$avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
		} else {
			$avatar = "<img alt='{$safe_alt}' src='{$default}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
		}
		
		$return = '';
		
		if (!isset($id)) { $id = 0; }
		if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == "on") {
		
			$profile_photo = __cpc__get_meta($id, 'profile_avatar');
			$profile_avatars = get_option(CPC_OPTIONS_PREFIX.'_profile_avatars');
		
			if ($profile_photo == '' || $profile_photo == 'upload_failed' || $profile_avatars != 'on') {
				$return .= apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt);
			} else {
				$return .= "<img src='".WP_CONTENT_URL."/plugins/".CPC_DIR."/server/get_profile_avatar.php?uid=".$id."' style='width:".$size."px; height:".$size."px' class='avatar avatar-".$size." photo' />";
			}
			
		} else {

			$profile_photo = __cpc__get_meta($id, 'profile_photo');
			$profile_avatars = get_option(CPC_OPTIONS_PREFIX.'_profile_avatars');

			if ($profile_photo == '' || $profile_photo == 'upload_failed' || $profile_avatars != 'on') {
				$return .= apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt);
			} else {
				$img_url = get_option(CPC_OPTIONS_PREFIX.'_img_url')."/members/".$id."/profile/";	
				$img_src = str_replace('//','/',$img_url) . $profile_photo;
				$return .= "<img src='".$img_src."' style='width:".$size."px; height:".$size."px' class='avatar avatar-".$size." photo' />";
			}
			
		}
		
		if (!get_option(CPC_OPTIONS_PREFIX.'_cpc_use_gravatar') && strpos($return, 'gravatar')) {
			$return = "<img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/unknown.jpg' style='width:".$size."px; height:".$size."px' class='avatar avatar-".$size." photo' />";
		}

		// Get URL to profile
		if (function_exists('__cpc__profile') && $id != '' ) {
			$profile_url = __cpc__get_url('profile');
			$profile_url = $profile_url.__cpc__string_query($profile_url).'uid='.$id;
			if ($link) {
				$p = " style='cursor:pointer' onclick='javascript:document.location=\"".$profile_url."\";' />";
			} else {
				$p = " style='cursor:pointer' />";
			}
	       	$return = str_replace("/>", $p, $return);                          
		}

		// Filter to allow changes
		$return = apply_filters('__cpc__get_avatar_filter', $return, $id);

		// Add Profile Plus (hover box) if installed
		if (function_exists('__cpc__profile_plus')) {
			if (get_option(CPC_OPTIONS_PREFIX.'_cpc_show_hoverbox') == 'on') {
				if ($id != '') {
					$display_name = str_replace("'", "&apos;", $wpdb->get_var("select display_name from ".$wpdb->base_prefix."users where ID = '".$id."'"));
				} else {
					$display_name = '';
				}
				if (__cpc__friend_of($id, $current_user->ID)) {
			       	$return = str_replace("class='", "rel='friend' title = '".$display_name."' id='".$id."' class='__cpc__follow ", $return);
				} else {
					if (__cpc__pending_friendship($id)) {
				       	$return = str_replace("class='", "rel='pending' title = '".$display_name."' id='".$id."' class='__cpc__follow ", $return);
					} else {
				       	$return = str_replace("class='", "rel='' title = '".$display_name."' id='".$id."' class='__cpc__follow ", $return);
					}
				}
				if (__cpc__is_following($current_user->ID, $id)) {
					$return = str_replace("class='", "rev='following' class='", $return);
				} else {
					$return = str_replace("class='", "rev='' class='", $return);
				}
			}
		}

		return $return;

	}
	
}

// Update user activity on page load
function __cpc__lastactivity() {
   	global $wpdb, $current_user;
	wp_get_current_user();
	
	// Update last logged in
	if (is_user_logged_in() && __cpc__get_meta($current_user->ID, 'status') != 'offline') {
		__cpc__update_meta($current_user->ID, 'last_activity', "'".date("Y-m-d H:i:s")."'");
	}

}

function __cpc__concealed_avatar() {
	if (__cpc__required()) {
		global $current_user;
		// Place hidden div of current user to use when adding to screen
		echo "<div id='__cpc__current_user_avatar' style='display:none;'>";
		echo get_avatar($current_user->ID, 200);
		echo "</div>";
		// Hover box
		echo "<div id='__cpc__follow_box' class='widget-area corners' style='display:none'>Hi</div>";
	}
}

function __cpc__footer_admin () {
	// Hidden DIV for admin dialog boxes
	echo '<span id="footer-thankyou">' . __( 'Vielen Dank für die Erstellung mit <a href="https://www.classicpress.net//">ClassicPress</a>.' ) . '</span>';
	echo "<div id='cpcommunitie_dialog' class='wp-dialog' style='padding:10px;display:none'></div>";				
}

// Hook to replace Smilies
function __cpc__buffer($buffer){ // $buffer contains entire page

	if (!get_option(CPC_OPTIONS_PREFIX.'_cpc_lite') && !strpos($buffer, "<rss") ) {

		global $wpdb;
		
		if (get_option(CPC_OPTIONS_PREFIX.'_emoticons') == "on") {
			
			$smileys = CPC_PLUGIN_URL . '/images/smilies/';
			$smileys_dir = CPC_PLUGIN_DIR . '/images/smilies/';
			// Smilies as classic text
			$buffer = str_replace(":)", "<img src='".$smileys."smile.png' />", $buffer);
			$buffer = str_replace(":-)", "<img src='".$smileys."smile.png' />", $buffer);
			$buffer = str_replace(":(", "<img src='".$smileys."sad.png' />", $buffer);
			$buffer = str_replace(":'(", "<img src='".$smileys."crying.png' />", $buffer);
			$buffer = str_replace(":x", "<img src='".$smileys."kiss.png' />", $buffer);
			$buffer = str_replace(":X", "<img src='".$smileys."shutup.png' />", $buffer);
			$buffer = str_replace(":D", "<img src='".$smileys."laugh.png' />", $buffer);
			$buffer = str_replace(":|", "<img src='".$smileys."neutral.png' />", $buffer);
			$buffer = str_replace(":?", "<img src='".$smileys."question.png' />", $buffer);
			$buffer = str_replace(":z", "<img src='".$smileys."sleepy.png' />", $buffer);
			$buffer = str_replace(":P", "<img src='".$smileys."tongue.png' />", $buffer);
			$buffer = str_replace(";)", "<img src='".$smileys."wink.png' />", $buffer);
			// Other images
			
			$i = 0;
			do {
				$i++;
				$start = strpos($buffer, "{{");
				if ($start === false) {
				} else {
					$end = strpos($buffer, "}}");
					if ($end === false) {
					} else {
						$first_bit = substr($buffer, 0, $start);
						$last_bit = substr($buffer, $end+2, strlen($buffer)-$end-2);
						$bit = substr($buffer, $start+2, $end-$start-2);
						$buffer = $first_bit."<img style='width:24px;height:24px' src='".$smileys.strip_tags($bit).".png' />".$last_bit;
					}
				}
			} while ($i < 100 && strpos($buffer, "{{")>0);
			
		}
			
		if (get_option(CPC_OPTIONS_PREFIX.'_tags') == "on") {

			// User tagging		
			
			$profile_url = __cpc__get_url('profile');
			$profile = $profile_url.__cpc__string_query($profile_url).'uid=';
			$needles = array();
			for($i=0;$i<=47;$i++){ array_push($needles, chr($i)); }
			for($i=58;$i<=63;$i++){ array_push($needles, chr($i)); }
			for($i=91;$i<=96;$i++){ array_push($needles, chr($i)); }
			
			$i = 0;
			do {
				$i++;
				$start = strpos($buffer, "@");
				if ($start === false) {
				} else {
					$end = __cpc__strpos($buffer, $needles, $start);
					if ($end === false) $end = strlen($buffer);
					$first_bit = substr($buffer, 0, $start);
					$last_bit = substr($buffer, $end, strlen($buffer)-$end+2);
					$bit = substr($buffer, $start+1, $end-$start-1);
					$sql = 'SELECT ID FROM '.$wpdb->base_prefix.'users WHERE replace(display_name, " ", "") = %s LIMIT 0,1';
					$id = $wpdb->get_var($wpdb->prepare($sql, $bit));
					if ($id) {
						$buffer = $first_bit.'<a href="'.$profile.$id.'" class="__cpc__usertag">&#64;'.$bit.'</a>'.$last_bit;
					} else {
						$sql = 'SELECT ID FROM '.$wpdb->base_prefix.'users WHERE user_login = %s LIMIT 0,1';
						$id = $wpdb->get_var($wpdb->prepare($sql, $bit));
						if ($id) {
							$buffer = $first_bit.'<a href="'.$profile.$id.'" class="__cpc__usertag">&#64;'.$bit.'</a>'.$last_bit;
						} else {
							$buffer = $first_bit.'&#64;'.$bit.$last_bit;
						}
					}
				}
			} while ($i < 100 && strpos($buffer, "@"));		
		}
		
	}

	return $buffer;
	
}

function __cpc__strip_smilies($buffer){ 
	$buffer = str_replace(":)", "", $buffer);
	$buffer = str_replace(":-)", "", $buffer);
	$buffer = str_replace(":(", "", $buffer);
	$buffer = str_replace(":'(", "", $buffer);
	$buffer = str_replace(":x", "", $buffer);
	$buffer = str_replace(":X", "", $buffer);
	$buffer = str_replace(":D", "", $buffer);
	$buffer = str_replace(":|", "", $buffer);
	$buffer = str_replace(":?", "", $buffer);
	$buffer = str_replace(":z", "", $buffer);
	$buffer = str_replace(":P", "", $buffer);
	$buffer = str_replace(";)", "", $buffer);
	
	return $buffer;
}

// Hook for adding unread mail, etc
function __cpc__unread($buffer){ 
	
   	global $wpdb, $current_user;
	wp_get_current_user();

	// Unread mail
	$unread_in = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix.'cpcommunitie_mail'." WHERE mail_to = ".$current_user->ID." AND mail_in_deleted != 'on' AND mail_read != 'on'");
	if ($unread_in > 0) {
		$buffer = str_replace("%m", "(".$unread_in.")", $buffer);
	} else {
		$buffer = str_replace("%m", "", $buffer);
	}
	
    // Pending friends
	$pending_friends = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix."cpcommunitie_friends f WHERE f.friend_to = ".$current_user->ID." AND f.friend_accepted != 'on'");

	if ($pending_friends > 0) {
		$buffer = str_replace("%f", "(".$pending_friends.")", $buffer);
	} else {
		$buffer = str_replace("%f", "", $buffer);
	}

    return $buffer;
    
}

// Add jQuery and jQuery scripts
function __cpc__js_init() {

	global $wpdb;
		
	$plugin = CPC_PLUGIN_URL;

	// Only load if not admin (and chosen in Settings)
	if (!is_admin()) {

		if (get_option(CPC_OPTIONS_PREFIX.'_jquery') == "on") {
			wp_enqueue_script('jquery');	 		
		}

		if (get_option(CPC_OPTIONS_PREFIX.'_jqueryui') == "on") {
			wp_enqueue_script('jquery-ui-custom', $plugin.'/js/jquery-ui-custom.min.js', array('jquery'));	
		    wp_register_style('__cpc__jquery-ui-css', CPC_PLUGIN_URL.'/css/jquery-ui-custom.css');
			wp_enqueue_style('__cpc__jquery-ui-css');
		}	

	 	if (get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg') == "on" || function_exists('__cpc__events_main') || function_exists('__cpc__group')) {
	 		if (!get_option(CPC_OPTIONS_PREFIX.'_tinymce') == "on") {
		 		wp_enqueue_script('cpc-tinymce', $plugin.'/tiny_mce/tiny_mce_src.js', array('jquery'));	
		 	}
	 	}

	 	if (get_option(CPC_OPTIONS_PREFIX.'_jwplayer') == "on") {
	 		wp_enqueue_script('cpc-jwplayer', $plugin.'/js/jwplayer.js', array('jquery'));	
	 	}

		// Upload CSS
	    wp_register_style('__cpc__upload_ui_css', CPC_PLUGIN_URL.'/css/jquery.fileupload-ui.css');
		wp_enqueue_style('__cpc__upload_ui_css');
	    // Upload JS
		wp_enqueue_script('__cpc__tmpl', CPC_PLUGIN_URL.'/js/tmpl.min.js', array('jquery'));	
		wp_enqueue_script('__cpc__load_image', CPC_PLUGIN_URL.'/js/load-image.min.js', array('jquery'));	
		wp_enqueue_script('__cpc__canvas_to_blob', CPC_PLUGIN_URL.'/js/canvas-to-blob.min.js', array('jquery'));	
		wp_enqueue_script('__cpc__iframe_transport', CPC_PLUGIN_URL.'/js/jquery.iframe-transport.js', array('jquery'));	
		wp_enqueue_script('__cpc__fileupload', CPC_PLUGIN_URL.'/js/jquery.fileupload.js', array('jquery'));	
		wp_enqueue_script('__cpc__fileupload_fp', CPC_PLUGIN_URL.'/js/jquery.fileupload-fp.js', array('jquery'));	
		wp_enqueue_script('__cpc__fileupload_ui', CPC_PLUGIN_URL.'/js/jquery.fileupload-ui.js', array('jquery'));	

	}
	
}

// Perform admin duties, such as add jQuery and jQuery scripts and other admin jobs
function __cpc__admin_init() {
	if (is_admin()) {

		// jQuery dialog box for use in admin
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		
		// ClassicPress color picker
		wp_enqueue_style( 'farbtastic' );
	    wp_enqueue_script( 'farbtastic' );

	  	// Load admin CSS
	  	$myStyleUrl = CPC_PLUGIN_URL . '/css/cpc-admin.css';
	  	$myStyleFile = CPC_PLUGIN_DIR . '/css/cpc-admin.css';
	  	if ( file_exists($myStyleFile) ) {
	    	wp_register_style('__cpc__Admin_StyleSheet', $myStyleUrl);
	    	wp_enqueue_style('__cpc__Admin_StyleSheet');
	  	}

	}
}

// Add JS scripts to ClassicPress for use and other preparatory stuff
function __cpc__scriptsAction() {

	$__cpc__plugin_url = CPC_PLUGIN_URL;
	$__cpc__plugin_path = str_replace("http://".$_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"], "", $__cpc__plugin_url);
 
	global $wpdb, $current_user;
	wp_get_current_user();

	// Set script timeout
	if (get_option(CPC_OPTIONS_PREFIX.'_cpc_time_out') > 0) {
		set_time_limit(get_option(CPC_OPTIONS_PREFIX.'_cpc_time_out'));
	}

	// Debug mode?
	define('CPC_DEBUG', get_option(CPC_OPTIONS_PREFIX.'_debug_mode'));

	// Using Panel?
	$use_panel = false;
	if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__add_notification_bar_activated') || get_option(CPC_OPTIONS_PREFIX.'__cpc__add_notification_bar_network_activated'))	&& file_exists(dirname(__FILE__).'/panel.php'))
		$use_panel = true;
		
	// Set up variables for use throughout
	if (!is_admin()) {

		// Mail
		if ( !isset($_GET['view']) ) { 
			$view = "in"; 
		} else {
			$view = $_GET['view'];
		} 
	
		// Current User Page (eg. a profile page)
		if (isset($_GET['uid'])) {
			$page_uid = $_GET['uid']*1;
		} else {
			$page_uid = 0;
			if (isset($_POST['uid'])) { 
				$page_uid = $_POST['uid']*1; 
			} else {
				// Try the permalink?
				if (get_option(CPC_OPTIONS_PREFIX.'_permalink_structure')) {
					// get URL
					$url = $_SERVER["REQUEST_URI"];
					
					// if trailing slash, remove if
					if ( $url[strlen($url)-1] == '/' )
						$url = substr($url, 0, strlen($url)-1);
					$last_slash = strrpos($url, '/');
					
					if ($last_slash === FALSE) {
						$page_uid = $current_user->ID;
					} else {
						$u = substr($url, $last_slash+1, strlen($url)-$last_slash);
						$sql = "SELECT ID FROM ".$wpdb->base_prefix."users WHERE replace(display_name, ' ', '') = %s";
						$id = $wpdb->get_row($wpdb->prepare($sql, str_replace(' ', '', $u)));
						if ($id) {
							$page_uid = $id->ID;
						} else {
							$page_uid = $current_user->ID;
						}
					}
				} else {
					// default then to current user
					$page_uid = $current_user->ID;
				}
			}
		}
		if ($page_uid == 0) {
			if (isset($_POST['from']) && $_POST['from'] == 'small_search') {
				$search = $_POST['member_small'];
				$get_uid = $wpdb->get_var("SELECT u.ID FROM ".$wpdb->base_prefix."users u WHERE (u.display_name LIKE '".$search."%') OR (u.display_name LIKE '% %".$search."%') ORDER BY u.display_name LIMIT 0,1");
				if ($get_uid) { $page_uid = $get_uid; }
			} 
		}		
		define('CPC_CURRENT_USER_PAGE', $page_uid);

		// Forum
		if (isset($_GET['show'])) {
			$show_tid = $_GET['show']*1;
		} else {
			$show_tid = 0;
			if (isset($_POST['tid'])) { $show_tid = $_POST['tid']*1; }
		}
		$cat_id = '';
		if (isset($_GET['cid'])) { $cat_id = $_GET['cid']; }
		if (isset($_POST['cid'])) { $cat_id = $_POST['cid']; }

		// Group page
		if (isset($_GET['gid'])) {
			$page_gid = $_GET['gid']*1;
		} else {
			$page_gid = 0;
			if (isset($_POST['gid'])) { 
				$page_gid = $_POST['gid']*1; 
			}
		}
		// If visiting a group page, check to see if forum is default view
		if (is_user_logged_in() && $page_gid > 0) {
			$forum = $wpdb->get_row($wpdb->prepare("SELECT group_forum, default_page FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d", $page_gid));
			if ($forum->default_page == 'forum' && $forum->group_forum == 'on') {
				$cat_id = 0;
			}
		}
								
		// Gallery
		$album_id = 0;
		if (isset($_GET['album_id'])) { $album_id = $_GET['album_id']; }
		if (isset($_POST['album_id'])) { $album_id = $_POST['album_id']; }
		
		// Get styles for JS
		if (get_option(CPC_OPTIONS_PREFIX.'_use_styles') == "on") {
			$bg_color_2 = get_option(CPC_OPTIONS_PREFIX.'_bg_color_2');
			$row_border_size = get_option(CPC_OPTIONS_PREFIX.'_row_border_size');
			$row_border_style = get_option(CPC_OPTIONS_PREFIX.'_row_border_style');
			$text_color_2 = get_option(CPC_OPTIONS_PREFIX.'_text_color_2');
		} else {
			$bg_color_2 = '';
			$row_border_size = '';
			$row_border_style = '';
			$text_color_2 = '';
		}
	
		// GET post?
		if (isset($_GET['post'])) {
			$GETpost = $_GET['post'];
		} else {
			$GETpost = '';
		}
	
		// Display Name
		if (isset($current_user->display_name)) {
			$display_name = stripslashes($current_user->display_name);
		} else {
			$display_name = '';
		}

		// Embedded content from external plugin?
		if (isset($_GET['embed'])) {
			$embed = 'on';
		} else {
			$embed = '';
		}
	
		// to parameter
		if (isset($_GET['to'])) {
			$to = $_GET['to'];
		} else {
			$to = '';
		}
		
		// mail ID
		if (isset($_GET['mid'])) {
			$mid = $_GET['mid'];
		} else {
			$mid = '';
		}
		
		// chat sound
		$chat_sound = __cpc__get_meta($current_user->ID, 'chat_sound');
		if (!$chat_sound) $chat_sound = 'Pop.mp3';
		
		// Get forum upload valid extensions
		$permitted_ext = get_option(CPC_OPTIONS_PREFIX.'_image_ext').','.get_option(CPC_OPTIONS_PREFIX.'_video_ext').','.get_option(CPC_OPTIONS_PREFIX.'_doc_ext');

		global $blog_id;
		if ($blog_id > 1) {
			$cpc_content = get_option(CPC_OPTIONS_PREFIX.'_img_url')."/".$blog_id;
		} else {
			$cpc_content = get_option(CPC_OPTIONS_PREFIX.'_img_url');
		}
				
		// Load JS
	 	wp_enqueue_script('__cpc__', $__cpc__plugin_url.'/js/'.get_option(CPC_OPTIONS_PREFIX.'_cpc_js_file'), array('jquery'));
	
	 	// Load JScharts?
	 	if (get_option(CPC_OPTIONS_PREFIX.'_jscharts')) {
	 	    if (get_option(CPC_OPTIONS_PREFIX.'_cpc_js_file') == 'cpc.js') {
			 	wp_enqueue_script('cpc_jscharts', $__cpc__plugin_url.'/js/jscharts.js', array('jquery'));
	 	    } else {
			 	wp_enqueue_script('cpc_jscharts', $__cpc__plugin_url.'/js/jscharts.min.js', array('jquery'));
	 	    }
	 	}
	 	
	 	// Use WP editor? (not for use yet!!!!)
	 	update_option(CPC_OPTIONS_PREFIX.'_use_wp_editor', false);
	 	
		// Set JS variables
		wp_localize_script( '__cpc__', '__cpc__', array(
			// variables
			'permalink' => get_permalink(),
			'plugins' => WP_PLUGIN_URL, 
			'plugin_url' => CPC_PLUGIN_URL.'/', 
			'cpc_content_dir' => WP_CONTENT_DIR.'/cpc-content',
			'plugin_path' => $__cpc__plugin_path,
			'images_url' => get_option(CPC_OPTIONS_PREFIX.'_images'),
			'inactive' => get_option(CPC_OPTIONS_PREFIX.'_online'),
			'forum_url' => __cpc__get_url('forum'),
			'mail_url' => __cpc__get_url('mail'),
			'profile_url' => __cpc__get_url('profile'),
			'groups_url' => __cpc__get_url('groups'),
			'group_url' => __cpc__get_url('group'),
			'gallery_url' => __cpc__get_url('gallery'),
			'page_gid' => $page_gid,
			'offline' => get_option(CPC_OPTIONS_PREFIX.'_offline'),
			'use_chat' => get_option(CPC_OPTIONS_PREFIX.'_use_chat'),
			'chat_polling' => get_option(CPC_OPTIONS_PREFIX.'_chat_polling'),
			'bar_polling' => get_option(CPC_OPTIONS_PREFIX.'_bar_polling'),
			'view' => $view,
			'profile_default' => get_option(CPC_OPTIONS_PREFIX.'_cpc_profile_default'),
			'show_tid' => $show_tid,
			'cat_id' => $cat_id,
			'album_id' => $album_id,
			'current_user_id' => $current_user->ID,
			'current_user_display_name' => $display_name,
			'current_user_level' => __cpc__get_current_userlevel($current_user->ID),
			'current_user_page' => $page_uid,
			'current_group' => $page_gid,
			'post' => $GETpost,
			'please_wait' => __('Bitte warte...', 'cp-communitie'),
			'saving' => __('Saving...', 'cp-communitie'),
			'site_title' => get_bloginfo('name'),
			'site_url' => get_bloginfo('url'),
			'bg_color_2' => $bg_color_2,
			'row_border_size' => $row_border_size,
			'row_border_style' => $row_border_style,
			'text_color_2' => $text_color_2,
			'template_mail_tray' => get_option(CPC_OPTIONS_PREFIX.'_template_mail_tray'),
			'embed' => $embed,
			'to' => $to,
			'is_admin' => 0,
			'mail_id' => $mid,
			'permitted_ext' => $permitted_ext,
			'forum_ajax' => get_option(CPC_OPTIONS_PREFIX.'_forum_ajax'),
			'cpc_lite' => get_option(CPC_OPTIONS_PREFIX.'_cpc_lite'),
			'cpc_use_poke' => get_option(CPC_OPTIONS_PREFIX.'_use_poke'),
			'cpc_forum_stars' => get_option(CPC_OPTIONS_PREFIX.'_forum_stars'),
			'cpc_forum_refresh' => get_option(CPC_OPTIONS_PREFIX.'_forum_refresh'),
			'cpc_wysiwyg' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg'),
			'cpc_wysiwyg_1' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_1'),
			'cpc_wysiwyg_2' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_2'),
			'cpc_wysiwyg_3' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_3'),
			'cpc_wysiwyg_4' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_4'),
			'cpc_wysiwyg_css' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_css'),
			'cpc_wysiwyg_skin' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_skin'),
			'cpc_wysiwyg_width' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_width'),
			'cpc_wysiwyg_height' => get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_height'),
			'cpc_plus' => (defined('CPC_PLUS')) ? CPC_PLUS : '',
			'cpc_alerts_activated' => (get_option(CPC_OPTIONS_PREFIX.'__cpc__news_main_activated') || get_option(CPC_OPTIONS_PREFIX.'__cpc__news_main_network_activated')),
			'cpc_admin_page' => 'na',
			'dir_page_length' => get_option(CPC_OPTIONS_PREFIX.'_dir_page_length'),
			'dir_full_ver' => get_option(CPC_OPTIONS_PREFIX.'_dir_full_ver') ? true : false,
			'use_elastic' => get_option(CPC_OPTIONS_PREFIX.'_elastic'),
			'events_user_places' => get_option(CPC_OPTIONS_PREFIX.'_events_user_places'),
			'events_use_wysiwyg' => get_option(CPC_OPTIONS_PREFIX.'_events_use_wysiwyg'),
			'debug' => CPC_DEBUG,
			'include_context' => get_option(CPC_OPTIONS_PREFIX.'_include_context'),
			'use_wp_editor' => get_option(CPC_OPTIONS_PREFIX.'_use_wp_editor'),
			'profile_menu_scrolls' => get_option(CPC_OPTIONS_PREFIX.'_profile_menu_scrolls'),
			'profile_menu_delta' => get_option(CPC_OPTIONS_PREFIX.'_profile_menu_delta'),
			'profile_menu_adjust' => get_option(CPC_OPTIONS_PREFIX.'_profile_menu_adjust'),
			'panel_enabled' => $use_panel,
			'chat_sound' => $chat_sound,
			'cpc_content' => $cpc_content,
			// translations
			'clear' 			=> __( 'Leeren', 'cp-communitie'),
			'update' 			=> __( 'Aktualisieren', 'cp-communitie'),
			'cancel' 			=> __( 'Abbrechen', 'cp-communitie'),
			'pleasewait' 		=> __( 'Bitte warte', 'cp-communitie'),
			'saving' 			=> __( 'Speichern', 'cp-communitie'),
			'more' 				=> __( 'mehr...', 'cp-communitie'),
			'next' 				=> __( 'Nächste', 'cp-communitie'),
			'areyousure' 		=> __( 'Bist du Dir sicher?', 'cp-communitie'),
			'browseforfile' 	=> __( 'Nach Datei suchen', 'cp-communitie'),
			'attachimage' 		=> __( 'Bild anhängen', 'cp-communitie'),
			'attachfile' 		=> __( 'Datei anhängen', 'cp-communitie'),
			'whatsup' 			=> stripslashes(get_option(CPC_OPTIONS_PREFIX.'_status_label')),
			'whatsup_done' 		=> __( 'Beitrag zu Deiner Aktivität hinzugefügt.', 'cp-communitie'),
			'sendmail' 			=> __( 'Sende eine private Mail...', 'cp-communitie'),
			'privatemail' 		=> __( 'Private Mail', 'cp-communitie'),
			'privatemailsent' 	=> __( 'Private Mail verschickt!', 'cp-communitie'),
			'addasafriend' 		=> sprintf(__("Als %s hinzufügen...", 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friend')),
			'friendpending' 	=> sprintf(__("%s Anfrage gesendet", 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friend')),
			'attention' 		=> get_option(CPC_OPTIONS_PREFIX.'_poke_label'),
			'follow' 			=> __( 'Folgen', 'cp-communitie'),
			'unfollow' 			=> __( 'Entfolgen', 'cp-communitie'),
			'sent' 				=> __( 'Nachricht verschickt!', 'cp-communitie'),
			'likes' 			=> __( 'Likes', 'cp-communitie'),
			'dislikes'		 	=> __( 'Dislikes', 'cp-communitie'),
			'forumsearch' 		=> __( 'Suche im Forum', 'cp-communitie'),
			'gallerysearch' 	=> __( 'Galerie durchsuchen', 'cp-communitie'),
			'profile_info' 		=> __( 'Mitgliedsprofil', 'cp-communitie'),
			'plus_mail' 		=> __( 'Mailbox', 'cp-communitie'),
			'plus_follow_who' 	=> __( 'Wem folge ich?', 'cp-communitie'),
			'plus_friends' 		=> get_option(CPC_OPTIONS_PREFIX.'_alt_friends'),
			'request_sent' 		=> sprintf(__("Your %s request has been sent.", 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friend')),
			'add_a_comment' 	=> __( 'Add a comment:', 'cp-communitie'),
			'add' 				=> __( 'Hinzufügen', 'cp-communitie'),
			'show_original' 	=> __( 'Show original', 'cp-communitie'),
			'write_a_comment' 	=> __( 'Schreibe einen Kommentar...', 'cp-communitie'),
			'follow_box' 		=> __( 'Hi', 'cp-communitie'),
			'events_enable_places' => __( 'Enable booking places:', 'cp-communitie'),
			'events_max_places' => __( 'Maximum places:', 'cp-communitie'),
			'events_show_max'	 => __( 'Maximum places:', 'cp-communitie'),
			'events_confirmation' => __( 'Bookings require confirmation:', 'cp-communitie'),
			'events_tickets_per_booking' => __( 'Max tickets per booking:', 'cp-communitie'),
			'events_tab_1' 		=> __( 'Summary', 'cp-communitie'),
			'events_tab_2' 		=> __( 'More Information', 'cp-communitie'),
			'events_tab_3' 		=> __( 'Confirmation Email', 'cp-communitie'),
			'events_tab_4' 		=> __( 'Attendees', 'cp-communitie'),
			'events_send_email' => __( 'Send confirmation email:', 'cp-communitie'),
			'events_replacements' => __( 'You can use the following:', 'cp-communitie'),
			'events_pay_link' 	=> __( 'HTML for payment:', 'cp-communitie'),
			'events_cost' 		=> __( 'Price per booking:', 'cp-communitie'),
			'events_howmany' 	=> __( 'How many tickets do you want?', 'cp-communitie'),
			'events_labels' 	=> __( 'Ref|User|Booked|Confirmation email sent|# Tickets|Payment Confirmed|Actions|Confirm attendee|Send Mail|Re-send confirmation email|Remove attendee|Confirm payment', 'cp-communitie'),
			'gallery_labels' 	=> __( 'Rename|Photo renamed.|Drag thumbnails to re-order, and then|save|Delete this photo|Set as album cover', 'cp-communitie'),
			'sending' 			=> __( 'Sending', 'cp-communitie'),
			'go' 				=> __( 'Go', 'cp-communitie'),
			'bbcode_url'	 	=> __( 'Enter a website URL...', 'cp-communitie'),
			'bbcode_problem' 	=> __( 'Please make sure all BB Codes have open and close tags!', 'cp-communitie'),
			'bbcode_label' 		=> __( 'Enter text to show...', 'cp-communitie')			
		));

	}
	
	if (is_admin()) {
		
		// Load admin JS
	 	wp_enqueue_script('__cpc__', $__cpc__plugin_url.'/js/cpc-admin.js', array('jquery'));
	 	
		// Set JS variables
		wp_localize_script( '__cpc__', '__cpc__', array(
			'plugins' => WP_PLUGIN_URL, 
			'plugin_url' => CPC_PLUGIN_URL.'/', 
			'plugin_path' => $__cpc__plugin_path,
			'images_url' => get_option(CPC_OPTIONS_PREFIX.'_images'),
			'inactive' => get_option(CPC_OPTIONS_PREFIX.'_online'),
			'forum_url' => get_option(CPC_OPTIONS_PREFIX.'_forum_url'),
			'mail_url' => get_option(CPC_OPTIONS_PREFIX.'_mail_url'),
			'profile_url' => get_option(CPC_OPTIONS_PREFIX.'_profile_url'),
			'groups_url' => get_option(CPC_OPTIONS_PREFIX.'_groups_url'),
			'group_url' => get_option(CPC_OPTIONS_PREFIX.'_group_url'),
			'gallery_url' => get_option(CPC_OPTIONS_PREFIX.'_gallery_url'),
			'offline' => get_option(CPC_OPTIONS_PREFIX.'_offline'),
			'use_chat' => get_option(CPC_OPTIONS_PREFIX.'_use_chat'),
			'chat_polling' => get_option(CPC_OPTIONS_PREFIX.'_chat_polling'),
			'bar_polling' => get_option(CPC_OPTIONS_PREFIX.'_bar_polling'),
			'current_user_id' => $current_user->ID,
			'is_admin' => 1,
			'cpc_admin_page' => 'cpcommunitie_debug'
			
		));
	}
	
}

/* ====================================================== PAGE LOADED FUNCTIONS ====================================================== */

function __cpc__replace() {
	if (__cpc__required()) {	
		ob_start();
		ob_start('__cpc__unread');
	}
}

/* ====================================================== ADMIN FUNCTIONS ====================================================== */

// Add Stylesheet
function __cpc__add_stylesheet() {

	global $wpdb;

	if (!is_admin()) {

	    // Load CSS
	    $myStyleUrl = CPC_PLUGIN_URL . '/css/'.get_option(CPC_OPTIONS_PREFIX.'_cpc_css_file');
	    $myStyleFile = CPC_PLUGIN_DIR . '/css/'.get_option(CPC_OPTIONS_PREFIX.'_cpc_css_file');
	    if ( file_exists($myStyleFile) ) {
	        wp_register_style('__cpc__StyleSheet', $myStyleUrl);
	        wp_enqueue_style('__cpc__StyleSheet');
	    }

			
	}

}

// Language files
function __cpc__languages() {
	
	if ( file_exists(dirname(__FILE__).'/languages/') ) {
        load_plugin_textdomain(CPC_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)).'/languages/');
    } else {
        if ( file_exists(dirname(__FILE__).'/lang/') ) {
            load_plugin_textdomain(CPC_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)).'/lang/');
        } else {
			load_plugin_textdomain(CPC_TEXT_DOMAIN);
        }
    }

}


?>
