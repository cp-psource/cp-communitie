<?php

/**
 * Template Name: Demo mail page 5
 * Description: A Mail Page Template to demonstrate using classes
 *
 * @package ClassicPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 

/*
The following is shown here now for reference, but should be placed in a separate file
*/

?>

<script>

jQuery(document).ready(function() { 	

	// Show/hide compose form

	jQuery("#my-show-compose-form").on('click', function(){

		jQuery("#my-compose-form").show();

		jQuery("#my-inbox").hide();

		jQuery("#my-inbox-pages").hide();

		jQuery("#my-hide-compose-form").show();

		jQuery("#my-show-compose-form").hide();

	});

	jQuery("#my-hide-compose-form").on('click', function(){

		jQuery("#my-compose-form").hide();

		jQuery("#my-inbox").show();

		jQuery("#my-inbox-pages").show();

		jQuery("#my-hide-compose-form").hide();

		jQuery("#my-show-compose-form").show();

	});

});

</script>

<?php

// include the PHP class files, the path should match your server!
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc.php');
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_user.php');
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_mail.php');
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_ui.php');

$cpc = new cpc();
$cpc_mail = new cpc_mail(); // defaults to current logged in user
$cpc_ui = new cpc_ui();

/*

First we over-ride settings for mail page to ensure links to this page across go to
the correct page. Note that you will need to visit/reload this page
the first time the script is run, as various constants are set prior to this page template
loading. If you visit Admin->Installation the default values will be reset, 
which includes after upgrading CPC, so re-visit this page at least once after visiting 
the Installation page, to put things back to the new page. Alternatively, create a 
page that updates this (and maybe other) URLs that you can visit as admin once after upgrading CPC.

This is hardcoded to a particular page for now. If distributing to other user's this will
need to be dynamically set! Change it to make the URL of your new mail page, mine is as
per the tutorial (ie. a page called "AA Mail").
*/

$cpc->set_mail_url('/aa-mail');

?>

<!--
Links to styles used in this page template - shouldn't be included in the page template really,
but is included here to keep things simple for the tutorial at www.cpcymposium.com/blog.
Should be included in the theme header.php in the <HEAD> ... </HEAD> tags.
This also assumes the .css file is also in the current theme folder along with this page file. 
-->

<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/cpc_mail_page.css" />

<div id="primary">

	<div id="content" role="main">

	<?php

	if ( isset($_GET['action']) || isset($_POST['action']) ) {

		$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];

		// Check to see if action is to send a mail
		// Should be in a separate file that then comes back here to avoid resending of the page is refreshed!
		// But here for the sake of demonstrating and keeping it all in one file for the tutorial

		if ($action == 'sendmail') {

			if ( !$cpc_mail->sendmail($_POST['my-recipient']) ) {

				echo '<p>Problem sending mail, sorry.</p>';

			}

		}

		// Check to see if action is to delete a mail

		if ($action == 'delete') {

			if ( !$cpc_mail->set_as_deleted($_GET['id']) ) {

				echo '<p>Problem deleting mail, sorry.</p>';

			}

		}

	}
	

	if ( !isset($_GET['mail_id']) && !isset($_GET['mid'])) { // mid is added for compatibility with other functions

		// Compose new mail form

		echo '<a href="javascript:void(0)" id="my-show-compose-form">Compose new mail</a>';
		echo '<a href="javascript:void(0)" id="my-hide-compose-form" style="display:none;">Back to inbox...</a>';

		echo '<form action="" method="POST">';

		echo '<input type="hidden" name="action" value="sendmail" />';

		echo '<div id="my-compose-form">';

			echo '<div id="my-compose-form-to">';

				echo '<div style="float:left">';

					echo "To: ";

				echo '</div>';

				// Get list of friends IDs (have to be a friend to be able to send a mail)

				$cpc_user = new cpc_user();

				$friends = $cpc_user->get_friends();

				// Build an array, so we can sort by display name

				$friends_array = array();

				foreach ($friends as $friend) {				

					$friend_user = new $cpc_user($friend['id']);

					// Add row with id and friend's name

					$friends_array[] = array("id"=>$friend_user->id, "display_name"=>$friend_user->get_display_name());

				}

				// Add current user so can send yourself a mail (as you're not in your own friends list)

				$friends_array[] = array("id"=>$current_user->ID, "display_name"=>$current_user->display_name);

				

				// Now sort the array by display name

				$friends_array=__cpc__sub_val_sort($friends_array, 'display_name');
				

				// And show the drop down list of friends (you could choose a different selection method if you prefer)

				echo '<select name="my-recipient">';

				foreach ($friends_array as $i => $row) {				

					echo '<option value='.$row['id'].'>'.$row['display_name'].'</option>';

				}

				echo '</select>';

			echo '</div>';

			echo $cpc_ui->mail_subject("Subject of mail", "my-compose-form-subject").'<br />';

			echo $cpc_ui->mail_message("Message...", "my-compose-form-message").'<br />';

			echo $cpc_ui->mail_send_button("Send", "my-submit-button");

		echo '</div>';

		echo '</form>';

		// Inbox	

		echo '<div id="my-inbox">';

			echo '<div id="my-inbox-header">';

				echo '<div class="my-inbox-from">From</div>';

				echo '<div class="my-inbox-message">Subject</div>';

				echo '<div class="my-inbox-sent">Received</div>';

			echo '</div>';

			$start = isset($_GET['start']) ? $_GET['start'] : 0;

			$page_length = 5;

			$inbox = $cpc_mail->get_inbox($page_length, $start, 40, "", true, 75); // defaults to 10 messages from first available

			if ($inbox) {

				foreach($inbox as $mail) {

					$mail_unread = $mail['mail_read'] == 'on' ? '' : 'my-inbox-unread';

					echo '<div class="my-inbox-row '.$mail_unread.'">';

						echo '<div class="my-inbox-from">';

							echo '<div class="my-inbox-avatar">';

								echo $mail['avatar'];

							echo '</div>';

							echo $mail['display_name_link'];

						echo '</div>';

						echo '<div class="my-inbox-message">';

							echo '<div class="my-inbox-subject">';

								echo '<a href="'.$cpc->get_mail_url().'?mail_id='.$mail['mail_id'].'">'.$mail['mail_subject'].'</a>';

							echo '</div>';

							echo $mail['mail_message'];

						echo '</div>';

						$dt=explode(' ',$mail['mail_sent']);

						$d=explode('-',$dt[0]);

						$t=explode(':',$dt[1]);

						echo '<div class="my-inbox-sent">';

							echo $d[2].' '.substr(__cpc__get_monthname($d[1]),0,3).' '.$d[0].', '.$t[0].':'.$t[1].'<br />';

							echo '<a href="'.$cpc->get_mail_url().'?start='.$start.'&action=delete&id='.$mail['mail_id'].'"><img src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/delete.png" /></a>';

						echo '</div>';

					echo '</div>';

				}

			} else {

				echo '<div class="my-inbox-row">';

					echo "No mail to show....";

				echo '</div>';

			}

		echo '</div>';

		// Show "pages" of messages (if more than 1)

		$inbox_count = $cpc_mail->get_inbox_count();

		$pages = ($inbox_count % $page_length) == 0 ? $inbox_count / $page_length : floor($inbox_count / $page_length) + 1;

		echo '<div id="my-inbox-pages">';

		if ($pages > 1) {

			$current_page = floor($start/$page_length)+1;

			if ($current_page > 1) echo '<a href="'.$cpc->get_mail_url().'?start=0">First</a> ';

			if ($current_page > 1) echo '<a href="'.$cpc->get_mail_url().'?start='.((($current_page-1)*$page_length)-$page_length).'">Previous</a> ';

			for ($p=1; $p<=$pages; $p++) {

				if ($p==$current_page) {

					echo ' <strong>'.$p.'</strong>';

				} else {

					echo ' <a href="'.$cpc->get_mail_url().'?start='.(($p*$page_length)-$page_length).'">'.$p.'</a>';

				}

			}

			if ($current_page < $pages) echo ' <a href="'.$cpc->get_mail_url().'?start='.((($current_page+1)*$page_length)-$page_length).'">Next</a>';

			if ($current_page < $pages) echo ' <a href="'.$cpc->get_mail_url().'?start='.(($pages*$page_length)-$page_length).'">Last</a>';

		}	

		echo '</div>';	

	} else {

		// View message	

		$id = isset($_GET['mail_id']) ? $_GET['mail_id'] : $_GET['mid']; // To ensure compatibility

		$message = $cpc_mail->get_message($id); // Ge message details

		if ($message) {

			$sender = new cpc_user($message->mail_from);

			$dt=explode(' ',$message->mail_sent);

			$d=explode('-',$dt[0]);

			$t=explode(':',$dt[1]);

			echo '<a href="'.$cpc->get_mail_url().'">Back to inbox...</a>';

			echo '<div id="my-mail">';

				echo '<div id="my-mail-avatar">'.$sender->get_avatar(120).'</div>';

				echo '<div id="my-mail-from"><div class="my-mail-label"></span>From:</div> '.$sender->get_profile_url().'</div>';

				echo '<div id="my-mail-subject"><div class="my-mail-label">Subject:</div> '.stripslashes($message->mail_subject).'</div>';

				echo '<div id="my-mail-sent"><div class="my-mail-label">Received:</div> '.$d[2].' '.substr(__cpc__get_monthname($d[1]),0,3).' '.$d[0].', '.$t[0].':'.$t[1].'</div>';

				echo '<div id="my-mail-message">'.__cpc__make_url(stripslashes($message->mail_message)).'</div>';

			echo '</div>';

		} else {

			echo "Invalid Mail ID parameter, sorry.";

		}

		// Update mail read flag

		$cpc_mail->set_as_read($message->mail_mid);

	}

	?>

	</div><!-- #content -->

</div><!-- #primary -->

<?php get_footer(); ?>

