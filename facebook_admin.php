<?php
	
	global $wpdb;

	// Store any new values
    if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == 'cpcommunitie_facebook_menu' ) {
    	    	        
        $facebook_api = $_POST[ 'facebook_api' ];
        $facebook_secret = $_POST[ 'facebook_secret' ];

		update_option(CPC_OPTIONS_PREFIX.'_facebook_api', $facebook_api);
		update_option(CPC_OPTIONS_PREFIX.'_facebook_secret', $facebook_secret);

        // Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Facebook options saved', 'cp-communitie').".</p></div>";

    } else {
	    // Get values from database  
		$facebook_api = get_option(CPC_OPTIONS_PREFIX.'_facebook_api');
		$facebook_secret = get_option(CPC_OPTIONS_PREFIX.'_facebook_secret');
    }


  	echo '<div class="wrap">';

	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
		echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';
		
		__cpc__show_tabs_header('facebook');

		?>

			<h3>Installation</h3>

			<div style="margin:10px">
			<p><?php _e("A Facebook application is used to post messages to Facebook Walls - you need to create a Facebook application for your website:", 'cp-communitie') ?></p>

			<ol>
				<li><?php _e('Log in to ', 'cp-communitie'); ?><a target='_blank' href="http://www.facebook.com">Facebook</a>.</li> 
				<li><?php _e('Go', 'cp-communitie'); ?> <a target='_blank' href='https://developers.facebook.com/apps'><?php _e('here', 'cp-communitie'); ?></a>. </li> 
				<li><?php _e('Click on ', 'cp-communitie'); ?><img src="<?php echo plugin_dir_url( __FILE__ ) ?>/library/create_app.gif" /><?php _e(' button', 'cp-communitie'); ?></li> 
				<li><?php _e('Enter an <strong>App Display Name</strong> (that will appear under Facebook Wall posts), eg: Example Web Site', 'cp-communitie'); ?></li> 
				<li><?php _e('You can leave <strong>App Namespace</strong> blank and ignore <strong>Web Hosting</strong>, click on Continue', 'cp-communitie'); ?></li> 
				<li><?php _e('Enter the security check words to continue to the next screen', 'cp-communitie'); ?></li> 
				<li><?php _e('Disable <strong>Sandbox Mode</strong>', 'cp-communitie'); ?></li> 
				<li><?php _e('Click on <strong>Website with Facebook Login</strong> and enter your site URL, eg: ', 'cp-communitie'); ?><?php echo str_replace('http:/', 'http://', str_replace('//', '/', get_bloginfo('wpurl').'/')); ?> <?php _e('(including trailing slash).', 'cp-communitie'); ?></li> 
				<li><?php _e('Click on <strong>Save Changes</strong> on Facebook', 'cp-communitie'); ?></li> 
				<li><?php _e('Copy and Paste the <strong>App ID</strong> and <strong>App Secret</strong> below, and click on the Save Changes button below', 'cp-communitie'); ?></li> 
			</ol>
			</div>
	
			<h3><?php _e('Facebook Application values', 'cp-communitie') ?></h3>

			<form method="post" action=""> 
			<input type="hidden" name="cpcommunitie_update" value="cpcommunitie_facebook_menu">

			<table class="form-table __cpc__admin_table"> 

				<tr valign="top"> 
				<td scope="row"><label for="facebook_api"><?php _e('Facebook Application ID', 'cp-communitie'); ?></label></td> 
				<td><input name="facebook_api" type="text" id="facebook_api"  value="<?php echo $facebook_api; ?>" style="width:250px" /> 
				<span class="description"><?php echo __('Also called your OAuth client_id', 'cp-communitie'); ?></td> 
				</tr> 

				<tr valign="top"> 
				<td scope="row"><label for="facebook_secret"><?php _e('Facebook Application Secret', 'cp-communitie'); ?></label></td> 
				<td><input name="facebook_secret" type="text" id="facebook_secret"  value="<?php echo $facebook_secret; ?>" style="width:250px" /> 
				<span class="description"><?php echo __('Also called your OAuth client_secret', 'cp-communitie'); ?></td> 
				</tr> 

			<?php
			echo '</table>';

			echo '<p class="submit" style="margin-left:6px;">';
			echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Save Changes', 'cp-communitie').'" />';
			echo '</p>';
			echo '</form>';
			
			echo '<h3>'.__('Example Facebook Application values', 'cp-communitie').'</h3>'; 
			
			echo '<p>'.__('The following settings are used with the www.cpcymposium.com website, as an example.', 'cp-communitie').'</p>';
			
			echo '<img src="'.plugin_dir_url( __FILE__ ).'/images/facebook_admin_screenshot.png" />';
			
		__cpc__show_tabs_header_end();
			
	echo '</div>';
	
?>
