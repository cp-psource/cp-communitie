<?php
/*
CP Community Groups
Description: Groups Directory and Page plugin compatible with CP Community. Put [cpcommunitie-groups] and [cpcommunitie-group] on any ClassicPress page.
*/


/* ***************************************************** GROUP PAGE ***************************************************** */

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

global $wpdb;

// [cpcommunitie-group] (wall)
function __cpc__group()  
{  
	        			
	global $wpdb;
	$gid = '';

	if (isset($_GET['gid'])) {
		$gid = $_GET['gid'];
	} else {
		if (isset($_POST['gid'])) {
			$gid = $_POST['gid'];
		}
	}

	if ($gid) {
		$default_page = $wpdb->get_var($wpdb->prepare("SELECT default_page FROM ".$wpdb->prefix . 'cpcommunitie_groups WHERE gid=%d', $gid));
		return __cpc_show_group($default_page);
	} else {
		return 'Keine Gruppen-ID gesendet....';
	}
	
	exit;
		
}

// [cpcommunitie-group-members]
function __cpc__group_members()  
{  

	return __cpc_show_group("members");
	exit;
		
}

// [cpcommunitie-group-settings]
function __cpc__group_settings()  
{  
										
	return __cpc_show_group("settings");
	exit;
		
}


// Adds group page
function __cpc_show_group($page)  
{  

	global $wpdb, $current_user;

	$gid = '';

	if (isset($_GET['gid'])) {
		$gid = $_GET['gid'];
	} else {
		if (isset($_POST['gid'])) {
			$gid = $_POST['gid'];
		}
	}
	
	$group_url = __cpc__get_url('group');
	if (strpos($group_url, '?') !== FALSE) {
		$q = "&";
	} else {
		$q = "?";
	}
	
	// Check if private or public
	$sql = "SELECT private FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d";
	$private = $wpdb->get_var($wpdb->prepare($sql, $gid));

	if (is_user_logged_in()) {
		
		if ($gid != '') {
			
			// Wrapper
			$html = "<div class='__cpc__wrapper'>";
					
				$plugin = CPC_PLUGIN_URL;

				// Group views, therefore considered active, so update last activity
				$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."cpcommunitie_groups SET last_activity = %s WHERE gid = %d", date("Y-m-d H:i:s"), $gid ));

				$group = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . 'cpcommunitie_groups WHERE gid=%d', $gid));

				// Use default layout, or templates?
				if (get_option(CPC_OPTIONS_PREFIX.'_use_group_templates') == "on") {
					$template = get_option(CPC_OPTIONS_PREFIX.'_template_group');
					$template = str_replace("[]", "", stripslashes($template));
				} else {				
					$template = "<div id='group_header_div'><div id='group_header_panel'>";
					$template .= "<div id='group_details'>";
					$template .= "<div id='group_name'>[group_name]</div>";
					$template .= "<div id='group_description'>[group_description]</div>";
					$template .= "<div style='padding-top: 15px;padding-bottom: 15px;'>[actions]</div>";
					$template .= "</div>";
					$template .= "</div>";
					$template .= "<div id='group_photo' class='corners'>[avatar,170]</div>";
					$template .= "</div>";
					$template .= "<div id='group_wrapper'>";
					$template .= "<div id='force_group_page' style='display:none'>[default]</div>";
					$template .= "<div id='group_body_wrapper'>";
					$template .= "[menu_tabs]";
					$template .= "<div id='group_body' class='group_body_full'>[page]</div>";
					$template .= "</div>";
					$template .= "</div>";
				}
						
				// Buttons									
				$buttons = "";
				$member_of = __cpc__member_of($gid);
				
				if (is_user_logged_in()) {
				
					if ($member_of != "yes") {
						
						if ($member_of == "no") {

							// Not a member, or pending, so show join button
							$member_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(gmid) FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id = %d", $gid));
							if ($group->max_members == 0 || $member_count < $group->max_members) {
								if ($group->private != "on") {
									$buttons .='<input type="submit" value="'.__("Gruppe beitreten", 'cp-communitie').'" id="groups_join_button" class="__cpc__button">';
									$buttons .='<p id="groups_join_button_done" style="padding:6px;display:none">'.__('Du bist jetzt Mitglied dieser Gruppe.', 'cp-communitie').'</p>';
								} else {
									$buttons .='<input type="submit" value="'.__("Beitrittsanfrage", 'cp-communitie').'" id="groups_join_button" class="__cpc__button">';
									$buttons .='<p id="groups_join_button_done" style="padding:6px;display:none">'.__('Deine Mitgliedschaft wartet auf die Genehmigung.', 'cp-communitie').'</p>';
								}
							} else {
								$buttons .='<p>'.__('Die Gruppenmitgliedschaft ist voll.', 'cp-communitie').'</p>';
							}

						} else {
							
							// Asked to join, waiting for decision
							$buttons .= "<p>".__("Deine Beitrittsanfrage wartet auf Genehmigung.", 'cp-communitie')."</p>";

						}
									
					} else {

						if (__cpc__group_admin($gid) != "yes") {
							// Is a member, so show leave button (if not an admin)
							$buttons .='<input type="submit" value="'.__("Gruppe verlassen", 'cp-communitie').'" id="groups_leave_button" class="__cpc__button">';
							$buttons .='<p id="groups_leave_button_done" style="padding:6px;display:none">'.__('Du bist kein Mitglied dieser Gruppe mehr.', 'cp-communitie').'</p>';
						}
						
					}

					if (__cpc__group_admin($gid) == "yes" || __cpc__get_current_userlevel() == 5) {
						// Admin, so can delete group
						if (__cpc__get_current_userlevel() == 5) {
							$buttons .= '<input type="submit" value="'.__("Gruppe löschen", 'cp-communitie').'" id="groups_delete_button" class="__cpc__button">';
							$buttons .='<p id="groups_delete_button_done" style="padding:6px;display:none">'.__('Gruppe gelöscht.', 'cp-communitie').'</p>';
						} else {
							$buttons .= '<input type="submit" title="'.$gid.'" value="'.__("Gruppe löschen", 'cp-communitie').'" id="groups_delete_button_request" class="__cpc__button">';
						}
					} 
				
				} else {
				
					$buttons = "";
				
				}
				// Replace Header Codes
				$template = str_replace("[group_name]", stripslashes($group->name), $template);
				$template = str_replace("[group_description]", stripslashes($group->description), $template);
				$template = str_replace("[actions]", $buttons, $template);

				// Avatar
				if (strpos($template, '[avatar') !== FALSE) {
					if (strpos($template, '[avatar]')) {
						$template = str_replace("[avatar]", __cpc__get_group_avatar($gid, 200), $template);						
					} else {
						$x = strpos($template, '[avatar');
						$avatar = substr($template, 0, $x);
						$avatar2 = substr($template, $x+8, 3);
						$avatar3 = substr($template, $x+12, strlen($template)-$x-12);

						$template = $avatar . __cpc__get_group_avatar($gid, $avatar2) . $avatar3;
					}
				}
				
				// Menu
				if (strpos($template, '[menu]') !== false) {
					// vertical menu
					$menu = "";
					$menu .= '<div id="group_menu_all" class="__cpc__group_menu">'.__('Alle Gruppen', 'cp-communitie').'</div>';
					$menu .= '<div id="group_menu_about" class="__cpc__group_menu">'.__('Startseite', 'cp-communitie').'</div>';
					if ($member_of == "yes" || $group->content_private != "on") {
						$menu .= '<div id="group_menu_wall" class="__cpc__group_menu">'.__('Gruppenaktivität', 'cp-communitie').'</div>';
						if ($group->group_forum == "on") {
							$menu .= '<div id="group_menu_forum" class="__cpc__group_menu">'.__('Gruppenforum', 'cp-communitie').'</div>';
						}
						$menu .= '<div id="group_menu_members" class="__cpc__group_menu">'.__('Aktive Mitglieder', 'cp-communitie').'</div>';
					}
					if (__cpc__group_admin($gid) == "yes" || __cpc__get_current_userlevel() == 5) {
						$menu .= '<div id="group_menu_settings" class="__cpc__group_menu">'.__('Gruppeneinstellungen', 'cp-communitie').'</div>';
						if (get_option(CPC_OPTIONS_PREFIX.'_group_invites') == 'on') {
							$menu .= '<div id="group_menu_invites" class="__cpc__group_menu">'.__('Gruppeneinladungen', 'cp-communitie').'</div>';
						}
					}
					$template = str_replace("[menu]", $menu, $template);
				} else {
					// horizontal menu
					$template = str_replace("[menu_tabs]", __cpc__show_group_menu_tabs($gid, $member_of, $group), $template);
				}

				// Body
				if ($member_of == "yes" || $group->content_private != "on") {
					$template = str_replace("[page]", "<img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/busy.gif' />", $template);
					$template = str_replace("[default]", $page, $template);
				} else {
					$private_link = '';
					if (!is_user_logged_in()) {
						$private_link .= " <a href=".wp_login_url( $group_url.$q.'gid='.$gid )." class='simplemodal-login' title='".__("Login", 'cp-communitie')."'>".__("Login", 'cp-communitie').".</a>";
					}
					$template = str_replace("[page]", $private_link, $template);
					$template = str_replace("[default]", "", $template);
				}
				$template .= "<br class='clear' />";
				
				$html .= $template;
					

			$html .= "</div>"; // End of Wrapper
			$html .= "<br class='clear' />";
						
		} else {
			
			$html = __("Gruppe nicht gefunden, tut mir leid.", 'cp-communitie');
		}
		
	} else {
		
		$html = __cpc__show_login_link(__("Du musst Dich <a href='%s'>anmelden</a>, um auf diese Gruppe zuzugreifen.", 'cp-communitie'));
		
	}
	
	// Filter for header
	$html = apply_filters ( '__cpc__group_header_filter', $html, $gid );

	
	return $html;								
	exit;

}  

function __cpc__show_group_menu_tabs($gid, $member_of, $group) {
        	
	global $wpdb, $current_user;

		$structure = get_option(CPC_OPTIONS_PREFIX."_group_menu_structure");

		$str_arr = explode(chr(10), $structure);
				
		$menu = '<ul class="__cpc__dropdown">';

		// Build menu		
		$started_top_level = false;
		foreach($str_arr as $item) {
			
			// Top level menu items
			if (strpos($item, '[') !== false) {
				$item = str_replace('[', '', $item);
				$item = str_replace(']', '', $item);
				if ($started_top_level) {
					$menu .= '</ul></li>';
				}
				$started_top_level = true;

				if ($member_of == "yes" || $group->content_private != "on") {			
					$menu .= '<li class="__cpc__top_menu">'.$item;
				} else {
					$menu .= '<li class="__cpc__top_menu" style="display:none">'.$item;
				}
				$menu .= '<ul class="__cpc__sub_menu">';
			}
			
			// Child item
			if (strpos($item, '=') !== false) {
				list($title,$value) = explode('=', $item);
				$value = str_replace(chr(13), '', $value);
				$i = '';

				switch ($value) {
				case 'welcome' :
					$i = '<li id="group_menu_about" class="__cpc__group_menu">'.$title.'</li>';
					break;
				case 'settings':
					if (__cpc__group_admin($gid) == "yes" || __cpc__get_current_userlevel() == 5) {
						$i = '<li id="group_menu_settings" class="__cpc__group_menu" href="javascript:void(0)">'.$title.'</li>';
					}
					break;
				case 'invites' :
					if (__cpc__group_admin($gid) == "yes" || __cpc__get_current_userlevel() == 5) {
						if (get_option(CPC_OPTIONS_PREFIX.'_group_invites') == 'on')
							$i = '<li id="group_menu_invites" class="__cpc__group_menu" href="javascript:void(0)">'.$title.'</li>';
					}
					break;				
				case 'activity' :
					$i = '<li id="group_menu_wall" class="__cpc__group_menu" href="javascript:void(0)">'.$title.'</li>';
					break;
				case 'forum' :
					if ($group->group_forum == "on")
						$i = '<li id="group_menu_forum" class="__cpc__group_menu" href="javascript:void(0)">'.$title.'</li>';
					break;
				case 'members' :
					$i = '<li id="group_menu_members" class="__cpc__group_menu" href="javascript:void(0)">'.$title.'</li>';
					break;
				default :
					$i = apply_filters ( '__cpc__group_menu_tabs', '', $title, $value, $gid, $member_of, $group);
					break;
				}
				if ($i) $menu .= $i;
			}
			
		}
		if ($started_top_level) {
			$menu .= '</ul></li>';
		}

		$menu .= '<div id="__cpc__menu_tabs_wrapper"></div>';
			
		$menu .= '</ul><div style="clear:both;padding-bottom:20px;"></div>';
				
	return $menu;

}

/* ***************************************************** GROUPS ***************************************************** */

function __cpc__groups() {	
	
	
	global $wpdb, $current_user;
	
	// View (and set tabs)
	if (!isset($_GET['view']) || $_GET['term'] != '') {
		$browse_active = 'active';
		$create_active = 'inactive';
		$view = "browse";
	} 
	if ( isset($_GET['view']) && $_GET['view'] == "create") {
		$browse_active = 'inactive';
		$create_active = 'active';
		$view = "create";
	} 

	$thispage = get_permalink();
	if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }

	$group_url = get_option(CPC_OPTIONS_PREFIX.'_group_url');
	$group_all_create = get_option(CPC_OPTIONS_PREFIX.'_group_all_create');

	if (isset($_GET['page_id']) && $_GET['page_id'] != '') {
		// No Permalink
		$thispage = $group_url;
		$q = "&";
	} else {
		$q = "?";
	}

	if (isset($_GET['term'])) {
		$term = $_GET['term'];
	} else {
		$term = '';
	}

	$html = '<div class="__cpc__wrapper">';

		if ( (is_user_logged_in()) && ($group_all_create == "on" || __cpc__get_current_userlevel() == 5) ) {

			$html .= "<input type='submit' id='show_create_group_button' class='__cpc__button' value='".__("Gruppe erstellen", 'cp-communitie')."'>";

			$html .= "<div id='create_group_form' style='display:none'>";
				$html .= "<div>";
				$html .= "<strong>".__("Name der Gruppe", 'cp-communitie')."</strong><br />";
				$html .= "<input type='text' id='name_of_group' class='new-topic-subject-input' style='width: 98% !important;'>";
				$html .= "</div>";

				$html .= "<div>";
				$html .= "<strong>".__("Beschreibung", 'cp-communitie')."</strong><br />";
				$html .= "<input type='text' id='description_of_group' style='width: 98% !important;'>";
				$html .= "</div>";

				$html .= "<div style='margin-top:10px'>";
				$html .= "<input type='submit' id='create_group_button' class='__cpc__button' value='".__("Erstellen", 'cp-communitie')."'>";
				$html .= "<input type='submit' id='cancel_create_group_button' class='__cpc__button' value='".__("Abbrechen", 'cp-communitie')."'>";
				$html .= "</div>";
			$html .= "</div>";

		}
		
		$html .= "<div id='groups_results'>";
		
		if ( $term != '' ) {
	
			$me = $current_user->ID;
			$page = 1;
			$page_length = 25;
	
			$term = "";
			if (isset($_POST['group'])) { $term .= $_POST['group']; }
			if (isset($_GET['term'])) { $term .= $_GET['term']; }

			$html .= "<div style='padding:0px;'>";
			$html .= '<input type="text" id="group" name="group" autocomplete="off" class="groups_search_box" value="'.$term.'" style="margin-right:10px" />';
			$html .= '<input type="hidden" id="group_id" name="group_id" />';
			$html .= '<input id="groups_go_button" type="submit" class="__cpc__button" value="'.__("Suche", 'cp-communitie').'" />';
			$html .= "</div>";	

	
			$sql = "SELECT g.*, (SELECT COUNT(*) FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id = g.gid) AS member_count
			FROM ".$wpdb->prefix."cpcommunitie_groups g WHERE  
			( g.name LIKE '%".$term."%') OR 
			( g.description LIKE '%".$term."%' )
			ORDER BY group_order, last_activity DESC LIMIT 0,25";
			
			$groups = $wpdb->get_results($sql);


			if ($groups) {
				
				foreach ($groups as $group) {

					if (__cpc__member_of($group->gid) == 'yes') { 
						$html .= "<div class='groups_row row_odd corners'>";
					} else {
						$html .= "<div class='groups_row row corners'>";
					}					
					
						$html .= "<div class='groups_avatar'>";
							$html .= __cpc__get_group_avatar($group->gid, 64);
						$html .= "</div>";

						$html .= "<div class='group_name'>";
						$name = stripslashes($group->name) != '' ? stripslashes($group->name) : __('[Kein Name]', 'cp-communitie');
						$html .= "<a class='row_link' href='".__cpc__get_url('group')."?gid=".$group->gid."'>".$name."</a>";
						$html .= "</div>";
						
						$html .= "<div class='group_member_count'>";
						$html .= __("Mitgliederzahl:", 'cp-communitie')." ".$group->member_count;
						if ($group->last_activity) {
							$html .= '<br /><em>'.__('letzte Aktivität', 'cp-communitie').' '.__cpc__time_ago($group->last_activity)."</em>";
						}
						$html .= "</div>";
					
						$html .= "<div class='group_description'>";
						$html .= $group->description;
						$html .= "</div>";
						
					$html .= "</div>";
					
				}
	
			}
			
		} else {
	
	
			$html .= "<div style='padding:0px;'>";
			$html .= '<input type="text" id="__cpc__group" name="group" autocomplete="off" class="groups_search_box" value="'.$term.'" style="margin-right:10px" />';
			$html .= '<input type="hidden" id="group_id" name="group_id" />';
			$html .= '<input id="groups_go_button" type="submit" class="__cpc__button" value="'.__("Suche", 'cp-communitie').'" />';
			$html .= "</div>";	
	
			
			$html .= "<div id='__cpc__groups'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/busy.gif' /></div>";
			
		}
		
		$html .= "</div>"; // End of Groups Results
		
		if (isset($groups) && !$groups) 
				$html .= "<div style='clear:both'>".__("Keine Gruppe gefunden....", 'cp-communitie')."</div>";
		
	$html .= '</div>'; // End of Wrapper
	
	// Send HTML
	return $html;

}

/* ====================================================== ADMIN ====================================================== */


// Add plugin to admin menu via hook
function __cpc__add_groups_to_admin_menu()
{
	$hidden = get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden' : '';
	add_submenu_page('cpcommunitie_debug'.$hidden, __('Gruppen', 'cp-communitie'), __('Gruppen', 'cp-communitie'), 'manage_options', CPC_DIR.'/groups_admin.php');
}
add_action('__cpc__admin_menu_hook', '__cpc__add_groups_to_admin_menu');

// Add JS scripts to ClassicPress for use
function __cpc__groups_init()
{
}

/* ====================================================== SET SHORTCODE ====================================================== */


add_action('init', '__cpc__groups_init');
add_shortcode(CPC_SHORTCODE_PREFIX.'-groups', '__cpc__groups');  
add_shortcode(CPC_SHORTCODE_PREFIX.'-group', '__cpc__group');  
add_shortcode(CPC_SHORTCODE_PREFIX.'-group-members', '__cpc__group_members');  
add_shortcode(CPC_SHORTCODE_PREFIX.'-group-settings', '__cpc__group_settings');  


?>
