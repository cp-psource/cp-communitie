<?php

include_once('../../../../wp-config.php');

// Request group delete
if ($_POST['action'] == 'requestDelete') {
	global $wpdb, $current_user;

	$r = 'OK';

	$group_id = $_POST['group_id'];
	$request_text = $_POST['request_text'];

	__cpc__sendmail(get_bloginfo('admin_email'), __('Anfrage zum Löschen einer Gruppe', 'cp-communitie'), __('Von:', 'cp-communitie').': '.$current_user->display_name.'<br /><br />'.$request_text.'<br /><br />Ref: '.$group_id);							

	exit;	
}

// Group Invites
if ($_POST['action'] == 'group_menu_invites') {
	
	$html = '';

	if (is_user_logged_in()) {

		$html .= '<h1>'.__('Gruppeneinladung', 'cp-communitie').'</h1>';
		
		$html .= '<p>'.__('Gib die E-Mail-Adressen der Personen, die Du in Deine Gruppe einladen möchtest, durch Kommas getrennt oder in separaten Zeilen ein.', 'cp-communitie').' ';
		$html .= __('Sie erhalten eine E-Mail mit einem Link, auf den sie klicken müssen, um zu dieser Webseite und Gruppenseite zu gelangen.', 'cp-communitie').' ';
		$html .= __('Wenn sie kein Mitglied dieser Webseite sind, können sie sich registrieren, bevor sie sich anmelden.', 'cp-communitie').'</p>';

		$html .= '<p style="font-weight:bold">'.sprintf(__('Du kannst maximal %d Personen gleichzeitig einladen.', 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_group_invites_max')).'</p>';
		
		$html .= '<textarea id="cpcommunitie_group_invites" rows="10" style="width:98%; margin-bottom:10px;"></textarea>';
		$html .= '<input type="submit" id="cpcommunitie_group_invites_button" name="Submit" class="__cpc__button" value="'.__('Einladen', 'cp-communitie').'" /> ';

		$html .= '<div id="cpcommunitie_group_invites_sent" style="display:none"></div>';
		
	}
	
	echo $html;
	exit;
}

// Send group invites
if ($_POST['action'] == 'sendInvites') {

	$html = '';

	if (is_user_logged_in()) {

		$from_email = get_option(CPC_OPTIONS_PREFIX.'_from_email');
		if ($from_email == '') { $from_email = "noreply@".get_bloginfo('url'); }	
	
		$group = $wpdb->get_row($wpdb->prepare("SELECT name, description FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d", $_POST['group_id']));
	
		$crlf = PHP_EOL;
		$html = 'E-Mails gesendet an:<br />';
		$blog_name = get_bloginfo('name');
		$url = __cpc__get_url('group');
		$url = $url.__cpc__string_query($url).'gid='.$_POST['group_id'];
	
		$emails = $_POST['emails'];	
		$emails = eregi_replace(" ", "", $emails);
		$emails = eregi_replace(";", ",", $emails);
		$emails = eregi_replace(PHP_EOL, ",", $emails);
			
		$email_addresses = explode(',', $emails);
		
		if ($email_addresses) {
			foreach ($email_addresses as $email_address) {
					
				if (trim($email_address)) {
					$body = "<h1>".__('Gruppeneinladung', 'cp-communitie')."</h1>";
		
					$body .= '<p>'.__('An:', 'cp-communitie').' '.$email_address.'<br />';
					$body .= __('Von:', 'cp-communitie').' '.$current_user->user_email.'</p>';
		
					$body .= '<p>'.sprintf(__("Komm und trete meiner Gruppe auf %s bei", 'cp-communitie'), $blog_name).'!</p>';
		
					$body .= '<h2>'.$group->name.'</h2>';
					$body .= '<p>'.$group->description.'</p>';
					$body .= '<p>'.$url.'</p>';
		
					$body .= "<p><em>";
					$body .= $current_user->display_name;
					$body .= "</em></p>";
		
					$body = str_replace(chr(13), "<br />", $body);
					$body = str_replace("\\r\\n", "<br />", $body);
					$body = str_replace("\\", "", $body);
				
					// To send HTML mail, the Content-type header must be set
					$headers = "MIME-Version: 1.0" . $crlf;
					$headers .= "Content-type:text/html;charset=utf-8" . $crlf;
					$headers .= "From: ".$from_email . $crlf;
		
					// finally send mail
					if (__cpc__sendmail($email_address, __('Gruppeneinladung', 'cp-communitie'), $body)) {
						$html .= $email_address.'<br />';
					} else {
						$html .= $email_address.' (fehlgeschlagen)<br />';
					}
				}
			}			
		}
	}
	
	echo $html;
	exit;
}

// Member delete
if ($_POST['action'] == 'member_delete') {
	global $wpdb, $current_user;

	if (is_user_logged_in()) {
		
		$uid = $current_user->ID;		
		$gid = $_POST['group_id'];		
		$id = $_POST['id'];		

		// First check is a group admin
		$sql = "SELECT member_id FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id=%d AND member_id=%d and admin='on'";
		$admin_check = $wpdb->get_var($wpdb->prepare($sql, $gid, $uid));
		if ($admin_check || __cpc__get_current_userlevel() == 5) {
			$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id=%d AND member_id = %d";
			$wpdb->query( $wpdb->prepare( $sql, $gid, $id ) );	
		}
	}
	
}

// Group Change Order
if ($_POST['action'] == 'changeGroupOrder') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {

		$uid = $current_user->ID;		
		$gid = $_POST['gid'];	
		$order = $_POST['order'];	
		
		// first check this user is a group admin
		$sql = "SELECT admin FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id = %d AND member_id = %d";
		$admin = $wpdb->get_var($wpdb->prepare($sql, $gid, $uid));	
		
		if ($admin == "on" || __cpc__get_current_userlevel() == 5) {

			if (__cpc__safe_param($gid) && __cpc__safe_param($order)) {
			
				$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_groups SET group_order = %d WHERE gid = %d";
				$wpdb->query( $wpdb->prepare( $sql, $order, $gid ) );	
				
			}
			echo __('OK', 'cp-communitie');
			
		} else {
			echo __('KEIN GRUPPENADMIN', 'cp-communitie');
		}
				
	} else {
		echo __('NICHT EINGELOGGT', 'cp-communitie');
	}
		
	exit;
}


// Group Delete
if ($_POST['action'] == 'deleteGroup') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {

		$uid = $current_user->ID;		
		$gid = $_POST['gid'];	
		
		// first check this user is a group admin
		$sql = "SELECT admin FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id = %d AND member_id = %d";
		$admin = $wpdb->get_var($wpdb->prepare($sql, $gid, $uid));	
		
		if ($admin == "on" || __cpc__get_current_userlevel() == 5) {

			if (__cpc__safe_param($gid)) {
			
				// delete all wall comments
				$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_comments WHERE is_group = 'on' AND subject_uid = %d";
				$wpdb->query( $wpdb->prepare( $sql, $gid ) );	
	
				// delete members			
				$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id = %d";
				$wpdb->query( $wpdb->prepare( $sql, $gid ) );	
				
				// delete group			
				$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d";
				$wpdb->query( $wpdb->prepare( $sql, $gid ) );	
	
				// delete topics			
				$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_topics WHERE topic_group = %d";
				$wpdb->query( $wpdb->prepare( $sql, $gid ) );	
				
				// delete from news (if plugin activated)
				if (function_exists('__cpc__news_main')) {
					$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_news WHERE news LIKE %s";
					$wpdb->query( $wpdb->prepare( $sql, '%gid='.$gid.'&%' ) );	
				}
			
			}
			echo __('OK', 'cp-communitie');
			
		} else {
			echo __('KEIN GRUPPENADMIN', 'cp-communitie');
		}
				
	} else {
		echo __('NICHT EINGELOGGT', 'cp-communitie');
	}
		
	exit;
}

// Group Accept
if ($_POST['action'] == 'acceptGroup') {

	global $wpdb;

	if (is_user_logged_in()) {

		$uid = $_POST['uid'];		
		$gid = $_POST['gid'];		

		$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_group_members SET valid = 'on' WHERE group_id = %d AND member_id = %d";
		if (__cpc__safe_param($gid)) {
			$wpdb->query( $wpdb->prepare( $sql, $gid, $uid ) );	
		}

		// Email to let the member know the result
		$sql = "SELECT ID, user_email FROM ".$wpdb->base_prefix."users u WHERE ID = %d";
		$recipient = $wpdb->get_row($wpdb->prepare($sql, $uid));	
				
		if ($recipient) {
							
			$body = "<h1>".__("Gruppenmitgliedschaft", 'cp-communitie')."</h1>";
			$body .= "<p>".__('Du bist dieser Gruppe erfolgreich beigetreten', 'cp-communitie').".</p>";
			$body .= "<p><a href='".__cpc__get_url('group')."&gid=".$gid."'>".__('Go to the group', 'cp-communitie')."...</a></p>";

			if ( $recipient->ID != $current_user->ID) {
				__cpc__sendmail($recipient->user_email, __('Gruppenmitgliedschaft', 'cp-communitie'), $body);
			}
		}

		// Get group name
		$sql = "SELECT name, new_member_emails FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d";
		$group = $wpdb->get_row($wpdb->prepare($sql, $gid));

		// Tell other members
		$html = __cpc__inform_members($group->name, $gid, $group->new_member_emails);	
			
		echo $uid;		
		
	} else {
		echo __('NICHT EINGELOGGT', 'cp-communitie');
	}
		
	exit;
}

// Group Reject
if ($_POST['action'] == 'rejectGroup') {

	global $wpdb;

	if (is_user_logged_in()) {

		$uid = $_POST['uid'];		
		$gid = $_POST['gid'];		

		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id = %d AND member_id = %d";
		if (__cpc__safe_param($gid)) {
			$wpdb->query( $wpdb->prepare( $sql, $gid, $uid ) );	
		}

		echo $uid;		
		
	} else {
		echo __("NICHT EINGELOGGT", 'cp-communitie');
	}
		
	exit;
}


// Group Subscribe
if ($_POST['action'] == 'group_subscribe') {

	global $wpdb;	

	if (is_user_logged_in()) {
	
		$notify = $_POST['notify'];
		$gid = $_POST['gid'];
		
		$wpdb->query("UPDATE ".$wpdb->prefix."cpcommunitie_group_members SET notify = '".$notify."' WHERE member_id = ".$current_user->ID." AND group_id = ".$gid);
		
		echo $wpdb->last_query;
		
	}
	
	exit;
}

// Leave Group
if ($_POST['action'] == 'leaveGroup') {

	global $wpdb;

	if (is_user_logged_in()) {
	
		$gid = $_POST['gid'];
		if (__cpc__safe_param($gid)) {
			$wpdb->query("DELETE FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE member_id = ".$current_user->ID." AND group_id = ".$gid);
		}
	}
	
	exit;
		
}

// Join Group
if ($_POST['action'] == 'joinGroup') {


	global $wpdb;

	if (is_user_logged_in()) {
	
		$gid = $_POST['gid'];
		
		// Check if private or public
		$sql = "SELECT private FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d";
		$private = $wpdb->get_var($wpdb->prepare($sql, $gid));
		
		if ($private == "on") {
			$valid = '';
		} else {
			$valid = 'on';
		}

		// First delete (to avoid any duplicate entries)
		$sql = "DELETE FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id = %d AND member_id = %d";
	   	$wpdb->query($wpdb->prepare($sql, $gid, $current_user->ID));
		
		// Add membership
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->prefix."cpcommunitie_group_members
			( 	group_id, 
				member_id,
				admin,
				valid,
				joined
			)
			VALUES ( %d, %d, %s, %s, %s )", 
	        array(
	        	$gid, 
	        	$current_user->ID, 
	        	'',
	        	$valid,
	        	date("Y-m-d H:i:s")
	        	) 
	        ) );

		// Get group name
		$sql = "SELECT name, new_member_emails FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d";
		$group = $wpdb->get_row($wpdb->prepare($sql, $gid));
	        
		if ($private == "on") {

			// Send email to group admin, so get group admin email address
			$sql = "SELECT u.user_email 
					FROM ".$wpdb->base_prefix."users u 
					LEFT JOIN ".$wpdb->prefix."cpcommunitie_group_members m ON u.ID = m.member_id 
					WHERE m.group_id = %d AND m.admin = 'on'";
			$email_address = $wpdb->get_var($wpdb->prepare($sql, $gid));
	
			$body = "<h1>".__('Gruppenanfrage', 'cp-communitie')."</h1>";
			$body .= '<p>'.sprintf(__("Anfrage für neues Gruppenmitglied für %s", 'cp-communitie'), stripslashes($group->name)).': '.$current_user->display_name.'</p>';
	
			$url = __cpc__get_url('group');
			$url .= __cpc__string_query($url);
			$url .= "gid=".$gid;
			
			$body .= '<p><a href="'.$url.'">'.$url.'</a></p>';
			
			$body = str_replace(chr(13), "<br />", $body);
			$body = str_replace("\\r\\n", "<br />", $body);
			$body = str_replace("\\", "", $body);
		
			// finally send mail
			if (__cpc__sendmail($email_address, __('Gruppenanfrage', 'cp-communitie'), $body)) {
				$html = '';
			} else {
				$html = 'E-Mail konnte nicht gesendet werden '.$email_address;
			}
		} else {
			// Tell other members
			$html = __cpc__inform_members($group->name, $gid, $group->new_member_emails);
		}		
		echo $html;	
			        
		exit;
			        
	} else {
		
		echo __("NICHT EINGELOGGT", 'cp-communitie');
		
	}
	
	exit;
		
}

function __cpc__inform_members($group_name, $gid, $new_member_emails) {
	
	
	global $wpdb, $current_user;

	$html = '';
	
	// First check that this group tells about new members
	if ($new_member_emails == 'on') {
		
		$body = "<h1>".stripslashes($group_name)."</h1>";
		$body .= '<p>'.__("Neues Gruppenmitglied", 'cp-communitie').': '.$current_user->display_name.'</p>';
	
		$url = __cpc__get_url('group');
		$url .= __cpc__string_query($url);
		$url .= "gid=".$gid;
		
		$body .= '<p><a href="'.$url.'">'.$url.'</a></p>';
		
	    $sql = "SELECT u.user_email 
				FROM ".$wpdb->base_prefix."users u 
				LEFT JOIN ".$wpdb->prefix."cpcommunitie_group_members m ON u.ID = m.member_id 
				WHERE m.group_id = %d";
				
		$recipients = $wpdb->get_results($wpdb->prepare($sql, $gid));	
	
		foreach ($recipients AS $recipient) {
			if (__cpc__sendmail($recipient->user_email, __('Neues Gruppenmitglied', 'cp-communitie'), $body)) {
				//$html .= 'Sent to '.$recipient->user_email.' ';
			} else {
				$html .= 'E-Mail konnte nicht gesendet werden '.$recipient->user_email.'<br />';
			}
		}

	} else {
		//$html .= 'Not sending emails for this group!';
	}
	
	return $html;
	
}

// Update Group Settings
if ($_POST['action'] == 'updateGroupSettings') {

	global $wpdb, $blog_id;

	if (is_user_logged_in()) {

		$gid = $_POST['gid'];
		
		$sql = "SELECT member_id FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id = %d AND admin='on'";
		$current_group_admin = $wpdb->get_var($wpdb->prepare($sql, $gid));
		
		if ($current_group_admin == $current_user->ID || __cpc__get_current_userlevel() == 5) {
	
			$groupname = $_POST['groupname'];
			$groupdescription = $_POST['groupdescription'];
			$private = (isset($_POST['is_private'])) ? $_POST['is_private'] : '';
			$content_private = (isset($_POST['content_private'])) ? $_POST['content_private'] : '';
			$group_forum = (isset($_POST['group_forum'])) ? $_POST['group_forum'] : '';
			$allow_new_topics = (isset($_POST['allow_new_topics'])) ? $_POST['allow_new_topics'] : '';
			$new_member_emails = (isset($_POST['new_member_emails'])) ? $_POST['new_member_emails'] : '';		
			$add_alerts = (isset($_POST['add_alerts'])) ? $_POST['add_alerts'] : '';		
			$group_admin = (isset($_POST['group_admin'])) ? $_POST['group_admin'] : '0';		
			$default_page = $_POST['default_page'];
			$max_members = ($_POST['max_members'] != '') ? $_POST['max_members'] : '0';		
						
			$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix."cpcommunitie_groups SET 
				name = '".$groupname."',  
				description = '".$groupdescription."',  
				private = '".$private."',  
				content_private = '".$content_private."',
				group_forum = '".$group_forum."',
				allow_new_topics = '".$allow_new_topics."',
				add_alerts = '".$add_alerts."',
				new_member_emails = '".$new_member_emails."',  
				max_members = ".$max_members.",  
				default_page = '".$default_page."'  
				WHERE gid = %d", $gid ) );
				
			// Save group image
			if (isset($_POST['x'])) {
				$x = $_POST['x'];
				$y = $_POST['y'];
				$w = $_POST['w'];
				$h = $_POST['h'];
			} else {
				$x = 0;
				$y = 0;
				$w = 0;
				$h = 0;
			}
			
			// update group admin, first clear current admin
			$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_group_members
					SET admin = ''
					WHERE group_id = %d";
			$wpdb->query($wpdb->prepare($sql, $gid));
			// then set new one
			$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_group_members
					SET admin = 'on'
					WHERE group_id = %d AND member_id = %d";
			$wpdb->query($wpdb->prepare($sql, $gid, $group_admin));
			
			$r = '';
	
			if ($w > 0) {	
	
				// set new size and quality
				$targ_w = $targ_h = 200;
				$jpeg_quality = 90;
				
				// database or filesystem
				if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == 'on') {
					
					// Using database
				
					$sql = "SELECT group_avatar FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d";
					$avatar = stripslashes($wpdb->get_var($wpdb->prepare($sql, $gid)));	
			
					// create master from database
					$img_r = imagecreatefromstring($avatar);
					// set new size
					$targ_w = $targ_h = 200;
					// create temporary image
					$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );		
					// copy to new image, with new dimensions
					imagecopyresampled($dst_r,$img_r,0,0,$x,$y,$targ_w,$targ_h,$w,$h);
					// copy to variable
					ob_start();
					imageJPEG($dst_r);
					$new_img = ob_get_contents();
					ob_end_clean();
				
					// update database with resized blob
					$wpdb->update( $wpdb->prefix.'cpcommunitie_groups', 
						array( 'group_avatar' => addslashes($new_img) ), 
						array( 'gid' => $gid ), 
						array( '%s' ), 
						array( '%d' )
						);
					
					$r .= 'reload';
					
				} else {
					
					// Using filesystem
	
					$profile_photo = $wpdb->get_var($wpdb->prepare("SELECT profile_photo FROM ".$wpdb->prefix.'cpcommunitie_groups WHERE gid = %d', $gid));
				
					if ($blog_id > 1) {
						$src = get_option(CPC_OPTIONS_PREFIX.'_img_path')."/".$blog_id."/groups/".$gid."/profile/".$profile_photo;				
						$to_path = get_option(CPC_OPTIONS_PREFIX.'_img_path')."/".$blog_id."/groups/".$gid."/profile/";
					} else {
						$src = get_option(CPC_OPTIONS_PREFIX.'_img_path')."/groups/".$gid."/profile/".$profile_photo;
						$to_path = get_option(CPC_OPTIONS_PREFIX.'_img_path')."/groups/".$gid."/profile/";
					}
					
					$img_r = imagecreatefromjpeg($src);
					$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );
				
					imagecopyresampled($dst_r,$img_r,0,0,$x,$y,$targ_w,$targ_h,$w,$h);
			
					$filename = time().'.jpg';
					$to_file = $to_path.$filename;
					if (file_exists($to_path)) {
					    // folder already there
					} else {
						mkdir(str_replace('//','/',$to_path), 0777, true);
					}
						
					if ( imagejpeg($dst_r,$to_file,$jpeg_quality) ) {
						
						// update database
						$wpdb->update( $wpdb->base_prefix.'cpcommunitie_groups', 
							array( 'profile_photo' => $filename ), 
							array( 'gid' => $gid ), 
							array( '%s' ), 
							array( '%d' )
							);
							
						$r .= 'reload';
							
					} else {
						
						$r .= 'resize failed: '.$wpdb->last_query;
							
					}
									
				}
			
			}
			
		} else {
			
			$r = "NOT ADMIN (".$current_group_admin.")";
		}
		
	} else {
		
		$r = "NOT LOGGED IN";
		
	}
	
	echo $r;
	exit;
	
}

// Show Group Settings
if ($_POST['action'] == 'group_menu_settings') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {

		$gid = $_POST['uid1'];
	
		$group = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . 'cpcommunitie_groups WHERE gid=%d', $gid));
	
		$groupname = stripslashes($group->name);
		$groupdescription = stripslashes($group->description);
		$private = $group->private;
		$content_private = $group->content_private;
		$group_forum = $group->group_forum;
		$allow_new_topics = $group->allow_new_topics;
		$new_member_emails = $group->new_member_emails;
		$add_alerts = $group->add_alerts;
		$default_page = $group->default_page;
		$max_members = $group->max_members;
		$html = '';
		
		if (__cpc__group_admin($gid) == "yes" || __cpc__get_current_userlevel($current_user->ID) == 5 ) {
		
			$html .= "<div id='profile_left_column'>";
			
				$html .= '<div id="cpcommunitie_settings_table">';
		
					// Display name
					$html .= '<div style="clear:right; margin-bottom:15px;">';
						$html .= __('Gruppenname', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<input type="text" id="groupname" value="'.$groupname.'">';
						$html .= '</div>';
					$html .= '</div>';
					
					// Description
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Gruppenbeschreibung', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<input type="text" id="groupdescription" value="'.$groupdescription.'">';
						$html .= '</div>';
					$html .= '</div>';
					
					// Private?
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Müssen neue Mitglieder akzeptiert werden?', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<input type="checkbox" name="private" id="private"';
								if ($private == "on") { $html .= "CHECKED"; }
								$html .= '/>';
						$html .= '</div>';
					$html .= '</div>';
				
					// Private Content to non-members?
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Werden Inhalte für Nichtmitglieder verborgen?', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<input type="checkbox" name="content_private" id="content_private"';
								if ($content_private == "on") { $html .= "CHECKED"; }
								$html .= '/>';
						$html .= '</div>';
					$html .= '</div>';

					// Max number of members
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Maximale Mitgliederzahl (0 für unbegrenzt)', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<input type="text" style="width:50px" name="max_members" id="max_members" value="'.$max_members.'">';
						$html .= '</div>';
					$html .= '</div>';
					
	
					// Forum?
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Gruppenforum aktivieren?', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<input type="checkbox" name="group_forum" id="group_forum"';
								if ($group_forum == "on") { $html .= "CHECKED"; }
								$html .= '/>';
						$html .= '</div>';
					$html .= '</div>';
				
					// Allow new topics
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Mitgliedern erlauben, Forenthemen zu erstellen?', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<input type="checkbox" name="allow_new_topics" id="allow_new_topics"';
								if ($allow_new_topics == "on") { $html .= "CHECKED"; }
								$html .= '/>';
						$html .= '</div>';
					$html .= '</div>';
							
					// Inform members of new group members
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Gruppenadmin per E-Mail benachrichtigen, wenn ein neues Mitglied beitritt?', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<input type="checkbox" name="new_member_emails" id="new_member_emails"';
								if ($new_member_emails == "on") { $html .= "CHECKED"; }
								$html .= '/>';
						$html .= '</div>';
					$html .= '</div>';
				
					// Default group page
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Was soll die Standardseite sein?', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$html .= '<select name="default_page" id="default_page">';
								$html .= '<option value="activity"';
									if ($default_page == 'activity') { $html .= ' SELECTED'; }
									$html .= '>'.__('Aktivität', 'cp-communitie').'</option>';
								$html .= '<option value="forum"';
									if ($default_page == 'forum') { $html .= ' SELECTED'; }
									$html .= '>'.__('Forum', 'cp-communitie').'</option>';
								$html .= '<option value="about"';
									if ($default_page == 'about') { $html .= ' SELECTED'; }
									$html .= '>'.__('Startseite', 'cp-communitie').'</option>';
							$html .= '</select>';
						$html .= '</div>';
					$html .= '</div>';
				
					// Add activity to alerts?
					if (function_exists('__cpc__news_add')) {
						$html .= '<div style="clear: right; margin-bottom:15px;">';
							$html .= __('Aktivitätsbeiträge in Benachrichtigungen einschließen?', 'cp-communitie');
							$html .= '<div style="float:right;">';
								$html .= '<input type="checkbox" name="add_alerts" id="add_alerts"';
									if ($add_alerts == "on") { $html .= "CHECKED"; }
									$html .= '/>';
							$html .= '</div>';
						$html .= '</div>';
					}
				
					// Transfer group ownership
					$html .= '<div style="clear: right; margin-bottom:15px;">';
						$html .= __('Gruppenadministrator übertragen an:', 'cp-communitie');
						$html .= '<div style="float:right;">';
							$sql = "SELECT u.ID, u.display_name, m.admin
									FROM ".$wpdb->base_prefix."users u
									LEFT JOIN ".$wpdb->prefix."cpcommunitie_group_members m ON u.ID = m.member_id 
									WHERE m.group_id = %d 
									ORDER BY u.display_name";
							$members = $wpdb->get_results($wpdb->prepare($sql, $gid));
							$html .= '<select name="transfer_admin" id="transfer_admin">';
							foreach ($members AS $member) {
								$html .= '<option value="'.$member->ID.'"';
								if ($member->admin == 'on') { $html .= ' SELECTED'; }
								$html .= '>'.$member->display_name.'</option>';
							}
							$html .= '</select>';
						$html .= '</div>';
					$html .= '</div>';
				
					// Choose a new avatar
					$html .= '<div style="clear: right; margin-bottom:15px;">';	
						$html .= '<div style="float:right;">';
							include_once('../server/file_upload_include.php');
							$html .= show_upload_form(
								WP_CONTENT_DIR.'/cpc-content/members/'.$current_user->ID.'/group_avatar_upload/', 
								WP_CONTENT_URL.'/cpc-content/members/'.$current_user->ID.'/group_avatar_upload/',
								'group_avatar',
								__('Gruppenbild hochladen', 'cp-communitie'),
								0,
								$gid
							);
						$html .= '</div>';								
						$html .= '<p>'.__('Wähle ein Bild für die Gruppe...', 'cp-communitie').'</p>';
						$html .= '<div id="group_image_to_crop" style="width:95%;margin-bottom:15px; float:left;"></div>';
					$html .= '</div>';								

					$html .= '<p style="clear:both">';
					$html .= '<input type="submit" id="updateGroupSettingsButton" name="Submit" class="__cpc__button" value="'.__('Einstellungen speichern', 'cp-communitie').'" /> ';
					$html .= '</p>';

				
				$html .= '</div> ';
				 
			
			$html .= "</div>";
			
		} else {
			
			$html .= "Nur Gruppenadministrator";
			
		}
		
	}
	
	echo $html;
	exit;
	
}

// AJAX function to add comment
if ($_POST['action'] == 'group_addComment') {

	global $wpdb, $current_user;

	$uid = $_POST['uid'];
	$text = $_POST['text'];
	$parent = $_POST['parent'];

	if (is_user_logged_in()) {

		if ( ($text != __(addslashes("Schreibe einen Kommentar..."), "cp-communitie")) && ($text != '') ) {
	
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$wpdb->base_prefix."cpcommunitie_comments
				( 	subject_uid, 
					author_uid,
					comment_parent,
					comment_timestamp,
					comment,
					is_group
				)
				VALUES ( %d, %d, %d, %s, %s, %s )", 
		        array(
		        	$uid, 
		        	$current_user->ID, 
		        	$parent,
		        	date("Y-m-d H:i:s"),
		        	$text,
		        	'on'
		        	) 
		        ) );

			// New Post ID
			$author_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $current_user->ID));
			$group_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d", $uid));
		        
			// Update last activity
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."cpcommunitie_groups SET last_activity = %s WHERE gid = %d", date("Y-m-d H:i:s"), $uid ));

			// Email all members who want to know about it
			$sql = "SELECT u.ID, u.user_email, m.notify FROM ".$wpdb->base_prefix."users u 
			INNER JOIN ".$wpdb->prefix."cpcommunitie_group_members m ON u.ID = m.member_id
			WHERE group_id = %d";

			$recipients = $wpdb->get_results($wpdb->prepare($sql, $uid));	

			// Group post URL					
			$url = __cpc__get_url('group');
			$url .= __cpc__string_query($url);
			$url .= "gid=".$uid."&post=".$parent;
						
			// Should alerts be sent out?
			$add_alerts = $wpdb->get_var($wpdb->prepare("SELECT add_alerts FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d", $uid));
			
			if ($recipients) {
								
				$body = "<h1>".stripslashes($group_name)."</h1>";
				$body .= "<p>".$author_name." ".__('hat der Gruppe eine neue Antwort hinzugefügt', 'cp-communitie').":</p>";
				$body .= "<p>".stripslashes($text)."</p>";
				$body .= "<p><a href='".$url."'>".__('Gehe zum Gruppenbeitrag', 'cp-communitie')."...</a></p>";
				foreach ($recipients as $recipient) {
					if ( $recipient->ID != $current_user->ID) {
						if ($recipient->notify == 'on') {
							__cpc__sendmail($recipient->user_email, __('Neuer Gruppenbeitrag', 'cp-communitie'), $body);
						}
						if (function_exists('__cpc__news_add') && $add_alerts == 'on') {
							__cpc__news_add($current_user->ID, $recipient->ID, "<a href='".$url."'>".__("Gruppenantwort:", 'cp-communitie')." ".$author_name." ".__("has replied in", 'cp-communitie')." ".$group_name."</a>");
						}
					}
				}
			}
											
			exit;

		} else {

			exit;
			
		}
			
			
	} else {
		
		exit;
		
	}
}

// AJAX function to add status
if ($_POST['action'] == 'group_addStatus') {

	global $wpdb, $current_user;

	$subject_uid = $_POST['subject_uid'];
	$author_uid = $_POST['author_uid'];
	$text = $_POST['text'];
	$group_id = $_POST['gid'];

	if (is_user_logged_in()) {
		
		if ( ($text != __("Schreibe einen Kommentar...", 'cp-communitie')) && ($text != '') ) {

			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$wpdb->base_prefix."cpcommunitie_comments
				( 	subject_uid, 
					author_uid,
					comment_parent,
					comment_timestamp,
					comment,
					is_group
				)
				VALUES ( %d, %d, %d, %s, %s, %s )", 
		        array(
		        	$subject_uid, 
		        	$author_uid, 
		        	0,
		        	date("Y-m-d H:i:s"),
		        	$text,
		        	'on'
		        	) 
		        ) );

			// New Post ID
			$new_id = $wpdb->insert_id;
						
			$author_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $author_uid));
			$group_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM ".$wpdb->base_prefix."cpcommunitie_groups WHERE gid = %d", $subject_uid));

			// Update last activity
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."cpcommunitie_groups SET last_activity = %s WHERE gid = %d", array( date("Y-m-d H:i:s"), $subject_uid ) ));
		        
			// Email all members who want to know about it
			$sql = "SELECT u.ID, u.user_email, m.notify FROM ".$wpdb->base_prefix."users u 
			INNER JOIN ".$wpdb->prefix."cpcommunitie_group_members m ON u.ID = m.member_id
			WHERE group_id = %d";

			$recipients = $wpdb->get_results($wpdb->prepare($sql, $subject_uid));
			
			// URL of group post
			$url = __cpc__get_url('group');
			$url .= __cpc__string_query($url);
			$url .= "gid=".$subject_uid."&post=".$new_id;
			
			// Should alerts be sent out?
			$add_alerts = $wpdb->get_var($wpdb->prepare("SELECT add_alerts FROM ".$wpdb->prefix."cpcommunitie_groups WHERE gid = %d", $subject_uid));
					
			if ($recipients) {
								
				$body = "<h1>".stripslashes($group_name)."</h1>";
				$body .= "<p>".$author_name." ".__('hat einen neuen Beitrag zur Gruppe hinzugefügt', 'cp-communitie').":</p>";
				$body .= "<p>".stripslashes($text)."</p>";
				$body .= "<p><a href='".$url."'>".__('Gehe zum Gruppenbeitrag', 'cp-communitie')."...</a></p>";
				foreach ($recipients as $recipient) {
					if ( $recipient->ID != $current_user->ID) {
						if ($recipient->notify == 'on') {
							__cpc__sendmail($recipient->user_email, __('Neuer Gruppenbeitrag', 'cp-communitie'), $body);
						}
						if (function_exists('__cpc__news_add') && $add_alerts == 'on') {
							__cpc__news_add($author_uid, $recipient->ID, "<a href='".$url."'>".__("Gruppenbeitrag:", 'cp-communitie')." ".$author_name." ".__("has posted in", 'cp-communitie')." ".$group_name."</a>");							
						}
					}
				}
				
			}
			
			exit;
			
		} else {

			exit;
			
		}

	} else {

		exit;
	}
	
	
		
}

// Show about page
if ($_POST['action'] == 'group_menu_about') {
	
	global $wpdb, $current_user;
	$gid = $_POST['uid1'];
	
	$source = $wpdb->get_var($wpdb->prepare("SELECT about_page FROM ".$wpdb->prefix . "cpcommunitie_groups WHERE gid = %d", $gid));
	
	$html = '<div id="__cpc__group_about_page">';
	$html .= stripslashes($source);
	$html .= '</div>';
	if (__cpc__group_admin($gid) == "yes") {
		$html .= "<a href='javascript:void(0);' id='__cpc__about_group_edit'>Gruppen-Startseite bearbeiten</a><br />";
	}
	
	
	$html .= '</div>';
	
	
	echo $html;
	
	exit;

}

// Edit group about page
if ($_POST['action'] == 'group_menu_about_edit') {
	
	global $wpdb, $current_user;
	$gid = $_POST['group_id'];
	
	if (__cpc__group_admin($gid) == "yes") {
	
		$source = $wpdb->get_var($wpdb->prepare("SELECT about_page FROM ".$wpdb->prefix . "cpcommunitie_groups WHERE gid = %d", $gid));
		echo '<textarea id="__cpc__about_group_edit_textarea">';
		echo stripslashes($source);	
		echo '</textarea>';
	} else {
		echo __('Kein Gruppenadministrator', 'cp-communitie');
	}
	
	exit;
}

// Update group about page
if ($_POST['action'] == 'update_welcome_message') {
	
	global $wpdb, $current_user;
	$gid = $_POST['gid'];
	$message = $_POST['message'];
	
	if (__cpc__group_admin($gid) == "yes") {
	
		$sql = "UPDATE ".$wpdb->prefix."cpcommunitie_groups SET about_page = %s WHERE gid = %d";
		$wpdb->query( $wpdb->prepare( $sql, $message, $gid ) );	

		echo __('OK', 'cp-communitie');	
		
	} else {
		echo __('Kein Gruppenadministrator', 'cp-communitie');
	}
	
	exit;
}

// Show Wall
if ($_POST['action'] == 'group_menu_wall') {

	global $wpdb, $current_user;
	
	$uid1 = $_POST['uid1'];
	$uid2 = $current_user->ID;
	$post = $_POST['post'];
	$limit_from = $_POST['limit_from'];

	$limit_count = 10; // How many new items should be shown
	
	$plugin = CPC_PLUGIN_URL;

	if (get_option(CPC_OPTIONS_PREFIX.'_use_styles') == "on") {
		$bg_color_2 = 'background-color: '.get_option(CPC_OPTIONS_PREFIX.'_bg_color_2');
	} else {
		$bg_color_2 = '';
	}
	
	$html = "";
	
	$profile_page = get_option(CPC_OPTIONS_PREFIX.'_profile_url');
	if ($profile_page[strlen($profile_page)-1] != '/') { $profile_page .= '/'; }
	$q = __cpc__string_query($profile_page);		

	$html .= "<div id='profile_left_column' style='";
	if (get_option(CPC_OPTIONS_PREFIX.'_show_wall_extras') != 'on') {
		$html .= " border-left:0px;";
	}			
	$html .= "'>";		
	
		// Notification choice
		if (__cpc__member_of($uid1) == "yes" && $limit_from == 0 && $post == 0) {
			$notify = $wpdb->get_var($wpdb->prepare("SELECT notify FROM ".$wpdb->prefix . "cpcommunitie_group_members WHERE group_id = %d AND member_id = %d", $uid1, $uid2));
			$html .= "<input type='checkbox' id='group_notify'";
			if ($notify == "on") { $html .= " CHECKED"; }
			$html .= "> ".__("Erhalte E-Mails, wenn es neue Beiträge und Antworten gibt", 'cp-communitie');
		}
			
		// Wall
		$html .= "<div id='__cpc__wall'>";
		
			// Post Comment Input
			if (is_user_logged_in() && __cpc__member_of($uid1) == "yes" && $limit_from == 0 && $post == 0) {

				// Add status surrounding div
				$html .= '<div id="cpcommunitie_add_status">';

					$whatsup = __('Schreibe einen Kommentar...', 'cp-communitie');

					$html .= '<textarea ';
					if (get_option(CPC_OPTIONS_PREFIX.'_elastic')) $html .= 'class="elastic" ';
					$html .= 'id="__cpc__group_comment"  onblur="this.value=(this.value==\'\') ? \''.$whatsup.'\' : this.value;" onfocus="this.value=(this.value==\''.$whatsup.'\') ? \'\' : this.value;">';
					$html .= $whatsup;
					$html .= '</textarea>';

					if (get_option(CPC_OPTIONS_PREFIX.'_show_buttons')) {
						$html .= '<br /><input id="cpcommunitie_group_add_comment" type="submit" class="__cpc__button" style="width:75px;" value="'.__('Veröffentlichen', 'cp-communitie').'" /> ';
					}
					
				$html .= '</div>';
				
			}

			if ($post != '' && __cpc__safe_param($post)) {
				
				// Re-act to a single post (probably from mail link)

				$sql = "SELECT c.*, u.display_name FROM ".$wpdb->base_prefix."cpcommunitie_comments c LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID WHERE c.cid = %d AND c.comment_parent = 0 AND c.is_group = 'on' ORDER BY c.comment_timestamp DESC LIMIT %d, %d";
				$comments = $wpdb->get_results($wpdb->prepare($sql, $post, $limit_from, $limit_count));	
				
			} else {
				
				// Show whole wall

				$sql = "SELECT c.*, u.display_name FROM ".$wpdb->base_prefix."cpcommunitie_comments c LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID WHERE c.comment_parent = 0 AND c.subject_uid = %d AND c.is_group = 'on' ORDER BY c.comment_timestamp DESC LIMIT %d, %d";	
				$comments = $wpdb->get_results($wpdb->prepare($sql, $uid1, $limit_from, $limit_count));	

			}
			

			if ($comments) {
				foreach ($comments as $comment) {

					$html .= "<div id='".$comment->cid."' class='wall_post_div'>";

						$html .= "<div class='wall_post_avatar' style='width:64px;'>";
							$html .= get_avatar($comment->author_uid, 64);
						$html .= "</div>";

						$html .= "<div class='wall_post_entry'>";
							$html .= "<div class='wall_post'>";
							
								if (__cpc__get_current_userlevel($uid2) == 5 || $comment->author_uid == $uid2) {
									$html .= "<a title='".$comment->cid."' href='javascript:void(0);' class='delete_post delete_post_top'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/delete.png' style='width:16px;height:16px' /></a>";
								}
								$html .= '<a href="'.$profile_page.$q.'uid='.$comment->author_uid.'">'.stripslashes($comment->display_name).'</a> ';
								$html .= __cpc__time_ago($comment->comment_timestamp).".<br />";
								$c = $comment->comment;
								if (get_option(CPC_OPTIONS_PREFIX.'_force_utf8') == 'on') 
									$c = utf8_decode($c);
								$html .= __cpc__make_url(stripslashes($c));

								// Replies
								$sql = "SELECT c.*, u.display_name FROM ".$wpdb->base_prefix."cpcommunitie_comments c 
									LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
									WHERE c.comment_parent = %d ORDER BY c.cid";
									
								$replies = $wpdb->get_results($wpdb->prepare($sql, $comment->cid));	
								$count = 0;
								if ($replies) {
									if (count($replies) > 4) {
										$html .= "<div id='view_all_comments_div'>";
										$html .= "<a title='".$comment->cid."' class='view_all_comments' href='javascript:void(0);'>".__(sprintf("Alle %d Kommentare anzeigen", count($replies)), "cp-communitie")."</a>";
										$html .= "</div>";
									}
									foreach ($replies as $reply) {
										$count++;
										if ($count > count($replies)-4) {
											$reply_style = "";
										} else {
											$reply_style = "display:none; ";
										}
										$html .= "<div id='".$reply->cid."' class='reply_div' style='".$reply_style."'>";
											$html .= "<div class='__cpc__wall_reply_div' style='".$bg_color_2.";'>";
												$html .= "<div class='wall_reply'>";
													if (__cpc__get_current_userlevel($uid2) == 5 || $reply->subject_uid == $uid2 || $reply->author_uid == $uid2) {
														$html .= "<a title='".$reply->cid."' href='javascript:void(0);' class='delete_post delete_reply'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/delete.png' style='width:16px;height:16px' /></a>";
													}
													$html .= '<a href="'.$profile_page.$q.'uid='.$reply->author_uid.'">'.stripslashes($reply->display_name).'</a> ';
													$html .= __cpc__time_ago($reply->comment_timestamp).".<br />";
													$c = $reply->comment;
													if (get_option(CPC_OPTIONS_PREFIX.'_force_utf8') == 'on') 
														$c = utf8_decode($c);
													$html .= __cpc__make_url(stripslashes($c));
												$html .= "</div>";
											$html .= "</div>";
											
											$html .= "<div class='wall_reply_avatar'>";
												$html .= get_avatar($reply->author_uid, 40);
											$html .= "</div>";		
										$html .= "</div>";
									}
								} else {
									$html .= "<div class='no_wall_replies'></div>";
								}												
								$html .= "<div style='clear:both;' id='__cpc__comment_".$comment->cid."'></div>";

								// Reply field
								if (is_user_logged_in() && __cpc__member_of($uid1) == "yes") {
									$html .= '<div>';
									

												$html .= '<textarea id="__cpc__reply_'.$comment->cid.'" title="'.$comment->cid.'" class="__cpc__group_reply';
												if (get_option(CPC_OPTIONS_PREFIX.'_elastic')) $html .= ' elastic';
												$html .= '" id="__cpc__reply_'.$comment->cid.'" onblur="this.value=(this.value==\'\') ? \''.__('Schreibe einen Kommentar...', 'cp-communitie').'\' : this.value;" onfocus="this.value=(this.value==\''.__('Schreibe einen Kommentar...', 'cp-communitie').'\') ? \'\' : this.value;">'.__('Schreibe einen Kommentar...', 'cp-communitie').'</textarea>';
												
												if (get_option(CPC_OPTIONS_PREFIX.'_show_buttons')) {
													$html .= '<br /><input title="'.$comment->cid.'" id="__cpc__reply_'.$comment->cid.'" type="submit" style="width:75px" class="__cpc__button reply_field-button" value="'.__('Hinzufügen', 'cp-communitie').'" />';
												}
												$html .= '<input id="cpcommunitie_author_'.$comment->cid.'" type="hidden" value="'.$comment->author_uid.'" />';
									$html .= '</div>';

								}
								
							$html .= "</div>";
						$html .= "</div>";
					$html .= "</div>";
							
				}

				$html .= "<a href='javascript:void(0)' id='showmore_group_wall' title='".($limit_from+$limit_count)."'>".__("mehr...", 'cp-communitie')."</a>";

			} else {
				$html .= "<br />".__("Nichts zu sehen, tut mir leid.", 'cp-communitie');
			}
		
		$html .= "</div>";
			
	$html .= "</div>";

	echo $html;
	
	exit;
	
}

// Show Members
if ($_POST['action'] == 'group_menu_members') {

	global $wpdb, $current_user;
	
	$uid1 = $_POST['uid1'];
	$uid2 = $current_user->ID;
	$post = $_POST['post'];

	$plugin = CPC_PLUGIN_URL;

	$html = "";
	
	$me = $current_user->ID;
	$page = 1;
	$page_length = 25;

	$html .= "<div id='profile_left_column'>";				

		$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."cpcommunitie_group_members WHERE group_id=%d";
		$member_count = $wpdb->get_var($wpdb->prepare($sql, $uid1));
		
		$html .= "<div id='group_member_count'>".__("Mitgliederzahl:", 'cp-communitie')." ".$member_count."</div>";
		
		$sql = "SELECT u.ID, g.admin, g.valid 
		FROM ".$wpdb->prefix."cpcommunitie_group_members g 
		RIGHT JOIN ".$wpdb->base_prefix."users u ON g.member_id = u.ID 
		WHERE u.ID > 0 AND g.group_id = %d ORDER BY g.valid DESC LIMIT ".($page*$page_length-$page_length).",".$page_length;
		
		$get_members = $wpdb->get_results($wpdb->prepare($sql, $uid1));
		
		if ($get_members) {

			$members_array = array();
			foreach ($get_members as $member) {

				$add = array (	
					'ID' => $member->ID,
					'admin' => $member->admin,
					'valid' => $member->valid,
					'last_activity' => __cpc__get_meta($member->ID, 'last_activity'),
					'city' => __cpc__get_meta($member->ID, 'city'),
					'country' => __cpc__get_meta($member->ID, 'country'),
					'share' => __cpc__get_meta($member->ID, 'share')
				);

				array_push($members_array, $add);
			}
			$members = __cpc__sub_val_sort($members_array, 'last_activity', false);
			
			$inactive = get_option(CPC_OPTIONS_PREFIX.'_online');
			$offline = get_option(CPC_OPTIONS_PREFIX.'_offline');
			$profile = __cpc__get_url('profile');
			
			$shown_pending_title = false;
			$shown_members_title = true;
			
			foreach ($members as $member) {
				
				if ($member['valid'] != "on" && $shown_pending_title == false) {
					$html .= "<br /><p><strong>".__("Erwartet Genehmigung", 'cp-communitie')."</strong></p>";
					$shown_pending_title = true;
					$shown_members_title = false;					
				}
				
				if ($member['valid'] == "on" && $shown_members_title == false) {
					$html .= "<br /><p><strong>".__("Mitglieder", 'cp-communitie')."</strong></p>";
				}
				
				$time_now = time();
				$last_active_minutes = strtotime($member['last_activity']);
				$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
												
				$html .= "<div id='request_".$member['ID']."' class='wall_post_div members_row row_odd corners'>";		

					$html .= "<div class='members_info'>";

						// Delete icons
						if ( (__cpc__get_current_userlevel() == 5 || __cpc__group_admin($uid1) == "yes") && ($member['admin'] != 'on') ) {
							$html .= " <a title='".$member['ID']."' href='javascript:void(0);' style='display:none; float:right;' class='delete_group_member delete delete_post_top'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/delete.png' style='width:16px;height:16px' /></a>";
						}

						if ( ($member['ID'] == $me) || (is_user_logged_in() && strtolower($member['share']) == 'everyone') || (strtolower($member['share']) == 'public') || (strtolower($member['share']) == 'friends only' && __cpc__friend_of($member['ID'], $current_user->ID)) ) {
							$html .= "<div class='members_location'>";
								if (isset($city) && $city != '') {
									$html .= $member['city'];
								}
								if (isset($country) && $country != '') {
									if ($city != '') {
										$html .= ', '.$member['country'];
									} else {
										$html .= $member['country'];
									}
								}								
							$html .= "</div>";
						}
	
						$html .= "<div class='members_avatar'>";
							$html .= get_avatar($member['ID'], 64);
						$html .= "</div>";
						$html .= __cpc__profile_link($member['ID']).', '.__('letzte Aktivität', 'cp-communitie').' '.__cpc__time_ago($member['last_activity']).". ";
						if ($last_active_minutes >= $offline) {
							//$html .= '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/loggedout.gif">';
						} else {
							if ($last_active_minutes >= $inactive) {
								$html .= '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/inactive.gif">';
							} else {
								$html .= '<img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/online.gif">';
							}
						}
						if ($member['admin'] == "on") {
							$html .= "<br />[".__("Gruppenadministrator", 'cp-communitie')."]";
						}
						
						// Requesting group membership...
						if ($member['valid'] != "on") {
							$html .= "<div style='clear: both; margin-bottom:15px;'>";
								$html .= "<div style='float:right;'>";
									$html .= '<input type="submit" title="'.$member['ID'].'" id="rejectgrouprequest" class="__cpc__button" value="'.__('Ablehnen', 'cp-communitie').'" /> ';
								$html .= "</div>";
								$html .= "<div style='float:right;'>";
									$html .= '<input type="submit" title="'.$member['ID'].'" id="acceptgrouprequest" class="__cpc__button" value="'.__('Akzeptieren', 'cp-communitie').'" /> ';
								$html .= "</div>";
							$html .= "</div>";
						}
					$html .= "</div>";
				$html .= "</div>";
			}

		} else {
			$html .= __('Keine Mitglieder', 'cp-communitie')."....";
		}			
			
	$html .= "</div>";
		
	echo $html;
	
	exit;
	
}

		
?>

	
