<?php
/*
CP Community Forum
Description: Forum component for the Symposium suite of plug-ins. Put [cpcommunitie-forum] on any WordPress page to display forum.
*/
	


// Get constants
require_once(dirname(__FILE__).'/default-constants.php');


function __cpc__forum($atts) {	

	global $wpdb, $current_user;
	$level = __cpc__get_current_userlevel();
	
	$html = '';
	$topic_id = '';

	extract( shortcode_atts( array(
		'cat' => ''
	), $atts, CPC_SHORTCODE_PREFIX.'-forum' ) );
	$cat_id = $cat;

	// resolve stubs if using permalinks
	if ( get_option(CPC_OPTIONS_PREFIX.'_permalink_structure') && get_query_var('stub')) {
		$stubs = explode('/', get_query_var('stub'));
		$stub0 = $stubs[0];
		$stub1 = $stubs[1];
		if (CPC_DEBUG) echo $stub0.'/'.$stub1.'<br />';

		if ($stub0 && get_option(CPC_OPTIONS_PREFIX.'_permalinks_cats')) {
			// Two parameters, so go to topic
			$cat_id = __cpc__get_stub_id($stub0, 'forum-cat');
			$topic_id = __cpc__get_stub_id($stub1, 'forum-topic');
			if (!$cat_id) $cat_id = '';
			if (!$topic_id) $topic_id = '';
			if (CPC_DEBUG) echo '(1):'.$cat_id.'/'.$topic_id.' ('.$stub0.'/'.$stub1.')<br />';
		} else {
			// One parameter, so go to category
			if ($stub0) $stub1 = $stub0;
			$cat_id = __cpc__get_stub_id($stub1, 'forum-cat');
			if (CPC_DEBUG) echo '(2):'.$cat_id.' ('.$stub1.')<br />';
			if (!$cat_id) {
				// Couldn't find category, so look for topic instead
				$cat_id = '';
				$topic_id = __cpc__get_stub_id($stub1, 'forum-topic');
				if (CPC_DEBUG) echo '(3):'.$topic_id.' ('.$stub1.')<br />';
				if (!$topic_id) $topic_id = '';
			}
		}
		$html .= "<div id='cpcommunitie_perma_cat_id' style='display:none'>".$cat_id."</div>";
		$html .= "<div id='cpcommunitie_perma_topic_id' style='display:none'>".$topic_id."</div>";
	}

	
	// not using AJAX (or permalinks not found, for backward compatibility with old links)
	if ( ( $topic_id == '' && $cat_id == '') || ( !$cat_id != '' && get_option(CPC_OPTIONS_PREFIX.'_forum_ajax') && !get_option(CPC_OPTIONS_PREFIX.'_permalink_structure') ) ) {
		$cat_id = isset($_GET['cid']) ? $_GET['cid'] : 0;
		$topic_id = isset($_GET['show']) ? $_GET['show'] : 0;
	}
		
	// Wrapper
	$html .= "<div class='__cpc__wrapper'>";


	// Check to see if this member is in the included list of roles
	$user = get_userdata( $current_user->ID );
	$can_view = false;
	$viewer = str_replace('_', '', str_replace(' ', '', strtolower(get_option(CPC_OPTIONS_PREFIX.'_viewer'))));
	if (is_user_logged_in()) {
		$capabilities = $user->{$wpdb->base_prefix.'capabilities'};
	
		if ($capabilities) {
			foreach ( $capabilities as $role => $name ) {
				if ($role) {
					$role = strtolower($role);
					$role = str_replace(' ', '', $role);
					$role = str_replace('_', '', $role);
					if (CPC_DEBUG) $html .= 'Checking global forum (cpcommunitie_forum) role '.$role.' against '.$viewer.'<br />';
					if (strpos($viewer, $role) !== FALSE) $can_view = true;
				}
			}		 														
		} else {
			// No WordPress role stored
		}
	} 
		
	$everyone = str_replace(' ', '', strtolower(__('everyone', 'cp-communitie'))); // Deal with some foreign translations of 'everyone'
	if ( $can_view || strpos($viewer, $everyone) !== FALSE ) {

		$html .= "<div id='__cpc__forum_div'>";
		
		if ( get_option(CPC_OPTIONS_PREFIX.'_permalink_structure') || !get_option(CPC_OPTIONS_PREFIX.'_forum_ajax') ) {
			if ($topic_id == 0) {
				$forum = __cpc__getForum($cat_id);
				if (($x = strpos($forum, '[|]')) !== FALSE) $forum = substr($forum, $x+3);
				$html .= $forum;
			} else {
				$html .= __cpc__getTopic($topic_id);	
			}
		}
		
		$html .= "</div>";
		
		
	 } else {

		$html .= "<p>".__("Sorry, but you are not permitted to view the forum.", 'cp-communitie')."</p>";
		if (__cpc__get_current_userlevel() == 5) $html .= sprintf(__('Permissions are set via the WordPress admin dashboard->%s->Options->Forum.', 'cp-communitie'), CPC_WL_SHORT);

	 }

	$html .= "</div>";
	// End Wrapper
	
	
	$html .= "<div style='clear: both'></div>";
	
	// Send HTML
	return $html;

}

function cpcommunitie_forum_latestposts($attr) {
	
	global $wpdb;
	$use_answers = get_option(CPC_OPTIONS_PREFIX.'_use_answers');

	$count = isset($attr['count']) ? $attr['count'] : '';
	$cat_id = isset($attr['cat']) ? $attr['cat'] : 0;
	
	$html = '<div id="forum_activity_div">';
	$html .= cpcommunitie_forum_latestposts_showThreadChildren($count, $cat_id, 0, 0, $use_answers);	
	$html .= '</div>';

	return $html;

}

function cpcommunitie_forum_latestposts_showThreadChildren($count, $cat_id, $parent, $level, $use_answers) {
	
	global $wpdb, $current_user;

	$thispage = __cpc__get_url('forum');
	if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
	$q = __cpc__string_query($thispage);		

	$cpcommunitie_last_login = __cpc__get_meta($current_user->ID, 'cpcommunitie_last_login');
	
	$html = "";
	
	$preview = 30;	
	if ($count != '') { 
		$postcount = $count; 
	} else {
		$postcount = get_option(CPC_OPTIONS_PREFIX.'_cpcommunitie_forumlatestposts_count');
	}
	
	if ($level == 0) {
		$avatar_size = 30;
		$margin_top = 10;
		$desc = "DESC";
	} else {
		$avatar_size = 20;
		$margin_top = 6;
		$desc = "DESC";
	}

	// All topics started
	$cat_sql = ($cat_id) ? " AND t.topic_category = ".$cat_id : '';
	$posts = $wpdb->get_results("
		SELECT t.tid, t.topic_subject, t.stub, p.stub as parent_stub, t.topic_owner, t.topic_post, t.topic_category, t.topic_started, u.display_name, t.topic_parent, t.topic_answer, t.topic_date, t.topic_approved 
		FROM ".$wpdb->prefix.'cpcommunitie_topics'." t INNER JOIN ".$wpdb->base_prefix.'users'." u ON t.topic_owner = u.ID 
		LEFT JOIN ".$wpdb->prefix.'cpcommunitie_topics'." p ON t.topic_parent = p.tid 
		WHERE t.topic_parent = ".$parent." AND t.topic_group = 0".$cat_sql." ORDER BY t.tid ".$desc." LIMIT 0,".$postcount); 

	if ($posts) {

		foreach ($posts as $post)
		{
			if ( ($post->topic_approved == 'on') || ($post->topic_approved != 'on' && ($post->topic_owner == $current_user->ID || current_user_can('level_10'))) ) {

				$padding_left = ($level == 0) ? 40 : 30;
				$html .= "<div class='__cpc__latest_forum_row' style='padding-left: ".$padding_left."px; margin-left: ".($level*40)."px; margin-top:".$margin_top."px;'>";		
					$html .= "<div class='__cpc__latest_forum_row_avatar'>";
						$html .= get_avatar($post->topic_owner, $avatar_size);
					$html .= "</div>";
					$html .= "<div style='float:left'>";
						if ($post->topic_parent > 0) {
							$text = strip_tags(stripslashes($post->topic_post));
							if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
							$reply_text = $level == 1 ? 'replied' : 'commented';
							$html .= __cpc__profile_link($post->topic_owner)." ".__($reply_text, 'cp-communitie')." ";
							if (get_option(CPC_OPTIONS_PREFIX.'_permalink_structure')) {
								$perma_cat = __cpc__get_forum_category_part_url($post->topic_category);
								$html .= "<a title='".$text."' href='".$thispage.$perma_cat.$post->parent_stub."'>";
							} else {
								$html .= "<a title='".$text."' href='".$thispage.$q."cid=".$post->topic_category."&show=".$post->topic_parent."'>";
							}
							$html .= $text."</a> ".__cpc__time_ago($post->topic_started);
							if ($use_answers == 'on' && $post->topic_answer == 'on') {
								$html .= ' <img style="width:12px;height:12px" src="'.get_option(CPC_OPTIONS_PREFIX.'_images').'/tick.png" alt="'.__('Answer Accepted', 'cp-communitie').'" />';
							}
							$html .= "<br>";
						} else {
							$text = stripslashes($post->topic_subject);
							if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
							$html .= __cpc__profile_link($post->topic_owner)." ".__('started', 'cp-communitie');
							if (get_option(CPC_OPTIONS_PREFIX.'_permalink_structure')) {
								$perma_cat = __cpc__get_forum_category_part_url($post->topic_category);
								$html .= " <a title='".$text."'  href='".$thispage.$perma_cat.$post->stub."'>".$text."</a> ";
							} else {
								$html .= " <a title='".$text."'  href='".$thispage.$q."cid=".$post->topic_category."&show=".$post->tid."'>".$text."</a> ";
							}
							$html .= __cpc__time_ago($post->topic_started).".<br>";
						}
					$html .= "</div>";
					if ($post->topic_date > $cpcommunitie_last_login && $post->topic_owner != $current_user->ID) {
						$html .= "<div style='float:left;'>";
							$html .= "&nbsp;<img src='".get_option(CPC_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' />";
						$html .= "</div>";
					}		
					if ($post->topic_approved != 'on') {
						$html .= "&nbsp;<em>[".__("pending approval", 'cp-communitie')."]</em>";
					}
				$html .= "</div>";
				
			}
			
			$html .= cpcommunitie_forum_latestposts_showThreadChildren($count, $cat_id, $post->tid, $level+1, $use_answers);
			
		}
	}	
	
	return $html;
}


/* ====================================================== SET SHORTCODE ====================================================== */

if (!is_admin()) {
	add_shortcode(CPC_SHORTCODE_PREFIX.'-forum', '__cpc__forum');  
	add_shortcode(CPC_SHORTCODE_PREFIX.'-forumlatestposts', 'cpcommunitie_forum_latestposts');  
}



?>
