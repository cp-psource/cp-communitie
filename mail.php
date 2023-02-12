<?php
/*
CP Community Mail
Description: Mail component for the CP Community suite of plug-ins. Put [cpcommunitie-mail] on any ClassicPress page.
*/

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

function __cpc__mail() {	
	
	global $wpdb, $current_user;
	wp_get_current_user();

	$thispage = get_permalink();
	if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
	$mail_url = get_option(CPC_OPTIONS_PREFIX.'_mail_url');
	$mail_all = get_option(CPC_OPTIONS_PREFIX.'_mail_all');

	if (isset($_GET['page_id']) && $_GET['page_id'] != '') {
		// No Permalink
		$thispage = $mail_url;
		$q = "&";
	} else {
		$q = "?";
	}
	
	$plugin_dir = CPC_PLUGIN_URL;
	
	$html = '';
	
	if (is_user_logged_in()) {

		$inbox_active = 'active';
		$sent_active = 'inactive';
		$compose_active = 'inactive';

		$template = '';
		$template .= '<div id="mail_tabs">';
		$template .= '<div id="cpcommunitie_compose_tab" class="mail_tab nav-tab-'.$compose_active.'"><a href="javascript:void(0)" class="nav-tab-'.$compose_active.'-link" style="text-decoration:none !important;">'.__('Compose', 'cp-communitie').'</a></div>';
		$template .= '<div id="cpcommunitie_inbox_tab" class="mail_tab nav-tab-'.$inbox_active.'"><a href="javascript:void(0)" class="nav-tab-'.$inbox_active.'-link" style="text-decoration:none !important;">'.__('In Box', 'cp-communitie').' <span id="in_unread"></span></a></div>';
		$template .= '<div id="cpcommunitie_sent_tab" class="mail_tab nav-tab-'.$sent_active.'"><a href="javascript:void(0)" class="nav-tab-'.$sent_active.'-link" style="text-decoration:none !important;">'.__('Sent Items', 'cp-communitie').'</a></div>';
		$template .= '</div>';	
		
		$template .= '<div id="mail-main-div">';

			$template .= "<div id='mail_sent_message'></div>";
		
			$template .= "[compose_form]";

			$template .= "<div id='mailbox'>";
				$template .= "<div id='__cpc__search'>";
					$template .= "<input id='search_inbox' type='text' style='width: 160px'>";
					$template .= "<input id='search_inbox_go' class='__cpc__button message_search' type='submit' style='margin-left:10px;' value='".__('Search', 'cp-communitie')."'>";
					$template .= "[unread]";
				$template .= "</div>";
				$template .= "<div>";
					$template .= "<select id='__cpc__mail_bulk_action'>";
					$template .= "<option value=''>".__('Bulk action...', 'cp-communitie').'</option>';
					$template .= "<option value='delete'>".__('Delete checked items', 'cp-communitie').'</option>';
					$template .= "<option id='__cpc__mark_all' value='readall'>".__('Mark all mail as read', 'cp-communitie').'</option>';
					$template .= "<option value='deleteall'>".__('Delete all mail!', 'cp-communitie').'</option>';
					$template .= "<option value='recoverall'>".__('Recover all deleted mail', 'cp-communitie').'</option>';
					$template .= "</select>";
				$template .= "</div>";
				$template .= "<div id='mailbox_list'></div>";
				$template .= "<div id='messagebox'></div>";
			$template .= "</div>";
		
		$template .= '</div>';	
		
		$html .= '<div id="next_message_id" style="display:none">0</div>';
		$html .= '<div class="__cpc__wrapper">'.$template.'</div>';
			
		// Compose Form	
		if (CPC_CURRENT_USER_PAGE == $current_user->ID) {
		
			$compose = '<div id="compose_form" style="display:none">';
			
				$compose .= '<div id="compose_mail_to">';

					$compose .= '<div class="send_button" style="padding:4px;">';
					$compose .= '<input type="submit" id="mail_cancel_button" class="__cpc__button" value="'.__('Cancel', 'cp-communitie').'" />';
					$compose .= '<input type="submit" id="mail_send_button" class="__cpc__button" value="'.__('Send', 'cp-communitie').'" />';
					$compose .= '</div>';
	 	
					$compose .= '<select id="mail_recipient_list">';
					$compose .= '<option class="__cpc__mail_recipient_list_option" value='.$current_user->ID.'>'.$current_user->display_name.'</option>';
	
					if ($mail_all == 'on' || __cpc__get_current_userlevel() == 5) {
						
						$sql = "SELECT u.ID AS friend_to, u.display_name
						FROM ".$wpdb->base_prefix."users u
						ORDER BY u.display_name";

						$friends = $wpdb->get_results($sql);
					
					} else {
						
						$sql = "SELECT f.friend_to, u.display_name
						FROM ".$wpdb->base_prefix."cpcommunitie_friends f 
						INNER JOIN ".$wpdb->base_prefix."users u ON f.friend_to = u.ID 
						WHERE f.friend_from = %d AND f.friend_accepted = 'on' 
						ORDER BY u.display_name";

						$friends = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));	

					}
					
							
					if ($friends) {
						foreach ($friends as $friend) {
							$compose .= '<option class="__cpc__mail_recipient_list_option" value='.$friend->friend_to.'>'.$friend->display_name.'</option>';
						}
					}
					$compose .= '</select>';
	 			$compose .= '</div>';	
				
				$compose .= '<div class="new-topic-subject label">'.__('Subject', 'cp-communitie').'</div>';
 				$compose .= "<input type='text' id='compose_subject' class='new-topic-subject-input' value='' />";
				
				$compose .= '<div id="compose_mail_message">';
					$compose .= '<div class="new-topic-subject label">'.__('Message', 'cp-communitie').'</div>';
					$compose .= '<textarea class="reply-topic-subject-text" id="compose_text"></textarea>';
	 			$compose .= '</div>';
				
				$compose .= '<input type="hidden" id="compose_previous" value="" />';
		
			$compose .= "</div>";

		} else {
			
			$compose = '<div id="compose_form" style="display:none">';
				$compose .= __('New mail can only be sent by this member.', 'cp-communitie').'<br /><br />';
				$compose .= '<input id="mail_cancel_button" type="submit" class="__cpc__button" value="'.__('Back to mail', 'cp-communitie').'" />';
			$compose .= "</div>";
			
			
		}
				
		// Replace template codes
		$html = str_replace("[compose_form]", $compose, stripslashes($html));
		$html = str_replace("[compose]", __("Compose", 'cp-communitie'), stripslashes($html));
		$html = str_replace("[inbox]", __("Inbox", 'cp-communitie'), stripslashes($html));
		$html = str_replace("[sent]", __("Sent", 'cp-communitie'), stripslashes($html));
		$html = str_replace("[unread]", "<input type='checkbox' id='unread_only' /> ".__("Unread only", 'cp-communitie'), stripslashes($html));
		

	} else {
		// Not logged in
		$html .= __('You have to login to access your mail.', 'cp-communitie');
	}
	
	// Send HTML
	return $html;

}

/* ====================================================== SET SHORTCODE ====================================================== */

if (!is_admin()) {
	add_shortcode(CPC_SHORTCODE_PREFIX.'-mail', '__cpc__mail');  
}



?>
