<?php
/*
CP Community Facebook
Description: Facebook Status plugin compatible with CP Community. Activate to use.
*/

	
// Get constants
require_once(dirname(__FILE__).'/default-constants.php');


// Function to WordPress knows this plugin is activated
function __cpc__facebook()  
{  
	        			
	return 'cp-communitie';
	exit;
		
}


// Add plugin to admin menu via hook
function cpcommunitie_add_facebook_to_admin_menu()
{
	$hidden = get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('cpcommunitie_debug'.$hidden, __('Facebook', CPC_TEXT_DOMAIN), __('Facebook', CPC_TEXT_DOMAIN), 'manage_options', CPC_DIR.'/facebook_admin.php');
}
add_action('__cpc__admin_menu_hook', 'cpcommunitie_add_facebook_to_admin_menu');


?>
