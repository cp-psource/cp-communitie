<?php

/* ====================================================== ADMIN MENU ====================================================== */


function __cpc__plugin_menu() {
	
	global $wpdb, $current_user;
		
	// Act on any parameters, so menu counts are correct
	if (isset($_GET['action'])) {
		
		switch($_GET['action']) {
			
			case "post_del":
				if (isset($_GET['tid'])) {

					if (__cpc__safe_param($_GET['tid'])) {

						// Get details
						$post = $wpdb->get_row( $wpdb->prepare("SELECT t.*, u.user_email FROM ".$wpdb->prefix."cpcommunitie_topics t LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE tid = %d", $_GET['tid']) );
	
						$body = "<span style='font-size:24px'>".__('Dein Forumsbeitrag wurde vom Moderator abgelehnt', 'cp-communitie').".</span>";
						if ($post->topic_parent == 0) { $body .= "<p><strong>".stripslashes($post->topic_subject)."</strong></p>"; }
						$body .= "<p>".stripslashes($post->topic_post)."</p>";
						$body = str_replace(chr(13), "<br />", $body);
						$body = str_replace("\\r\\n", "<br />", $body);
						$body = str_replace("\\", "", $body);
							
						// Email author to let them know it was deleted
						if (get_option(CPC_OPTIONS_PREFIX.'_moderation_email_rejected') == "on")						
							__cpc__sendmail($post->user_email, __('Forumsbeitrag abgelehnt', 'cp-communitie'), $body);

						// Update
						$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix."cpcommunitie_topics WHERE tid = %d", $_GET['tid'] ) );

					} else {
						echo "BAD PARAMETER PASSED: ".$_GET['tid'];
					}
					
				}
				break;

			case "post_approve":
				if (isset($_GET['tid'])) {

					$forum_url = __cpc__get_url('forum');
					$group_url = __cpc__get_url('group');
					$q = __cpc__string_query($forum_url);		
					
					if (__cpc__safe_param($_GET['tid'])) {

						// Update
						$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."cpcommunitie_topics SET topic_approved = 'on' WHERE tid = %d", $_GET['tid'] ) );
						
						// Get details
						$post = $wpdb->get_row( $wpdb->prepare("SELECT t.*, u.user_email, u.display_name FROM ".$wpdb->prefix."cpcommunitie_topics t LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE tid = %d", $_GET['tid']) );
	
						$body = "<span style='font-size:24px'>".__('Dein Forumsbeitrag wurde vom Moderator genehmigt', 'cp-communitie').".</span>";
						if ($post->topic_parent == 0) { $body .= "<p><strong>".stripslashes($post->topic_subject)."</strong></p>"; }
						$body .= "<p>".stripslashes($post->topic_post)."</p>";
						$url = $forum_url.$q."cid=".$post->topic_category."&show=".$_GET['tid'];
						$body .= "<p><a href='".$url."'>".$url."</a></p>";
						$body = str_replace(chr(13), "<br />", $body);
						$body = str_replace("\\r\\n", "<br />", $body);
						$body = str_replace("\\", "", $body);
						
						// Work out URL
						$parent = $wpdb->get_row("SELECT tid, topic_subject FROM ".$wpdb->prefix."cpcommunitie_topics WHERE tid = ".$post->topic_parent);
						if ($post->topic_group == 0) {	

							if ($post->topic_parent == 0) {					
								$url = $forum_url.$q."cid=".$post->topic_category."&show=".$_GET['tid'];
							} else {
								$url = $forum_url.$q."cid=".$post->topic_category."&show=".$parent->tid;
							}	
						
						} else {
							
							if ($post->topic_parent == 0) {					
								$url = $group_url.$q."gid=".$post->topic_group."&cid=".$post->topic_category."&show=".$_GET['tid'];
							} else {
								$url = $group_url.$q."gid=".$post->topic_group."&cid=".$post->topic_category."&show=".$parent->tid;
							}							
						
						}						

						// Email author to let them know
						if (get_option(CPC_OPTIONS_PREFIX.'_moderation_email_accepted') == "on")						
							__cpc__sendmail($post->user_email, __('Forumsbeitrag genehmigt', 'cp-communitie'), $body);
	
						// Email people who want to know and prepare body (and post activity comment)
						if ($post->topic_parent > 0) {						
							$body = "<span style='font-size:24px'>".$parent->topic_subject."</span><br /><br />";
							$body .= "<p>".$post->display_name." ".__('antwortete', 'cp-communitie')."...</p>";
						} else {
							$body = "<span style='font-size:24px'>".$post->topic_subject."</span><br /><br />";
							$body .= "<p>".$post->display_name." ".__('gestartet', 'cp-communitie')."...</p>";
							$post_url = __('Neues Forumsthema gestartet:', 'cp-communitie').' <a href="'.$url.'">'.$post->topic_subject.'</a>';
							do_action('__cpc__forum_newtopic_hook', $post->topic_owner, $post->display_name, $post->topic_owner, $post_url, 'forum', $_GET['tid']);	
						}
						
						$body .= "<p>".$post->topic_post."</p>";
						$body .= "<p>".$url."</p>";
						$body = str_replace(chr(13), "<br />", $body);
						$body = str_replace("\\r\\n", "<br />", $body);
						$body = str_replace("\\", "", $body);

						$email_list = '0,';				
						if ($post->topic_group == 0) {	
							
							// Main Forum			
												
							if ($post->topic_parent > 0) {
								$query = $wpdb->get_results("
									SELECT u.ID, u.user_email
									FROM ".$wpdb->base_prefix."users u RIGHT JOIN ".$wpdb->prefix."cpcommunitie_subs s ON s.uid = u.ID 
									WHERE tid = ".$parent->tid);
							} else {
								$query = $wpdb->get_results("
									SELECT u.ID, u.user_email
									FROM ".$wpdb->base_prefix."users u RIGHT JOIN ".$wpdb->prefix."cpcommunitie_subs s ON s.uid = u.ID 
									WHERE cid = ".$post->topic_category);
							}
														
							if ($query) {						
								foreach ($query as $user) {		
									// Filter to allow further actions to take place
									if ($post->topic_parent > 0) {
										apply_filters ('__cpc__forum_newreply_filter', $user->ID, $post->topic_owner, $post->display_name, $url);								
									} else {
										apply_filters ('__cpc__forum_newtopic_filter', $user->ID, $post->topic_owner, $post->display_name, $url);
									}										

									// Keep track of who sent to so far
									$email_list .= $user->ID.',';

									__cpc__sendmail($user->user_email, __('Neuer Forumsbeitrag', 'cp-communitie'), $body);							
								}
							}
							
						} else {
							
							// Group Forum
							$group_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM ".$wpdb->base_prefix."cpcommunitie_groups WHERE gid = %d", $post->topic_group));
			
							$sql = "SELECT ID, user_email FROM ".$wpdb->base_prefix."users u 
							LEFT JOIN ".$wpdb->prefix."cpcommunitie_group_members g ON u.ID = g.member_id 
							WHERE u.ID > 0 AND g.group_id = %d AND u.ID != %d";
			
							$members = $wpdb->get_results($wpdb->prepare($sql, $post->topic_group, $current_user->ID));
			
							if ($members) {
								foreach ($members as $member) {
									if ($post->topic_parent > 0) {
										apply_filters ('__cpc__forum_newreply_filter', $member->ID, $post->topic_owner, $post->display_name, $url);								
									} else {
										apply_filters ('__cpc__forum_newtopic_filter', $member->ID, $post->topic_owner, $post->display_name, $url);
									}										

									// Keep track of who sent to so far
									$email_list .= $member->ID.',';

									__cpc__sendmail($member->user_email, __('Neuer Beitrag im Gruppenforum', 'cp-communitie'), $body);							
								}
							}
						}							

						// Now send to everyone who wants to know about all new topics and replies
						$email_list .= '0';
						$sql = "SELECT ID,user_email FROM ".$wpdb->base_prefix."users u 
							WHERE ID != %d AND 
							ID NOT IN (".$email_list.")";
						$list = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));
		
						if ($list) {
							
							$list_array = array();
							foreach ($list as $item) {
				
								if (__cpc__get_meta($item->ID, 'forum_all') == 'on') {
									$add = array (	
										'ID' => $item->ID,
										'user_email' => $item->user_email
									);						
									array_push($list_array, $add);
								}
								
							}
							$query = __cpc__sub_val_sort($list_array, 'last_activity');	
							
						} else {
						
							$query = false;
							
						}	

						// Get list of permitted roles for this topic category
						$sql = "SELECT level FROM ".$wpdb->prefix."cpcommunitie_cats WHERE cid = %d";
						$level = $wpdb->get_var($wpdb->prepare($sql, $post->topic_category));
						$cat_roles = unserialize($level);
							
						if ($query) {						
							foreach ($query as $user) {	

								// Get role of recipient user
								$the_user = get_userdata( $user->ID );
								$capabilities = $the_user->{$wpdb->prefix . 'capabilities'};
		
								if ( !isset( $wp_roles ) )
									$wp_roles = new WP_Roles();
									
								$user_role = 'NONE';
								foreach ( $wp_roles->role_names as $role => $name ) {
									
									if ( array_key_exists( $role, $capabilities ) ) {
										$user_role = $role;
									}
								}
								
								// Check in this topics category level
								if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {	 
		
									// Filter to allow further actions to take place
									apply_filters ('__cpc__forum_newreply_filter', $user->ID, $current_user->ID, $current_user->display_name, $url);

									// Send mail
									__cpc__sendmail($user->user_email, __('Neuer Forumsbeitrag', 'cp-communitie'), $body);							
									
								}
							}
						}
											
					} else {
						echo "BAD PARAMETER PASSED: ".$_GET['tid'];
					}

				}
				break;

		}
	}

	if (!CPC_HIDE_PLUGINS) {

		// Build menu
		$count = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'cpcommunitie_topics'." WHERE topic_approved != 'on'"); 
		if ($count > 0) {
			$count1 = "<span class='update-plugins' title='".$count." comments to moderate'><span class='update-count'>".$count."</span></span>";
			$count2 = " (".$count.")";
		} else {
			$count1 = "";
			$count2 = "";
		}
	
		// Aggregate menu items?
		$hidden = get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	
		// Build menus
		if (__cpc__is_wpmu()) {
			// WPMS
			add_menu_page(CPC_WL_SHORT,CPC_WL_SHORT.$count1, 'manage_options', 'cpcommunitie_debug', '__cpc__plugin_debug', 'none'); 
			add_submenu_page('cpcommunitie_debug', __('Installation', 'cp-communitie'), __('Installation', 'cp-communitie'), 'manage_options', 'cpcommunitie_debug', '__cpc__plugin_debug');
			add_submenu_page('cpcommunitie_debug', __('Willkommen', 'cp-communitie'), __('Willkommen', 'cp-communitie'), 'manage_options', 'cpcommunitie_welcome', '__cpc__plugin_welcome');
			add_submenu_page('cpcommunitie_debug'.$hidden, __('Einstellungen', 'cp-communitie'), __('Einstellungen', 'cp-communitie'), 'manage_options', 'cpcommunitie_settings', '__cpc__plugin_settings');
			add_submenu_page('cpcommunitie_debug'.$hidden, __('Werbung', 'cp-communitie'), __('Werbung', 'cp-communitie'), 'manage_options', 'cpcommunitie_advertising', '__cpc__plugin_advertising');
			add_submenu_page('cpcommunitie_debug'.$hidden, __('Thesaurus', 'cp-communitie'), __('Thesaurus', 'cp-communitie'), 'manage_options', 'cpcommunitie_thesaurus', '__cpc__plugin_thesaurus');
			add_submenu_page('cpcommunitie_debug'.$hidden, __('Vorlagen', 'cp-communitie'), __('Vorlagen', 'cp-communitie'), 'manage_options', 'cpcommunitie_templates', '__cpc__plugin_templates');
			if (get_option(CPC_OPTIONS_PREFIX.'_audit') == "on") 
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Audit', 'cp-communitie'), __('Audit', 'cp-communitie'), 'manage_options', 'cpcommunitie_audit', '__cpc__plugin_audit');
	
			// Aggregate menu items
			if (get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on") {
				add_submenu_page('cpcommunitie_debug', __('Optionen', 'cp-communitie'), __('Optionen', 'cp-communitie'), 'manage_options', 'cpcommunitie_options', '__cpc__menu_options');
				add_submenu_page('cpcommunitie_debug', __('Verwalten', 'cp-communitie'), __('Verwalten', 'cp-communitie'), 'manage_options', 'cpcommunitie_manage', '__cpc__menu_manage');
				if ($count2) add_submenu_page('cpcommunitie_debug', __('Moderieren', 'cp-communitie'), sprintf(__('Moderiere %s', 'cp-communitie'), $count1), 'manage_options', 'cpcommunitie_moderation', '__cpc__plugin_moderation');
			}
			add_submenu_page('cpcommunitie_debug', __('Stile', 'cp-communitie'), __('Stile', 'cp-communitie'), 'manage_options', 'cpcommunitie_styles', '__cpc__plugin_styles');
			
			if (function_exists('__cpc__profile')) {
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Profil', 'cp-communitie'), __('Profil', 'cp-communitie'), 'manage_options', 'cpcommunitie_profile', '__cpc__plugin_profile');
			}
			if (function_exists('__cpc__forum')) {
				if (!current_user_can('manage_options')) {
					// Not an administrator, so check if Forum Moderation menu item should be shown
					$can_moderate = false;
					$user = get_userdata( $current_user->ID );
					$moderators = str_replace('_', '', str_replace(' ', '', strtolower(get_option(CPC_OPTIONS_PREFIX.'_moderators'))));
					$capabilities = $user->{$wpdb->base_prefix.'capabilities'};

					if ($capabilities) {
						foreach ( $capabilities as $role => $name ) {
							if ($role) {
								$role = strtolower($role);
								$role = str_replace(' ', '', $role);
								$role = str_replace('_', '', $role);
								if (CPC_DEBUG) $html .= 'Checking user role '.$role.' against '.$moderators.'<br />';
								if (strpos($moderators, $role) !== FALSE) $can_moderate = true;
							}
						}		 														
					} else {
						// No ClassicPress role stored
					}
					if ($can_moderate)
						add_menu_page('Moderation','Moderation'.$count1, 'read', 'cpcommunitie_moderation', '__cpc__plugin_moderation', plugin_dir_url( __FILE__ ).'/images/logo_admin_icon.png', 8); 
				}
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Forum', 'cp-communitie'), __('Forum', 'cp-communitie'), 'manage_options', 'cpcommunitie_forum', '__cpc__plugin_forum');
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Forenkategorien', 'cp-communitie'), __('Forenkategorien', 'cp-communitie'), 'manage_options', 'cpcommunitie_categories', '__cpc__plugin_categories');
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Forenbeiträge', 'cp-communitie'), sprintf(__('Forumsbeiträge %s', 'cp-communitie'), $count2), 'manage_options', 'cpcommunitie_moderation', '__cpc__plugin_moderation');
			}
			if (function_exists('__cpc__add_notification_bar')) {
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Panel', 'cp-communitie'), __('Panel', 'cp-communitie'), 'manage_options', 'cpcommunitie_bar', '__cpc__plugin_bar');
			}
			if (function_exists('__cpc__members')) {
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Mitgliederverzeichnis', 'cp-communitie'), __('Mitgliederverzeichnis', 'cp-communitie'), 'manage_options', '__cpc__members_menu', '__cpc__members_menu');
			}
			if (function_exists('__cpc__mail')) {
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Mail', 'cp-communitie'), __('Mail', 'cp-communitie'), 'update_core', '__cpc__mail_menu', '__cpc__mail_menu');
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Mail-Nachrichten', 'cp-communitie'), __('Mail-Nachrichten', 'cp-communitie'), 'update_core', '__cpc__mail_messages_menu', '__cpc__mail_messages_menu');
			}
		} else {
			// Single intallation
			add_menu_page(CPC_WL_SHORT,CPC_WL_SHORT.$count1, 'manage_options', 'cpcommunitie_debug', '__cpc__plugin_debug', 'none'); 
			add_submenu_page('cpcommunitie_debug', __('Installation', 'cp-communitie'), __('Installation', 'cp-communitie'), 'manage_options', 'cpcommunitie_debug', '__cpc__plugin_debug');
			add_submenu_page('cpcommunitie_debug', __('Willkommen', 'cp-communitie'), __('Willkommen', 'cp-communitie'), 'manage_options', 'cpcommunitie_welcome', '__cpc__plugin_welcome');
			add_submenu_page('cpcommunitie_debug'.$hidden, __('Einstellungen', 'cp-communitie'), __('Einstellungen', 'cp-communitie'), 'manage_options', 'cpcommunitie_settings', '__cpc__plugin_settings');
			add_submenu_page('cpcommunitie_debug'.$hidden, __('Werbung', 'cp-communitie'), __('Werbung', 'cp-communitie'), 'manage_options', 'cpcommunitie_advertising', '__cpc__plugin_advertising');
			add_submenu_page('cpcommunitie_debug'.$hidden, __('Thesaurus', 'cp-communitie'), __('Thesaurus', 'cp-communitie'), 'manage_options', 'cpcommunitie_thesaurus', '__cpc__plugin_thesaurus');
			add_submenu_page('cpcommunitie_debug'.$hidden, __('Vorlagen', 'cp-communitie'), __('Vorlagen', 'cp-communitie'), 'manage_options', 'cpcommunitie_templates', '__cpc__plugin_templates');
			if (get_option(CPC_OPTIONS_PREFIX.'_audit') == "on") 
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Audit', 'cp-communitie'), __('Audit', 'cp-communitie'), 'manage_options', 'cpcommunitie_audit', '__cpc__plugin_audit');
			
			// Aggregate menu items
			if (get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on") {
				add_submenu_page('cpcommunitie_debug', __('Optionen', 'cp-communitie'), __('Optionen', 'cp-communitie'), 'manage_options', 'cpcommunitie_options', '__cpc__menu_options');
				add_submenu_page('cpcommunitie_debug', __('Verwalten', 'cp-communitie'), __('Verwalten', 'cp-communitie'), 'manage_options', 'cpcommunitie_manage', '__cpc__menu_manage');
				if ($count2) add_submenu_page('cpcommunitie_debug', __('Moderation', 'cp-communitie'), sprintf(__('Moderiere %s', 'cp-communitie'), $count1), 'manage_options', 'cpcommunitie_moderation', '__cpc__plugin_moderation');
			}
	
			add_submenu_page('cpcommunitie_debug', __('Stile', 'cp-communitie'), __('Stile', 'cp-communitie'), 'manage_options', 'cpcommunitie_styles', '__cpc__plugin_styles');
			
			if (function_exists('__cpc__profile')) {
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Profil', 'cp-communitie'), __('Profil', 'cp-communitie'), 'manage_options', 'cpcommunitie_profile', '__cpc__plugin_profile');
			}
			if (function_exists('__cpc__forum')) {
				if (!current_user_can('manage_options')) {
					// Not an administrator, so check if Forum Moderation menu item should be shown
					$can_moderate = false;
					$user = get_userdata( $current_user->ID );
					$moderators = str_replace('_', '', str_replace(' ', '', strtolower(get_option(CPC_OPTIONS_PREFIX.'_moderators'))));
					$capabilities = $user->{$wpdb->base_prefix.'capabilities'};

					if ($capabilities) {
						foreach ( $capabilities as $role => $name ) {
							if ($role) {
								$role = strtolower($role);
								$role = str_replace(' ', '', $role);
								$role = str_replace('_', '', $role);
								if (CPC_DEBUG) $html .= 'Checking user role '.$role.' against '.$moderators.'<br />';
								if (strpos($moderators, $role) !== FALSE) $can_moderate = true;
							}
						}		 														
					} else {
						// No ClassicPress role stored
					}
					if ($can_moderate)
						add_menu_page('Moderation','Moderation'.$count1, 'read', 'cpcommunitie_moderation', '__cpc__plugin_moderation', plugin_dir_url( __FILE__ ).'/images/logo_admin_icon.png', 8); 
				}
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Forum', 'cp-communitie'), __('Forum', 'cp-communitie'), 'manage_options', 'cpcommunitie_forum', '__cpc__plugin_forum');
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Forenkategorien', 'cp-communitie'), __('Forenkategorien', 'cp-communitie'), 'manage_options', 'cpcommunitie_categories', '__cpc__plugin_categories');
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Forenbeiträge', 'cp-communitie'), sprintf(__('Forumsbeiträge %s', 'cp-communitie'), $count2), 'manage_options', 'cpcommunitie_moderation', '__cpc__plugin_moderation');
			}
			if (function_exists('__cpc__add_notification_bar')) {
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Panel', 'cp-communitie'), __('Panel', 'cp-communitie'), 'manage_options', 'cpcommunitie_bar', '__cpc__plugin_bar');
			}
			if (function_exists('__cpc__members')) {
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Mitgliederverzeichnis', 'cp-communitie'), __('Mitgliederverzeichnis', 'cp-communitie'), 'manage_options', '__cpc__members_menu', '__cpc__members_menu');
			}
			if (function_exists('__cpc__mail')) {
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Mail', 'cp-communitie'), __('Mail', 'cp-communitie'), 'manage_options', '__cpc__mail_menu', '__cpc__mail_menu');
				add_submenu_page('cpcommunitie_debug'.$hidden, __('Mail-Nachrichten', 'cp-communitie'), __('Mail-Nachrichten', 'cp-communitie'), 'manage_options', '__cpc__mail_messages_menu', '__cpc__mail_messages_menu');
			}
		}
		do_action('__cpc__admin_menu_hook');
	}
}

function __cpc__menu_options() {
  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';

	__cpc__show_tabs_header('options');
	
	echo '<table class="form-table __cpc__admin_table"><tr><td>';
	
	$show = '';
	if (function_exists('__cpc__profile')) $show .= '<li><a href="admin.php?page=cpcommunitie_profile">'.__('Mitgliedsprofil', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__forum')) $show .= '<li><a href="admin.php?page=cpcommunitie_forum">'.__('Forum', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__members')) $show .= '<li><a href="admin.php?page=__cpc__members_menu">'.__('Mitgliederverzeichnis', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__mail')) $show .= '<li><a href="admin.php?page=__cpc__mail_menu">'.sprintf(__('Private %s-Mail', 'cp-communitie'), CPC_WL).'</a></li>';
	
	if ($show) {
		echo '<h2>'.__('Registerkarten „Optionen“.', 'cp-communitie').'</h2>';
		echo '<p><em>'.__('Die entsprechende Funktion muss <a href="admin.php?page=cpcommunitie_debug">aktiviert</a> werden, damit der obige Reiter erscheint.', 'cp-communitie').'</em></p>';	
		echo '<ul style="list-style-type: square; margin: 10px 0 10px 30px;">';
		echo $show;
		if (function_exists('__cpc__forum')) echo '<li><a href="admin.php?page=cpcommunitie_categories">'.sprintf(__('Forenkategorien verwalten', 'cp-communitie'), CPC_WL).'</a></li>';
		if (function_exists('__cpc__forum')) echo '<li><a href="admin.php?page=cpcommunitie_moderation">'.sprintf(__('Forenbeiträge verwalten', 'cp-communitie'), CPC_WL).'</a></li>';
		echo '</ul>';
	}

	$show2 = '';
	if (function_exists('__cpc__add_notification_bar')) $show2 .= '<li><a href="admin.php?page=cpcommunitie_bar">'.__('Panel (Benachrichtigungsleiste/Chat)', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__profile_plus')) $show2 .= '<li><a href="admin.php?page='.CPC_DIR.'/plus_admin.php">'.__('Zusätzliche profilbezogene Optionen', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__group')) $show2 .= '<li><a href="admin.php?page='.CPC_DIR.'/gallery_admin.php">'.__('Gallerie', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__gallery')) $show2 .= '<li><a href="admin.php?page='.CPC_DIR.'/groups_admin.php">'.sprintf(__('%s Gruppen', 'cp-communitie'), CPC_WL).'</a></li>';
	if (function_exists('__cpc__news_main')) $show2 .= '<li><a href="admin.php?page='.CPC_DIR.'/news_admin.php">'.__('Benachrichtigungen', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__events_main')) $show2 .= '<li><a href="admin.php?page='.CPC_DIR.'/events_admin.php">'.sprintf(__('%s Events', 'cp-communitie'), CPC_WL).'</a></li>';
	if (function_exists('__cpc__facebook')) $show2 .= '<li><a href="admin.php?page='.CPC_DIR.'/facebook_admin.php">'.__('Profilnachrichten auf Facebook posten', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__mailinglist')) $show2 .= '<li><a href="admin.php?page='.CPC_DIR.'/mailinglist_admin.php">'.__('Antworte auf Forenthemen und Antworten per Mail', 'cp-communitie').'</a></li>';
	if (function_exists('__cpc__lounge_main')) $show2 .= '<li><a href="admin.php?page='.CPC_DIR.'/lounge_admin.php">'.__('Die Lounge-Optionen (Demonstration)', 'cp-communitie').'</a></li>';
	
	if (!$show && !$show2) {
		echo '<h2>'.sprintf(__('Einige %s-Plugins aktivieren', 'cp-communitie'), CPC_WL).'</h2>';
		echo '<p>'.sprintf(__('Damit die relevanten Optionsregisterkarten oben erscheinen, <a href="admin.php?page=cpcommunitie_debug">aktiviere</a> einige %s-Funktionen.', 'cp-communitie'), CPC_WL).'</p>';
	}
	
	echo '</td></tr></table>';	
	
	__cpc__show_tabs_header_end();
}

function __cpc__menu_manage() {
  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s-Verwaltung', 'cp-communitie'), CPC_WL).'</h2><br />';

	__cpc__show_manage_tabs_header('manage');
	
	echo '<table class="form-table __cpc__admin_table"><tr><td>';
	
	echo '<h2>'.__('Verwaltungsregisterkarten', 'cp-communitie').'</h2>';
	echo '<ul style="list-style-type: square; margin: 10px 0 10px 30px;">';
	echo '<li><a href="admin.php?page=cpcommunitie_settings">'.sprintf(__('Allgemeine Einstellungen für %s', 'cp-communitie'), CPC_WL).'</a></li>';
	echo '<li><a href="admin.php?page=cpcommunitie_advertising">'.sprintf(__('Optionale Anzeigeblöcke', 'cp-communitie'), CPC_WL).'</a></li>';
	echo '<li><a href="admin.php?page=cpcommunitie_thesaurus">'.sprintf(__('Formulierungsalternativen %s', 'cp-communitie'), CPC_WL).'</a></li>';
	if (function_exists('__cpc__forum')) echo '<li><a href="admin.php?page=cpcommunitie_categories">'.sprintf(__('%s Forenkategorien und Berechtigungen', 'cp-communitie'), CPC_WL).'</a></li>';
	if (function_exists('__cpc__forum')) echo '<li><a href="admin.php?page=cpcommunitie_moderation">'.sprintf(__('Anzeigen und Moderieren von %s Forenthemen und Antworten', 'cp-communitie'), CPC_WL).'</a></li>';
	if (function_exists('__cpc__mail')) echo '<li><a href="admin.php?page=__cpc__mail_messages_menu">'.sprintf(__('%s Mail-Nachrichten anzeigen und löschen', 'cp-communitie'), CPC_WL).'</a></li>';
	echo '<li><a href="admin.php?page=cpcommunitie_templates">'.sprintf(__('%s Layoutvorlagen', 'cp-communitie'), CPC_WL).'</a></li>';
	if (get_option(CPC_OPTIONS_PREFIX.'_audit') == "on") echo '<li><a href="admin.php?page=cpcommunitie_audit">'.__('Analysiere die Audit-Tabelle', 'cp-communitie').'</a></li>';
	echo '</ul>';

	echo '<h2>'.__('Benutzerverwaltung', 'cp-communitie').'</h2>';
	echo '<strong>'.__('Benutzer löschen', 'cp-communitie').'</strong><br />';
	
	// Delete user, step 1
	if ( (isset($_POST['delete_cpc_user'])) && ($_POST['delete_cpc_user'] != '') ) {
        $u = sanitize_text_field($_POST['delete_cpc_user']);
        $u =  preg_replace('#[^0-9]#','',$u);

		if (is_numeric($u)) {
			$id = $u;
			$user_info = get_userdata($id);
			$user_login = $user_info->user_login;
		} else {
			$user_info = get_user_by('login', 'nobody');
			$id = $user_info->ID;
			$user_login = $u;
		}
		if ($id && $user_login) {
			$user_info = get_user_by('login', 'nobody');
			if ($user_info) {

				echo sprintf(__('Mail, Events, Galerien und Chat werden gelöscht. Forenbeiträge und -aktivitäten werden „nobody“ zugewiesen.', 'cp-communitie'), $user_login, $id).'<br />';

				echo '<form action="" method="POST">';	
					echo '<input name="delete_cpc_user_id" type="hidden" value="'.$id.'" />';
					echo '<input name="delete_cpc_user_transfer" type="hidden" value="'.$user_info->ID.'" />';
					echo '<input type="submit" class="button-primary" value="'.sprintf(__('Inhalt neu zuweisen/löschen und %s entfernen', 'cp-communitie'), $user_login).'" />';
				echo '</form>';
			} else {
				echo '<em>'.__('<a href="user-new.php">Erstelle zuerst einen Benutzer</a> mit dem Benutzernamen "nobody", sende eine Mail an "nobody@example.com" und setze das Vornamenfeld auf etwas wie "Mitglied existiert nicht mehr".', 'cp-communitie').'</em><br />';
			}

			echo '<br /><br />';
		} else {
			echo '<span style="color:red; font-weight:bold">'.sprintf(__('Benutzer %s (ID %d) konnte nicht gefunden werden.', 'cp-communitie'), $user_login, $id).'</span><br /><br />';
		}
	} 

	// Delete user, step 2
	if ( (isset($_POST['delete_cpc_user_id'])) && ($_POST['delete_cpc_user_id'] != '') ) {
		$id = $_POST['delete_cpc_user_id'];
		$to = $_POST['delete_cpc_user_transfer'];
		$user_info = get_userdata($id);
		$deleting = $user_info->user_login;
		$user_info = get_userdata($to);
		$transfer = $user_info->user_login;
		echo sprintf(__('%s wird gelöscht und %s neu zugewiesen...', 'cp-communitie'), $deleting, $transfer).' ';

		global $wpdb;

		// Delete mail
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_mail WHERE mail_from=%d OR mail_to=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete events
		$sql = "SELECT * FROM ".$wpdb->prefix."cpcommunitie_events WHERE event_owner=%d";
		$events = $wpdb->get_results($wpdb->prepare($sql, $id));
		foreach ($events as $event) {
			$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_events_bookings WHERE event_id=%d";
			$wpdb->query($wpdb->prepare($sql, $event->eid));		
		}
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_events WHERE event_owner=%d";
		$wpdb->query($wpdb->prepare($sql, $id));
		
		// Delete galleries
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_gallery WHERE owner=%d";
		$wpdb->query($wpdb->prepare($sql, $id));
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_gallery_items WHERE owner=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete friendships
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_friends WHERE friend_from=%d OR friend_to=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete followers
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_following WHERE uid=%d OR following=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete likes
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_likes WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete chat
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_chat_2 WHERE from_id=%d OR to_id=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_chat_2_users WHERE id=%d";
		$wpdb->query($wpdb->prepare($sql, $id));
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_chat_2_typing WHERE typing_from=%d OR typing_to=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete from lounge plugin
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_lounge WHERE author=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete from news
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_news WHERE author=%d OR subject=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete from subscriptions
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_subs WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete from scores in forum, before re-assigning
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_topics_scores WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Re-assign comments
		$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_comments SET subject_uid=%d WHERE subject_uid=%d";
		$wpdb->query($wpdb->prepare($sql, $to, $id));
		$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_comments SET author_uid=%d WHERE author_uid=%d";
		$wpdb->query($wpdb->prepare($sql, $to, $id));
		
		// Re-assign forum posts and images
		$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_topics SET topic_owner=%d WHERE topic_owner=%d";
		$wpdb->query($wpdb->prepare($sql, $to, $id));
		$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_topics SET uid=%d WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $to, $id));
		
		// Delete CPC user
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_usermeta WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete ClassicPress user
		wp_delete_user( $id );

		echo __('Erledigt.', 'cp-communitie');
		echo '<br /><br />';
	}

	echo __('Gib einen Benutzernamen oder eine Benutzer-ID ein:', 'cp-communitie').'<br />';
	echo '<form action="" method="POST">';
	echo '<input type="text" name="delete_cpc_user" />&nbsp;';
	echo '<input type="submit" class="button-primary" value="'.__('Benutzer finden', 'cp-communitie').'" /><br />';
	echo '</form>';

	echo '</td></tr></table>';	
	
	__cpc__show_manage_tabs_header_end();
}

function __cpc__plugin_welcome() {

	update_option(CPC_OPTIONS_PREFIX.'_motd', '');

	if ($file = @file_get_contents(CPC_WELCOME_MESSAGE)) {
		// CPC_WELCOME_MESSAGE should defined in default-constants.php and is an absolute local path and filename
		echo $file;

	} else {
	
		?>
		<div id="cpc-welcome-panel" class="welcome-panel" style="background-image: none; background-color: #dfd; margin: 30px 20px 0 0">
			<div id="motd" class="welcome-panel-content">

			    <h3><?php echo CPC_WL; ?></h3>		    
			    
				<p class="about-description">
				<?php echo sprintf(__( 'Vielen Dank für die Installation von %s v%s, willkommen an Bord! Fahre fort und besuche die <a href="%s">Installationsseite</a>, um Deine Installation/Dein Upgrade abzuschließen.', 'cp-communitie'), CPC_WL, CPC_VER, "admin.php?page=cpcommunitie_debug"); ?>
			    <?php
			    $ver = str_replace('.', '-', CPC_VER);
			    if (strpos($ver, ' ') !== false) $ver = substr($ver, 0, strpos($ver, ' ')); 
			    echo '<br />'.sprintf(__('Bitte lese immer die <a href="%s" target="_blank">Versionshinweise</a>, bevor Du %s aktualisierst.', 'cp-communitie'), 'https://cp-community.n3rds.work/cp-community-erste-schritte/releases/', CPC_WL);
				echo ' '.__( 'Und denke daran, trinke Tee, Tee ist gut.' );
				echo ' <img style="width:20px;height:20px" src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/smilies/coffee.png" alt="Tee ist gut" />';
				echo '<br /><br />'.sprintf(__('Wenn Du %s zum ersten Mal verwendest, ist das CP-TwentyTen-Theme derzeit wahrscheinlich am besten, <a href="%s">ändere es hier</a>.', 'cp-communitie'), CPC_WL, 'themes.php');
				?>
			    </p>
	
				<div class="welcome-panel-column-container">
					<div class="welcome-panel-column" style="margin-left:5px;margin-right:-5px;width:33%;">
						<h4><?php _e( 'Lege los' ); ?></h4>
						<ul>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-view-site" target="_blank" href="%s">%s</a>' ), "https://cp-community.n3rds.work/cp-community-erste-schritte/", __('Erste Schritte', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-add-page" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=cpcommunitie_debug') ), __('Aktiviere einige Funktionen', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-add-page" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=cpcommunitie_debug') ), __('Füge Deiner Webseite Seiten hinzu', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-widgets-menus" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=cpcommunitie_settings') ), __('Überprüfe Deine Einstellungen', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-write-blog" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=cpcommunitie_styles') ), __('Wähle ein Farbschema aus', 'cp-communitie') ); ?></li>
						</ul>						
					</div>
					<div class="welcome-panel-column">
						<h4><?php _e('Upgrade von einer früheren Version?', 'cp-communitie') ?></h4>
						<?php echo __('Bitte beachte folgendes:', 'cp-communitie'); ?>
						<ul>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-widgets-menus" href="%s">%s</a>' ), esc_url( admin_url('plugins.php') ), __('Stelle sicher, dass das Plugin aktiviert ist', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-add-page" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=cpcommunitie_debug') ), __('Aktiviere Funktionen auf der Installationsseite', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-widgets-menus" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=cpcommunitie_templates') ), __('Setze Deine Vorlagen zurück', 'cp-communitie') ); ?></li>
						</ul>
						<?php echo sprintf(__( 'Es ist <em>sehr wichtig</em>, dass Du die <a href="%s" target="_blank">Versionshinweise</a> liest.', 'cp-communitie'), "https://cp-community.n3rds.work/cp-community-erste-schritte/releases/"); ?><br />
                    </div>
					<div class="welcome-panel-column welcome-panel-last">
						<h4><?php _e( 'Benötigst Du ein wenig zusätzliche Hilfe?', 'cp-communitie'); ?></h4>
						<ul>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-view-site" href="%s" target="_blank">%s</a>' ), esc_url( 'https://cp-community.n3rds.work/haeufig-gestellte-fragen/' ), __('Häufig gestellte Fragen', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url( 'https://cp-community.n3rds.work/trythisfirst'), __('Versuche dies zuerst!', 'cp-communitie') ); ?></li>
                        	<li><?php echo sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url( 'https://cp-community.n3rds.work/admin-guide/' ), __('Lies den Administratorleitfaden', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url( 'https://cp-community.n3rds.work/discuss'), __('Besuche das Forum', 'cp-communitie') ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url( 'https://cp-community.n3rds.work/tutorials'), __('Schaue Dir die Tutorials an', 'cp-communitie') ); ?></li>
						</ul>
                	</div>
				</div>
	
			</div>
			
				<form action="index.php" method="post">
				<div style="float:right;margin-bottom:10px">
					<input type="submit" class="button-primary" value="<?php _e("Jetzt ausblenden (verfügbar über das Menü)", 'cp-communitie'); ?>" />
					<input type="hidden" name="cpcommunitie_hide_motd" value="Y" />
					<?php wp_nonce_field('cpcommunitie_hide_motd_nonce','cpcommunitie_hide_motd_nonce'); ?>
				</div>
				</form>
			
		</div>
	<?php
	}

}

function __cpc__plugin_reminder() {
	
	if ($file = @file_get_contents(CPC_WELCOME_MESSAGE)) {
		// CPC_WELCOME_MESSAGE should defined in default-constants.php and is an absolute local path and filename
		// As already displayed, do nothing here
	} else {
	
		if (!get_option(CPC_OPTIONS_PREFIX.'_motd')) {
			$top_margin = '5';
		} else {
			$top_margin = '30';
		}
		
		?>
		
		<div id="cpc-welcome-panel" class="welcome-panel" style="background-image: none; background-color: #ddf; margin: <?php echo $top_margin; ?>px 20px 0 0;">
			<div id="motd" class="welcome-panel-content" >

			    <h3><?php echo CPC_WL.__(' - Vervollständige Deine Installation', 'cp-communitie'); ?></h3>		    
			    
				<p class="about-description">
				<?php echo sprintf(__( 'Bitte stelle sicher, dass Deine Installation/Dein Upgrade erfolgreich abgeschlossen wurde, indem Du die <a href="%s">Installationsseite</a> besuchst.', 'cp-communitie'), "admin.php?page=cpcommunitie_debug"); ?> 
				<?php echo sprintf(__( 'Du solltest Deine <a href="%s">Vorlagen</a> zurücksetzen.', 'cp-communitie'), "admin.php?page=cpcommunitie_templates"); ?>
			    </p>
	
			</div>
			
				<form action="index.php" method="post">
				<div style="float:right;margin-bottom:10px; margin-top:20px;">
					<input type="submit" class="button-primary" value="<?php _e("Danke, hab ich erledigt...", 'cp-communitie'); ?>" />
					<input type="hidden" name="cpcommunitie_hide_reminder" value="Y" />
					<?php wp_nonce_field('cpcommunitie_hide_reminder_nonce','cpcommunitie_hide_reminder_nonce'); ?>
				</div>
				</form>

			
			
		</div>
	<?php
	}

}

function __cpc__update_templates() {

	if (isset($_POST['profile_header_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_profile_header', str_replace(chr(13), "[]", $_POST['profile_header_textarea']));
	if (isset($_POST['profile_body_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_profile_body', str_replace(chr(13), "[]", $_POST['profile_body_textarea']));
	if (isset($_POST['page_footer_textarea'])) {
		if ($_POST['page_footer_textarea'] == "") {
			update_option(CPC_OPTIONS_PREFIX.'_template_page_footer', str_replace(chr(13), "[]", sprintf("<!-- Powered by %s v%s -->", CPC_WL, get_option(CPC_OPTIONS_PREFIX."_version"))));
		} else {
			update_option(CPC_OPTIONS_PREFIX.'_template_page_footer', str_replace(chr(13), "[]", $_POST['page_footer_textarea']));
		}
	}
	if (isset($_POST['email_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_email', str_replace(chr(13), "[]", $_POST['email_textarea']));
	if (isset($_POST['template_mail_tray_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_mail_tray', str_replace(chr(13), "[]", $_POST['template_mail_tray_textarea']));
	if (isset($_POST['template_mail_message_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_mail_message', str_replace(chr(13), "[]", $_POST['template_mail_message_textarea']));
	if (isset($_POST['template_forum_header_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_forum_header', str_replace(chr(13), "[]", $_POST['template_forum_header_textarea']));
	if (isset($_POST['template_group_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_group', str_replace(chr(13), "[]", $_POST['template_group_textarea']));
	if (isset($_POST['template_forum_category_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_forum_category', str_replace(chr(13), "[]", $_POST['template_forum_category_textarea']));
	if (isset($_POST['template_forum_topic_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_forum_topic', str_replace(chr(13), "[]", $_POST['template_forum_topic_textarea']));
	if (isset($_POST['template_group_forum_category_textarea'])) {
		// Not currently supported
	}
	if (isset($_POST['template_group_forum_topic_textarea']))
		update_option(CPC_OPTIONS_PREFIX.'_template_group_forum_topic', str_replace(chr(13), "[]", $_POST['template_group_forum_topic_textarea']));			
}

function __cpc__plugin_templates() {

	global $wpdb;
	
	if (isset($_POST['cpcommunitie_template_update']) && $_POST['cpcommunitie_template_update'] == 'on') {
		
		if (is_multisite() && isset($_POST['cpcommunitie_templates_network_update']) && $_POST['cpcommunitie_templates_network_update']) {
		    $blogs = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
		    $list = '';
		    if ($blogs) {
		        foreach($blogs as $blog) {
		            switch_to_blog($blog->blog_id);
					__cpc__update_templates();
					$list .= '<br />&nbsp;&middot;&nbsp;'.get_bloginfo('name');
		        }
		        restore_current_blog();
		    }   
	
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Netzwerkvorlagen aktualisiert:'.$list, 'cp-communitie')."</p></div>";
		    
		} else {
		
			__cpc__update_templates();
	
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Webseitevorlagen aktualisiert', 'cp-communitie').".</p></div>";
		}
		
	}

	$template_profile_header = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_profile_header')));
	$template_profile_body = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_profile_body')));
	$template_page_footer = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_page_footer')));
	$template_email = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_email')));
	$template_mail_tray = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_mail_tray')));
	$template_mail_message = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_mail_message')));
	$template_forum_header = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_forum_header')));
	$template_group = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_group')));
	$template_forum_category = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_forum_category')));
	$template_forum_topic = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_forum_topic')));
	$template_group_forum_category = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_group_forum_category')));
	$template_group_forum_topic = str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_template_group_forum_topic')));

  	echo '<div class="wrap">';

	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s-Verwaltung', 'cp-communitie'), CPC_WL).'</h2><br />';
		__cpc__show_manage_tabs_header('templates');

		// Import
		echo '<div id="cpcommunitie_import_templates_form" style="display:none">';
		echo '<input type="submit" class="cpcommunitie_templates_cancel button" style="margin-left: 6px;" value="Cancel">';
		echo '<input id="cpcommunitie_import_file_button" type="submit" class="button-primary" style="float:left" value="Import"><div id="cpcommunitie_import_file_pleasewait" style="display:none;float:left;margin-left:10px;margin-right:5px;margin-top:5px;width:15px;"></div>';
		echo '<p>'.__('Füge zuvor exportierte Vorlagen in den Textbereich unten ein – stelle sicher, dass Du keinen verdächtigen Code einfügst.', 'cp-communitie').'</h3>';
		echo '<br /><table class="widefat">';
		echo '<thead>';
		echo '<tr>';
		echo '<th style="font-size:1.2em">'.__('Vorlage importieren', 'cp-communitie').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tr>';
		echo '<td>';

			echo '<textarea id="cpcommunitie_import_file" style="width:100%; height:600px;font-family:courier;font-size:11px;background-color:#fff;"></textarea>';

		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
			
		// Export
		echo '<div id="cpcommunitie_export_templates_form" style="display:none">';
		echo '<input type="submit" class="cpcommunitie_templates_cancel button" value="Cancel">';
		echo '<p>'.__('Kopiere Folgendes und füge es in einen Texteditor ein, um es zu sichern oder mit anderen zu teilen. Ändere die Kommentare nicht!', 'cp-communitie').'</h3>';
		echo '<br /><table class="widefat">';
		echo '<thead>';
		echo '<tr>';
		echo '<th style="font-size:1.2em">'.__('Vorlage exportieren', 'cp-communitie').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tr>';
		echo '<td>';

			echo '<textarea style="width:100%; height:600px;font-family:courier;font-size:11px;background-color:transparent;border:0px;">';
		
				echo '<!-- template_profile_header -->'.chr(13).chr(10);
				echo $template_profile_header.chr(13).chr(10);
				echo '<!-- end_template_profile_header -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_profile_body -->'.chr(13).chr(10);
				echo $template_profile_body.chr(13).chr(10);
				echo '<!-- end_template_profile_body -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_page_footer -->'.chr(13).chr(10);
				echo $template_page_footer.chr(13).chr(10);
				echo '<!-- end_template_page_footer -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_email -->'.chr(13).chr(10);
				echo $template_email.chr(13).chr(10);
				echo '<!-- end_template_email -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_mail_tray -->'.chr(13).chr(10);
				echo $template_mail_tray.chr(13).chr(10);
				echo '<!-- end_template_mail_tray -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_mail_message -->'.chr(13).chr(10);
				echo $template_mail_message.chr(13).chr(10);
				echo '<!-- end_template_mail_message -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_forum_header -->'.chr(13).chr(10);
				echo $template_forum_header.chr(13).chr(10);
				echo '<!-- end_template_forum_header -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_group -->'.chr(13).chr(10);
				echo $template_group.chr(13).chr(10);
				echo '<!-- end_template_group -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_forum_category -->'.chr(13).chr(10);
				echo $template_forum_category.chr(13).chr(10);
				echo '<!-- end_template_forum_category -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_forum_topic -->'.chr(13).chr(10);
				echo $template_forum_topic.chr(13).chr(10);
				echo '<!-- end_template_forum_topic -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_group_forum_category -->'.chr(13).chr(10);
				echo $template_group_forum_category.chr(13).chr(10);
				echo '<!-- end_template_group_forum_category -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_group_forum_topic -->'.chr(13).chr(10);
				echo $template_group_forum_topic.chr(13).chr(10);
				echo '<!-- end_template_group_forum_topic -->'.chr(13).chr(10).chr(13).chr(10);
		
			echo '</textarea>';

		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '<div id="cpcommunitie_templates_values">';

			echo '<input id="cpcommunitie_import_templates" type="submit" class="button" style="float:left;margin-right:6px;" value="'.__('Importieren', 'cp-communitie').'">';
			echo '<input id="cpcommunitie_export_templates" type="submit" class="button" style="float:left;" value="'.__('Exportieren', 'cp-communitie').'">';

			echo '<form action="" method="post">';
			echo '<input type="hidden" name="cpcommunitie_template_update" value="on" />';
		
			echo '<input type="submit" class="button-primary" style="float:right;" value="'.__('Speichern', 'cp-communitie').'">';
						
			$show_super_admin = (is_super_admin() && __cpc__is_wpmu());
			if ( $show_super_admin )
				echo '<div style="float:right;margin:5px 10px 0 0;"><input type="checkbox" name="cpcommunitie_templates_network_update" /> '.__('Aktualisiere beim Speichern das gesamte Netzwerk', 'cp-communitie').'</div>';

			// Profile Page Header
			echo '<br />';
			if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') == 'on') {
				echo '<br /><table class="widefat">';
				echo '<thead>';
				echo '<tr>';
				echo '<th style="font-size:1.2em">'.__('Kopfzeile der Profilseite', 'cp-communitie');
				echo ' (<a href="admin.php?page=cpcommunitie_profile">'.__('Optionen', 'cp-communitie').'</a>)';
				echo '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>';
					echo '<table style="float:right;width:39%">';
					echo '<tr>';
					echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
					echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tbody>';
					echo '<tr>';
					echo '<td>[follow]</td>';
					echo '<td>'.__('Schaltflächen \'Folgen\' und \'Entfolgen\' (erfordert Profile Plus)', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[poke]</td>';
					echo '<td>'.__('Schaltfläche "Anstupsen“ anzeigen" wie in <a href=\'admin.php?page=cpcommunitie_profile\'>Profileinstellungen</a> definiert', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[display_name]</td>';
					echo '<td>'.__('Anzeigename', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[profile_label]</td>';
					echo '<td>'.__('Vom Administrator festgelegte Profilbezeichnung', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[location]</td>';
					echo '<td>'.__('Stadt und/oder Land', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[born]</td>';
					echo '<td>'.__('Geburtstag', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[actions]</td>';
					echo '<td>'.sprintf(__('%s Schaltflächen zum Anfordern/Senden von Mails usw', 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friend')).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[avatar,x]</td>';
					echo '<td>'.__('Avatar anzeigen, Größe x in Pixel (ohne Leerzeichen)', 'cp-communitie').'</td>';
					echo '</tr>';
					if (function_exists('__cpc__profile_plus')) {				
						echo '<tr>';
						echo '<td>[ext_slug]</td>';
						echo '<td>'.__('Erweitertes Feld (Slug ersetzen)', 'cp-communitie').'</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '<textarea id="profile_header_textarea" name="profile_header_textarea" style="width:60%;height: 260px;">';
					echo $template_profile_header;
					echo '</textarea>';
					echo '<br /><a id="reset_profile_header" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<textarea id="profile_header_textarea" name="profile_header_textarea" style="display:none">';
				echo $template_profile_header;
				echo '</textarea>';
			}

			// Profile Page Body
			if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') == 'on') {
				echo '<br /><table class="widefat">';
				echo '<thead>';
				echo '<tr>';
				echo '<th style="font-size:1.2em">'.__('Body der Profilseite', 'cp-communitie').'</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>';
					echo '<table style="float:right;width:39%">';
					echo '<tr>';
					echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
					echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tbody>';
					echo '<tr>';
					echo '<td>[default]</td>';
					echo '<td>'.__('Wird verwendet, um Seitenparameter zu erzwingen (wichtig)', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[page]</td>';
					echo '<td>'.__('Wo Seiteninhalte platziert werden', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[menu]</td>';
					echo '<td>'.__('Profilmenü', 'cp-communitie').'</td>';
					echo '</tr>';
					if (function_exists('__cpc__profile_plus')) {
						echo '<tr>';
						echo '<td>[menu_tabs]</td>';
						echo '<td>'.__('Horizontales Menü', 'cp-communitie').'</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '<textarea id="profile_body_textarea" name="profile_body_textarea" style="width:60%;height: 200px;">';
					echo $template_profile_body;
					echo '</textarea>';
					echo '<br /><a id="reset_profile_body" href="javascript:void(0)">'.__('Auf Standard zurücksetzen (vertikales Menü)', 'cp-communitie').'</a>';
					echo ' | <a id="reset_profile_body_tabs" href="javascript:void(0)">'.__('Auf Standard zurücksetzen (horizontales Menü)', 'cp-communitie').'</a>';
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<textarea id="profile_body_textarea" name="profile_body_textarea" style="display:none">';
				echo $template_profile_body;
				echo '</textarea>';
			}

			// Page Footer
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Fußzeile', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
				echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[powered_by_message]</td>';
				echo '<td>'.sprintf(__('Standard-Powered-By-%s-Nachricht', 'cp-communitie'), CPC_WL).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[version]</td>';
				echo '<td>'.sprintf(__('Version von %s', 'cp-communitie'), CPC_WL).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="page_footer_textarea" name="page_footer_textarea" style="width:60%;height: 200px;">';
				echo $template_page_footer;
				echo '</textarea>';
				echo '<br /><a id="reset_page_footer" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Mail Tray Item
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Mail-Seite: Tray-Element', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
				echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[mail_sent]</td>';
				echo '<td>'.__('Wann die Nachricht gesendet wurde', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_from]</td>';
				echo '<td>'.__('Absender/Empfänger der Nachricht', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_subject]</td>';
				echo '<td>'.__('Betreff der Nachricht', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_message]</td>';
				echo '<td>'.__('Ein Ausschnitt der Mail-Nachricht', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_mail_tray_textarea" name="template_mail_tray_textarea" style="width:60%;height: 200px;">';
				echo $template_mail_tray;
				echo '</textarea>';
				echo '<br /><a id="reset_mail_tray" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';
		
			// Mail Message
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Mail-Seite: Nachricht', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
				echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[avatar,x]</td>';
				echo '<td>'.__('Avatar anzeigen, Größe x in Pixel (ohne Leerzeichen)', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_subject]</td>';
				echo '<td>'.__('Betreff der Nachricht', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_recipient]</td>';
				echo '<td>'.__('Absender/Empfänger der Nachricht', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_sent]</td>';
				echo '<td>'.__('Wann die Nachricht gesendet wurde', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[delete_button]</td>';
				echo '<td>'.__('Mail löschen Schaltfläche', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[reply_button]</td>';
				echo '<td>'.__('Mail-Antwort-Button', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[message]</td>';
				echo '<td>'.__('Die Mailnachricht', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_mail_message_textarea" name="template_mail_message_textarea" style="width:60%;height: 200px;">';
				echo $template_mail_message;
				echo '</textarea>';
				echo '<br /><a id="reset_mail_message" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';
			
			// Forum Header
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Forum-Kopfzeile', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
				echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[breadcrumbs]</td>';
				echo '<td>'.__('Spur aus Brotkrumen', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[new_topic_button]</td>';
				echo '<td>'.__('Schaltfläche Neues Thema.', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[new_topic_form]</td>';
				echo '<td>'.__('Formular für neues Thema', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[digest]</td>';
				echo '<td>'.__('Abonniere die tägliche Zusammenfassung', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[subscribe]</td>';
				echo '<td>'.__('Mail für neue Themen erhalten', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[forum_options]</td>';
				echo '<td>'.__('Suchen, Alle Aktivitäten usw', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[sharing]</td>';
				echo '<td>'.__('Sharing-Symbole', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[top_advert]</td>';
				echo '<td>'.__('Werbefläche über dem Forum', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_forum_header_textarea" name="template_forum_header_textarea" style="width:60%;height: 200px;">';
				echo $template_forum_header;
				echo '</textarea>';
				echo '<br /><a id="reset_forum_header" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Forum Categories
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Forenkategorien (Liste)', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
				echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[avatar,x]</td>';
				echo '<td>'.__('Avatar anzeigen, Größe x in Pixel (ohne Leerzeichen)', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replied]</td>';
				echo '<td>'.__('Geantwortet oder Text begonnen', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[subject]</td>';
				echo '<td>'.__('Betreff des letzten Beitrags/der letzten Antwort', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[subject_text]</td>';
				echo '<td>'.__('Text aus dem Beitrag', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[ago]</td>';
				echo '<td>'.__('Alter des letzten Beitrags/der letzten Antwort', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[post_count]</td>';
				echo '<td>'.__('Wie viele Beiträge in der nächsten Ebene dieser Kategorie', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic_count]</td>';
				echo '<td>'.__('Wie viele Themen in der nächsten Ebene dieser Kategorie', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[category_title]</td>';
				echo '<td>'.__('Titel der Kategorie', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[category_desc]</td>';
				echo '<td>'.__('Beschreibung der Kategorie', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_forum_category_textarea" name="template_forum_category_textarea" style="width:60%;height: 200px;">';
				echo $template_forum_category;
				echo '</textarea>';
				echo '<br /><a id="reset_template_forum_category" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Forum Topics
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Forenthemen (Liste)', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
				echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[avatarfirst,x]</td>';
				echo '<td>'.__('Avatar des ursprünglichen Beitrags anzeigen, Größe x in Pixel', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[avatar,x]</td>';
				echo '<td>'.__('Avatar der letzten Antwort anzeigen, Größe x in Pixel', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[startedby]</td>';
				echo '<td>'.__('Wer hat den ersten Themenbeitrag gepostet?', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[started]</td>';
				echo '<td>'.__('Alter des ersten Themenbeitrags', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replied]</td>';
				echo '<td>'.__('Wer hat zuletzt geantwortet', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic]</td>';
				echo '<td>'.__('Letzter Antworttext', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[ago]</td>';
				echo '<td>'.__('Alter der letzten Antwort', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[views]</td>';
				echo '<td>'.__('Anzahl der Aufrufe für dieses Thema', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replies]</td>';
				echo '<td>'.__('Anzahl der Antworten für dieses Thema', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic_title]</td>';
				echo '<td>'.__('Titel des Themas', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_forum_topic_textarea" name="template_forum_topic_textarea" style="width:60%;height: 200px;">';
				echo $template_forum_topic;
				echo '</textarea>';
				echo '<br /><a id="reset_template_forum_topic" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Group
			if (get_option(CPC_OPTIONS_PREFIX.'_use_group_templates') == 'on') {
				echo '<br /><a name="group_options"></a>';
				echo '<table class="widefat">';
				echo '<thead>';
				echo '<tr>';
				echo '<th style="font-size:1.2em">'.__('Gruppenseite', 'cp-communitie');
				echo ' (<a href="admin.php?page=cp-communitie/groups_admin.php">'.__('Optionen', 'cp-communitie').'</a>)';
				echo '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>';
				if (function_exists('__cpc__groups')) {
					echo '<table style="float:right;width:39%">';
					echo '<tr>';
					echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
					echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tbody>';
					echo '<tr>';
					echo '<td>[group_name]</td>';
					echo '<td>'.__('Gruppenname', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[group_description]</td>';
					echo '<td>'.__('Gruppenbeschreibung', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[actions]</td>';
					echo '<td>'.__('Beitreten/Löschen/usw.-Schaltflächen', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[avatar,x]</td>';
					echo '<td>'.__('Avatar anzeigen, Größe x in Pixel (ohne Leerzeichen)', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[default]</td>';
					echo '<td>'.__('Wird verwendet, um Seitenparameter zu erzwingen (wichtig)', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[page]</td>';
					echo '<td>'.__('Wo Seiteninhalte platziert werden', 'cp-communitie').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[menu]</td>';
					echo '<td>'.__('Gruppenmenü', 'cp-communitie').'</td>';
					echo '</tr>';
					if (function_exists('__cpc__profile_plus')) {
						echo '<tr>';
						echo '<td>[menu_tabs]</td>';
						echo '<td>'.__('Horizontales Menü', 'cp-communitie').'</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '<textarea id="template_group_textarea" name="template_group_textarea" style="width:60%;height: 200px;">';
					echo $template_group;
					echo '</textarea>';
					echo '<br /><a id="reset_group" href="javascript:void(0)">'.__('Auf Standard zurücksetzen (vertikales Menü)', 'cp-communitie').'</a>';
					echo ' | <a id="reset_group_tabs" href="javascript:void(0)">'.__('Auf Standard zurücksetzen (horizontales Menü)', 'cp-communitie').'</a>';
				}
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<textarea id="template_group_textarea" name="template_group_textarea" style="display:none">';
				echo $template_group;
				echo '</textarea>';
			}

			// Group Forum Topics
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Themen des Gruppenforums (Liste)', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
				echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[avatarfirst,x]</td>';
				echo '<td>'.__('Avatar des ursprünglichen Beitrags anzeigen, Größe x in Pixel', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[avatar,x]</td>';
				echo '<td>'.__('Avatar der letzten Antwort anzeigen, Größe x in Pixel', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[startedby]</td>';
				echo '<td>'.__('Wer hat den ersten Themenbeitrag gepostet?', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[started]</td>';
				echo '<td>'.__('Alter des ersten Themenbeitrags', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replied]</td>';
				echo '<td>'.__('Wer hat zuletzt geantwortet', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic]</td>';
				echo '<td>'.__('Letzter Antworttext', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[ago]</td>';
				echo '<td>'.__('Alter der letzten Antwort', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[views]</td>';
				echo '<td>'.__('Anzahl der Aufrufe für dieses Thema', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replies]</td>';
				echo '<td>'.__('Anzahl der Antworten für dieses Thema', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic_title]</td>';
				echo '<td>'.__('Titel des Themas', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_group_forum_topic_textarea" name="template_group_forum_topic_textarea" style="width:60%;height: 200px;">';
				echo $template_group_forum_topic;
				echo '</textarea>';
				echo '<br /><a id="reset_template_group_forum_topic" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Email Notifications
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.sprintf(__('%s Mails', 'cp-communitie'), CPC_WL).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes verfügbar', 'cp-communitie').'</td>';
				echo '<td>'.__('Ausgabe', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[message]</td>';
				echo '<td>'.__('Die Mail-Nachricht', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[footer]</td>';
				echo '<td>'.__('Nachricht in der Fußzeile', 'cp-communitie').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[powered_by_message]</td>';
				echo '<td>'.sprintf(__('Standard-Powered-By-%s-Nachricht', 'cp-communitie'), CPC_WL).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[version]</td>';
				echo '<td>'.sprintf(__('Version von %s', 'cp-communitie'), CPC_WL).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="email_textarea" name="email_textarea" style="width:60%;height: 200px;">';
				echo $template_email;
				echo '</textarea>';
				echo '<br /><a id="reset_email" href="javascript:void(0)">'.__('Zurücksetzen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';

			echo '</tbody>';
			echo '</table>';
		
			echo '</form>';
			
		echo '</div>';

		__cpc__show_manage_tabs_header_end();		
		
	echo '</div>';
}

function __cpc__plugin_moderation() {

	global $wpdb, $current_user;

	// First check if can moderate forum (Options->Forum->Permissions->Moderate)	
	// Administrators always can
	$can_moderate = current_user_can('manage_options') ? true : false;

	if (!$can_moderate && is_user_logged_in()) {
		$user = get_userdata( $current_user->ID );
		$moderators = str_replace('_', '', str_replace(' ', '', strtolower(get_option(CPC_OPTIONS_PREFIX.'_moderators'))));
		$capabilities = $user->{$wpdb->base_prefix.'capabilities'};

		if ($capabilities) {
			foreach ( $capabilities as $role => $name ) {
				if ($role) {
					$role = strtolower($role);
					$role = str_replace(' ', '', $role);
					$role = str_replace('_', '', $role);
					if (CPC_DEBUG) $html .= 'Checking user role '.$role.' against '.$moderators.'<br />';
					if (strpos($moderators, $role) !== FALSE) $can_moderate = true;
				}
			}		 														
		} else {
			// No ClassicPress role stored
		}
	}

	// Set orphaned topic?
	if (isset($_POST['cpcommunitie_cat_list_tid'])) {
		$tid = $_POST['cpcommunitie_cat_list_tid'];
		$cid = $_POST['cpcommunitie_cat_list'];
		// UPDATE topic
		$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_topics SET topic_category=%d WHERE tid=%d";
		$wpdb->query($wpdb->prepare($sql, $cid, $tid));
		// UPDATE any replies to match
		$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_topics SET topic_category=%d WHERE topic_parent=%d";
		$wpdb->query($wpdb->prepare($sql, $cid, $tid));
	}

  	echo '<div class="wrap">';
  	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s-Verwaltung', 'cp-communitie'), CPC_WL).'</h2><br />';
		__cpc__show_manage_tabs_header('posts');
		echo '<div style="float:right;">';
		echo '<a href="admin.php?page=cpcommunitie_forum">'.__('Gehe zu den Forum-Optionen', 'cp-communitie').'</a>';	 
		echo '</div>';

		if ($can_moderate) {
			  	
		  	$all = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."cpcommunitie_topics"); 
		  	$approved = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_approved = 'on'"); 
		  	$unapproved = $all-$approved;
		  	$topics_count = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_parent = 0"); 
		  	$orphaned_count = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_parent = 0 AND topic_category = 0"); 
		  	
		  	$mod = 'all';
		  	if (isset($_GET['mod']) && $_GET['mod'] != '') { $mod = $_GET['mod']; }
		  	if (!isset($_GET['mod']) && isset($_POST['mod']) && $_POST['mod'] != '') { $mod = $_POST['mod']; }
		  	
		  	if ($mod == "all") { $all_class='current'; $approved_class=''; $unapproved_class=''; $topics_class=''; $orphaned_class=''; }
		  	if ($mod == "approved") { $all_class=''; $approved_class='current'; $unapproved_class=''; $topics_class=''; $orphaned_class=''; }
		  	if ($mod == "unapproved") { $all_class=''; $approved_class=''; $unapproved_class='current'; $topics_class=''; $orphaned_class=''; }
		  	if ($mod == "topics") { $all_class=''; $approved_class=''; $unapproved_class=''; $topics_class='current'; $orphaned_class=''; }
		  	if ($mod == "orphaned") { $all_class=''; $approved_class=''; $unapproved_class=''; $topics_class=''; $orphaned_class='current'; }
		  	
		  	echo '<ul class="subsubsub" style="margin-top:-3px;">';
			echo "<li><a href='admin.php?page=cpcommunitie_moderation' class='".$all_class."'>".__('Alle', 'cp-communitie')." <span class='count'>(".$all.")</span></a> |</li>";
			echo "<li><a href='admin.php?page=cpcommunitie_moderation&mod=approved' class='".$approved_class."'>".__('Genehmigt', 'cp-communitie')." <span class='count'>(".$approved.")</span></a> |</li>"; 
			echo "<li><a href='admin.php?page=cpcommunitie_moderation&mod=unapproved' class='".$unapproved_class."'>".__('Abgelehnt', 'cp-communitie')." <span class='count'>(".$unapproved.")</span></a></li>";
			echo "<li><a href='admin.php?page=cpcommunitie_moderation&mod=topics' class='".$topics_class."'>".__('Nur Themen', 'cp-communitie')." <span class='count'>(".$topics_count.")</span></a></li>";
			echo "<li><a href='admin.php?page=cpcommunitie_moderation&mod=orphaned' class='".$orphaned_class."'>".__('Themen ohne Kategorie', 'cp-communitie')." <span class='count'>(".$orphaned_count.")</span></a></li>";
			echo "</ul>";
			
			$__cpc__search = (isset($_POST['__cpc__search'])) ? $_POST['__cpc__search'] : '';
			echo '<form action="#" method="POST">';
			echo '<input type="submit" class="button-primary" style="margin-right:15px;float:right;" value="'.__('Zurücksetzen', 'cp-communitie').'" />';
			echo '<input type="hidden" name="__cpc__search" value="" />';
			echo '</form>';
			echo '<form action="#" method="POST">';
			echo '<input type="submit" class="button-primary" style="margin-right:5px;float:right;" value="'.__('Suche', 'cp-communitie').'" />';
			echo '<input type="text" name="__cpc__search" style="margin-right:5px;margin-bottom:5px; float:right;" value="'.$__cpc__search.'" />';
			echo '</form>';
			
			// Paging info
			$showpage = 0;
			$pagesize = 20;
			$numpages = floor($all / $pagesize);
			if ($all % $pagesize > 0) { $numpages++; }
		  	if (isset($_GET['showpage']) && $_GET['showpage']) { $showpage = $_GET['showpage']-1; } else { $showpage = 0; }
		  	if ($showpage >= $numpages) { $showpage = $numpages-1; }
			$start = ($showpage * $pagesize);		
			if ($start < 0) { $start = 0; }  
					
			// Query
			$sql = "SELECT t.*, u.display_name FROM ".$wpdb->prefix.'cpcommunitie_topics'." t LEFT JOIN ".$wpdb->base_prefix.'users'." u ON t.topic_owner = u.ID ";
			if ($mod == "approved") { $sql .= "WHERE t.topic_approved = 'on' "; }
			if ($mod == "unapproved") { $sql .= "WHERE t.topic_approved != 'on' "; }
			if ($mod == "topics") { $sql .= "WHERE t.topic_parent = 0 "; }
			if ($mod == "orphaned") { $sql .= "WHERE t.topic_parent = 0 AND t.topic_category = 0 "; }
			if (isset($_POST['__cpc__search'])) {
				if (strpos($sql, 'WHERE') !== FALSE) {
					$sql .= "AND t.topic_post LIKE '%".$__cpc__search."%' ";
				} else {
					$sql .= "WHERE t.topic_post LIKE '%".$__cpc__search."%' ";
				}
			}
			$sql .= "ORDER BY tid DESC "; 
			$sql .= "LIMIT ".$start.", ".$pagesize;
			$posts = $wpdb->get_results($sql);
		
			// Pagination (top)
			echo __cpc__pagination($numpages, $showpage, "admin.php?page=cpcommunitie_moderation&mod=".$mod."&showpage=");
			
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th>ID</td>';
			echo '<th>'.__('Autor', 'cp-communitie').'</th>';
			echo '<th>'.__('Kat/ID', 'cp-communitie').'</th>';
			echo '<th style="width: 30px; text-align:center;">'.__('Status', 'cp-communitie').'</th>';
			echo '<th>'.__('Vorschau', 'cp-communitie').'</th>';
			echo '<th>'.__('IP &amp; Proxy', 'cp-communitie').'</th>';
			echo '<th>'.__('Zeit', 'cp-communitie').'</th>';
			echo '<th>'.__('Aktion', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tfoot>';
			echo '<tr>';
			echo '<th>ID</th>';
			echo '<th>'.__('Autor', 'cp-communitie').'</th>';
			echo '<th>'.__('Kat/ID', 'cp-communitie').'</th>';
			echo '<th style="width: 30px; text-align:center;">'.__('Status', 'cp-communitie').'</th>';
			echo '<th>'.__('Vorschau', 'cp-communitie').'</th>';
			echo '<th>'.__('IP &amp; Proxy', 'cp-communitie').'</th>';
			echo '<th>'.__('Zeit', 'cp-communitie').'</th>';
			echo '<th>'.__('Aktion', 'cp-communitie').'</th>';
			echo '</tr>';
			echo '</tfoot>';
			echo '<tbody>';
			
			if ($posts) {

				$forum_url = __cpc__get_url('forum');
				if (strpos($forum_url, '?') !== FALSE) {
					$q = "&";
				} else {
					$q = "?";
				}
							
				foreach ($posts as $post) {
		
					echo '<tr>';
					echo '<td valign="top" style="width: 30px">'.$post->tid.'</td>';
					echo '<td valign="top" style="width: 175px; max-width:175px;">'.$post->display_name.'</td>';
					echo '<td valign="top" style="width: 30px">'.$post->topic_category.'/'.$post->tid.'</td>';
					echo '<td valign="top" style="width: 30px; text-align:center;">';
					if ($post->topic_approved != "on") {
						echo '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/forum_orange.png" alt="Abgelehnt" />';
					} else {
						echo '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/forum_green.png" alt="Abgelehnt" />';
					}
					echo '</td>';
					echo '<td style="width:350px;max-width:350px;overflow:hidden;" valign="top">';
					if ($post->topic_parent == 0) {
						echo '<a href="'.$forum_url.$q.'cid='.$post->topic_category.'&show='.$post->tid.'">';
						echo '<strong>'.__('Neues Thema', 'cp-communitie').'</strong>';
					} else {
						echo '<a href="'.$forum_url.$q.'cid='.$post->topic_category.'&show='.$post->topic_parent.'">';
						echo '<strong>'.__('Neue Antwort', 'cp-communitie').'</strong>';
					}
					echo '</a>';
					echo ' ('.__('Eltern', 'cp-communitie').'='.$post->topic_parent.')<br />';
					$preview = stripslashes($post->topic_post);
					if ( strlen($preview) > 150 ) { $preview = substr($preview, 0, 150)."..."; }
					echo '<div style="float: left;">'.$preview;
					if ( strlen($preview) > 150 ) { 
						echo '<span class="show_full_post" title="'.stripslashes(str_replace('"', '&quot;', $post->topic_post)).'" style="margin-left:6px; cursor:pointer; text-decoration:underline;">'.__('Ansehen', 'cp-communitie').'</span>';
					}
					echo '</div>';
					echo '</td>';
					echo '<td valign="top" style="width: 150px">'.$post->remote_addr.'<br />'.$post->http_x_forwarded_for.'</td>';
					echo '<td valign="top" style="width: 150px">'.$post->topic_started.'</td>';
					echo '<td valign="top" style="width: 150px">';
					$showpage = (isset($_GET['showpage'])) ? $_GET['showpage'] : 0;
					if ($post->topic_approved != "on" ) {
						echo "<a href='admin.php?page=cpcommunitie_moderation&action=post_approve&showpage=".$showpage."&tid=".$post->tid."'>".__('Genehmigen', 'cp-communitie')."</a> | ";
					}
					echo "<span class='trash delete'><a href='admin.php?page=cpcommunitie_moderation&action=post_del&showpage=".$showpage."&tid=".$post->tid."'>".__('Müll', 'cp-communitie')."</a></span>";
					// Change category
					echo '<form action="#" method="POST">';
					echo '<input type="hidden" name="cpcommunitie_cat_list_tid" value="'.$post->tid.'">';
					echo '<input type="hidden" name="mod" value="'.$mod.'">';
					if ($post->topic_parent == 0) {
						echo '<div style="width:325px">';
						$sql = "SELECT * from ".$wpdb->prefix."cpcommunitie_cats ORDER BY title";
						$c = $wpdb->get_results($sql);
						if ($c) {
							echo '<SELECT NAME="cpcommunitie_cat_list">';
							echo '<OPTION VALUE="0">'.__('Kategorie wechseln...', 'cp-communitie').'</OPTION>';
							foreach ($c as $cat) {
								echo '<OPTION VALUE="'.$cat->cid.'"';
								if ($cat->cid == $post->topic_category) echo ' SELECTED';
								echo '>'.stripslashes($cat->title).'</OPTION>';
							}
							echo '</SELECT>';
							echo '<INPUT TYPE="SUBMIT" CLASS="button-primary" VALUE="'.__('Setzen', 'cp-communitie').'" />';
						}
						echo "</div></form>";
					}
					echo '</td>';
					echo '</tr>';			
		
				}
			} else {
				echo '<tr><td colspan="6">&nbsp;</td></tr>';
			}
			echo '</tbody>';
			echo '</table>';
		
			// Pagination (bottom)
			echo __cpc__pagination($numpages, $showpage, "admin.php?page=cpcommunitie_moderation&mod=".$mod."&showpage=");

		} else {

			echo __('Du kannst das Forum leider nicht moderieren.', 'cp-communitie');

		}
		__cpc__show_manage_tabs_header_end();
		
	echo '</div>'; // End of wrap div

}

function __cpc__plugin_debug() {

/* ============================================================================================================================ */

	global $wpdb, $current_user;
	wp_get_current_user();

 	$wpdb->show_errors();

  	$fail = "<span style='color:red; font-weight:bold;'>";
  	$fail2 = "</span><br /><br />";
 	
  	echo '<div class="wrap">';
        	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Installation', "cp-communitie"), CPC_WL).'</h2>';

	  	// ********** Summary
		echo '<div style="margin-top:10px; margin-bottom:10px">';
			echo sprintf(__("Besuche diese Seite, um die Installation abzuschließen, nachdem Du einer Seite einen %s-Shortcode hinzugefügt hast, Seiten mit %s Shortcodes wechselst, wenn Du ClassicPress Permalinks änderst, oder wenn Du Probleme hast.", 'cp-communitie'), CPC_WL, CPC_WL);
		echo '</div>';

		// Check for activated/deactivated sub-plugins	 
		if (isset($_POST['__cpc__installation_update']) && $_POST['__cpc__installation_update'] == 'Y') {
			// Network activations
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__events_main_network_activated', isset($_POST['__cpc__events_main_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__forum_network_activated', isset($_POST['__cpc__forum_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__profile_network_activated', isset($_POST['__cpc__profile_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__mail_network_activated', isset($_POST['__cpc__mail_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__members_network_activated', isset($_POST['__cpc__members_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__add_notification_bar_network_activated', isset($_POST['__cpc__add_notification_bar_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__facebook_network_activated', isset($_POST['__cpc__facebook_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__gallery_network_activated', isset($_POST['__cpc__gallery_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__groups_network_activated', isset($_POST['__cpc__groups_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__lounge_main_network_activated', isset($_POST['__cpc__lounge_main_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__news_main_network_activated', isset($_POST['__cpc__news_main_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__profile_plus_network_activated', isset($_POST['__cpc__profile_plus_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__rss_main_network_activated', isset($_POST['__cpc__rss_main_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__mailinglist_network_activated', isset($_POST['__cpc__mailinglist_network_activated']), true);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__wysiwyg_network_activated', isset($_POST['__cpc__wysiwyg_network_activated']), true);
			// Site specific
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__events_main_activated', isset($_POST['__cpc__events_main_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__forum_activated', isset($_POST['__cpc__forum_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__profile_activated', isset($_POST['__cpc__profile_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__mail_activated', isset($_POST['__cpc__mail_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__members_activated', isset($_POST['__cpc__members_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__add_notification_bar_activated', isset($_POST['__cpc__add_notification_bar_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__facebook_activated', isset($_POST['__cpc__facebook_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__gallery_activated', isset($_POST['__cpc__gallery_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__groups_activated', isset($_POST['__cpc__groups_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__lounge_main_activated', isset($_POST['__cpc__lounge_main_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__news_main_activated', isset($_POST['__cpc__news_main_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__profile_plus_activated', isset($_POST['__cpc__profile_plus_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__rss_main_activated', isset($_POST['__cpc__rss_main_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__mailinglist_activated', isset($_POST['__cpc__mailinglist_activated']), false);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'__cpc__wysiwyg_activated', isset($_POST['__cpc__wysiwyg_activated']), false);
		}

		if (isset($_POST['cpcommunitie_validation_code'])):
            $clean = preg_replace('/[^,;a-zA-Z0-9_-]/','',$_POST['cpcommunitie_validation_code']);
			__cpc__update_option(CPC_OPTIONS_PREFIX.'_activation_code', $clean, true);
        endif;

		$show_super_admin = (is_super_admin() && __cpc__is_wpmu());
				
		echo "<div style='margin-top:15px; margin-bottom:15px; '>";

			$colspan = 5;
			if ( $show_super_admin ) $colspan = 6;

			echo '<form action="admin.php?page=cpcommunitie_debug" method="POST">';
			echo '<input type="hidden" name="__cpc__installation_update" value="Y" />';
			echo '<table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			if ( $show_super_admin )
				echo '<th width="10px">'.__('Netzwerk&nbsp;aktiviert', 'cp-communitie').'</th>';
			echo '<th width="10px">'.__('Aktiviert', 'cp-communitie').'</th>';
			echo '<th width="150px">'.__('Modul', 'cp-communitie').'</th>';
			echo '<th>'.__('ClassicPress-Seite/URL gefunden', 'cp-communitie').'</th>';
			echo '<th  style="text-align:center;width:90px;">'.__('Status', 'cp-communitie');
			if (current_user_can('update_core'))
				echo ' [<a href="javascript:void(0);" id="cpcommunitie_url">?</a>]</tg>';
			if (current_user_can('update_core'))
				echo '<th class="cpcommunitie_url">'.sprintf(__('%s-Einstellungen', 'cp-communitie'), CPC_WL_SHORT).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
				if ( $show_super_admin )
					echo '<td>&nbsp;</td>';
				echo '<td style="text-align:center"><img src="'.CPC_PLUGIN_URL.'/images/tick.png" /></td>';
				echo '<td>'.__('Core', 'cp-communitie').'</td>';
				echo '<td>&nbsp;</td>';
				echo '<td style="text-align:center"><img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/smilies/good.png" /></td>';
				if (current_user_can('update_core'))
					echo '<td class="cpcommunitie_url" style="background-color:#efefef">-</td>';
			echo '</tr>';

			// Get version numbers installed (if applicable)

			__cpc__install_row('profile', __('Profil', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-profile', '__cpc__profile', get_option(CPC_OPTIONS_PREFIX.'_profile_url'), CPC_DIR.'/profile.php', 'admin.php?page=profile', '__cpc__<a href="admin.php?page=cpcommunitie_profile">Einstellungen</a>');
			__cpc__install_row('forum', __('Forum', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-forum', '__cpc__forum', get_option(CPC_OPTIONS_PREFIX.'_forum_url'), CPC_DIR.'/forum.php', 'admin.php?page=forum', '__cpc__<a href="admin.php?page=cpcommunitie_forum">Einstellungen</a>');
			__cpc__install_row('members', __('Mitglieder', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-members', '__cpc__members', get_option(CPC_OPTIONS_PREFIX.'_members_url'), CPC_DIR.'/members.php', 'admin.php?page=__cpc__members_menu', '__cpc__<a href="admin.php?page=__cpc__members_menu">Einstellungen</a>');
			__cpc__install_row('mail', __('Mail', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-mail', '__cpc__mail', get_option(CPC_OPTIONS_PREFIX.'_mail_url'), CPC_DIR.'/mail.php', '', '__cpc__<a href="admin.php?page=__cpc__mail_menu">Einstellungen</a>');		
			__cpc__install_row('panel', __('Panel/Chat', 'cp-communitie'), '', '__cpc__add_notification_bar', '-', CPC_DIR.'/panel.php', 'admin.php?page=bar', '__cpc__<a href="admin.php?page=cpcommunitie_bar">Einstellungen</a>');
			__cpc__install_row('wysiwyg', __('Forum-WYSIWYG-Editor', 'cp-communitie'), '', '__cpc__wysiwyg', '-', '', CPC_DIR.'/forum.php', '__cpc__<a href="admin.php?page=cpcommunitie_forum">Einstellungen</a>');
			__cpc__install_row('profile_plus', __('Profile_Plus', 'cp-communitie'), '', '__cpc__profile_plus', '-', 'cp-communitie/plus.php', 'admin.php?page='.CPC_DIR.'/plus_admin.php', '__cpc__<a href="admin.php?page=cp-communitie/plus_admin.php">Einstellungen</a>');
			__cpc__install_row('groups', __('Gruppen', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-groups', '__cpc__groups', get_option(CPC_OPTIONS_PREFIX.'_groups_url'), CPC_DIR.'/groups.php', 'admin.php?page='.CPC_DIR.'/groups_admin.php', '__cpc__<a href="admin.php?page=cp-communitie/groups_admin.php">Einstellungen</a>');
			__cpc__install_row('group', __('Gruppe', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-group', '__cpc__group', get_option(CPC_OPTIONS_PREFIX.'_group_url'), CPC_DIR.'/groups.php', '', '__cpc__');
			__cpc__install_row('gallery', __('Galerie', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-galleries', '__cpc__gallery', '/gallery/', CPC_DIR.'/gallery.php','admin.php?page='.CPC_DIR.'/gallery_admin.php', '__cpc__<a href="admin.php?page=cp-communitie/gallery_admin.php">Einstellungen</a>');
			__cpc__install_row('alerts', __('Benachrichtigungen', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-alerts', '__cpc__news_main', '-', CPC_DIR.'/news.php', 'admin.php?page='.CPC_DIR.'/news_admin.php', '__cpc__<a href="admin.php?page=cp-communitie/news_admin.php">Einstellungen</a>');
			__cpc__install_row('events', __('Veranstaltungen', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-events', '__cpc__events_main', '-', CPC_DIR.'/events.php', 'admin.php?page='.CPC_DIR.'/events_admin.php', '__cpc__<a href="admin.php?page=cp-communitie/events_admin.php">Einstellungen</a>');
			__cpc__install_row('reply_by_email', __('Antwort_per_Mail', 'cp-communitie'), '', '__cpc__mailinglist', '-', CPC_DIR.'/mailinglist.php', 'admin.php?page='.CPC_DIR.'/cpcommunitie_mailinglist_admin.php', '__cpc__<a href="admin.php?page=cp-communitie/mailinglist_admin.php">Einstellungen</a>');
			__cpc__install_row('the_lounge', __('Die_Lounge', 'cp-communitie'), CPC_SHORTCODE_PREFIX.'-lounge', '__cpc__lounge_main', '-', CPC_DIR.'/lounge.php', 'admin.php?page='.CPC_DIR.'/lounge_admin.php', '__cpc__<a href="admin.php?page=cp-communitie/lounge_admin.php">Einstellungen</a>');
			__cpc__install_row('rss_feed', __('RSS_Feed', 'cp-communitie'), '', '__cpc__rss_main', '-', CPC_DIR.'/rss.php', '', '__cpc__');
			__cpc__install_row('facebook', __('Facebook', 'cp-communitie'), '', '__cpc__facebook', '-', CPC_DIR.'/facebook.php', 'admin.php?page='.CPC_DIR.'/facebook_admin.php', '__cpc__');
	
			do_action('__cpc__installation_hook');

			echo '<tr style="height:50px">';
				echo '<td style="vertical-align:middle;padding-left:10px;" colspan='.$colspan.'>';
				echo '<input type="submit" class="button-primary" value="'.__('Aktualisieren', 'cp-communitie').'" />';
				echo '</td>';
			echo '</tr>';
			
			echo '</tbody>';
			echo '</table>';
			
			echo '</form>';
				
		echo "</div>";

		// Check for request in URL to go straight to site
		if (isset($_GET['gotosite']) && $_GET['gotosite'] == '1') {
			echo '<script>';
			echo 'window.location.replace("'.get_bloginfo('url').'?p='.$_GET['pid'].'");';
			echo '</script>';
			die();
		}
		
		// Only show following to admins and above
		if (current_user_can('update_core') && (!CPC_HIDE_INSTALL_INFO)) {
			
			if (isset($_POST['__cpc__install_assist_action'])) {
				$action = $_POST['__cpc__install_assist_action'];
				if ($action == 'hide') {
					update_option(CPC_OPTIONS_PREFIX."_install_assist", false);
				} else {
					update_option(CPC_OPTIONS_PREFIX."_install_assist", true);
				}
			}
			
			$show = get_option(CPC_OPTIONS_PREFIX."_install_assist");
			
			if (!$show) {

				echo '<form action="" method="POST">';
				echo '<input type="hidden" name="__cpc__install_assist_action" value="show" />';
				echo "<input id='__cpc__install_assist_button' type='submit' class='button-secondary' value='".__('Installationshilfe anzeigen', 'cp-communitie')."' />";
				echo '</form>';
				
			} else {
				
				echo '<form action="" method="POST">';
				echo '<input type="hidden" name="__cpc__install_assist_action" value="hide" />';
				echo "<input id='__cpc__install_assist_button' type='submit' class='button-secondary' value='".__('Installationshilfe ausblenden', 'cp-communitie')."' />";
				echo '</form>';
				
				echo "<div id='__cpc__install_assist' style='margin-top:15px'>";
				
					echo "<div style='width:49%; float:left;'>";
					
						echo '<table class="widefat"><tr><td style="padding:0 0 0 10px">';
							echo '<h2 style="margin-bottom:10px">'.__('Core Information', 'cp-communitie').'</h2>';
				
							echo '<p>';
							echo __('Webseite-Domänenname', 'cp-communitie').': '.get_bloginfo('url').'<br />';
							echo '</p>';
				
							echo "<p>";
				
								global $blog_id;
								echo __("ClassicPress-Site-ID:", 'cp-communitie')." ".$blog_id.'<br />';
								echo __("Name der ClassicPress-Site:", 'cp-communitie')." ".get_bloginfo('name').'<br />';
								echo '<br />';
								echo sprintf(__("%s interne Codeversion:", 'cp-communitie'), CPC_WL)." ";
								$ver = get_option(CPC_OPTIONS_PREFIX."_version");
								if (!$ver) { 
									echo "<br /><span style='clear:both;color:red; font-weight:bold;'>Error!</span> ".__('Keine Codeversion festgelegt. Versuche die Datenbanktabellen <a href="admin.php?page=cpcommunitie_debug&force_create_cpc=yes">neu zu erstellen/zu ändern</a>.', 'cp-communitie')."</span><br />"; 
								} else {
									echo $ver."<br />";
								}
						
							echo "</p>";
							
							// Curl / JSON
							$disabled_functions=explode(',', ini_get('disable_functions'));
							$ok=true;
							if (!is_callable('curl_init')) {
								echo $fail.__('Die CURL-PHP-Erweiterung ist nicht installiert. Bitte wende Dich an Dein Hosting-Unternehmen.', 'cp-communitie').$fail2;
								$ok=false;
							} else {
								if (in_array('curl_init', $disabled_functions)) {
									echo $fail.__('Die CURL-PHP-Erweiterung ist in php.ini deaktiviert, bitte wende Dich an Dein Hosting-Unternehmen.', 'cp-communitie').$fail2;
									$ok=false;
								} else {
									echo '<p>'.__('Die CURL-PHP-Erweiterung ist in php.ini installiert und aktiviert.', 'cp-communitie').'</p>';
								}
							}
							if (!is_callable('json_decode')) {
								echo $fail.__('Die JSON-PHP-Erweiterung ist nicht installiert. Bitte wende Dich an Dein Hosting-Unternehmen.', 'cp-communitie').$fail2;
								$ok=false;
							} else {
								if (in_array('json_decode', $disabled_functions)) {
									echo $fail.__('Die JSON-PHP-Erweiterung ist in php.ini deaktiviert, bitte wende Dich an Dein Hosting-Unternehmen.', 'cp-communitie').$fail2;
									$ok=false;
								} else {
									echo "<p>".__('Die JSON-PHP-Erweiterung ist in php.ini installiert und aktiviert.', 'cp-communitie')."</p>";
								}
							}
							if (!$ok)
								echo $fail.__('Bitte wende Dich an Dein Hosting-Unternehmen, um die Installation/Aktivierung des oben Genannten anzufordern.', 'cp-communitie').$fail2;
							
							// Debug mode?
							if (CPC_DEBUG) {
								echo "<p style='font-weight:bold'>".__('Wird im DEBUG-Modus ausgeführt.', 'cp-communitie')."</p>";
							}
						echo '</td></tr></table>';
		
						// Integrity check
						echo '<table class="widefat" style="margin-top:10px"><tr><td style="padding:0 10px 0 10px">';
							echo '<a name="ric"></a>';
							echo '<h2 style="margin-bottom:10px">'.__('Integritätsprüfung', 'cp-communitie').'</h2>';
							
							if (isset($_POST['cpcommunitie_ric'])) {
								$report = '';

								// Check that user meta matches user table and delete to synchronise
								if (isset($_POST['cpcommunitie_ric_syn'])) {
									
									if (!isset($_POST['cpcommunitie_ric_username'])) {
										$sql = "SELECT user_id
												FROM ".$wpdb->base_prefix."usermeta m 
												LEFT JOIN ".$wpdb->base_prefix."users u 
												ON m.user_id = u.ID 
												WHERE u.ID IS NULL;";
										$missing_users = $wpdb->get_results($sql); 
									} else {
										$sql = "SELECT user_id
												FROM ".$wpdb->base_prefix."usermeta m 
												LEFT JOIN ".$wpdb->base_prefix."users u 
												ON m.user_id = u.ID 
												WHERE u.ID IS NULL AND u.user_login = %s;";
										$missing_users = $wpdb->get_results($wpdb->prepare($sql, $_POST['cpcommunitie_ric_username'])); 
									}
											
									if ($missing_users) {
										foreach ($missing_users as $missing) {
											$sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d";
											$wpdb->query($wpdb->prepare($sql, $missing->uid)); 
											$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_friends WHERE friend_from = %d or friend_to = %d";
											$wpdb->query($wpdb->prepare($sql, $missing->uid, $missing->uid)); 
											$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_group_members WHERE member_id = %d";
											$wpdb->query($wpdb->prepare($sql, $missing->uid)); 			
										}
									}	
									$report .= __("Benutzertabellen synchronisiert", 'cp-communitie').".<br />";															
								}
								
								// Fix missing categories, where replies exist with a category
	  							$sql = "SELECT * from ".$wpdb->prefix."cpcommunitie_topics where topic_parent = 0 AND topic_category = 0 order by tid desc";
							  	$a = $wpdb->get_results($sql);
							  	$updated = 0;
							  	foreach ($a as $b) {
							  	    if ($b->topic_category == 0) {
										// Got no category, so check for a reply that has a category
							  	        $sql = "select * from ".$wpdb->prefix."cpcommunitie_topics where topic_category > 0 AND topic_parent = %d LIMIT 0,1";
							  	        $d = $wpdb->get_row($wpdb->prepare($sql, $b->tid));
							  	        if ($d) {
							  	            // Update the parent category from 0, to that of it's reply
											$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_topics SET topic_category = %d WHERE tid = %d";
											$wpdb->query($wpdb->prepare($sql, $d->topic_category, $b->tid));
											$updated++;
							  	        }
							  	    }
							  	}
							  	if (count($a) > 0) 
									$report .= sprintf( __("Bei %d Themen fehlten Kategorien, %d wurden durch Kopieren aus einer ihrer Antworten behoben", 'cp-communitie'), count($a), $updated ).".<br />";
									if (count($a)-$updated > 0) 
										$report .= sprintf(__('Korrigiere die verbleibenden verwaisten Themen <a href="%s">hier</a>.', 'cp-communitie'), 'admin.php?page=cpcommunitie_moderation&mod=orphaned').'<br />';
							  								    	
								// Update topic categories (if category missing and with a parent)
								$sql = "SELECT * FROM ".$wpdb->prefix."cpcommunitie_topics
										WHERE topic_category = 0 AND topic_parent > 0";
								$topics = $wpdb->get_results($sql);
								if ($topics) {
									foreach ($topics AS $topic) {
										// Get the category of the parent and update
										$sql = "SELECT topic_category FROM ".$wpdb->prefix."cpcommunitie_topics WHERE tid = %d";
										$parent_cat = $wpdb->get_var($wpdb->prepare($sql, $topic->topic_parent));
										// Update this topic's category to it
										$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_topics SET topic_category = %d WHERE tid = %d";
										$wpdb->query($wpdb->prepare($sql, $parent_cat, $topic->tid));
									}
									$report .= sprintf( __("Bei %d Antworten fehlten Kategorien, die daher von der übergeordneten Kategorie kopiert wurden", 'cp-communitie'), count($topics) )."<br />";
								}
								
								// If a members folder exists in cpc-content, but user doesn't exist, report that it exists (can remove?)
								$path = get_option(CPC_OPTIONS_PREFIX.'_img_path').'/members';
								if(file_exists($path) && is_dir($path)) { 
									if ($handler = opendir($path)) {
										while (($sub = readdir($handler)) !== FALSE) {
											if ($sub != "." && $sub != ".." && $sub != "Thumb.db" && $sub != "Thumbs.db" && is_numeric($sub)) {
												if (is_dir($path."/".$sub)) {
													$id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID = %d", $sub));
													if (!$id) {
														$report .= 'Benutzer-ID ['.$sub.'] nicht gefunden, aber '.$path."/".$sub.' existiert<br />';
														//__cpc__rrmdir($path."/".$sub);
													}
												}
											}
										}
									}
								} else {
									// Folder doesn't exist so create it
									if (!mkdir($path, 0777, true)) {
										$report .= sprintf(__("Der Bild-/Medienpfad %s konnte nicht erstellt werden (%s), prüfe die Rechte und führe die Integritätsprüfung erneut aus", 'cp-communitie'), CPC_WL, $path);
									} else {
										$report .= sprintf(__("Der %s Bilder-/Medienpfad (%s) wurde erstellt", 'cp-communitie'), CPC_WL, $path);
									}
								}
								
								// Remove any users with user_id = Null
								$sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE user_id IS Null";
								$wpdb->query($sql);
			
								// Get a list of users that have duplicate keys in wp_usermeta
									$sql = "SELECT DISTINCT user_id FROM (
										SELECT user_id, meta_key, COUNT( user_id ) AS cnt
										FROM ".$wpdb->base_prefix."usermeta
										GROUP BY user_id, meta_key
										HAVING meta_key LIKE  '%cpcommunitie%'
										AND cnt > 1
										) AS results";
								$users = $wpdb->get_results($sql); 
			
								// Loop through each user
								if ($users) {
									foreach ($users AS $user) {
			
										if ($user->user_id != null) {
			
											$report .= '<strong>'.sprintf(__("Doppelte meta_keys für Benutzer %d gefunden", 'cp-communitie'), $user->user_id).'</strong><br />';
			
											// Get list of meta keys that have duplicates
											$sql = "SELECT DISTINCT meta_key 
													FROM ".$wpdb->base_prefix."usermeta
													WHERE user_id = ".$user->user_id."
													AND meta_key LIKE '%cpcommunitie%'";
			
											$meta_keys = $wpdb->get_results($sql);
			
											// For each meta_key get latest, delete them all and re-add just one
											if ($meta_keys) {
												foreach ($meta_keys AS $meta) {
			
													$sql = "SELECT umeta_id, meta_key, meta_value 
															FROM ".$wpdb->base_prefix."usermeta
															WHERE user_id = %d
															AND meta_key =  %s
															ORDER BY umeta_id DESC 
															LIMIT 0 , 1";
			
													$single = $wpdb->get_row($wpdb->prepare($sql, $user->user_id, $meta->meta_key));
			
													// Don't include following as standard as may produce large HTML output
													// $report .= sprintf(__("Setting user %d meta_key '%s' as %s", 'cp-communitie'), $user->user_id, $single->meta_key, $single->meta_value).'<br />';
			
													// Do the clean up
													$sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d AND meta_key = %s";
													$wpdb->query($wpdb->prepare($sql, $user->user_id, $single->meta_key));
													update_user_meta( $user->user_id, $single->meta_key, $single->meta_value );
			
												}
											}
																							
										}
									}
								}
								
								// Update lat/long for distance calculation for all those users with a city and country
								if (function_exists('__cpc__profile_plus')) {
									if (isset($_POST['cpcommunitie_ric_username']) && $_POST['cpcommunitie_ric_username'] != '') {
										$sql = "SELECT * FROM ".$wpdb->base_prefix."users WHERE user_login = %s OR display_name = %s";
										$users = $wpdb->get_results($wpdb->prepare($sql, $_POST['cpcommunitie_ric_username'], $_POST['cpcommunitie_ric_username']));
										$report .= sprintf(__("Geokodierung für %s", 'cp-communitie'), $_POST['cpcommunitie_ric_username']).'<br />';
									} else {
										$sql = "SELECT * FROM ".$wpdb->base_prefix."users";
										$users = $wpdb->get_results($sql);
									}
									$not_reported_limit = false;
									
									if ($users) {

										foreach ($users as $user) {
											if (isset($_POST['cpcommunitie_ric_username']) && $_POST['cpcommunitie_ric_username'] != '')
												$report .= sprintf(__("%s gefunden", 'cp-communitie'), $_POST['cpcommunitie_ric_username']).'<br />';
											
											$lat = get_user_meta($user->ID, 'cpcommunitie_plus_lat', true);
											$lng = get_user_meta($user->ID, 'cpcommunitie_plus_long', true);
											
											if ( (!$lat || !$lng) || (isset($_POST['cpcommunitie_ric_username']) && $_POST['cpcommunitie_ric_username'] != '') ) {
												$city = get_user_meta($user->ID, 'cpcommunitie_extended_city', true);
												$country = get_user_meta($user->ID, 'cpcommunitie_extended_country', true);
		
												if ($city != '' && $country != '') {
													$city = str_replace(' ','%20',$city);
													$country = str_replace(' ','%20',$country);
									
													$fgc = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$city.'+'.$country.'&sensor=false';
											
													if ($json = @file_get_contents($fgc) ) {
														if (CPC_DEBUG || (isset($_POST['cpcommunitie_ric_username']) && $_POST['cpcommunitie_ric_username'] != '')) $report .= "URL mit Google API verbinden mit: ".$fgc."<br />";
														$json_output = json_decode($json, true);
														$json_output_array = __cpc__displayArray($json_output);
														if (strpos($json_output_array, "OVER_QUERY_LIMIT") !== false) {
															if (!$not_reported_limit) {
																$report .= "<span style='color:red; font-weight:bold;'>".__("Google-API-Limit erreicht, bitte wiederhole dies für verbleibende Nutzer oder gib einen Nutzer-Login ein.", 'cp-communitie').'</span><br />';														
																$not_reported_limit = true;
															}
														} else {
															$lat_new = $json_output['results'][0]['geometry']['location']['lat'];
															$lng_new = $json_output['results'][0]['geometry']['location']['lng'];														
															if (CPC_DEBUG || (isset($_POST['cpcommunitie_ric_username']) && $_POST['cpcommunitie_ric_username'] != ''))
																$report .= " - Google-Ergebnisse: ".$lat_new."/".$lng_new."<br />";
			
															update_user_meta($user->ID, 'cpcommunitie_plus_lat', $lat_new);
															update_user_meta($user->ID, 'cpcommunitie_plus_long', $lng_new);
															
															if (!$not_reported_limit)
																$report .= sprintf(__("%s [%d] Geocode-Informationen für %s,%s von %s,%s auf %s,%s aktualisiert", 'cp-communitie'), $user->display_name, $user->ID, $city, $country, $lat, $lng, $lat_new, $lng_new).'<br />';
														}
													} else {
														$report .= "<span style='color:red; font-weight:bold;'>".sprintf(__("Fehler beim Verbinden mit Google API<br>%s", 'cp-communitie'), $json).'</span><br />';
													}
												}
											}
										}
									} else {
										$report .= __("Keine Benutzer gefunden.").'<br />';
									}
									
								} else {
									$report .= __("Geokodierung wird nicht überprüft, da Profile Plus nicht aktiviert ist.").'<br />';
								}								
								
								// Remove dead friendships
								$del_count = 0;
								$sql = "SELECT fid from ".$wpdb->base_prefix."cpcommunitie_friends f
										left JOIN ".$wpdb->base_prefix."users u ON u.ID = f.friend_from
										WHERE u.ID is null";
								$orphaned = $wpdb->get_results($sql);
								foreach ($orphaned as $orphan) {
									$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_friends WHERE fid = %d";
									$wpdb->query($wpdb->prepare($sql, $orphan->fid));
									$del_count++;
								}
								$sql = "SELECT fid from ".$wpdb->base_prefix."cpcommunitie_friends f
										left JOIN ".$wpdb->base_prefix."users u ON u.ID = f.friend_to
										WHERE u.ID is null";
								$orphaned = $wpdb->get_results($sql);
								foreach ($orphaned as $orphan) {
									$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_friends WHERE fid = %d";
									$wpdb->query($wpdb->prepare($sql, $orphan->fid));
									$del_count++;
								}
								if ($del_count) $report .= sprintf(__("%d verwaiste Freundschaften entfernt.", 'cp-communitie'), $del_count).'<br />';
			
								// Filter
								$report = apply_filters( '__cpc__integrity_check_hook', $report );						
			
								// Done
								echo "<div style='margin-top:15px;margin-right:15px; border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								if ($report == '') { $report = __('Keine Probleme gefunden.', 'cp-communitie'); }
								echo "<strong>".__("Integritätsprüfung abgeschlossen.", 'cp-communitie')."</strong><br />".$report;
								echo "</div>";
								
							}
				
										
							echo "<p>".sprintf(__('<strong>Klicke nur einmal auf die Schaltfläche unten!<br />Dies kann eine Weile dauern, wenn Du viele Benutzer hast. Bitte warte bis es fertig ist.<br />Wenn Dein Browser "zeitüberschreitet", kannst Du den Vorgang Wiederholen, bis er abgeschlossen ist.</strong><br />Du solltest die Integritätsprüfung regelmäßig durchführen, vorzugsweise täglich. Bevor Du eine Support-Anfrage meldest, führe bitte die %s-Integritätsprüfung durch. Dadurch werden potenzielle Ungenauigkeiten in der Datenbank beseitigt.', 'cp-communitie'), CPC_WL_SHORT)."</p>";
				
							echo '<form method="post" action="#ric">';
							echo '<input type="hidden" name="cpcommunitie_ric" value="Y">';
							echo __('Gib einen Anmelde-/Anzeigenamen für den Benutzer ein, um ihn auf einen Benutzer zu beschränken.', 'cp-communitie').'<br />';
							echo '<input type="text" name="cpcommunitie_ric_username" value=""><br />';
							echo '<input type="checkbox" name="cpcommunitie_ric_syn"> '.__('Synchronisiere ClassicPress-Benutzertabellen mit CP Community', 'cp-communitie');
							echo '<p></p><input type="submit" name="Submit" class="button-primary" value="'.__('Führe eine Integritätsprüfung durch', 'cp-communitie').'" /></p>';
							echo '</form>';
		
						echo '</td></tr></table>';
		
						// ********** Reset database version
						echo '<table class="widefat" style="margin-top:10px"><tr><td style="padding:0 0 0 10px">';
							echo '<h2 style="margin-bottom:10px">'.sprintf(__('Aktualisiere %s', 'cp-communitie'), CPC_WL).'</h2>';
							echo "<p>".__('Um die Erstellung/Änderung der Datenbanktabelle erneut auszuführen, <a href="admin.php?page=cpcommunitie_debug&force_create_cpc=yes">klicke hier</a>.<br /><strong>Dadurch werden keine vorhandenen Tabellen oder Daten zerstört</strong>.', 'cp-communitie')."</p>";
							echo "<p>".sprintf(__('Dadurch wird auch die <a href="%s">Willkommensseite</a> von %s angezeigt.', 'cp-communitie'), CPC_WL, "admin.php?page=cpcommunitie_welcome")."</p>";
						echo '</td></tr></table>';
		
						// Purge chat
						echo '<table class="widefat" style="margin-top:10px"><tr><td style="padding:0 0 0 10px">';
							echo "<a name='purge'></a>";
							echo '<h2 style="margin-bottom:10px">'.__('Forum/Chat löschen', 'cp-communitie').'</h2>';
			
							if (isset($_POST['purge_chat']) && $_POST['purge_chat'] != '' && is_numeric($_POST['purge_chat']) ) {
								
								$sql = "SELECT COUNT(id) FROM ".$wpdb->prefix."cpcommunitie_chat2 WHERE sent <= ".(time() - $_POST['purge_chat'] * 24 * 60 * 60);	
								$cnt = $wpdb->get_var( $sql );
								$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_chat2 WHERE sent <= ".(time() - $_POST['purge_chat'] * 24 * 60 * 60);	
								$wpdb->query( $sql );
								
								echo "<div style='margin-top:10px; border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo "Chat gelöscht: ".$cnt;
								echo "</div>";
							}
							
							// Purge topics
							if (isset($_POST['purge_topics']) && $_POST['purge_topics'] != '' && is_numeric($_POST['purge_topics']) ) {
								
								$sql = "SELECT tid FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_started <= '".date("Y-m-d H:i:s",strtotime('-'.$_POST['purge_topics'].' days'))."'";	
								$topics = $wpdb->get_results( $sql );
								
								$cnt = 0;
								if ($topics) {
									foreach ($topics as $topic) {
										$cnt++;
										$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."cpcommunitie_subs WHERE tid = %d", $topic->tid));
										$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."cpcommunitie_topics WHERE tid = %d", $topic->tid));
									}
								}
								
								echo "<div style='margin-top:10px; border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo "Gelöschte Themen: ".$cnt;
								echo "</div>";
							}
			
							echo '<p>'.__('Forenaktivitäten und gelöschte Chats werden <strong>gelöscht</strong> - Du kannst dies nicht rückgängig machen. Vorher Backup machen!', 'cp-communitie').'</p>';
				
							echo '<form action="" method="post"><table style="margin-bottom:10px">';
							echo '<tr><td style="border:0">'.__('Chat älter als', 'cp-communitie');
								echo '</td><td style="border:0"><input type="text" size="3" name="purge_chat"> ';
								echo __('Tage', 'cp-communitie')."</td></tr>";
							echo '<tr><td style="border:0">'.__('Forenthemen älter als', 'cp-communitie');
								echo '</td><td style="border:0"><input type="text" size="3" name="purge_topics"> ';
								echo __('Tage', 'cp-communitie')."</td></tr></table>";
							echo '<input type="submit" class="button-primary delete" value="'.__('Löschen', 'cp-communitie').'">';
							echo '</form><br />';
						echo '</td></tr></table>';
		
					echo "</div>";
					echo "<div style='width:50%; float:right; padding-bottom:15px;'>";
		
						// Permalinks
						echo '<table class="widefat" style="float:right;"><tr><td style="padding:0 0 0 10px">';
							echo '<a name="perma"></a>';
							echo '<h2 style="margin-bottom:10px">'.sprintf(__('%s Permalinks', 'cp-communitie'), CPC_WL_SHORT).'</h2>';
							echo '<p style="font-weight:bold">'.__('Es wird empfohlen, diese vor der Implementierung zu testen.', 'cp-communitie').'</p>';
							
							// Act on submit
							$just_switched_on = false;
							if (isset($_POST[ 'cpcommunitie_permalinks' ])) {
								if ( $_POST[ 'cpcommunitie_permalinks_enable' ] == 'on' ) {
									// If switching on, default categories to on
									if (!get_option(CPC_OPTIONS_PREFIX.'_permalink_structure')) {
										update_option(CPC_OPTIONS_PREFIX.'_permalinks_cats', 'on');	
										$just_switched_on = true;			   	    
									} else {
										// If already on, act on categories checkbox
										if (isset($_POST[ 'cpcommunitie_permalinks_cats' ])) {
											update_option(CPC_OPTIONS_PREFIX.'_permalinks_cats', 'on');
										} else {
											update_option(CPC_OPTIONS_PREFIX.'_permalinks_cats', '');
										}
									}
									update_option(CPC_OPTIONS_PREFIX.'_permalink_structure', 'on');				   	    
									
								} else {
			
									if (get_option(CPC_OPTIONS_PREFIX.'_permalink_structure')) {
										echo '<p>'.__('Wenn Du Permalinks zum ersten Mal aktivierst, habe bitte etwas Geduld, während Deine Datenbank aktualisiert wird.', 'cp-communitie').'</p>'; 
									}
									delete_option('cpcommunitie_permalink_structure');
									delete_option('cpcommunitie_permalinks_cats');
								}
							}
			
							if ( get_option('permalink_structure') != '' ) {
			
								echo '<form method="post" action="#perma">';
			
									if ( get_option(CPC_OPTIONS_PREFIX.'_permalink_structure')  ) {
										
										// Can't work with Forum in AJAX mode
										if (get_option(CPC_OPTIONS_PREFIX.'_forum_ajax')) {
											update_option(CPC_OPTIONS_PREFIX.'_forum_ajax', '');
											echo '<p style="color:green; font-weight:bold;">'.__('Der "AJAX-Modus" des Forums wurde deaktiviert, da dieser nicht mit Permalinks kompatibel ist.', 'cp-communitie').'</p>'; 
										}
				
										// Do a check to ensure all forum categories have a slug
										$sql = "SELECT * FROM ".$wpdb->prefix."cpcommunitie_cats WHERE stub = ''";
										$cats = $wpdb->get_results($sql);
										if ($cats) {
											foreach ($cats as $cat) {
												$stub = __cpc__create_stub($cat->title);
												$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_cats SET stub = '".$stub."' WHERE cid = %d";
												$wpdb->query($wpdb->prepare($sql, $cat->cid));
												if (CPC_DEBUG) echo $wpdb->last_query.'<br>';
											}
										}
										// Do a check to ensure all forum topics have a slug
										$sql = "SELECT * FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_parent = 0 AND stub = '' ORDER BY tid DESC";
										$topics = $wpdb->get_results($sql);
										if ($topics) {
											foreach ($topics as $topic) {
												$stub = __cpc__create_stub($topic->topic_subject);
												$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_topics SET stub = '".$stub."' WHERE tid = %d";
												$wpdb->query($wpdb->prepare($sql, $topic->tid));
												if (CPC_DEBUG) echo $wpdb->last_query.'<br>';
											}
										} 
			
										// update any POSTed values or update default values if necessary
										$reset = isset($_POST['cpcommunitie_permalinks_reset']) ? true : false;
			
										if ( (!$just_switched_on) && (!$reset) && ( get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single') || get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double') || get_option(CPC_OPTIONS_PREFIX.'_rewrite_members') ) )  {
												
												if (isset($_POST['cpcommunitie_permalinks']) && $_POST['cpcommunitie_permalinks'] == 'Y') {
													update_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single', $_POST['cpcommunitie_rewrite_forum_single']);
													update_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single_target', $_POST['cpcommunitie_rewrite_forum_single_target']);
													update_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double', $_POST['cpcommunitie_rewrite_forum_double']);
													update_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double_target', $_POST['cpcommunitie_rewrite_forum_double_target']);
													update_option(CPC_OPTIONS_PREFIX.'_rewrite_members', $_POST['cpcommunitie_rewrite_members']);
													update_option(CPC_OPTIONS_PREFIX.'_rewrite_members_target', $_POST['cpcommunitie_rewrite_members_target']);
												}
												flush_rewrite_rules();
												
										} else {
											
											// check that options exist if not put in defaults
			//								if ( ($reset) || ( !get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single') && !get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double')  && !get_option(CPC_OPTIONS_PREFIX.'_rewrite_members') ) ) {
			
												// get forum path and pagename
												$sql = "SELECT ID, post_title FROM ".$wpdb->prefix."posts WHERE post_content LIKE  '%[cpcommunitie-forum]%' AND post_status =  'publish' AND post_type =  'page'";
												$page = $wpdb->get_row($sql);
												$permalink = __cpc__get_url('forum');
												$p = strtolower(trim(str_replace(get_bloginfo('url'), '', $permalink), '/'));
												$post_title = rawurlencode($page->post_title);
			
												// get profile path and pagename
												$sql = "SELECT ID, post_title FROM ".$wpdb->prefix."posts WHERE post_content LIKE  '%[cpcommunitie-profile]%' AND post_status =  'publish' AND post_type =  'page'";
												$page = $wpdb->get_row($sql);
												$permalink = __cpc__get_url('profile');
												$m = strtolower(trim(str_replace(get_bloginfo('url'), '', $permalink), '/'));
												$members_title = rawurlencode($page->post_title);
												
												update_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single', $p.'/([^/]+)/?');
												update_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single_target', 'index.php?pagename='.$post_title.'&stub=/$matches[1]');
												update_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double', $p.'/([^/]+)/([^/]+)/?');
												update_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double_target', 'index.php?pagename='.$post_title.'&stub=$matches[1]/$matches[2]');
												update_option(CPC_OPTIONS_PREFIX.'_rewrite_members', $m.'/([^/]+)/?');
												update_option(CPC_OPTIONS_PREFIX.'_rewrite_members_target', 'index.php?pagename='.$members_title.'&stub=$matches[1]');
			
												flush_rewrite_rules();
												echo '<p style="color:green; font-weight:bold;">'.__('Regeln neu schreiben, die als vorgeschlagene Standardwerte gespeichert sind.', 'cp-communitie').'</p>'; 
												
												update_option(CPC_OPTIONS_PREFIX.'_permalinks_cats', 'on');
			
			//								}
										}
			
										// Flush WP permalinks to clean up
										global $wp_rewrite;				
										$wp_rewrite->flush_rules();
			
										// Display fields allowing them to be altered												
																
										echo '<strong>'.__('Forum', 'cp-communitie').'</strong><br />';
										echo '<input type="text" name="cpcommunitie_rewrite_forum_single" style="width:150px" value="'.get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single').'" /> => ';
										echo '<input type="text" name="cpcommunitie_rewrite_forum_single_target" style="width:400px" value="'.get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_single_target').'" /><br />';
										echo '<input type="text" name="cpcommunitie_rewrite_forum_double" style="width:150px" value="'.get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double').'" /> => ';
										echo '<input type="text" name="cpcommunitie_rewrite_forum_double_target" style="width:400px" value="'.get_option(CPC_OPTIONS_PREFIX.'_rewrite_forum_double_target').'" /><br />';
										echo '<br /><strong>'.__('Mitgliedsprofil', 'cp-communitie').'</strong><br />';
										echo '<input type="text" name="cpcommunitie_rewrite_members" style="width:150px" value="'.get_option(CPC_OPTIONS_PREFIX.'_rewrite_members').'" /> => ';
										echo '<input type="text" name="cpcommunitie_rewrite_members_target" style="width:400px" value="'.get_option(CPC_OPTIONS_PREFIX.'_rewrite_members_target').'" /><br /><br />';
										
										echo '<input type="hidden" name="cpcommunitie_permalinks" value="Y">';
										echo '<input type="checkbox" name="cpcommunitie_permalinks_enable" CHECKED > '.sprintf(__('%s Permalinks aktiviert', 'cp-communitie'), CPC_WL_SHORT).'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
										echo '<input type="checkbox" name="cpcommunitie_permalinks_cats"';
											if (get_option(CPC_OPTIONS_PREFIX.'_permalinks_cats')) echo ' CHECKED';
											echo '> '.__('Füge Kategorien in Foren-Hyperlinks ein', 'cp-communitie').'<br /><br />';
										echo '<input type="checkbox" name="cpcommunitie_permalinks_reset" /> '.__('Zurücksetzen auf die vorgeschlagenen Standardwerte (wenn Du beispielsweise Seitennamen geändert hast)', 'cp-communitie');
										echo '<p style="margin: 10px 0 10px 0"><input type="submit" class="button-primary" value="'.__('Aktualisieren', 'cp-communitie').'" />';
										
									} else {
										echo '<input type="hidden" name="cpcommunitie_permalinks" value="Y">';
										echo '<input type="checkbox" name="cpcommunitie_permalinks_enable"> '.sprintf(__('Aktivieren um %s Permalinks zu aktivieren', 'cp-communitie'), CPC_WL_SHORT);
										echo '<p style="margin: 10px 0 10px 0"><input type="submit" class="button-primary" value="'.__('Aktualisieren', 'cp-communitie').'" />';
									}
			
			
								echo '</form>';
			
							} else {
								echo '<p>'.__('Du kannst keine Permalinks verwenden, wenn Deine ClassicPress <a href="options-permalink.php">Permalink-Einstellung</a> die Standardeinstellung ist.', 'cp-communitie').'</p>'; 
							}
							
						echo '</td></tr></table>';
		
						// ********** Test Email   	
						echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
						
							if( isset($_POST[ 'cpcommunitie_testemail' ]) && $_POST[ 'cpcommunitie_testemail' ] == 'Y' && $_POST['cpcommunitie_testemail_address'] != '' ) {
								$to = $_POST['cpcommunitie_testemail_address'];
								if (__cpc__sendmail($to, sprintf("%s Test Email", CPC_WL), __("Dies ist eine Test-Mail, gesendet von ", 'cp-communitie')." ".get_bloginfo('url'))) {
									echo "<div class='updated'><p>";
									$from = get_option(CPC_OPTIONS_PREFIX.'_from_email');
									echo sprintf(__('Mail gesendet an %s von', 'cp-communitie'), $to);
									echo ' '.$from;
									echo "</p></div>";
								} else {
									echo "<div class='error'><p>".__("Mail konnte nicht gesendet werden", 'cp-communitie').".</p></div>";
								}
							}
							echo '<h2 style="margin-bottom:10px">'.__('Sende eine Test-Mail', 'cp-communitie').'</h2>';
				
							echo '<p>'.__('Gib eine gültige Mail-Adresse ein, um das Senden einer Mail vom Server zu testen', 'cp-communitie').'.</p>';
							echo '<form method="post" action="">';
							echo '<input type="hidden" name="cpcommunitie_testemail" value="Y">';
							echo '<p><input type="text" name="cpcommunitie_testemail_address" value="" style="margin-right:15px;height:24px;width:300px" class="regular-text">';
							echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Mail senden', 'cp-communitie').'" /></p';
							echo '</form>';
							
						echo '</td></tr></table>';
		
						// Image uploading
						echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
							echo '<a name="image"></a>';
							echo '<h2 style="margin-bottom:10px">'.__('Hochladen von Bildern', 'cp-communitie').'</h2>';
						
							echo "<div>";
							echo "<div id='cpcommunitie_user_login' style='display:none'>".strtolower($current_user->user_login)."</div>";
							echo "<div id='cpcommunitie_user_email' style='display:none'>".strtolower($current_user->user_email)."</div>";
							if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == "on") {
								echo __("<p>Du speicherst Bilder in der Datenbank.</p>", 'cp-communitie');
							} else {
								echo __("<p>Du speicherst Bilder im Dateisystem.</p>", 'cp-communitie');			
					
								if (file_exists(get_option(CPC_OPTIONS_PREFIX.'_img_path'))) {
									echo "<p>".sprintf(__('Der Ordner %s existiert, in dem hochgeladene Bilder abgelegt werden.', 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_img_path'))."</p>";
								} else {
									echo "<p>".sprintf(__('Der Ordner %s existiert nicht, in dem hochgeladene Bilder abgelegt werden, versuche zu erstellen...', 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_img_path'))."</p>";
									if (!mkdir(get_option(CPC_OPTIONS_PREFIX.'_img_path'), 0755, true)) {
										echo '<p>Fehler beim erstellen von '.get_option(CPC_OPTIONS_PREFIX.'_img_path').'.</p>';
										$error = error_get_last();
									    echo '<p>'.$error['message'].'<br />';
									    echo sprintf(__('Zur Information, dieses Skript befindet sich in %s.', 'cp-communitie'), __FILE__);
									} else {
										echo '<p>Erstellt '.get_option(CPC_OPTIONS_PREFIX.'_img_path').'.</p>';
									}
								}
								
								if (get_option(CPC_OPTIONS_PREFIX.'_img_url') == '') {
									echo "<p>".$fail.__('Du musst die URL für Deine Bilder in den <a href="admin.php?page=cpcommunitie_settings">Einstellungen</a> aktualisieren.', 'cp-communitie').$fail2."</p>";
								} else {
									echo "<p>".__('Die URL zu Deinem Bilderordner lautet', 'cp-communitie')." <a href='".get_option(CPC_OPTIONS_PREFIX.'_img_url')."'>".get_option(CPC_OPTIONS_PREFIX.'_img_url')."</a>.</p>";
								}
			
								$tmpDir = get_option(CPC_OPTIONS_PREFIX.'_img_path').'/tmp';
								$tmpFile = '.txt';
								$tmpFile = time().'.tmp';
								$targetTmpFile = $tmpDir.'/'.$tmpFile;
								
								// Does tmp folder exist?
								if (!file_exists($tmpDir)) {
									if (@mkdir($tmpDir)) {
										echo '<p>'.sprintf(__('Der temporäre %s Bildordner (%s) existiert derzeit nicht', 'cp-communitie'), CPC_WL_SHORT, $tmpDir);
										echo __(', und wurde erstellt.', 'cp-communitie').'</p>';
									} else {
										echo '<p>'.$fail.sprintf(__('Der temporäre %s Bildordner (%s) existiert derzeit nicht', 'cp-communitie'), CPC_WL_SHORT, $tmpDir);
										echo __(', und konnte nicht erstellt werden - bitte überprüfe die Berechtigungen dieses Pfades.', 'cp-communitie').$fail2.'</p>';
									}
								} else {
									echo '<p>'.sprintf(__('Der temporäre %s Bildordner (%s) existiert.', 'cp-communitie'), CPC_WL_SHORT, $tmpDir).'</p>';
									
									// Check creating a temporary file in tmp
									if (touch($targetTmpFile)) {
										@unlink($targetTmpFile);
										echo "<p>".sprintf(__('Temporäre Datei (%s) erfolgreich erstellt und entfernt.', 'cp-communitie'), $tmpFile)."</p>";
									} else {
										echo '<p>'.$fail.sprintf(__('Eine temporäre Datei (%s) konnte nicht erstellt werden (in %s), bitte überprüfe die Berechtigungen.', 'cp-communitie'), $targetTmpFile, $tmpDir);
									}
								}
								
							}
							echo "</div>";
						echo '</td></tr></table>';

						// Link to licence
						echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
							echo '<a name="image"></a>';
							echo '<h2 style="margin-bottom:10px">'.__('Endbenutzer-Lizenzvereinbarung', 'cp-communitie').'</h2>';
						
							echo "<p>".sprintf(__("Wenn Du die Bedingungen der <a href='%s'>Lizenz</a> nicht akzeptierst, entferne bitte dieses Plugin", 'cp-communitie'), CPC_PLUGIN_URL."/licence.txt").".</p>";
							
						echo '</td></tr></table>';
								
						// ********** Daily Digest 
						echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
							echo '<h2 style="margin-bottom:10px">'.__('Tägliche Zusammenfassung', 'cp-communitie').'</h2>';
				
							if( isset($_POST[ 'cpcommunitie_dailydigest' ]) && $_POST[ 'cpcommunitie_dailydigest' ] == 'Y' ) {
								$to_users = isset($_POST['cpcommunitie_dailydigest_users']) ? $_POST['cpcommunitie_dailydigest_users'] : '';
								$to_admin = isset($_POST['cpcommunitie_dailydigest_admin']) ? $_POST['cpcommunitie_dailydigest_admin'] : '';
								if ($to_users == 'on' || $to_admin == 'on') {
									echo "<div style='border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>Läuft...<br />";
									if ($to_users == "on") {
										echo "Zusammenfassenden Bericht und an alle Benutzer senden...<br />";
										$success = __cpc__notification_do_jobs('send_admin_summary_and_to_users');
									}								
									if ($to_admin == "on") {
										echo "Zusammenfassenden Bericht und Tagesauszug werden nur an den Administrator gesendet... ";
										$success = __cpc__notification_do_jobs('cpcommunitie_dailydigest_admin');
									}			
									echo $success;
									echo "Vollständig.<br />";
									if ($success == 'OK' && $to_admin == 'on') {
										echo "Summary report sent to ".get_bloginfo('admin_email').".<br />";
									}
									echo "</div>";
								}
							}
							echo '<p>'.__('Die Tägliche Zusammenfassung führt auch einige grundlegende Datenbankbereinigungsvorgänge durch, die jederzeit ausgeführt werden können', 'cp-communitie').'.</p>';
							echo '<form method="post" action="">';
							echo '<input type="hidden" name="cpcommunitie_dailydigest" value="Y">';
							echo '<input type="checkbox" name="cpcommunitie_dailydigest_admin" > '.__('Sende Tägliche Zusammenfassung und Zusammenfassung an den Administrator', 'cp-communitie').' ('.get_bloginfo('admin_email').')<br />';
							echo '<input type="checkbox" name="cpcommunitie_dailydigest_users" > '.__('Sende jetzt Tägliche Zusammenfassung an Benutzer (einschließlich Zusammenfassung an den Administrator)', 'cp-communitie');
							echo '<p style="margin-top:10px"><input type="submit" name="Submit" class="button-primary" value="'.__('Tägliche Zusammenfassung senden', 'cp-communitie').'" /></p>';
							echo '</form>';
						echo '</td></tr></table>';
		
					echo "</div>";
					
					echo "<div style='clear:both;'></div>";
		
					// ********** Stylesheets	
					echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
					
						echo '<h2 style="margin-bottom:10px">'.__('Stylesheets', 'cp-communitie').'</h2>';
				
						// CSS check
						$myStyleFile = CPC_PLUGIN_DIR . '/css/'.get_option(CPC_OPTIONS_PREFIX.'_cpc_css_file');
						if ( !file_exists($myStyleFile) ) {
							echo $fail . sprintf(__('Stylesheet (%s) nicht gefunden.', 'cp-communitie'), $myStyleFile) . $fail2;
						} else {
							echo "<p style='color:green; font-weight:bold;'>" . sprintf(__('Stylesheet (%s) gefunden.', 'cp-communitie'), $myStyleFile) . "</p>";
						}
							
						// ********** Javascript			
						echo '<h2 style="margin-bottom:10px">'.__('Javascript', 'cp-communitie').'</h2>';
				
						// JS check
						$myJSfile = CPC_PLUGIN_DIR . '/js/'.get_option(CPC_OPTIONS_PREFIX.'_cpc_js_file');
						if ( !file_exists($myJSfile) ) {
							echo $fail . sprintf(__('Javascript-Datei (%s) nicht gefunden.', 'cp-communitie'), $myJSfile) . $fail2;
						} else {
							echo "<p style='color:green; font-weight:bold;'>" . sprintf(__("Javascript-Datei (%s) gefunden.", 'cp-communitie'), $myJSfile) . "</p>";
						}
						echo "<p>" . sprintf(__("Wenn Du feststellst, dass bestimmte %s-Dinge nicht funktionieren, wie z.B. Schaltflächen oder das Hochladen von Profilfotos, liegt dies wahrscheinlich daran, dass die %s-JavaScript-Datei nicht geladen wird und/oder nicht funktioniert. Normalerweise liegt dies an einem anderen ClassicPress-Plugin. Versuche alle Nicht-%s-Plug-ins zu deaktivieren und zum Design TwentyEleven zu wechseln. Wenn %s dann funktioniert, aktiviere die Plug-Ins nacheinander erneut, bis der Fehler erneut auftritt. Dies hilft Dir, das kollidierende Plug-In zu finden. Wechsel dann Dein Theme zurück. Versuche auch, die Entwicklertools Deines Browsers zu verwenden – dies zeigt Dir, wo der Javascript-Fehler auftritt.", 'cp-communitie'), CPC_WL, CPC_WL, CPC_WL_SHORT, CPC_WL_SHORT)."</p>";
						echo "<p>".__("Wenn Du Probleme hast, <a href='https://cp-community.n3rds.work//trythisfirst' target='_blank'>versuche es zuerst hier</a>.", 'cp-communitie')."</p>";
								
						echo "<div id='jstest'>".$fail.sprintf(__( "Du hast Probleme mit Javascript. Dies kann daran liegen, dass ein Plug-in eine andere Version von jQuery oder der jQuery-Benutzeroberfläche lädt. Versuche alle Plug-ins außer %s-Plug-ins zu deaktivieren, und aktiviere sie nacheinander erneut, bis der Fehler erneut auftritt. Dies hilft Dir, das Plug-in zu finden kollidiert. Es kann auch daran liegen, dass eine JS-Datei, entweder cpcommunitie.js oder ein anderes Plugin-Skript, einen Fehler enthält.", 'cp-communitie'), CPC_WL_SHORT).$fail2."</div>";
					echo '</td></tr></table>';
		
			
					// ********** bbPress migration
					echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
						echo '<a name="bbpress"></a>';
						echo '<h2 style="margin-bottom:10px">'.__('bbPress Migration', 'cp-communitie').'</h2>';
				
						// migrate any chosen bbPress forums
						if( isset($_POST[ 'cpcommunitie_bbpress' ]) && $_POST[ 'cpcommunitie_bbpress' ] == 'Y' ) {
							$id = $_POST['bbPress_forum'];
							$cat_title = $_POST['bbPress_category'];
							
							$success = true;
							$success_message = "";
							
							if ($cat_title != '') {
								
								$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'forum' AND ID = %d";
								$forum = $wpdb->get_row($wpdb->prepare($sql, $id));
								$success_message .= "Creating &quot;".$cat_title."&quot; from &quot;".$forum->post_title."&quot;. ";

								$stub = trim(preg_replace("/[^A-Za-z0-9 ]/",'',$cat_title));
								$stub = strtolower(str_replace(' ', '-', $stub));
								$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."cpcommunitie_cats WHERE stub = '".$stub."'";
								$cnt = $wpdb->get_var($sql);
								if ($cnt > 0) $stub .= "-".$cnt;
								$stub = str_replace('--', '-', $stub);

								// Add new forum category
								if ( $wpdb->query( $wpdb->prepare( "
									INSERT INTO ".$wpdb->prefix.'cpcommunitie_cats'."
									( 	title, 
										cat_parent,
										listorder,
										cat_desc,
										allow_new,
										hide_breadcrumbs,
										hide_main,
										stub
									)
									VALUES ( %s, %d, %d, %s, %s, %s, %s, %s )", 
									array(
										$cat_title, 
										0,
										0,
										$forum->post_content,
										'on',
										'',
										'',
										$stub
										) 
									) )
								) {
									
									$success_message .= __("Forum erstellt OK mit Stub ".$stub.".", 'cp-communitie')."<br />";
									
									$new_forum_id = $wpdb->insert_id;
			
									$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'topic' AND post_parent = %d";
									$topics = $wpdb->get_results($wpdb->prepare($sql, $id));
									$success_message .= "Migrieren von Themen zu &quot;".$cat_title."&quot;.<br />";
									
									if ($topics) {
										
										$failed = 0;
										foreach ($topics AS $topic) {
											
											$stub = __cpc__create_stub($topic->post_title);

											if ( $wpdb->query( $wpdb->prepare( "
												INSERT INTO ".$wpdb->prefix."cpcommunitie_topics 
												( 	topic_subject,
													topic_category, 
													topic_post, 
													topic_date, 
													topic_started, 
													topic_owner, 
													topic_parent, 
													topic_views,
													topic_approved,
													for_info,
													topic_group,
													stub
												)
												VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %s, %d, %s )", 
												array(
													$topic->post_title, 
													$new_forum_id,
													$topic->post_content, 
													$topic->post_modified,
													$topic->post_date, 
													$topic->post_author, 
													0,
													0,
													'on',
													'',
													0, 
													$stub
													) 
												) ) ) {
			
													$success_message .= "Migriert &quot;".$topic->post_title."&quot; OK.<br />";	
													
													$new_topic_id = $wpdb->insert_id;
							
													$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'reply' AND post_parent = %d";
													$replies = $wpdb->get_results($wpdb->prepare($sql, $topic->ID));
													
													if ($replies) {
														$success_message .= "Migrating replies to &quot;".$topic->post_title."&quot; OK. ";	
													
														$failed_replies = 0;
														foreach ($replies AS $reply) {
			
															if ( $wpdb->query( $wpdb->prepare( "
															INSERT INTO ".$wpdb->prefix."cpcommunitie_topics
															( 	topic_subject, 
																topic_category,
																topic_post, 
																topic_date, 
																topic_started, 
																topic_owner, 
																topic_parent, 
																topic_views,
																topic_approved,
																topic_group,
																topic_answer
															)
															VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %d, %s )", 
															array(
																'', 
																$new_forum_id,
																$reply->post_content, 
																$reply->post_modified,
																$reply->post_date, 
																$reply->post_author, 
																$new_topic_id,
																0,
																'on',
																0,
																''
																) 
															) ) ) {
															} else {
																$failed_replies++;
															}
															
														}
			
														if ($failed_replies == 0) {
								
															$success_message .= __("Antworten migriert OK.", 'cp-communitie')."<br />";
															
														} else {
															$success_message .= sprintf(__("%d Antworten konnten nicht migriert werden.", 'cp-communitie'), $failed_replies)."<br />";
															$success = false;
														}
			
													} else {
														$success_message .= __("Keine Antworten zum Migrieren.", 'cp-communitie')."<br />";
													}
											
											} else {
												$failed++;
											}
											   
										}
										
										if ($failed == 0) {
				
											$success_message .= __("Themen und Antworten wurden OK migriert.", 'cp-communitie')."<br />";
											
										} else {
											$success_message .= sprintf(__("%d Themen konnten nicht migriert werden.", 'cp-communitie'), $failed)."<br />";
											$success = false;
										}
									} else {
											$success_message .= __("Keine Themen zum Migrieren.", 'cp-communitie')."<br />";
									}
									
								} else {
									$success_message .= __("Forum konnte nicht migriert werden", 'cp-communitie')."<br />";
									$success_message .= $wpdb->last_query."<br />";
									$success = false;
								}
									
									
							} else {
								$success_message .= __('Bitte gib einen neuen Titel für die Forenkategorie ein', 'cp-communitie');
							}
							
							if ($success) {
								echo "<div style='margin-top:10px;border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo $success_message;
								echo "Vollständig.<br />";			
								echo "</div>";
							} else {
								echo "<div style='margin-top:10px;border:1px solid #600;background-color: #f99; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo $success_message;
								echo "</div>";
							}
							
						}
				
						// check to see if any bbPress forums exist
						$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'forum'";
						$forums = $wpdb->get_results($sql);
						if ($forums) {
							echo '<p>'.sprintf(__('Wenn Du bbPress v2-Plugin-Foren hast, kannst Du diese als neue Kategorie in Dein %s-Forum migrieren.', 'cp-communitie'), CPC_WL).'</p>';
							echo '<p>'.__('Diese Migration funktioniert mit dem <a href="" target="_blank">ClassicPress bbPress-Plugin v2</a>. Wenn Du eine frühere oder eigenständige Version von bbPress ausführst, solltest Du zuerst Deine Installation aktualisieren.', 'cp-communitie').'</p>';
							echo '<p>'.__('Du solltest vor der Migration ein Backup Deiner Datenbank erstellen, nur für den Fall, dass es ein Problem gibt.', 'cp-communitie').'</p>';
							echo '<form method="post" action="#bbpress">';
							echo '<input type="hidden" name="cpcommunitie_bbpress" value="Y">';
							echo __('Zu migrierendes Forum auswählen:', 'cp-communitie').' ';
							echo '<select name="bbPress_forum">';
							foreach ($forums AS $forum) {
								echo '<option value="'.$forum->ID.'">'.$forum->post_title.'</option>';
							}
							echo '</select><br />';
							echo __('Gib den Titel der neuen Forenkategorie ein:', 'cp-communitie').' ';
							echo '<input type="text" name="bbPress_category" />';
							echo '<p><em>' . __("Obwohl Dein bbPress-Forum nicht verändert wird und nur neue Kategorien/Themen/Antworten hinzugefügt werden, wird empfohlen, dass Du zuerst Deine Datenbank sicherst.", 'cp-communitie') . '</em></p>';
							echo '<p class="submit"><input type="submit" name="Submit" class="button-primary" value="'.__('bbPress migrieren', 'cp-communitie').'" /></p>';
							echo '</form>';
						} else {
							echo '<p>'.__('Keine bbPress-Foren gefunden', 'cp-communitie').'.</p>';
						}
					echo '</td></tr></table>';
		
		
					// ********** Mingle migration
					echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';			
						echo '<a name="mingle"></a>';
						echo '<h2 style="margin-bottom:10px">'.__('Mingle Migration', 'cp-communitie').'</h2>';
			
						// migrate any chosen mingle forums
						if( isset($_POST[ 'cpcommunitie_mingle' ]) && $_POST[ 'cpcommunitie_mingle' ] == 'Y' ) {
							$id = $_POST['mingle_forum'];
							$cat_title = $_POST['mingle_category'];
							
							$success = true;
							$success_message = "";
							
							if ($cat_title != '') {
								
								$sql = "SELECT * FROM ".$wpdb->prefix."forum_forums WHERE id = %d";
								$forum = $wpdb->get_row($wpdb->prepare($sql, $id));
								$success_message .= "Creating &quot;".$cat_title."&quot; from &quot;".$forum->name."&quot;. ";
			
								// Add new forum category
								if ( $wpdb->query( $wpdb->prepare( "
									INSERT INTO ".$wpdb->prefix.'cpcommunitie_cats'."
									( 	title, 
										cat_parent,
										listorder,
										cat_desc,
										allow_new
									)
									VALUES ( %s, %d, %d, %s, %s )", 
									array(
										$cat_title, 
										0,
										0,
										$forum->description,
										'on'
										) 
									) )
								) {
									
									$success_message .= __("Forum erstellt OK.", 'cp-communitie')."<br />";
									
									$new_forum_id = $wpdb->insert_id;
									
									// Get Mingle threads	
									$sql = "SELECT * FROM ".$wpdb->prefix."forum_threads WHERE parent_id = %d";
									$topics = $wpdb->get_results($wpdb->prepare($sql, $id));
									$success_message .= "Migrieren von Themen zu &quot;".$cat_title."&quot;.<br />";
									
									if ($topics) {
										
										$failed = 0;								
										foreach ($topics AS $topic) {
											
											if ( $wpdb->query( $wpdb->prepare( "
												INSERT INTO ".$wpdb->prefix."cpcommunitie_topics 
												( 	topic_subject,
													topic_category, 
													topic_post, 
													topic_date, 
													topic_started, 
													topic_owner, 
													topic_parent, 
													topic_views,
													topic_approved,
													for_info,
													topic_group
												)
												VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %s, %d )", 
												array(
													$topic->subject, 
													$new_forum_id,
													'nopost', 
													$topic->last_post,
													$topic->date, 
													$topic->starter, 
													0,
													0,
													'on',
													'',
													0
													) 
												) ) ) {
													
													// Set up topic, now add all the replies	
													$success_message .= "Migriert &quot;".$topic->subject."&quot; OK.<br />";	
													
													$new_topic_id = $wpdb->insert_id;
							
													$sql = "SELECT * FROM ".$wpdb->prefix."forum_posts WHERE parent_id = %d";
													$replies = $wpdb->get_results($wpdb->prepare($sql, $topic->id));
													
													if ($replies) {
														$success_message .= "Migrieren von Antworten zu &quot;".$topic->subject."&quot;.<br />";	
													
														$failed_replies = 0;
														$done_first_reply = false;
														foreach ($replies AS $reply) {
															
															if ($done_first_reply) {
			
																if ( $wpdb->query( $wpdb->prepare( "
																INSERT INTO ".$wpdb->prefix."cpcommunitie_topics
																( 	topic_subject, 
																	topic_category,
																	topic_post, 
																	topic_date, 
																	topic_started, 
																	topic_owner, 
																	topic_parent, 
																	topic_views,
																	topic_approved,
																	topic_group,
																	topic_answer
																)
																VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %d, %s )", 
																array(
																	'', 
																	$new_forum_id,
																	$reply->text, 
																	$reply->date,
																	$reply->date, 
																	$reply->author_id, 
																	$new_topic_id,
																	0,
																	'on',
																	0,
																	''
																	) 
																) ) ) {
																} else {
																	$failed_replies++;
																}
																
															} else {
																$done_first_reply = true;
																if ( $wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix."cpcommunitie_topics SET topic_post = '".$reply->text."' WHERE tid = %d", $new_topic_id) ) ) {
																	$success_message .= "Aktualisiertes Thema mit dem ersten Beitrag OK.<br />";
																} else {
																	$failed_replies++;
																}	
																
															}
															
														}
			
														if ($failed_replies == 0) {
								
															$success_message .= __("Antworten migriert OK.", 'cp-communitie')."<br />";
															
														} else {
															$success_message .= sprintf(__("%d Antworten konnten nicht migriert werden.", 'cp-communitie'), $failed_replies)."<br />";
															$success = false;
														}
			
													} else {
														$success_message .= __("Keine Antworten zum Migrieren.", 'cp-communitie')."<br />";
													}
																					
											} else {
												$failed++;
											}
											   
										}
										
										if ($failed == 0) {
				
											$success_message .= __("Themen und Antworten wurden OK migriert.", 'cp-communitie')."<br />";
											
										} else {
											$success_message .= sprintf(__("%d Themen konnten nicht migriert werden.", 'cp-communitie'), $failed)."<br />";
											$success = false;
										}
									} else {
											$success_message .= __("Keine Themen zum Migrieren.", 'cp-communitie')."<br />";
									}
									
								} else {
									$success_message .= __("Forum konnte nicht migriert werden", 'cp-communitie')."<br />";
									$success_message .= $wpdb->last_query."<br />";
									$success = false;
								}
									
									
							} else {
								$success_message .= __('Bitte gib einen neuen Titel für die Forenkategorie ein', 'cp-communitie');
							}
							
							if ($success) {
								echo "<div style='margin-top:10px;border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
									echo 'Bitte suche nun im Forum nach Deiner neu migrierten Kategorie. Bei Bedarf kannst Du die Position der Kategorie in den <a href="admin.php?page=cpcommunitie_categories">Forenkategorien</a> verschieben (oder löschen)..<br />';
									echo 'Migration abgeschlossen. ';
									echo '<a href="javascript:void(0)" class="cpcommunitie_expand">Zeige Bericht</a>';
									echo '<div class="expand_this" style="display:none">';
										echo $success_message;
									echo "</div>";
								echo "</div>";
							} else {
								echo "<div style='margin-top:10px;border:1px solid #600;background-color: #f99; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo $success_message;
								echo "</div>";
							}
							
						}
				
						// check to see if any Mingle forums exist
						if($wpdb->get_var("show tables like '%".$wpdb->prefix."forum_forums%'") == $wpdb->prefix."forum_forums") {
							$sql = "SELECT * FROM ".$wpdb->prefix."forum_forums";
							$forums = $wpdb->get_results($sql);
							if ($forums) {
								echo '<p>'.sprintf(__('Wenn Du das Plugin Mingle v1.0.33 (oder höher) hast, kannst Du die Foren als neue Kategorie in Dein %s-Forum migrieren.', 'cp-communitie'), CPC_WL).'</p>';
								echo '<p>'.__('Diese Migration funktioniert mit dem <a href="" target="_blank">ClassicPress Mingle-Plugin</a>. Wenn Du eine frühere Version von Mingle ausführst, solltest Du zuerst Deine Installation aktualisieren.', 'cp-communitie').'</p>';
								echo '<p>'.__('Du solltest vor der Migration ein Backup Deiner Datenbank erstellen, nur für den Fall, dass es ein Problem gibt.', 'cp-communitie').'</p>';
								echo '<form method="post" action="#mingle">';
								echo '<input type="hidden" name="cpcommunitie_mingle" value="Y">';
								echo __('Zu migrierendes Forum auswählen:', 'cp-communitie').' ';
								echo '<select name="mingle_forum">';
								foreach ($forums AS $forum) {
									echo '<option value="'.$forum->id.'">'.$forum->name.' ('.$forum->description.')</option>';
								}
								echo '</select><br />';
								echo __('Gib den Titel der neuen Forenkategorie ein:', 'cp-communitie').' ';
								echo '<input type="text" name="mingle_category" />';
								echo '<p><em>' . __("Obwohl Dein Mingle-Forum nicht verändert wird und nur neue Kategorien/Themen/Antworten hinzugefügt werden, wird empfohlen, dass Du zuerst Deine Datenbank sicherst.", 'cp-communitie') . '</em></p>';
								echo '<p class="submit"><input type="submit" name="Submit" class="button-primary" value="'.__('Mingle migrieren', 'cp-communitie').'" /></p>';
								echo '</form>';
							} else {
								echo '<p>'.__('Keine Mingle-Foren gefunden', 'cp-communitie').'.</p>';
							}
						} else {
								echo '<p>'.__('Mingle-Forum nicht installiert', 'cp-communitie').'.</p>';
						}
					echo '</td></tr></table>';
				
				echo '</div>'; 	
			
			}

	  	echo '</div>'; 	

	} // end admin check	
		
}
	  
function __cpc__rrmdir($dir) {
   if (is_dir($dir)) {
	 $objects = scandir($dir);
	 foreach ($objects as $object) {
	   if ($object != "." && $object != "..") {
		 if (filetype($dir."/".$object) == "dir") __cpc__rrmdir($dir."/".$object); else unlink($dir."/".$object);
	   }
	 }
	 reset($objects);
	 rmdir($dir);
   }
}  

function __cpc__install_row($handle, $name, $shortcode, $function, $config_url, $plugin_dir, $settings_url, $install_help) {

	if (substr($install_help, 0, 7) == '__cpc__') {
		
		global $wpdb;
		$install_help = str_replace('\\', '/', $install_help);
		$name = str_replace('_', ' ', $name);
		
		$status = '';
		
		echo '<tr>';

				$style = (is_super_admin() && __cpc__is_wpmu()) ? '' : 'display:none;';
				echo '<td style="'.$style.'text-align:center">';
				
					if ($function != '__cpc__group' && $install_help != '__cpc_activated') {
						$network_activated = get_option(CPC_OPTIONS_PREFIX.$function.'_network_activated') ? 'CHECKED' : '';
						echo '<input type="checkbox" name="'.$function.'_network_activated" '.$network_activated.' />';
					} else {
						echo __('-auto-', 'cp-communitie');
					}
				
				echo '</td>';
	
			echo '<td style="text-align:center">';
			
				if ($function != '__cpc__group') {
					$activated = get_option(CPC_OPTIONS_PREFIX.$function.'_activated') ? 'CHECKED' : '';
					$style = !get_option(CPC_OPTIONS_PREFIX.$function.'_network_activated') ? $style = '' : $style = 'style="display:none"';
					if ($install_help != '__cpc__activated') {
						echo '<input type="checkbox" '.$style.' name="'.$function.'_activated" '.$activated.' />';
						if ($network_activated) 
							echo '<img src="'.CPC_PLUGIN_URL.'/images/tick.png" />';
					} else {
						echo '<img src="'.CPC_PLUGIN_URL.'/images/tick.png" />';
					}
				} else {
					echo __('-auto-', 'cp-communitie');
				}
			
			echo '</td>';
						
			// Name of Plugin
			echo '<td style="height:30px">';
				echo $name;
				if (!file_exists(WP_PLUGIN_DIR.'/'.$plugin_dir))
					echo '<br><span style="color:red;font-weight:bold">'.WP_PLUGIN_DIR.'/'.$plugin_dir.' missing!</span>';
				if (isset($network_activated) && ($network_activated || $activated) ) {
					if (strpos($install_help, '__cpc__') == 0) $install_help = substr($install_help, 7, strlen($install_help)-7);
					$install_help = str_replace("bronze__", "", $install_help);
					if ($install_help != '') $install_help = ' ['.$install_help.']';
				} else {
					$install_help = '';
				}
			echo '</td>';
					
			// Shortcode on a page?
			$sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE lower(post_content) LIKE '%[".$shortcode."]%' AND post_type = 'page' AND post_status = 'publish';";
			$pages = $wpdb->get_results($sql);	
			if ( ($pages) && ($shortcode != '') ) {
				$page = $pages[0];
				$url = str_replace(get_bloginfo('url'), '', get_permalink($page->ID));
				echo '<td>';
					echo '<a href="'.get_permalink($page->ID).'" target="_blank">'.$url.'</a> ';
					echo '[<a href="post.php?post='.$page->ID.'&action=edit">'.__('Bearbeiten', 'cp-communitie').'</a>] ';
					if (isset($status) && $status == 'tick') {
						if ($settings_url != '') {
							echo '[<a href="'.$settings_url.'">'.__('Konfigurieren', 'cp-communitie').'</a>]';
						}
					}
				if ( (isset($status)) && ($url != $config_url && $status != 'cross') ) $status = 'error';
				if ($config_url == '-') $status = 'tick';
				echo $install_help.'</td>';
			} else {
				$url = '';
				echo '<td>';
				if ( (isset($status)) && ($status != 'cross') && ($status != 'notinstalled') && ($shortcode != '') ) {
					$status = 'add';
					echo '<div style="padding-top:4px;float:left; width:175px">'.sprintf(__('Hinzufügen von [%s] zu:', 'cp-communitie'), $shortcode).'</div>';
					echo '<input type="submit" class="button cpcommunitie_addnewpage" id="'.$name.'" title="'.$shortcode.'" value="'.__('Neue Seite', 'cp-communitie').'" />';
					$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title";
					$pages = $wpdb->get_results($sql);
					if ($pages) {
						echo ' '.__('oder', 'cp-communitie').' ';
						echo '<select id="cpcommunitie_pagechoice_'.$shortcode.'" style="width:120px">';
						foreach ($pages as $page) {
							echo '<option value="'.$page->ID.'">'.$page->post_title;
						}
						echo '</select> ';
						echo '<input type="submit" class="button cpcommunitie_addtopage" id="'.$name.'" title="'.$shortcode.'" value="'.__('Hinzufügen', 'cp-communitie').'" />';
					}
				} else {
					if (isset($status) && $status == 'tick') {
						if ($settings_url != '') {
							echo '[<a href="'.$settings_url.'">'.__('Konfigurieren', 'cp-communitie').'</a>]';
						}
					}
					if ($function == '__cpc__wysiwyg') {
						if (current_user_can('update_core'))
							echo __('Aktiviert auch die optionale Forum BB Code Toolbar', 'cp-communitie');
					}
					if ($function == '__cpc__add_notification_bar') {
						if (current_user_can('update_core'))
							echo ' [<a href="https://cp-community.n3rds.work//chat/" target="_blank">'.__('Lies dies!', 'cp-communitie').'</a>]';
					}
					if (isset($status) && $status == '') $status = 'tick';
				}
				echo '</td>';
			}
			
		
			
			// Status
			echo '<td style="text-align:center">';
	
				// Fix URL
				$fixed_url = false;
				$current_value = get_option(CPC_OPTIONS_PREFIX.'_'.strtolower($handle).'_url');
					if ($current_value != $url) {
						update_option(CPC_OPTIONS_PREFIX.'_'.strtolower($handle).'_url', $url);
						$fixed_url = true;
						if ($url != '') {
							echo '[<a href="javascript:void(0)" class="cpcommunitie_help" title="'.sprintf(__("URL erfolgreich aktualisiert. Es ist wichtig, diese Seite zu besuchen, um die Installation abzuschließen; nachdem Du einer Seite einen %s-Shortcode hinzugefügt hast; Seiten mit %s Shortcodes wechselst; wenn Du ClassicPress Permalinks änderst; oder wenn Du Probleme hast.", 'cp-communitie'), CPC_WL, CPC_WL).'">'.__('Aktualisiert okay!', 'cp-communitie').'</a>]';
						} else {
							echo '[<a href="javascript:void(0)" class="cpcommunitie_help" title="'.sprintf(__("URL entfernt. Es ist wichtig, diese Seite zu besuchen, um die Installation abzuschließen; nachdem Du einer Seite einen %s-Shortcode hinzugefügt hast; Seiten mit %s Shortcodes wechselst; wenn Du ClassicPress Permalinks änderst; oder wenn Du Probleme hast.", 'cp-communitie'), CPC_WL, CPC_WL).'">'.__('URL entfernt', 'cp-communitie').'</a>]';
						}
					} else {
						if ($current_value) {
							$status = 'tick';
						}
					}
				
				if (!$fixed_url) {
						
					if (isset($status) && $status == 'notinstalled') {
						if ($function != '__cpc__gallery') {
							echo '[<a href="javascript:void(0)" class="cpcommunitie_help" title="'.$install_help.'">'.__('Installieren', 'cp-communitie').'</a>]';
						} else {
							echo __('Demnächst', 'cp-communitie');
						}
					}
					if (isset($status) && $status == 'tick') {
						echo '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/smilies/good.png" />';
					}
					if (isset($status) && $status == 'upgrade') {
						echo '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/warning.png" />';
					}
					if (isset($status) && $status == 'cross') {			
						echo '[<a href="plugins.php?plugin_status=inactive">'.__('Aktivieren', 'cp-communitie').'</a>]';
					}
		
					if (isset($status) && $status == 'add') {
						echo '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/'.$status.'.png" />';
					}
					
				}
				
			echo '</td>';
	
			// Setting in database
			if (current_user_can('update_core')) {
				echo '<td class="cpcommunitie_url" style="background-color:#efefef">';
				
					$value = get_option(CPC_OPTIONS_PREFIX.'_'.strtolower($handle).'_url');
					if (!$value && $status != 'add') { 
						echo 'n/a';
					} else {
						if ($value != 'Wichtig: Bitte besuche die Installationsseite!') {
							echo $value;
						}	
					}
				echo '</td>';
			}
			
		echo '</tr>';
		
	}

}

function __cpc__field_exists($tablename, $fieldname) {
	global $wpdb;
	$fields = $wpdb->get_results("SHOW fields FROM ".$tablename." LIKE '".$fieldname."'");

	if ($fields) {
		return true;
	} else {
		echo __('Fehlendes Feld', 'cp-communitie').": ".$fieldname."<br />";
		return false;
	}

	return true;
}

function __cpc__plugin_bar() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';

	__cpc__show_tabs_header('panel');

	global $wpdb;

		// See if the user has posted notification bar settings
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__plugin_bar' ) {

			update_option(CPC_OPTIONS_PREFIX.'_use_chat', isset($_POST[ 'use_chat' ]) ? $_POST[ 'use_chat' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_bar_polling', $_POST[ 'bar_polling' ]);
			update_option(CPC_OPTIONS_PREFIX.'_chat_polling', $_POST[ 'chat_polling' ]);
			update_option(CPC_OPTIONS_PREFIX.'_cpc_panel_all', isset($_POST[ 'cpc_panel_all' ]) ? $_POST[ 'cpc_panel_all' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'cpc_panel_offline', isset($_POST[ 'cpc_panel_offline' ]) ? $_POST[ 'cpc_panel_offline' ] : '');
			
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Gespeichert', 'cp-communitie').".</p></div>";
			
		}


			if (!function_exists('__cpc__profile')) { 		
				echo "<div class='error'><p>".__('Das Profil-Plugin muss aktiviert sein, damit Chatfenster funktionieren. Der Chatroom funktioniert ohne das Profil-Plugin.', 'cp-communitie')."</p></div>";
			} 
			?>
			
			<form method="post" action=""> 
			<input type="hidden" name="cpcommunitie_update" value="__cpc__plugin_bar">
		
			<table class="form-table __cpc__admin_table">

			<tr><td colspan="2"><h2><?php _e('Einstellungen', 'cp-communitie') ?></h2></td></tr>
			
			<tr valign="top"> 
			<td scope="row"><label for="cpc_panel_all"><?php echo __('Alle Mitglieder anzeigen', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="cpc_panel_all" id="cpc_panel_all" 
				<?php 
				if (get_option(CPC_OPTIONS_PREFIX.'_cpc_panel_all') == "on") { echo "CHECKED"; } 
				?> 
			/>
			<span class="description"><?php echo __('Aktivieren, um alle Mitglieder einzuschließen - Deaktivieren, um nur Freunde einzuschließen', 'cp-communitie'); ?></span></td> 
			</tr> 
		
			<tr valign="top"> 
			<td scope="row"><label for="cpc_panel_offline"><?php echo __('Offline-Mitglieder anzeigen', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="cpc_panel_offline" id="cpc_panel_offline" 
				<?php 
				if (get_option(CPC_OPTIONS_PREFIX.'cpc_panel_offline') == "on") { echo "CHECKED"; } 
				?> 
			/>
			<span class="description"><?php echo __('Aktivieren, um Mitglieder anzuzeigen, die offline sind', 'cp-communitie'); ?></span></td> 
			</tr> 
		
			<tr valign="top"> 
			<td scope="row"><label for="use_chat"><?php echo __('Chatfenster aktivieren', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="use_chat" id="use_chat" 
				<?php 
				if (!function_exists('__cpc__profile')) { echo 'disabled="disabled" '; }
				if (get_option(CPC_OPTIONS_PREFIX.'_use_chat') == "on") { echo "CHECKED"; } 
				?>
			/>
			<span class="description"><?php echo __('Chatfenster in Echtzeit', 'cp-communitie'); ?></span></td> 
			</tr> 
										
			<tr valign="top"> 
			<td scope="row"><label for="bar_polling"><?php echo __('Abfrageintervalle', 'cp-communitie'); ?></label></td> 
			<td><input name="bar_polling" type="text" id="bar_polling"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_bar_polling'); ?>" /> 
			<span class="description"><?php echo __('Häufigkeit der Überprüfung auf neue Mails, Online-Freunde usw. in Sekunden. Empfohlen 120.', 'cp-communitie'); ?></td> 
			</tr> 
						
			<tr valign="top"> 
			<td scope="row"><label for="chat_polling">&nbsp;</label></td> 
			<td><input name="chat_polling" type="text" id="chat_polling"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_chat_polling'); ?>" /> 
			<span class="description"><?php echo __('Häufigkeit der Aktualisierungen des Chatfensters in Sekunden. Empfohlen 10.', 'cp-communitie'); ?></td> 
			</tr> 

			</table> 
			 
			
			<p class="submit" style="margin-left:6px"> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
			</p> 
			</form> 

			<p style="margin-left:6px">
			<strong><?php echo __('Anmerkungen:', 'cp-communitie'); ?></strong>
			<ol>
			<li><?php echo __('Die Polling-Intervalle erfolgen zusätzlich zu einer initialen Überprüfung bei jedem Seitenaufbau.', 'cp-communitie'); ?></li>
			<li><?php echo __('Je häufiger die Polling-Intervalle sind, desto größer ist die Belastung Deines Servers.', 'cp-communitie'); ?></li>
			<li><?php echo __('Das Deaktivieren von Chatfenstern reduziert die Belastung des Servers.', 'cp-communitie'); ?></li>
			</ol>
			</p>
			
			<?php

		__cpc__show_tabs_header_end();
				
	echo '</div>';
}

function __cpc__plugin_profile() {

	echo '<div class="wrap">';

	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';

	__cpc__show_tabs_header('profile');

	global $wpdb;
	global $user_ID;
	wp_get_current_user();
	
	include_once( ABSPATH . 'wp-includes/formatting.php' );
	
		// Delete an extended field?
   		if ( isset($_GET['del_eid']) && $_GET['del_eid'] != '') {

			// get slug
			$sql = "SELECT extended_slug from ".$wpdb->base_prefix."cpcommunitie_extended WHERE eid = %d";
			$slug = $wpdb->query($wpdb->prepare($sql, $_GET['del_eid']));

			// now delete extended field
			$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix.'cpcommunitie_extended'." WHERE eid = %d", $_GET['del_eid']  ) );
				
			// finally delete all of these extended fields
			$sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE meta_key = 'cpcommunitie_".$slug."'";
			$wpdb->query($sql);

		}	
		

		// See if the user has posted profile settings
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__plugin_profile' ) {

			update_option(CPC_OPTIONS_PREFIX.'_online', $_POST['online'] != '' ? $_POST['online'] : 5);
			update_option(CPC_OPTIONS_PREFIX.'_offline', $_POST['offline'] != '' ? $_POST['offline'] : 15);
			update_option(CPC_OPTIONS_PREFIX.'_use_poke', isset($_POST['use_poke']) ? $_POST['use_poke'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_poke_label', $_POST['poke_label'] != '' ? $_POST['poke_label'] : __('Hey!', "cp-communitie"));
			update_option(CPC_OPTIONS_PREFIX.'_status_label', $_POST['status_label'] != '' ? str_replace("'", "`", $_POST['status_label']) : __('Was ist los?', "cp-communitie"));
			update_option(CPC_OPTIONS_PREFIX.'_enable_password', isset($_POST['enable_password']) ? $_POST['enable_password'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_show_wall_extras', isset($_POST['show_wall_extras']) ? $_POST['show_wall_extras'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_profile_google_map', $_POST['profile_google_map'] != '' ? $_POST['profile_google_map'] : 250);
			update_option(CPC_OPTIONS_PREFIX.'_profile_comments', isset($_POST['profile_comments']) ? $_POST['profile_comments'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_show_dob', isset($_POST['show_dob']) ? $_POST['show_dob'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_show_dob_format', ($_POST['show_dob_format'] != '') ? $_POST['show_dob_format'] : __('Geboren', 'cp-communitie').' %monthname %day%th, %year');
			update_option(CPC_OPTIONS_PREFIX.'_profile_avatars', isset($_POST['profile_avatars']) ? $_POST['profile_avatars'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_initial_friend', $_POST['initial_friend']);
			update_option(CPC_OPTIONS_PREFIX.'_redirect_wp_profile', isset($_POST['redirect_wp_profile']) ? $_POST['redirect_wp_profile'] : '');
			
			update_option(CPC_OPTIONS_PREFIX.'_profile_show_unchecked', isset($_POST['profile_show_unchecked']) ? $_POST['profile_show_unchecked'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_cpc_profile_default', isset($_POST['cpc_profile_default']) ? $_POST['cpc_profile_default'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_cpc_default_privacy', isset($_POST['cpc_default_privacy']) ? $_POST['cpc_default_privacy'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_cpc_use_gravatar', isset($_POST['cpc_use_gravatar']) ? $_POST['cpc_use_gravatar'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_hide_location', isset($_POST['cpcommunitie_hide_location']) ? $_POST['cpcommunitie_hide_location'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_profile_menu_type', isset($_POST[ 'cpc_profile_menu_type' ]) ? $_POST[ 'cpc_profile_menu_type' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_templates', isset($_POST[ 'cpc_use_templates' ]) ? $_POST[ 'cpc_use_templates' ] : '');


			if (isset($_POST['__cpc__profile_extended_fields'])) {
		   		$range = array_keys($_POST['__cpc__profile_extended_fields']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['__cpc__profile_extended_fields'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(CPC_OPTIONS_PREFIX.'_profile_extended_fields', $level);	
			// This is the hidden field, if not using default layout
			if (isset($_POST[ '__cpc__profile_extended_fields_list' ])) {
				update_option(CPC_OPTIONS_PREFIX.'_profile_extended_fields', $_POST[ '__cpc__profile_extended_fields_list' ] );
			}


			// Profile menu
			
			// Vertical menu
			if (!get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type')) {
				
				update_option(CPC_OPTIONS_PREFIX.'_menu_texthtml', isset($_POST['menu_texthtml']) ? $_POST['menu_texthtml'] : '');

				update_option(CPC_OPTIONS_PREFIX.'_menu_my_activity', isset($_POST['menu_my_activity']) ? $_POST['menu_my_activity'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity', isset($_POST['menu_friends_activity']) ? $_POST['menu_friends_activity'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_all_activity', isset($_POST['menu_all_activity']) ? $_POST['menu_all_activity'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_profile', isset($_POST['menu_profile']) ? $_POST['menu_profile'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_friends', isset($_POST['menu_friends']) ? $_POST['menu_friends'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_mentions', isset($_POST['menu_mentions']) ? $_POST['menu_mentions'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_groups', isset($_POST['menu_groups']) ? $_POST['menu_groups'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_events', isset($_POST['menu_events']) ? $_POST['menu_events'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_gallery', isset($_POST['menu_gallery']) ? $_POST['menu_gallery'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_following', isset($_POST['menu_following']) ? $_POST['menu_following'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_followers', isset($_POST['menu_followers']) ? $_POST['menu_followers'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_lounge', isset($_POST['menu_lounge']) ? $_POST['menu_lounge'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_avatar', isset($_POST['menu_avatar']) ? $_POST['menu_avatar'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_details', isset($_POST['menu_details']) ? $_POST['menu_details'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_settings', isset($_POST['menu_settings']) ? $_POST['menu_settings'] : '');
				
				update_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_other', isset($_POST['menu_my_activity_other']) ? $_POST['menu_my_activity_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_other', isset($_POST['menu_friends_activity_other']) ? $_POST['menu_friends_activity_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_other', isset($_POST['menu_all_activity_other']) ? $_POST['menu_all_activity_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_profile_other', isset($_POST['menu_profile_other']) ? $_POST['menu_profile_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_friends_other', isset($_POST['menu_friends_other']) ? $_POST['menu_friends_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_mentions_other', isset($_POST['menu_mentions_other']) ? $_POST['menu_mentions_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_groups_other', isset($_POST['menu_groups_other']) ? $_POST['menu_groups_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_events_other', isset($_POST['menu_events_other']) ? $_POST['menu_events_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_gallery_other', isset($_POST['menu_gallery_other']) ? $_POST['menu_gallery_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_following_other', isset($_POST['menu_following_other']) ? $_POST['menu_following_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_followers_other', isset($_POST['menu_followers_other']) ? $_POST['menu_followers_other'] : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_lounge_other', isset($_POST['menu_lounge_other']) ? $_POST['menu_lounge_other'] : '');
				
				update_option(CPC_OPTIONS_PREFIX.'_menu_profile_text', isset($_POST['menu_profile_text']) ? stripslashes($_POST['menu_profile_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_text', isset($_POST['menu_my_activity_text']) ? stripslashes($_POST['menu_my_activity_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_text', isset($_POST['menu_friends_activity_text']) ? stripslashes($_POST['menu_friends_activity_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_text', isset($_POST['menu_all_activity_text']) ? stripslashes($_POST['menu_all_activity_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_friends_text', isset($_POST['menu_friends_text']) ? stripslashes($_POST['menu_friends_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_mentions_text', isset($_POST['menu_mentions_text']) ? stripslashes($_POST['menu_mentions_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_groups_text', isset($_POST['menu_groups_text']) ? stripslashes($_POST['menu_groups_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_events_text', isset($_POST['menu_events_text']) ? stripslashes($_POST['menu_events_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_gallery_text', isset($_POST['menu_gallery_text']) ? stripslashes($_POST['menu_gallery_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_following_text', isset($_POST['menu_following_text']) ? stripslashes($_POST['menu_following_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_followers_text', isset($_POST['menu_followers_text']) ? stripslashes($_POST['menu_followers_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_lounge_text', isset($_POST['menu_lounge_text']) ? stripslashes($_POST['menu_lounge_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_avatar_text', isset($_POST['menu_avatar_text']) ? stripslashes($_POST['menu_avatar_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_details_text', isset($_POST['menu_details_text']) ? stripslashes($_POST['menu_details_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_settings_text', isset($_POST['menu_settings_text']) ? stripslashes($_POST['menu_settings_text']) : '');
	
				update_option(CPC_OPTIONS_PREFIX.'_menu_profile_other_text', isset($_POST['menu_profile_other_text']) ? stripslashes($_POST['menu_profile_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_other_text', isset($_POST['menu_my_activity_other_text']) ? stripslashes($_POST['menu_my_activity_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_other_text', isset($_POST['menu_friends_activity_other_text']) ? stripslashes($_POST['menu_friends_activity_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_other_text', isset($_POST['menu_all_activity_other_text']) ? stripslashes($_POST['menu_all_activity_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_friends_other_text', isset($_POST['menu_friends_other_text']) ? stripslashes($_POST['menu_friends_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_mentions_other_text', isset($_POST['menu_mentions_other_text']) ? stripslashes($_POST['menu_mentions_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_groups_other_text', isset($_POST['menu_groups_other_text']) ? stripslashes($_POST['menu_groups_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_events_other_text', isset($_POST['menu_events_other_text']) ? stripslashes($_POST['menu_events_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_gallery_other_text', isset($_POST['menu_gallery_other_text']) ? stripslashes($_POST['menu_gallery_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_following_other_text', isset($_POST['menu_following_other_text']) ? stripslashes($_POST['menu_following_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_followers_other_text', isset($_POST['menu_followers_other_text']) ? stripslashes($_POST['menu_followers_other_text']) : '');
				update_option(CPC_OPTIONS_PREFIX.'_menu_lounge_other_text', isset($_POST['menu_lounge_other_text']) ? stripslashes($_POST['menu_lounge_other_text']) : '');
				
				
				
			}

			// Horizontal menu
			if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type') || get_option(CPC_OPTIONS_PREFIX.'_use_templates') != "on") {

				$default_menu_structure = '[Profil]
Profil anzeigen=viewprofile
Profildetails=details
Community-Einstellungen=settings
Avatar hochladen=avatar
[Aktivität]
Meine Aktivität=activitymy
Freunde-Aktivität=activityfriends
Alle Aktivitäten=activityall
[Social%f]
Meine Freunde=myfriends
Meine Gruppen=mygroups
Die Lounge=lounge
Meine @Erwähnungen=mentions
Wem ich folge=following
Meine Abonnenten=followers
[Mehr]
Meine Events=events
Meine Gallerie=gallery';

				$default_menu_structure_other = '[Profil]
Profil anzeigen=viewprofile
Profildetails=details
Community-Einstellungen=settings
Avatar hochladen=avatar
[Aktivität]
Aktivität=activitymy
Freunde-Aktivität=activityfriends
Alle Aktivitäten=activityall
[Social]
Freunde=myfriends
Gruppen=mygroups
Die Lounge=lounge
@Erwähnungen=mentions
Ich folge=following
Mir folgen=followers
[Mehr]
Events=events
Galerie=gallery';

				update_option(CPC_OPTIONS_PREFIX.'_profile_menu_structure', (isset($_POST['profile_menu_structure']) && $_POST['profile_menu_structure']) ? $_POST['profile_menu_structure'] : $default_menu_structure);
				update_option(CPC_OPTIONS_PREFIX.'_profile_menu_structure_other', (isset($_POST['profile_menu_structure_other']) && $_POST['profile_menu_structure_other']) ? $_POST['profile_menu_structure_other'] : $default_menu_structure_other);
			
			}

			// Update extended fields
	   		if (isset($_POST['eid']) && $_POST['eid'] != '') {
		   		$range = array_keys($_POST['eid']);
				foreach ($range as $key) {
					$eid = $_POST['eid'][$key];
					$order = $_POST['order'][$key];
					$type = $_POST['type'][$key];
					$default = $_POST['default'][$key];
					$readonly = $_POST['readonly'][$key];
					$search = $_POST['search'][$key];
					$name = $_POST['name'][$key];
					$slug = strtolower(preg_replace("/[^A-Za-z0-9_]/", '',$_POST['slug'][$key]));
					if (in_array($slug, array( "city", "country" ))) $slug .= '_2';
					$wp_usermeta = $_POST['wp_usermeta'][$key];
					$old_wp_usermeta = $_POST['old_wp_usermeta'][$key];
					
					if ( $wp_usermeta != $old_wp_usermeta ) {
						// Hook for connecting/disconnecting EF to/from WP metadata, do something with user data based on admin's choice
						do_action('cpcommunitie_update_extended_metadata_hook', $slug, $wp_usermeta, $old_wp_usermeta);
					}
					
					$wpdb->query( $wpdb->prepare( "
						UPDATE ".$wpdb->base_prefix.'cpcommunitie_extended'."
						SET extended_name = %s, extended_order = %s, extended_slug = %s, extended_type = %s, readonly = %s, search = %s, extended_default = %s, wp_usermeta = %s
						WHERE eid = %d", 
						$name, $order, $slug, $type, $readonly, $search, $default, $wp_usermeta, $eid ) );
				}		
			}
			
			// Add new extended field if applicable
			if ($_POST['new_name'] != '' && $_POST['new_name'] != __('Neues Label', 'cp-communitie') ) {

				if ( ( $_POST['new_slug'] == '' ) || ( $_POST['new_slug'] == __('Neuer Slug', 'cp-communitie') ) ) { $slug = $_POST['new_name']; } else { $slug = $_POST['new_slug']; }
				$slug = sanitize_title_with_dashes( $slug );
				$slug = substr( $slug, 0, 64 );
				
				if (in_array($slug, array( "city", "country" ))) $slug .= '_2';

				$wpdb->query( $wpdb->prepare( "
					INSERT INTO ".$wpdb->base_prefix.'cpcommunitie_extended'."
					( 	extended_name, 
						extended_order,
						extended_slug,
						readonly,
						search,
						extended_type,
						extended_default,
						wp_usermeta
					)
					VALUES ( %s, %d, %s, %s, %s, %s, %s, %s )", 
					array(
						$_POST['new_name'], 
						$_POST['new_order'],
						$slug,
						$_POST['new_readonly'],
						'',
						$_POST['new_type'],
						$_POST['new_default'],
						$_POST['new_wp_usermeta']
					) 
				) );

			}
			
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Gespeichert', 'cp-communitie').".</p></div>";
			
		}
					?>
						
					<form method="post" action=""> 
					<input type="hidden" name="cpcommunitie_update" value="__cpc__plugin_profile">
				
					<table class="form-table __cpc__admin_table"> 

					<tr><td colspan="2"><h2><?php _e('Einstellungen', 'cp-communitie') ?></h2></td></tr>

					<tr valign="top"> 
					<td scope="row"><label for="cpc_use_templates"><?php echo __('Benutzerdefinierte Profilseitenvorlagen', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="cpc_use_templates" id="cpc_use_templates" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo sprintf(__('Aktiviere <a href="%s">Vorlagen</a> für die Profilseite (wenn nicht, wird das Standardlayout verwendet)', 'cp-communitie'), 'admin.php?page=cpcommunitie_templates'); ?></span></td> 
					</tr> 

					<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') == "on") { ?>
						<tr valign="top"> 
						<td scope="row"><label for="cpc_profile_menu_type"><?php echo __('Horizontaler Menüstil', 'cp-communitie'); ?></label></td>
						<td>
						<input type="checkbox" name="cpc_profile_menu_type" id="cpc_profile_menu_type" <?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type') == "on") { echo "CHECKED"; } ?>/>
						<span class="description"><?php echo __('Aktiviere diese Option, um die horizontale Menüversion mit Dropdown-Elementen für Profil- und Gruppenseiten auszuwählen.', 'cp-communitie'); ?></span><br /><br />
						<span class="description"><strong><?php echo __('Wichtig! Wenn aktiviert, stelle sicher, dass Du auch Folgendes tust:', 'cp-communitie'); ?></strong></span><br />
						<ol>
						<span class="description"><li><?php echo __('Setze die Vorlage <a href="admin.php?page=cpcommunitie_templates">Profilseiten-Body</a> zurück.', 'cp-communitie'); ?></span><br />
						<span class="description"><li><?php echo __('Richte Dein Menü ein (unten).', 'cp-communitie'); ?></li></span>
						<?php if (function_exists('__cpc__group')) { ?>
							<span class="description"><li><?php echo __('Setze die Vorlage <a href="admin.php?page=cpcommunitie_templates">Gruppenseite</a> zurück.', 'cp-communitie'); ?></span><br />
							<span class="description"><li><?php echo sprintf(__('Richte Dein <a href="%s">Gruppenmenü</a> ein.', 'cp-communitie'), 'admin.php?page=cp-communitie/groups_admin.php'); ?></li></span>
						<?php } ?>
						</ol>
						</td> 
						</tr> 
						<?php
						?>
						
					<?php } else { ?>
						<input type="hidden" name="cpc_profile_menu_type" id="cpc_profile_menu_type" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type') == 'on') { echo 'on'; } ?>" />
					<?php } ?>

					<tr valign="top"> 
					<td scope="row"><label for="redirect_wp_profile"><?php echo __('Profilseite umleiten', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="redirect_wp_profile" id="redirect_wp_profile" <?php if (get_option(CPC_OPTIONS_PREFIX.'_redirect_wp_profile') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo sprintf(__('Von ClassicPress generierte Links für die ClassicPress-Profilseite auf %s Profilseite umleiten', 'cp-communitie'), CPC_WL_SHORT); ?></span></td> 
					</tr> 
				
					<tr valign="top">
					<td scope="row"><label for="cpc_default_profile"><?php echo __('Standardansicht', 'cp-communitie'); ?></label></td> 
					<td>
					<select name="cpc_profile_default">
						<option value='extended'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_profile_default') == 'extended') { echo ' SELECTED'; } ?>><?php echo __('Profil', 'cp-communitie'); ?></option>
						<option value='wall'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_profile_default') == 'wall') { echo ' SELECTED'; } ?>><?php echo __('Meine Aktivität', 'cp-communitie'); ?></option>
						<option value='activity'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_profile_default') == 'activity') { echo ' SELECTED'; } ?>><?php echo __('Freundesaktivität (schließt meine Aktivität ein)', 'cp-communitie'); ?></option>
						<option value='all'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_profile_default') == 'all') { echo ' SELECTED'; } ?>><?php echo __('Alle Aktivitäten', 'cp-communitie'); ?></option>
					</select> 
					<span class="description"><?php echo __("Standardansicht für die eigene Profilseite des Mitglieds", 'cp-communitie'); ?></span></td> 
					</tr> 		

					<tr valign="top">
					<td scope="row"><label for="cpc_default_privacy"><?php echo __('Standard-Datenschutzstufe', 'cp-communitie'); ?></label></td> 
					<td>
					<select name="cpc_default_privacy">
						<option value='Nobody'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_default_privacy') == 'Nobody') { echo ' SELECTED'; } ?>><?php echo __('Niemand', 'cp-communitie'); ?></option>
						<option value='Friends only'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_default_privacy') == 'Friends only') { echo ' SELECTED'; } ?>><?php echo __('Nur Freunde', 'cp-communitie'); ?></option>
						<option value='Everyone'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_default_privacy') == 'Everyone') { echo ' SELECTED'; } ?>><?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_alt_everyone')); ?></option>
						<option value='public'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_default_privacy') == 'public') { echo ' SELECTED'; } ?>><?php echo __('Öffentlich', 'cp-communitie'); ?></option>
					</select> 
					<span class="description"><?php echo __("Standard-Datenschutzeinstellung für neue Mitglieder", 'cp-communitie'); ?></span></td> 
					</tr> 		

					<tr valign="top"> 
					<td scope="row"><label for="initial_friend"><?php echo __('Standardfreund', 'cp-communitie'); ?></label></td> 
					<td><input name="initial_friend" type="text" id="initial_friend"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_initial_friend'); ?>" /> 
					<span class="description"><?php echo __('Durch Kommas getrennte Liste von Benutzer-IDs, die automatisch Freunde neuer Benutzer werden (leer lassen für niemanden)', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="profile_avatars"><?php echo __('Profilbilder', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="profile_avatars" id="profile_avatars" <?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_avatars') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Mitgliedern erlauben, ihre eigenen Profilfotos hochzuladen und die internen ClassicPress-Avatare zu überschreiben', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="cpc_use_gravatar"><?php echo __('Verwende Gravatar', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="cpc_use_gravatar" id="cpc_use_gravatar" <?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_use_gravatar') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Wenn es Mitgliedern erlaubt wird, Profilfotos hochzuladen, sollte <a href="http://www.gravatar.com" target="_blank">gravatar</a> verwendet werden, wenn sie dies noch nicht getan haben?', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="use_poke"><?php echo __('Poke/Nudge/Wink/etc', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="use_poke" id="use_poke" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_poke') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Aktiviere diese Funktion', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="poke_label"><?php echo __('Poke Label', 'cp-communitie'); ?></label></td> 
					<td><input name="poke_label" type="text" id="poke_label"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_poke_label'); ?>" /> 
					<span class="description"><?php echo __('Das "Poke"-Button-Label für Deine Webseite, achte auf markenrechtlich geschützte Wörter (einschließlich Poke und Nudge zum Beispiel)', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="status_label"><?php echo __('Status Label', 'cp-communitie'); ?></label></td> 
					<td><input name="status_label" type="text" id="status_label"  value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_status_label')); ?>" /> 
					<span class="description"><?php echo __('Die Standardaufforderung für neue Aktivitätsbeiträge auf der Profilseite', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="show_dob"><?php echo __('Verwende das Geburtsdatum', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="show_dob" id="show_dob" <?php if (get_option(CPC_OPTIONS_PREFIX.'_show_dob') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Geburtsdatum im Profil verwenden', 'cp-communitie'); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="show_dob_format"><?php echo __('Geburtsdatumsformat', 'cp-communitie'); ?></label></td>
					<td><input name="show_dob_format" type="text" id="show_dob_format" style="width:250px;" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_show_dob_format'); ?>" /> 
					<span class="description"><?php echo sprintf(__('Gültige Parameter: %%0day %%day %%th %%0month %%month %%monthname %%year (siehe <a href="%s">Admin-Leitfaden</a>)', 'cp-communitie'), 'https://dl.dropbox.com/u/49355018/cpc.pdf'); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="show_wall_extras"><?php echo __('Kürzlich aktive Freunde-Box', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="show_wall_extras" id="show_wall_extras" <?php if (get_option(CPC_OPTIONS_PREFIX.'_show_wall_extras') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Feld "Kürzlich aktive Freunde" an der Seite der Wand anzeigen (kann je nach Seitenvorlage Platz einnehmen)', 'cp-communitie'); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="profile_google_map"><?php echo __('Google Map', 'cp-communitie'); ?></label></td> 
					<td><input name="profile_google_map" type="text" id="profile_google_map" style="width:50px" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_profile_google_map'); ?>" /> 
					<span class="description"><?php echo __('Größe der Standortkarte in Pixel. zB: 250. Zum Ausblenden auf 0 setzen.', 'cp-communitie'); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="profile_comments"><?php echo __('Kommentarfelder anzeigen', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="profile_comments" id="profile_comments" <?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_comments') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Post-Kommentarfelder immer anzeigen (oder zum Anzeigen Hover)', 'cp-communitie'); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="cpcommunitie_hide_location"><?php echo __('Standortfelder entfernen', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="cpcommunitie_hide_location" id="cpcommunitie_hide_location" <?php if (get_option(CPC_OPTIONS_PREFIX.'_hide_location') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Standortprofilfelder ausblenden und deaktivieren und Entfernungen aus dem Mitgliederverzeichnis ausschließen', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="enable_password"><?php echo __('Kennwortänderung aktivieren', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="enable_password" id="enable_password" <?php if (get_option(CPC_OPTIONS_PREFIX.'_enable_password') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Mitgliedern erlauben, ihr Passwort zu ändern', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="online"><?php echo __('Inaktivitätszeitraum', 'cp-communitie'); ?></label></td> 
					<td><input name="online" type="text" id="online" style="width:50px"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_online'); ?>" /> 
					<span class="description"><?php echo __('Wie viele Minuten, bevor ein Mitglied als offline angesehen wird', 'cp-communitie'); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="offline">&nbsp;</label></td> 
					<td><input name="offline" type="text" id="offline" style="width:50px"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_offline'); ?>" /> 
					<span class="description"><?php echo __('Wie viele Minuten bevor angenommen wird, dass sich ein Mitglied abgemeldet hat', 'cp-communitie'); ?></span></td> 
					</tr> 
					
					<tr><td colspan="2"><h2><?php _e('Elemente des Profilmenüs', 'cp-communitie') ?></h2></td></tr>

					<?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type') || get_option(CPC_OPTIONS_PREFIX.'_use_templates') != "on") { ?>

						<tr valign="top"> 
						<td scope="row"><label for="profile_menu_structure"><?php echo __('Persönliche Seite', 'cp-communitie'); ?></label></td>
						<td>
						<textarea rows="12" cols="40" name="profile_menu_structure" id="profile_menu_structure"><?php echo get_option(CPC_OPTIONS_PREFIX.'_profile_menu_structure') ?></textarea><br />
						<span class="description">
							<?php echo sprintf(__('Gilt nur für die horizontale Version des Profilseitenmenüs', 'cp-communitie'), CPC_WL); ?><br />
							<?php echo sprintf(__('%%f wird durch ausstehende Freundschaftsanfragen in Gegenständen der obersten Ebene ersetzt', 'cp-communitie'), CPC_WL); ?>
						</span><br />
						<a id="__cpc__reset_profile_menu" href="javascript:void(0)"><?php echo __('Auf Standard zurücksetzen...', 'cp-communitie'); ?></a>
						</td> 
						</tr> 
					
						<tr valign="top"> 
						<td scope="row"><label for="profile_menu_structure_other"><?php echo __('Andere Mitglieder', 'cp-communitie'); ?></label></td>
						<td>
						<textarea rows="12" cols="40" name="profile_menu_structure_other" id="profile_menu_structure_other"><?php echo get_option(CPC_OPTIONS_PREFIX.'_profile_menu_structure_other') ?></textarea><br />
						<span class="description"><?php echo sprintf(__('Gilt nur für die horizontale Version des Profilseitenmenüs', 'cp-communitie'), CPC_WL); ?></span><br />
						<a id="__cpc__reset_profile_menu_other" href="javascript:void(0)"><?php echo __('Auf Standard zurücksetzen...', 'cp-communitie'); ?></a>
						</td> 
						</tr> 
						
					<?php } else { ?>

						<input type="hidden" name="profile_menu_structure" id="profile_menu_structure" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_profile_menu_structure') ?>" />
						<input type="hidden" name="profile_menu_structure_other" id="profile_menu_structure_other" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_profile_menu_structure_other') ?>" />
	
					<?php } ?>

					<?php if (!get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type') && get_option(CPC_OPTIONS_PREFIX.'_use_templates') == "on") { ?>
					
						<tr valign="top"> 
						<td colspan="2" style="padding:0">
							<table>
								<tr style='font-weight:bold'>
									<td style="width:125px"><?php _e('Menüpunkt', 'cp-communitie'); ?></td>
									<td><?php _e('Persönliche Seite', 'cp-communitie'); ?></td>
									<td><?php _e('Persönlicher Seitentext', 'cp-communitie'); ?></td>
									<td><?php _e('Andere Mitglieder', 'cp-communitie'); ?></td>
									<td><?php _e('Andere Mitglieder Text', 'cp-communitie'); ?></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Profil', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_profile" id="menu_profile" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_profile') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_profile_text" type="text" id="menu_profile_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_profile_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_profile_other" id="menu_profile_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_profile_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_profile_other_text" type="text" id="menu_profile_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_profile_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Meine Aktivität', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_my_activity" id="menu_my_activity" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_my_activity_text" type="text" id="menu_my_activity_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_my_activity_other" id="menu_my_activity_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_my_activity_other_text" type="text" id="menu_my_activity_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Freunde-Aktivität', 'cp-communitie'); ?></span></span></td>
									<td align='center'><input type="checkbox" name="menu_friends_activity" id="menu_friends_activity" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_friends_activity_text" type="text" id="menu_friends_activity_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_friends_activity_other" id="menu_friends_activity_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_friends_activity_other_text" type="text" id="menu_friends_activity_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Alle Aktivitäten', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_all_activity" id="menu_all_activity" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_all_activity_text" type="text" id="menu_all_activity_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_all_activity_other" id="menu_all_activity_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_all_activity_other_text" type="text" id="menu_all_activity_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Freunde', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_friends" id="menu_friends" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_friends_text" type="text" id="menu_friends_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_friends_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_friends_other" id="menu_friends_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_friends_other_text" type="text" id="menu_friends_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_friends_other_text'); ?>" /></td>
								</tr>
								<?php if ( function_exists('__cpc__profile_plus') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Forum @Erwähnungen', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_mentions" id="menu_mentions" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_mentions') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_mentions_text" type="text" id="menu_mentions_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_mentions_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_mentions_other" id="menu_mentions_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_mentions_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_mentions_other_text" type="text" id="menu_mentions_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_mentions_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__cpc__group') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Gruppen', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_groups" id="menu_groups" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_groups') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_groups_text" type="text" id="menu_groups_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_groups_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_groups_other" id="menu_groups_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_groups_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_groups_other_text" type="text" id="menu_groups_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_groups_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__cpc__events_main') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Events', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_events" id="menu_events" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_events') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_events_text" type="text" id="menu_events_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_events_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_events_other" id="menu_events_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_events_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_events_other_text" type="text" id="menu_events_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_events_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__cpc__gallery') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Galerie', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_gallery" id="menu_gallery" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_gallery') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_gallery_text" type="text" id="menu_gallery_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_gallery_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_gallery_other" id="menu_gallery_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_gallery_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_gallery_other_text" type="text" id="menu_gallery_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_gallery_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__cpc__profile_plus') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Ich folge', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_following" id="menu_following" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_following') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_following_text" type="text" id="menu_following_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_following_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_following_other" id="menu_following_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_following_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_following_other_text" type="text" id="menu_following_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_following_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Mir folgen', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_followers" id="menu_followers" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_followers') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_followers_text" type="text" id="menu_followers_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_followers_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_followers_other" id="menu_followers_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_followers_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_followers_other_text" type="text" id="menu_followers_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_followers_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__cpc__lounge_main') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Die Lounge', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_lounge" id="menu_lounge" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_lounge') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_lounge_text" type="text" id="menu_lounge_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_lounge_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_lounge_other" id="menu_lounge_other" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_lounge_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_lounge_other_text" type="text" id="menu_lounge_other_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_lounge_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<tr>
									<td><span class="description"><?php echo __('Profilbild', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_avatar" id="menu_avatar" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_avatar') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_avatar_text" type="text" id="menu_avatar_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_avatar_text'); ?>" /></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Profildetails', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_details" id="menu_details" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_details') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_details_text" type="text" id="menu_details_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_details_text'); ?>" /></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('', 'cp-communitie'); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_settings" id="menu_settings" <?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_settings') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_settings_text" type="text" id="menu_settings_text"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_settings_text'); ?>" /></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
							</table>
						
						</td> 
						</tr> 
	
						<tr valign="top"> 
						<td scope="row"><label for="menu_texthtml"><?php echo __('Profil Menütext/HTML', 'cp-communitie'); ?></label></td>
						<td>
						<textarea name="menu_texthtml" id="menu_texthtml" rows="4" cols="30"><?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_menu_texthtml')); ?></textarea><br />
						<span class="description"><?php echo __('Text/HTML, der am Ende des Profilmenüs erscheint', 'cp-communitie'); ?></span></td> 
						</tr> 

					<?php } else {?>

						<input type="hidden" name="menu_profile" id="menu_profile" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_profile') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_profile_text" id="menu_profile_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_profile_text'); ?>" />
						<input type="hidden" name="menu_profile_other" id="menu_profile_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_profile_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_profile_other_text" id="menu_profile_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_profile_other_text'); ?>" />
						
						<input type="hidden" name="menu_my_activity" id="menu_my_activity" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_my_activity_text" id="menu_my_activity_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_text'); ?>" />
						<input type="hidden" name="menu_my_activity_other" id="menu_my_activity_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_my_activity_other_text" id="menu_my_activity_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_my_activity_other_text'); ?>" />
						
						<input type="hidden" name="menu_friends_activity" id="menu_friends_activity" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_friends_activity_text" id="menu_friends_activity_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_text'); ?>" />
						<input type="hidden" name="menu_friends_activity_other" id="menu_friends_activity_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_friends_activity_other_text" id="menu_friends_activity_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_friends_activity_other_text'); ?>" />
						
						<input type="hidden" name="menu_all_activity" id="menu_all_activity" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_all_activity_text" id="menu_all_activity_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_text'); ?>" />
						<input type="hidden" name="menu_all_activity_other" id="menu_all_activity_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_all_activity_other_text" id="menu_all_activity_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_all_activity_other_text'); ?>" />
						
						<input type="hidden" name="menu_friends" id="menu_friends" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_friends_text" id="menu_friends_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_friends_text'); ?>" />
						<input type="hidden" name="menu_friends_other" id="menu_friends_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_friends_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_friends_other_text" id="menu_friends_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_friends_other_text'); ?>" />
						
						<input type="hidden" name="menu_mentions" id="menu_mentions" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_mentions') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_mentions_text" id="menu_mentions_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_mentions_text'); ?>" />
						<input type="hidden" name="menu_mentions_other" id="menu_mentions_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_mentions_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_mentions_other_text" id="menu_mentions_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_mentions_other_text'); ?>" />
						
						<input type="hidden" name="menu_groups" id="menu_groups" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_groups') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_groups_text" id="menu_groups_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_groups_text'); ?>" />
						<input type="hidden" name="menu_groups_other" id="menu_groups_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_groups_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_groups_other_text" id="menu_groups_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_groups_other_text'); ?>" />
						
						<input type="hidden" name="menu_events" id="menu_events" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_events') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_events_text" id="menu_events_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_events_text'); ?>" />
						<input type="hidden" name="menu_events_other" id="menu_events_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_events_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_events_other_text" id="menu_events_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_events_other_text'); ?>" />
						
						<input type="hidden" name="menu_gallery" id="menu_gallery" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_gallery') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_gallery_text" id="menu_gallery_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_gallery_text'); ?>" />
						<input type="hidden" name="menu_gallery_other" id="menu_gallery_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_gallery_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_gallery_other_text" id="menu_gallery_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_gallery_other_text'); ?>" />
						
						<input type="hidden" name="menu_following" id="menu_following" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_following') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_following_text" id="menu_following_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_following_text'); ?>" />
						<input type="hidden" name="menu_following_other" id="menu_following_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_following_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_following_other_text" id="menu_following_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_following_other_text'); ?>" />
						
						<input type="hidden" name="menu_followers" id="menu_followers" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_followers') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_followers_text" id="menu_followers_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_followers_text'); ?>" />
						<input type="hidden" name="menu_followers_other" id="menu_followers_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_followers_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_followers_other_text" id="menu_followers_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_followers_other_text'); ?>" />
						
						<input type="hidden" name="menu_lounge" id="menu_lounge" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_lounge') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_lounge_text" id="menu_lounge_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_lounge_text'); ?>" />
						<input type="hidden" name="menu_lounge_other" id="menu_lounge_other" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_lounge_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_lounge_other_text" id="menu_lounge_other_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_lounge_other_text'); ?>" />
						
						<input type="hidden" name="menu_avatar" id="menu_avatar" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_avatar') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_avatar_text" id="menu_avatar_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_avatar_text'); ?>" />
						
						<input type="hidden" name="menu_details" id="menu_details" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_details') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_details_text" id="menu_details_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_details_text'); ?>" />
						
						<input type="hidden" name="menu_settings" id="menu_settings" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_menu_settings') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_settings_text" id="menu_settings_text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_settings_text'); ?>" />
						
						<input type="hidden" name="menu_texthtml" id="menu_texthtml" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_menu_texthtml'); ?>" />
						
											
					<?php } ?>

					<?php
						// Hook to add items to the Profile settings page
						echo apply_filters ( '__cpc__profile_settings_before_ef_hook', "" );
					?>						

					<tr><td colspan="2"><h2><?php _e('Erweiterte Felder', 'cp-communitie') ?></h2></td></tr>

					<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') != "on") { ?>

						<?php
						// Optionally include extended fields
						$sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_extended";
						$extensions = $wpdb->get_results($sql);
		
						$ext_rows = array();		
						if ($extensions) {		
							foreach ($extensions as $extension) {
								array_push ($ext_rows, array (	'eid'=>$extension->eid,
																'name'=>$extension->extended_name,
																'type'=>$extension->extended_type,
																'order'=>$extension->extended_order ) );
							}
						}						
						if ($ext_rows) {
							?>
							<tr valign="top"> 
							<td scope="row"><label for="redirect_wp_profile"><?php echo __('Erweiterte Felder, die<br />in der Kopfzeile der Profilseite angezeigt werden', 'cp-communitie'); ?></label></td>
							<td>
							<?php
							$include = get_option(CPC_OPTIONS_PREFIX.'_profile_extended_fields');
							$ext_rows = __cpc__sub_val_sort($ext_rows,'order');
							foreach ($ext_rows as $row) {
								echo '<input type="checkbox" ';
								if (strpos($include, $row['eid'].',') !== FALSE)
									echo 'CHECKED ';
								echo 'name="__cpc__profile_extended_fields[]" value="'.$row['eid'].'" />';
								echo ' <span class="description">'.stripslashes($row['name']).'</span><br />';
							}
							echo '</td></tr>';

						}
						?>
						
					<?php } else { 
						echo '<input type="hidden" name="__cpc__profile_extended_fields_list" value="'.get_option(CPC_OPTIONS_PREFIX.'_profile_extended_fields').'" />';
					} ?>
					
					<tr valign="top"> 
					<td scope="row"><?php echo __('Aktuelle erweiterte Felder', 'cp-communitie'); ?></td><td>
					
						<?php
						echo '<input type="checkbox" name="profile_show_unchecked" id="profile_show_unchecked"';
						if (get_option(CPC_OPTIONS_PREFIX.'_profile_show_unchecked') == "on") { echo "CHECKED"; }
						echo '/> <span class="description">'. __('Nicht ausgewählte Kontrollkästchenfelder anzeigen (auf der Mitgliederprofilseite)', 'cp-communitie').'</span>';

						// Extended Fields table
						$extensions = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_extended ORDER BY extended_order, extended_name");
						$sql = " WHERE meta_key NOT LIKE 'cpcommunitie_%'";
						$sql .= " AND meta_key NOT LIKE '%wp_%'";
						$sql .= " AND meta_key NOT LIKE '%level%'";
						$sql .= " AND meta_key NOT LIKE '%role%'";
						$sql .= " AND meta_key NOT LIKE '%capabilit%'";
						$sql = apply_filters( 'cpcommunitie_query_wp_metadata_hook', $sql );						
						$rows = $wpdb->get_results("SELECT DISTINCT meta_key FROM ".$wpdb->base_prefix."usermeta".$sql);
						
						echo '<style>.widefat td { border:0 } </style>';
						echo '<table class="widefat">';
						echo '<thead>';
						echo '<tr>';
						echo '<th style="width:40px">'.__('Sortierung', 'cp-communitie').'</th>';
						echo '<th style="width:40px">'.__('Slug', 'cp-communitie').'</th>';
						echo '<th>'.__('Label', 'cp-communitie').'</th>';
						echo '<th>'.__('Standardwert', 'cp-communitie').'</th>';
						echo '<th>'.__('Schreibgeschützt?', 'cp-communitie').'</th>';
						echo '<th>'.__('Erweiterte Suche?', 'cp-communitie').'</th>';
						echo '<th style="width:80px">'.__('Typ', 'cp-communitie').'</th>';
						echo '<th style="width:30px">&nbsp;</th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
						$cnt = 0;
						if ($extensions) {
							foreach ($extensions as $extension) {

								$slug = (!$extension->extended_slug) ? 'slug_'.$extension->eid : $extension->extended_slug ;
								$cnt++;
								if ( $cnt % 2 != 0 ) {
									echo '<tr>';
								} else {
									echo '<tr style="background-color:#eee">';
								}
									echo '<td>';
									echo '<input type="hidden" name="eid[]" value="'.$extension->eid.'" />';
									echo '<input type="text" name="order[]" style="width:40px" value="'.$extension->extended_order.'" />';
									echo '</td>';
									echo '<td>';
									echo '<input type="hidden" name="slug[]" value="'.$slug.'" />'.$slug;
									echo '</td>';
									echo '<td>';
									echo '<input type="text" name="name[]" value="'.stripslashes($extension->extended_name).'" />';
									echo '</td>';
									echo '<td>';
									echo '<input type="text" name="default[]" value="'.stripslashes($extension->extended_default).'" />';
									echo '</td>';
									echo '<td>';
									echo '<select name="readonly[]">';
									echo '<option value=""';
										if ($extension->readonly != 'on') echo ' SELECTED';
										echo '>'.__('Nein', 'cp-communitie').'</option>';
									echo '<option value="on"';
										if ($extension->readonly == 'on') echo ' SELECTED';
										echo '>'.__('Ja', 'cp-communitie').'</option>';
									echo '</select>';
									echo '</td>';
									echo '<td>';
									if ($extension->extended_type == 'Checkbox' || $extension->extended_type == 'List') {
										echo '<select name="search[]">';
										echo '<option value=""';
											if ($extension->search != 'on') echo ' SELECTED';
											echo '>'.__('Nein', 'cp-communitie').'</option>';
										echo '<option value="on"';
											if ($extension->search == 'on') echo ' SELECTED';
											echo '>'.__('Ja', 'cp-communitie').'</option>';
										echo '</select>';
									} else {
										echo '<select name="search[]">';
										echo '<option value=""';
											if ($extension->search != 'on') echo ' SELECTED';
											echo '>'.__('Nein (falscher Typ)', 'cp-communitie').'</option>';
										echo '</select>';
									}
									echo '</td>';
									echo '<td>';
									echo '<select name="type[]">';
									echo '<option value="Text"';
										if ($extension->extended_type == 'Text') { echo ' SELECTED'; }
										echo '>'.__('Text', 'cp-communitie').'</option>';
									echo '<option value="Checkbox"';
										if ($extension->extended_type == 'Checkbox') { echo ' SELECTED'; }
										echo '>'.__('Kontrollkästchen', 'cp-communitie').'</option>';
									echo '<option value="List"';
										if ($extension->extended_type == 'List') { echo ' SELECTED'; }
										echo '>'.__('Liste', 'cp-communitie').'</option>';
									echo '<option value="Textarea"';
										if ($extension->extended_type == 'Textarea') { echo ' SELECTED'; }
										echo '>'.__('Textbereich', 'cp-communitie').'</option>';
									echo '</select>';
									echo '</td>';
									echo '<td>';
									echo "<a href='admin.php?page=cpcommunitie_profile&view=profile&del_eid=".$extension->eid."' class='delete'>".__('Löschen', 'cp-communitie')."</a>";
									echo '</td>';
								echo '</tr>';
								if ( $cnt % 2 != 0 ) {
									echo '<tr>';
								} else {
									echo '<tr style="background-color:#eee">';
								}
								echo '<td colspan="2"></td><td colspan="6">';
									echo __('Verknüpfte CP-Metadaten', 'cp-communitie').':<br />';
                                    echo '<input type="hidden" name="old_wp_usermeta[]" value="'.$extension->wp_usermeta.'" />';
									echo '<select name="wp_usermeta[]"><option value="" SELECTED></option>';
									if ($rows) {
										foreach ($rows as $row) {
											echo '<option value="'.$row->meta_key .'"';
											if ( $row->meta_key == $extension->wp_usermeta ) { echo ' SELECTED'; }
											echo '>'.$row->meta_key.'</option>';
										}
									}
									echo '</select>';
								echo '</td>';
								echo '</tr>';
							}
						}
						echo '</table>';
						
						echo '<tr valign="top">';
						echo '<td scope="row">'.__('Erweitertes Feld hinzufügen', 'cp-communitie').'</td><td>';

						echo '<table class="widefat">';
						echo '<thead><tr>';
						echo '<th style="width:40px">'.__('Sortierung', 'cp-communitie').'</th>';
						echo '<th style="width:40px">'.__('Slug', 'cp-communitie').'</th>';
						echo '<th>'.__('Label', 'cp-communitie').'</th>';
						echo '<th>'.__('Standardwert', 'cp-communitie').'</th>';
						echo '<th>&nbsp;</th>';
						echo '<th>&nbsp;</th>';
						echo '<th style="width:80px">'.__('Typ', 'cp-communitie').'</th>';
						echo '<th style="width:30px">&nbsp;</th>';
						echo '</tr></thead>';
						echo '<tr>';
							echo '<td>';
							echo '<input type="text" name="new_order" style="width:40px" onclick="javascript:this.value = \'\'" value="0" />';
							echo '</td>';
							echo '<td>';
							echo '<input type="text" name="new_slug" style="width:75px" onclick="javascript:this.value = \'\'" value="'.__('Neuer Slug', 'cp-communitie').'" />';
							echo '</td>';
							echo '<td>';
							echo '<input type="text" name="new_name" onclick="javascript:this.value = \'\'" value="'.__('Neues Label', 'cp-communitie').'" />';
							echo '</td>';
							echo '<td>';
							echo '<input type="text" name="new_default" onclick="javascript:this.value = \'\'" value="" />';
							echo '</td>';
							echo '<td>';
							echo '<select name="new_readonly">';
							echo '<option value="" SELECTED>'.__('Nein', 'cp-communitie').'</option>';
							echo '<option value="on">'.__('Ja', 'cp-communitie').'</option>';
							echo '</select>';
							echo '</td>';
							echo '<td></td>';
							echo '<td>';
							echo '<select name="new_type">';
							echo '<option value="Text" SELECTED>'.__('Text', 'cp-communitie').'</option>';
							echo '<option value="Checkbox">'.__('Kontrollkästchen', 'cp-communitie').'</option>';
							echo '<option value="List">'.__('Liste', 'cp-communitie').'</option>';
							echo '<option value="Textarea">'.__('Textbereich', 'cp-communitie').'</option>';
							echo '</select>';
							echo '</td>';
							echo '<td>&nbsp;</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td colspan="2"></td><td colspan="5">';
							echo __('Verknüpfte CP-Metadaten', 'cp-communitie').':<br />';
							echo '<select name="new_wp_usermeta"><option value="" SELECTED></option>';
							if ($rows) {
								foreach ($rows as $row) {
									echo '<option value="'.$row->meta_key .'">'.$row->meta_key.'</option>';
								}
							}
							echo '</select>';
							echo '</td>';
						echo '</tr>';
						echo '<tr><td colspan="7"><span class="description">';
						echo __('Gib für Listen alle Werte durch Kommas getrennt ein – der erste Wert ist die Standardauswahl.', 'cp-communitie');
						echo '<br />'.__('Gib für Kontrollkästchen den Wert \'on\' ein, um standardmäßig aktiviert zu sein.', 'cp-communitie');
						echo '<br />'.__('Slugs sollten ein einzelnes beschreibendes Wort sein (Schlüsselwort für besseres SEO).', 'cp-communitie');
						echo '<br />'.__('Die Werte der erweiterten Mitgliederfelder werden nicht angezeigt, wenn sie leer gelassen werden, mit Ausnahme von Kontrollkästchen, in denen Du auswählen kannst, was oben passiert.', 'cp-communitie');

						echo '<br /><br /><strong>'.__('Erweiterte Felder und Metadaten des ClassicPress-Profils', 'cp-communitie').'</strong>';
						echo '<br />'.__('Erweiterte Felder können mit den Metadaten des ClassicPress-Profils verknüpft werden – stelle sicher, dass Du den richtigen Typ auswählst, der mit den Metadaten des ClassicPress-Profils übereinstimmt.', 'cp-communitie');
						echo '<br />'.__('Verlinke nur auf ClassicPress-Profilmetadaten, auf die Deine Benutzer zugreifen sollen, und verwende die schreibgeschützte Einstellung, um zu verhindern, dass sie Änderungen vornehmen.', 'cp-communitie');

						// Display user info as an example
						$rows = $wpdb->get_results("SELECT meta_key, meta_value FROM ".$wpdb->base_prefix."usermeta".$sql." AND user_id = '".$user_ID."'");
						echo '<br /><br />';
						echo '<input id="cpcommunitie_meta_show_button" style="margin-bottom:10px;" onclick="document.getElementById(\'cpcommunitie_meta_show\').style.display=\'block\';document.getElementById(\'cpcommunitie_meta_show_button\').style.display=\'none\';document.getElementById(\'cpcommunitie_meta_show_button_hide\').style.display=\'block\';" value="'.__('CP-Metadaten für den aktuellen Benutzer anzeigen', 'cp-communitie').'" type="button">';
						echo '<input id="cpcommunitie_meta_show_button_hide" style="margin-bottom:10px;display:none;" onclick="document.getElementById(\'cpcommunitie_meta_show\').style.display=\'none\';document.getElementById(\'cpcommunitie_meta_show_button\').style.display=\'block\';document.getElementById(\'cpcommunitie_meta_show_button_hide\').style.display=\'none\';" value="'.__('CP-Metadaten ausblenden', 'cp-communitie').'" type="button">';
						echo '<div id="cpcommunitie_meta_show" style="display:none;">';
						
						echo '<table class="widefat" style="width:400px"><thead><tr>';
						echo '<th>'.__('CP Metadata', 'cp-communitie').'</th>';
						echo '<th>'.__('Wert', 'cp-communitie').'</th>';
						echo '</tr></thead><tbody>';
						foreach ($rows as $row) {
							echo '<tr><td>'.$row->meta_key.'</td><td>';
							$meta_value = maybe_unserialize($row->meta_value);
							if (is_array($meta_value)) {
								echo '<input class="regular-text all-options disabled" type="text" value="'.__('SERIALIZED DATA', 'cp-communitie').'" disabled="disabled" />';
							} else {
								// let's cut very long strings in parts so that browsers display them correctly
								$v = str_replace(",", ", ", $row->meta_value);
								$v = str_replace(";", "; ", $v);
								echo $v;
							}
							echo '</td></tr>';
						}
						echo '</tbody></table>';
						echo '</div>';
						
						echo '</td></tr></tbody></table>'; // class="widefat"
						
						// Hook to add items to the Profile settings page
						echo apply_filters ( '__cpc__profile_settings_hook', "" );

						?>
						<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
			
						<table style="margin-left:10px; margin-top:10px;">						
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-stream]</td>
								<td><?php echo __('Alle Aktivitäten anzeigen, mit Aktivitätsfeld.', 'cp-communitie'); ?></td></tr>
							<tr><td width="230px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-profile]</td>
								<td><?php echo __('Profilseite, standardmäßig Aktivität.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-extended]</td>
								<td><?php echo __('Profilseite, standardmäßig erweiterte Informationen.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-activity]</td>
								<td><?php echo __('Profilseite, standardmäßig die Aktivitäten von Freunden.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-settings]</td>
								<td><?php echo __('Profilseite, standardmäßig auf Einstellungen.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-gallery]</td>
								<td><?php echo __('Profilseite, standardmäßig Galerie.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-personal]</td>
								<td><?php echo __('Profilseite, standardmäßig mit persönlichen Informationen.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-friends]</td>
								<td><?php echo __('Profilseite, standardmäßig Freunde.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-avatar]</td>
								<td><?php echo __('Profilseite, standardmäßig Avatar-Upload.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-following]</td>
								<td><?php echo __('Profilseite, standardmäßig für Mitglieder, die folgen.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-menu]</td>
								<td><?php echo __('Zeigt das Profilmenü an.', 'cp-communitie'); ?></td></tr>
							<tr><td>[<?php echo CPC_SHORTCODE_PREFIX; ?>-member-header]</td>
								<td><?php echo __('Zeigt nur die Kopfzeile der Profilseite des Mitglieds an.', 'cp-communitie'); ?></td></tr>
							<?php if (function_exists('__cpc__profile_plus')) { ?>
							<tr><td width="230px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-following]</td>
								<td><?php echo __('Zeigt die Profilseite an und zeigt standardmäßig an, wem das Mitglied folgt.', 'cp-communitie'); ?></td></tr>
							<?php } ?>
						</table>
						
						<?php
												
						echo '</table>'; // class="form-table"
					?>
					</td></tr>
					
					</table>
	
					<?php
					echo '<p class="submit" style="margin-left:6px">';
					echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Änderungen speichern', 'cp-communitie').'" />';
					echo '</p>';
					echo '</form>';
					
					
		__cpc__show_tabs_header_end();
	  	

	echo '</div>';									  

}

function __cpc__plugin_audit() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Audit', 'cp-communitie'), CPC_WL).'</h2><br />';
	__cpc__show_manage_tabs_header('audit');

	global $wpdb, $blog_id;

	// Clear audit table
	if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__plugin_audit_clear' ) {
		$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_audit";
		$wpdb->query($sql);

		echo "<div class='updated slideaway'><p>".__('Protokoll gelöscht', 'cp-communitie').".</p></div>";

	}

	// See if the user has posted general settings
	if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__plugin_audit' ) {

		$type = $_POST['type'];
		$blogID = $_POST['blog'];
		$action = $_POST['action'];
		$userID = $_POST['user_id'];
		$current_userID = $_POST['current_user_id'];
		$metafield = $_POST['meta'];
		
		$orderby = $_POST['orderby'];
		$asc = $_POST['asc'];
		$start_date = $_POST['start'];
		$end_date = $_POST['end'];
		$count = $_POST['count'];
					
	} else {
		
		$type = 'all';
		$blogID = $blog_id;
		$action = 'all';
		$userID = 'all';
		$current_userID = 'all';
		$metafield = 'all';
		
		$orderby = 'blog_id';
		$asc = '';
		$start = date("Y-m-d");
		$start_date = date('Y-m-d', strtotime($start . ' - 1 month'));
		$end = date("Y-m-d");
		$end_date = date('Y-m-d', strtotime($end . ' + 1 day'));
		$count = 50;
	}


?>

	<table> 	

		<tr style="font-weight:bold">
		<td><?php _e('Typ', 'cp-communitie'); ?></td>
		<td><?php _e('Blog', 'cp-communitie'); ?></td>
		<td><?php _e('Aktion', 'cp-communitie'); ?></td>
		<td><?php _e('Benutzer', 'cp-communitie'); ?></td>
		<td><?php _e('Aktueller Benutzer', 'cp-communitie'); ?></td>
		<td><?php _e('Meta', 'cp-communitie'); ?></td>
		</tr> 
		<tr>
		<form method="post" action=""> 
		<input type="hidden" name="cpcommunitie_update" value="__cpc__plugin_audit">
		<td>
		<select name="type">
			<option value="all" <?php if ($type == 'all') echo ' SELECTED'; ?>><?php _e('Alle', 'cp-communitie'); ?></option>
			<option value="usermeta" <?php if ($type == 'usermeta') echo ' SELECTED'; ?>><?php _e('Benutzermeta', 'cp-communitie'); ?></option>
		</select>
		</td> 
		<td>
		<select name="blog">
			<option value="all"><?php _e('Alle', 'cp-communitie'); ?></option>
			<?php 
		    $blogs = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."blogs");
		    if ($blogs) {
		        foreach($blogs as $blog) {
		            echo '<option value='.$blog->blog_id;
		            if ($blog->blog_id == $blogID) echo ' SELECTED';
		            echo '>'.$blog->blog_id.': '.$blog->path.'</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td> 
		<td>
		<select name="action">
			<option value="all"><?php _e('Alle', 'cp-communitie'); ?></option>
			<?php 
		    $actions = $wpdb->get_results("SELECT DISTINCT action FROM ".$wpdb->base_prefix."cpcommunitie_audit ORDER BY action");
		    if ($actions) {
		        foreach($actions as $a) {
		            echo '<option value='.$a->action;
		            if ($a->action == $action) echo ' SELECTED';
		            echo '>'.$a->action.'</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td> 
		<td>
		<select name="user_id">
			<option value="all"><?php _e('Alle', 'cp-communitie'); ?></option>
			<?php 
		    $users = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."users ORDER BY display_name");
		    if ($users) {
		        foreach($users as $user) {
		            echo '<option value='.$user->ID;
		            if ($user->ID == $userID) echo ' SELECTED';
		            echo '>'.$user->display_name.' ('.$user->user_login.')</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td> 
		<td>
		<select name="current_user_id">
			<option value="all"><?php _e('Alle', 'cp-communitie'); ?></option>
			<?php 
		    $users = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."users ORDER BY display_name");
		    if ($users) {
		        foreach($users as $user) {
		            echo '<option value='.$user->ID;
					if ($user->ID == $current_userID) echo ' SELECTED';
		            echo '>'.$user->display_name.' ('.$user->user_login.')</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td>
		<td>
		<select name="meta">
			<option value="all"><?php _e('Alle', 'cp-communitie'); ?></option>
			<?php 
		    $meta = $wpdb->get_results("SELECT DISTINCT meta FROM ".$wpdb->base_prefix."cpcommunitie_audit ORDER BY meta");
		    if ($meta) {
		        foreach($meta as $m) {
		            echo '<option value='.$m->meta;
		            if ($m->meta == $metafield) echo ' SELECTED';
		            echo '>'.$m->meta.'</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td> 
		</tr>
		<tr><td colspan="8"><hr /></td></tr>
		<tr style="font-weight:bold">
		<td><?php _e('Start', 'cp-communitie'); ?></td>
		<td><?php _e('Ende', 'cp-communitie'); ?></td>
		<td><?php _e('Sortieren nach', 'cp-communitie'); ?></td>
		<td></td>
		<td><?php _e('Zählen', 'cp-communitie'); ?></td>
		</tr>		
		<tr>
		<td>
			<input type="text" name="start" style="width:100px" value="<?php echo $start_date; ?>" />
		</td> 
		<td>
			<input type="text" name="end" style="width:100px" value="<?php echo $end_date; ?>" />
		</td> 
		<td>
		<select name="orderby">
			<option value="blog_id" <?php if ($orderby == 'blog') echo ' SELECTED'; ?>><?php _e('Blog', 'cp-communitie'); ?></option>
			<option value="action" <?php if ($orderby == 'action') echo ' SELECTED'; ?>><?php _e('Aktion', 'cp-communitie'); ?></option>
			<option value="user_id" <?php if ($orderby == 'user_id') echo ' SELECTED'; ?>><?php _e('Benutzer', 'cp-communitie'); ?></option>
			<option value="current_user_id" <?php if ($orderby == 'current_user_id') echo ' SELECTED'; ?>><?php _e('Aktueller Benutzer', 'cp-communitie'); ?></option>
			<option value="meta" <?php if ($orderby == 'meta') echo ' SELECTED'; ?>><?php _e('Meta', 'cp-communitie'); ?></option>
			<option value="timestamp" <?php if ($orderby == 'timestamp') echo ' SELECTED'; ?>><?php _e('Datum', 'cp-communitie'); ?></option>
		</select>
		</td>
		<td>
		<select name="asc">
			<option value=""><?php _e('Aufsteigend', 'cp-communitie'); ?></option>
			<option value="DESC" <?php if ($asc == 'DESC') echo ' SELECTED'; ?>><?php _e('Absteigend', 'cp-communitie'); ?></option>
		</select>
		</td> 
		<td>
			<table><tr><td>
				<input type="text" name="count" style="float: left;width:35px;margin-right:5px;" value="<?php echo $count; ?>" />
				<input type="submit" style="float:left; margin-right:15px;" class="button-primary" value="<?php _e('Filter', 'cp-communitie'); ?>" />
				</form>
			</td><td>
				<form method="post" action="">
					<input type="hidden" name="cpcommunitie_update" value="__cpc__plugin_audit_clear">
					<input type="submit" style="float:left" class="__cpc__are_you_sure button-primary" value="<?php _e('Audit-Log löschen', 'cp-communitie'); ?>" />
				</form>
			</td></tr></table>
		</td> 
		</tr> 
		
	</table>

	<table class="widefat" style="margin-top:30px">
		<thead>
		<tr>
		<th><?php _e('Typ', 'cp-communitie'); ?></th>
		<th><?php _e('Blog', 'cp-communitie'); ?></th>
		<th><?php _e('Aktion', 'cp-communitie'); ?></th>
		<th><?php _e('Benutzer', 'cp-communitie'); ?></th>
		<th><?php _e('Aktueller Benutzer', 'cp-communitie'); ?></th>
		<th><?php _e('Meta', 'cp-communitie'); ?></th>
		<th><?php _e('Wert', 'cp-communitie'); ?></th>
		<th><?php _e('Zeitstempel', 'cp-communitie'); ?></th>
		</tr> 
		</thead>
				
		<?php
		$sql = "
		SELECT a.*, u1.display_name, u2.display_name as display_name2
		FROM ".$wpdb->base_prefix."cpcommunitie_audit a
		LEFT JOIN ".$wpdb->base_prefix."users u1 ON a.user_id = u1.ID
		LEFT JOIN ".$wpdb->base_prefix."users u2 ON a.current_user_id = u2.ID WHERE ";
		if ($type != 'all') 			$sql .= " a.type = '".$type."' AND";
		if ($blogID != 'all') 			$sql .= " a.blog_id = ".$blogID." AND";
		if ($action != 'all') 			$sql .= " a.action = '".$action."' AND";
		if ($userID != 'all') 			$sql .= " a.user_id = ".$userID." AND";
		if ($current_userID != 'all') 	$sql .= " a.current_user_id = ".$current_userID." AND";
		if ($metafield != 'all') 		$sql .= " a.meta = '".$metafield."' AND";
		$sql .= " (timestamp >= '".$start_date." 00:00:00' AND timestamp <= '".$end_date." 23:59:59')";
		$sql .= " ORDER BY ".$orderby;
		if ($asc) $sql .= " DESC";
		$sql .= " LIMIT 0,".$count;
				
		$results = $wpdb->get_results($sql);
		if ($results) {
			foreach ($results as $r) {
				echo '<tr>';
					echo '<td>'.$r->type.'</td>';
					echo '<td>'.$r->blog_id.'</td>';
					echo '<td>'.$r->action.'</td>';
					echo '<td>'.$r->display_name.'</td>';
					echo '<td>'.$r->display_name2.'</td>';
					echo '<td>'.$r->meta.'</td>';
					echo '<td>'.$r->value.'</td>';
					echo '<td>'.$r->timestamp.'</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="8">'.__('Keine Ergebnisse', 'cp-communitie').'</td></tr>';
		}
		
		?>
												
	</table>
	
	<?php
			
	__cpc__show_manage_tabs_header_end();

	echo '</div>';					  
	
}

function __cpc__plugin_thesaurus() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s-Verwaltung', 'cp-communitie'), CPC_WL).'</h2><br />';
	__cpc__show_manage_tabs_header('thesaurus');

	global $wpdb;

		// See if the user has posted general settings
	if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__plugin_theasurus' ) {

		update_option(CPC_OPTIONS_PREFIX.'_alt_friend', stripslashes($_POST[ 'alt_friend' ]));
		update_option(CPC_OPTIONS_PREFIX.'_alt_friends', stripslashes($_POST[ 'alt_friends' ]));
		update_option(CPC_OPTIONS_PREFIX.'_alt_everyone', stripslashes($_POST[ 'alt_everyone' ]));
			
		echo "<div class='updated slideaway'>";
		
		// Put an settings updated message on the screen
		echo "<p>".__('Gespeichert', 'cp-communitie').".</p></div>";
		
	}

	echo '<p>'.sprintf(__('Gib Alternativen für Folgendes ein, die zu Deiner Webseite passen. Ersetze beispielsweise Freund/Freunde durch Kollege/Kollegen. Möglicherweise möchtest Du auch die <a href="%s">Elemente im Profilmenü</a> ändern.', 'cp-communitie'), esc_url( admin_url('admin.php?page=cpcommunitie_profile') )).'</p>';

?>
	<form method="post" action=""> 
	<input type="hidden" name="cpcommunitie_update" value="__cpc__plugin_theasurus">

	<table> 	

		<tr style="font-weight:bold"> 
		<td width="150"><?php _e('Label', 'cp-communitie'); ?></td> 
		<td width="200"><?php _e('Singular', 'cp-communitie'); ?></td>
		<td><?php _e('Plural', 'cp-communitie'); ?></td>
		</tr> 
												
		<tr> 
		<td><label for="alt_friend"><?php echo __('Freunde', 'cp-communitie'); ?></label></td> 
		<td><input name="alt_friend" type="text" id="alt_friend" style="width:100px" value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_alt_friend')); ?>" class="regular-text" /></td> 
		<td><input name="alt_friends" type="text" id="alt_friends" style="width:100px" value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_alt_friends')); ?>" class="regular-text" /></td> 
		</tr> 
												
		<tr> 
		<td><label for="alt_everyone"><?php echo __('Jeder', 'cp-communitie'); ?></label></td> 
		<td><input name="alt_everyone" type="text" id="alt_everyone" style="width:100px" value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_alt_everyone')); ?>" class="regular-text" /></td> 
		<td>&nbsp;</td> 
		</tr> 
												
	</table>
	 
	<p class="submit" style="margin-left:6px"> 
	<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
	</p> 
	
	<?php
	
	echo '</form>';
		
	__cpc__show_manage_tabs_header_end();

	echo '</div>';					  
	
}

function __cpc__plugin_advertising() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Werbung', 'cp-communitie'), CPC_WL).'</h2><br />';
	__cpc__show_manage_tabs_header('advertising');

	global $wpdb;


	// See if the user has posted advertising settings
	if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__plugin_advertising' ) {

		update_option(CPC_OPTIONS_PREFIX.'_ad_forum_topic_start', stripslashes($_POST[ 'ad_forum_topic_start' ]));
		update_option(CPC_OPTIONS_PREFIX.'_ad_forum_categories', stripslashes($_POST[ 'ad_forum_categories' ]));
		update_option(CPC_OPTIONS_PREFIX.'_ad_forum_in_categories', stripslashes($_POST[ 'ad_forum_in_categories' ]));
			
		echo "<div class='updated slideaway'>";
		
		// Put an settings updated message on the screen
		echo "<p>".__('Gespeichert', 'cp-communitie').".</p></div>";
		
	}

	echo '<p>'.__('Poste unten Werbecode, zum Beispiel von Google Adsense. Du kannst auch HTML einfügen (vielleicht für das Layout).', 'cp-communitie').'</p>';
	echo '<p>'.__('Bitte beachte, dass Google maximal drei Standard-Anzeigenblöcke pro Webseite festlegt.', 'cp-communitie').'</p>';

?>
	

	<form method="post" action=""> 
	<input type="hidden" name="cpcommunitie_update" value="__cpc__plugin_advertising">

	<h2><?php _e('Forum', 'cp-communitie'); ?></h2>

	<?php echo __('Innerhalb des Themas, unter dem anfänglichen Startbeitrag, vor den Antworten.', 'cp-communitie'); ?><br />
	<textarea name="ad_forum_topic_start" type="text" id="ad_forum_topic_start" style="width:600px; height:200px;"><?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_ad_forum_topic_start')); ?></textarea><br />

	<?php echo __('Auf der Liste der Forenkategorien oder Liste der Themen innerhalb einer Kategorie.', 'cp-communitie'); ?><br />
	<?php echo sprintf(__('Füge [top_advert] zur <a href="%s">Forum-Header-Vorlage</a> hinzu, wahrscheinlich nach der untersten Zeile, die bereits vorhanden ist.', 'cp-communitie'), esc_url( admin_url('admin.php?page=cpcommunitie_templates')) ); ?><br />
	<textarea name="ad_forum_categories" type="text" id="ad_forum_categories" style="width:600px; height:200px;"><?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_ad_forum_categories')); ?></textarea><br />
												
	<?php echo __('Innerhalb der Liste der Forenkategorien oder Liste der Themen innerhalb einer Kategorie (nach dem dritten Punkt).', 'cp-communitie'); ?><br />
	<textarea name="ad_forum_in_categories" type="text" id="ad_forum_in_categories" style="width:600px; height:200px;"><?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_ad_forum_in_categories')); ?></textarea><br />
												
	 
	<p class="submit" style="margin-left:6px"> 
	<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
	</p> 
	
	<?php
	
	echo '</form>';
		
	__cpc__show_manage_tabs_header_end();

	echo '</div>';					  
	
}

function __cpc__plugin_settings() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s-Verwaltung', 'cp-communitie'), CPC_WL).'</h2><br />';
	__cpc__show_manage_tabs_header('settings');

	global $wpdb;

		// See if the user has posted general settings
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__plugin_settings' ) {

			update_option(CPC_OPTIONS_PREFIX.'_footer', $_POST[ 'email_footer' ]);
			update_option(CPC_OPTIONS_PREFIX.'_from_email', $_POST[ 'from_email' ]);
			update_option(CPC_OPTIONS_PREFIX.'_jquery', isset($_POST[ 'jquery' ]) ? $_POST[ 'jquery' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_jqueryui', isset($_POST[ 'jqueryui' ]) ? $_POST[ 'jqueryui' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_tinymce', isset($_POST[ 'tinymce' ]) ? $_POST[ 'tinymce' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_jwplayer', isset($_POST[ 'jwplayer' ]) ? $_POST[ 'jwplayer' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_emoticons', isset($_POST[ 'emoticons' ]) ? $_POST[ 'emoticons' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_wp_width', str_replace('%', 'pc', ($_POST[ 'wp_width' ])));
			update_option(CPC_OPTIONS_PREFIX.'_wp_alignment', $_POST[ 'wp_alignment' ]);
			update_option(CPC_OPTIONS_PREFIX.'_img_db', isset($_POST[ 'img_db' ]) ? $_POST[ 'img_db' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_img_path', str_replace("\\\\", "\\", $_POST[ 'img_path' ]));
			update_option(CPC_OPTIONS_PREFIX.'_img_url', $_POST[ 'img_url' ]);
			update_option(CPC_OPTIONS_PREFIX.'_img_crop', isset($_POST[ 'img_crop' ]) ? $_POST[ 'img_crop' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_show_buttons', isset($_POST[ 'show_buttons' ]) ? $_POST[ 'show_buttons' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_striptags', isset($_POST[ 'striptags' ]) ? $_POST[ 'striptags' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_image_ext', strtolower($_POST[ 'image_ext' ]));
			update_option(CPC_OPTIONS_PREFIX.'_video_ext', strtolower($_POST[ 'video_ext' ]));
			update_option(CPC_OPTIONS_PREFIX.'_doc_ext', strtolower($_POST[ 'doc_ext' ]));
			update_option(CPC_OPTIONS_PREFIX.'_elastic', isset($_POST[ 'elastic' ]) ? $_POST[ 'elastic' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_force_utf8', isset($_POST[ 'force_utf8' ]) ? $_POST[ 'force_utf8' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_images', $_POST[ 'images' ]);
			update_option(CPC_OPTIONS_PREFIX.'_cpc_lite', isset($_POST[ 'cpc_lite' ]) ? $_POST[ 'cpc_lite' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_cpc_time_out', $_POST[ 'cpc_time_out' ] != '' ? $_POST[ 'cpc_time_out' ] : 0);
			update_option(CPC_OPTIONS_PREFIX.'_cpc_js_file', $_POST[ 'cpc_js_file' ]);
			update_option(CPC_OPTIONS_PREFIX.'_cpc_css_file', $_POST[ 'cpc_css_file' ]);
			update_option(CPC_OPTIONS_PREFIX.'_allow_reports', isset($_POST[ 'allow_reports' ]) ? $_POST[ 'allow_reports' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_ajax_widgets', isset($_POST[ 'cpc_ajax_widgets' ]) ? $_POST[ 'cpc_ajax_widgets' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_jscharts', isset($_POST[ 'jscharts' ]) ? $_POST[ 'jscharts' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_subject_mail_new', $_POST[ 'subject_mail_new' ]);
			update_option(CPC_OPTIONS_PREFIX.'_subject_forum_new', $_POST[ 'subject_forum_new' ]);
			update_option(CPC_OPTIONS_PREFIX.'_subject_forum_reply', $_POST[ 'subject_forum_reply' ]);
			update_option(CPC_OPTIONS_PREFIX.'_long_menu', isset($_POST[ 'long_menu' ]) ? $_POST[ 'long_menu' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_debug_mode', isset($_POST[ 'debug_mode' ]) ? $_POST[ 'debug_mode' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_always_load', isset($_POST[ 'always_load' ]) ? $_POST[ 'always_load' ] : '');			
			update_option(CPC_OPTIONS_PREFIX.'_audit', isset($_POST[ 'audit' ]) ? $_POST[ 'audit' ] : '');			
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_media_manager', isset($_POST[ 'use_wysiwyg_media_manager' ]) ? $_POST[ 'use_wysiwyg_media_manager' ] : '');			
			update_option(CPC_OPTIONS_PREFIX.'_basic_upload', isset($_POST[ 'basic_upload' ]) ? $_POST[ 'basic_upload' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_cpc_login_url', isset($_POST[ 'cpc_login_url' ]) ? $_POST[ 'cpc_login_url' ] : '');

			echo "<div class='updated slideaway'>";
			
			// Making content path if it doesn't exist
			$img_db = isset($_POST[ 'img_db' ]) ? $_POST[ 'img_db' ] : '';
			if ($img_db != 'on') {
				
				if (!file_exists($_POST[ 'img_path' ])) {
					if (!mkdir($_POST[ 'img_path' ], 0777, true)) {
						echo '<p>Fehler beim Erstellen von '.$_POST[ 'img_path' ].'...</p>';
					} else {
						echo '<p>Erstellt '.$_POST[ 'img_path' ].'.</p>';
					}
				}
			
			}
			
			// Put an settings updated message on the screen
			echo "<p>".__('Gespeichert', 'cp-communitie').".</p></div>";
			
		}

		$readonly = (get_option(CPC_OPTIONS_PREFIX.'_activation_code') == 'vip') ? true : false;
		
		?>
									
		<form method="post" action=""> 
			<input type="hidden" name="cpcommunitie_update" value="__cpc__plugin_settings">

			<table class="form-table __cpc__admin_table"> 

			<?php if ($readonly) { ?>
				<!-- Values that can't be edited when running with readonly flag -->
				<input type="hidden" name="cpc_login_url" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_cpc_login_url'); ?>" />			
				<input type="hidden" name="cpc_time_out" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_cpc_time_out'); ?>" />			
				<input type="hidden" name="cpc_js_file" value="cpc.min.js" />
				<input type="hidden" name="cpc_css_file" value="cpc.min.css" />
				<input type="hidden" name="cpc_ajax_widgets" value="" />
				<input type="hidden" name="cpc_lite" value="" />
				<input type="hidden" name="img_db" value="" />
				<input name="img_path" type="hidden" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_img_path'); ?>" /> 
				<input name="img_url" type="hidden" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_img_url'); ?>" /> 
				<input name="images" type="hidden" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_images'); ?>" /> 
				<input name="img_crop" type="hidden" value="on" /> 
				<input name="image_ext" type="hidden" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_image_ext'); ?>" /> 
				<input name="video_ext" type="hidden" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_video_ext'); ?>" /> 
				<input name="doc_ext" type="hidden" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_doc_ext'); ?>" /> 
				<input name="always_load" type="hidden" value="" /> 
				<input name="long_menu" type="hidden" value="on" /> 				
				<input name="jquery" type="hidden" value="on" /> 				
				<input name="jqueryui" type="hidden" value="on" /> 				
				<input name="tinymce" type="hidden" value="on" /> 				
				<input name="jscharts" type="hidden" value="on" /> 				
				<input name="emoticons" type="hidden" value="on" /> 				
				<input name="elastic" type="hidden" value="on" /> 				
				<input name="force_utf8" type="hidden" value="" /> 				
				<input name="audit" type="hidden" value="" /> 				
				<input name="debug_mode" type="hidden" value="" /> 
				
			<?php } ?>
			

			<?php if (!$readonly) { ?>
				<tr valign="top"> 
				<td scope="row"><label for="cpc_time_out"><?php echo __('Skript-Zeitüberschreitung', 'cp-communitie'); ?></label></td>
				<td><input name="cpc_time_out" type="text" id="cpc_time_out" style="width:50px" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_cpc_time_out'); ?>"/> 
				<span class="description"><?php echo __('Maximaler PHP-Skript-Zeitüberschreitungswert, auf 0 setzen, um diese Einstellung zu deaktivieren.', 'cp-communitie'); ?></span></td> 
				</tr> 
			<?php } ?>

			<?php if (!$readonly) { ?>
				<tr valign="top">
				<td scope="row"><label for="cpc_js_file"><?php echo sprintf(__('%s JS-Dateien', 'cp-communitie'), CPC_WL_SHORT); ?></label></td> 
				<td>
				<select name="cpc_js_file">
					<option value='cpc.min.js'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_js_file') == 'cpc.min.js') { echo ' SELECTED'; } ?>><?php echo __('Minimiert', 'cp-communitie'); ?></option>
					<option value='cpc.js'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_js_file') == 'cpc.js') { echo ' SELECTED'; } ?>><?php echo __('Normal', 'cp-communitie'); ?></option>
				</select> 
				<span class="description"><?php echo __('Minimierte laden schneller, normal ist nützlich zum Debuggen', 'cp-communitie'); ?></span></td> 
				</tr> 		
				
				<tr valign="top">
				<td scope="row"><label for="cpc_css_file"><?php echo sprintf(__('%s CSS-Dateien', 'cp-communitie'), CPC_WL_SHORT); ?></label></td> 
				<td>
				<select name="cpc_css_file">
					<option value='cpc.min.css'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_css_file') == 'cpc.min.css') { echo ' SELECTED'; } ?>><?php echo __('Minimiert', 'cp-communitie'); ?></option>
					<option value='cpc.css'<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_css_file') == 'cpc.css') { echo ' SELECTED'; } ?>><?php echo __('Normal', 'cp-communitie'); ?></option>
				</select> 
				<span class="description"><?php echo __('Minimierte laden schneller, normal ist nützlich zum Debuggen', 'cp-communitie'); ?></span></td> 
				</tr> 		
			<?php } ?>
				

			<?php if (!$readonly) { ?>
				<tr valign="top"> 
				<td scope="row" style="width:150px;"><label for="cpc_ajax_widgets"><?php echo __('Widgets AJAX-Modus', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="cpc_ajax_widgets" id="cpc_ajax_widgets" <?php if (get_option(CPC_OPTIONS_PREFIX.'_ajax_widgets') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo sprintf(__("Verwende AJAX, um %s-Widgets zu laden (oder mit Seite zu laden).", 'cp-communitie'), CPC_WL_SHORT); ?></span></td> 
				</tr> 
	
				<tr valign="top"> 
				<td scope="row" style="width:150px;"><label for="cpc_lite"><?php echo __('Aktiviere den LITE-Modus', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="cpc_lite" id="cpc_lite" <?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_lite') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __("Empfohlen für Shared Hosting oder wenn die Serverlast ein Problem darstellt.", 'cp-communitie'); ?></span></td> 
				</tr> 
	
				<?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_lite') == "on") { ?>
					
					<tr valign="top"></tr> 
					<td></td><td style="border:1px dotted #999; background-color: #fff;">
						<strong><?php echo sprintf(__('%s LITE-Modus', 'cp-communitie'), CPC_WL); ?></strong>
						<p>
						<?php echo sprintf(__('Du führst %s im LITE-Modus aus, der die Serverlast reduziert, aber bestimmte Funktionen der %s-Plug-ins deaktiviert/reduziert und Vorrang vor allen anderen von Dir vorgenommenen Einstellungen hat.', 'cp-communitie'), CPC_WL, CPC_WL).' '; ?>
						<?php echo __('Wenn Du zusätzliche Plugins aktivierst, kehre zu dieser Seite zurück, um unten eine aktualisierte Liste anzuzeigen.', 'cp-communitie'); ?>
						</p>
	
						<p><?php echo __('Um die Leistung weiter zu verbessern, wird Folgendes empfohlen:', 'cp-communitie'); ?></p>
						<ul style="list-style-type: circle; margin: 10px 0 20px 30px;">
							<li><?php echo sprintf(__('Minimiere die Gesamtzahl aller verwendeten ClassicPress-Plugins und -Widgets (%s und andere). <a href="plugins.php?plugin_status=active">Deaktivier viele wie möglich!</a>', 'cp-communitie'), CPC_WL); ?></li>
							<?php if (function_exists('__cpc__add_notification_bar')) { ?>
								<li><?php echo __('<a href="plugins.php?plugin_status=active">Panel deaktivieren</a> oder <a href="admin.php?page=cpcommunitie_bar">Polling-Intervalle hoch setzen</a>, zB: mindestens 300 und 20 Sekunden.', 'cp-communitie'); ?></li>
							<?php } ?>
							<?php if (function_exists('__cpc__news_main')) { ?>
								<li><?php echo __('<a href="plugins.php?plugin_status=active">Benachrichtigungen deaktivieren</a> oder <a href="admin.php?page='.CPC_DIR.'/news_admin.php">das Abrufintervall festlegen< /a> hoch, zB: mindestens 120 Sekunden.', 'cp-communitie'); ?></li>
							<?php } ?>
						</ul>
						
						<?php if (function_exists('__cpc__add_notification_bar')) { ?>
							<p><strong><?php echo __('Panel', 'cp-communitie'); ?></strong></p>
							<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
								<li><?php echo __('Chatfenster und der Chatroom sind deaktiviert', 'cp-communitie'); ?></li>
								<li><?php echo __('Die Benachrichtigung über neue E-Mails (usw.) erfordert ein Neuladen der Seite', 'cp-communitie'); ?></li>
							</ul>
						<?php } ?>
						
						<?php if (function_exists('__cpc__news_main')) { ?>
						<p><strong><?php echo __('Benachrichtigungen', 'cp-communitie'); ?></strong></p>
						<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
							<li><?php echo __('Live-Benachrichtigung bei neuen Nachrichten deaktiviert (Neuladen der Seite erforderlich)', 'cp-communitie'); ?></li>
						</ul>
						<?php } ?>
						
						<p><strong><?php echo __('Forum', 'cp-communitie'); ?></strong></p>
						<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
							<li><?php echo __('Themen-, Beitrags- und Antwortzähler werden nicht angezeigt', 'cp-communitie'); ?></li>
							<li><?php echo __('Es werden nur neue Themen angezeigt, nicht die neuesten Antworten', 'cp-communitie'); ?></li>
							<li><?php echo __('Beantwortete Themen werden nicht in der Themenliste angezeigt', 'cp-communitie'); ?></li>
							<li><?php echo __('Vereinfachte Breadcrumbs (Forennavigationslinks)', 'cp-communitie'); ?></li>
							<li><?php echo __('Smilies/Emoticons werden nicht durch Bilder ersetzt', 'cp-communitie'); ?></li>
							<li><?php echo __('Benutzer-@tagging funktioniert nicht', 'cp-communitie'); ?></li>
						</ul>
						
						<p><strong><?php echo __('Mitgliederverzeichnis', 'cp-communitie'); ?></strong></p>
						<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
							<li><?php echo __('Letzter Aktivitätsbeitrag wird nicht angezeigt', 'cp-communitie'); ?></li>
							<li><?php echo __('Schaltflächen Als Freund hinzufügen/E-Mail senden deaktiviert', 'cp-communitie'); ?></li>
						</ul>
						
						<p><strong><?php echo __('Profil', 'cp-communitie'); ?></strong></p>
						<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
							<li><?php echo __('Freunde: Der letzte Aktivitätsbeitrag wird nicht angezeigt', 'cp-communitie'); ?></li>
							<li><?php echo __('Freunde: Neu geschlossene Freundschaften werden nicht angezeigt', 'cp-communitie'); ?></li>
							<li><?php echo __('Forum: Beiträge/Antworten werden nicht angezeigt', 'cp-communitie'); ?></li>
						</ul>
						
					</td>
					</tr> 	
				
				<?php } ?>
									
				<tr valign="top"> 
				<td scope="row"><label for="img_db"><?php echo __('Uploads in Datenbank speichern', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="img_db" id="img_db" <?php if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Standardmäßig deaktiviert, um im Dateisystem zu speichern (empfohlen). Wähle zum Hochladen in die Datenbank', 'cp-communitie').' - '; ?><span style='font-weight:bold; text-decoration: underline'><?php echo __("Wenn Du änderst, müssen Bilder neu geladen werden, sie bleiben in ihrem Speicherzustand.", 'cp-communitie'); ?></span></span></td> 
				</tr> 
				
				<?php if (get_option(CPC_OPTIONS_PREFIX.'_img_db') != "on") { ?>
					
					<tr valign="top" style='border-top: 1px dashed #666; border-right: 1px dashed #666; border-left: 1px dashed #666; '> 
					<td class="highlighted_row" scope="row"><label for="img_path"><?php echo __('Bildverzeichnis', 'cp-communitie'); ?></label></td> 
					<td class="highlighted_row"><input name="img_path" type="text" id="img_path"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_img_path'); ?>" class="regular-text" /> 
					<span class="description">
					<?php echo __('Bild-Upload-Verzeichnis, zB:', 'cp-communitie').' '.WP_CONTENT_DIR.'/cpc-content'; ?>
					<input type="button" onclick="document.getElementById('img_path').value='<?php echo WP_CONTENT_DIR.'/cpc-content'; ?>'" value="<?php _e('Empfehlen', 'cp-communitie'); ?>" class="button" /></td> 
					</tr> 					
					
					<tr valign="top" style='border-right: 1px dashed #666; border-left: 1px dashed #666; '> 
					<td class="highlighted_row" scope="row"><label for="img_url"><?php echo __('Bild-URL', 'cp-communitie'); ?></label></td> 
					<td class="highlighted_row"><input name="img_url" type="text" id="img_url"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_img_url'); ?>" class="regular-text" /> 
					<?php $url = WP_CONTENT_URL.'/cpc-content'; $url = str_replace(__cpc__siteURL(), '', $url); ?>
					<span class="description"><?php echo __('URL zum Bildverzeichnis, ohne http:// oder Ihren Domainnamen, z. B.: ', 'cp-communitie').' <a href="'.$url.'">'.$url.'</a>'; ?>
					<input type="button" onclick="document.getElementById('img_url').value='<?php echo $url; ?>'" value="<?php _e('Empfehlen', 'cp-communitie'); ?>" class="button" /></td> 
					</tr> 					
	
					<tr valign="top" style='border-right: 1px dashed #666; border-bottom: 1px dashed #666; border-left: 1px dashed #666; '> 
					<td class="highlighted_row" colspan=2>
						<?php $img_tmp = ini_get('upload_tmp_dir'); ?>
						<?php echo __('Zur Information, aus der PHP.INI auf Deinem Server lautet der temporäre PHP-Upload-Ordner:', 'cp-communitie').' '.$img_tmp; ?>
						<?php if ($img_tmp == '') { echo '<strong>'.__("Du musst dies <a href='http://uk.php.net/manual/en/ini.core.php#ini.upload-tmp-dir'>in Deiner php.ini</a>-Datei festlegen", 'cp-communitie').'</strong>'; } ?>
					</td>
					</tr> 	
	
				<?php } else { ?>
	
					<input name="img_path" type="hidden" id="img_path"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_img_path'); ?>" /> 
					<input name="img_url" type="hidden" id="img_url"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_img_url'); ?>" /> 
					
				<?php } ?>
	
				<tr valign="top"> 
				<td scope="row"><label for="images"><?php echo sprintf(__('%s Bilder-URL', 'cp-communitie'), CPC_WL_SHORT); ?></label></td> 
				<td><input name="images" type="text" id="images" class="regular-text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_images'); ?>"/> 
				<span class="description"><?php echo __('Ändere ob Du Deinen eigenen Satz benutzerdefinierter Bilder erstellen möchtest.', 'cp-communitie'); ?></span>
				<input type="button" onclick="document.getElementById('images').value='<?php echo str_replace(__cpc__siteURL(), '', CPC_PLUGIN_URL.'/images'); ?>'" value="<?php _e('Empfehlen', 'cp-communitie'); ?>" class="button" /></td> 
				</tr> 
					
				<tr valign="top"> 
				<td scope="row"><label for="img_crop"><?php echo __('Avatarbilder zuschneiden', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="img_crop" id="img_crop" <?php if (get_option(CPC_OPTIONS_PREFIX.'_img_crop') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __("Zuschneiden hochgeladener Bilder zulassen</span>", 'cp-communitie'); ?></span></td> 
				</tr> 
	
				<tr valign="top"> 
				<td scope="row"><label for="image_ext"><?php echo __('Bilderweiterungen', 'cp-communitie'); ?></label></td> 
				<td><input name="image_ext" type="text" id="image_ext" class="regular-text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_image_ext'); ?>"/> 
				<span class="description"><?php echo __('Eine durch Kommas getrennte Liste zulässiger Dateierweiterungen, leer lassen für keine. *.jpg,*.jpeg,*.png und *.gif unterstützt.', 'cp-communitie'); ?></span></td> 
				</tr> 
	
				<?php if (get_option(CPC_OPTIONS_PREFIX.'_img_db') != "on") { ?>
	
					<tr valign="top"> 
					<td scope="row"><label for="video_ext"><?php echo __('Videoerweiterungen', 'cp-communitie'); ?></label></td> 
					<td><input name="video_ext" type="text" id="video_ext" class="regular-text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_video_ext'); ?>"/> 
					<span class="description"><?php echo sprintf(__('Eine durch Kommas getrennte Liste zulässiger Dateierweiterungen, leer lassen für keine. H.264-Format wird unterstützt, <a %s>siehe hier</a>.', 'cp-communitie'), 'href="http://www.longtailvideo.com/support/jw-player/jw-player-for-flash-v5/12539/supported-video-and-audio-formats" target="_blank"'); ?></span></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="doc_ext"><?php echo __('Dokumenterweiterungen', 'cp-communitie'); ?></label></td> 
					<td><input name="doc_ext" type="text" id="doc_ext" class="regular-text" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_doc_ext'); ?>"/> 
					<span class="description"><?php echo __('Eine durch Kommas getrennte Liste zulässiger Dateierweiterungen, leer lassen für keine. In separatem Fenster angezeigt oder heruntergeladen.', 'cp-communitie'); ?></span></td> 
					</tr> 
					
				<?php } else { ?>
	
					<tr valign="top"> 
					<td scope="row"><label for="video_ext"><?php echo __('Videoerweiterungen', 'cp-communitie'); ?></label></td> 
					<td><input name="video_ext" type="hidden" id="video_ext" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_video_ext'); ?>"/> 
					<span class="description"><?php echo __('Entschuldigung, Videos können nur gespeichert werden, wenn sie im Dateisystem gespeichert werden.', 'cp-communitie'); ?></span></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="doc_ext"><?php echo __('Dokumenterweiterungen', 'cp-communitie'); ?></label></td> 
					<td><input name="doc_ext" type="hidden" id="doc_ext" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_doc_ext'); ?>"/> 
					<span class="description"><?php echo __('Entschuldigung, Dokumente können nur beim Speichern im Dateisystem gespeichert werden.', 'cp-communitie'); ?></span></td> 
					</tr> 
	
				<?php } ?>

			<?php } ?>
			
			<tr valign="top"> 
			<td scope="row"><label for="email_footer"><?php echo __('E-Mail Benachrichtigungen', 'cp-communitie'); ?></label></td> 
			<td><input name="email_footer" type="text" id="email_footer"  value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_footer')); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('Fußzeile an Benachrichtigungs-E-Mails angehängt', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="from_email">&nbsp;</label></td> 
			<td><input name="from_email" type="text" id="from_email"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_from_email'); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('E-Mail-Adresse, die für E-Mail-Benachrichtigungen verwendet wird', 'cp-communitie'); ?></span></td> 
			</tr> 
										
			<tr valign="top"> 
			<td scope="row"><label for="subject_mail_new"><?php echo __('Mail Betreffzeile', 'cp-communitie'); ?></label></td> 
			<td><input name="subject_mail_new" type="text" id="subject_mail_new"  value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_subject_mail_new')); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('Neue E-Mail-Nachricht, [subject] wird durch den Betreff der Nachricht ersetzt', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="subject_forum_new">&nbsp;</label></td> 
			<td><input name="subject_forum_new" type="text" id="subject_forum_new"  value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_subject_forum_new')); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('Neues Forumsthema, [topic] wird durch das Thema Betreff ersetzt', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="subject_forum_reply">&nbsp;</label></td> 
			<td><input name="subject_forum_reply" type="text" id="subject_forum_reply"  value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_subject_forum_reply')); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('Neue Forumsantwort, [topic] wird durch den Betreff des Themas ersetzt', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="wp_width"><?php echo __('Breite', 'cp-communitie'); ?></label></td>
			<td><input name="wp_width" type="text" id="wp_width" style="width:50px" value="<?php echo str_replace('pc', '%', get_option(CPC_OPTIONS_PREFIX.'_wp_width')); ?>"/> 
			<span class="description"><?php echo sprintf(__('Breite aller %s Plugins, zB: 600px oder 100%%', 'cp-communitie'), CPC_WL); ?></span></td> 
			</tr> 

			<tr valign="top">
			<td scope="row"><label for="wp_alignment"><?php echo __('Ausrichtung', 'cp-communitie'); ?></label></td> 
			<td>
			<select name="wp_alignment">
				<option value='Left'<?php if (get_option(CPC_OPTIONS_PREFIX.'_wp_alignment') == 'Left') { echo ' SELECTED'; } ?>><?php echo __('Links', 'cp-communitie'); ?></option>
				<option value='Center'<?php if (get_option(CPC_OPTIONS_PREFIX.'_wp_alignment') == 'Center') { echo ' SELECTED'; } ?>><?php echo __('Zentriert', 'cp-communitie'); ?></option>
				<option value='Right'<?php if (get_option(CPC_OPTIONS_PREFIX.'_wp_alignment') == 'Right') { echo ' SELECTED'; } ?>><?php echo __('Rechts', 'cp-communitie'); ?></option>
			</select> 
			<span class="description"><?php echo sprintf(__('Ausrichtung aller %s-Plugins', 'cp-communitie'), CPC_WL); ?></span></td> 
			</tr> 		

			<tr valign="top"> 
			<td scope="row"><label for="show_buttons"><?php echo __('Schaltflächen auf Aktivitätsseiten', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="show_buttons" id="show_buttons" <?php if (get_option(CPC_OPTIONS_PREFIX.'_show_buttons') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __("Durch Drücken der Eingabetaste wird ein Beitrag/Kommentar gesendet. Wähle diese Option, um auch Schaltflächen zum Senden anzuzeigen.</span>", 'cp-communitie'); ?></span></td> 
			</tr>
			<?php 
			if (get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg') == 'on') {
				?>
				<tr valign="top"> 
				<td scope="row"><label for="use_wysiwyg_media_manager"><?php echo __('Verwende den Medienmanager', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="use_wysiwyg_media_manager" id="use_wysiwyg_media_manager" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_media_manager') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __("Wechselt von der Eingabe einer Bild-URL (beim Einfügen eines Bildes) zu einer Liste von Bildern im ClassicPress-Medienmanager.</span>", 'cp-communitie'); ?></span></td> 
				</tr>
			<?php } ?>

			<tr valign="top"> 
			<td scope="row"><label for="striptags"><?php echo __('Tags entfernen', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="striptags" id="striptags" <?php if (get_option(CPC_OPTIONS_PREFIX.'_striptags') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php 
			echo __("HTML/Script-Tags vollständig entfernen. Wenn nicht markiert &lt; und &gt; wird ersetzt durch &amp;lt; und &amp;gt;.", 'cp-communitie'); 
			?></span></td> 
			</tr>
								
			<tr valign="top"> 
			<td scope="row"><label for="allow_reports"><?php echo __('Berichte zulassen', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="allow_reports" id="allow_reports" <?php if (get_option(CPC_OPTIONS_PREFIX.'_allow_reports') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __("Zeigt ein Warnsymbol an, um Inhalte an den Webseiten-Administrator zu melden.", 'cp-communitie'); ?></span></td> 
			</tr>

			<tr valign="top"> 
			<td scope="row"><label for="basic_upload"><?php echo __('Einfacher Datei-Upload', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="basic_upload" id="basic_upload" <?php if (get_option(CPC_OPTIONS_PREFIX.'_basic_upload') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __("Verwende das einfache Hochladen von HTML-Dateien, um Konflikte mit Designs und Plugins zu vermeiden.", 'cp-communitie'); ?><br /><?php echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __("Avatare können nicht zugeschnitten werden und einige Upload-Funktionen sind eingeschränkt.", 'cp-communitie'); ?><br /><?php echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __("Kann nur verwendet werden, wenn Bilder im Dateisystem gespeichert werden.", 'cp-communitie'); ?></span></td> 
			</tr>
			
			<?php if (!$readonly) { ?>

				<tr valign="top"> 
				<td scope="row"><label for="cpc_login_url"><?php echo __('URL der Anmeldeseite', 'cp-communitie'); ?></label></td> 
				<td><input name="cpc_login_url" type="text" id="cpc_login_url"  value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_cpc_login_url')); ?>" class="regular-text" /> 
				<span class="description"><?php echo __('Link zur Anmeldeseite überschreiben. [url] wird durch die URL der aktuellen Seite ersetzt.', 'cp-communitie'); ?></span></td> 
				</tr> 

				<tr valign="top"> 
				<td scope="row"><label for="always_load"><?php echo sprintf(__('Lade %s auf jeder Seite', 'cp-communitie'), CPC_WL_SHORT); ?></label></td>
				<td>
				<input type="checkbox" name="always_load" id="always_load" <?php if (get_option(CPC_OPTIONS_PREFIX.'_always_load') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo sprintf(__("Lädt immer %s Komponenten oder versucht eine Überprüfung ob Bedarf besteht.", 'cp-communitie'), CPC_WL); ?></span></td> 
				</tr>

				<tr valign="top"> 
				<td scope="row"><label for="long_menu"><?php echo __('Admin-Tabs', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="long_menu" id="cpcommunitie_long_menu" <?php if (get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo sprintf(__("%s Admin-Menüpunkte reduzieren und als Tabs anzeigen.", 'cp-communitie'), CPC_WL); ?></span></td> 
				</tr>
			<?php } ?>				

			<?php				
			// Hook to add items to the plugin settings page, just above debug options
			do_action ( '__cpc__plugin_settings_mail_title_hook' );
			?>					
			
			<?php				
			// Hook to add items to the plugin settings page
			echo apply_filters( '__cpc__plugin_settings_hook', "" );

			if (!$readonly) { ?>
			
				<tr valign="top"> 
				<td colspan="2"><?php echo '<h2>'.__('Fehlerbehebung', 'cp-communitie').'</h2>'; echo __('Das Folgende kann Konflikte mit anderen ClassicPress-Plugins usw. lösen.', 'cp-communitie'); ?>:</td>
				</tr> 
	
				<tr valign="top"> 
				<td scope="row"><label for="jquery"><?php echo __('Lade jQuery', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="jquery" id="jquery" <?php if (get_option(CPC_OPTIONS_PREFIX.'_jquery') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Lade jQuery auf Nicht-Admin-Seiten, deaktiviere es, wenn es Probleme verursacht', 'cp-communitie'); ?></span></td> 
				</tr> 
	
				<tr valign="top"> 
				<td scope="row"><label for="jqueryui"><?php echo __('Lade jQuery UI', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="jqueryui" id="jqueryui" <?php if (get_option(CPC_OPTIONS_PREFIX.'_jqueryui') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Lade die jQuery-Benutzeroberfläche auf Nicht-Admin-Seiten, deaktiviere sie, wenn sie Probleme verursacht', 'cp-communitie'); ?></span></td> 
				</tr> 

				<tr valign="top"> 
				<td scope="row"><label for="tinymce"><?php echo __('Nicht TinyMCE', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="tinymce" id="tinymce" <?php if (get_option(CPC_OPTIONS_PREFIX.'_tinymce') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Lade TinyMCE NICHT auf Nicht-Admin-Seiten, deaktviere es, wenn es Probleme verursacht', 'cp-communitie'); ?></span></td> 
				</tr> 

				<tr valign="top"> 
				<td scope="row"><label for="jscharts"><?php echo __('Lade JScharts/Jcrop', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="jscharts" id="jscharts" <?php if (get_option(CPC_OPTIONS_PREFIX.'_jscharts') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Lade JSCharts und Jcrop auf Nicht-Admin-Seiten, deaktiviere sie, wenn sie Probleme verursachen', 'cp-communitie'); ?></span></td> 
				</tr>					
			
				<tr valign="top"> 
				<td scope="row"><label for="jwplayer"><?php echo __('Lade JW Player', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="jwplayer" id="jwplayer" <?php if (get_option(CPC_OPTIONS_PREFIX.'_jwplayer') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Lade den JW Player für das Hochladen von Forenvideos, deaktiviere ihn, falls nicht erforderlich', 'cp-communitie'); ?></span></td> 
				</tr> 
			
				<tr valign="top"> 
				<td scope="row"><label for="emoticons"><?php echo __('Smilies/Emoticons', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="emoticons" id="emoticons" <?php if (get_option(CPC_OPTIONS_PREFIX.'_emoticons') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Smileys/Emoticons automatisch durch grafische Bilder ersetzen', 'cp-communitie'); ?></span></td> 
				</tr> 		
														
				<tr valign="top"> 
				<td scope="row"><label for="elastic"><?php echo __('Elastische Textfelder', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="elastic" id="elastic" <?php if (get_option(CPC_OPTIONS_PREFIX.'_elastic') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Elastische jQuery-Funktion einschließen (Textfelder automatisch erweitern)', 'cp-communitie'); ?></span></td> 
				</tr> 		
	
				<tr valign="top"> 
				<td scope="row"><label for="force_utf8"><?php echo __('UTF8-Decodierung erzwingen', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="force_utf8" id="force_utf8" <?php if (get_option(CPC_OPTIONS_PREFIX.'_force_utf8') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Kann akzentuierte Zeichen lösen, die nicht richtig angezeigt werden', 'cp-communitie'); ?></span></td> 
				</tr> 		
	
				<tr valign="top"> 
				<td colspan="2"><h2><?php echo __('Nur für Entwickler', 'cp-communitie'); ?></h2></td>
				</tr> 
	
				<tr valign="top"> 
				<td scope="row"><label for="audit"><?php echo __('Audit', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="audit" id="cpcommunitie_audit" <?php if (get_option(CPC_OPTIONS_PREFIX.'_audit') == "on") { echo "CHECKED"; } ?>/>
				<?php if (get_option(CPC_OPTIONS_PREFIX.'_audit') == "on") { ?>
					<span class="description"><?php echo sprintf(__("Aktiviere die Überwachung wichtiger Ereignisse (<a href='%s'>analyse</a>).", 'cp-communitie'), esc_url( admin_url('admin.php?page=cpcommunitie_audit') )); ?></span></td> 
				<?php } else { ?>
					<span class="description"><?php echo sprintf(__("Auditing von Schlüsselereignissen einschalten (Ergebnisse dann verfügbar über %s->Manage->Audit).", 'cp-communitie'), CPC_WL); ?></span></td>
				<?php } ?>
				</tr>
	
				<tr valign="top"> 
				<td scope="row"><label for="debug_mode"><?php echo __('Debug-Modus', 'cp-communitie'); ?></label></td>
				<td>
				<input type="checkbox" name="debug_mode" id="debug_mode" <?php if (get_option(CPC_OPTIONS_PREFIX.'_debug_mode') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo sprintf(__("Zeige zusätzliche %s-Informationen auf dem Bildschirm und in Dialogfeldern an.", 'cp-communitie'), CPC_WL); ?></span></td> 
				</tr>
				
			<?php } ?>			
						
			</table>
			 
			<p class="submit" style="margin-left:6px"> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
			</p> 
			
			<?php
		
		echo '</form>';
		
		__cpc__show_manage_tabs_header_end();

	echo '</div>';					  
}

function __cpc__plugin_forum() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';

	__cpc__show_tabs_header('forum');

	global $wpdb;

		// See if the user has posted forum settings
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__plugin_forum' ) {

			update_option(CPC_OPTIONS_PREFIX.'_send_summary', isset($_POST[ 'send_summary' ]) ? $_POST[ 'send_summary' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_include_admin', isset($_POST[ 'include_admin' ]) ? $_POST[ 'include_admin' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_oldest_first', isset($_POST[ 'oldest_first' ]) ? $_POST[ 'oldest_first' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_votes', isset($_POST[ 'use_votes' ]) ? $_POST[ 'use_votes' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_votes_remove', $_POST[ 'use_votes_remove' ] != '' ? $_POST[ 'use_votes_remove' ] : 0);
			update_option(CPC_OPTIONS_PREFIX.'_use_votes_min', $_POST[ 'use_votes_min' ] != '' ? $_POST[ 'use_votes_min' ] : 10);
			update_option(CPC_OPTIONS_PREFIX.'_preview1', $_POST[ 'preview1' ] != '' ? $_POST[ 'preview1' ] : 0);
			update_option(CPC_OPTIONS_PREFIX.'_preview2', $_POST[ 'preview2' ] != '' ? $_POST[ 'preview2' ] : 100);
			update_option(CPC_OPTIONS_PREFIX.'_chatroom_banned', $_POST[ 'chatroom_banned' ]);
			update_option(CPC_OPTIONS_PREFIX.'_closed_word', $_POST[ 'closed_word' ]);
			update_option(CPC_OPTIONS_PREFIX.'_bump_topics', isset($_POST[ 'bump_topics' ]) ? $_POST[ 'bump_topics' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_forum_ajax', isset($_POST[ 'forum_ajax' ]) ? $_POST[ 'forum_ajax' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_forum_login', isset($_POST[ 'forum_login' ]) ? $_POST[ 'forum_login' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_moderation', isset($_POST[ 'moderation' ]) ? $_POST[ 'moderation' ] : '');
			$sharing_permalink = (isset($_POST[ 'sharing_permalink' ])) ? "pl;" : ""; 
			$sharing_facebook = (isset($_POST[ 'sharing_facebook' ])) ? "fb;" : ""; 
			$sharing_twitter = (isset($_POST[ 'sharing_twitter' ])) ? "tw;" : ""; 
			$sharing_myspace = (isset($_POST[ 'sharing_myspace' ])) ? "ms;" : ""; 
			$sharing_bebo = (isset($_POST[ 'sharing_bebo' ])) ? "be;" : ""; 
			$sharing_linkedin = (isset($_POST[ 'sharing_linkedin' ])) ? "li;" : ""; 
			$sharing_email = (isset($_POST[ 'sharing_email' ])) ? "em;" : ""; 
			$sharing = $sharing_permalink.$sharing_facebook.$sharing_twitter.$sharing_myspace.$sharing_bebo.$sharing_linkedin.$sharing_email;
			update_option(CPC_OPTIONS_PREFIX.'_sharing', $sharing);
			$forum_ranks = (isset($_POST[ 'forum_ranks' ])) ? $_POST[ 'forum_ranks' ].';' : '';
			for ( $rank = 1; $rank <= 11; $rank ++) {
				$forum_ranks .= $_POST['rank'.$rank].";";
				$forum_ranks .= $_POST['score'.$rank].";";
			}
			update_option(CPC_OPTIONS_PREFIX.'_forum_ranks', $forum_ranks);
			update_option(CPC_OPTIONS_PREFIX.'_cpcommunitie_forumlatestposts_count', $_POST[ 'cpcommunitie_forumlatestposts_count' ] != '' ? $_POST[ 'cpcommunitie_forumlatestposts_count' ] : 10);
			update_option(CPC_OPTIONS_PREFIX.'_forum_uploads', isset($_POST[ 'forum_uploads' ]) ? $_POST[ 'forum_uploads' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_forum_thumbs', isset($_POST[ 'forum_thumbs' ]) ? $_POST[ 'forum_thumbs' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_forum_thumbs_size', $_POST[ 'forum_thumbs_size' ]);
			update_option(CPC_OPTIONS_PREFIX.'_forum_login_form', isset($_POST[ 'forum_login_form' ]) ? $_POST[ 'forum_login_form' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_forum_info', isset($_POST[ 'forum_info' ]) ? $_POST[ 'forum_info' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_forum_stars', isset($_POST[ 'forum_stars' ]) ? $_POST[ 'forum_stars' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_forum_refresh', isset($_POST[ 'forum_refresh' ]) ? $_POST[ 'forum_refresh' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_answers', isset($_POST[ 'use_answers' ]) ? $_POST[ 'use_answers' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_cpc_default_forum', $_POST[ 'cpc_default_forum' ]);
			update_option(CPC_OPTIONS_PREFIX.'_use_bbcode', isset($_POST[ 'use_bbcode' ]) ? $_POST[ 'use_bbcode' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_bbcode_icons', isset($_POST[ 'use_bbcode_icons' ]) ? $_POST[ 'use_bbcode_icons' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg', isset($_POST[ 'use_wysiwyg' ]) && !isset($_POST[ 'use_bbcode' ]) ? $_POST[ 'use_wysiwyg' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_1', (isset($_POST[ 'use_wysiwyg_1' ]) && $_POST[ 'use_wysiwyg_1' ] != '') ? $_POST[ 'use_wysiwyg_1' ] : 'bold,italic,|,fontselect,fontsizeselect,forecolor,backcolor,|,bullist,numlist,|,link,unlink,|,image,media,|,emotions');
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_2', (isset($_POST[ 'use_wysiwyg_2' ]) && $_POST[ 'use_wysiwyg_2' ] != '') ? $_POST[ 'use_wysiwyg_2' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_3', (isset($_POST[ 'use_wysiwyg_3' ]) && $_POST[ 'use_wysiwyg_3' ] != '') ? $_POST[ 'use_wysiwyg_3' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_4', (isset($_POST[ 'use_wysiwyg_4' ]) && $_POST[ 'use_wysiwyg_4' ] != '') ? $_POST[ 'use_wysiwyg_4' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_css', (isset($_POST[ 'use_wysiwyg_css' ])) ? $_POST[ 'use_wysiwyg_css' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_skin', (isset($_POST[ 'use_wysiwyg_skin' ])) ? $_POST[ 'use_wysiwyg_skin' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_width', $_POST[ 'use_wysiwyg_width' ]);
			update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_height', $_POST[ 'use_wysiwyg_height' ]);
			update_option(CPC_OPTIONS_PREFIX.'_forum_lock', $_POST[ 'forum_lock' ] != '' ? $_POST[ 'forum_lock' ] : 0);
			update_option(CPC_OPTIONS_PREFIX.'_include_context', isset($_POST[ 'include_context' ]) ? $_POST[ 'include_context' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_allow_subscribe_all', isset($_POST[ 'allow_subscribe_all' ]) ? $_POST[ 'allow_subscribe_all' ] : '');
			
			update_option(CPC_OPTIONS_PREFIX.'_alt_subs', isset($_POST[ '_alt_subs' ]) ? $_POST[ '_alt_subs' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_pagination', isset($_POST[ 'pagination' ]) ? $_POST[ 'pagination' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_pagination_size', isset($_POST[ 'pagination_size' ]) ? $_POST[ 'pagination_size' ] : '10');
			update_option(CPC_OPTIONS_PREFIX.'_pagination_location', $_POST[ 'pagination_location' ]);

			update_option(CPC_OPTIONS_PREFIX.'_show_dropdown', isset($_POST[ 'show_dropdown' ]) ? $_POST[ 'show_dropdown' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_topic_count', isset($_POST[ 'topic_count' ]) ? $_POST[ 'topic_count' ] : '');
			
			update_option(CPC_OPTIONS_PREFIX.'_moderation_email_rejected', isset($_POST[ 'moderation_email_rejected' ]) ? $_POST[ 'moderation_email_rejected' ] : '');
			update_option(CPC_OPTIONS_PREFIX.'_moderation_email_accepted', isset($_POST[ 'moderation_email_accepted' ]) ? $_POST[ 'moderation_email_accepted' ] : '');

			update_option(CPC_OPTIONS_PREFIX.'_suppress_forum_notify', isset($_POST[ 'suppress_forum_notify' ]) ? $_POST[ 'suppress_forum_notify' ] : '');

			// Clear forum subscriptions
			if (isset($_POST['clear_forum_subs'])) {
				$wpdb->query("DELETE FROM ".$wpdb->prefix."cpcommunitie_subs");
				echo "<script>alert('Forum subscriptions cleared');</script>";
			}

			// Forum moderators
			if (isset($_POST['moderators'])) {
		   		$range = array_keys($_POST['moderators']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['moderators'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(CPC_OPTIONS_PREFIX.'_moderators', serialize($level));

			// Forum viewers
			if (isset($_POST['viewers'])) {
		   		$range = array_keys($_POST['viewers']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['viewers'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(CPC_OPTIONS_PREFIX.'_viewer', serialize($level));
			
			// Forum editors (new topic)
			if (isset($_POST['editors'])) {
		   		$range = array_keys($_POST['editors']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['editors'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(CPC_OPTIONS_PREFIX.'_forum_editor', serialize($level));

			// Forum replies
			if (isset($_POST['repliers'])) {
		   		$range = array_keys($_POST['repliers']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['repliers'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(CPC_OPTIONS_PREFIX.'_forum_reply', serialize($level));	

			// Forum replies
			if (isset($_POST['commenters'])) {
		   		$range = array_keys($_POST['commenters']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['commenters'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(CPC_OPTIONS_PREFIX.'_forum_reply_comment', serialize($level));	
			
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Gespeichert', 'cp-communitie').".</p></div>";

		}
		
		?>

			<form method="post" action=""> 
			<input type="hidden" name="cpcommunitie_update" value="__cpc__plugin_forum">
				
			<table class="form-table __cpc__admin_table"> 
		
			<tr><td colspan="2">
			<div style="float: right; margin-top:-15px;">
			<?php echo '<a href="admin.php?page=cpcommunitie_categories">'.__('Gehe zur Forenverwaltung', 'cp-communitie').'</a>'; ?>
			</div>
			<h2>Editor</h2></td></tr>

            <tr valign="top"> 
            <td scope="row"><label for="use_wysiwyg_width"><?php echo __('Breite', 'cp-communitie'); ?></label></td>
            <td><span class="description"><?php echo __('Breite des Editors (zB: 300px oder 100%)', 'cp-communitie'); ?></span><br />
            <input name="use_wysiwyg_width" type="text" id="use_wysiwyg_width"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_width'); ?>" />
            </td> 
            </tr> 
            <tr valign="top"> 
            <td scope="row"><label for="use_wysiwyg_height"><?php echo __('Höhe', 'cp-communitie'); ?></label></td>
            <td><span class="description"><?php echo __('Höhe des Editors (zB: 250px)', 'cp-communitie'); ?></span><br />
            <input name="use_wysiwyg_height" type="text" id="use_wysiwyg_height"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_height'); ?>" />
            </td> 
            </tr> 

			<?php if (get_option(CPC_OPTIONS_PREFIX.'__cpc__wysiwyg_activated') || get_option(CPC_OPTIONS_PREFIX.'__cpc__wysiwyg_network_activated')) { ?>

	            <tr valign="top"> 
	            <td scope="row"><label for="use_bbcode"><?php echo __('BB-Code-Symbolleiste', 'cp-communitie'); ?></label></td>
	            <td>
	            <input type="checkbox" name="use_bbcode" id="use_bbcode" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_bbcode') == "on") { echo "CHECKED"; } ?>/>
	            <span class="description">
	            <?php echo __('Verwende die BB-Code-Symbolleiste in den Foren (kann nicht mit dem WYSIWYG-Editor verwendet werden).', 'cp-communitie'); ?><br />
	            </span></td> 
	            </tr> 

				<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_bbcode') == 'on') { ?>
                    <tr valign="top" style='border-bottom: 1px dashed #666;border-right: 1px dashed #666; border-left: 1px dashed #666; border-top: 1px dashed #666;'> 
                    <td class="highlighted_row" scope="row"><label for="use_bbcode_icons"><?php echo __('BB-Code-Symbolleistensymbole', 'cp-communitie').'<br />bold|italic|underline|link|quote|code'; ?></label></td>
                    <td class="highlighted_row"><span class="description">
                    	<?php echo __('Symbole, die in die BB-Code-Symbolleiste aufgenommen werden sollen.', 'cp-communitie'); ?></span><br />
                    <?php if (!get_option(CPC_OPTIONS_PREFIX.'_use_bbcode_icons')) update_option(CPC_OPTIONS_PREFIX.'_use_bbcode_icons', 'bold|italic|underline|link|quote|code'); ?>
                    <input name="use_bbcode_icons" style="width:350px" type="text" id="use_bbcode_icons"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_use_bbcode_icons'); ?>" />
                    </td> 
                    </tr>

                <?php } else {
                    echo '<input type="hidden" name="use_bbcode_icons" id="use_bbcode_icons" value="'.get_option(CPC_OPTIONS_PREFIX.'_use_bbcode_icons').'" />';
                }  
                ?>

                <tr valign="top"> 
                <td scope="row"><label for="use_wysiwyg"><?php echo __('WYSIWYG-Editor', 'cp-communitie'); ?></label></td>
                <td>
                <input type="checkbox" name="use_wysiwyg" id="use_wysiwyg" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg') == "on") { echo "CHECKED"; } ?>/>
                <span class="description">
                <?php echo __('Verwende den TinyMCE WYSIWYG-Editor/die Symbolleiste in den Foren.', 'cp-communitie'); ?><br />
                <?php echo __('NB. Einige Themes verursachen Layoutprobleme mit TinyMCE. Mit TwentyEleven verifiziert und mit vielen anderen getestet, aber', 'cp-communitie'); ?><br />
                <?php echo __('wenn das Layout Deiner Editor-Symbolleiste defekt ist, überprüfe Deine Design-Stylesheets.', 'cp-communitie'); ?>
                </span></td> 
                </tr> 
            	                
				<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg') == 'on') { ?>					
                    <tr valign="top" style='border-right: 1px dashed #666; border-left: 1px dashed #666; border-top: 1px dashed #666;'> 
                    <td scope="row" class="highlighted_row"><label for="include_context"><?php echo __('Kontextmenü', 'cp-communitie'); ?></label></td>
                    <td class="highlighted_row">
                    <input type="checkbox" name="include_context" id="include_context" <?php if (get_option(CPC_OPTIONS_PREFIX.'_include_context') == "on") { echo "CHECKED"; } ?>/>
                    <span class="description"><?php echo __('Rechtsklick-Kontextmenü aktivieren.', 'cp-communitie'); ?></span></td> 
                    </tr> 
                
                    <tr valign="top" style='border-right: 1px dashed #666; border-left: 1px dashed #666;'> 
                    <td class="highlighted_row" scope="row"><label for="use_wysiwyg_1"><?php echo __('Editor-Symbolleisten', 'cp-communitie'); ?><br />
                    <a href="http://www.tinymce.com/wiki.php/Buttons/controls" target="_blank"><?php echo __('See all buttons/controls', 'cp-communitie') ?></a><br />
                    <a href="javascript:void(0);" id="use_wysiwyg_reset"><?php echo __('Zurücksetzen (vollständig)', 'cp-communitie'); ?></a><br />
                    <a href="javascript:void(0);" id="use_wysiwyg_reset_min"><?php echo __('Zurücksetzen (minimal)', 'cp-communitie'); ?></a>
                    </label></td>
                    <td class="highlighted_row">
                        <span class="description"><?php echo __('Symbolleiste Zeile 1', 'cp-communitie'); ?></span><br />
                        <textarea name="use_wysiwyg_1" style="width:350px; height:80px" id="use_wysiwyg_1"><?php echo get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_1'); ?></textarea><br />
                        <span class="description"><?php echo __('Symbolleiste Zeile 2', 'cp-communitie'); ?></span><br />
                        <textarea name="use_wysiwyg_2" style="width:350px; height:80px" id="use_wysiwyg_2"><?php echo get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_2'); ?></textarea><br />
                        <span class="description"><?php echo __('Symbolleiste Zeile 3', 'cp-communitie'); ?></span><br />
                        <textarea name="use_wysiwyg_3" style="width:350px; height:80px" id="use_wysiwyg_3"><?php echo get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_3'); ?></textarea><br />
                        <span class="description"><?php echo __('Symbolleiste Zeile 4', 'cp-communitie'); ?></span><br />
                        <textarea name="use_wysiwyg_4" style="width:350px; height:80px" id="use_wysiwyg_4"><?php echo get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_4'); ?></textarea><br />
                    </td> 
                    </tr> 
                    <tr valign="top" style='border-right: 1px dashed #666; border-left: 1px dashed #666; '> 
                    <td class="highlighted_row" scope="row"><label for="use_wysiwyg_css"><?php echo __('Editor-CSS', 'cp-communitie'); ?></label></td>
                    <td class="highlighted_row"><span class="description"><?php echo __('Pfad für CSS-Datei, zB:', 'cp-communitie').' '.str_replace(__cpc__siteURL(), '', CPC_PLUGIN_URL."/tiny_mce/themes/advanced/skins/cpc.css"); ?></span><br />
                    <span class="description"><?php echo __('Möglicherweise musst Du Deinen Browser-Cache löschen, wenn Du den Inhalt der Datei änderst.', 'cp-communitie'); ?></span><br />
                    <?php if (!get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_css')) update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_css', str_replace(__cpc__siteURL(), '', CPC_PLUGIN_URL."/tiny_mce/themes/advanced/skins/cpc.css")); ?>
                    <input name="use_wysiwyg_css" style="width:350px" type="text" id="use_wysiwyg_css"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_css'); ?>" />
                    </td> 
                    </tr> 
                    <tr valign="top" style='border-bottom: 1px dashed #666; border-right: 1px dashed #666; border-left: 1px dashed #666; '> 
                    <td class="highlighted_row" scope="row"><label for="use_wysiwyg_skin"><?php echo __('Skin-Ordner', 'cp-communitie'); ?></label></td>
                    <td class="highlighted_row"><span class="description"><?php echo sprintf(__('Ordner werden in %s/tiny_mce/themes/advanced/skins gespeichert; zB: cirkuit', 'cp-communitie'), str_replace(get_bloginfo('url'), '', CPC_PLUGIN_URL)); ?></span><br />
                    <?php if (!get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_skin')) update_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_skin', 'cirkuit'); ?>
                    <input name="use_wysiwyg_skin" type="text" id="use_wysiwyg_skin"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_skin'); ?>" />
                    </td> 
                    </tr> 
                <?php } else {
                    echo '<input type="hidden" name="use_wysiwyg_1" id="use_wysiwyg_1" value="'.get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_1').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_2" id="use_wysiwyg_2" value="'.get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_2').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_3" id="use_wysiwyg_3" value="'.get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_3').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_4" id="use_wysiwyg_4" value="'.get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_4').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_css" id="use_wysiwyg_css" value="'.get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_css').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_skin" id="use_wysiwyg_skin" value="'.get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_skin').'" />';
                }  
                					
			} else {

				echo '<tr valign="top"><td colspan="2">';
				echo '<em>NB. Einige Themen verursachen Layoutprobleme mit dem TinyMCE WYSIWYG-Editor. Der Editor wurde mit TwentyTwelve verifiziert und mit vielen anderen getestet, aber wenn das Layout Deiner Editor-Symbolleiste defekt ist, überprüfe Deine Design-Stylesheets.</em>';
				echo '</td></tr>';
				
				echo '<input type="hidden" name="use_wysiwyg" id="use_wysiwyg" value="'.get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg_1').'" />';
			} ?>

			<tr><td colspan="2"><h2>AJAX/Refresh</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="forum_ajax"><?php echo __('Verwende AJAX', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_ajax" id="forum_ajax" <?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_ajax') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Verwende AJAX oder Hyperlinks und Seitenneuladen?', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_refresh"><?php echo __('Forum nach Antwort aktualisieren', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_refresh" id="forum_refresh" <?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_refresh') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Lade die Seite neu, nachdem Du eine Antwort im Forum gepostet hast.', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr><td colspan="2"><h2>Moderation</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="moderation"><?php echo __('Moderation', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="moderation" id="moderation" <?php if (get_option(CPC_OPTIONS_PREFIX.'_moderation') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Neue Themen und Beiträge müssen vom Administrator genehmigt werden', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="moderation_email_rejected"><?php echo __('Ablehnungs-E-Mail', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="moderation_email_rejected" id="moderation_email_rejected" <?php if (get_option(CPC_OPTIONS_PREFIX.'_moderation_email_rejected') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('E-Mail an Benutzer senden, wenn Forumsbeitrag abgelehnt wurde', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="moderation_email_accepted"><?php echo __('Akzeptiert-E-Mail', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="moderation_email_accepted" id="moderation_email_accepted" <?php if (get_option(CPC_OPTIONS_PREFIX.'_moderation_email_accepted') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('E-Mail an den Benutzer senden, wenn der Forumsbeitrag angenommen wurde', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr><td colspan="2"><h2>Anhänge</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="forum_uploads"><?php echo __('Hochladen zulassen', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_uploads" id="forum_uploads" <?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_uploads') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Mitgliedern erlauben, Dateien mit Forumsbeiträgen hochzuladen', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_thumbs"><?php echo __('Inline-Anhänge', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_thumbs" id="forum_thumbs" <?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_thumbs') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Hochgeladene Forenanhänge als Bilder/Videos (keine Links) anzeigen. Dokumente sind immer Links.', 'cp-communitie'); ?></span></td> 
			</tr> 
		
			<tr valign="top"> 
			<td scope="row"><label for="forum_thumbs_size"><?php echo __('Thumbnail Größe', 'cp-communitie'); ?></label></td>
			<td><input name="forum_thumbs_size" style="width:50px" type="text" id="forum_thumbs_size"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_forum_thumbs_size'); ?>" /> 
			<span class="description"><?php echo __('Bei Verwendung von Inline-Anhängen maximale Breite', 'cp-communitie'); ?></span></td> 
			</tr> 
			
			<tr><td colspan="2"><h2>Abstimmung</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="use_votes"><?php echo __('Verwende Abstimmung', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="use_votes" id="use_votes" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_votes') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Erlaube Mitgliedern, über Forumsbeiträge abzustimmen (plus oder minus).', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="use_votes_min"><?php echo __('Abstimmung (Mindestbeiträge)', 'cp-communitie'); ?></label></td>
			<td><input name="use_votes_min" style="width:50px" type="text" id="use_votes_min"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_use_votes_min'); ?>" /> 
			<span class="description"><?php echo __('Wie viele Beiträge muss ein Mitglied gemacht haben, um abstimmen zu können', 'cp-communitie'); ?></span></td> 
			</tr> 
	
	
			<tr valign="top"> 
			<td scope="row"><label for="use_votes_remove"><?php echo __('Abstimmung (Entfernungspunkt)', 'cp-communitie'); ?></label></td>
			<td><input name="use_votes_remove" style="width:50px" type="text" id="use_votes_remove"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_use_votes_remove'); ?>" /> 
			<span class="description"><?php echo __('Wenn ein Forumsbeitrag so viele Stimmen erhält, wird er entfernt. Kann + oder - sein. Zum Ignorieren auf 0 belassen.', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="use_answers"><?php echo __('Abstimmung (Antworten)', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="use_answers" id="use_answers" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_answers') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Ermöglicht Themeneigentümern und -administratoren, eine Antwort als Antwort zu markieren (eine pro Thema)', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr><td colspan="2"><h2>Berechtigungen</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="moderation_roles"><?php echo __('Rollen, die das Forum moderieren können', 'cp-communitie'); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$view_roles = get_option(CPC_OPTIONS_PREFIX.'_moderators');

				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="moderators[]" value="'.$role['name'].'"';
					if ($role['name'] == 'Administrator' || strpos(strtolower($view_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description">
					<?php echo sprintf(__('Die ClassicPress-Rollen, die <a href="%s">Forenbeiträge moderieren</a> können. Administrator ist immer ausgewählt.', 'cp-communitie'), "admin.php?page=cpcommunitie_moderation"); ?><br />
					<?php echo __('Aktivierte Rollen können das Forum auch über das Frontend verwalten.', 'cp-communitie'); ?>
			</span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="viewer"><?php echo __('Forenrollen anzeigen', 'cp-communitie'); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$view_roles = get_option(CPC_OPTIONS_PREFIX.'_viewer');

				echo '<input type="checkbox" name="viewers[]" value="'.__('everyone', 'cp-communitie').'"';
				if (strpos(strtolower($view_roles), strtolower(__('everyone', 'cp-communitie')).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.__('Gäste', 'cp-communitie').' ... <span class="description">'.__('bedeutet, dass jeder das Forum sehen kann, wenn es aktiviert ist', 'cp-communitie').'</span><br />';						
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="viewers[]" value="'.$role['name'].'"';
					if (strpos(strtolower($view_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description"><?php echo __('Die ClassicPress-Rollen, die das gesamte Forum sehen können (Feinabstimmung mit <a href="admin.php?page=cpcommunitie_categories">Forenkategorien</a>)', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="forum_editor"><?php echo __('Forum neue Themenrollen', 'cp-communitie'); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$view_roles = get_option(CPC_OPTIONS_PREFIX.'_forum_editor');

				echo '<input type="checkbox" name="editors[]" value="'.__('everyone', 'cp-communitie').'"';
				if (strpos(strtolower($view_roles), strtolower(__('everyone', 'cp-communitie')).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.__('Mitglied', 'cp-communitie').' ... <span class="description">'.__('bedeutet, dass alle Mitglieder neue Themen posten können, wenn diese Option aktiviert ist', 'cp-communitie').'</span><br />';						
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="editors[]" value="'.$role['name'].'"';
					if (strpos(strtolower($view_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description"><?php echo __('Die ClassicPress-Rollen, die ein neues Thema im Forum veröffentlichen können', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_reply"><?php echo __('Rollen für Antworten im Forum', 'cp-communitie'); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$reply_roles = get_option(CPC_OPTIONS_PREFIX.'_forum_reply');

				echo '<input type="checkbox" name="repliers[]" value="'.__('everyone', 'cp-communitie').'"';
				if (strpos(strtolower($reply_roles), strtolower(__('everyone', 'cp-communitie')).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.__('Mitglied', 'cp-communitie').' ... <span class="description">'.__('bedeutet, dass alle Mitglieder auf Themen antworten können, wenn diese Option aktiviert ist', 'cp-communitie').'</span><br />';						
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="repliers[]" value="'.$role['name'].'"';
					if (strpos(strtolower($reply_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description"><?php echo __('Die ClassicPress-Rollen, die auf ein Thema im Forum antworten können', 'cp-communitie'); ?></span></td> 
			</tr> 
		
			<tr valign="top"> 
			<td scope="row"><label for="forum_comments"><?php echo __('Rollen für Forumskommentare', 'cp-communitie'); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$reply_roles = get_option(CPC_OPTIONS_PREFIX.'_forum_reply_comment');

				echo '<input type="checkbox" name="commenters[]" value="'.__('Mitglied', 'cp-communitie').'"';
				if (strpos(strtolower($reply_roles), strtolower(__('everyone', 'cp-communitie')).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.__('Mitglied', 'cp-communitie').' ... <span class="description">'.__('bedeutet, dass alle Mitglieder Antworten kommentieren können, wenn diese Option aktiviert ist', 'cp-communitie').'</span><br />';						
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="commenters[]" value="'.$role['name'].'"';
					if (strpos(strtolower($reply_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description"><?php echo __('Die ClassicPress-Rollen, die Kommentare zu Forumsantworten hinzufügen können', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr><td colspan="2"><a name="ranks"></a><h2>Ranks</h2></td></tr>

			<?php
			$ranks = explode(';', get_option(CPC_OPTIONS_PREFIX.'_forum_ranks'));
			?>
			<tr valign="top"> 
			<td scope="row"><label for="forum_ranks"><?php echo __('Forumsränge', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_ranks" id="forum_ranks" <?php if ($ranks[0] == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Ränge im Forum verwenden?', 'cp-communitie'); ?></span></td> 
			</tr>

			<?php
			for ( $rank = 1; $rank <= 11; $rank ++) {
				echo '<tr valign="top">';
					if ($rank == 1) { 

						echo '<td scope="row">';
							echo __('Titel und Beiträge erforderlich', 'cp-communitie');
						echo '</td>';

					} else {

						echo '<td scope="row">';
						
							if ($rank == 11) {
								echo '<em>'.__('(leere Ränge werden nicht verwendet)', 'cp-communitie').'</em>';
							} else {
								echo "&nbsp;";
							}
						
						echo '</td>';

					}
					?>
					<td>
						<?php 
							$this_rank = $rank*2-1;
							$this_rank_label = $ranks[$this_rank];
							$this_rank_value = $ranks[$this_rank+1];
							
							if ($this_rank_label != '') {
								echo '<input name="rank'.$rank.'" type="text" id="rank'.$rank.'"  value="'.$this_rank_label.'" /> ';
								if ($rank > 1) {
									echo '<input name="score'.$rank.'" type="text" id="score'.$rank.'" style="width:50px" value="'.$this_rank_value.'" /> ';
								} else {
									echo '<input name="score'.$rank.'" type="text" id="score'.$rank.'" style="width:50px; display:none;"" /> ';
								} 
							} else {
								echo '<input name="rank'.$rank.'" type="text" id="rank'.$rank.'"  value="" /> ';
								if ($rank > 1) {
									echo '<input name="score'.$rank.'" type="text" id="score'.$rank.'" style="width:50px" value="" /> ';
								}
							}
						?>

						<span class="description">
						<?php 
						if ($rank == 1) {
							echo __('Die meisten Beiträge', 'cp-communitie'); 
						} else {
							echo __('Rang', 'cp-communitie').' '.($rank-1); 							
						}
						?></span>
					</td> 
				</tr>
			<?php
			}
			do_action('__cpc__menu_forum_hook');	
			?>
			
			<tr><td colspan="2"><a name="display"></a><h2>Display</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="_alt_subs"><?php echo __('Unterkategorien', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="_alt_subs" id="_alt_subs" <?php if (get_option(CPC_OPTIONS_PREFIX.'_alt_subs') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Untergeordnete Kategorien unter übergeordneten Kategorien anzeigen', 'cp-communitie'); ?></span>
			</td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="pagination"><?php echo __('Paginierung', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="pagination" id="pagination" <?php if (get_option(CPC_OPTIONS_PREFIX.'_pagination') == "on" && get_option(CPC_OPTIONS_PREFIX.'_forum_ajax') != "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Teilt Themenantworten in Seiten auf', 'cp-communitie'); ?></span>
			<?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_ajax') == "on")
				echo '<br /><span class="description" style="color:red;">'.__('Entschuldigung, dies ist nicht kompatibel, wenn Du AJAX im Forum verwendest (oben).', 'cp-communitie').'</span>';
			?>
			</td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="pagination_location"><?php echo __('Platzierung der Paginierung', 'cp-communitie'); ?></label></td>
			<td>
			<select name="pagination_location">
				<option value="both"<?php if (get_option(CPC_OPTIONS_PREFIX.'_pagination_location') == 'both') echo ' SELECTED'; ?>><?php echo __('Ober und Unter Antworten', 'cp-communitie'); ?></option>
				<option value="top"<?php if (get_option(CPC_OPTIONS_PREFIX.'_pagination_location') == 'top') echo ' SELECTED'; ?>><?php echo __('Über Antworten', 'cp-communitie'); ?></option>
				<option value="bottom"<?php if (get_option(CPC_OPTIONS_PREFIX.'_pagination_location') == 'bottom') echo ' SELECTED'; ?>><?php echo __('Unter Antworten', 'cp-communitie'); ?></option>
			</select>
			<span class="description"><?php echo __('Wenn Paginierung verwendet wird, wo die Seitennavigation angezeigt werden soll', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="pagination_size"><?php echo __('Anzahl der Antworten pro Seite (Paginierung)', 'cp-communitie'); ?></label></td>
			<td><input name="pagination_size" style="width:50px" type="text" id="pagination_size"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_pagination_size') ? get_option(CPC_OPTIONS_PREFIX.'_pagination_size') : 10; ?>" /> 
			<span class="description"><?php echo __('Wenn Paginierung verwendet wird, wie viele Antworten pro Seite.', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="topic_count"><?php echo __('Beitragsanzahl der Forenkategorie', 'cp-communitie'); ?></label></td>
			<td><input name="topic_count" style="width:50px" type="text" id="topic_count"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_topic_count') ? get_option(CPC_OPTIONS_PREFIX.'_topic_count') : 10; ?>" /> 
			<span class="description"><?php echo __('Wie viele Themen werden in einer Forenkategorie angezeigt (verwende eine gerade Zahl).', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="show_dropdown"><?php echo __('Dropdown-Kategorieliste', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="show_dropdown" id="show_dropdown" <?php if (get_option(CPC_OPTIONS_PREFIX.'_show_dropdown') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Zeige eine Dropdown-Liste mit Kategorien für eine schnelle Navigation an', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_info"><?php echo __('Mitgliedsinfo', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_info" id="forum_info" <?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_info') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Mitgliedsinformationen unter dem Avatar im Forum anzeigen', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_stars"><?php echo __('Neue Beiträge-Sterne', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_stars" id="forum_stars" <?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_stars') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Sterne für Beiträge anzeigen, die seit der letzten Anmeldung hinzugefügt wurden.', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="forum_login"><?php echo __('Login Link', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_login" id="forum_login" <?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_login') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Login-Link im Forum anzeigen, wenn nicht eingeloggt?', 'cp-communitie'); ?></span></td> 
			</tr> 
                                                			
			<tr valign="top"> 
			<td scope="row"><label for="forum_login_form"><?php echo __('Login-Link unter dem Thema anzeigen', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="forum_login_form" id="forum_login_form" <?php if (get_option(CPC_OPTIONS_PREFIX.'_forum_login_form') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Wenn sich ein Benutzer anmelden muss, zeigen Sie den Anmeldelink unter dem Thema/den Antworten an.', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr><td colspan="2"><a name="more"></a><h2>Abonnements</h2></td></tr>
									
			<tr valign="top"> 
			<td scope="row"><label for="suppress_forum_notify"><?php echo __('Forum-Abonnement', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="suppress_forum_notify" id="suppress_forum_notify" <?php if (get_option(CPC_OPTIONS_PREFIX.'_suppress_forum_notify') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Alle Abonnementoptionen für das Forum ausblenden', 'cp-communitie'); ?></span><br />
			<input type="checkbox" name="clear_forum_subs" id="clear_forum_subs" />
			<span class="description"><?php echo __('Wenn aktiviert, werden alle Forenabonnements beim Speichern gelöscht (Option nicht gespeichert, nur einmal angewendet).', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="allow_subscribe_all"><?php echo __('Abonniere alle Forumsaktivitäten', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="allow_subscribe_all" id="allow_subscribe_all" <?php if (get_option(CPC_OPTIONS_PREFIX.'_allow_subscribe_all') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Erlaubt Allen über die Profilseite, Profildetails zu abonnieren. Wenn Du viele Benutzer hast, solltest Du dies deaktivieren, um die Leistung des Forums zu verbessern.', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr><td colspan="2"><a name="more"></a><h2>Mehr...</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="send_summary"><?php echo __('Tägliche Zusammenfassung', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="send_summary" id="send_summary" <?php if (get_option(CPC_OPTIONS_PREFIX.'_send_summary') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Ermögliche allen Mitgliedern tägliche Zusammenfassungen der Forumsaktivitäten per E-Mail', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="include_admin"><?php echo __('Admin-Ansichten', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="include_admin" id="include_admin" <?php if (get_option(CPC_OPTIONS_PREFIX.'_include_admin') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Schließe den Administrator, der ein Thema ansieht, in die Gesamtzahl der Aufrufe ein', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="bump_topics"><?php echo __('Schlage Themen', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="bump_topics" id="bump_topics" <?php if (get_option(CPC_OPTIONS_PREFIX.'_bump_topics') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Verschiebt Themen an den Anfang des Forums, wenn neue Antworten gepostet werden', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="oldest_first"><?php echo __('Reihenfolge der Antworten', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="oldest_first" id="oldest_first" <?php if (get_option(CPC_OPTIONS_PREFIX.'_oldest_first') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Älteste Antworten zuerst anzeigen (deaktivieren, um die Reihenfolge umzukehren)', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="forum_lock"><?php echo __('Bearbeitung Sperrzeit', 'cp-communitie'); ?></label></td>
			<td><input name="forum_lock" style="width:50px" type="text" id="forum_lock"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_forum_lock'); ?>" /> 
			<span class="description"><?php echo __('Wie viele Minuten, bevor ein Forumsthema/eine Antwort nicht mehr bearbeitet/gelöscht werden kann, 0 für keine Sperre.', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="preview1"><?php echo __('Vorschaulänge', 'cp-communitie'); ?></label></td>
			<td><input name="preview1" style="width:50px" type="text" id="preview1"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_preview1'); ?>" /> 
			<span class="description"><?php echo __('Maximale Anzahl von Zeichen, die in der Themenvorschau angezeigt werden', 'cp-communitie'); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="preview2"></label></td>
			<td><input name="preview2" style="width:50px" type="text" id="preview2"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_preview2'); ?>" /> 
			<span class="description"><?php echo __('Maximale Anzahl von Zeichen, die in der Antwortvorschau angezeigt werden', 'cp-communitie'); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="cpc_default_forum"><?php echo __('Standardkategorien', 'cp-communitie'); ?></label></td>
			<td><input name="cpc_default_forum" type="text" id="cpc_default_forum"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_cpc_default_forum'); ?>" /> 
			<span class="description"><?php echo __('Liste der Forenkategorie-IDs, die neue Site-Mitglieder automatisch abonnieren (kommagetrennt)', 'cp-communitie'); ?></span></td> 
			</tr> 
			
			<tr valign="top"> 
			<td scope="row"><label for="chatroom_banned"><?php echo __('Verbotene Forenwörter', 'cp-communitie'); ?></label></td> 
			<td><input name="chatroom_banned" type="text" id="chatroom_banned"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_chatroom_banned'); ?>" /> 
			<span class="description"><?php echo __('Kommaseparierte Liste von Wörtern, die im Forum nicht erlaubt sind', 'cp-communitie'); ?></td> 
			</tr> 

									
			<tr valign="top"> 
			<td scope="row"><label for="closed_word"><?php echo __('Geschlossen-Wort', 'cp-communitie'); ?></label></td>
			<td><input name="closed_word" type="text" id="closed_word"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_closed_word'); ?>" /> 
			<span class="description"><?php echo __('Wort zur Kennzeichnung eines abgeschlossenen Themas (siehe auch Stile)', 'cp-communitie'); ?></span></td> 
			</tr> 

			<?php
			$sharing = get_option(CPC_OPTIONS_PREFIX.'_sharing');
			if ( strpos($sharing, "pl") === FALSE ) { $sharing_permalink = ''; } else { $sharing_permalink = 'on'; }
			if ( strpos($sharing, "fb") === FALSE ) { $sharing_facebook = ''; } else { $sharing_facebook = 'on'; }
			if ( strpos($sharing, "tw") === FALSE ) { $sharing_twitter = ''; } else { $sharing_twitter = 'on'; }
			if ( strpos($sharing, "ms") === FALSE ) { $sharing_myspace = ''; } else { $sharing_myspace = 'on'; }
			if ( strpos($sharing, "li") === FALSE ) { $sharing_linkedin = ''; } else { $sharing_linkedin = 'on'; }
			if ( strpos($sharing, "be") === FALSE ) { $sharing_bebo = ''; } else { $sharing_bebo = 'on'; }
			if ( strpos($sharing, "em") === FALSE ) { $sharing_email = ''; } else { $sharing_email = 'on'; }
			?>
			

			<tr valign="top"> 
			<td scope="row"><label for="sharing_permalink"><?php echo __('Sharing-Icons enthalten', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="sharing_permalink" id="sharing_permalink" <?php if ($sharing_permalink == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Permalink (zum Kopieren)', 'cp-communitie'); ?></span><br />
			<input type="checkbox" name="sharing_email" id="sharing_email" <?php if ($sharing_email == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Email', 'cp-communitie'); ?></span><br />
			<input type="checkbox" name="sharing_facebook" id="sharing_facebook" <?php if ($sharing_facebook == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Facebook', 'cp-communitie'); ?></span><br />
			<input type="checkbox" name="sharing_twitter" id="sharing_twitter" <?php if ($sharing_twitter == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Twitter', 'cp-communitie'); ?></span><br />
			<input type="checkbox" name="sharing_myspace" id="sharing_myspace" <?php if ($sharing_myspace == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('MySpace', 'cp-communitie'); ?></span><br /> 
			<input type="checkbox" name="sharing_bebo" id="sharing_bebo" <?php if ($sharing_bebo == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Bebo', 'cp-communitie'); ?></span><br />
			<input type="checkbox" name="sharing_linkedin" id="sharing_linkedin" <?php if ($sharing_linkedin == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('LinkedIn', 'cp-communitie'); ?></span>
			</td> 
			</tr> 

			<tr valign="top"> 
			<td colspan=2>
				<p>
				<span class="description">
				<strong><?php echo __('Anmerkungen', 'cp-communitie'); ?></strong>
				<ul style='margin-left:6px'>
				<li>&middot;&nbsp;<?php echo __('Tägliche Zusammenfassungen (falls es etwas zu senden gibt) werden gesendet, wenn der erste Besucher nach Mitternacht Ortszeit auf die Webseite kommt.', 'cp-communitie'); ?></li>
				<li>&middot;&nbsp;<?php echo __('Beachte die von Deinem Hosting-Provider festgelegten Beschränkungen für das Versenden von Massen-E-Mails, da diese Deine Webseite möglicherweise sperren.', 'cp-communitie'); ?></li>
				</ul>
				</p>
			</td>
			</tr> 

			<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>

			<tr><td><?php echo '['.CPC_SHORTCODE_PREFIX.'-forum]'; ?></td>
				<td><?php _e('Zeige das Forum an.', 'cp-communitie'); ?></td></tr>
			<tr><td><?php echo '['.CPC_SHORTCODE_PREFIX.'-forum cat="2"]'; ?></td>
				<td><?php _e('Zeige nur die Forumskategorie-ID 2 auf einer einzigen ClassicPress-Seite an.', 'cp-communitie'); ?></td></tr>
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_forumlatestposts_count"><?php echo '['.CPC_SHORTCODE_PREFIX.'-forumlatestposts]'; ?></label></td>
			<td><?php _e('Zeige die neuesten Forenbeiträge und Antworten an.', 'cp-communitie'); ?><br /><input name="cpcommunitie_forumlatestposts_count" style="width:50px" type="text" id="cpcommunitie_forumlatestposts_count"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_cpcommunitie_forumlatestposts_count'); ?>" /> 
			<span class="description"><?php 
			echo sprintf(__('Standardanzahl der anzuzeigenden Themen. Kann überschrieben werden, zB: [%s-forumlatestposts count=10]', 'cp-communitie'), CPC_SHORTCODE_PREFIX).'<br />'; 
			echo '<span style="margin-left:55px">'.sprintf(__('Forenkategorie-IDs können im Shortcode angegeben werden, z. B.: [%s-forumlatestposts cat=1]', 'cp-communitie'), CPC_SHORTCODE_PREFIX).'</span>'; ?></span></td> 
			</tr> 
			

															
			</table> 	
		 
			<p class="submit" style='margin-left:6px;'> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
			</p> 
			</form> 
		
	<?php	__cpc__show_tabs_header_end(); ?> 
	</div>
<?php
}



function __cpc__plugin_categories() {

	global $wpdb;

  	if (!current_user_can('manage_options'))  {
		wp_die( __('Du hast keine ausreichenden Berechtigungen, um auf diese Seite zuzugreifen.', 'cp-communitie') );
  	}
  	
  	if (isset($_GET['action'])) {
		$action = $_GET['action'];
	} else {
		$action = '';
	}

	// Update values
	if (isset($_POST['title'])) {
		
   		$range = array_keys($_POST['cid']);
		foreach ($range as $key) {
			$cid = $_POST['cid'][$key];
			$cat_parent = $_POST['cat_parent'][$key];
			$title = $_POST['title'][$key];

			if (isset($_POST['level_'.$cid])) {
		   		$range2 = array_keys($_POST['level_'.$cid]);
		   		$level = '';
	   			foreach ($range2 as $key2) {
					$level .= $_POST['level_'.$cid][$key2].',';
		   		}
			} else {
				$level = '';
			}
			
			$listorder = $_POST['listorder'][$key];
			$allow_new = $_POST['allow_new'][$key];
			$hide_breadcrumbs = $_POST['hide_breadcrumbs'][$key];
			$hide_main = $_POST['hide_main'][$key];
			$cat_desc = $_POST['cat_desc'][$key];
			$min_rank = $_POST['min_rank'][$key] ? $_POST['min_rank'][$key] : 0;
			
			if ($cid == $_POST['default_category']) {
				$defaultcat = "on";
			} else {
				$defaultcat = "";
			}
			
			$wpdb->query( $wpdb->prepare( "
				UPDATE ".$wpdb->prefix.'cpcommunitie_cats'."
				SET title = %s, cat_parent = %d, min_rank = %d, listorder = %s, allow_new = %s, hide_breadcrumbs = %s, hide_main = %s, cat_desc = %s, defaultcat = %s, level = %s 
				WHERE cid = %d", 
				$title, $cat_parent, $min_rank, $listorder, $allow_new, $hide_breadcrumbs, $hide_main, $cat_desc, $defaultcat, serialize($level), $cid  ) );
							
		}

	}
		
  	// Add new category?
  	if ( (isset($_POST['new_title']) && $_POST['new_title'] != '') && ($_POST['new_title'] != __('Neue Kategorie hinzufügen', 'cp-communitie').'...') ) {
  		
  		$new_cat_desc = $_POST['new_cat_desc'];
  		if ($new_cat_desc == __('Optionale Beschreibung', 'cp-communitie')."...") {
  			$new_cat_desc = '';  		
  		}
  	
		$stub = trim(preg_replace("/[^A-Za-z0-9 ]/",'',$_POST['new_title']));
		$stub = strtolower(str_replace(' ', '-', $stub));
		$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."cpcommunitie_cats WHERE stub = '".$stub."'";
		$cnt = $wpdb->get_var($sql);
		if ($cnt > 0) $stub .= "-".$cnt;
		$stub = str_replace('--', '-', $stub);
		  		
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->prefix.'cpcommunitie_cats'."
			( 	title, 
				cat_parent,
				listorder,
				cat_desc,
				allow_new,
				stub
			)
			VALUES ( %s, %d, %d, %s, %s, %s )", 
			array(
				$_POST['new_title'], 
				$_POST['new_parent'],
				$_POST['new_listorder'],
				$new_cat_desc,
				$_POST['new_allow_new'],
				$stub
				) 
			) );
		  
	}

  	// Delete a category?
  	if ( ($action == 'delcid') && (current_user_can('level_10')) ) {
  		// Must leave at least one category, so check
		$cat_count = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'cpcommunitie_cats');
		if ($cat_count > 1) {
			$wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix.'cpcommunitie_cats'." WHERE cid = %d", $_GET['cid']) );
			if ($_GET['all'] == 1) {
				$wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix.'cpcommunitie_topics'." WHERE topic_category = %d", $_GET['cid']) );
			} else {
				$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix.'cpcommunitie_topics'." SET topic_category = 0 WHERE topic_category = %d", $_GET['cid']) );
			}
		} else {
			echo "<div class='error'><p>".__('Du musst mindestens eine Kategorie haben', 'cp-communitie').".</p></div>";
		}
  	}
 
		// See if the user has posted updated category information
		if( isset($_POST[ 'categories_update' ]) && $_POST[ 'categories_update' ] == 'Y' ) {
			
	   		$range = array_keys($_POST['tid']);
			foreach ($range as $key) {
		
				$tid = $_POST['tid'][$key];
				$topic_category = $_POST['topic_category'][$key];
				$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix.'cpcommunitie_topics'." SET topic_category = ".$topic_category." WHERE tid = %d", $tid) );					
			}
	
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Kategorien gespeichert', 'cp-communitie')."</p></div>";
	
		}
 	

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s-Verwaltung', 'cp-communitie'), CPC_WL).'</h2><br />';
	__cpc__show_manage_tabs_header('categories');
	echo '<div style="float:right">';
	echo '<a href="admin.php?page=cpcommunitie_forum">'.__('Gehe zu den Forum-Optionen', 'cp-communitie').'</a><br /><br />';	 
	echo '</div>';
	?> 
	<form method="post" action=""> 

	<table class="widefat">
	<thead>
	<tr>
	<th style="width:40px">ID</th>
	<th style="width:60px"><?php echo __('Eltern ID', 'cp-communitie'); ?></th>
	<th><?php echo __('Kategorietitel und Beschreibung', 'cp-communitie'); ?></th>
	<th><?php echo __('Zulässige Rollen', 'cp-communitie'); ?></th>
	<th style="text-align:center"><?php echo __('Themen', 'cp-communitie'); ?></th>
	<th><?php echo __('Sortierung', 'cp-communitie'); ?></th>
	<th><?php echo __('Einstellungen', 'cp-communitie'); ?></th>
	<th>&nbsp;</th>
	</tr> 
	</thead>
	<?php	
	$included = __cpc__show_forum_children(0, 0, '');

	// Get list of roles
	global $wp_roles;
	$all_roles = $wp_roles->roles;
	
	// Check for categories with incorrect Parent IDs
	$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."cpcommunitie_cats ORDER BY cid");
	$shown_header = false;
	if ($categories) {
		foreach ($categories as $category) {

			if (!__cpc__inHaystack($included, $category->cid)) {
				
				if (!$shown_header) {
					$shown_header = true;
					?>
					<thead>
					<tr>
					<th style="width:20px"></th>
					<th style="width:60px">&nbsp;</th>
					<th><strong><?php echo __('Folgendes wird aufgrund der Eltern-ID nicht angezeigt (aktualisieren oder löschen)', 'cp-communitie'); ?>...</strong></th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					</tr> 
					</thead>
					<?php
				}
				echo '<tr valign="top">';
				echo '<input name="cid[]" type="hidden" value="'.$category->cid.'" />';
				echo '<td>'.$category->cid.'</td>';
				echo '<td><input name="cat_parent[]" type="text" value="'.stripslashes($category->cat_parent).'" style="width:50px" /></td>';
				echo '<td>';
				echo '<input name="title[]" type="text" value="'.stripslashes($category->title).'" class="regular-text" style="width:150px" /><br />';
				echo '<input name="cat_desc[]" type="text" value="'.stripslashes($category->cat_desc).'" class="regular-text" style="width:150px" />';
				echo '</td>';
				echo '<td>';
				$cat_roles = unserialize($category->level);
				echo '<input type="checkbox" class="cpc_forum_cat_'.$category->cid.'" name="level_'.$category->cid.'[]" value="everyone"';
				if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> Everyone<br />';
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" class="cpc_forum_cat_'.$category->cid.'" name="level_'.$category->cid.'[]" value="'.$role['name'].'"';
					if (strpos(strtolower($cat_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}				
				echo '<a href="javascript:void(0);" title="'.$category->cid.'" class="cpcommunitie_cats_check">'.__('Alle aktivieren/deaktivieren', 'cp-communitie').'</a><br />';
				
				echo '</td>';
				echo '<td style="text-align:center;">';
				echo $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_category = ".$category->cid);
				echo '</td>';
				echo '<td><input name="listorder[]" type="text" value="'.$category->listorder.'" style="width:50px" /></td>';
				echo '<td>';
				echo '<select name="allow_new[]">';
				echo '<option value="on"';
					if ($category->allow_new == "on") { echo " SELECTED"; }
					echo '>'.__('Ja', 'cp-communitie').'</option>';
				echo '<option value=""';
					if ($category->allow_new != "on") { echo " SELECTED"; }
					echo '>'.__('Nein', 'cp-communitie').'</option>';
				echo '</select>';
				echo '</td>';
				echo '<td>';
				echo '<a class="delete" href="?page=cpcommunitie_categories&action=delcid&all=0&cid='.$category->cid.'">'.__('Kategorie löschen', 'cp-communitie').'</a><br />';
				echo '<a class="delete" href="?page=cpcommunitie_categories&action=delcid&all=1&cid='.$category->cid.'">'.__('Kategorie und Beiträge löschen', 'cp-communitie').'</a>';
				echo '</td>';
				echo '</tr>';
				
			}
		}
	}
	echo '<tr><td colspan="8">';
	echo sprintf(__('Hinweis: "Forumsrollen anzeigen", "Forumsrollen für neue Themen" und "Forumsantwortrollen" in den <a href="%s">Forumseinstellungen</a> wirken sich auf das gesamte Forum aus, die oben erlaubten Rollen sind zum Anzeigen und Bearbeiten pro Forumskategorie.', 'cp-communitie'), "admin.php?page=cpcommunitie_forum");
	echo '</td></tr>';
	
	?>
	
	<thead>
	<tr>
	<th style="width:20px"></th>
	<th style="width:60px"><?php echo __('Eltern ID', 'cp-communitie'); ?></th>
	<th><?php echo __('Neue Kategorie hinzufügen', 'cp-communitie'); ?></th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th><?php echo __('Sortierung', 'cp-communitie'); ?></th>
	<th><?php echo __('Neue Themen zulassen', 'cp-communitie'); ?></th>
	<th>&nbsp;</th>
	</tr> 
	</thead>

	<tr valign="top">
	<td>&nbsp;</td>
	<td><input name="new_parent" type="text" value="0" style="width:50px" /></td>
	<td>
		<input name="new_title" type="text" onclick="javascript:this.value = ''" value="<?php echo __('Neue Kategorie hinzufügen', 'cp-communitie'); ?>..." class="regular-text" style="width:150px" /><br />
		<input name="new_cat_desc" type="text" onclick="javascript:this.value = ''" value="<?php echo __('Optionale Beschreibung', 'cp-communitie'); ?>..." class="regular-text" style="width:150px" />
	</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>
		<input name="new_listorder" type="text" value="0" style="width:50px" />
	</td>
	<td>
	<input type="checkbox" name="new_allow_new" CHECKED />
	</td>
	<td colspan=2>&nbsp;</td>
	</tr>
	</table> 

	<br /><?php echo __('Standardkategorie', 'cp-communitie'); ?>:
	<?php
	$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'cpcommunitie_cats ORDER BY listorder');

	if ($categories) {
		echo "<select name='default_category'>";
		foreach ($categories as $category) {
			echo "<option value=".$category->cid;
			if ($category->defaultcat == "on") { echo " SELECTED"; }
			echo ">".$category->title."</option>";
		}
		echo "</select>";
	}	
	?>
	 
	<p class="submit"> 
	<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
	</p> 
	
	<p>
	<?php
	echo __('Anmerkung:', 'cp-communitie');
	echo '<li>'.__('Wähle „Kategorie und Beiträge löschen“, um eine Kategorie und alle Themen in dieser Kategorie zu löschen.', 'cp-communitie').'</li>';
	echo '<li>'.sprintf(__('Wenn Kategoriebeschreibungen nicht angezeigt werden, versuche Deine <a href="%s">Forenvorlagen</a> zurückzusetzen.', 'cp-communitie'), "admin.php?page=cpcommunitie_templates");
	echo '<span class="__cpc__tooltip" title="'.__('Die Vorlage für Forumkategorien (Liste) sollte den Code [category_desc] enthalten,<br />um die Beschreibung der Forumkategorie anzuzeigen.', 'cp-communitie').'">?</span></li>';
	?>
	<p>
	</form> 
	
	<?php
	__cpc__show_manage_tabs_header_end();
	
  	echo '</div>';

} 	
function __cpc__inHaystack($haystack, $needle) {
	$haystack = explode(',', $haystack);
	return in_array($needle, $haystack);
}

function __cpc__show_forum_children($id, $indent, $list) {
	
	global $wpdb;

	// Get list of roles
	global $wp_roles;
	$all_roles = $wp_roles->roles;
	
	$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."cpcommunitie_cats WHERE cat_parent = ".$id." ORDER BY listorder");

	if ($categories) {
		foreach ($categories as $category) {
			
			$list = $list.$category->cid.",";
			
			switch($indent) {
			case 0:
				$style="background-color:#aaf";
				break;
			case 1:
				$style="background-color:#bfb";
				break;
			case 2:
				$style="background-color:#fcc";
				break;
			case 3:
				$style="background-color:#ddd";
				break;
			case 4:
				$style="background-color:#eee";
				break;
			case 5:
				$style="background-color:#fff";
				break;
			default:
				$style="background-color:#fff";
				break;
			}

			echo '<tr valign="top">';
			echo '<input name="cid[]" type="hidden" value="'.$category->cid.'" />';
			echo '<td style="'.$style.'">'.str_repeat("...", $indent).'&nbsp;'.$category->cid.'</td>';
			echo '<td><input name="cat_parent[]" type="text" value="'.stripslashes($category->cat_parent).'" style="width:30px" />';
			echo '<span class="__cpc__tooltip" title="'.__('ID der Forenkategorie, in der diese Forenkategorie erscheinen soll.<br />Ermöglicht es Dir, eine Hierarchie von Forenkategorien zu erstellen.', 'cp-communitie').'">?</span>';
			echo '</td>';
			echo '<td>';
			echo str_repeat("&nbsp;&nbsp;&nbsp;", $indent).'<input name="title[]" type="text" value="'.stripslashes($category->title).'" class="regular-text" style="width:150px" />';
			echo '<span class="__cpc__tooltip" title="'.__('Titel der Forumskategorie', 'cp-communitie').'">?</span>';
			echo '<br />';
			echo str_repeat("&nbsp;&nbsp;&nbsp;", $indent).'<input name="cat_desc[]" type="text" value="'.stripslashes($category->cat_desc).'" class="regular-text" style="width:150px" />';
			echo '<span class="__cpc__tooltip" title="'.__('Optionale Beschreibung der Forumskategorie', 'cp-communitie').'">?</span>';

			// Add option for minimum forum rank if in use
			$ranks = explode(';', get_option(CPC_OPTIONS_PREFIX.'_forum_ranks'));
			$using_ranks = $ranks[0] == 'on' ? true : false;
			if ($using_ranks) {
				echo '<br /><br />';
				echo '<table>';
				echo '<tr><td style="border:0px;padding-top:8px">'.sprintf(__('Mindest-<a href="%s">Rangpunktzahl</a>', 'cp-communitie'), 'admin.php?page=cpcommunitie_forum#ranks') . ':</td>';
				echo '<td style="border:0px"><input name="min_rank[]" type="text" value="'.$category->min_rank.'" style="width:50px;" /><span class="__cpc__tooltip" title="'.__('Überprüfe die Ergebnisse des Forum-Rangs über den Link. Ein Benutzer muss mindestens<br />die hier eingegebene Punktzahl haben, um die Forenkategorie sehen zu können.', 'cp-communitie').'">?</span></td></tr>';
				echo '</table>';
			}
			echo '</td>';
			echo '<td>';
			$cat_roles = unserialize($category->level);
			echo '<input type="checkbox" class="cpc_forum_cat_'.$category->cid.'" name="level_'.$category->cid.'[]" value="everyone"';
			if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE) {
				echo ' CHECKED';
			}
			echo '> Everyone<br />';
			foreach ($all_roles as $role) {
				echo '<input type="checkbox" class="cpc_forum_cat_'.$category->cid.'" name="level_'.$category->cid.'[]" value="'.$role['name'].'"';
				if (strpos(strtolower($cat_roles), strtolower($role['name']).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.$role['name'].'<br />';
			}
			
			echo '<a href="javascript:void(0);" title="'.$category->cid.'" class="cpcommunitie_cats_check">'.__('Alle aktivieren/deaktivieren', 'cp-communitie').'</a><br />';
			echo '</td>';
			echo '<td style="text-align:center">';
			echo $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_parent = 0 AND topic_category = ".$category->cid);
			echo '</td>';
			echo '<td><input name="listorder[]" type="text" value="'.$category->listorder.'" style="width:50px" />';
			echo '<span class="__cpc__tooltip" title="'.__('Lege die Reihenfolge der Kategorien fest - verwende numerische Werte.', 'cp-communitie').'">?</span>';
			echo '</td>';
			echo '<td>';
			echo __('Neue Themen zulassen?', 'cp-communitie').'<br />';
			echo '<select name="allow_new[]">';
			echo '<option value="on"';
				if ($category->allow_new == "on") { echo " SELECTED"; }
				echo '>'.__('Ja', 'cp-communitie').'</option>';
			echo '<option value=""';
				if ($category->allow_new != "on") { echo " SELECTED"; }
				echo '>'.__('Nein', 'cp-communitie').'</option>';
			echo '</select>';
			echo '<span class="__cpc__tooltip" title="'.__('Sollen Benutzer<br />neue Themen in dieser Kategorie starten können?<br />Administratoren können jederzeit<br />ein neues Thema erstellen.', 'cp-communitie').'">?</span>';
			echo '<br />'.__('Breadcrumbs verstecken?', 'cp-communitie').'<br />';
			echo '<select name="hide_breadcrumbs[]">';
			echo '<option value="on"';
				if ($category->hide_breadcrumbs == "on") { echo " SELECTED"; }
				echo '>'.__('Ja', 'cp-communitie').'</option>';
			echo '<option value=""';
				if ($category->hide_breadcrumbs != "on") { echo " SELECTED"; }
				echo '>'.__('Nein', 'cp-communitie').'</option>';
			echo '</select>';
			echo '<span class="__cpc__tooltip" title="'.__('Die Breadcrumbs des Forums für diese Kategorie ausblenden?', 'cp-communitie').'">?</span>';
			echo '<br />'.__('Vom Forum ausschließen?', 'cp-communitie').'<br />';
			echo '<select name="hide_main[]">';
			echo '<option value="on"';
				if ($category->hide_main == "on") { echo " SELECTED"; }
				echo '>'.__('Ja', 'cp-communitie').'</option>';
			echo '<option value=""';
				if ($category->hide_main != "on") { echo " SELECTED"; }
				echo '>'.__('Nein', 'cp-communitie').'</option>';
			echo '</select>';
			echo '<span class="__cpc__tooltip" title="'.__('Vom Hauptforum ausschließen?', 'cp-communitie').'">?</span>';
			echo '</td>';
			echo '</td>';
			echo '<td>';
			echo '<a class="delete" href="?page=cpcommunitie_categories&action=delcid&all=0&cid='.$category->cid.'">'.__('Kategorie löschen', 'cp-communitie').'</a><br />';
			echo '<a class="delete" href="?page=cpcommunitie_categories&action=delcid&all=1&cid='.$category->cid.'">'.__('Kategorie und Beiträge löschen', 'cp-communitie').'</a>';
			echo '</td>';
			echo '</tr>';

			$list = __cpc__show_forum_children($category->cid, $indent+1, $list);
	
		}
	}
	
	return $list;
}

function __cpc__plugin_styles() {
	
	global $wpdb;

	if (!current_user_can('manage_options'))  {
		wp_die( __('Du hast keine ausreichenden Berechtigungen, um auf diese Seite zuzugreifen.', 'cp-communitie') );
	}

  	echo '<div class="wrap">';
  		echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.__('Stile', 'cp-communitie').'</h2>';

		// See if the user has saved CSS
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == 'CSS' ) {
			$css = str_replace(chr(13), "[]", $_POST['css']);
			update_option(CPC_OPTIONS_PREFIX.'_css', $css);
  		}

		// See if the user has saved responsive CSS
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == 'responsive' ) {
			$css = str_replace(chr(13), "[]", $_POST['css']);
			update_option(CPC_OPTIONS_PREFIX.'_responsive', $css);
  		}

		// See if the user is deleting a style
		if ( isset($_GET[ 'delstyle' ]) ) {
			$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_styles WHERE sid = %d";
			if ( $wpdb->query( $wpdb->prepare( $sql, $_GET[ 'delstyle' ])) ) {
				echo "<div class='updated slideaway'><p>".__('Vorlage gelöscht', 'cp-communitie')."</p></div>";
			}
		}	
		// See if the user has selected a template
		if( isset($_POST[ 'sid' ]) ) {
			$style = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.'cpcommunitie_styles'." WHERE sid = ".$_POST['sid']);
			if ($style) {
				update_option(CPC_OPTIONS_PREFIX.'_use_styles', 'on');
				update_option(CPC_OPTIONS_PREFIX.'_categories_background', $style->__cpc__categories_background);
				update_option(CPC_OPTIONS_PREFIX.'_categories_color', $style->categories_color);
				update_option(CPC_OPTIONS_PREFIX.'_border_radius', $style->border_radius);
				update_option(CPC_OPTIONS_PREFIX.'_main_background', $style->main_background);
				update_option(CPC_OPTIONS_PREFIX.'_bigbutton_background', $style->bigbutton_background);
				update_option(CPC_OPTIONS_PREFIX.'_bigbutton_background_hover', $style->bigbutton_background_hover);
				update_option(CPC_OPTIONS_PREFIX.'_bigbutton_color', $style->bigbutton_color);
				update_option(CPC_OPTIONS_PREFIX.'_bigbutton_color_hover', $style->bigbutton_color_hover);
				update_option(CPC_OPTIONS_PREFIX.'_bg_color_1', $style->bg_color_1);
				update_option(CPC_OPTIONS_PREFIX.'_bg_color_2', $style->bg_color_2);
				update_option(CPC_OPTIONS_PREFIX.'_bg_color_3', $style->bg_color_3);
				update_option(CPC_OPTIONS_PREFIX.'_row_border_style', $style->row_border_style);
				update_option(CPC_OPTIONS_PREFIX.'_row_border_size', $style->row_border_size);
				update_option(CPC_OPTIONS_PREFIX.'_replies_border_size', $style->replies_border_size);
				update_option(CPC_OPTIONS_PREFIX.'_table_rollover', $style->table_rollover);
				update_option(CPC_OPTIONS_PREFIX.'_table_border', $style->table_border);
				update_option(CPC_OPTIONS_PREFIX.'_text_color', $style->text_color);
				update_option(CPC_OPTIONS_PREFIX.'_text_color_2', $style->text_color_2);
				update_option(CPC_OPTIONS_PREFIX.'_link', $style->link);
				update_option(CPC_OPTIONS_PREFIX.'_underline', $style->underline);
				update_option(CPC_OPTIONS_PREFIX.'_link_hover', $style->link_hover);
				update_option(CPC_OPTIONS_PREFIX.'_label', $style->label);
				update_option(CPC_OPTIONS_PREFIX.'_fontfamily', $style->fontfamily);
				update_option(CPC_OPTIONS_PREFIX.'_fontsize', $style->fontsize);
				update_option(CPC_OPTIONS_PREFIX.'_headingsfamily', $style->headingsfamily);
				update_option(CPC_OPTIONS_PREFIX.'_headingssize', $style->headingssize);

				$style_save_as = $style->title;
				$style_id = $style->sid;

				// Put an settings updated message on the screen
				echo "<div class='updated slideaway'><p>".__('Vorlage angewendet', 'cp-communitie')."</p></div>";
			} else {
				echo "<div class='error'><p>".__('Vorlage nicht gefunden', 'cp-communitie')."</p></div>";
			}
		}

		// See if the user has posted us some information
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == 'Y' ) {

			update_option(CPC_OPTIONS_PREFIX.'_use_styles', isset($_POST['use_styles']) ? $_POST['use_styles'] : '');
			update_option(CPC_OPTIONS_PREFIX.'_categories_background', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['__cpc__categories_background']));
			update_option(CPC_OPTIONS_PREFIX.'_categories_color', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['categories_color']));
			update_option(CPC_OPTIONS_PREFIX.'_border_radius', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['border_radius']));
			update_option(CPC_OPTIONS_PREFIX.'_bigbutton_background', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bigbutton_background']));
			update_option(CPC_OPTIONS_PREFIX.'_bigbutton_background_hover', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bigbutton_background_hover']));
			update_option(CPC_OPTIONS_PREFIX.'_bigbutton_color', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bigbutton_color']));
			update_option(CPC_OPTIONS_PREFIX.'_bigbutton_color_hover', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bigbutton_color_hover']));
			update_option(CPC_OPTIONS_PREFIX.'_bg_color_1', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bg_color_1']));
			update_option(CPC_OPTIONS_PREFIX.'_bg_color_2', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bg_color_2']));
			update_option(CPC_OPTIONS_PREFIX.'_bg_color_3', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bg_color_3']));
			update_option(CPC_OPTIONS_PREFIX.'_row_border_style', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['row_border_style']));
			update_option(CPC_OPTIONS_PREFIX.'_row_border_size', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['row_border_size']));
			update_option(CPC_OPTIONS_PREFIX.'_table_rollover', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['table_rollover']));
			update_option(CPC_OPTIONS_PREFIX.'_table_border', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['table_border']));
			update_option(CPC_OPTIONS_PREFIX.'_replies_border_size', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['replies_border_size']));
			update_option(CPC_OPTIONS_PREFIX.'_text_color', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['text_color']));
			update_option(CPC_OPTIONS_PREFIX.'_text_color_2', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['text_color_2']));
			update_option(CPC_OPTIONS_PREFIX.'_link', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['link']));
			update_option(CPC_OPTIONS_PREFIX.'_underline', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['underline']));
			update_option(CPC_OPTIONS_PREFIX.'_link_hover', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['link_hover']));
			update_option(CPC_OPTIONS_PREFIX.'_label', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['label']));
			update_option(CPC_OPTIONS_PREFIX.'_closed_opacity', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['closed_opacity']));
			update_option(CPC_OPTIONS_PREFIX.'_fontfamily', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['fontfamily']));
			update_option(CPC_OPTIONS_PREFIX.'_fontsize', str_replace("px", "", strtolower(preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST[ 'fontsize' ]))));
			update_option(CPC_OPTIONS_PREFIX.'_headingsfamily', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['headingsfamily']));
			update_option(CPC_OPTIONS_PREFIX.'_headingssize', str_replace("px", "", strtolower(preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST[ 'headingssize' ]))));
			update_option(CPC_OPTIONS_PREFIX.'_main_background', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['main_background']));
			
			if( $_POST[ 'style_save_as' ] != '' ) {

				// Delete previous version if it exists
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix."cpcommunitie_styles WHERE title = %s", $_POST['style_save_as'] ) );

				// Save new template
			   	$rows_affected = $wpdb->insert( $wpdb->prefix."cpcommunitie_styles", array( 
				'title' => $_POST['style_save_as'], 
				'border_radius' => $_POST['border_radius'],
				'bigbutton_background' => $_POST['bigbutton_background'], 
				'bigbutton_background_hover' => $_POST['bigbutton_background_hover'],
				'bigbutton_color' => $_POST['bigbutton_color'], 
				'bigbutton_color_hover' => $_POST['bigbutton_color_hover'], 
				'bg_color_1' => $_POST['bg_color_1'], 
				'bg_color_2' => $_POST['bg_color_2'],
				'bg_color_3' => $_POST['bg_color_3'], 
				'table_rollover' => $_POST['table_rollover'], 
				'table_border' => $_POST['table_border'], 
				'row_border_style' => $_POST['row_border_style'], 
				'row_border_size' => $_POST['row_border_size'], 
				'replies_border_size' => $_POST['replies_border_size'], 
				'__cpc__categories_background' => $_POST['__cpc__categories_background'], 
				'categories_color' => $_POST['categories_color'], 
				'text_color' => $_POST['text_color'], 
				'text_color_2' => $_POST['text_color_2'], 
				'link' => $_POST['link'], 
				'underline' => $_POST['underline'], 
				'link_hover' => $_POST['link_hover'], 
				'label' => $_POST['label'],
				'main_background' => $_POST['main_background'],
				'closed_opacity' => $_POST['closed_opacity'],
				'fontfamily' => stripslashes($_POST['fontfamily']),
				'fontsize' => str_replace("px", "", strtolower($_POST[ 'fontsize' ])),
				'headingsfamily' => stripslashes($_POST['headingsfamily']),
				'headingssize' => str_replace("px", "", strtolower($_POST[ 'headingssize' ]))
				) );	
						
				// Put an settings updated message on the screen
				echo "<div class='updated slideaway'><p>".__('Vorlage gespeichert', 'cp-communitie')."</p></div>";
				
				$style_save_as = $_POST[ 'style_save_as' ];	   
			} else {
				$style_save_as = '';	   
			}

		}

		
		// Start tabs
		include_once(dirname(__FILE__).'/show_tabs_style.php');

		// View
		$styles_active = 'active';
		$css_active = 'inactive';
		$responsive_active = 'inactive';
		$view = "styles";
		if (isset($_GET['view']) && $_GET['view'] == 'css') {
			$styles_active = 'inactive';
			$responsive_active = 'inactive';
			$css_active = 'active';
			$view = "css";
		}
		if (isset($_GET['view']) && $_GET['view'] == 'responsive') {
			$styles_active = 'inactive';
			$responsive_active = 'active';
			$css_active = 'inactive';
			$view = "responsive";
		}
	
		echo '<div class="__cpc__wrapper" style="margin-top:15px">';
	
			echo '<div id="mail_tabs">';
			echo '<div class="mail_tab nav-tab-'.$styles_active.'"><a href="admin.php?page=cpcommunitie_styles&view=styles" class="nav-tab-'.$styles_active.'-link">'.__('Stile', 'cp-communitie').'</a></div>';
			echo '<div class="mail_tab nav-tab-'.$css_active.'"><a href="admin.php?page=cpcommunitie_styles&view=css" class="nav-tab-'.$css_active.'-link">'.__('CSS', 'cp-communitie').'</a></div>';
			echo '<div class="mail_tab nav-tab-'.$responsive_active.'" style="width:100px"><a href="admin.php?page=cpcommunitie_styles&view=responsive" class="nav-tab-'.$responsive_active.'-link">'.__('Responsiv', 'cp-communitie').'</a></div>';
			echo '</div>';
		
			echo '<div id="mail-main">';

				// Responsive
				if ($view == "responsive") {

					$css = get_option(CPC_OPTIONS_PREFIX.'_responsive');
					$css = str_replace("[]", chr(13), stripslashes($css));

					echo '<form method="post" action=""> ';
					echo '<input type="submit" class="button-primary" style="float:right;" value="'.__('Speichern', 'cp-communitie').'">';

					echo __('Diese Stile wirken sich auf die Ausgabe aus, wenn Deine Webseite auf Tablets und Telefonen angezeigt wird.', 'cp-communitie');

					echo '<input type="hidden" name="cpcommunitie_update" value="responsive">';

					echo '<table class="widefat" style="clear: both; margin-top:25px">';
					echo '<tbody>';
					echo '<tr>';
					echo '<td>';
					echo '<textarea id="css" name="css" style="width:100%;height: 600px;">';
					echo $css;
					echo '</textarea>';
					echo '</td>';
					echo '</tr>';
					echo '</tbody>';
					echo '</table>';
					
					echo '</form>';
					
				}

				// CSS
				if ($view == "css") {

					$css = get_option(CPC_OPTIONS_PREFIX.'_css');
					$css = str_replace("[]", chr(13), stripslashes($css));

					echo '<form method="post" action=""> ';
					echo __('Hier eingegebene Stile haben Vorrang vor verknüpften Stylesheets, nicht aber <a href="admin.php?page=cpcommunitie_styles&view=responsive">responsive Stile</a>.', 'cp-communitie');
					echo '<input type="submit" class="button-primary" style="float:right;" value="'.__('Speichern', 'cp-communitie').'">';

					echo '<input type="hidden" name="cpcommunitie_update" value="CSS">';

					echo '<table class="widefat" style="clear: both; margin-top:25px">';
					echo '<tbody>';
					echo '<tr>';
					echo '<td style="width:60%">';
					echo '<textarea id="css" name="css" style="width:100%;height: 600px;">';
					echo $css;
					echo '</textarea>';
					echo '</td>';
					echo '<td>';
						echo '<table class="widefat">';
						echo '<tr>';
						echo '<td style="font-weight:bold">'.__('Anmerkungen', 'cp-communitie').'</td>';
						echo '</tr>';
						echo '<tbody>';
						echo '<tr><td>';
						echo __('Um die Dinge zu beschleunigen, warum öffnest Du nicht ein neues Fenster und aktualisieren es jedes Mal, wenn Du hier eine Änderung speicherst?', 'cp-communitie');
						echo '</td></tr>';
						echo '<tr><td>';
						echo sprintf(__('CSS überschreibt die %s-Stile (andere Registerkarte), aber Dein Design hat möglicherweise Vorrang.', 'cp-communitie'), CPC_WL);
						echo '</td></tr>';
						echo '<tr><td>';
						echo __('Wenn ein Stil nicht zutrifft, versuche es mit !important dahinter. zB: color:red !important;', 'cp-communitie');
						echo '</td></tr>';
						echo '<tr><td>';
						echo __('Weitere Hilfe und Beispiele findest Du unter <a href="https://cp-community.n3rds.work/cpc_handbuch/styling-cp-community">Styling CP Community</a>.', 'cp-communitie');
						echo '</td></tr>';
						echo '</tbody>';
						echo '</table>';
					echo '</td>';
					echo '</tr>';
					echo '</tbody>';
					echo '</table>';
					
					echo '</form>';
					
				}
			
				// STYLES
				if ($view == "styles") {
			
						?> 

					<form method="post" action=""> 
					<input type="hidden" name="cpcommunitie_update" value="Y">

					<table class="form-table __cpc__admin_table"> 

					<tr valign="top"> 
					<td scope="row"><label for="use_styles"><?php echo __('Stile verwenden?', 'cp-communitie'); ?></label></td>
					<td>
					<input type="checkbox" name="use_styles" id="use_styles" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_styles') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Aktivieren, um Stile auf dieser Seite zu verwenden, deaktivieren, um sich auf Stylesheets zu verlassen', 'cp-communitie'); ?></span></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="fontfamily"><?php echo __('Fließtext', 'cp-communitie'); ?></label></td> 
					<td><input name="fontfamily" type="text" id="fontfamily" value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_fontfamily')); ?>"/> 
					<span class="description"><?php echo __('Schriftfamilie für Fließtext', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="fontsize"></label></td> 
					<td><input name="fontsize" type="text" id="fontsize" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_fontsize'); ?>"/> 
					<span class="description"><?php echo __('Schriftgröße in Pixel für Fließtext', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="headingsfamily"><?php echo __('Überschriften', 'cp-communitie'); ?></label></td> 
					<td><input name="headingsfamily" type="text" id="headingsfamily" value="<?php echo stripslashes(get_option(CPC_OPTIONS_PREFIX.'_headingsfamily')); ?>"/> 
					<span class="description"><?php echo __('Schriftfamilie für Überschriften und großen Text', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="headingssize"></label></td> 
					<td><input name="headingssize" type="text" id="headingssize" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_headingssize'); ?>"/> 
					<span class="description"><?php echo __('Schriftgröße in Pixel für Überschriften und großen Text', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="main_background"><?php echo __('Haupthintergrund', 'cp-communitie'); ?></label></td> 
					<td><input name="main_background" type="text" id="main_background" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_main_background'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>

					<span class="description"><?php echo __('Haupthintergrundfarbe (z. B. neues/bearbeitetes Forumsthema/Beitrag)', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="label"><?php echo __('Labels', 'cp-communitie'); ?></label></td> 
					<td><input name="label" type="text" id="label" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_label'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Farbe der Textbeschriftungen außerhalb der Forenbereiche', 'cp-communitie'); ?></span></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="text_color"><?php echo __('Textfarbe', 'cp-communitie'); ?></label></td> 
					<td><input name="text_color" type="text" id="text_color" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_text_color'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Primäre Textfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="text_color_2"></label></td> 
					<td><input name="text_color_2" type="text" id="text_color_2" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_text_color_2'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Sekundäre Textfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="link"><?php echo __('Links', 'cp-communitie'); ?></label></td> 
					<td><input name="link" type="text" id="link" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_link'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Linkfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="link_hover"</label></td> 
					<td><input name="link_hover" type="text" id="link_hover" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_link_hover'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Linkfarbe bei Hover', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="underline"><?php echo __('Unterstrichen?', 'cp-communitie'); ?></label></td> 
					<td>
					<select name="underline" id="underline"> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_underline')=='') { echo "selected='selected'"; } ?> value=''><?php echo __('Nein', 'cp-communitie'); ?></option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_underline')=='on') { echo "selected='selected'"; } ?> value='on'><?php echo __('Ja', 'cp-communitie'); ?></option> 
					</select> 
					<span class="description"><?php echo __('Ob Links unterstrichen sind oder nicht', 'cp-communitie'); ?></span></td> 
					</tr> 
			
					<tr valign="top"> 
					<td scope="row"><label for="border_radius"><?php echo __('Ecken', 'cp-communitie'); ?></label></td> 
					<td>
					<select name="border_radius" id="border_radius"> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='0') { echo "selected='selected'"; } ?> value='0'>0 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='1') { echo "selected='selected'"; } ?> value='1'>1 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='2') { echo "selected='selected'"; } ?> value='2'>2 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='3') { echo "selected='selected'"; } ?> value='3'>3 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='4') { echo "selected='selected'"; } ?> value='4'>4 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='5') { echo "selected='selected'"; } ?> value='5'>5 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='6') { echo "selected='selected'"; } ?> value='6'>6 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='7') { echo "selected='selected'"; } ?> value='7'>7 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='8') { echo "selected='selected'"; } ?> value='8'>8 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='9') { echo "selected='selected'"; } ?> value='9'>9 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='10') { echo "selected='selected'"; } ?> value='10'>10 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='11') { echo "selected='selected'"; } ?> value='11'>11 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='12') { echo "selected='selected'"; } ?> value='12'>12 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='13') { echo "selected='selected'"; } ?> value='13'>13 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='14') { echo "selected='selected'"; } ?> value='14'>14 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_border_radius')=='15') { echo "selected='selected'"; } ?> value='15'>15 Pixel</option> 
					</select> 
					<span class="description"><?php echo __('Abgerundeter Eckenradius (nicht in allen Browsern unterstützt)', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bigbutton_background"><?php echo __('Schaltflächen', 'cp-communitie'); ?></label></td> 
					<td><input name="bigbutton_background" type="text" id="bigbutton_background" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_bigbutton_background'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Hintergrundfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bigbutton_background_hover"></label></td> 
					<td><input name="bigbutton_background_hover" type="text" id="bigbutton_background_hover" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_bigbutton_background_hover'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Hintergrundfarbe bei Hover', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bigbutton_color"></label></td> 
					<td><input name="bigbutton_color" type="text" id="bigbutton_color" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_bigbutton_color'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Textfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bigbutton_color_hover"></label></td> 
					<td><input name="bigbutton_color_hover" type="text" id="bigbutton_color_hover" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_bigbutton_color_hover'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Textfarbe bei Hover', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bg_color_1"><?php echo __('Tabelle', 'cp-communitie'); ?></label></td> 
					<td><input name="bg_color_1" type="text" id="bg_color_1" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_bg_color_1'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Primärfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bg_color_2"></label></td> 
					<td><input name="bg_color_2" type="text" id="bg_color_2" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_bg_color_2'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Zeilenfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bg_color_3"></label></td> 
					<td><input name="bg_color_3" type="text" id="bg_color_3" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_bg_color_3'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Alternative Zeilenfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="table_rollover"></label></td> 
					<td><input name="table_rollover" type="text" id="table_rollover" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_table_rollover'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Zeilenfarbe bei Hover', 'cp-communitie'); ?></span></td> 
					</tr> 
		
					<tr valign="top"> 
					<td scope="row"><label for="table_border"></label></td> 
					<td>
					<select name="table_border" id="table_border"> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_table_border')=='0') { echo "selected='selected'"; } ?> value='0'>0 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_table_border')=='1') { echo "selected='selected'"; } ?> value='1'>1 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_table_border')=='2') { echo "selected='selected'"; } ?> value='2'>2 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_table_border')=='3') { echo "selected='selected'"; } ?> value='3'>3 Pixel</option> 
					</select> 
					<span class="description"><?php echo __('Rahmengrösse', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="row_border_style"><?php echo __('Tabelle/Zeilen', 'cp-communitie'); ?></label></td> 
					<td>
					<select name="row_border_style" id="row_border_styledefault_role"> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_row_border_style')=='dotted') { echo "selected='selected'"; } ?> value='dotted'><?php echo __('Gepunktet', 'cp-communitie'); ?></option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_row_border_style')=='dashed') { echo "selected='selected'"; } ?> value='dashed'><?php echo __('Gestrichelt', 'cp-communitie'); ?></option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_row_border_style')=='solid') { echo "selected='selected'"; } ?> value='solid'><?php echo __('Solide', 'cp-communitie'); ?></option> 
					</select> 
					<span class="description"><?php echo __('Rahmenstil zwischen Reihen', 'cp-communitie'); ?></span></td> 
					</tr> 
		
					<tr valign="top"> 
					<td scope="row"><label for="row_border_size"></label></td> 
					<td>
					<select name="row_border_size" id="row_border_size"> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_row_border_size')=='0') { echo "selected='selected'"; } ?> value='0'>0 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_row_border_size')=='1') { echo "selected='selected'"; } ?> value='1'>1 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_row_border_size')=='2') { echo "selected='selected'"; } ?> value='2'>2 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_row_border_size')=='3') { echo "selected='selected'"; } ?> value='3'>3 Pixel</option> 
					</select> 
					<span class="description"><?php echo __('Rahmengröße zwischen Zeilen', 'cp-communitie'); ?></span></td> 
					</tr> 
		
					<tr valign="top"> 
					<td scope="row"><label for="replies_border_size"><?php echo __('Andere Rahmen', 'cp-communitie'); ?></label></td> 
					<td>
					<select name="replies_border_size" id="replies_border_size"> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_replies_border_size')=='0') { echo "selected='selected'"; } ?> value='0'>0 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_replies_border_size')=='1') { echo "selected='selected'"; } ?> value='1'>1 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_replies_border_size')=='2') { echo "selected='selected'"; } ?> value='2'>2 Pixel</option> 
						<option <?php if ( get_option(CPC_OPTIONS_PREFIX.'_replies_border_size')=='3') { echo "selected='selected'"; } ?> value='3'>3 Pixel</option> 
					</select> 
					<span class="description"><?php echo __('Für neue Themen/Antworten und Themenantworten', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="__cpc__categories_background"><?php echo __('Sonstiges', 'cp-communitie'); ?></label></td> 
					<td><input name="__cpc__categories_background" type="text" id="__cpc__categories_background" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_categories_background'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Hintergrundfarbe zB der aktuellen Kategorie', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="categories_color"></label></td> 
					<td><input name="categories_color" type="text" id="categories_color" class="cpc_pickColor" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_categories_color'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Textfarbe', 'cp-communitie'); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td colspan="2"><h3><?php echo __('Forenstile', 'cp-communitie'); ?></h3></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="closed_opacity"><?php echo __('Geschlossene Themen', 'cp-communitie'); ?></label></td> 
					<td><input name="closed_opacity" type="text" id="closed_opacity" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_closed_opacity'); ?>"  /> 
					<?php
					$closed_word = get_option(CPC_OPTIONS_PREFIX.'_closed_word');
					?>
					<span class="description"><?php echo sprintf(__('Deckkraft von Themen mit {%s} im Betreff (zwischen 0.0 und 1.0)', 'cp-communitie'), $closed_word); ?></span></td> 
					</tr> 

					</table> 
					<br />
	 
					<h2><?php echo __('Stilvorlagen', 'cp-communitie'); ?></h2>
						
					<p><?php echo __('Gib zum Speichern als neue Stilvorlage unten einen Namen ein, andernfalls lasse das Feld leer.', 'cp-communitie'); ?></p>

					<p>
					<?php echo __('Speichern als:', 'cp-communitie'); ?>
					<input type='text' id='style_save_as' name='style_save_as' value='<?php if (isset($style_save_as)) { echo str_replace("'", "&apos;", stripslashes($style_save_as)); } ?>' />
					<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Speichern', 'cp-communitie') ?>" /> 
					</p>
					</form>
						
					<?php
					$styles_lib = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'cpcommunitie_styles ORDER BY title');
					if ($styles_lib) {
						
						echo '<table class="widefat" style="width:450px">';
						echo '<thead>';
						echo '<tr>';
						echo '<th style="font-size:1.2em">'.__('Stilvorlage laden', 'cp-communitie').'</th>';
						echo '<th style="font-size:1.2em"></th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
						foreach ($styles_lib as $style_lib)
						{
							echo '<form method="post" action="">';
							echo "<input type='hidden' name='sid' value='".$style_lib->sid."' />";
							echo '<tr valign="top"><td>';
								echo stripslashes($style_lib->title);
							echo "</td><td style='text-align:right'>";
								echo "<input type='submit' id='style_save_as_button' style='margin-right:10px;' class='button' value='".__('Laden', 'cp-communitie')."' />";
								echo "<a class='delete' href='admin.php?page=cpcommunitie_styles&delstyle=".$style_lib->sid."'>".__('Löschen', 'cp-communitie')."</a>";
							echo "</td>";
							
							echo "</tr>";
							echo "</form>";
						}
						echo "</tbody></table>";
					}
					?>
					<p style='clear:both;'><br />
					<?php echo __("NB. Wenn die Änderungen nicht den oben genannten folgen, überschreibe sie möglicherweise mit dem Design-Stylesheet.", 'cp-communitie') ?>
					</p>
	
					<?php	
				}

			echo '</div>';
	
	 	echo '</div>'; // End of Styles 

 	echo '</div>'; // End of wrap

} 	


function __cpc__mail_messages_menu() {

	global $wpdb;

	if (isset($_GET['mail_mid_del'])) {

		if (__cpc__safe_param($_GET['mail_mid_del'])) {
			// Update
			$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_mail WHERE mail_mid = %d", $_GET['mail_mid_del'] ) );
		} else {
			echo "BAD PARAMETER PASSED: ".$_GET['mail_mid_del'];
		}
		
	}

  	echo '<div class="wrap">';
  	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s-Verwaltung', 'cp-communitie'), CPC_WL).'</h2><br />';
		__cpc__show_manage_tabs_header('messages');
	  			
	  	$all = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->base_prefix."cpcommunitie_mail"); 
		// Paging info
		$showpage = 0;
		$pagesize = 20;
		$numpages = floor($all / $pagesize);
		if ($all % $pagesize > 0) { $numpages++; }
	  	if (isset($_GET['showpage']) && $_GET['showpage']) { $showpage = $_GET['showpage']-1; } else { $showpage = 0; }
	  	if ($showpage >= $numpages) { $showpage = $numpages-1; }
		$start = ($showpage * $pagesize);		
		if ($start < 0) { $start = 0; }  
				
		// Query
		$sql = "SELECT m.* FROM ".$wpdb->base_prefix."cpcommunitie_mail m ";
		$sql .= "ORDER BY m.mail_mid DESC ";
		$sql .= "LIMIT ".$start.", ".$pagesize;
		$messages = $wpdb->get_results($sql);
				
		// Pagination (top)
		echo __cpc__pagination($numpages, $showpage, "admin.php?page=__cpc__mail_messages_menu&showpage=");
		
		echo '<br /><table class="widefat">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>ID</td>';
		echo '<th>'.__('Von', 'cp-communitie').'</th>';
		echo '<th>'.__('An', 'cp-communitie').'</th>';
		echo '<th>'.__('Betreff', 'cp-communitie').'</th>';
		echo '<th>'.__('Gesendet', 'cp-communitie').'</th>';
		echo '<th>'.__('Aktion', 'cp-communitie').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<th>ID</th>';
		echo '<th>'.__('Von', 'cp-communitie').'</th>';
		echo '<th>'.__('An', 'cp-communitie').'</th>';
		echo '<th>'.__('Betreff', 'cp-communitie').'</th>';
		echo '<th>'.__('Gesendet', 'cp-communitie').'</th>';
		echo '<th>'.__('Aktion', 'cp-communitie').'</th>';
		echo '</tr>';
		echo '</tfoot>';
		echo '<tbody>';
		
		echo '<style>.mail_rollover:hover { background-color: #ccc; } </style>';

		if ($messages) {
			
			foreach ($messages as $message) {
	
				echo '<tr class="mail_rollover">';
				echo '<td valign="top" style="width: 30px">'.$message->mail_mid.'</td>';
				echo '<td valign="top" style="width: 100px">'.__cpc__profile_link($message->mail_from).'</td>';
				echo '<td valign="top" style="width: 100px">'.__cpc__profile_link($message->mail_to).'</td>';
				echo '<td valign="top" style="width: 200px; text-align:center;">';
				$preview = stripslashes($message->mail_subject);
				$preview_length = 150;
				if ( strlen($preview) > $preview_length ) { $preview = substr($preview, 0, $preview_length)."..."; }
				echo '<div style="float: left;">';
				echo '<a class="show_full_message" id="'.$message->mail_mid.'" style="cursor:pointer;margin-left:6px;">';
				echo $preview;
				echo '</a></div>';
				echo '</td>';
				echo '<td valign="top" style="width: 150px">'.$message->mail_sent.'</td>';
				echo '<td valign="top" style="width: 50px">';
				$showpage = (isset($_GET['showpage'])) ? $_GET['showpage'] : 0;
				echo "<span class='trash delete'><a href='admin.php?page=__cpc__mail_messages_menu&action=message_del&showpage=".$showpage."&mail_mid_del=".$message->mail_mid."'>".__('Müll', 'cp-communitie')."</a></span>";
				echo '</td>';
				echo '</tr>';			
	
			}
		} else {
			echo '<tr><td colspan="6">&nbsp;</td></tr>';
		}

		echo '</tbody>';
		echo '</table>';
	
		// Pagination (bottom)
		echo __cpc__pagination($numpages, $showpage, "admin.php?page=__cpc__mail_messages_menu&showpage=");

		__cpc__show_manage_tabs_header_end();		
		
	echo '</div>'; // End of wrap div

}

function __cpc__mail_menu() {

	global $wpdb, $current_user;

	// See if the user has posted forum settings
	if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__mail_menu' ) {
		$mail_all = (isset($_POST[ 'mail_all' ])) ? $_POST[ 'mail_all' ] : '';
		
		// Update database
		update_option(CPC_OPTIONS_PREFIX.'_mail_all', $mail_all);

	}
	
	if ( isset($_POST['bulk_message']) ) {

		$cnt = 0;

		$subject = $_POST['bulk_subject'];
		$message =$_POST['bulk_message'];
		
		if ($subject == '' || $message == '') {
			echo "<div class='error'><p>".__('Bitte fülle die Felder Betreff und Nachricht aus.', 'cp-communitie').".</p></div>";
		} else {

			if (isset($_POST['roles'])) {
		   		$range = array_keys($_POST['roles']);
		   		$include_roles = '';
	   			foreach ($range as $key) {
					  $include_roles .= $_POST['roles'][$key].',';
		   		}
					$include_roles = str_replace('', ' ', $include_roles);
			} else {
				$include_roles = '';
			}

			// Chosen at least one ClassicPress role?
			if ($include_roles != '') {

		  	$url = __cpc__get_url('mail');	
	
				$sql = "SELECT * FROM ".$wpdb->base_prefix."users";
				$members = $wpdb->get_results($sql);
			
				foreach ($members as $member) {

					// Get this member's WP role and check in permitted list
					$the_user = get_userdata( $member->ID );
					$capabilities = $the_user->{$wpdb->prefix . 'capabilities'};
		
					$user_role = 'NONE';
					if ( !isset( $wp_roles ) )
						$wp_roles = new WP_Roles();

					if ($capabilities) {
						foreach ( $wp_roles->role_names as $role => $name ) {
							if ( array_key_exists( $role, $capabilities ) ) {
								$user_role = str_replace(' ', '', $role);
							}
						}
					}
								
					// Check in this topics category level
					if (strpos(strtolower($include_roles), 'everyone,') !== FALSE || strpos(strtolower($include_roles), $user_role.',') !== FALSE) {	
				
						// Send mail
						if ( $rows_affected = $wpdb->prepare( $wpdb->insert( $wpdb->base_prefix . "cpcommunitie_mail", array( 
						'mail_from' => $current_user->ID, 
						'mail_to' => $member->ID, 
						'mail_sent' => date("Y-m-d H:i:s"), 
						'mail_subject' => $subject,
						'mail_message' => $message
						 ) ), '' ) ) {
					 		$cnt++;
				 		}
		
						$mail_id = $wpdb->insert_id;
				
						// Filter to allow further actions to take place
						apply_filters ('__cpc__sendmessage_filter', $member->ID, $current_user->ID, $current_user->display_name, $mail_id);
			
						// Send real email if chosen
						if ( __cpc__get_meta($member->ID, 'notify_new_messages') ) {
		
							$body = "<h1>".$subject."</h1>";
							$body .= "<p><a href='".$url.__cpc__string_query($url)."mid=".$mail_id."'>".__("Gehe zu Mail", 'cp-communitie')."...</a></p>";
							$body .= "<p>";
							$body .= $message;
							$body .= "</p>";
							$body .= "<p><em>";
							$body .= $current_user->display_name;
							$body .= "</em></p>";
				
							$body = str_replace(chr(13), "<br />", $body);
							$body = str_replace("\\r\\n", "<br />", $body);
							$body = str_replace("\\", "", $body);
		
							// Send real email
							if (isset($_POST['bulk_email'])) {
								__cpc__sendmail($member->user_email, __('Neue Mail-Nachricht', 'cp-communitie'), $body);
							}
						}
					}		
				}
			
				echo "<div class='updated'><p>";
				if (isset($_POST['bulk_email'])) {
					echo sprintf(__('Massennachricht an %d Mitglieder und an ihre E-Mail-Adressen gesendet.', 'cp-communitie'), $cnt);
				} else {
					echo sprintf(__('Massennachricht an %d Mitglieder gesendet (aber nicht an ihre E-Mail-Adressen).', 'cp-communitie'), $cnt);
				}
				echo "</p></div>";	
				$subject = '';
				$message = '';			
			} else {

				echo "<div class='error'><p>".__('Bitte wähle mindestens eine ClassicPress-Rolle aus.', 'cp-communitie').".</p></div>";

			}
		}
	} else {
		$subject = '';
		$message = '';
	}

	// Get config data to show
	$mail_all = get_option(CPC_OPTIONS_PREFIX.'_mail_all');
	
  	echo '<div class="wrap">';
  	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';
	
		__cpc__show_tabs_header('mail');
		?>
			
			<form method="post" action=""> 
			<input type="hidden" name="cpcommunitie_update" value="__cpc__mail_menu">
	
			<table class="form-table __cpc__admin_table"> 
			
			<tr><td colspan="2"><h2><?php _e('Einstellungen', 'cp-communitie') ?></h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="mail_all"><?php echo __('Mail an alle', 'cp-communitie'); ?></label></td>
			<td>
			<input type="checkbox" name="mail_all" id="mail_all" <?php if ($mail_all == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Mail an alle Mitglieder zulassen, auch wenn es kein Freund ist?', 'cp-communitie'); ?></span></td> 
			</tr> 
															
			</table> 	
		 
			<p class="submit" style='margin-left:6px;'> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
			</p> 
			</form> 

		
		<?php
		echo '<div style="margin-left:10px">';
		echo '<h2>'.__('Massenmail versenden', 'cp-communitie').'</h2>';
		echo '<p>'.sprintf(__('Sende eine Nachricht von Dir (%s) an alle Mitglieder dieser Webseite - wenn Du ClassicPress MultiSite betreibst, bedeutet dies alle Mitglieder in Deinem Webseiten-Netzwerk.', 'cp-communitie'), $current_user->display_name).'</p>';
		echo '<form method="post" action="">';
		echo '<strong>'.__('Betreff', 'cp-communitie').'</strong><br />';
		echo '<textarea name="bulk_subject" style="width:500px; height:23px; margin-bottom:15px; overflow:hidden;">'.$subject.'</textarea><br />';
		echo '<strong>'.__('Wähle die einzuschließenden ClassicPress-Rollen aus', 'cp-communitie').'</strong><br />';
	  echo '<div style="margin:10px">';
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
				echo '<input type="checkbox" name="roles[]" value="everyone"> '.__('Alle Benutzer', 'cp-communitie').'<br />';
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="roles[]" value="'.$role['name'].'"';
					echo '> '.$role['name'].'<br />';
				}			
		echo '</div>';
		echo '<strong>'.__('Nachricht', 'cp-communitie').'</strong><br />';
		echo '<textarea name="bulk_message" style="width:500px; height:200px;">'.$message.'</textarea><br />';
		echo '<p><em>'.__('Du kannst HTML einschließen.', 'cp-communitie').'</em></p>';
		echo '<input type="checkbox" name="bulk_email" CHECKED> '.__('Es soll interne Post versendet werden, aber auch E-Mail-Benachrichtigungen versendet werden?', 'cp-communitie');
		echo '<br /><em>'.__('Sei vorsichtig mit den Einschränkungen Deines Hosting-Providers. Mitglieder, die keine E-Mail-Benachrichtigungen wünschen, erhalten keine.', 'cp-communitie').'</em><br /><br />';
		echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Senden', 'cp-communitie').'" />';
		echo '</form></div>';

		?>
		<table style="margin-left:10px; margin-top:10px;">						
			<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
			<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-mail]</td>
				<td><?php echo __('Zeige die Mail-Seite an.', 'cp-communitie'); ?></td></tr>
		</table>
		
		<?php		
		
		__cpc__show_tabs_header_end();

	echo '</div>';
	

}

function __cpc__members_menu() {
	
	global $wpdb;

	// See if the user has posted notification bar settings
	if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__members_menu' ) {

		$dir_atoz_order = (isset($_POST['dir_atoz_order'])) ? $_POST['dir_atoz_order'] : '';
		$show_dir_buttons = (isset($_POST['show_dir_buttons'])) ? $_POST['show_dir_buttons'] : '';
		$dir_page_length = (isset($_POST['dir_page_length']) && $_POST['dir_page_length'] != '') ? $_POST['dir_page_length'] : '25';
		$dir_full_ver = (isset($_POST['dir_full_ver']) && $_POST['dir_full_ver'] != '') ? $_POST['dir_full_ver'] : '';
		$dir_hide_public = (isset($_POST['dir_hide_public']) && $_POST['dir_hide_public'] != '') ? $_POST['dir_hide_public'] : '';
		
		
		update_option(CPC_OPTIONS_PREFIX.'_dir_atoz_order', $dir_atoz_order);
		update_option(CPC_OPTIONS_PREFIX.'_show_dir_buttons', $show_dir_buttons);
		update_option(CPC_OPTIONS_PREFIX.'_dir_page_length', $dir_page_length);
		update_option(CPC_OPTIONS_PREFIX.'_dir_full_ver', $dir_full_ver);
		update_option(CPC_OPTIONS_PREFIX.'dir_hide_public', $dir_hide_public);
		

		// Included roles
		if (isset($_POST['dir_level'])) {
	   		$range = array_keys($_POST['dir_level']);
	   		$level = '';
   			foreach ($range as $key) {
				$level .= $_POST['dir_level'][$key].',';
	   		}
		} else {
			$level = '';
		}

		update_option(CPC_OPTIONS_PREFIX.'_dir_level', serialize($level));
		
		// Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Gespeichert', 'cp-communitie').".</p></div>";
		
	}

	// Get values to show
	$show_dir_buttons = get_option(CPC_OPTIONS_PREFIX.'_show_dir_buttons');
	$dir_page_length = get_option(CPC_OPTIONS_PREFIX.'_dir_page_length');
	
  	echo '<div class="wrap">';
  	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';
	
		__cpc__show_tabs_header('directory');
		?>

			<form method="post" action=""> 
			<input type="hidden" name="cpcommunitie_update" value="__cpc__members_menu">

			<table class="form-table __cpc__admin_table">

			<tr><td colspan="2"><h2><?php _e('Einstellungen', 'cp-communitie') ?></h2></td></tr>
			
			<tr valign="top">
			<td scope="row"><label for="dir_atoz_order"><?php echo __('Standardansicht', 'cp-communitie'); ?></label></td> 
			<td>
			<select name="dir_atoz_order">
				<option value='last_activity'<?php if (get_option(CPC_OPTIONS_PREFIX.'_dir_atoz_order') == 'last_activity') { echo ' SELECTED'; } ?>><?php echo __('Zuletzt aktiv', 'cp-communitie'); ?></option>
				<option value='display_name'<?php if (get_option(CPC_OPTIONS_PREFIX.'_dir_atoz_order') == 'display_name') { echo ' SELECTED'; } ?>><?php echo __('Anzeigename', 'cp-communitie'); ?></option>
				<option value='surname'<?php if (get_option(CPC_OPTIONS_PREFIX.'_dir_atoz_order') == 'surname') { echo ' SELECTED'; } ?>><?php echo __('Nachname (falls in display_name eingetragen)', 'cp-communitie'); ?></option>
			</select> 
			<span class="description"><?php echo __("Einstiegsansicht des Mitgliederverzeichnisses", 'cp-communitie'); ?></span></td>
			</tr> 		

			<tr valign="top"> 
			<td scope="row"><label for="dir_hide_public"><?php echo __('Privatisieren?', 'cp-communitie') ?></label></td>
			<td>
			<input type="checkbox" name="dir_hide_public" id="dir_hide_public" <?php if (isset($dir_hide_public) && $dir_hide_public == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Aus der öffentlichen Ansicht ausblenden, erfordert eine Anmeldung, um das Verzeichnis anzuzeigen', 'cp-communitie'); ?></span></td> 
			</tr> 
			
			<tr valign="top"> 
			<td scope="row"><label for="dir_full_ver"><?php echo __('Schnellere Suche?', 'cp-communitie') ?></label></td>
			<td>
			<input type="checkbox" name="dir_full_ver" id="dir_full_ver" <?php if (isset($dir_full_ver) && $dir_full_ver == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Verbessert die Suchzeit, aber die Suchergebnisse sind begrenzt und können die Suchergebnisse nicht neu anordnen', 'cp-communitie'); ?></span></td> 
			</tr> 
			
			<tr valign="top"> 
			<td scope="row"><label for="show_dir_buttons"><?php echo __('Mitgliederaktionen einbeziehen?', 'cp-communitie') ?></label></td>
			<td>
			<input type="checkbox" name="show_dir_buttons" id="show_dir_buttons" <?php if ($show_dir_buttons == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Sollen Schaltflächen zum Hinzufügen als Freund oder zum Senden von Mails im Verzeichnis angezeigt werden?', 'cp-communitie'); ?></span></td> 
			</tr> 
			
			<tr valign="top"> 
			<td scope="row"><label for="dir_page_length"><?php echo __('Seitenlänge', 'cp-communitie') ?></label></td> 
			<td><input name="dir_page_length" type="text" id="dir_page_length" style="width:50px" value="<?php echo $dir_page_length; ?>"  /> 
			<span class="description"><?php echo __('Anzahl der Mitglieder, die gleichzeitig im Verzeichnis angezeigt werden', 'cp-communitie'); ?></span></td> 
			</tr> 	

			<tr valign="top"> 
			<td scope="row"><label for="dir_level"><?php echo __('Rollen, die in das Verzeichnis aufgenommen werden sollen', 'cp-communitie') ?></label></td> 
			<td>
			<?php

				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;

				$dir_roles = get_option(CPC_OPTIONS_PREFIX.'_dir_level');

				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="dir_level[]" value="'.$role['name'].'"';
					if (strpos(strtolower($dir_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}	

			?>
			</td>
			
			</tr>

			<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
			
			<tr valign="top"> 
				<td scope="row">
					[<?php echo CPC_SHORTCODE_PREFIX; ?>-members]
				</td>
				<td>
				<?php echo __('Zeigt eine Liste der Mitglieder basierend auf den oben ausgewählten Rollen an.', 'cp-communitie').'<br />'; ?>
				<?php echo '<strong>'.__('Parameter', 'cp-communitie').'</strong><br />'; ?>
				<?php echo __('<div style="width:75px;float:left;">roles:</div>Überschreibe die obigen Rollen und beschränke Dich auf die enthaltenen (kommagetrennt)', 'cp-communitie').'<br />'; ?>
				<?php echo '<strong>'.__('Beispiel', 'cp-communitie').'</strong><br />'; ?>
				<?php echo sprintf(__('[%s-members roles="administrator,subscriber"]', 'cp-communitie'), CPC_SHORTCODE_PREFIX).'<br />'; ?>
				<span class="description"><?php echo __('Du kannst diesen Shortcode (mit unterschiedlichen Parametern) auf mehreren Seiten verwenden.', 'cp-communitie'); ?></span>
				</td>
			</tr>
									
			</table>
		
			<p class="submit" style="margin-left:6px;"> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
			</p> 
			</form> 

	<?php
	__cpc__show_tabs_header_end();
	echo '</div>';
		
}

function __cpc__show_tabs_header($active_tab) {

	if (get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on") {

		include_once(dirname(__FILE__).'/show_tabs_style.php');
	
		$options_active = $active_tab == 'options' ? 'active' : 'inactive';
		$profile_active = $active_tab == 'profile' ? 'active' : 'inactive';
		$forum_active = $active_tab == 'forum' ? 'active' : 'inactive';
		$bar_active = $active_tab == 'panel' ? 'active' : 'inactive';
		$directory_active = $active_tab == 'directory' ? 'active' : 'inactive';
		$mail_active = $active_tab == 'mail' ? 'active' : 'inactive';
		$plus_active = $active_tab == 'plus' ? 'active' : 'inactive';
		$events_active = $active_tab == 'events' ? 'active' : 'inactive';
		$facebook_active = $active_tab == 'facebook' ? 'active' : 'inactive';
		$groups_active = $active_tab == 'groups' ? 'active' : 'inactive';
		$lounge_active = $active_tab == 'lounge' ? 'active' : 'inactive';
		$replybyemail_active = $active_tab == 'replybyemail' ? 'active' : 'inactive';
		$alerts_active = $active_tab == 'alerts' ? 'active' : 'inactive';
		$gallery_active = $active_tab == 'gallery' ? 'active' : 'inactive';
			
		echo '<div id="mail_tabs">';
		echo '<div class="mail_tab nav-tab-'.$options_active.'"><a href="admin.php?page=cpcommunitie_options" class="nav-tab-'.$options_active.'-link">'.__('Optionen', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__profile')) 		echo '<div class="mail_tab nav-tab-'.$profile_active.'"><a href="admin.php?page=cpcommunitie_profile" class="nav-tab-'.$profile_active.'-link">'.__('Profile', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__profile_plus')) 	echo '<div class="mail_tab nav-tab-'.$plus_active.' bronze"><a href="admin.php?page='.CPC_DIR.'/plus_admin.php" class="nav-tab-'.$plus_active.'-link">'.__('Extra', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__forum')) 		echo '<div class="mail_tab nav-tab-'.$forum_active.'"><a href="admin.php?page=cpcommunitie_forum" class="nav-tab-'.$forum_active.'-link">'.__('Forum', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__members')) 		echo '<div class="mail_tab nav-tab-'.$directory_active.'"><a href="admin.php?page=__cpc__members_menu" class="nav-tab-'.$directory_active.'-link">'.__('Verzeichnis', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__mail')) 			echo '<div class="mail_tab nav-tab-'.$mail_active.'"><a href="admin.php?page=__cpc__mail_menu" class="nav-tab-'.$mail_active.'-link">'.__('Mail', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__group')) 		echo '<div class="mail_tab nav-tab-'.$groups_active.' bronze"><a href="admin.php?page='.CPC_DIR.'/groups_admin.php" class="nav-tab-'.$groups_active.'-link">'.__('Gruppen', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__gallery')) 		echo '<div class="mail_tab nav-tab-'.$gallery_active.' bronze"><a href="admin.php?page='.CPC_DIR.'/gallery_admin.php" class="nav-tab-'.$gallery_active.'-link">'.__('Galerie', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__news_main')) 	echo '<div class="mail_tab nav-tab-'.$alerts_active.' bronze"><a href="admin.php?page='.CPC_DIR.'/news_admin.php" class="nav-tab-'.$alerts_active.'-link">'.__('Benachrichtigungen', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__add_notification_bar')) 	echo '<div class="mail_tab nav-tab-'.$bar_active.'"><a href="admin.php?page=cpcommunitie_bar" class="nav-tab-'.$bar_active.'-link">'.__('Panel', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__events_main')) 	echo '<div class="mail_tab nav-tab-'.$events_active.' bronze"><a href="admin.php?page='.CPC_DIR.'/events_admin.php" class="nav-tab-'.$events_active.'-link">'.__('Veranstaltungen', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__facebook')) 		echo '<div class="mail_tab nav-tab-'.$facebook_active.' bronze"><a href="admin.php?page='.CPC_DIR.'/facebook_admin.php" class="nav-tab-'.$facebook_active.'-link">'.__('Facebook', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__mailinglist')) 	echo '<div class="mail_tab nav-tab-'.$replybyemail_active.' bronze"><a href="admin.php?page='.CPC_DIR.'/mailinglist_admin.php" class="nav-tab-'.$replybyemail_active.'-link">'.__('Antwort', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__lounge_main')) 	echo '<div class="mail_tab nav-tab-'.$lounge_active.' bronze"><a href="admin.php?page='.CPC_DIR.'/lounge_admin.php" class="nav-tab-'.$lounge_active.'-link">'.__('Lounge', 'cp-communitie').'</a></div>';
		echo '</div>';
	
		echo '<div id="mail-main">';
		
	}
}

function __cpc__show_tabs_header_end() {

	if (get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on")
		echo '</div>';
	
}	

function __cpc__show_manage_tabs_header($active_tab) {

	if (get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on") {

		include_once(dirname(__FILE__).'/show_tabs_style.php');
		?> <style> .wrap .mail_tab { width: 110px; } </style> <?php
	
		$manage_active = $active_tab == 'manage' ? 'active' : 'inactive';
		$categories_active = $active_tab == 'categories' ? 'active' : 'inactive';
		$posts_active = $active_tab == 'posts' ? 'active' : 'inactive';
		$messages_active = $active_tab == 'messages' ? 'active' : 'inactive';
		$templates_active = $active_tab == 'templates' ? 'active' : 'inactive';
		$settings_active = $active_tab == 'settings' ? 'active' : 'inactive';
		$advertising_active = $active_tab == 'advertising' ? 'active' : 'inactive';
		$thesaurus_active = $active_tab == 'thesaurus' ? 'active' : 'inactive';
		$audit_active = $active_tab == 'audit' ? 'active' : 'inactive';
		
		global $wpdb;
  		$count = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'cpcommunitie_topics'." WHERE topic_approved != 'on'"); 
		if ($count > 0) {
			$count2 = " (".$count.")";
		} else {
			$count2 = "";
		}
					
		echo '<div id="mail_tabs">';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$manage_active.'"><a href="admin.php?page=cpcommunitie_manage" class="nav-tab-'.$manage_active.'-link">'.__('Verwalten', 'cp-communitie').'</a></div>';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$settings_active.'"><a href="admin.php?page=cpcommunitie_settings" class="nav-tab-'.$settings_active.'-link">'.__('Einstellungen', 'cp-communitie').'</a></div>';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$advertising_active.'"><a href="admin.php?page=cpcommunitie_advertising" class="nav-tab-'.$advertising_active.'-link">'.__('Werbung/Anzeigen', 'cp-communitie').'</a></div>';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$thesaurus_active.'"><a href="admin.php?page=cpcommunitie_thesaurus" class="nav-tab-'.$thesaurus_active.'-link">'.__('Thesaurus', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__forum') && current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$categories_active.'"><a href="admin.php?page=cpcommunitie_categories" class="nav-tab-'.$categories_active.'-link">'.__('Kategorien', 'cp-communitie').'</a></div>';
		if (function_exists('__cpc__forum')) echo '<div class="mail_tab nav-tab-'.$posts_active.'"><a href="admin.php?page=cpcommunitie_moderation" class="nav-tab-'.$posts_active.'-link">'.sprintf(__('Forum %s', 'cp-communitie'), $count2).'</a></div>';
		if (function_exists('__cpc__mail') && current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$messages_active.'"><a href="admin.php?page=__cpc__mail_messages_menu" class="nav-tab-'.$messages_active.'-link">'.__('Mail-Nachrichten', 'cp-communitie').'</a></div>';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$templates_active.'"><a href="admin.php?page=cpcommunitie_templates" class="nav-tab-'.$templates_active.'-link">'.__('Vorlagen', 'cp-communitie').'</a></div>';
		if (get_option(CPC_OPTIONS_PREFIX.'_audit') == "on") echo '<div class="mail_tab nav-tab-'.$audit_active.'"><a href="admin.php?page=cpcommunitie_audit" class="nav-tab-'.$audit_active.'-link">'.__('Audit', 'cp-communitie').'</a></div>';
		echo '</div>';
	
		echo '<div id="mail-main">';
		
	}
}

function __cpc__show_manage_tabs_header_end() {

	if (get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on")
		echo '</div>';
	
}	

/* Update option on all blogs as applicable */

function __cpc__update_option($option, $value, $update_network)
{

	if (is_multisite() && $update_network) {	
	
		global $wpdb;
		
		$blogs = $wpdb->get_results("
			SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE site_id = '{$wpdb->siteid}'
			AND archived = '0'
			AND spam = '0'
			AND deleted = '0'
		");
		
		foreach ($blogs as $blog) {
			__cpc__set_options($blog->blog_id, $option, $value);
		}
        
	} else {
	
		update_option($option, $value);	
	}
}

function __cpc__set_options($option, $value, $blog_id = null )
{
    if ($blog_id) {
        switch_to_blog($blog_id);
    }

    update_option($option, $value);
    
    if ($blog_id) {
        restore_current_blog();
    }
}


/* =============== ADD TO ADMIN MENU =============== */

if (is_admin()) {
	add_action('admin_menu', '__cpc__plugin_menu');
}

?>
