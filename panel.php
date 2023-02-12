<?php
/*
CP Community Panel
Description: Panel bottom corner of screen to display new mail, friends online, etc. Also controls live chat windows and online status.
*/

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

/* ====================================================== PHP FUNCTIONS ====================================================== */

// Adds notification bar
function __cpc__add_notification_bar()  
{  

   	global $wpdb, $current_user;
	wp_get_current_user();

	$plugin = CPC_PLUGIN_URL;

	if ( is_user_logged_in() ) {

		$use_chat = get_option(CPC_OPTIONS_PREFIX.'_use_chat');
		if (get_option(CPC_OPTIONS_PREFIX.'_cpc_lite')) 
			$use_chat = ''; 
		$inactive = get_option(CPC_OPTIONS_PREFIX.'_online');
		$offline = get_option(CPC_OPTIONS_PREFIX.'_offline');
		if (get_option(CPC_OPTIONS_PREFIX.'_use_styles') == "on")
			$border_radius = get_option(CPC_OPTIONS_PREFIX.'_border_radius');


		?>
			
		<style>

			<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_styles') == "on") { 
				echo '.header_bg_blink {';
					echo 'background-color: '.get_option(CPC_OPTIONS_PREFIX.'_categories_background');
				echo '}';
			} ?>
			
			.__cpc__online_box {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
				<?php if (!function_exists('__cpc__profile')) {
					echo 'display: none';
				}?>
			}
			.__cpc__online_box-none {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
				<?php if (!function_exists('__cpc__profile')) {
					echo 'display: none';
				}?>
			}
			
			#__cpc__logout {
				background-image:url('<?php echo get_option(CPC_OPTIONS_PREFIX.'_images'); ?>/logout.gif');
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
			}
										
			.__cpc__email_box {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
				<?php if (!function_exists('__cpc__mail')) {
					echo 'display: none';
				}?>
			}
			.__cpc__email_box-read {
				background-image:url('<?php echo get_option(CPC_OPTIONS_PREFIX.'_images'); ?>/email.gif');
			}
			.__cpc__email_box-unread {
				background-image:url('<?php echo get_option(CPC_OPTIONS_PREFIX.'_images'); ?>/emailunread.gif');
			}

			.__cpc__friends_box {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
				<?php if (!function_exists('__cpc__profile')) {
					echo 'display: none';
				}?>
			}
			.__cpc__friends_box-none {
				background-image:url('<?php echo get_option(CPC_OPTIONS_PREFIX.'_images'); ?>/friends.gif');
			}
			.__cpc__friends_box-new {
				background-image:url('<?php echo get_option(CPC_OPTIONS_PREFIX.'_images'); ?>/friendsnew.gif');
			}
			.corners {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
			}
		</style>

		
		<?php
		
		
		echo "<!-- NOTIFICATION BAR -->";

			if (is_user_logged_in()) {

				// DIV for who's online
				echo "<div id='__cpc__who_online'>";
				
					echo "<div id='__cpc__who_online_header'>";
						echo "<div id='__cpc__who_online_close'><img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/close.png' alt='".__("Schließen", 'cp-communitie')."' /></div>";
						echo "<div id='__cpc__who_online_close_label'>".sprintf(__("%s Status", 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friends'))."</div>";
					echo "</div>";
					echo "<div id='__cpc__friends_online_list'></div>";
													
				echo "</div>";
								
				// Logout button DIV
				echo "<div id='__cpc__logout_div'>";
					echo "<div id='__cpc__online_status_div'>";
						echo "<input type='checkbox' id='__cpc__online_status' ";
						if (__cpc__get_meta($current_user->ID, 'status') == "offline") { echo " CHECKED"; }
						echo "> ".__("Offline erscheinen?", 'cp-communitie');
					echo "</div>";
					echo "<div id='__cpc__online_status_div'>";
						echo "<img style='float: left; margin-left: 1px; margin-right: 5px;' src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/close.png' alt='".__("Ausloggen", 'cp-communitie')."' />";
						echo "<a id='__cpc__logout-link' href='javascript:void(0);'>".__("Ausloggen", 'cp-communitie')."</a>";
					echo "</div>";
				echo "</div>";

				echo '<div id="__cpc__notification_bar" >';

					// Log out
					echo "<div id='__cpc__logout'>".__('Ausloggen', 'cp-communitie')."</div>";

					// Pending Friends
					if (function_exists('__cpc__profile')) {
						echo "<div id='__cpc__friends_box' title='".sprintf(__("Gehe zu %s", 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friends'))."' class='__cpc__friends_box __cpc__friends_box-none'>";
					} else {
						echo "<div id='__cpc__friends_box' style='display:none'>";
					}
					echo "</div>";
					
					// Unread Mail
					if (function_exists('__cpc__mail')) {
						echo "<div id='__cpc__email_box' title='".__("Gehe zu Mail", 'cp-communitie')."' class='__cpc__email_box __cpc__email_box-read'>";
					} else {
						echo "<div id='__cpc__email_box' style='display:none'>";
					}
					echo "</div>";
	
					// Friends Status/Online
					echo "<div id='__cpc__online_box' class='__cpc__online_box-none'></div>";
							
			echo "</div>";

		} 	

		// Re-open any windows (and add DIV for sound alert)
		echo '<div id="player_div"></div>';
		if ((get_option(CPC_OPTIONS_PREFIX.'__cpc__add_notification_bar_activated') || get_option(CPC_OPTIONS_PREFIX.'__cpc__add_notification_bar_network_activated'))) {
			// re-open any previous chatboxes
			@session_start();
			if (isset($_SESSION['chatbox_status'])) {
				print '<script type="text/javascript">';
				print 'jQuery(function() {';
				foreach ($_SESSION['chatbox_status'] as $openedchatbox) {
					if (isset($openedchatbox['partner_id']) && isset($openedchatbox['partner_username']) && isset($openedchatbox['chatbox_status'])) 
						print 'PopupChat('.$openedchatbox['partner_id'].',"'.$openedchatbox['partner_username'].'",'.$openedchatbox['chatbox_status'].',1);';
				}
				print "});";
				print '</script>';
			}
		}
	}

}  

if (!is_admin()) {
	add_action('wp_footer', '__cpc__add_notification_bar', 1);
}


?>
