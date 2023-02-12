<?php
// If uninstall not called from ClassicPress exit
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
exit ();

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

global $wpdb, $wp_rewrite;
if (is_multisite()) {
    $blogs = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
    if ($blogs) {
        foreach($blogs as $blog) {
            switch_to_blog($blog->blog_id);
			__cpc__uninstall_delete();
			__cpc__uninstall_rrmdir(WP_CONTENT_DIR.'/cpc-content');
			$wp_rewrite->flush_rules();

        }
        restore_current_blog();
    }   
} else {
	__cpc__uninstall_delete();
	__cpc__uninstall_rrmdir(WP_CONTENT_DIR.'/cpc-content');			
	$wp_rewrite->flush_rules();
}


function __cpc__uninstall_rrmdir($dir) {
   if (is_dir($dir)) {
	 $objects = scandir($dir);
	 foreach ($objects as $object) {
	   if ($object != "." && $object != "..") {
		 if (filetype($dir."/".$object) == "dir") __cpc__uninstall_rrmdir($dir."/".$object); else unlink($dir."/".$object);
	   }
	 }
	 reset($objects);
	 rmdir($dir);
   }
} 

function __cpc__uninstall_delete() {
	
	global $wpdb;
	
	// Clear up Reply by Email
	$_SESSION['__cpc__mailinglist_lock'] = 'locked';
	wp_clear_scheduled_hook('__cpc__mailinglist_hook');
	$_SESSION['__cpc__mailinglist_lock'] = '';
	
	// delete options		
   	$wpdb->query("DELETE FROM ".$wpdb->prefix."options WHERE option_name LIKE '".CPC_OPTIONS_PREFIX."%'");
   	
   	// delete user meta data
	$wpdb->query("DELETE FROM ".$wpdb->prefix."usermeta WHERE meta_key like '".CPC_OPTIONS_PREFIX."%'");
	
	// delete tables
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_cats");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_chat");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_chat2");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_chat2_users");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_comments");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_events");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_events_bookings");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_extended");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_following");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_friends");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_gallery");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_gallery_comments");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_gallery_items");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_groups");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_group_members");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_likes");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_lounge");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_mail");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_news");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_styles");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_subs");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_topics");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_topics_images");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_topics_scores");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."cpcommunitie_usermeta");
   	
	// clear schedules
	wp_clear_scheduled_hook('cpcommunitie_notification_hook');	
	
}	

?>
