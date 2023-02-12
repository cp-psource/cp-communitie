<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>
<?php
echo '<h2>'.sprintf(__('%s Options', 'cp-communitie'), CPC_WL).'</h2><br />';

__cpc__show_tabs_header('events');

	global $wpdb;
    // See if the user has posted profile settings
    if( isset($_POST[ 'cpcommunitie_events_updated' ]) ) {

	 	// Update *******************************************************************************
		update_option(CPC_OPTIONS_PREFIX."_events_global_list", $_POST[ 'cpcommunitie_events_global_list' ]);
		update_option(CPC_OPTIONS_PREFIX.'_events_user_places', isset($_POST[ 'cpcommunitie_events_user_places' ]) ? $_POST[ 'cpcommunitie_events_user_places' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_events_use_wysiwyg', isset($_POST[ 'cpcommunitie_events_use_wysiwyg' ]) ? $_POST[ 'cpcommunitie_events_use_wysiwyg' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_events_hide_expired', isset($_POST[ 'cpcommunitie_events_hide_expired' ]) ? $_POST[ 'cpcommunitie_events_hide_expired' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_events_sort_order', isset($_POST[ 'cpcommunitie_events_sort_order' ]) ? $_POST[ 'cpcommunitie_events_sort_order' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_events_calendar', isset($_POST[ 'cpcommunitie_events_calendar' ]) ? $_POST[ 'cpcommunitie_events_calendar' ] : 'list');

		// Included roles
		if (isset($_POST['dir_level'])) {
	   		$range = array_keys($_POST['dir_level']);
	   		$level = '';
   			foreach ($range as $key) {
				$level .= $_POST['dir_level'][$key].',';
	   		}
		} else {
			$level = '';
		}

		update_option(CPC_OPTIONS_PREFIX.'_events_profile_include', serialize($level));

        // Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Saved', 'cp-communitie').".</p></div>";
		
    }

	// Get option value
	$__cpc__events_global_list = get_option(CPC_OPTIONS_PREFIX."_events_global_list") ? get_option(CPC_OPTIONS_PREFIX."_events_global_list") : '';

	?>

	<form method="post" action=""> 
	<input type='hidden' name='cpcommunitie_events_updated' value='Y'>
	<table class="form-table __cpc__admin_table"> 

	<tr><td colspan="2"><h2><?php _e('Options', 'cp-communitie') ?></h2></td></tr>

	<tr valign="top"> 
	<td scope="row"><label for="cpcommunitie_events_global_list"><?php _e('Global events list', 'cp-communitie'); ?></label></td>
	<td><input name="cpcommunitie_events_global_list" type="text" style="width:150px" id="cpcommunitie_events_global_list" value="<?php echo $__cpc__events_global_list; ?>" class="regular-text" /> 
	<span class="description"><?php echo __('Limits the members included when using [cpcommunitie-events]. Enter User IDs (comma separated) or leave blank for all.', 'cp-communitie'); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row"><label for="cpcommunitie_events_sort_order"><?php echo __('Reverse list order', 'cp-communitie'); ?></label></td> 
	<td><input type="checkbox" name="cpcommunitie_events_sort_order" id="cpcommunitie_events_sort_order" <?php if (get_option(CPC_OPTIONS_PREFIX.'_events_sort_order') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Select to reverse list order (by start date)', 'cp-communitie'); ?></td> 
	</tr> 
			
	<tr valign="top"> 
	<td scope="row"><label for="cpcommunitie_events_calendar"><?php echo __('Style of display', 'cp-communitie'); ?></label></td> 
	<td>
	<select name="cpcommunitie_events_calendar">
		<option value="list" <?php if (get_option(CPC_OPTIONS_PREFIX.'_events_calendar') != "calendar") { echo "SELECTED"; } ?>><?php _e('List', 'cp-communitie'); ?></option>
		<option value="calendar" <?php if (get_option(CPC_OPTIONS_PREFIX.'_events_calendar') == "calendar") { echo "SELECTED"; } ?>><?php _e('Calendar', 'cp-communitie'); ?></option>
	</select>
	<span class="description"><?php echo __('Display the global events page as a list or as a calendar', 'cp-communitie'); ?></td> 
	</tr> 
			
	<tr valign="top"> 
	<td scope="row"><label for="cpcommunitie_events_hide_expired"><?php echo __('Hide expired events', 'cp-communitie'); ?></label></td> 
	<td><input type="checkbox" name="cpcommunitie_events_hide_expired" id="cpcommunitie_events_hide_expired" <?php if (get_option(CPC_OPTIONS_PREFIX.'_events_hide_expired') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Do not display events that have finished (by end date)', 'cp-communitie'); ?></td> 
	</tr> 
			
	<tr valign="top"> 
	<td scope="row"><label for="cpcommunitie_events_user_places"><?php echo __('Non-admin event manager', 'cp-communitie'); ?></label></td> 
	<td><input type="checkbox" name="cpcommunitie_events_user_places" id="cpcommunitie_events_user_places" <?php if (get_option(CPC_OPTIONS_PREFIX.'_events_user_places') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Can non-administrators set up event bookings (or just list basic information)?', 'cp-communitie'); ?></td> 
	</tr> 
			
	<tr valign="top"> 
	<td scope="row"><label for="cpcommunitie_events_use_wysiwyg"><?php echo __('Use WYSIWYG editor', 'cp-communitie'); ?></label></td> 
	<td><input type="checkbox" name="cpcommunitie_events_use_wysiwyg" id="cpcommunitie_events_use_wysiwyg" <?php if (get_option(CPC_OPTIONS_PREFIX.'_events_use_wysiwyg') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Use WYSIWYG editor for more information and confirmation email (not summary)?', 'cp-communitie'); ?></td> 
	</tr> 

	<tr><td colspan="2"><h2><?php _e('Profile Menu Items', 'cp-communitie') ?></h2></td></tr>
			
	<tr valign="top"> 
	<td scope="row"><label for="dir_level"><?php echo __('Roles who get "My Events" on profile page', 'cp-communitie') ?></label></td> 
	<td>
	<?php

		// Get list of roles
		global $wp_roles;
		$all_roles = $wp_roles->roles;

		$dir_roles = get_option(CPC_OPTIONS_PREFIX.'_events_profile_include');

		foreach ($all_roles as $role) {
			echo '<input type="checkbox" name="dir_level[]" value="'.$role['name'].'"';
			if (strpos(strtolower($dir_roles), strtolower($role['name']).',') !== FALSE) {
				echo ' CHECKED';
			}
			echo '> '.$role['name'].'<br />';
		}	

	?>
	</td></tr>
	
	<?php
	echo '</table>';

	?>
	<table style="margin-left:10px; margin-top:10px;">						
		<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
		<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-events]</td>
			<td><?php echo __('Show all events on a site.', 'cp-communitie'); ?></td></tr>
	</table>
	<?php 	
	 					
	echo '<p class="submit" style="margin-left:12px">';
	echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Save Changes', 'cp-communitie').'" />';
	echo '</p>';
	echo '</form>';
					  
?>

<?php __cpc__show_tabs_header_end(); ?>
</div>
