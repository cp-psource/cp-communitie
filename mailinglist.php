<?php
/*
CP Community Reply-by-Email
Description: Allows replies to forum notifications by email.
*/


// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

/* ====================================================================== MAIN =========================================================================== */



// get any waiting emails and act upon them
function __cpc__mailinglist() {

}


// add custom time to cron
function __cpc__mailinglist_filter_cron_schedules( $schedules ) {
	$schedules['__cpc__mailinglist_interval'] = array(
		'interval' => get_option(CPC_OPTIONS_PREFIX.'_mailinglist_cron'),
		'display' => sprintf(__('%s reply-by-email interval', 'cp-communitie'), CPC_WL)
	);
	return $schedules;
}
add_filter( 'cron_schedules', '__cpc__mailinglist_filter_cron_schedules' );

// send automatic scheduled email
if ( !wp_next_scheduled('__cpc__mailinglist_hook') ) {
	wp_schedule_event( time(), '__cpc__mailinglist_interval', '__cpc__mailinglist_hook' ); // Schedule event
}

// This is what is run
function __cpc__mailinglist_hook_function() {
	__cpc__check_pop3(false,true);
}
add_action('__cpc__mailinglist_hook', '__cpc__mailinglist_hook_function');
 

function __cpc__check_pop3($output=false,$process=true) {
	
	if (function_exists('__cpc__mailinglist')) {
		
		if (!isset($_SESSION['__cpc__mailinglist_lock']) || $_SESSION['__cpc__mailinglist_lock'] != 'locked') {
			
			$_SESSION['__cpc__mailinglist_lock'] = 'locked';
			
			require_once(CPC_PLUGIN_DIR.'/class.cpc_forum.php');
			$cpc_forum = new cpc_forum();
			
			global $wpdb;
			
			if ($output) {
				if ($process) {
					echo '<h3>'.__('Wartende E-Mail wird bearbeitet...', 'cp-communitie').'</h3>';
				} else {
					echo '<h3>'.__('Suche nach wartenden E-Mails, aber keine Verarbeitung...', 'cp-communitie').'</h3>';
				}
			}
		
			$server = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_server');
			$port = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_port');
			$username = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_username');
			$password = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_password');
			
			if ($mbox = imap_open ("{".$server.":".$port."/pop3}INBOX", $username, $password) ) {
				
				if ($output) echo __('Verbunden', 'cp-communitie').', ';
				
				$num_msg = imap_num_msg($mbox);
				if ($output) echo __('Anzahl der gefundenen Nachrichten', 'cp-communitie').': '.$num_msg.'<br /><br />';
		
				$carimap = array("=C3=A9", "=C3=A8", "=C3=AA", "=C3=AB", "=C3=A7", "=C3=A0", "=20", "=C3=80", "=C3=89", "\n", "> ");
				$carhtml = array("�", "�", "�", "�", "�", "�", "&nbsp;", "�", "�", "<br>", "");
				
				if ($num_msg > 0) {
					
					if ($output) {
						echo '<table class="widefat">';
						echo '<thead>';
						echo '<tr>';
						echo '<th style="font-size:1.2em">'.__('Von', 'cp-communitie').'</th>';
						echo '<th style="font-size:1.2em">'.__('Datum', 'cp-communitie').'</th>';
						echo '<th style="font-size:1.2em">'.__('Themen-ID', 'cp-communitie').'</th>';
						echo '<th style="font-size:1.2em" width="50%">'.__('Snippet', 'cp-communitie').'</th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
					}

					for ($i = 1; $i <= $num_msg; ++$i) {

						// Get email info
						$header = imap_header($mbox, $i);
		        		$prettydate = date("jS F Y H:i:s", $header->udate);
		        		$email = $header->from[0]->mailbox.'@'.$header->from[0]->host;
						$subject = $header->subject;
						
						// check email address is a registered email address
						$sql = "SELECT ID FROM ".$wpdb->base_prefix."users WHERE user_email = %s";
						$emailcheck = $wpdb->get_var($wpdb->prepare($sql, $email));
						
						if ($emailcheck) {						
		
							// Note user ID and get display_name
							$uid = $emailcheck;
							$sql = "SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %s";
							$display_name = $wpdb->get_var($wpdb->prepare($sql, $uid));
						
							$x = strpos($subject, '#TID=');
							if ($x !== FALSE) {
								
								// Get TID and continue
								$tid = substr($subject, $x+5, 1000);
								$x = strpos($tid, ' ');
								$tid = substr($tid, 0, $x);
								
								$sql = "SELECT tid FROM ".$wpdb->prefix."cpcommunitie_topics WHERE tid = %d";
								$tidcheck = $wpdb->get_var($wpdb->prepare($sql, $tid));
								
								if ($tidcheck) {
									
									// Get message to add as a reply					
									$body = imap_fetchbody($mbox, $i, "1.1");
									if ($body == "") {
									    $body = imap_fetchbody($mbox, $i, "1");
									}
									$body = quoted_printable_decode($body);
									$body = imap_utf8($body);
					  				$body = str_replace($carimap, $carhtml, $body);
					
									$divider = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_divider');
									$divider_bottom = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_divider_bottom');
									$x = strpos($body, $divider);
									$y = strpos($body, $divider_bottom);
									
									if ($x && $y) {
					
										$body = substr($body, $x+strlen($divider), strlen($body)-$x-strlen($divider)-1);
										$x = strpos($body, $divider_bottom);
										$body = trim(quoted_printable_decode(substr($body, 0, $x)));
										if (substr($body, 0, 4) == '<br>') { $body = substr($body, 4, strlen($body)-4); }
										
										// Replace <script> tags
										if (strpos($body, '<') !== FALSE) { str_replace('<', '&lt;', $body); }
										if (strpos($body, '>') !== FALSE) { str_replace('>', '&gt;', $body); }
										
										$snippet = trim(substr(quoted_printable_decode($body), 0, 100));
	
										// get category for topic
										$sql = "SELECT topic_category from ".$wpdb->prefix."cpcommunitie_topics WHERE tid = %d";
										$cid = $wpdb->get_var($wpdb->prepare($sql, $tid));
										
										// insert as a new reply
										if ($process) {

											if ($cpc_forum->add_reply($tid, $body, $uid, true)) {
	
												$snippet .= '<span style="color:green">'.__('Zum Forum hinzugefügt.', 'cp-communitie').'</span>';

						        				// Delete from mailbox
												imap_delete($mbox, $i);
	
											} else {
												
												$snippet = '<span style="color:red">'.__('Hinzufügen zum Forum fehlgeschlagen', 'cp-communitie').' '.$tid.'</span>';
												$snippet .= '<br>'.$subject;
												
											}		
											
										} else {
											$snippet ='<span style="color:green">'.__('Nicht hinzugefügt, nur überprüft.', 'cp-communitie').'</span>';
											$snippet .= '<br>'.$subject;
										}
										
														
									} else {
										
										$snippet = '<span style="color:red">'.__('Leere Antwort. Keine Grenzen gefunden', 'cp-communitie').'</span>';
										
									}
									
									
								} else {
		
									$tid = '<span style="color:red">'.__('Themen-ID nicht gefunden', 'cp-communitie').': '.$tid.'</span>';
									$snippet = $subject;
									
								}
								
							} else {
								
								$tid = '<span style="color:red">'.__('Keine TID im Betreff gefunden', 'cp-communitie').'.</span>';
								$snippet = '';
								
							}
							
							
						} else {
							
							$email = '<span style="color:red">'.$email.' '.__('nicht in Benutzern gefunden', 'cp-communitie').'.</span>';
							$tid = '';
							$snippet = '';
							
						}
		
						if ($output) {
							echo '<tr>';
							echo '<td>'.$email.'</td>';
							echo '<td>'.$prettydate.'</td>';
							echo '<td>'.$tid.'</td>';
							echo '<td>'.$snippet.'</td>';
							echo '</tr>';
						}
		
					}
					    		
					if ($output) echo '</tbody></table>';
											
				} else {
					
					if ($output) echo __('Keine Nachrichten gefunden', 'cp-communitie').'.';
					
				}

				// purge deleted mail
				imap_expunge($mbox);
				// close the mailbox
				imap_close($mbox); 
				
			} else {
			
				if ($output) echo __('Problem bei der Verbindung zum Mailserver', 'cp-communitie').': ' . imap_last_error().' '.__('(oder keine Internetverbindung)', 'cp-communitie').'.<br />';		
				if ($output) echo __('Überprüfe die Adresse Deines Mailservers und die Portnummer, den Benutzernamen und das Passwort', 'cp-communitie').'.';
				
			}
			
			$_SESSION['__cpc__mailinglist_lock'] = '';
			
		} else {
			if ($output) echo __('Wird gerade verarbeitet, bitte versuche es in ein paar Minuten erneut.', 'cp-communitie').'.<br />';		
		}
	}
}
	

// ----------------------------------------------------------------------------------------------------------------------------------------------------------


// Add "Alerts" to admin menu via hook
function __cpc__add_mailinglist_to_admin_menu()
{
	$hidden = get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('cpcommunitie_debug'.$hidden, __('Antwort per E-Mail', 'cp-communitie'), __('Antwort per E-Mail', 'cp-communitie'), 'manage_options', CPC_DIR.'/mailinglist_admin.php');
}
add_action('__cpc__admin_menu_hook', '__cpc__add_mailinglist_to_admin_menu');


?>