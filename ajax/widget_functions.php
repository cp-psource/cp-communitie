<?php

include_once('../../../../wp-config.php');

global $wpdb, $current_user;


// cpcommunitie_summary_Widget
if ($_POST['action'] == 'cpcommunitie_summary_Widget') {
	
	$show_loggedout = $_POST['show_loggedout'];
	$show_form = $_POST['form'];
	$login_url = $_POST['login_url'];
	$show_avatar = $_POST['show_avatar'];
	$show_avatar_size = $_POST['show_avatar_size'];
	$login_username = $_POST['login_username'];
	$login_password = $_POST['login_password'];
	$login_remember_me = $_POST['login_remember_me'];
	$login_button = $_POST['login_button'];
	$login_forgot = $_POST['login_forgot'];
	$login_register = $_POST['login_register'];
		
	__cpc__do_summary_Widget($show_loggedout,$show_form,$login_url,$show_avatar,$login_username,$login_password,$login_remember_me,$login_button,$login_forgot,$login_register,$show_avatar_size);
		
}

// cpcommunitie_friends_Widget
if ($_POST['action'] == 'cpcommunitie_friends_Widget') {
	
	$__cpc__friends_count = $_POST['count'];
	$__cpc__friends_desc = $_POST['desc'];
	$__cpc__friends_mode = $_POST['mode'];
	$__cpc__friends_show_light = $_POST['show_light'];
	$__cpc__friends_show_mail = $_POST['show_mail'];

	__cpc__do_friends_Widget($__cpc__friends_count,$__cpc__friends_desc,$__cpc__friends_mode,$__cpc__friends_show_light,$__cpc__friends_show_mail);

}

// __cpc__Forumexperts_Widget
if ($_POST['action'] == 'Forumexperts_Widget') {
	
	$cat_id = $_POST['cat_id'];
	$cat_id_exclude = $_POST['cat_id_exclude'];
	$timescale = $_POST['timescale'];
	$postcount = $_POST['postcount'];
	$groups = $_POST['groups'];

	__cpc__do_Forumexperts_Widget($cat_id,$cat_id_exclude,$timescale,$postcount,$groups);
}

// __cpc__Forumnoanswer_Widget
if ($_POST['action'] == 'Forumnoanswer_Widget') {
	
	$preview = $_POST['preview'];
	$cat_id = $_POST['cat_id'];
	$cat_id_exclude = $_POST['cat_id_exclude'];
	$timescale = $_POST['timescale'];
	$postcount = $_POST['postcount'];
	$groups = $_POST['groups'];

	__cpc__do_Forumnoanswer_Widget($preview,$cat_id,$cat_id_exclude,$timescale,$postcount,$groups);
	
}

// recent_Widget
if ($_POST['action'] == 'recent_Widget') {
	
	$__cpc__recent_count = $_POST['count'];
	$__cpc__recent_desc = $_POST['desc'];
	$__cpc__recent_show_light = $_POST['show_light'];
	$__cpc__recent_show_mail = $_POST['show_mail'];

	do_recent_Widget($__cpc__recent_count,$__cpc__recent_desc,$__cpc__recent_show_light,$__cpc__recent_show_mail);
}

// members_Widget
if ($_POST['action'] == 'members_Widget') {
	
	$__cpc__members_count = $_POST['count'];
	__cpc__do_members_Widget($__cpc__members_count);
	
}

// __cpc__friends_status_Widget
if ($_POST['action'] == 'friends_status_Widget') {
	
	$postcount = $_POST['postcount'];
	$preview = $_POST['preview'];
	$forum = $_POST['forum'];
	__cpc__do_friends_status_Widget($postcount,$preview,$forum);
	
}

// __cpc__Forumrecentposts_Widget
if ($_POST['action'] == '__cpc__Forumrecentposts_Widget') {

	$postcount = isset($_POST['postcount']) ? $_POST['postcount'] : 5;
	$preview = isset($_POST['preview']) ? $_POST['preview'] : 100;
	$cat_id = isset($_POST['cat_id']) ? $_POST['cat_id'] : '';
	$show_replies = isset($_POST['show_replies']) ? $_POST['show_replies'] : false;
	$incl_cat = $_POST['incl_cat'];
	$incl_parent = $_POST['incl_parent'];
	$just_own = $_POST['just_own'];
	
	__cpc__do_Forumrecentposts_Widget($postcount,$preview,$cat_id,$show_replies,$incl_cat,$incl_parent,$just_own);
}

// Recentactivity_Widget
if ($_POST['action'] == 'Recentactivity_Widget') {
	
	$postcount = $_POST['postcount'];
	$preview = $_POST['preview'];
	$forum = $_POST['forum'];
	
	__cpc__do_Recentactivity_Widget($postcount,$preview,$forum);
	
}

// Recentactivity_Widget
if ($_POST['action'] == 'Alerts_Widget') {
	
	$postcount = $_POST['postcount'];
	
	__cpc__do_Alerts_Widget($postcount);
	
}

// Login
if ($_POST['action'] == 'doLogin') {

	global $wpdb, $current_user, $wp_error;
	
	if ($_POST['username'] != '') {

		$creds = array();
		$creds['user_login'] = $_POST['username'];
		$creds['user_password'] = $_POST['password'];
		$creds['remember'] = true;

		$user = wp_signon($creds, false);

		if(is_wp_error($user)) {
			echo 'FAIL';
		} else {

		  	if ($_POST['show_form'] == 'on') {
				if ($_POST['login_url'] != '') {
					$url = $_POST['login_url'];	
				} else {
					$url = __cpc__get_url('profile');	
				}
				echo $url;	
  			} else {
				echo '/';	
			}
		}
	} else {
		echo 'FAIL';
	}

	exit;
}

// Vote Widget
if ($_POST['action'] == 'doVote') {

	global $wpdb, $current_user;
	wp_get_current_user();

	if (is_user_logged_in()) {
	
		$vote = $_POST['vote'];
		
		__cpc__update_meta($current_user->ID, 'widget_voted', 'on');
	
		if ($vote == "yes") {
			update_option( "cpcommunitie_vote_yes", get_option(CPC_OPTIONS_PREFIX."_vote_yes")+1 );
		} else {
			update_option( "cpcommunitie_vote_no", get_option(CPC_OPTIONS_PREFIX."_vote_no")+1 );
		}

		echo $vote;

	} else {
		echo "NOT LOGGED IN";		
	}
	
	exit;
}

?>

	
