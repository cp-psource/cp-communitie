<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>

<?php
echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';

__cpc__show_tabs_header('replybyemail');

	
	if (isset($_POST['check_pop3']) && $_POST['check_pop3'] == 'get') {
		__cpc__check_pop3(true,true);
		$_SESSION['__cpc__mailinglist_lock'] = '';
	}
	if (isset($_POST['check_pop3']) && $_POST['check_pop3'] == 'check') {
		__cpc__check_pop3(true,false);
		$_SESSION['__cpc__mailinglist_lock'] = '';
	}
	
	if (isset($_POST['check_pop3']) && $_POST['check_pop3'] == 'delete') {
			
		if (!isset($_SESSION['__cpc__mailinglist_lock']) || $_SESSION['__cpc__mailinglist_lock'] != 'locked') {
			
			$_SESSION['__cpc__mailinglist_lock'] = 'locked';
						
			global $wpdb;
			
		
			$server = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_server');
			$port = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_port');
			$username = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_username');
			$password = get_option(CPC_OPTIONS_PREFIX.'_mailinglist_password');

			echo '<h3>'.__('Deleting POP3 mailbox contents', 'cp-communitie').'</h3>';
			
			if ($mbox = imap_open ("{".$server.":".$port."/pop3}INBOX", $username, $password) ) {
				
				echo __('Connected', 'cp-communitie').', ';
				
				$num_msg = imap_num_msg($mbox);
				echo __('number of messages to be deleted', 'cp-communitie').': '.$num_msg.'<br />';
		
				if ($num_msg > 0) {

					for ($i = 1; $i <= $num_msg; ++$i) {

        				// Delete from mailbox
						imap_delete($mbox, $i);	
						
					}
				} else {
					
					echo __('No messages found', 'cp-communitie').'.';
					
				}

				// purge deleted mail
				imap_expunge($mbox);
				// close the mailbox
				imap_close($mbox); 
				
			} else {
			
				echo __('Problem connecting to mail server', 'cp-communitie').': ' . imap_last_error().' '.__('(or no internet connection)', 'cp-communitie').'.<br />';		
				echo __('Check your mail server address and port number, username and password', 'cp-communitie').'.';
				
			}
			
			$_SESSION['__cpc__mailinglist_lock'] = '';
			
		} else {
			if ($output) echo __('Currently processing, please try again in a few minutes.', 'cp-communitie').'.<br />';		
		}
	}
	
	if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == 'cpcommunitie_plugin_mailinglist' ) {
	
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_server', isset($_POST[ 'cpcommunitie_mailinglist_server' ]) ? $_POST[ 'cpcommunitie_mailinglist_server' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_port', isset($_POST[ 'cpcommunitie_mailinglist_port' ]) ? $_POST[ 'cpcommunitie_mailinglist_port' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_username', isset($_POST[ 'cpcommunitie_mailinglist_username' ]) ? $_POST[ 'cpcommunitie_mailinglist_username' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_password', isset($_POST[ 'cpcommunitie_mailinglist_password' ]) ? $_POST[ 'cpcommunitie_mailinglist_password' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_prompt', isset($_POST[ 'cpcommunitie_mailinglist_prompt' ]) ? $_POST[ 'cpcommunitie_mailinglist_prompt' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_divider', isset($_POST[ 'cpcommunitie_mailinglist_divider' ]) ? $_POST[ 'cpcommunitie_mailinglist_divider' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_divider_bottom', isset($_POST[ 'cpcommunitie_mailinglist_divider_bottom' ]) ? $_POST[ 'cpcommunitie_mailinglist_divider_bottom' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_cron', isset($_POST[ 'cpcommunitie_mailinglist_cron' ]) ? $_POST[ 'cpcommunitie_mailinglist_cron' ] : 900);
		update_option(CPC_OPTIONS_PREFIX.'_mailinglist_from', isset($_POST[ 'cpcommunitie_mailinglist_from' ]) ? $_POST[ 'cpcommunitie_mailinglist_from' ] : '');

		__cpc__check_pop3(true,false);
		$_SESSION['__cpc__mailinglist_lock'] = '';
	
	    // Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Saved', 'cp-communitie').".</p></div>";
		
	}
	
	?>
		
	<form action="" method="POST">
	
			<input type="hidden" name="cpcommunitie_update" value="cpcommunitie_plugin_mailinglist">
				
			<table class="form-table __cpc__admin_table"> 

			<tr><td colspan="2"><h2><?php echo __('Options', 'cp-communitie'); ?></h2></td></tr>
		
			<tr><td colspan="2">
				<?php echo __('Allows members to reply to forum topics by email (by replying to the notification received).', 'cp-communitie'); ?><br />
				<?php
				$cron = _get_cron_array();
				$schedules = wp_get_schedules();
				$date_format = _x( 'M j, Y @ G:i', 'Publish box date format', 'cp-communitie');
				foreach ( $cron as $timestamp => $cronhooks ) {
					foreach ( (array) $cronhooks as $hook => $events ) {
						foreach ( (array) $events as $key => $event ) {
							if ($hook == '__cpc__mailinglist_hook') {
								if ($timestamp-time() < 0) {
									echo __('Next scheduled run', 'cp-communitie').': '.date_i18n( $date_format, $timestamp ).'<br />';
								} else {
									echo __('Next scheduled run', 'cp-communitie').': '.date_i18n( $date_format, $timestamp ).' ';
									echo '('.sprintf(__('in %d seconds', 'cp-communitie'), $timestamp-time()).').<br />';
								}
							}
						}
					}
				}
				
				?>
				<?php echo __('Click on the button below to check for replies by email and add to the forum (this may take a few minutes).', 'cp-communitie'); ?>
			</td></tr>
			
			<tr><td colspan="2">
			
			</td></tr>
			
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_server"><?php echo __('Server', 'cp-communitie'); ?></label></td> 
			<td><input name="cpcommunitie_mailinglist_server" type="text" id="cpcommunitie_mailinglist_server"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_server'); ?>" /> 
			<span class="description"><?php echo __('Server URL or IP address, eg: mail.example.com', 'cp-communitie'); ?></td> 
			</tr> 
															
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_port"><?php echo __('Port', 'cp-communitie'); ?></label></td> 
			<td><input name="cpcommunitie_mailinglist_port" type="text" id="cpcommunitie_mailinglist_port"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_port'); ?>" /> 
			<span class="description"><?php echo __('Port used by mail server, eg: 110', 'cp-communitie'); ?></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_username"><?php echo __('Username', 'cp-communitie'); ?></label></td> 
			<td><input name="cpcommunitie_mailinglist_username" type="text" id="cpcommunitie_mailinglist_username"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_username'); ?>" /> 
			<span class="description"><?php echo __('Username of mail account', 'cp-communitie'); ?></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_password"><?php echo __('Password', 'cp-communitie'); ?></label></td> 
			<td><input name="cpcommunitie_mailinglist_password" type="password" id="cpcommunitie_mailinglist_password"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_password'); ?>" /> 
			<span class="description"><?php echo __('Password of mail account', 'cp-communitie'); ?></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_from"><?php echo __('Email sent from', 'cp-communitie'); ?></label></td> 
			<td><input name="cpcommunitie_mailinglist_from" type="text" id="cpcommunitie_mailinglist_from"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_from'); ?>" /> 
			<span class="description"><?php echo __('Email address to reply to, eg: forum@example.com', 'cp-communitie'); ?></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_prompt"><?php echo __('Prompt', 'cp-communitie'); ?></label></td> 
			<td><input style="width: 400px" name="cpcommunitie_mailinglist_prompt" type="text" id="cpcommunitie_mailinglist_prompt"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_prompt'); ?>" /> 
			<span class="description"><?php echo __('Line of text to appear as a prompt where to enter reply text', 'cp-communitie'); ?></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_divider"><?php echo __('Top divider', 'cp-communitie'); ?></label></td> 
			<td><input style="width: 400px" name="cpcommunitie_mailinglist_divider" type="text" id="cpcommunitie_mailinglist_divider"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_divider'); ?>" /> 
			<span class="description"><?php echo __('The top boundary of where replies should be entered', 'cp-communitie'); ?></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_divider_bottom"><?php echo __('Bottom divider', 'cp-communitie'); ?></label></td> 
			<td><input style="width: 400px" name="cpcommunitie_mailinglist_divider_bottom" type="text" id="cpcommunitie_mailinglist_divider_bottom"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_divider_bottom'); ?>" /> 
			<span class="description"><?php echo __('The bottom boundary of where replies should be entered', 'cp-communitie'); ?></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="cpcommunitie_mailinglist_cron"><?php echo __('Check Frequency', 'cp-communitie'); ?></label></td> 
			<td><input name="cpcommunitie_mailinglist_cron" type="text" id="cpcommunitie_mailinglist_cron"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_mailinglist_cron'); ?>" /> 
			<span class="description"><?php echo __('Frequency (in seconds) to check, uses ClassicPress cron schedule, so requires your site to be visited. Not too low!!', 'cp-communitie'); ?></td> 
			</tr> 
	
			</table> 	
		 
			<div style='margin-top:25px; margin-left:6px; float:left;'> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', 'cp-communitie'); ?>" /> 
			</div> 
			
	</form>		
	<form action="" method="POST">
		<input type='hidden' name='check_pop3' value='get' />
		<input type="submit" name="submit" class="button-primary" style="float:left;margin-top:25px; margin-left:10px;" value="<?php echo __('Check for replies now and process', 'cp-communitie'); ?>" /> 
	</form>
	<form action="" method="POST">
		<input type='hidden' name='check_pop3' value='check' />
		<input type="submit" name="submit" class="button-primary" style="float:left;margin-top:25px; margin-left:10px;" value="<?php echo __('Check for replies now (but don\'t process any)', 'cp-communitie'); ?>" /> 
	</form>
	<form action="" method="POST">
		<input type='hidden' name='check_pop3' value='delete' />
		<input type="submit" name="submit" class="button-primary" style="margin-top:25px; margin-left:10px;" value="<?php echo __('Delete POP3 mailbox contents', 'cp-communitie'); ?>" /> 
	</form>
		
<?php __cpc__show_tabs_header_end(); ?>
	
</div>
