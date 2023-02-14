<?php
include_once('../wp-config.php');
include_once(dirname(__FILE__).'/mobile_check.php');

global $wpdb, $current_user;

require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc.php');
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_ui.php');
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_user.php');

$cpc = new cpc();
$cpc_user = new cpc_user($cpc->get_current_user_page()); // default to current user, or pass a user ID

// Redirect if not on a mobile
if (!$mobile) {
	header('Location: ./..');
}

?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo get_bloginfo('name');?></title>
<meta charset="UTF-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
<?php if ($big_display) { ?>
	<link rel="stylesheet" type="text/css" href="bigdisplay.css" />
<?php } ?>

</head>
<body>


<?php

if ( !is_user_logged_in() ) {

	include_once('./header_loggedout.php');

	echo '<br /><br />';
	echo '<input type="submit" onclick="location.href=\'login.php?'.$a.'\'" class="submit small blue fullwidth" value="'.__('Login', 'cp-communitie').'" />';
	echo '<br /><br />';
	
} else {

	include_once('./header_loggedin.php');
	show_header('[home,forum,topics,replies]');

	$friends = $cpc_user->get_friends(100);
	if ($friends) {
		foreach ($friends as $friend) {
			$f = new cpc_user($friend['id']);
			echo '<div class="__cpc__friends_div">';
				echo '<div class="__cpc__friends_avatar">'.$f->get_avatar(128, false).'</div>';
				echo '<div class="friends_info">';
					echo '<a href="index.php?'.$a.'&uid='.$friend['id'].'">'.stripslashes($f->display_name).'</a><br />';
					echo __('Last active', 'cp-communitie').' '.__cpc__time_ago($friend['last_activity']).'<br />';
					$post = $f->get_latest_activity();
					$post = str_replace(__('Started a new forum topic:', 'cp-communitie'), __('Started ', 'cp-communitie'), $post);
					echo $post;
				echo '</div>';
			echo '</div>';
		}
	} else {
		echo __('No friends yet :(', 'cp-communitie');
	}
	
}

include_once(dirname(__FILE__).'/footer.php');	

?>
</body>
</html>
