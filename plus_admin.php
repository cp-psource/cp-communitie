<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>
<?php



echo '<h2>'.sprintf(__('%s Einstellungen', CPC_TEXT_DOMAIN), CPC_WL).'</h2><br />';


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
		echo "<div class='updated slideaway'><p>".__('Gespeichert', CPC_TEXT_DOMAIN).".</p></div>";
		
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
		echo "<div class='updated' style='padding-bottom:10px'><p style='font-weight:bold'>".__('Force friends to all', CPC_TEXT_DOMAIN)."</p>";
		echo "<p>".__("Are you sure you want to make ALL users friends with each other? <strong>This cannot be reversed!</strong> Please take a backup of your database first!", CPC_TEXT_DOMAIN)."</p>";
		echo "<p>".__("Depending on how many users you have, this may take a few minutes.", CPC_TEXT_DOMAIN)."</p>";
		echo "<table border=0><tr><td>";
		echo "<form method='post' action=''><input type='hidden' name='force_all_friends_confirm' value='Y' /><input type='submit' class='button-primary' value='".__("Yes", CPC_TEXT_DOMAIN)."' /></form>";
		echo "</td><td>";
		echo "<form method='post' action=''><input type='hidden' name='force_all_friends_confirm' value='N' /><input type='submit' class='button-primary' value='".__("No", CPC_TEXT_DOMAIN)."' /></form>";
		echo "</td><tr></table>";
		echo "</div>";
	}
	if (isset($_POST['force_all_friends_confirm']) && $_POST['force_all_friends_confirm'] == 'Y') {
		echo "<div class='updated slideaway'><p style='font-weight:bold'>".__('Force friends to all', CPC_TEXT_DOMAIN)."</p>";
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
		echo "<p>".__("All users are now friends with each other.", CPC_TEXT_DOMAIN)."</p>";
		echo "</div>";
	}
	
?>
	
	<form method="post" action=""> 
	<input type='hidden' name='__cpc__profile_plus_updated' value='Y'>
	<table class="form-table __cpc__admin_table"> 

	<tr><td colspan="2"><h2><?php _e('Options', CPC_TEXT_DOMAIN) ?></h2></td></tr>
		
<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') == "on") { ?>
	<tr valign="top"> 
	<td scope="row"><label for="profile_menu_scrolls"><?php echo __('Scrolling profile menu', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="profile_menu_scrolls" id="profile_menu_scrolls" <?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_scrolls') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Profile menu (vertical version only) scrolls down with page, remaining visible', CPC_TEXT_DOMAIN); ?></span></td> 
	</tr> 
<?php } else { ?>
	<input type="hidden" name="profile_menu_scrolls" id="profile_menu_scrolls" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_scrolls') == "on") { echo "on"; } ?>" />
<?php } ?>

<?php if (get_option(CPC_OPTIONS_PREFIX.'_use_templates') == "on") { ?>
	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="profile_menu_delta"><?php echo __('Space above menu', CPC_TEXT_DOMAIN); ?></label></td> 
	<td><input name="profile_menu_delta" type="text" id="profile_menu_delta" style="width:50px" value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_profile_menu_delta'); ?>" /> 
	<span class="description"><?php echo __('Space above the menu when moving down with the page (pixels)', CPC_TEXT_DOMAIN); ?></td> 
	</tr> 
<?php } else { ?>
	<input type="hidden" name="profile_menu_delta" id="profile_menu_delta" value="<?php if (get_option(CPC_OPTIONS_PREFIX.'_profile_menu_delta') == "on") { echo "on"; } ?>" />
<?php } ?>


	<tr valign="top"> 
	<td scope="row"><label for="show_forum_replies_on_activity"><?php _e('Forum Replies on Activity', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="show_forum_replies_on_activity" id="show_forum_replies_on_activity" <?php if ($show_forum_replies_on_activity == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Include forum replies in activity stream', CPC_TEXT_DOMAIN); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="show_group_replies_on_activity"><?php _e('Group Forum Replies on Activity', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="show_group_replies_on_activity" id="show_group_replies_on_activity" <?php if ($show_group_replies_on_activity == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Include group forum replies in activity stream', CPC_TEXT_DOMAIN); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="activity_likes"><?php _e('Activity Like/Dislike', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="activity_likes" id="activity_likes" <?php if ($activity_likes == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Adds a like and dislike icon to all activity posts', CPC_TEXT_DOMAIN); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="activity_images"><?php _e('Allow activity/status images', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="activity_images" id="activity_images" <?php if ($activity_images == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Allow users to upload images to the activity feed', CPC_TEXT_DOMAIN); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="activity_youtube"><?php _e('Allow activity/status YouTube', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="activity_youtube" id="activity_youtube" <?php if ($activity_youtube == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Allow users to embed YouTube to the activity feed', CPC_TEXT_DOMAIN); ?></span>
	</td> 
	</tr> 
		
	<tr valign="top"> 
	<td scope="row"><label for="cpcommunitie_tags"><?php _e('Enable @user tags', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="cpcommunitie_tags" id="cpcommunitie_tags" <?php if ($__cpc__tags == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Replace @user with a link to profile page. Understands usernames and display names (with spaces removed)', CPC_TEXT_DOMAIN); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="unique_display_name"><?php _e('Unique display names', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="unique_display_name" id="unique_display_name" <?php if ($unique_display_name == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('Include check for unique display names on %s profile community settings', CPC_TEXT_DOMAIN), CPC_WL_SHORT); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="use_distance"><?php _e('Enable distance', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="use_distance" id="use_distance" <?php if ($use_distance == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Enable distance in the member directory', CPC_TEXT_DOMAIN); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="lat_long"><?php _e('Use miles for geocoding distance', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="lat_long" id="lat_long" <?php if ($lat_long == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Set distance to miles, otherwise kilometers', CPC_TEXT_DOMAIN); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="show_alt"><?php _e('Show alternative', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="show_alt" id="show_alt" <?php if ($show_alt == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('eg. If above set to miles, also show kilometers', CPC_TEXT_DOMAIN); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="all_friends"><?php _e('Friends to all?', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="all_friends" id="all_friends" <?php if ($all_friends == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Automatically add new users as friends to all', CPC_TEXT_DOMAIN); ?>
	<br /><input type="checkbox" name="force_all_friends" /> <?php echo __('Set all users as friends to all', CPC_TEXT_DOMAIN); ?></span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="cpc_show_hoverbox"><?php echo __('Enable hover box', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_show_hoverbox" id="cpc_show_hoverbox" <?php if (get_option(CPC_OPTIONS_PREFIX.'_cpc_show_hoverbox') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('Enables the hover box when cursor moved over profile avatar. Requires <a href="%s">Profile Photos</a> to be activated.', CPC_TEXT_DOMAIN), 'admin.php?page=cpcommunitie_profile'); ?></span></td> 
	</tr> 

	<tr><td colspan="2"><h2><?php _e('Autocomplete search box', CPC_TEXT_DOMAIN) ?></h2></td></tr>

	<tr valign="top"> 
	<td colspan="2">
	<span class="description">
		<?php echo sprintf(__('To add a member search, use [%s-search] shortcode, or put &quot;echo %ssearch(150)&quot; in PHP, where 150 is the width in pixels.', CPC_TEXT_DOMAIN), CPC_SHORTCODE_PREFIX, '__cpc__'); ?><br />
		<?php echo __('The more results that are included, the slower the search may be and greater the impact on your server/database.', CPC_TEXT_DOMAIN); ?>
	</span></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_prompt"><?php echo __('Text prompt', CPC_TEXT_DOMAIN); ?></label></td> 
	<td><input name="cpc_site_search_prompt" type="text" id="cpc_site_search_prompt"  value="<?php echo get_option(CPC_OPTIONS_PREFIX.'_site_search_prompt'); ?>" /> 
	<span class="description"><?php echo __('Search box text prompt', CPC_TEXT_DOMAIN); ?></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_gallery"><?php echo __('Gallery', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_gallery" id="cpc_site_search_gallery" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_gallery') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('Include %s Photo albums in search results', CPC_TEXT_DOMAIN), CPC_WL); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_groups"><?php echo __('Groups', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_groups" id="cpc_site_search_groups" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_groups') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('Include %s Groups in search results', CPC_TEXT_DOMAIN), CPC_WL); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_pages"><?php echo __('Pages', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_pages" id="cpc_site_search_pages" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_pages') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Include WordPress pages in search results', CPC_TEXT_DOMAIN); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_posts"><?php echo __('Blog Posts', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_posts" id="cpc_site_search_posts" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_posts') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Include WordPress blog posts in search results', CPC_TEXT_DOMAIN); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row" style="text-align:right"><label for="cpc_site_search_topics"><?php echo __('Forum Topics', CPC_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="cpc_site_search_topics" id="cpc_site_search_topics" <?php if (get_option(CPC_OPTIONS_PREFIX.'_site_search_topics') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo sprintf(__('Include %s Forum topics in search results', CPC_TEXT_DOMAIN), CPC_WL); ?></span></td> 
	</tr> 

	</table>
		
	<table style="margin-left:10px; margin-top:10px;">						
		<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
		<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-search]</td>
			<td><?php echo __('Display the autocomplete search form.', CPC_TEXT_DOMAIN); ?></td></tr>
	</table>
	<?php 	
				
	echo '<p class="submit" style="margin-left:12px">';
	echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Save Changes', CPC_TEXT_DOMAIN).'" />';
	echo '</p>';
	
	echo '</form>';
  
?>

</div>
