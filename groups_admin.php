<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>
<?php
echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';

__cpc__show_tabs_header('groups');
?>

<?php

	global $wpdb;
	
    // See if the user has posted profile settings
    if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == 'cpcommunitie-groups' ) {

		$group_all_create = (isset($_POST[ 'group_all_create' ])) ? $_POST[ 'group_all_create' ] : '';
		$group_invites = (isset($_POST[ 'group_invites' ])) ? $_POST[ 'group_invites' ] : '';
		$initial_groups = (isset($_POST[ 'initial_groups' ])) ? $_POST[ 'initial_groups' ] : '';
		$group_invites_max = $_POST[ 'group_invites_max' ];
		$group_max_members = ($_POST[ 'group_max_members' ] != '') ? $_POST[ 'group_max_members' ] : '0';

		update_option(CPC_OPTIONS_PREFIX.'_group_all_create', $group_all_create);
		update_option(CPC_OPTIONS_PREFIX.'_group_invites', $group_invites);
		update_option(CPC_OPTIONS_PREFIX.'_group_invites_max', $group_invites_max);
		update_option(CPC_OPTIONS_PREFIX.'_initial_groups', $initial_groups);
		update_option(CPC_OPTIONS_PREFIX.'_group_max_members', $group_max_members);
		update_option(CPC_OPTIONS_PREFIX.'_use_group_templates', isset($_POST[ 'cpc_use_group_templates' ]) ? $_POST[ 'cpc_use_group_templates' ] : '');

		if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type')) {

			$default_menu_structure = '[Group]
Welcome=welcome
Settings=settings
Invite=invites
[Aktivität]
Group Activity=activity
Group Forum=forum
[Members]
Directory=members';

			update_option(CPC_OPTIONS_PREFIX.'_group_menu_structure', (isset($_POST['group_menu_structure']) && $_POST['group_menu_structure']) ? $_POST['group_menu_structure'] : $default_menu_structure);
		
		}		

        // Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Saved', 'cp-communitie').".</p></div>";
		
    }

    // Get values from database  
	$group_all_create = get_option(CPC_OPTIONS_PREFIX.'_group_all_create');
	$group_invites = get_option(CPC_OPTIONS_PREFIX.'_group_invites');
	$group_invites_max = get_option(CPC_OPTIONS_PREFIX.'_group_invites_max');
	$initial_groups = get_option(CPC_OPTIONS_PREFIX.'_initial_groups');
	$group_max_members = (get_option(CPC_OPTIONS_PREFIX.'_group_max_members')) ? get_option(CPC_OPTIONS_PREFIX.'_group_max_members') : '0';

	?>

	<form method="post" action=""> 
	<input type="hidden" name="cpcommunitie_update" value="cpcommunitie-groups">

	<table class="form-table __cpc__admin_table"> 

		<tr><td colspan="2"><h2><?php _e('Options', 'cp-communitie') ?></h2></td></tr>

		<tr valign="top"> 
		<td scope="row"><label for="cpc_use_group_templates"><?php echo __('Custom Group Page templates', 'cp-communitie'); ?></label></td>
		<td>
		<input type="checkbox" name="cpc_use_group_templates" id="cpc_use_group_templates" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_group_templates') == "on") { echo "CHECKED"; } ?>/>
		<span class="description"><?php echo sprintf(__('Activate <a href="%s">templates</a> for the group page (default layout used if not)', 'cp-communitie'), 'admin.php?page=cpcommunitie_templates#group_options'); ?></span></td> 
		</tr> 

		<tr valign="top"> 
		<td scope="row"><label for="group_all_create"><?php _e('All users can create', 'cp-communitie'); ?></label></td>
		<td>
		<input type="checkbox" name="group_all_create" id="group_all_create" <?php if ($group_all_create == "on") { echo "CHECKED"; } ?>/>
		<span class="description"><?php echo __('All users or restricted to administrators only', 'cp-communitie'); ?></span></td> 
		</tr> 

		<tr valign="top"> 
		<td scope="row"><label for="initial_groups"><?php _e('Default Groups', 'cp-communitie'); ?></label></td> 
		<td><input name="initial_groups" type="text" id="initial_groups"  value="<?php echo $initial_groups; ?>" /> 
		<span class="description"><?php echo __('Comma separated list of group ID\'s that new members are assigned to (leave blank for none)', 'cp-communitie'); ?></td> 
		</tr> 
		
		<tr valign="top"> 
		<td scope="row"><label for="group_invites"><?php _e('Allow group invites', 'cp-communitie'); ?></label></td>
		<td>
		<input type="checkbox" name="group_invites" id="group_invites" <?php if ($group_invites == "on") { echo "CHECKED"; } ?>/>
		<span class="description"><?php echo __("Allow group admin's to invite people to join via email.", 'cp-communitie'); ?></span></td> 
		</tr> 

		<tr valign="top"> 
		<td scope="row"><label for="group_max_members"><?php _e('Default maximum members', 'cp-communitie'); ?></label></td>
		<td><input name="group_max_members" style="width: 50px" type="text" id="group_max_members" value="<?php echo $group_max_members; ?>" class="regular-text" /> 
		<span class="description">
			<?php echo __('Maximum number of members a new group allows (can be changed in group settings), 0=unlimited.', 'cp-communitie'); ?>
		</span></td> 
		</tr> 

		<tr valign="top"> 
		<td scope="row"><label for="group_invites_max"><?php _e('Maximum invitations', 'cp-communitie'); ?></label></td>
		<td><input name="group_invites_max" style="width: 50px" type="text" id="group_invites_max" value="<?php echo $group_invites_max; ?>" class="regular-text" /> 
		<span class="description">
			<?php echo __('How many invitations to join the group can be sent out at one time (to avoid spamming from your server).', 'cp-communitie'); 
			__('Note: If people who are invited to join via email are not members they will be able to register first (if the option is set in ClassicPress).', 'cp-communitie'); ?>
		</span></td> 
		</tr> 

	<?php

	if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type')) { ?>

	<tr><td colspan="2"><h2><?php _e('Group Menu Items', 'cp-communitie') ?></h2></td></tr>

	<tr valign="top"> 
	<td scope="row"><label for="group_invites_max"><?php _e('Menu structure', 'cp-communitie'); ?></label></td>
	<td>
	<textarea rows="12" cols="40" name="group_menu_structure" id="group_menu_structure"><?php echo get_option(CPC_OPTIONS_PREFIX.'_group_menu_structure') ?></textarea><br />
	<span class="description"><?php echo sprintf(__('Only applicable to the horizontal version of the group page menu, set on the Plus options tab.', 'cp-communitie'), CPC_WL); ?></span><br />
	<a id="__cpc__reset_group_menu" href="javascript:void(0)"><?php echo __('Reset the above...', 'cp-communitie'); ?></a>
	</td> 
	</tr> 

	<?php } 

	echo '</table>';
	
	echo '<p class="submit" style="margin-left:6px;">';
	echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Save Changes', 'cp-communitie').'" />';
	echo '</p>';
	echo '</form>';
	
	echo '<h2>'.__('Delete group / manage group members', 'cp-communitie').'</h2>';

	echo '<p style="margin-left:10px">';	
	echo __("Select a group to show current members. Then type part of a member's display name or username to search. Keep blank for all users.", 'cp-communitie').'<br />';
	echo __("You cannot add or remove the group administrator. Group administrators are not displayed.", 'cp-communitie').'<br />';
	echo '</p>';


	$sql = "SELECT * FROM ".$wpdb->prefix."cpcommunitie_groups ORDER BY group_order, name";
	$groups = $wpdb->get_results($sql);
	
	if ($groups) {
	
		echo '<div style="margin-left:10px">';
		echo '<select id="group_list" style="margin-bottom:10px">';
		echo '<option value=0>'.__('-- Select a group --', 'cp-communitie').'</option>';
		foreach ($groups as $group) {
			echo '<option value='.$group->gid.'>'.$group->gid.': '.stripslashes($group->name).' (order = '.$group->group_order.')</option>';
		}
		echo '</select> ';
		echo '<input type="text" style="margin-left:180px" id="user_list_search" /> '; 
		echo '<input type="submit" id="user_list_search_button" name="Submit" class="button-primary" value="'.__('Search', 'cp-communitie').'" />';
		echo '</div>';
		
		echo '<div id="group_meta" style="display:none; margin-left:10px;">';
		echo '<form action="#" method="POST">';
		echo '<input type="hidden" name="action" value="update_group_order">';
		echo '<strong>Group Order (lower shown first)</strong><br />';
		echo '<input type="group_meta_order" style="width:50px" value="'.$group->group_order.'" />';
		echo '<input type="submit" class="button-secondary" value="Update" />';
		echo '</form>';
		echo '</div>';

		echo '<div id="group_list_delete" style="margin-left:10px; display:none;">';
		echo '<a href="javascript:void(0)" id="group_list_delete_link">'.__('Delete this group', 'cp-communitie').'</a>';
		echo '</div>';
		echo '<div id="group_order_update" style="margin-left:10px; display:none;">';
		echo '<a href="javascript:void(0)" id="group_order_update_link">'.__('Change this group&apos;s order', 'cp-communitie').'</a>';
		echo '</div>';
		
		echo '<div style="clear:both; margin:10px; float:left;">';
		echo '<strong>'.__('Available users', 'cp-communitie').'</strong><br />';
		echo '<div id="user_list" style="width:300px; height:300px; overflow:auto; background-color:#fff; padding:4px; border:1px solid #aaa;"></div>';
		echo '</div>';
	
		echo '<div style="margin-top:10px; margin-bottom:10px;float:left;">';
		echo '<strong>'.__('Group members', 'cp-communitie').'</strong><br />';
		echo '<div id="selected_users" style="width:300px; height:300px; overflow:auto; background-color:#fff; padding:4px; border:1px solid #aaa;"></div>';
		echo '</div>';

		echo '<div style="clear:both; margin:10px;margin-left:330px">';
		echo '<input type="submit" id="users_add_button" name="Submit" class="button-primary" value="'.__('Update', 'cp-communitie').'" />';
		echo '</div>';

		?>
		<table style="margin-left:10px; margin-top:10px;">						
			<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
			<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-group]</td>
				<td><?php echo __('Used to display a group page, should not be included in user navigation or menu.', 'cp-communitie'); ?></td></tr>
			<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-groups]</td>
				<td><?php echo __('Display the groups on the site.', 'cp-communitie'); ?></td></tr>
		</table>
		<?php 
		
	} else {

		echo '<p style="margin-left:10px">';
		echo __('No groups created yet.', 'cp-communitie');
		echo '</p>';

	}	
					  
?>



<?php __cpc__show_tabs_header_end(); ?>

</div>
