<?php
include_once('../../../../wp-config.php');

if (get_option(CPC_OPTIONS_PREFIX.'__cpc__gallery_activated') || get_option(CPC_OPTIONS_PREFIX.'__cpc__gallery_network_activated')) {

	// Member/Gallery search (autocomplete)
	if (isset($_GET['term'])) {
			
		global $wpdb, $current_user;	
		$return_arr = array();
		$term = $_GET['term'];
	
		$sql = "SELECT g.gid, g.owner, g.name, u.display_name, g.sharing FROM ".$wpdb->base_prefix."cpcommunitie_gallery g
		LEFT JOIN ".$wpdb->base_prefix."users u ON g.owner = u.ID
		WHERE ( ( name LIKE '%".$term."%') OR ( display_name LIKE '%".$term."%') ) AND u.display_name is not null
		ORDER BY name LIMIT 0,25";
		
		$list = $wpdb->get_results($sql);
		
		if ($list) {
			foreach ($list as $item) {
	
				// check for privacy
				if ( ($item->owner == $current_user->ID) || (strtolower($item->sharing) == 'public') || (is_user_logged_in() && strtolower($item->sharing) == 'everyone') || (strtolower($item->sharing) == 'public') || (strtolower($item->sharing) == 'friends only' && __cpc__friend_of($item->owner, $current_user->ID)) || __cpc__get_current_userlevel() == 5) {
					
					$row_array['id'] = $item->gid;	
					$row_array['owner'] = $item->owner;
					$row_array['display_name'] = $item->display_name;
					$row_array['name'] = $item->name;
					$row_array['avatar'] = get_avatar($item->owner, 40);
					
			        array_push($return_arr,$row_array);
			        
				}
			}
		}
	
		echo json_encode($return_arr);
		exit;
	
	}
	
	
	// Update to alerts and then redirect
	if (isset($_GET['href'])) {
		
		global $wpdb, $current_user;
		
		$num = isset($_GET['num']) ? $_GET['num'] : 0;
		$aid = $_GET['aid'];

		// Add to activity feed
		add_to_create_activity_feed($aid);
			
		// Then re-direct
		$href = __cpc__get_url('profile');
		$href .= __cpc__string_query($href);
		$href .= "uid=".$current_user->ID."&embed=on&album_id=".$aid;
		
		wp_redirect( $href ); 
		exit;	
		
	}

	
	// Re-order thumbnails
	if ($_POST['action'] == 'cpcommunitie_reorder_photos') {
		global $wpdb,$current_user;
		if (is_user_logged_in()) {
			$album_id = str_replace('cpcommunitie_gallery_photos_', '', $_POST['album_id']);
			$order = explode(",", $_POST['order']);		
			for($i=0;$i < sizeof($order);$i++){
				$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."cpcommunitie_gallery_items SET photo_order = %d WHERE iid = %d AND gid = %d AND owner = %d", ($i+1), $order[$i], $album_id, $current_user->ID  ) );  
			};
			echo __('Sortierung gespeichert, Seite neu laden, um neue Sortierung anzuzeigen.', 'cp-communitie');
			
		} else {
			echo __('NICHT EINGELOGGT', 'cp-communitie');
		}
	}
	
	// Comments for photo
	if ($_POST['action'] == 'cpcommunitie_get_photo_comments') {
	
		global $wpdb;	
		$photo_id = $_POST['photo_id'];
	
		$sql = "SELECT c.*, u.display_name FROM ".$wpdb->base_prefix."cpcommunitie_comments c 
			LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
			WHERE c.comment_parent = 0 AND c.type = 'photo' AND c.subject_uid = %d ORDER BY c.cid DESC";
	
		$comments = $wpdb->get_results($wpdb->prepare($sql, $photo_id));	
		
		$comments_array = array();
		foreach ($comments as $comment) {
			$add = array (
				'ID' => $comment->cid,
				'author_id' => $comment->author_uid,
				'avatar' => get_avatar($comment->author_uid, 32),
				'display_name' => $comment->display_name,
				'display_name_link' => __cpc__profile_link($comment->author_uid),
				'comment' => __cpc__buffer(__cpc__make_url(stripslashes($comment->comment))),
				'timestamp' => __cpc__time_ago($comment->comment_timestamp)
			);
			array_push($comments_array, $add);
		}
		
		echo json_encode($comments_array);
	
		exit;
		
	}	
	
	// Delete comment from photo
	if ($_POST['action'] == '__cpc__delete_gallery_comment') {
	
		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
				
			$cid = $_POST['cid'];
			$sql = "SELECT subject_uid, author_uid, comment FROM ".$wpdb->base_prefix."cpcommunitie_comments WHERE cid = %d";
			$c = $wpdb->get_row($wpdb->prepare($sql, $cid));
			
			$author_id = $c->author_uid;
			$photo_id = $c->subject_uid;
			$comment = $c->comment;
	
			$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_comments WHERE (cid = %d AND type='photo') OR (author_uid = %d AND comment LIKE '%%%s' AND type='gallery')";
			$wpdb->query($wpdb->prepare($sql, $cid, $author_id, $photo_id.'[]'.$comment));
	
		}
	
	}
	
	// Update comment on photo
	if ($_POST['action'] == 'cpcommunitie_update_photo_comment') {
	
		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
				
			$photo_id = $_POST['photo_id'];
			$comment = $_POST['comment'];
			$old_comment = $_POST['old_comment'];
	
			$sql = "UPDATE ".$wpdb->base_prefix."cpcommunitie_comments SET comment = %s WHERE subject_uid = %d AND comment = %s AND type = 'photo'";
			$wpdb->query($wpdb->prepare($sql, $comment, $photo_id, $old_comment));
	
			$sql = "SELECT cid,comment FROM ".$wpdb->base_prefix."cpcommunitie_comments WHERE comment LIKE '%%[]%d[]%s' AND type = 'gallery'";
			$o = str_replace("'", '_', $old_comment);
			$o = str_replace("\\", '_', $o);
			$sql = str_replace('%s', $o, $sql);
			$c = $wpdb->get_row($wpdb->prepare($sql, $photo_id));
	
			echo $wpdb->last_query;
	
			$new_c = str_replace('[]'.$photo_id.'[]'.$old_comment, '[]'.$photo_id.'[]'.$comment, $c->comment);
			$sql = "UPDATE ".$wpdb->base_prefix."cpcommunitie_comments SET comment = %s WHERE cid = %d";
			$wpdb->query($wpdb->prepare($sql, $new_c, $c->cid));
	
			echo $wpdb->last_query;
	
			exit;
		}
	
	}
	
	
	// Get photo gallery (for editing comment)
	
	if ($_POST['action'] == '__cpc__get_gallery_comment') {
	
		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
				
			$cid = $_POST['cid'];
			$sql = "SELECT comment FROM ".$wpdb->base_prefix."cpcommunitie_comments WHERE cid = %d LIMIT 0,1";
			$c = $wpdb->get_var($wpdb->prepare($sql, $cid));
			$c = stripslashes($c);
	
			echo $c;
			exit;
	
		}
	
	}	
		
	// Add comment to photo
	if ($_POST['action'] == 'cpcommunitie_add_photo_comment') {
	
		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
				
			$photo_id = $_POST['photo_id'];
			$comment = $_POST['comment'];
		
			// Insert comment
			$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."cpcommunitie_comments
			( 	subject_uid, 
				author_uid,
				comment_parent, 
				comment_timestamp, 
				comment, 
				is_group, 
				type
			)
			VALUES ( %d, %d, %d, %s, %s, %s, %s )", 
			array(
				$photo_id,
				$current_user->ID, 
				0,
				date("Y-m-d H:i:s"), 
				$comment, 
				'',
				'photo'
				) 
			) );
	
			// Get name of photo
			$sql = "SELECT gid,title FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE iid = %d";
			$photo = $wpdb->get_row($wpdb->prepare($sql, $photo_id));
		
			// Work out message
			$msg = __("Commented on", 'cp-communitie').' '.$photo->title.'[]'.$photo->gid.'[]comment[]'.$photo_id.'[]'.$comment;
		
			// Now add to activity feed
			__cpc__add_activity_comment($current_user->ID, $current_user->display_name, $current_user->ID, $msg, 'gallery');
			
		}
			
	}
	
	
	
	// Search gallery (shortcode)
	if ($_POST['action'] == 'getGallery') {
		
		global $wpdb, $current_user;
		
		$start = $_POST['start'];
		$term = $_POST['term'];
	
		$sql = "SELECT g.*, u.display_name FROM ".$wpdb->base_prefix."cpcommunitie_gallery g
				INNER JOIN ".$wpdb->base_prefix."users u ON g.owner = u.ID
				WHERE g.name LIKE '%".$term."%' 
				   OR u.display_name LIKE '%".$term."%' 
				ORDER BY gid DESC 
				LIMIT ".$start.",50";
		$albums = $wpdb->get_results($sql);
	
		$album_count = 0;	
		$total_count = 0;	
		$html = '';
	
		if ($albums) {
	
			$page_length = (get_option(CPC_OPTIONS_PREFIX."_gallery_page_length") != '') ? get_option(CPC_OPTIONS_PREFIX."_gallery_page_length") : 10;
	
			$html .= "<div id='cpcommunitie_gallery_albums'>";
			
			foreach ($albums AS $album) {
	
				$total_count++;	
				
				// check for privacy
				if ( ($album->owner == $current_user->ID) || (strtolower($album->sharing) == 'public') || (is_user_logged_in() && strtolower($album->sharing) == 'everyone') || (strtolower($album->sharing) == 'public') || (strtolower($album->sharing) == 'friends only' && __cpc__friend_of($album->owner, $current_user->ID)) || __cpc__get_current_userlevel() == 5) {
	
					$sql = "SELECT COUNT(iid) FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE gid = %d";
					$photo_count = $wpdb->get_var($wpdb->prepare($sql, $album->gid));				
		
					if ($photo_count > 0) {
						
						$html .= "<div id='__cpc__album_content' style='margin-bottom:30px'>";
					
						$html .= "<div id='cpc_gallery_album_name_".$album->gid."' class='topic-post-header'>".stripslashes($album->name)."</div>";
						$html .= "<p>".__cpc__profile_link($album->owner)."</p>";
			
						$sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE gid = %d ORDER BY photo_order";
						$photos = $wpdb->get_results($wpdb->prepare($sql, $album->gid));	
					
						if ($photos) {
		
							global $blog_id;
							$blog_path = ($blog_id > 1) ? '/'.$blog_id : '';
	
							$album_count++;
							
							$cnt = 0;
							$thumbnail_size = (get_option(CPC_OPTIONS_PREFIX."_gallery_thumbnail_size") != '') ? get_option(CPC_OPTIONS_PREFIX."_gallery_thumbnail_size") : 75;
							$html .= '<div id="cpc_comment_plus" style="width:98%;height:'.($thumbnail_size+10).'px;overflow:hidden; ">';
				
							$preview_count = (get_option(CPC_OPTIONS_PREFIX."_gallery_preview") != '') ? get_option(CPC_OPTIONS_PREFIX."_gallery_preview") : 5;
				       		foreach ($photos as $photo) {
				       		    
				       		    $cnt++;
				              					
								// Filesystem
								if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == "on") {
									$img_src = WP_CONTENT_URL."/plugins/cp-communitie/get_album_item.php?iid=".$photo->iid."&size=photo";
									$thumb_src = WP_CONTENT_URL."/plugins/cp-communitie/get_album_item.php?iid=".$photo->iid."&size=thumbnail";
								} else {
	
									if (get_option(CPC_OPTIONS_PREFIX."_gallery_show_resized") == 'on') {
					                	$img_src = get_option(CPC_OPTIONS_PREFIX.'_img_url').$blog_path.'/members/'.$album->owner.'/media/'.$album->gid.'/show_'.$photo->name;
									} else {
					                	$img_src = get_option(CPC_OPTIONS_PREFIX.'_img_url').$blog_path.'/members/'.$album->owner.'/media/'.$album->gid.'/'.$photo->name;
									}
				        	        $thumb_src = get_option(CPC_OPTIONS_PREFIX.'_img_url').$blog_path.'/members/'.$album->owner.'/media/'.$album->gid.'/thumb_'.$photo->name;
								}
				
				               	$html .= '<div class="__cpc__photo_outer">';
				           			$html .= '<div class="__cpc__photo_inner">';
				      					$html .= '<div class="__cpc__photo_cover">';
											$html .= '<a class="__cpc__photo_cover_action cpc_gallery_album" data-owner="'.$album->owner.'" data-iid="'.$photo->iid.'" data-name="'.stripslashes($photo->title).'" href="'.$img_src.'" rev="'.$cnt.'" rel="cpcommunitie_gallery_photos_'.$album->gid.'" title="'.stripslashes($album->name).'">';
					        					$html .= '<img class="__cpc__photo_image" style="width:'.$thumbnail_size.'px; height:'.$thumbnail_size.'px;" src="'.$thumb_src.'" />';
					        				$html .= '</a>';
				     					$html .= '</div>';
				       				$html .= '</div>';
				     			$html .= '</div>';
		
					       		if ($cnt == $preview_count) {
					       		    $html .= '<div id="cpc_gallery_comment_more" style="cursor:pointer">'.__('mehr...', 'cp-communitie').'<div style="clear:both"></div></div>';
					       		}   		
				      				
				       		}
				       		
				       		$html .= '</div>';
						
						} else {
						
					      	 $html .= __("Noch keine Fotos.", 'cp-communitie');
					     
						}
		
						$html .= '</div>';
					
						if ($album_count == $page_length) { break; }
						
					}
				}
	
			}
			$html .= "<div style='clear:both;text-align:center; margin-top:20px; width:100%'><a href='javascript:void(0)' id='showmore_gallery'>".__("mehr...", 'cp-communitie')."</a></div>";
			
			$html .= '</div>';
				
		} else {
			$html .= '<div style="clear:both;text-align:center; width:100%;">'.__('Keine Alben zum Anzeigen', 'cp-communitie').".</div>";
		}
		
		$html = $total_count."[split]".$html;
		echo $html;	
		exit;
	}
	
	// Select cover photo for album
	if ($_POST['action'] == 'menu_gallery_select_cover') {
		
		global $wpdb;
		if (isset($_POST['item_id'])) { $item_id = $_POST['item_id']; } else { $item_id = 0; }
		$sql = "SELECT gid FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE iid = %d";
		$gid = $wpdb->get_var($wpdb->prepare($sql, $item_id));
	
		if ($item_id > 0 && $gid > 0) {
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."cpcommunitie_gallery_items SET cover = 'on' WHERE gid = %d AND iid = %d", $gid, $item_id  ) );  
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."cpcommunitie_gallery_items SET cover = '' WHERE gid = %d AND iid != %d", $gid, $item_id  ) );  
			echo 'OK';
		} else {
			echo 'No item ID passed';
		}
	
		exit;
	}
	
	// Change sharing status
	if ($_POST['action'] == 'menu_gallery_change_share') {
	
		global $wpdb;
	
		if (isset($_POST['album_id'])) { $album_id = $_POST['album_id']; } else { $album_id = 0; }
		if (isset($_POST['new_share'])) { $new_share = $_POST['new_share']; } else { $new_share = ''; }
	
		if ($album_id > 0 && $new_share != '') {
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."cpcommunitie_gallery SET sharing = %s WHERE gid = %d", $new_share, $album_id  ) );  
			echo 'OK';
		} else {
			echo 'Wrong parameters';
		}
	
		exit;
	}
	
	
	// Delete photo
	if ($_POST['action'] == 'menu_gallery_manage_delete') {
	
	    global $wpdb, $current_user;
		
	    $item_id = 0;
	    if (isset($_POST['item_id'])) { $item_id = $_POST['item_id']; }
	 
	    if ($item_id != 0) {
	
			// Get owner
			$this_owner = stripslashes($wpdb->get_var($wpdb->prepare("SELECT owner FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE iid = %d", $item_id)));
		
			// check to see if storing in filesystem or database
			if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == "on") {
	
				// when deleting item, the fields in the record are deleted too, nothing to do here
	
			} else {
		
				// delete files (and from filesystem)
		
				// get album ID
			    $sql = "SELECT gid, name FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE iid = %d";
		    	$photo = $wpdb->get_row($wpdb->prepare($sql, $item_id));	
		
				// remove from album table
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE iid = %d", $item_id  ) );  
				// remove comments
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_comments WHERE subject_uid = %d AND type = 'photo'", $item_id  ) );  
		
				// delete files...
				$thumb_src = WP_CONTENT_DIR.'/cpc-content/members/'.$this_owner.'/media/'.$photo->gid.'/thumb_'.$photo->name;
				$show_src = WP_CONTENT_DIR.'/cpc-content/members/'.$this_owner.'/media/'.$photo->gid.'/show_'.$photo->name;
				$original_src = WP_CONTENT_DIR.'/cpc-content/members/'.$this_owner.'/media/'.$photo->gid.'/'.$photo->name;
				if (file_exists($thumb_src))
					unlink($thumb_src);	
				if (file_exists($show_src))
					unlink($show_src);	
				if (file_exists($original_src))
					unlink($original_src);	
		
			}
		
			// Rebuild activity entry
			add_to_create_activity_feed($photo->gid);
			
			echo __('Foto gelöscht.', 'cp-communitie');
	
	    } else {
	      echo __('Keine Element-ID übergeben', 'cp-communitie');
	    }
	
	    exit;   
	    
	}
	
	// Delete all photos in an album
	if ($_POST['action'] == 'menu_gallery_manage_delete_all') {
	
	    global $wpdb, $current_user;
		
	    $album_id = 0;
	    if (isset($_POST['album_id'])) { $album_id = $_POST['album_id']; }
	 
	    if ($album_id != 0) {
	
			// First delete the album...
	   		// Check for children albums first
			if (__cpc__get_current_userlevel() == 5) {
				$sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE parent_gid = %d ORDER BY updated DESC";
				$albums = $wpdb->get_results($wpdb->prepare($sql, $album_id));	
			} else {
				$sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE owner = %d AND parent_gid = %d ORDER BY updated DESC";
				$albums = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $album_id));	
			}
		
			if ($albums) {
		      	echo __('Bitte lösche zuerst Unteralben.', 'cp-communitie');
			} else {
		
		  		$sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE gid = %d";
		  		$photos = $wpdb->get_results($wpdb->prepare($sql, $album_id));	
		  		if ($photos) {
					
					// Delete photos in this album
					// Get owner
					$this_owner = stripslashes($wpdb->get_var($wpdb->prepare("SELECT owner FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE gid = %d", $album_id)));
				
					// check to see if storing in filesystem or database
					if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == "on") {
				
						$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE gid = %d AND groupid=0", $album_id  ) );  
				
					} else {
				
				
						// physically delete files from filesystem within album folder
						$dir = WP_CONTENT_DIR.'/cpc-content/members/'.$this_owner.'/media/'.$album_id;
						if (file_exists($dir)) {					
							$handle = opendir($dir);
							while (($file = readdir($handle)) !== false) {
								if (!is_dir($file)) {
									//unlink($dir.'/'.$file);	
								}
							}
							closedir($handle);
						}
				
						$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE gid = %d AND groupid=0", $album_id  ) );  
						
					}
					
					// Delete entire entry from activity
					// First get name of album
					$sql = "SELECT name FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE gid = %d";
					$name = $wpdb->get_var($wpdb->prepare($sql, $album_id));
					// Then delete
					$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_comments WHERE subject_uid = ".$current_user->ID." AND author_uid = %d AND comment LIKE '%".$name."%' AND (type = 'gallery' OR type = 'photo')";
					$wpdb->query($wpdb->prepare($sql, $current_user->ID));
					
		  		}
		
				// Now delete the album
				// Get owner
				$this_owner = stripslashes($wpdb->get_var($wpdb->prepare("SELECT owner FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE gid = %d", $album_id)));
	
	  			$wpdb->query("DELETE FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE gid = ".$album_id." AND owner = ".$this_owner);    
	   			$wpdb->query("DELETE FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE gid = ".$album_id." AND owner = ".$this_owner);
	
				// if using filesystem, remove the folder
				$dir = WP_CONTENT_DIR.'/cpc-content/members/'.$this_owner.'/media/'.$album_id;
				if (file_exists($dir)) {
					__cpc__rrmdir_tmp($dir);
				}
	
	       		echo 'OK';
		
			}
			
	    } else {
	      echo __('Keine Element-ID übergeben', 'cp-communitie');
	    }
	
	    exit;   
	    
	}
	
	// Rename photo title
	if ($_POST['action'] == 'menu_gallery_manage_rename') {
	
	    global $wpdb, $current_user;
		
	    $item_id = 0;
	    if (isset($_POST['item_id'])) { $item_id = $_POST['item_id']; }
	 
	    $new_name = '';
	    if (isset($_POST['new_name'])) { $new_name = $_POST['new_name']; }
	
	    if ($item_id != 0 && $new_name != '') {
	      $wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."cpcommunitie_gallery_items SET title = %s WHERE iid = %d", $new_name, $item_id  ) );  
	      echo 'OK';
	    } else {
	      echo __('Bitte gib einen Titel ein', 'cp-communitie');
	    }
	
	    exit;   
	    
	}
	
	// List albums / Create album form
	if ($_POST['action'] == 'menu_gallery') {
	
		global $wpdb, $current_user;
		
		$album_id = 0;
		if (isset($_POST['album_id'])) { $album_id = $_POST['album_id']; }
		$user_page = $_POST['uid1'];
		$user_id = $current_user->ID;
		
		$html = "<p class='__cpc__profile_heading'>".__('Galerie', 'cp-communitie')."</p>";
		
	    if ($album_id == 0 && $user_page == $user_id) {
			$html .= '<input type="submit" class="cpcommunitie_new_album_button __cpc__button" value="'.__("Erstellen", 'cp-communitie').'" />';	
		}
	
		// Get current album
		$owner = 0;
		if ($album_id > 0) {
			
			// Breadcrumb
		    $sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE gid = %d";
		    $this_album = $wpdb->get_row($wpdb->prepare($sql, $album_id));	   
		    $owner = $this_album->owner;   	
		    
			$html .= '<div id="__cpc__gallery_breadcrumb">';
		
				$html .= '<a href="javascript:void(0);" id="__cpc__gallery_top">'.__('Alle Alben', 'cp-communitie').'</a>';
		
			   	if ($this_album->parent_gid != 0) {
					$sql = "SELECT gid, name FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE gid = %d";
					$parent_album = $wpdb->get_row($wpdb->prepare($sql, $this_album->parent_gid));	      	
					$html .= '&nbsp;&rarr;&nbsp;<a href="javascript:void(0);" title="'.$parent_album->gid.'" id="cpcommunitie_gallery_up">'.stripslashes($parent_album->name).'</a>';
			    }           	
	
				$html .= '&nbsp;&rarr;&nbsp;<strong>'.stripslashes($this_album->name).'</strong>';
				if ($album_id != 0 && ($user_page == $user_id || __cpc__get_current_userlevel($current_user->ID) == 5)) {
					$html .= '<div style="float:right"><a href="javascript:void(0);" rel="'.$album_id.'" type="submit" class="__cpc__photo_delete_all">'.__('Dieses Album löschen', 'cp-communitie').'</a>';
					$html .= '<br /><a href="javascript:void(0);" class="cpcommunitie_new_album_button">'.__("Unteralbum erstellen", 'cp-communitie').'</a></div>';	
		   	  	}
	
			$html .= '</div>';
		
		}
	
	   	$html .= "<div id='__cpc__album_covers'>";
	
	   	$sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE owner = %d AND (parent_gid = %d OR parent_gid = 0) ORDER BY updated DESC";
	    $albums = $wpdb->get_results($wpdb->prepare($sql, $user_page, $album_id));	
	       
		// Show album covers
	   	if ($albums) {
	
			$html = apply_filters('__cpc__gallery_header', $html);
	 
	       	foreach ($albums as $album) {
	
				// check for privacy
				if ( ($album->owner == $current_user->ID) || (strtolower($album->sharing) == 'public') || (is_user_logged_in() && strtolower($album->sharing) == 'everyone') || (strtolower($album->sharing) == 'friends only' && __cpc__friend_of($album->owner, $current_user->ID)) || __cpc__get_current_userlevel() == 5) {
	
					// Get cover image
			     	$sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE gid = %d AND cover = 'on'";
					$cover = $wpdb->get_row($wpdb->prepare($sql, $album->gid));
					
					if ($cover) {
		
						if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == "on") {
							// Database
							$thumb_src = WP_CONTENT_URL."/plugins/cp-communitie/get_album_item.php?iid=".$cover->iid."&size=thumbnail";
						} else {
							// Filesystem
							if (file_exists(get_option(CPC_OPTIONS_PREFIX.'_img_path').'/members/'.$cover->owner.'/media/'.$album->gid.'/thumb_'.$cover->name)) {
				        	    $thumb_src = get_option(CPC_OPTIONS_PREFIX.'_img_url').'/members/'.$cover->owner.'/media/'.$album->gid.'/thumb_'.$cover->name;
							} else {
								$thumb_src = get_option(CPC_OPTIONS_PREFIX.'_images').'/broken_file_link.png';
							}
						}
						
					} else {
						$thumb_src = get_option(CPC_OPTIONS_PREFIX.'_images')."/unknown.jpg";
					}
		
		       		// Show cover
		        	if ($album->parent_gid == $album_id) {
						$html .= '<div class="__cpc__album_outer">';
		   				$html .= '<div class="__cpc__album_inner">';
								$html .= '<div class="__cpc__album_cover">';
		 							$html .= '<a class="__cpc__album_cover_action" href="javascript:void(0);" title="'.$album->gid.'">';
		 								$html .= '<img class="__cpc__album_image" src="'.$thumb_src.'" />';
		 								$html .= '</a>';
									$html .= '</div>';
		 						$html .= '</div>';
		 					$html .= '<div class="__cpc__album_title">'.stripslashes($album->name).'</div>';
		  				$html .= '</div>';
					}
				}
	       
			}
	       		
	    } else {
	
	    	if ($user_page == $user_id) {
	        	$html .= "<div class='cpcommunitie_new_album_button __cpc__menu_gallery_alert'>".__("Beginne mit der Erstellung eines Albums", 'cp-communitie')."</div>";
	        } else {
	        	$html .= __("Noch keine Alben.", 'cp-communitie');
	       	}
	       		
	    }
	   	
	 	$html .= "</div>";
	
		// Show contents of album (so long as in an album)
		if ($album_id > 0) {
			$html .= "<div id='__cpc__album_content'>";
	   
	  		$sql = "SELECT * FROM ".$wpdb->base_prefix."cpcommunitie_gallery_items WHERE gid = %d ORDER BY photo_order";
	  		$photos = $wpdb->get_results($wpdb->prepare($sql, $album_id));	
	
	    	if ($user_page == $user_id) {
	
				// Sharing for this album
				$share = $this_album->sharing;
				$album_owner = $this_album->owner;
		
				$html .= __('Teilen mit:', 'cp-communitie').' ';
				$html .= '<select title = '.$album_id.' id="gallery_share">';
					$html .= "<option value='nobody'";
						if ($share == 'nobody') { $html .= ' SELECTED'; }
						$html .= '>'.__('Niemanden', 'cp-communitie').'</option>';
					$html .= "<option value='friends only'";
						if ($share == 'friends only') { $html .= ' SELECTED'; }
						$html .= '>'.sprintf(__('Nur %s', 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friends')).'</option>';
					$html .= "<option value='everyone'";
						if ($share == 'everyone') { $html .= ' SELECTED'; }
						$html .= '>'.stripslashes(get_option(CPC_OPTIONS_PREFIX.'_alt_everyone')).'</option>';
					$html .= "<option value='public'";
						if ($share == 'public') { $html .= ' SELECTED'; }
						$html .= '>'.__('Öffentlich', 'cp-communitie').'</option>';
				$html .= '</select>';
				$html .= " <img id='__cpc__album_sharing_save' style='display:none' src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/busy.gif' /><br />";
				
				// Show maximum file upload size as set in PHP.INI to admin's
				if (__cpc__get_current_userlevel($current_user->ID) == 5) {
					$html .= '<p>Wie in PHP.INI festgelegt, ist die upload_max_filesize: '.ini_get('upload_max_filesize').'<br />(Diese Nachricht wird nur Webseiten-Administratoren angezeigt)</p>';
				} else {
					$html .= '<p>'.__('Die maximale Größe der hochgeladenen Dateien beträgt', 'cp-communitie').' '.ini_get('upload_max_filesize').'.</p>';
				}

				include_once('../server/file_upload_include.php');
				$html .= show_upload_form(
					WP_CONTENT_DIR.'/cpc-content/members/'.$current_user->ID.'/gallery_upload/', 
					WP_CONTENT_URL.'/cpc-content/members/'.$current_user->ID.'/gallery_upload/',
					'gallery',
					__('Fotos hochladen)', 'cp-communitie'),
					0,
					0,
					$album_id
				);
				$html .= "<div id='__cpc__gallery_flag' style='display:none'></div>"; // So that __cpc__init_file_upload() knows it's the gallery
	
			}
	  	
	    	if ($photos) {
	
				$cnt=0;
		
		       	foreach ($photos as $photo) {
	
					$cnt++;
					
		            // Add photo
					$thumbnail_size = ($value = get_option(CPC_OPTIONS_PREFIX."_gallery_thumbnail_size")) ? $value : '75';
		
					// DB or Filesystem
					if (get_option(CPC_OPTIONS_PREFIX.'_img_db') == "on") {
						$img_src = WP_CONTENT_URL."/plugins/cp-communitie/get_album_item.php?iid=".$photo->iid."&size=photo";
						$thumb_src = WP_CONTENT_URL."/plugins/cp-communitie/get_album_item.php?iid=".$photo->iid."&size=thumbnail";
					} else {
	
						$file_check = get_option(CPC_OPTIONS_PREFIX.'_img_path').'/members/'.$user_page.'/media/'.$album_id.'/thumb_'.$photo->name;
						if (file_exists($file_check)) {
	
							if (get_option(CPC_OPTIONS_PREFIX."_gallery_show_resized") == 'on') {
				               	$img_src = get_option(CPC_OPTIONS_PREFIX.'_img_url').'/members/'.$user_page.'/media/'.$album_id.'/show_'.$photo->name;
							} else {
				               	$img_src = get_option(CPC_OPTIONS_PREFIX.'_img_url').'/members/'.$user_page.'/media/'.$album_id.'/'.$photo->name;
							}
			        	    $thumb_src = get_option(CPC_OPTIONS_PREFIX.'_img_url').'/members/'.$user_page.'/media/'.$album_id.'/thumb_'.$photo->name;
	
						} else {
							$img_src = get_option(CPC_OPTIONS_PREFIX.'_images').'/broken_file_link.png';						
							$thumb_src = get_option(CPC_OPTIONS_PREFIX.'_images').'/broken_file_link.png';						
						}
					}
	
		            $html .= '<div class="__cpc__photo_outer">';
	           			$html .= '<div class="__cpc__photo_inner">';
							$html .= '<div class="__cpc__photo_cover">';
							$html .= '<a class="__cpc__photo_cover_action cpc_gallery_album" data-owner="'.$owner.'" data-iid="'.$photo->iid.'" data-name="'.stripslashes($photo->title).'" href="'.$img_src.'" rev="'.$cnt.'" rel="cpcommunitie_gallery_photos_'.$album_id.'" title="'.stripslashes($this_album->name).'">';
								$html .= '<img class="__cpc__photo_image" style="width:'.$thumbnail_size.'px; height:'.$thumbnail_size.'px;" src="'.$thumb_src.'" />';
							$html .= '</a>';
							$html .= '</div>';
						$html .= '</div>';
					$html .= '</div>';
		      				
		       	}
	  	
	    	} else {
	  	
	          	 	$html .= __("Noch keine Fotos.", 'cp-communitie');
	         
	    	}
	   
			$html .= "</div>";
		}	
	
		// Create new album form
		if ($album_id != '') {
			$this_album = stripslashes($wpdb->get_var($wpdb->prepare("SELECT name FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE gid = %d", $album_id)));
			$this_id = $album_id; 
		} else {
			$this_album = 'None';
			$this_id = 0;
		}
	
		$html .= "<div id='__cpc__create_gallery'>";
	
			$html .= '<div class="new-topic-subject label">'.__("Name des neuen Albums", 'cp-communitie').'</div>';
			$html .= "<input id='cpcommunitie_new_album_title' class='new-topic-subject-input' type='text'>";
	
			if ($this_id > 0) {
				$html .= "<div class='__cpc__create_sub_gallery label'>";
				$html .= "<input type='checkbox' title='".$this_id."' id='__cpc__create_sub_gallery_select' CHECKED> ".__("Als Unteralbum erstellen von ".$this_album, 'cp-communitie');
				$html .= "</div>";
			}
			
			$html .= "<div style='margin-top:10px'>";
			$html .= '<input id="cpcommunitie_new_album" type="submit" class="__cpc__button" style="float: left" value="'.__("Erstellen", 'cp-communitie').'" />';
			$html .= '<input id="cpcommunitie_cancel_album" type="submit" class="__cpc__button clear" onClick="javascript:void(0)" value="'.__("Abbrechen", 'cp-communitie').'" />';
			$html .= "</div>";
	
		$html .= "</div>";
	
		echo $html;
		exit;
		
	}
	
	// Create album
	if ($_POST['action'] == 'create_album') {
	
		global $wpdb, $current_user;
        
        if (is_user_logged_in()) {
		
            $name = sanitize_text_field($_POST['name']);
            $sub_album = $_POST['sub_album'];
            if ($sub_album == 'true') {
                $parent = $_POST['parent'];
            } else {
                $parent = 0;
            }

            // Create new album
            $wpdb->query( $wpdb->prepare( "
            INSERT INTO ".$wpdb->base_prefix."cpcommunitie_gallery
            ( 	parent_gid, 
                name,
                description, 
                owner, 
                sharing, 
                editing, 
                created, 
                updated, 
                is_group
            )
            VALUES ( %d, %s, %s, %d, %s, %s, %s, %s, %s )", 
            array(
                $parent, 
                $name,
                '', 
                $current_user->ID, 
                'everyone', 
                'nobody', 
                date("Y-m-d H:i:s"),
                date("Y-m-d H:i:s"),
                ''
                ) 
            ) );

            echo $wpdb->insert_id;
            
        }
		exit;
	
	}		
	
	// Widget
	if ($_POST['action'] == 'Gallery_Widget') {
	
		$albumcount = $_POST['albumcount'];
		__cpc__do_Gallery_Widget($albumcount);
	}
	
}

function add_to_create_activity_feed($aid) {
	
	global $wpdb, $current_user;
	
	// Get name of album
	$sql = "SELECT name FROM ".$wpdb->base_prefix."cpcommunitie_gallery WHERE gid = %d";
	$name = $wpdb->get_var($wpdb->prepare($sql, $aid));
	
	// Work out message
	$msg = __("Hinzugefügt zu", 'cp-communitie').' '.$name.'[]'.$aid.'[]added';
	
	// First remove any older messages to avoid duplication that mention this album
	$sql = "DELETE FROM ".$wpdb->base_prefix."cpcommunitie_comments WHERE subject_uid = ".$current_user->ID." AND author_uid = ".$current_user->ID." AND comment LIKE '%".$name."%' AND type = 'gallery'";
	$wpdb->query($sql);
	
	// Now add to activity feed
	__cpc__add_activity_comment($current_user->ID, $current_user->display_name, $current_user->ID, $msg, 'gallery');
	
}


function __cpc__rrmdir_tmp($dir) {
   if (is_dir($dir)) {
	 $objects = scandir($dir);
	 foreach ($objects as $object) {
	   if ($object != "." && $object != "..") {
		 if (filetype($dir."/".$object) == "dir") __cpc__rrmdir_tmp($dir."/".$object); else unlink($dir."/".$object);
	   }
	 }
	 reset($objects);
	 rmdir($dir);
   }
}  

	
?>

	
