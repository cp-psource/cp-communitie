<?php
/*
CP Community Mobile
Description: Mobile, SEO and Accessibility plugin compatible with CP Community. Activate and read instructions on Mobile tab on the <a href='admin.php?page=__cpc__mobile_menu'>options page</a>.
*/


/* ***************************************************** GROUP PAGE ***************************************************** */

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

global $wpdb;

// Added to page load to check for mobile
add_filter('__cpc__profile_header_filter', '__cpc__mobile_check', 10, 2);
function __cpc__mobile_check($html, $uid1='') {

	require_once(dirname(__FILE__).'/mobile-files/mobile_check.php');
	if (get_option(CPC_OPTIONS_PREFIX.'_mobile_useragent'))
		echo $useragent.'<br>';
	if (get_option(CPC_OPTIONS_PREFIX.'_mobile_useragent') && $mobile)
		echo 'Mobile/tablet detected<br>';
	
	$forum = __cpc__get_url('forum').'/';
	$profile = __cpc__get_url('profile').'/';
	$url = $_SERVER["REQUEST_URI"];
	
	if (strpos($profile, $url) || strpos($forum, $url)) {
		if ($mobile) {
			if (get_option(CPC_OPTIONS_PREFIX.'_mobile_notice') != 'hide') {
				$html = '<div id="mobile_notice">'.get_option(CPC_OPTIONS_PREFIX.'_mobile_notice').'</div>'.$html;
			}
		}
	}
	
	return $html;
	
}


// Function to WordPress knows this plugin is activated
function __cpc__mobile()  
{  

	// Add to WP admin menu
	return 'cp-communitie';
	exit;
		
}

// Add plugin to admin menu via hook
function __cpc__add_mobile_to_admin_menu()
{
	$hidden = get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('cpcommunitie_debug'.$hidden, __('Mobile', CPC_TEXT_DOMAIN), __('Mobile', CPC_TEXT_DOMAIN), 'manage_options', '__cpc__mobile_menu', '__cpc__mobile_menu');
}
add_action('__cpc__admin_menu_hook', '__cpc__add_mobile_to_admin_menu');

function __cpc__mobile_menu() {

		global $wpdb;
		
    	// See if the user has posted Mobile settings
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__mobile_menu' ) {
    	    	        
			update_option(CPC_OPTIONS_PREFIX.'_mobile_topics', $_POST['mobile_topics']);
			update_option(CPC_OPTIONS_PREFIX.'_mobile_notice', stripslashes($_POST['mobile_notice']));
			update_option(CPC_OPTIONS_PREFIX.'_mobile_useragent', isset($_POST['mobile_useragent']) ? $_POST['mobile_useragent'] : '');
			echo "<div class='updated slideaway'><p>".__('Saved', CPC_TEXT_DOMAIN).".</p></div>";

	    }
	 
	 	// Check for default values
	 	if (!get_option(CPC_OPTIONS_PREFIX.'_mobile_notice'))
	 		update_option(CPC_OPTIONS_PREFIX.'_mobile_notice', "<a href='/m'>Go Mobile!</a>");
	    // Get values from database  
		$mobile_topics = get_option(CPC_OPTIONS_PREFIX.'_mobile_topics');
		$mobile_notice = get_option(CPC_OPTIONS_PREFIX.'_mobile_notice');
		$mobile_useragent = get_option(CPC_OPTIONS_PREFIX.'_mobile_useragent');

	  	echo '<div class="wrap">';

		  	echo '<div id="icon-themes" class="icon32"><br /></div>';
		  	echo '<h2>'.sprintf(__('%s Options', CPC_TEXT_DOMAIN), CPC_WL).'</h2><br />';
		
			__cpc__show_tabs_header('mobile');

			?>
	
					<form method="post" action=""> 
					<input type="hidden" name="cpcommunitie_update" value="__cpc__mobile_menu">
		
					<table class="form-table"> 
		
						<tr><td colspan="2"><h2><?php _e('Options', CPC_TEXT_DOMAIN) ?></h2></td></tr>

						<tr valign="top"> 
						<td scope="row"><label for="mobile_notice"><?php _e('Mobile/Tablet notice', CPC_TEXT_DOMAIN); ?></label></td> 
						<td><input name="mobile_notice" type="text" id="mobile_notice"  value="<?php echo $mobile_notice; ?>" style="width:300px" /> <br />
						<span class="description">
							<?php echo __('Text shown at the top of relevant page if a mobile/tablet is detected (HTML/links permitted).<br />Enter \'hide\' to avoid displaying.', CPC_TEXT_DOMAIN); ?>
						</span></td> 
						</tr> 
	
						<tr valign="top"> 
						<td scope="row"><label for="mobile_topics"><?php _e('Maximum number of topics', CPC_TEXT_DOMAIN); ?></label></td> 
						<td><input name="mobile_topics" type="text" id="mobile_topics"  value="<?php echo $mobile_topics; ?>" style="width:50px" /> 
						<span class="description"><?php echo __('Threads view is also limited to last 7 days', CPC_TEXT_DOMAIN); ?></td> 
						</tr> 

						<tr valign="top"> 
						<td scope="row"><label for="mobile_useragent"><?php _e('Display user agent', CPC_TEXT_DOMAIN); ?></label></td>
						<td>
						<input type="checkbox" name="mobile_useragent" id="mobile_useragent" <?php if ($mobile_useragent == "on") { echo "CHECKED"; } ?>/>
						<span class="description"><?php echo __('For admin use only to check mobile device user agent', CPC_TEXT_DOMAIN); ?></span></td> 
						</tr> 
					
					</table>
		 					
				<p class="submit" style="margin-left:6px;">
				<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes', CPC_TEXT_DOMAIN); ?>" />
				</p>
				</form>

				<table class="form-table"><tr><td colspan="2">
				<h2><?php _e('Installation steps', CPC_TEXT_DOMAIN) ?></h2>

				<div style="margin:10px">
				<p><?php _e("To install the Mobile/SEO/Accessibility plugin on your site:", CPC_TEXT_DOMAIN) ?></p>

				<ol>
					<li>In the directory where WordPress is installed, create a folder for your mobile version, for example '/m'.</li>
					<li>For example, for /m create <?php echo str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'); ?>m to create <?php echo str_replace('//', '/', get_bloginfo('wpurl').'/'); ?>m</li>
					<li>Unzip <strong>mobile-files.zip</strong> (it can be found in your <?php echo CPC_WL; ?> plugin folder).</li>
					<li>Copy the <strong>extracted contents</strong> into the folder from step 2.</li>
				</ol>
				</div>
				</td></tr></table>

				<table class="form-table"><tr><td colspan="2">
				<h2><?php echo sprintf(__('Mobile version of %s', CPC_TEXT_DOMAIN), CPC_WL); ?></h2>
		
				<div style="margin:10px">
				<p>On your mobile device/phone browse to (for example) <a target='_blank' href='<?php echo str_replace('http:/', 'http://', str_replace('//', '/', get_bloginfo('wpurl').'/')); ?>m'><?php echo str_replace('http:/', 'http://', str_replace('//', '/', get_bloginfo('wpurl').'/')); ?>m</a></p>
				</div>

				</td></tr></table>

				<table class="form-table"><tr><td colspan="2">
				<h2><?php echo sprintf(__('Accessible version of %s', CPC_TEXT_DOMAIN), CPC_WL); ?></h2>
		
				<div style="margin:10px">
				<p>To force the mobile version to show in a normal browser, add ?a=1. For example, <a target='_blank' href='<?php echo get_bloginfo('wpurl'); ?>/m?a=1'><?php echo get_bloginfo('wpurl'); ?>/m?a=1</a></p>
				</div>
				</td></tr></table>

				<table class="form-table"><tr><td colspan="2">
				<h2><?php echo __('Search Engines', CPC_TEXT_DOMAIN); ?></h2>
		
				<div style="margin:10px">
				<p>Submit the URL (for example) <?php echo get_bloginfo('wpurl'); ?>/m to search engines. When people visit the link indexed they will automatically be redirected to the full site (unless on a mobile device).</p>
				</div>
				</td></tr></table>

			<?php __cpc__show_tabs_header_end(); ?>
		</div>
	<?php
}


?>
