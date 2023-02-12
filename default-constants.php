<?php

/**
 * Constants used for management purposes.
 * Do not edit unless you have been given instructions! If this file is changed, support will unfortunately not be provided.
 * Note: It is not permitted to re-sell this set of plugins as a commercial product without prior permission.
 * Important: Backup your site and database before making any changes.... just in case! You've been warned!
 * When upgrading, do so manually, then replace this file with your own version BEFORE accessing the website or admin area.
 */

/**
 * Can be changed post installation  ================================================================================================
 */
 
if ( !defined('CPC_WL') ) 						define('CPC_WL', 'CP Community'); 										// Long name
if ( !defined('CPC_WL_SHORT') ) 				define('CPC_WL_SHORT', 'CP Community');									// Alternative short name
if ( !defined('CPC_DIR') ) 						define('CPC_DIR', 'cp-communitie'); 										// Installed plugin folder (within ClassicPress plugins)
if ( !defined('CPC_WELCOME_MESSAGE') ) 			define('CPC_WELCOME_MESSAGE', '../cpc-welcome.html'); 					// Alternative file location of welcome message
if ( !defined('CPC_TEXT_DOMAIN') ) 				define('CPC_TEXT_DOMAIN', 'cp-communitie'); 								// Text domain for translations
if ( !defined('CPC_SHORTCODE_PREFIX') )			define('CPC_SHORTCODE_PREFIX', 'cpcommunitie');							// Prefix for shortcodes 
if ( !defined('CPC_HIDE_ACTIVATION') )			define('CPC_HIDE_ACTIVATION', false);									// Whether to hide activation code on Installation page
if ( !defined('CPC_HIDE_FOOTER') )				define('CPC_HIDE_FOOTER', false);										// Convenient way to permenantly hide the page footer
if ( !defined('CPC_HIDE_INSTALL_INFO') )		define('CPC_HIDE_INSTALL_INFO', false);									// Whether to hide additional info on Installation page
if ( !defined('CPC_HIDE_DASHBOARAD_W') )		define('CPC_HIDE_DASHBOARAD_W', false);									// Whether to hide CPC ClassicPress admin dashboard widget
if ( !defined('CPC_HIDE_PLUGINS') )				define('CPC_HIDE_PLUGINS', false);										// Whether to hide all plugins and menu (keep one non-CPC plugin active to avoid PHP warnings)
if ( !defined('CPC_CHANGE_PLUGINS') )			define('CPC_CHANGE_PLUGINS', false);									// Whether to re-brand plugins
/* Following are only used if CPC_CHANGE_PLUGINS is set to true and CPC_HIDE_PLUGINS set to false */
if ( !defined('CPC_CHANGE_DESC') )				define('CPC_CHANGE_DESC', 'CP Community plugin. All rights reserved.');	// Global CPC description, or false to skip
if ( !defined('CPC_CHANGE_VER') )				define('CPC_CHANGE_VER', '1');											// Version, or false to skip
if ( !defined('CPC_CHANGE_AUTHOR') )			define('CPC_CHANGE_AUTHOR', 'Simon Goodchild');							// Author, or false to skip
if ( !defined('CPC_CHANGE_AUTHORURI') )			define('CPC_CHANGE_AUTHORURI', 'https://cp-community.n3rds.work/');			// Author web address, or false to skip
if ( !defined('CPC_CHANGE_PLUGINURI') )			define('CPC_CHANGE_PLUGINURI', 'https://cp-community.n3rds.work/');			// Plugin web address, or false to skip

/* Allows ClassicPress plugin URL to be over-ridden */
if ( !defined('CPC_PLUGIN_DIR') ) 			define('CPC_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.CPC_DIR);

/**
 * References within code
 * You can also globally replace __cpc__ with __xxx__ in all files to rebrand internal code functions and CSS (make it unique!) 
 */
 
 
/**
 * Must not be changed post installation ============================================================================================
 */
 
if ( !defined('CPC_OPTIONS_PREFIX') )		define('CPC_OPTIONS_PREFIX', 'cpcommunitie'); 								// Prefix for ClassicPress options table (make this unique!!)



/**
 * Directory and URL of ClassicPress plugins ===========================================================================================
 */

if ( !defined('CPC_PLUGIN_DIR') ) 			define('CPC_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.CPC_DIR);
if ( !defined('CPC_PLUGIN_URL') ) 			define('CPC_PLUGIN_URL', plugins_url('', __FILE__));


?>
