<?php
/*
CP Community RSS Feed
Description:  Plugin to provide RSS feed of profile pages (to follow members activity, if they have permitted it)
*/

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

/* ====================================================================== MAIN =========================================================================== */


function __cpc__rss_main() {
	// Although there is nothing to put here, it is used to information Wordpress that it is activated.
}


/* ===================================================================== ADMIN =========================================================================== */


function __cpc__rss_init()
{

}
add_action('init', '__cpc__rss_init');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------


/* ================================================================== SET SHORTCODE ====================================================================== */

// Not applicable.

/* ====================================================== HOOKS/FILTERS INTO WORDPRESS/CP Community ====================================================== */

function __cpc__add_rss_icon($html,$uid1,$uid2,$privacy,$is_friend,$extended)  
{  

	global $wpdb;
	$rss_share = __cpc__get_meta($uid1, 'rss_share');

	if ($rss_share == 'on') {

		$display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $uid1));

		$html = "<div id='__cpc__rss_icon' title='".$display_name."'></div>".$html;
	}		
	return $html;
}  
add_filter('__cpc__profile_wall_header_filter', '__cpc__add_rss_icon', 10, 6);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------


?>
