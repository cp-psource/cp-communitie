<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>
<?php



echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';


__cpc__show_tabs_header('plus');


	global $wpdb;
	// See if the user has posted profile settings
	
	if( isset($_POST[ '__cpc__profile_plus_updated' ]) ) {



	 	$lat_long = (isset($_POST[ 'lat_long' ])) ? $_POST[ 'lat_long' ] : '';
	 	$show_alt = (isset($_POST[ 'show_alt' ])) ? $_POST[ 'show_alt' ] : '';
		$cpc_show_hoverbox = (isset($_POST['cpc_show_hoverbox']) ? $_POST['cpc_show_hoverbox'] : '');
		$use_distance = (isset($_POST['use_distance']) ? $_POST['use_distance'] : '');
		$unique_display_name = (isset($_POST['unique_display_name']) ? $_POST['unique_display_name'] : '');
		$all_friends = (isset($_POST['all_friends']) ? $_POST['all_friends'] : '');
		$activity_images = (isset($_POST['activity_images']) ? $_POST['activity_images'] : '');
		$activity_youtube = (isset($_POST['activity_youtube']) ? $_POST['activity_youtube'] : '');

		$profile_menu_scrolls = (isset($_POST['profile_menu_scrolls']) ? $_POST['profile_menu_scrolls'] : '');
	 	$profile_menu_delta = ($_POST[ 'profile_menu_delta' ] != '') ? $_POST[ 'profile_menu_delta' ] : '40';

		update_option(CPC_OPTIONS_PREFIX."_plus_lat_long", $lat_long);
		update_option(CPC_OPTIONS_PREFIX."_plus_show_alt", $show_alt);
		update_option(CPC_OPTIONS_PREFIX.'_cpc_show_hoverbox', $cpc_show_hoverbox);
		update_option(CPC_OPTIONS_PREFIX.'_use_distance', $use_distance);
		update_option(CPC_OPTIONS_PREFIX.'_unique_display_name', $unique_display_name);
		update_option(CPC_OPTIONS_PREFIX.'_all_friends', $all_friends);
		update_option(CPC_OPTIONS_PREFIX.'_activity_images', $activity_images);
		update_option(CPC_OPTIONS_PREFIX.'_activity_youtube', $activity_youtube);
		update_option(CPC_OPTIONS_PREFIX."_profile_menu_delta", $profile_menu_delta);
		update_option(CPC_OPTIONS_PREFIX."_profile_menu_scrolls", $profile_menu_scrolls);
	
		update_option(CPC_OPTIONS_PREFIX.'_show_forum_replies_on_activity', isset($_POST[ 'show_forum_replies_on_activity' ]) ? $_POST[ 'show_forum_replies_on_activity' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_show_group_replies_on_activity', isset($_POST[ 'show_group_replies_on_activity' ]) ? $_POST[ 'show_group_replies_on_activity' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_activity_likes', isset($_POST[ 'activity_likes' ]) ? $_POST[ 'activity_likes' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_site_search_prompt', isset($_POST[ 'cpc_site_search_prompt' ]) ? $_POST[ 'cpc_site_search_prompt' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_site_search_groups', isset($_POST[ 'cpc_site_search_groups' ]) ? $_POST[ 'cpc_site_search_groups' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_site_search_gallery', isset($_POST[ 'cpc_site_search_gallery' ]) ? $_POST[ 'cpc_site_search_gallery' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_site_search_topics', isset($_POST[ 'cpc_site_search_topics' ]) ? $_POST[ 'cpc_site_search_topics' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_site_search_posts', isset($_POST[ 'cpc_site_search_posts' ]) ? $_POST[ 'cpc_site_search_posts' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_site_search_pages', isset($_POST[ 'cpc_site_search_pages' ]) ? $_POST[ 'cpc_site_search_pages' ] : '');
		update_option(CPC_OPTIONS_PREFIX.'_tags', isset($_POST[ 'cpcommunitie_tags' ]) ? $_POST[ 'cpcommunitie_tags' ] : '');

		
		// Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Gespeichert', 'cp-communitie').".</p></div>";
		
	}

	// Get options
	$lat_long = ($value = get_option(CPC_OPTIONS_PREFIX."_plus_lat_long")) ? $value : '';
	$show_alt = ($value = get_option(CPC_OPTIONS_PREFIX."_plus_show_alt")) ? $value : '';
	$__cpc__tags = ($value = get_option(CPC_OPTIONS_PREFIX."_tags")) ? $value : '';
	$use_distance = ($value = get_option(CPC_OPTIONS_PREFIX."_use_distance")) ? $value : '';
	$unique_display_name = ($value = get_option(CPC_OPTIONS_PREFIX."_unique_display_name")) ? $value : '';
	$all_friends = ($value = get_option(CPC_OPTIONS_PREFIX."_all_friends")) ? $value : '';
	$activity_images = ($value = get_option(CPC_OPTIONS_PREFIX."_activity_images")) ? $value : '';
	$activity_youtube = ($value = get_option(CPC_OPTIONS_PREFIX."_activity_youtube")) ? $value : '';
	$activity_likes = ($value = get_option(CPC_OPTIONS_PREFIX."_activity_likes")) ? $value : '';
	$show_forum_replies_on_activity = ($value = get_option(CPC_OPTIONS_PREFIX."_show_forum_replies_on_activity")) ? $value : '';
	$show_group_replies_on_activity = ($value = get_option(CPC_OPTIONS_PREFIX."_show_group_replies_on_activity")) ? $value : '';

	// Set defaults
	if (get_option(CPC_OPTIONS_PREFIX."_profile_menu_delta") == '') update_option(CPC_OPTIONS_PREFIX."_profile_menu_delta", '40');
	
	
	// Force friends retrospectively?
	if (isset($_POST['force_all_friends']) && $_POST['force_all_friends']) {
		echo "<div class='updated' style='padding-bottom:10px'><p style='font-weight:bold'>".__('Alle Benutzer zu Freunden machen', 'cp-communitie')."</p>";
		echo "<p>".__("Bist Du sicher, dass Du ALLE Benutzer miteinander befreunden möchtes? <strong>Dies kann nicht rückgängig gemacht werden!</strong> Bitte erstelle zuerst eine Sicherungskopie Deiner Datenbank!", 'cp-communitie')."</p>";
		echo "<p>".__("Je nachdem, wie viele Benutzer Du hast, kann dies einige Minuten dauern.", 'cp-communitie')."</p>";
		echo "<table border=0><tr><td>";
		echo "<form method='post' action=''><input type='hidden' name='force_all_friends_confirm' value='Y' /><input type='submit' class='button-primary' value='".__("Ja", 'cp-communitie')."' /></form>";
		echo "</td><td>";
		echo "<form method='post' action=''><input type='hidden' name='force_all_friends_confirm' value='N' /><input type='submit' class='button-primary' value='".__("Nein", 'cp-communitie')."' /></form>";
		echo "</td><tr></table>";
		echo "</div>";
	}
	if (isset($_POST['force_all_friends_confirm']) && $_POST['force_all_friends_confirm'] == 'Y') {
		echo "<div class='updated slideaway'><p style='font-weight:bold'>".__('Alle Benutzer zu Freunden machen', 'cp-communitie')."</p>";
		// Delete existing friendships
		$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_friends";
		$wpdb->query($sql);
		// Loop through each user, adding them as a friend to all other users
		$sql = "SELECT ID FROM ".$wpdb->base_prefix."users";
		$users = $wpdb->get_results($sql);
		$users2 = $wpdb->get_results($sql);
		foreach ($users as $user) {
			foreach ($users2 as $user2) {
				if ($user->ID != $user2->ID) {
					$wpdb->query( $wpdb->prepare( "
						INSERT INTO ".$wpdb->base_prefix."cpcommunitie_friends
						( 	friend_from, 
							friend_to,
							friend_accepted,
							friend_message,
							friend_timestamp
						)
						VALUES ( %d, %d, %s, %s, %s )", 
					    array(
					    	$user->ID,
					    	$user2->ID,
					    	'on', 
					    	'',
					    	date("Y-m-d H:i:s")
					    	) 
					    ) );
				}
			}
			
		}
		echo "<p>".__("Alle Benutzer sind jetzt miteinander befreundet.", 'cp-communitie')."</p>";
		echo "</div>";
	}
	
?>
	
	<form method="post" action=""> 
	<input type='hidden' name='__cpc__profile_plus_updated' value='Y'>
	<table class="form-table __cpc__admin_table"> 

	<tr><td colspan="2"><h2><?php _e('Options', 'cp-communitie') ?></h2></td></tr>
		
<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') == "on") { ?>
	<tr valign="top"> 
	<td scope="row"><label for="profile_menu_scrolls"><?php echo __('Scrollendes Profilmenü', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="profile_menu_scrolls" id="profile_menu_scrolls" <?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_scrolls') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Das Profilmenü (nur vertikale Version) scrollt mit der Seite nach unten und bleibt sichtbar', 'cp-communitie'); ?></span></td> 
	</tr> 
<?php } else { ?>
	<input type="hidden" name="profile_menu_scrolls" id="profile_menu_scrolls" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_scrolls') == "on") { echo "on"; } ?>" />
<?php } ?>

<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') == "on") { ?>
	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="profile_menu_delta"><?php echo __('Platz über dem Menü', 'cp-communitie'); ?></label></td> 
	<td><input name="profile_menu_delta" type="text" id="profile_menu_delta" style="width:50px" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_profile_menu_delta'); ?>" /> 
	<span class="description"><?php echo __('Abstand über dem Menü, wenn sie sich mit der Seite nach unten bewegt (Pixel)', 'cp-communitie'); ?></td> 
	</tr> 
<?php } else { ?>
	<input type="hidden" name="profile_menu_delta" id="profile_menu_delta" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_delta') == "on") { echo "on"; } ?>" />
<?php } ?>


	<tr valign="top"> 
	<td scope="row"><label for="show_forum_replies_on_activity"><?php _e('Forum Antworten in Aktivität', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="show_forum_replies_on_activity" id="show_forum_replies_on_activity" <?php if ($show_forum_replies_on_activity == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Forenantworten in Aktivitäts-Stream aufnehmen', 'cp-communitie'); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="show_group_replies_on_activity"><?php _e('Gruppenforum-Antworten auf Aktivität', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="show_group_replies_on_activity" id="show_group_replies_on_activity" <?php if ($show_group_replies_on_activity == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Füge Antworten aus Gruppenforen in den Aktivitäts-Stream ein', 'cp-communitie'); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="activity_likes"><?php _e('Aktivität Gefällt mir/Gefällt mir nicht', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="activity_likes" id="activity_likes" <?php if ($activity_likes == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Fügt allen Aktivitätsbeiträgen ein „Gefällt mir“- und „Gefällt mir nicht“-Symbol hinzu', 'cp-communitie'); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="activity_images"><?php _e('Aktivitäts-/Statusbilder zulassen', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="activity_images" id="activity_images" <?php if ($activity_images == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Benutzern erlauben, Bilder in den Aktivitätsfeed hochzuladen', 'cp-communitie'); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="activity_youtube"><?php _e('Aktivität/Status YouTube zulassen', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="activity_youtube" id="activity_youtube" <?php if ($activity_youtube == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Nutzern erlauben, YouTube in den Aktivitätsfeed einzubetten', 'cp-communitie'); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="cpcommunitie_tags"><?php _e('Aktiviere @user-Tags', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="cpcommunitie_tags" id="cpcommunitie_tags" <?php if ($__cpc__tags == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Ersetze @user durch einen Link zur Profilseite. Versteht Benutzernamen und Anzeigenamen (ohne Leerzeichen)', 'cp-communitie'); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="unique_display_name"><?php _e('Eindeutige Anzeigenamen', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="unique_display_name" id="unique_display_name" <?php if ($unique_display_name == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('Prüfung auf eindeutige Anzeigenamen in %s-Profil-Community-Einstellungen einbeziehen', 'cp-communitie'), CPC_WL_SHORT); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="use_distance"><?php _e('Distanz aktivieren', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="use_distance" id="use_distance" <?php if ($use_distance == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Distanz im Mitgliederverzeichnis aktivieren', 'cp-communitie'); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="lat_long"><?php _e('Verwende Meilen für die Geokodierung der Entfernung', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="lat_long" id="lat_long" <?php if ($lat_long == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Stelle die Entfernung auf Meilen ein, sonst auf Kilometer', 'cp-communitie'); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="show_alt"><?php _e('Alternative anzeigen', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="show_alt" id="show_alt" <?php if ($show_alt == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('z.B. Wenn oben Meilen eingestellt, werden auch Kilometer angezeigt', 'cp-communitie'); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="all_friends"><?php _e('Alle sind Freunde?', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="all_friends" id="all_friends" <?php if ($all_friends == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Neue Benutzer automatisch als Freunde zu allen hinzufügen', 'cp-communitie'); ?>
	<br /><input type="checkbox" name="force_all_friends" /> <?php echo __('Lege alle Benutzer als Freunde für alle fest', 'cp-communitie'); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="cpc_show_hoverbox"><?php echo __('Hoverbox aktivieren', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_show_hoverbox" id="cpc_show_hoverbox" <?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_show_hoverbox') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('Aktiviert das Hover-Feld, wenn der Cursor über den Profil-Avatar bewegt wird. Erfordert die Aktivierung von <a href="%s">Profilfotos</a>.', 'cp-communitie'), 'admin.php?page=cpcommunitie_profile'); ?></span></td> 
	</tr> 

	<tr><td colspan="2"><h2><?php _e('Autocomplete search box', 'cp-communitie') ?></h2></td></tr>

	<tr valign="top"> 
	<td colspan="2">
	<span class="description">
		<?php echo sprintf(__('Um eine Mitgliedersuche hinzuzufügen, verwende den Shortcode [%s-search] oder verwende &quot;echo %ssearch(150)&quot; in PHP, wobei 150 die Breite in Pixel ist.', 'cp-communitie'), CPC_SHORTCODE_PREFIX, '__cpc__'); ?><br />
		<?php echo __('Je mehr Ergebnisse enthalten sind, desto langsamer kann die Suche sein und desto größer sind die Auswirkungen auf Deinn Server/Deine Datenbank.', 'cp-communitie'); ?>
	</span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_prompt"><?php echo __('Textaufforderung', 'cp-communitie'); ?></label></td> 
	<td><input name="cpc_site_search_prompt" type="text" id="cpc_site_search_prompt"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_site_search_prompt'); ?>" /> 
	<span class="description"><?php echo __('Eingabeaufforderung für Suchfeldtext', 'cp-communitie'); ?></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_gallery"><?php echo __('Galerie', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_gallery" id="cpc_site_search_gallery" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_gallery') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('%s Fotoalben in Suchergebnisse einbeziehen', 'cp-communitie'), CPC_WL); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_groups"><?php echo __('Gruppen', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_groups" id="cpc_site_search_groups" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_groups') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('%s Gruppen in Suchergebnisse einbeziehen', 'cp-communitie'), CPC_WL); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_pages"><?php echo __('Seiten', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_pages" id="cpc_site_search_pages" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_pages') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Füge ClassicPress-Seiten in die Suchergebnisse ein', 'cp-communitie'); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_posts"><?php echo __('Blogbeiträge', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_posts" id="cpc_site_search_posts" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_posts') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Füge ClassicPress-Blogbeiträge in die Suchergebnisse ein', 'cp-communitie'); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_topics"><?php echo __('Forumsthemen', 'cp-communitie'); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_topics" id="cpc_site_search_topics" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_topics') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('%s Forumsthemen in Suchergebnisse einbeziehen', 'cp-communitie'), CPC_WL); ?></span></td> 
	</tr> 

	</table>
		
	<table style="margin-left:10px; margin-top:10px;">						
		<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
		<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-search]</td>
			<td><?php echo __('Zeige das Suchformular für die automatische Vervollständigung an.', 'cp-communitie'); ?></td></tr>
	</table>
	<?php 	
				
	echo '<p class="submit" style="margin-left:12px">';
	echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Änderungen speichern', 'cp-communitie').'" />';
	echo '</p>';
	
	echo '</form>';
  
?>

</div>
