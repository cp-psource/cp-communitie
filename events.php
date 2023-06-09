<?php
/*
CP Community Events
Description: Create public or private events, invite other members, allow others to join, etc!
*/


/* ====================================================================== MAIN =========================================================================== */

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

function __cpc__events_main() {

	global $wpdb, $current_user; 
	
	$html = '<div class="__cpc__wrapper">';

		// Content
		$include = get_option(CPC_OPTIONS_PREFIX."_events_global_list");

		// get events
		$html .= '<div id="__cpc__events_list" style="width:95%">';
		
			
			if (get_option(CPC_OPTIONS_PREFIX."_events_hide_expired")) {
				$hide = "(event_start >= now() OR event_start = '0000-00-00 00:00:00') AND";
			} else {
				$hide = '';
			}
			
			if ($include) {
				$sql = "SELECT e.*, u.ID, u.display_name FROM ".$wpdb->base_prefix."cpcommunitie_events e LEFT JOIN ".$wpdb->base_prefix."users u ON event_owner = ID WHERE ".$hide." event_owner IN (".$include.") AND event_live = %s ORDER BY event_start";
			} else {
				$sql = "SELECT e.*, u.ID, u.display_name FROM ".$wpdb->base_prefix."cpcommunitie_events e LEFT JOIN ".$wpdb->base_prefix."users u ON event_owner = ID WHERE ".$hide." event_live = %s ORDER BY event_start";
			}
			if (get_option(CPC_OPTIONS_PREFIX."_events_sort_order")) $sql .= " DESC";
			$events = $wpdb->get_results($wpdb->prepare($sql, 'on'));

			if (CPC_DEBUG) $html .= $wpdb->last_query;
			
			if ($events) {
				
				if (get_option(CPC_OPTIONS_PREFIX.'_events_calendar') == "calendar") {
					
					// Calendar view

					$html .= '<div id="__cpc__events_calendar"></div>';


				} else {
					
					// List view
										
					foreach ($events as $event) {
						$html .= '<div class="__cpc__event_list_item row">';
						
							if ($event->event_google_map == 'on') {
								$html .= "<div id='event_google_profile_map' style='float:right; margin-left:5px; width:128px; height:128px'>";
								$html .= '<a target="_blank" href="https://maps.google.co.uk/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q='.$event->event_location.'&amp;ie=UTF8&amp;hq=&amp;hnear='.$event->event_location.'&amp;output=embed&amp;z=5" alt="Zum Vergrößern auf die Karte klicken" title="Zum Vergrößern auf die Karte klicken">';
								$html .= '<img src="https://maps.google.com/maps/api/staticmap?center='.$event->event_location.'&zoom=5&size=128x128&maptype=roadmap&markers=color:blue|label:&nbsp;|'.$event->event_location.'&sensor=false" />';
								$html .= "</a></div>";
							}
	
							if ( ($event->event_owner == $current_user->ID) || (__cpc__get_current_userlevel() == 5) ) {
								$html .= "<div class='__cpc__event_list_item_icons'>";
								if ($event->event_live != 'on') {
									$html .= '<div style="font-style:italic;float:right;">'.__('Zum Veröffentlichen bearbeiten', 'cp-communitie').'</div>';
								}
								$html .= "<a href='javascript:void(0)' class='cpcommunitie_delete_event floatright link_cursor' style='display:none;margin-right: 5px' id='".$event->eid."'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/delete.png' /></a>";
								$html .= "<a href='javascript:void(0)' class='__cpc__edit_event floatright link_cursor' style='display:none;margin-right: 5px' id='".$event->eid."'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/edit.png' /></a>";
								$html .= "</div>";
							}
												
							$html .= '<div class="__cpc__event_list_owner">'.__("Hinzugefügt von", 'cp-communitie')." ".__cpc__profile_link($event->ID).'</div>';
							$html .= '<div class="__cpc__event_list_name">'.stripslashes($event->event_name).'</div>';
							$html .= '<div class="__cpc__event_list_location">'.stripslashes($event->event_location).'</div>';
							if ($event->event_enable_places && $event->event_show_max) {
								$sql = "SELECT SUM(tickets) FROM ".$wpdb->base_prefix."cpcommunitie_events_bookings WHERE event_id = %d";
								$taken = $wpdb->get_var($wpdb->prepare($sql, $event->eid));
								$html .= '<div class="__cpc__event_list_places">';
									if ($event->event_max_places-$taken > 0) {
										$html .= __('Tickets übrig:', 'cp-communitie').' '.($event->event_max_places-$taken);
									} else {
										$html .= __('Ausgebucht', 'cp-communitie');
									}
								$html .= '</div>';
							}
							if (isset($event->event_cost) && $event->event_cost !== null) {
								$html .= '<div class="cpcommunitie_event_cost">'.__('Kosten pro Ticket:', 'cp-communitie').' '.$event->event_cost.'</div>';
							}
							$html .= '<div class="__cpc__event_list_description">';
							$html .= stripslashes($event->event_description);
							$html .= '</div>';
							$html .= '<div class="__cpc__event_list_dates">';
								if ($event->event_start != '0000-00-00 00:00:00') {
									$html .= date_i18n("D, d M Y", __cpc__convert_datetime($event->event_start));
								}
								if ($event->event_start != $event->event_end) {
									if ($event->event_end != '0000-00-00 00:00:00') {
										$html .= ' &rarr; ';
										$html .= date_i18n("D, d M Y", __cpc__convert_datetime($event->event_end));
									}
								}
							$html .= '</div>';
							$html .= '<div class="__cpc__event_list_times">';
								if ($event->event_start_hours != 99) {
									$html .= __('Start: ', 'cp-communitie').$event->event_start_hours.":".sprintf('%1$02d', $event->event_start_minutes);
								}
								if ($event->event_end_hours != 99) {
									$html .= ' '.__('Ende: ', 'cp-communitie').$event->event_end_hours.":".sprintf('%1$02d', $event->event_end_minutes);
								}
							$html .= '</div>';
	
							$html .= '<div>';
							if ($event->event_more) {
								$more = str_replace(chr(10), '<br />', stripslashes($event->event_more));
								$html .= '<div id="cpcommunitie_more_'.$event->eid.'" title="'.stripslashes($event->event_name).'" class="__cpc__dialog_content"><div style="text-align:left">'.$more.'</div></div>';
								$html .= '<input type="submit" id="cpcommunitie_event_more" rel="cpcommunitie_more_'.$event->eid.'" class="cpcommunitie-dialog __cpc__button" value="'.__("Mehr Info", 'cp-communitie').'" />';
							}
							if (is_user_logged_in() && $event->event_enable_places) {
									// check to see if already booked
									$sql = "select b.tickets, b.confirmed, b.bid, b.payment_processed, e.event_cost FROM ".$wpdb->base_prefix."cpcommunitie_events_bookings b LEFT JOIN ".$wpdb->base_prefix."cpcommunitie_events e ON b.event_id = e.eid WHERE event_id = %d AND uid = %d";
									$ret = $wpdb->get_row($wpdb->prepare($sql, $event->eid, $current_user->ID));
									if (!$ret || !$ret->tickets) {
										if ($event->event_max_places-$taken > 0)
											$html .= '<input type="submit" id="cpcommunitie_book_event" data-eid="'.$event->eid.'" data-max="'.$event->event_tickets_per_booking.'" class="__cpc__button cpcommunitie_book_event_button" value="'.__("Buchen", 'cp-communitie').'" />';
									} else {
										$html .= '<input type="submit" id="cpcommunitie_cancel_event" data-eid="'.$event->eid.'"  class="__cpc__button cpcommunitie_cancel_event_button" value="'.__("Abbrechen", 'cp-communitie').'" />';
									}
									if ($ret && !$ret->confirmed && !$ret->payment_processed && $ret->tickets && $ret->event_cost)
										$html .= '<input type="submit" id="cpcommunitie_pay_event" data-bid="'.$ret->bid.'" style="margin-left:5px" class="__cpc__button" value="'.__("Zahlung", 'cp-communitie').'" />';
									if ($ret && $ret->tickets ) {
										if ($ret->confirmed) {
											$html .= '<br />'.sprintf(_n('Confirmed by the event organiser for %d ticket.','Confirmed by the event organiser for %d tickets.', $ret->tickets, 'cp-communitie'), $ret->tickets);
										} else {
											$html .= '<br />'.sprintf(_n('Awaiting confirmation from the organiser for %d ticket.','Awaiting confirmation from the organiser for %d tickets.', $ret->tickets, 'cp-communitie'), $ret->tickets);
										}
									}
										
							}
							$html .= '</div>';
							
						$html .= '</div>';
					}
				}
			} else {
				$html .= __('No events yet.', 'cp-communitie');
			}
		
		$html .= '</div>';		
		
	$html .= '</div>';

	// This filter allows others to filter content
	$html = apply_filters ( '__cpc__events_shortcode_filter', $html);
	
	// Send HTML
	return $html;
	
}

/* ===================================================================== ADMIN =========================================================================== */


function __cpc__events_init()
{

}
add_action('init', '__cpc__events_init');




/* ================================================================== SET SHORTCODE ====================================================================== */

if (!is_admin()) {
	add_shortcode(CPC_SHORTCODE_PREFIX.'-events', '__cpc__events_main');  
}

/* ====================================================== HOOKS/FILTERS INTO WORDPRESS/CP Community ====================================================== */

// Add Menu item to Profile Menu through filter provided
// The menu picks up the id of div with id of menu_ (eg: menu_lounge) and will then run
// 'path-to/cp-communitie/ajax/lounge_functions.php' when clicked.
// It will pass $_POST['action'] set to menu_lounge to that file to then be acted upon.

function __cpc__add_events_menu($html,$uid1,$uid2,$privacy,$is_friend,$extended,$share,$extra_class)  
{  
	global $wpdb, $current_user;
	
	// Get included roles
	$dir_levels = strtolower(get_option(CPC_OPTIONS_PREFIX.'_events_profile_include'));
	if (strpos($dir_levels, ' ') !== FALSE) $dir_levels = str_replace(' ', '', $dir_levels);
	if (strpos($dir_levels, '_') !== FALSE) $dir_levels = str_replace('_', '', $dir_levels);

	if (CPC_DEBUG) $html .= 'Events, allowed roles = '.$dir_levels.'<br />';
	
	// Check to see if this member is in the included list of roles
	$user = get_userdata( $current_user->ID );
	$capabilities = $user->{$wpdb->prefix.'capabilities'};
	
	if (CPC_DEBUG) $html .= 'Events, user capabilities = '.$capabilities.'.<br />';

	$include = false;
	if ($capabilities) {
		
		foreach ( $capabilities as $role => $name ) {
			if ($role) {
				$role = strtolower($role);
				$role = str_replace(' ', '', $role);
				$role = str_replace('_', '', $role);
				if (CPC_DEBUG) $html .= 'Checking role '.$role.' against '.$dir_levels.'<br />';
				if (strpos($dir_levels, $role) !== FALSE) $include = true;
			}
		}		 														
	
	}	
	
	if ( ($include) && ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __cpc__get_current_userlevel() == 5) ) {
  
		if ($uid1 == $uid2) {
			if (get_option(CPC_OPTIONS_PREFIX.'_menu_events')) {
				if ($extra_class == '') {
					$html .= '<div id="menu_events" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_events_text')) != '' ? $t :  __('Meine Events', 'cp-communitie')).'</div>';  
				} else {
					$html .= '<div id="menu_events" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_events_text')) != '' ? $t :  __('Meine Events', 'cp-communitie')).'</div>';  
				}
			}
		} else {
			if (get_option(CPC_OPTIONS_PREFIX.'_menu_events_other')) {
				if ($extra_class == '') {
					$html .= '<div id="menu_events" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_events_other_text')) != '' ? $t :  __('Events', 'cp-communitie')).'</div>';  
				} else {
					$html .= '<div id="menu_events" class="__cpc__profile_menu '.$extra_class.'">'.(($t = get_option(CPC_OPTIONS_PREFIX.'_menu_events_other_text')) != '' ? $t :  __('Events', 'cp-communitie')).'</div>';  
				}
			}
		}
	}
	return $html;
}  
add_filter('__cpc__profile_menu_filter', '__cpc__add_events_menu', 9, 8);


function __cpc__add_events_menu_tabs($html,$title,$value,$uid1,$uid2,$privacy,$is_friend,$extended,$share)  
{  
	if ($value == 'events') {
		
		global $wpdb, $current_user;
		
		// Get included roles
		$dir_levels = strtolower(get_option(CPC_OPTIONS_PREFIX.'_events_profile_include'));
		if (strpos($dir_levels, ' ') !== FALSE) $dir_levels = str_replace(' ', '', $dir_levels);
		if (strpos($dir_levels, '_') !== FALSE) $dir_levels = str_replace('_', '', $dir_levels);
	
		if (CPC_DEBUG) $html .= 'Events, allowed roles = '.$dir_levels.'<br />';
		
		// Check to see if this member is in the included list of roles
		$include = false;
		if (is_user_logged_in()) {
			$user = get_userdata( $uid1 );
			$capabilities = $user->{$wpdb->prefix.'capabilities'};
			
			if ($capabilities) {
	
				foreach ( $capabilities as $role => $name ) {
					if ($role) {
						$role = strtolower($role);
						$role = str_replace(' ', '', $role);
						$role = str_replace('_', '', $role);
						if (CPC_DEBUG) $html .= 'Checking role '.$role.' against '.$dir_levels.'<br />';
						if (strpos($dir_levels, $role) !== FALSE) $include = true;
					}
				}		 														
			
			}	
		}
		
		if ( ($include) && ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __cpc__get_current_userlevel() == 5) ) {
			$html .= '<li id="menu_events" class="__cpc__profile_menu" href="javascript:void(0)">'.$title.'</li>';
		}

	}
		
	return $html;
	
}  
add_filter('__cpc__profile_menu_tabs_filter', '__cpc__add_events_menu_tabs', 9, 9);


// Add to admin menu via hook
function __cpc__add_events_to_admin_menu()
{
	$hidden = get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('cpcommunitie_debug'.$hidden, __('Events', 'cp-communitie'), __('Events', 'cp-communitie'), 'manage_options', CPC_DIR.'/events_admin.php');
}
add_action('__cpc__admin_menu_hook', '__cpc__add_events_to_admin_menu');



?>
