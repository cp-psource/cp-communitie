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
[Mitglieder]
Directory=members';

			update_option(CPC_OPTIONS_PREFIX.'_group_menu_structure', (isset($_POST['group_menu_structure']) && $_POST['group_menu_structure']) ? $_POST['group_menu_structure'] : $default_menu_structure);
		
		}		

        // Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Gespeichert', 'cp-communitie').".</p></div>";
		
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

		<tr><td colspan="2"><h2><?php _e('Einstellungen', 'cp-communitie') ?></h2></td></tr>

		<tr valign="top"> 
		<td scope="row"><label for="cpc_use_group_templates"><?php echo __('Benutzerdefinierte Vorlagen für Gruppenseiten', 'cp-communitie'); ?></label></td>
		<td>
		<input type="checkbox" name="cpc_use_group_templates" id="cpc_use_group_templates" <?php if (get_option(CPC_OPTIONS_PREFIX.'_use_group_templates') == "on") { echo "CHECKED"; } ?>/>
		<span class="description"><?php echo sprintf(__('Aktiviere <a href="%s">Vorlagen</a> für die Gruppenseite (wenn nicht, wird das Standardlayout verwendet)', 'cp-communitie'), 'admin.php?page=cpcommunitie_templates#group_options'); ?></span></td> 
		</tr> 

		<tr valign="top"> 
		<td scope="row"><label for="group_all_create"><?php _e('Alle Benutzer können erstellen', 'cp-communitie'); ?></label></td>
		<td>
		<input type="checkbox" name="group_all_create" id="group_all_create" <?php if ($group_all_create == "on") { echo "CHECKED"; } ?>/>
		<span class="description"><?php echo __('Alle Benutzer oder nur auf Administratoren beschränkt', 'cp-communitie'); ?></span></td> 
		</tr> 

		<tr valign="top"> 
		<td scope="row"><label for="initial_groups"><?php _e('Standardgruppen', 'cp-communitie'); ?></label></td> 
		<td><input name="initial_groups" type="text" id="initial_groups"  value="<?php echo $initial_groups; ?>" /> 
		<span class="description"><?php echo __('Kommagetrennte Liste der Gruppen-IDs, denen neue Mitglieder zugewiesen werden (leer lassen für keine)', 'cp-communitie'); ?></td> 
		</tr> 
		
		<tr valign="top"> 
		<td scope="row"><label for="group_invites"><?php _e('Gruppeneinladungen zulassen', 'cp-communitie'); ?></label></td>
		<td>
		<input type="checkbox" name="group_invites" id="group_invites" <?php if ($group_invites == "on") { echo "CHECKED"; } ?>/>
		<span class="description"><?php echo __("Erlaube Gruppenadministratoren, Personen per E-Mail zum Beitritt einzuladen.", 'cp-communitie'); ?></span></td> 
		</tr> 

		<tr valign="top"> 
		<td scope="row"><label for="group_max_members"><?php _e('Standardmaximalmitglieder', 'cp-communitie'); ?></label></td>
		<td><input name="group_max_members" style="width: 50px" type="text" id="group_max_members" value="<?php echo $group_max_members; ?>" class="regular-text" /> 
		<span class="description">
			<?php echo __('Maximale Anzahl an Mitgliedern, die eine neue Gruppe zulässt (kann in den Gruppeneinstellungen geändert werden), 0=unbegrenzt.', 'cp-communitie'); ?>
		</span></td> 
		</tr> 

		<tr valign="top"> 
		<td scope="row"><label for="group_invites_max"><?php _e('Maximale Einladungen', 'cp-communitie'); ?></label></td>
		<td><input name="group_invites_max" style="width: 50px" type="text" id="group_invites_max" value="<?php echo $group_invites_max; ?>" class="regular-text" /> 
		<span class="description">
			<?php echo __('Wie viele Einladungen zum Beitritt zur Gruppe können gleichzeitig versendet werden (um Spam von Deinem Server zu vermeiden).', 'cp-communitie'); 
			__('Hinweis: Wenn Personen, die per E-Mail zum Beitritt eingeladen werden, keine Mitglieder sind, können sie sich zuerst registrieren (wenn die Option in ClassicPress eingestellt ist).', 'cp-communitie'); ?>
		</span></td> 
		</tr> 

	<?php

	if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_type')) { ?>

	<tr><td colspan="2"><h2><?php _e('Menüelemente gruppieren', 'cp-communitie') ?></h2></td></tr>

	<tr valign="top"> 
	<td scope="row"><label for="group_invites_max"><?php _e('Menüstruktur', 'cp-communitie'); ?></label></td>
	<td>
	<textarea rows="12" cols="40" name="group_menu_structure" id="group_menu_structure"><?php echo get_option(CPC_OPTIONS_PREFIX.'_group_menu_structure') ?></textarea><br />
	<span class="description"><?php echo sprintf(__('Gilt nur für die horizontale Version des Gruppenseitenmenüs, das auf der Registerkarte "Plus" festgelegt wird.', 'cp-communitie'), CPC_WL); ?></span><br />
	<a id="__cpc__reset_group_menu" href="javascript:void(0)"><?php echo __('Auf Standard zurücksetzen...', 'cp-communitie'); ?></a>
	</td> 
	</tr> 

	<?php } 

	echo '</table>';
	
	echo '<p class="submit" style="margin-left:6px;">';
	echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Änderungen speichern', 'cp-communitie').'" />';
	echo '</p>';
	echo '</form>';
	
	echo '<h2>'.__('Gruppe löschen / Gruppenmitglieder verwalten', 'cp-communitie').'</h2>';

	echo '<p style="margin-left:10px">';	
	echo __("Wähle eine Gruppe aus, um aktuelle Mitglieder anzuzeigen. Gib dann einen Teil des Anzeigenamens oder Benutzernamens eines Mitglieds ein, um zu suchen. Für alle Benutzer leer lassen.", 'cp-communitie').'<br />';
	echo __("Du kannst den Gruppenadministrator nicht hinzufügen oder entfernen. Gruppenadministratoren werden nicht angezeigt.", 'cp-communitie').'<br />';
	echo '</p>';


	$sql = "SELECT * FROM ".$wpdb->prefix."cpcommunitie_groups ORDER BY group_order, name";
	$groups = $wpdb->get_results($sql);
	
	if ($groups) {
	
		echo '<div style="margin-left:10px">';
		echo '<select id="group_list" style="margin-bottom:10px">';
		echo '<option value=0>'.__('-- Wähle eine Gruppe aus --', 'cp-communitie').'</option>';
		foreach ($groups as $group) {
			echo '<option value='.$group->gid.'>'.$group->gid.': '.stripslashes($group->name).' (order = '.$group->group_order.')</option>';
		}
		echo '</select> ';
		echo '<input type="text" style="margin-left:180px" id="user_list_search" /> '; 
		echo '<input type="submit" id="user_list_search_button" name="Submit" class="button-primary" value="'.__('Suche', 'cp-communitie').'" />';
		echo '</div>';
		
		echo '<div id="group_meta" style="display:none; margin-left:10px;">';
		echo '<form action="#" method="POST">';
		echo '<input type="hidden" name="action" value="update_group_order">';
		echo '<strong>Gruppenreihenfolge (untere zuerst angezeigt)</strong><br />';
		echo '<input type="group_meta_order" style="width:50px" value="'.$group->group_order.'" />';
		echo '<input type="submit" class="button-secondary" value="Update" />';
		echo '</form>';
		echo '</div>';

		echo '<div id="group_list_delete" style="margin-left:10px; display:none;">';
		echo '<a href="javascript:void(0)" id="group_list_delete_link">'.__('Lösche diese Gruppe', 'cp-communitie').'</a>';
		echo '</div>';
		echo '<div id="group_order_update" style="margin-left:10px; display:none;">';
		echo '<a href="javascript:void(0)" id="group_order_update_link">'.__('Ändere die Reihenfolge dieser Gruppe', 'cp-communitie').'</a>';
		echo '</div>';
		
		echo '<div style="clear:both; margin:10px; float:left;">';
		echo '<strong>'.__('Verfügbare Benutzer', 'cp-communitie').'</strong><br />';
		echo '<div id="user_list" style="width:300px; height:300px; overflow:auto; background-color:#fff; padding:4px; border:1px solid #aaa;"></div>';
		echo '</div>';
	
		echo '<div style="margin-top:10px; margin-bottom:10px;float:left;">';
		echo '<strong>'.__('Gruppenmitglieder', 'cp-communitie').'</strong><br />';
		echo '<div id="selected_users" style="width:300px; height:300px; overflow:auto; background-color:#fff; padding:4px; border:1px solid #aaa;"></div>';
		echo '</div>';

		echo '<div style="clear:both; margin:10px;margin-left:330px">';
		echo '<input type="submit" id="users_add_button" name="Submit" class="button-primary" value="'.__('Aktualisieren', 'cp-communitie').'" />';
		echo '</div>';

		?>
		<table style="margin-left:10px; margin-top:10px;">						
			<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
			<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-group]</td>
				<td><?php echo __('Wird verwendet, um eine Gruppenseite anzuzeigen, sollte nicht in der Benutzernavigation oder im Menü enthalten sein.', 'cp-communitie'); ?></td></tr>
			<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-groups]</td>
				<td><?php echo __('Zeige die Gruppen auf der Seite an.', 'cp-communitie'); ?></td></tr>
		</table>
		<?php 
		
	} else {

		echo '<p style="margin-left:10px">';
		echo __('Noch keine Gruppen erstellt.', 'cp-communitie');
		echo '</p>';

	}	
					  
?>



<?php __cpc__show_tabs_header_end(); ?>

</div>
