<?php

// *************************************** HOOKS AND FILTERS ***************************************

function __cpc__rewrite($wp_rewrite) {

	// Forum

	if ( ($r = get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single')) && ($t = get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single_target')) ) {
		$wp_rewrite->rules = array_merge( array ( $r => $t ), $wp_rewrite->rules);
	}
	if ( ($r = get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double')) && ($t = get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double_target')) ) {
		$wp_rewrite->rules = array_merge( array ( $r => $t ), $wp_rewrite->rules);
	}
	
	// Profile
	if ( ($r = get_option(CPC_OPTIONS_PREFIX.'_rewrite_members')) && ($t = get_option(CPC_OPTIONS_PREFIX.'_rewrite_members_target')) ) {
		$wp_rewrite->rules = array_merge( array ( $r => $t ), $wp_rewrite->rules);
	}
	
	return $wp_rewrite;
		
}

function __cpc__query_var( $query_vars ){
	if ( get_option(CPC_OPTIONS_PREFIX.'_permalink_structure') ) {
		array_push($query_vars, 'stub');
	}
    return $query_vars;
}
if (get_option(CPC_OPTIONS_PREFIX.'_permalink_structure')) {
	add_filter('generate_rewrite_rules', '__cpc__rewrite');
	add_filter('query_vars', '__cpc__query_var' );
}

/* Filter plugins list */

function __cpc__plugin_permissions($plugins)
{

		if (!CPC_CHANGE_PLUGINS)
			return $plugins;
			
		$keys = array_keys($plugins); 
		$num = 0; 
		
		foreach ($plugins as $plugin) { 

			if (CPC_HIDE_PLUGINS && strpos($plugin['Name'], 'CPC') !== FALSE) {
			} else {
				if (strpos($plugin['Name'], 'CPC') !== FALSE) {
					$plugin['Name'] = str_replace('CPC', CPC_WL_SHORT, $plugin['Name']);
					if (CPC_CHANGE_DESC) $plugin['Description'] = CPC_CHANGE_DESC;
					if (CPC_CHANGE_VER) $plugin['Version'] = CPC_CHANGE_VER;
					if (CPC_CHANGE_AUTHOR) $plugin['Author'] = CPC_CHANGE_AUTHOR;
					if (CPC_CHANGE_AUTHORURI) $plugin['AuthorURI'] = CPC_CHANGE_AUTHORURI;
					if (CPC_CHANGE_PLUGINURIGE_DESC) $plugin['PluginURI'] = CPC_CHANGE_PLUGINURI;
				}
				$viewable_plugins[$keys[$num]] = $plugin; 
			}
			$num++; 

		} 
		
		return $viewable_plugins;

        
}

add_filter('all_plugins', '__cpc__plugin_permissions');

/* Add meta box to posts for forum link */
add_action( 'add_meta_boxes', '__cpc__add_custom_post_box' );
add_action( 'save_post', '__cpc__save_postdata' );

/* Adds a box to the main column on the Post and Page edit screens */
function __cpc__add_custom_post_box() {
	if (function_exists('__cpc__forum')) {
	    add_meta_box( 
	        'myplugin_sectionid',
	        __( 'Link to Forum', CPC_TEXT_DOMAIN ),
	        '__cpc__inner_custom_box',
	        'post' 
	    );
	}
}

/* Prints the box content */
function __cpc__inner_custom_box( $post ) {
	global $wpdb;
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'myplugin_noncename' );

  // The actual fields for data entry
  echo '<label for="myplugin_new_field">';
       _e("Select a topic", CPC_TEXT_DOMAIN );
  echo '</label> ';
  $value = get_post_meta($post->ID, 'CPC post link', true);
  echo '<select id="myplugin_new_field" name="myplugin_new_field">';
  $sql = "SELECT tid, topic_subject FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_parent = 0 AND topic_group = 0 ORDER BY topic_subject";
  $topics = $wpdb->get_results($sql);
  echo '<option value=0';
  if ($value == 0 || $value == '') { echo " SELECTED"; }
  echo '>'.__('None', CPC_TEXT_DOMAIN).'</option>';  
  if ($topics) {
	  foreach ($topics AS $topic) {
	      echo '<option value='.$topic->tid;
	      if ($topic->tid == $value) { echo " SELECTED"; }
	      echo '>'.stripslashes($topic->topic_subject).'</option>';
	  }
  }
  echo '</select>';
}

/* When the post is saved, saves our custom data */
function __cpc__save_postdata( $post_id ) {
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return;

  if ( !isset($_POST['myplugin_noncename']) || !wp_verify_nonce( $_POST['myplugin_noncename'], plugin_basename( __FILE__ ) ) )
      return;

  // Check permissions
  if ( 'page' == $_POST['post_type'] ) 
  {
    if ( !current_user_can( 'edit_page', $post_id ) )
        return;
  }
  else
  {
    if ( !current_user_can( 'edit_post', $post_id ) )
        return;
  }

  // OK, we're authenticated: we need to find and save the data
  $mydata = $_POST['myplugin_new_field'];

  // Do something with $mydata 
  update_post_meta($post_id, 'CPC post link', $mydata);
}

add_filter( 'the_content', '__cpc__post_content_filter', 10 );
function __cpc__post_content_filter( $content ) {

    if ( is_single() && function_exists('__cpc__forum') ) {

    	$value = get_post_meta($GLOBALS['post']->ID, 'CPC post link', true);
    	
    	if ($value && $value != '') {
	    	$forum_url = __cpc__get_url('forum');
			$q = __cpc__string_query($forum_url);		
    		$content .= "<p><a href='".$forum_url.$q."show=".$value."'>".__('Discuss on the forum...', CPC_TEXT_DOMAIN)."</a></p>";
    	}
    	
    }

    return $content;
}

// Profile Menu hook
function __cpc__add_profile_menu($html,$uid1,$uid2,$privacy,$is_friend,$extended,$share,$extra_class)  
{  
	global $wpdb,$current_user;
	
			if ( ( get_option(CPC_OPTIONS_PREFIX.'_menu_profile') == 'on') ) {
				if ($uid1 == $uid2) {
					if (get_option(CPC_OPTIONS_PREFIX.'_menu_profile'))
						$html .= '<div id="menu_extended" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_profile_text')) != '' ? $t :  __('My Profile', CPC_TEXT_DOMAIN)).'</div>';
				} else {
					if (get_option(CPC_OPTIONS_PREFIX.'_menu_profile_other'))
						$html .= '<div id="menu_extended" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_profile_other_text')) != '' ? $t :  __('Profile', CPC_TEXT_DOMAIN)).'</div>';
				}
			}

			if  ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($share) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __cpc__get_current_userlevel() == 5) {

				if ($uid1 == $uid2) {
					if (get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity') == 'on') {
						$html .= '<div id="menu_wall" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_text')) != '' ? $t :  __('My Activity', CPC_TEXT_DOMAIN)).'</div>';
					}
					if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity') == 'on') {
						if (strtolower($share) == 'public' && !(is_user_logged_in())) {
							// don't show friends activity to public
						} else {
							$html .= '<div id="menu_activity" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_text')) != '' ? $t :  __('My Friends Activity', CPC_TEXT_DOMAIN)).'</div>';
						}
					}
				} else {
					if (get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_other') == 'on') {
						$html .= '<div id="menu_wall" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_other_text')) != '' ? $t :  __('Activity', CPC_TEXT_DOMAIN)).'</div>';
					}
					if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_other') == 'on') {
						if (strtolower($share) == 'public' && !(is_user_logged_in())) {
							// don't show friends activity to public
						} else {
							$html .= '<div id="menu_activity" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_other_text')) != '' ? $t :  __('Friends Activity', CPC_TEXT_DOMAIN)).'</div>';
						}
					}
				}

				if (strtolower($share) == 'public' && !(is_user_logged_in())) {
					// don't show all activity to public
				} else {
					if ($uid1 == $uid2) {
						if (get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity')) {
							$t = ($t = get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_text')) != '' ? $t :  __('All Activity', CPC_TEXT_DOMAIN);
							$html .= '<div id="menu_all" class="__cpc__profile_menu '.$extra_class.'">'.$t.'</div>';
						}
					} else {
						if (get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_other')) {
							$t = ($t = get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_other_text')) != '' ? $t :  __('All Activity', CPC_TEXT_DOMAIN);
							$html .= '<div id="menu_all" class="__cpc__profile_menu '.$extra_class.'">'.$t.'</div>';
						}
					}					
				}
				if (function_exists('__cpc__group')) {
					if ($uid1 == $uid2) {
						if (get_option(CPC_OPTIONS_PREFIX.'_menu_groups'))
							$html .= '<div id="menu_groups" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_groups_text')) != '' ? $t :  __('My Groups', CPC_TEXT_DOMAIN)).'</div>';
					} else {
						if (get_option(CPC_OPTIONS_PREFIX.'_menu_groups_other'))
							$html .= '<div id="menu_groups" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_groups_other_text')) != '' ? $t :  __('Groups', CPC_TEXT_DOMAIN)).'</div>';
					}
				}				
			}

			if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'friends only' && $is_friend) || __cpc__get_current_userlevel() == 5) {
				if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends') == 'on') {
					if ($uid1 == $uid2) {
						if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends')) {
							$pending_friends = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->base_prefix."cpcommunitie_friends f WHERE f.friend_to = %d AND f.friend_accepted != 'on'", $uid1));
						
							if ( ($pending_friends > 0) && ($uid1 == $uid2) ) {
								$pending_friends = " (".$pending_friends.")";
							} else {
								$pending_friends = "";
							}
							$html .= '<div id="menu_friends" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_friends_text')) != '' ? $t :  __('My Friends', CPC_TEXT_DOMAIN)).' '.$pending_friends.'</div>';
						}
					} else {
						if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_other'))
							$html .= '<div id="menu_friends" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_friends_other_text')) != '' ? $t :  __('Friends', CPC_TEXT_DOMAIN)).'</div>';
					}
				}
			}

	return $html;
}  
add_action('__cpc__profile_menu_filter', '__cpc__add_profile_menu', 8, 8);

// Profile Menu hook (end of menu)
function __cpc__add_profile_menu_texthtml($html,$uid1,$uid2,$privacy,$is_friend,$extended,$share)  
{  
	global $wpdb,$current_user;
	
	$texthtml = get_option(CPC_OPTIONS_PREFIX.'_menu_texthtml');
	
	return $texthtml;
}
add_action('__cpc__profile_menu_end_filter', '__cpc__add_profile_menu_texthtml', 8, 7);

// Non-admin Header hook
function __cpc__header() {

	if (__cpc__required()) {

		include_once(dirname(__FILE__).'/styles.php');			
	
		if (CPC_DEBUG) {
			echo '<div style="overflow:auto; border:1px solid #000; background-color:#ccc; color: black; font-size:12px; padding:6px 12px 6px 12px; margin-left:auto; margin-right:auto; margin-top:10px; margin-bottom:10px; border-radius:5px;">';
			echo '<input style="float:right" id="cpcommunitie_deactivate_debug" type="submit" value="'.__('De-activate', CPC_TEXT_DOMAIN).'" />';
			echo '<strong>'.sprintf(__('%s Debug Mode', CPC_TEXT_DOMAIN), CPC_WL).'</strong><br />';
	
			global $wp_rewrite;
			echo '<a href="javascript:void(0);" rel="rewrite_rules" class="cpcommunitie-dialog">Show rewrite rules</a><br />';
				echo '<div id="rewrite_rules" title="Rewrite rules" style="display:none;margin-top:10px;background-color:#fff;color:#000;padding:6px;border:1px solid #000; border-radius:3px;">';
				echo __cpc__displayArray($wp_rewrite->rewrite_rules());
				echo '</div>';
	
			echo '</div>';
		}
		
	}
	
}

// Admin Header hook
function __cpc__admin_header() {

	if (get_option(CPC_OPTIONS_PREFIX.'_redirect_wp_profile') == 'on' && __cpc__get_current_userlevel() < 2) {
		if ( strpos($_SERVER['PHP_SELF'], "wp-admin/profile.php") !== FALSE ) {
			if (function_exists('__cpc__profile')) {
				$profile_page = __cpc__get_url('profile');
				if ( (isset($_GET['uid'])) && ($_GET['uid'] != '') ) {
					$uid = __cpc__string_query($profile_page).'uid='.$_GET['uid'];
				} else {
					$uid = '';
				}
				wp_redirect( $profile_page.$uid );
			}
		}
	}

}
if ( is_admin() )
	add_action( 'admin_menu', '__cpc__admin_header' );
	
// ****** Hooks and Filters to add comments when certain things happen to activity ******************************

// Add activity comment 
function __cpc__add_activity_comment($from_id, $from_name, $to_id, $url, $type, $var=0) {
	
	global $wpdb;
	
	$success = ($wpdb->query( $wpdb->prepare( "
		INSERT INTO ".$wpdb->base_prefix."cpcommunitie_comments
		( 	subject_uid, 
			author_uid,
			comment_parent,
			comment_timestamp,
			comment,
			is_group,
			type
		)
		VALUES ( %d, %d, %d, %s, %s, %s, %s )", 
        array(
        	$to_id, 
        	$from_id, 
        	0,
        	date("Y-m-d H:i:s"),
        	$url,
        	'',
        	$type
        	) 
        ) ) );	        
        
}
add_action('__cpc__forum_newtopic_hook', '__cpc__add_activity_comment', 10, 6);

// **************************************************************************************************************

// Add items to profile page and save them when admin or user saves profile

function __cpc__show_metadata($user) {
	
	global $wpdb;
	$uid = $user->ID;
	
	
	// get values
	$dob_day = __cpc__get_meta($uid, 'dob_day');
	$dob_month = __cpc__get_meta($uid, 'dob_month');
	$dob_year = __cpc__get_meta($uid, 'dob_year');
	$city = __cpc__get_meta($uid, 'extended_city');
	$country = __cpc__get_meta($uid, 'extended_country');
	$share = __cpc__get_meta($uid, 'share');
	$wall_share = __cpc__get_meta($uid, 'wall_share');
	if (function_exists('__cpc__rss_main')) {
		$rss_share = __cpc__get_meta($uid, 'rss_share');
	} else {
		$rss_share = '';
	}
	$trusted = __cpc__get_meta($uid, 'trusted');
	$notify_new_messages = __cpc__get_meta($uid, 'notify_new_messages');
	$notify_new_wall = __cpc__get_meta($uid, 'notify_new_wall');
	$forum_all = __cpc__get_meta($uid, 'forum_all');
	$signature = __cpc__get_meta($uid, 'signature');
	
	$html = '<h3>' . __("Profile Details", CPC_TEXT_DOMAIN) . '</h3>';

	$html .= '<table class="form-table">';
	
	// Share personal information
	$html .= '<tr><th><label for="share">'.__('Who do you want to share personal information with?', CPC_TEXT_DOMAIN).'</label></th>';
	$html .= '<td><select id="share" name="share">';
	$html .= "<option value='Nobody'";
		if ($share == 'Nobody') { $html .= ' SELECTED '; }
		$html .= '>'.__('Nobody', CPC_TEXT_DOMAIN).'</option>';
	$html .= "<option value='Friends only'";
		if ($share == 'Friends only') { $html .= ' SELECTED '; }
		$html .= '>'.sprintf(__('%s Only', CPC_TEXT_DOMAIN), get_option(CPC_OPTIONS_PREFIX.'_alt_friends')).'</option>';
	$html .= "<option value='Everyone'";
		if ($share == 'Everyone') { $html .= ' SELECTED '; }
		$html .= '>'.stripslashes(get_option(CPC_OPTIONS_PREFIX.'_alt_everyone')).'</option>';
	$html .= "<option value='public'";
		if ($share == 'public') { $html .= ' SELECTED '; }
		$html .= '>'.__('Public', CPC_TEXT_DOMAIN).'</option>';
	$html .= '</select></td></tr>';
	
	// Share Wall / Activity
	$html .= '<tr><th><label for="wall_share">'.__('Who do you want to share your activity with?', CPC_TEXT_DOMAIN).'</label></th>';
	$html .= '<td><select id="wall_share" name="wall_share">';
	$html .= "<option value='Nobody'";
		if ($wall_share == 'Nobody') { $html .= ' SELECTED '; }
		$html .= '>'.__('Nobody', CPC_TEXT_DOMAIN).'</option>';
	$html .= "<option value='Friends only'";
		if ($wall_share == 'Friends only') { $html .= ' SELECTED '; }
		$html .= '>'.sprintf(__('%s Only', CPC_TEXT_DOMAIN), get_option(CPC_OPTIONS_PREFIX.'_alt_friends')).'</option>';
	$html .= "<option value='Everyone'";
		if ($wall_share == 'Everyone') { $html .= ' SELECTED '; }
		$html .= '>'.stripslashes(get_option(CPC_OPTIONS_PREFIX.'_alt_everyone')).'</option>';
	$html .= "<option value='public'";
		if ($wall_share == 'public') { $html .= ' SELECTED '; }
		$html .= '>'.__('Public', CPC_TEXT_DOMAIN).'</option>';
	$html .= '</select></td></tr>';
	
	// Publish RSS feed?
	if (function_exists('__cpc__rss_main')) {
		$html .= '<tr><th><label for="rss_share">'.__('RSS feed', CPC_TEXT_DOMAIN).'</label></th>';
		$html .= '<td><select id="rss_share" name="rss_share">';
			$html .= "<option value=''";
				if ($rss_share == '') { $html .= ' SELECTED '; }
				$html .= '>'.__('No', CPC_TEXT_DOMAIN).'</option>';
			$html .= "<option value='on'";
				if ($rss_share == 'on') { $html .= ' SELECTED '; }
				$html .= '>'.__('Yes', CPC_TEXT_DOMAIN).'</option>';
		$html .= '</select> ';
		$html .= '<span class="description">'.__('Publish your activity via RSS (only your initial posts)?', CPC_TEXT_DOMAIN).'</span>';
		$html .= '</td></tr>';
	} else {
		$html .= '<input type="hidden" id="rss_share" value="">';
	}
	
	// Birthday
	if (get_option(CPC_OPTIONS_PREFIX.'_show_dob') == 'on') {

		$html .= '<tr><th><label for="dob">'.__('Your date of birth', CPC_TEXT_DOMAIN).'</label></th>';
		$html .= '<td><select id="dob_day" name="dob_day">';
			$html .= '<option value=0';
				if ($dob_day == 0) { $html .= ' SELECTED '; }
				$html .= '>---</option>';
			for ($i = 1; $i <= 31; $i++) {
				$html .= '<option value="'.$i.'"';
					if ($dob_day == $i) { $html .= ' SELECTED '; }
					$html .= '>'.$i.'</option>';
			}
		$html .= '</select> / ';									
		$html .= '<select id="dob_month" name="dob_month">';
			$html .= '<option value=0';
				if ($dob_month == 0) { $html .= ' SELECTED '; }
				$html .= '>---</option>';
			for ($i = 1; $i <= 12; $i++) {
				switch($i) {									
					case 1:$monthname = __("January", CPC_TEXT_DOMAIN);break;
					case 2:$monthname = __("February", CPC_TEXT_DOMAIN);break;
					case 3:$monthname = __("March", CPC_TEXT_DOMAIN);break;
					case 4:$monthname = __("April", CPC_TEXT_DOMAIN);break;
					case 5:$monthname = __("May", CPC_TEXT_DOMAIN);break;
					case 6:$monthname = __("June", CPC_TEXT_DOMAIN);break;
					case 7:$monthname = __("July", CPC_TEXT_DOMAIN);break;
					case 8:$monthname = __("August", CPC_TEXT_DOMAIN);break;
					case 9:$monthname = __("September", CPC_TEXT_DOMAIN);break;
					case 10:$monthname = __("October", CPC_TEXT_DOMAIN);break;
					case 11:$monthname = __("November", CPC_TEXT_DOMAIN);break;
					case 12:$monthname = __("December", CPC_TEXT_DOMAIN);break;
				}
				$html .= '<option value="'.$i.'"';
					if ($dob_month == $i) { $html .= ' SELECTED '; }
					$html .= '>'.$monthname.'</option>';
			}
		$html .= '</select> / ';									
		$html .= '<select id="dob_year" name="dob_year">';
			$html .= '<option value=0';
				if ($dob_year == 0) { $html .= ' SELECTED '; }
				$html .= '>---</option>';
			for ($i = date("Y"); $i >= 1900; $i--) {
				$html .= '<option value="'.$i.'"';
					if ($dob_year == $i) { $html .= ' SELECTED '; }
					$html .= '>'.$i.'</option>';
			}
			$html .= '</td></select>';									
	
	} else {
	
		$html .= '<input type="hidden" id="dob_day" value="'.$dob_day.'">';
		$html .= '<input type="hidden" id="dob_month" value="'.$dob_month.'">';
		$html .= '<input type="hidden" id="dob_year" value="'.$dob_year.'">';
	
	}
	
	// City
	$html .= '<tr><th><label for="extended_city">'.__('Which town/city are you in?', CPC_TEXT_DOMAIN).'</label></th>';
	$html .= '<td><input type="text" class="input-field" id="extended_city" name="extended_city" style="width:300px" value="'.trim($city, "'").'">';
	$html .= '</td></tr>';
	
	// Country
	$html .= '<tr><th><label for="extended_country">'.__('Which country are you in?', CPC_TEXT_DOMAIN).'</label></th>';
	$html .= '<td><input type="text" class="input-field" id="extended_country" name="extended_country" style="width:300px" value="'.trim($country, "'").'">';
	$html .= '</td></tr>';
	
	// Google map
	if ( ($city != '' || $country != '') && (get_option(CPC_OPTIONS_PREFIX.'_profile_google_map') > 0) ){ 	
						
		$html .= '<tr><th></th><td>';
		$html .= '<a target="_blank" style="width:'.get_option(CPC_OPTIONS_PREFIX.'_profile_google_map').'px; height:'.get_option(CPC_OPTIONS_PREFIX.'_profile_google_map').'px;" href="http://maps.google.co.uk/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q='.$city.',+'.$country.'&amp;ie=UTF8&amp;hq=&amp;hnear='.$city.',+'.$country.'&amp;output=embed&amp;z=5" alt="Click on map to enlarge" title="Click on map to enlarge">';
		$html .= '<img src="http://maps.google.com/maps/api/staticmap?center='.$city.',.+'.$country.'&zoom=5&size='.get_option(CPC_OPTIONS_PREFIX.'_profile_google_map').'x'.get_option(CPC_OPTIONS_PREFIX.'_profile_google_map').'&maptype=roadmap&markers=color:blue|label:&nbsp;|'.$city.',+'.$country.'&sensor=false" />';
		$html .= '</a><br /><span class="description"> '.sprintf(__("The Google map that will be displayed on top of your %s profile page, resulting from your personal data above.", CPC_TEXT_DOMAIN), CPC_WL).'</span></td></tr>';
	
	}
	
	// Extensions
	$extensions = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_extended ORDER BY extended_order, extended_name");
	if ($extensions) {
	
		$sql = "SELECT * FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d AND meta_key like 'cpcommunitie_extended_%%'";
		$fields = $wpdb->get_results($wpdb->prepare($sql, $uid));
		
		foreach ($extensions as $extension) {
			
			// Don't display Extended Fields that are associated to WP usermeta data, they should be displayed somewhere else in the dashboard
			if ( $extension->wp_usermeta == '' ) {
			
				$value = $extension->extended_default;
				if ($extension->extended_type == "List") {
					$default_list = explode(',', $extension->extended_default);
					$value = $default_list[0];
				}
				foreach ($fields as $field) {
					$slug = str_replace('cpcommunitie_extended_', '', $field->meta_key);
					if ($slug == $extension->extended_slug) { $value = $field->meta_value; break; }
				}
				
				// Draw the object according to type
				switch ($extension->extended_type) :
				case 'Text' :
					$html .= '<tr><th><label for="extended_'.$slug.'">'.stripslashes($extension->extended_name).'</label></th>';
					$html .= '<td><input type="text" class="input-field" id="extended_'.$slug.'" name="extended_'.$slug.'" style="width:300px" value="'.$value.'"';
					if ( $extension->readonly ) { $html .= ' disabled="disabled"'; }
					$html .= ' /></td></tr>';
				break;
				case 'Textarea' :
					$html .= '<tr><th><label for="extended_'.$slug.'">'.stripslashes($extension->extended_name).'</label></th>';
					$html .= '<td><textarea rows="5" cols="30" id="extended_'.$slug.'" name="extended_'.$slug.'"';
					if ( $extension->readonly ) { $html .= ' disabled="disabled"'; }
					$html .= '>'.$value.'</textarea></td></tr>';
				break;
				case 'List' :
					$html .= '<tr><th><label for="extended_'.$slug.'">'.stripslashes($extension->extended_name).'</label></th>';
					$html .= '<td><select id="extended_'.$slug.'" name="extended_'.$slug.'"';
					if ( $extension->readonly ) { $html .= ' disabled="disabled"'; }
					$html .= '>';
					foreach ($default_list as $list_value) {
						$html .= '<option value="'.$list_value.'"';
						if ( $value == $list_value) { $html .= ' SELECTED '; }
						$html .= '>'.$list_value.'</option>';
					}
					$html .= '</select></td></tr>';
				break;
				case 'Checkbox' :
					$html .= '<tr><th><label for="extended_'.$slug.'">'.stripslashes($extension->extended_name).'</label></th>';
					$html .= '<td><input type="checkbox" id="extended_'.$slug.'" name="extended_'.$slug.'"';
					if ( $extension->readonly ) { $html .= ' disabled="disabled"'; }
					if ( $value == 'on') { $html .= ' CHECKED '; }
					$html .= '/></td>';
					$html .= '</tr>';
				break;
				endswitch;
			}
		}
	}
	
	$html .= '</table>';
	
	$html .= '<h3>' . __("Community Settings", CPC_TEXT_DOMAIN) . '</h3>';
	$html .= '<table class="form-table">';
	
	// Trusted member (for example, for support staff)
	if (__cpc__get_current_userlevel() == 5) {
		$html .= '<tr><th><label for="trusted">'.__('Trusted Member?', CPC_TEXT_DOMAIN).'</label></th>';
		$html .= '<td><input type="checkbox" name="trusted" id="trusted"';
		if ($trusted == 'on') { $html .= ' CHECKED '; }
		$html .= '/> ';
		$html .= '<span class="description">'.__('Is this member trusted?', CPC_TEXT_DOMAIN).'</span>';
		$html .= '</td></tr>';
	} else {
		$html .= '<tr><td><input type="hidden" name="trusted_hidden" id="trusted_hidden" value="'.$trusted.'" /><td></tr>';
	}
	
	// profile_photo, avatar
	if ( get_option('show_avatars') ) {
		// AG - select your avatar here -->
	}
	
	// forum_digest
	
	// Email notifications for private messages
	$html .= '<tr><th><label for="notify_new_messages">'.__('Emails for private messages', CPC_TEXT_DOMAIN).'</label></th>';
	$html .= '<td><input type="checkbox" name="notify_new_messages" id="notify_new_messages"';
	if ($notify_new_messages =='on') { $html .= ' CHECKED '; }
	$html .= '/> ';
	$html .= '<span class="description">'.__('Receive an email when you get new mail messages?', CPC_TEXT_DOMAIN).'</span>';
	$html .= '</td></tr>';
	
	// Email notifications for wall posts
	$html .= '<tr><th><label for="notify_new_wall">'.__('Emails for posts on the Wall', CPC_TEXT_DOMAIN).'</label></th>';
	$html .= '<td><input type="checkbox" name="notify_new_wall" id="notify_new_wall"';
	if ($notify_new_wall == 'on') { $html .= ' CHECKED '; }
	$html .= '/> ';
	$html .= '<span class="description">'.__('Receive an email when a friend adds a post?', CPC_TEXT_DOMAIN).'</span>';
	$html .= '</td></tr>';
	
	if (function_exists('__cpc__forum')) {
		
		// Email notifications for all forum activity (if allowed)
		if (get_option(CPC_OPTIONS_PREFIX.'_allow_subscribe_all') == "on") {
			$html .= '<tr><th><label for="forum_all">'.__('Emails for all new forum topics and replies', CPC_TEXT_DOMAIN).'</label></th>';
			$html .= '<td><input type="checkbox" name="forum_all" id="forum_all"';
			if ($forum_all == 'on') { $html .= ' CHECKED '; }
			$html .= '/> ';
			$html .= '<span class="description">'.__('Receive an email for all new forum topics and replies?', CPC_TEXT_DOMAIN).'</span><br />';
			$html .= '</td></tr>';
		} else {
			$html .= '<input type="hidden" name="forum_all" value="" />';
		}
	
		// Signature in the forum
		$html .= '<tr><th><label for="signature">'.__('Forum signature', CPC_TEXT_DOMAIN).'</label></th>';
		$html .= '<td><input type="text" class="input-field" id="signature" name="signature" style="width:300px" value="'.stripslashes(trim($signature, "'")).'"><br />';
		$html .= '<span class="description">'.__('If you want a signature to be appended automatically under your forum posts', CPC_TEXT_DOMAIN).'</span></td></tr>';
	}
	
	// Facebook
	// AG - the return value needs to be dealt with...
	
	$html .= '</table>';
	
	echo $html;
}


global $user;

// Runs near the end of the user profile editing screen when the page is displayed by the user. Action function argument: profileuser.
add_action("show_user_profile", "__cpc__show_metadata", $user, 10, 1);

// Runs near the end of the user profile editing screen when the page is displayed in the admin menus. Action function argument: profileuser.
add_action("edit_user_profile", "__cpc__show_metadata", $user, 10, 1);

function __cpc__save_metadata($uid) {
	
	global $wpdb,$current_user;

	if ( $_POST["action"] == 'update' ) {
		__cpc__update_meta($uid, 'extended_city', isset($_POST["extended_city"]) ? addslashes($_POST["extended_city"]) : "");
		__cpc__update_meta($uid, 'extended_country', isset($_POST["extended_country"]) ? addslashes($_POST["extended_country"]) : "");
		__cpc__update_meta($uid, 'dob_day', isset($_POST["dob_day"]) ? $_POST["dob_day"] : "");
		__cpc__update_meta($uid, 'dob_month', isset($_POST["dob_month"]) ? $_POST["dob_month"] : "");
		__cpc__update_meta($uid, 'dob_year', isset($_POST["dob_year"]) ? $_POST["dob_year"] : "");
		__cpc__update_meta($uid, 'share', isset($_POST["share"]) ? $_POST["share"] : "");
		__cpc__update_meta($uid, 'wall_share', isset($_POST["wall_share"]) ? $_POST["wall_share"] : "");
		__cpc__update_meta($uid, 'cpcommunitie_forum_digest', isset($_POST["forum_digest"]) ? $_POST["forum_digest"] : "");
		__cpc__update_meta($uid, 'notify_new_messages', isset($_POST["notify_new_messages"]) ? $_POST["notify_new_messages"] : "");
		__cpc__update_meta($uid, 'notify_new_wall', isset($_POST["notify_new_wall"]) ? $_POST["notify_new_wall"] : "");
		__cpc__update_meta($uid, 'forum_all', isset($_POST["forum_all"]) ? $_POST["forum_all"] : "");
		__cpc__update_meta($uid, 'signature', isset($_POST["signature"]) ? addslashes($_POST["signature"]) : "");
		__cpc__update_meta($uid, 'trusted', isset($_POST["trusted"]) ? $_POST["trusted"] : "");
		__cpc__update_meta($uid, 'rss_share', isset($_POST["rss_share"]) ? $_POST["rss_share"] : "");

		// loop over extensions' $_POSTs
		$extensions = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_extended ORDER BY extended_order, extended_name");
		if ($extensions) {
			$sql = "SELECT * FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d AND meta_key like 'cpcommunitie_extended_%'";
			$fields = $wpdb->get_results($wpdb->prepare($sql, $uid));
			
			foreach ($extensions as $extension) {
				
				if ( $extension->wp_usermeta == '' ) {				
					// Not linked, so simply save
					$slug = 'extended_'.$extension->extended_slug;
					$value = ( isset($_POST[$slug])) ? $_POST[$slug] : "";
					$sql = "UPDATE ".$wpdb->base_prefix."usermeta SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
					$wpdb->query($wpdb->prepare($sql, $value, $uid, 'cpcommunitie_'.$slug));
				} else {					
					//	A linked field, so update CPC field (WP field updated by WordPress)
					$um = $extension->wp_usermeta;
					if ($um == 'show_admin_bar_front') $um = 'admin_bar_front';
					$value = (isset($_POST[$um])) ? $_POST[$um] : '';
					$sql = "UPDATE ".$wpdb->base_prefix."usermeta SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
					$wpdb->query($wpdb->prepare($sql, $value, $uid, 'cpcommunitie_extended_'.$extension->extended_slug));					
				}
			}
		}
	}
}

// Runs when the page data is edited by the user. Action function argument: user ID.
add_action("personal_options_update", "__cpc__save_metadata", 10, 1);

// Runs when the page data is edited by an admin. Action function argument: user ID.
add_action("edit_user_profile_update", "__cpc__save_metadata", 10, 1);

?>
