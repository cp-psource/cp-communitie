<?php
/**
 * Template Name: Demo profile page
 * Description: A Profile Page Template to demonstrate using classes
 *
 * @package ClassicPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 

// include the PHP class files, the path should match your server!
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_user.php');
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_ui.php');

$cpc_user = new cpc_user();
?>

		<div id="primary">
			<div id="content" role="main">

<?php

			echo $cpc_user->get_avatar().'<br />';
			echo $cpc_user->get_display_name().'<br />';
			echo 'User login: '.$cpc_user->get_user_login().'<br />';
			echo 'Email: '.$cpc_user->get_user_email().'<br />';
			echo 'City: '.$cpc_user->get_city().'<br />';
			echo 'Country: '.$cpc_user->get_country();

?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
