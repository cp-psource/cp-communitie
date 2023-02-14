<?php
include_once('../wp-config.php');
include_once(dirname(__FILE__).'/mobile_check.php');

global $wpdb, $current_user;

require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc.php');
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_ui.php');
require_once(ABSPATH.'wp-content/plugins/cp-communitie/class.cpc_user.php');

$cpc = new cpc();
$cpc_ui = new cpc_ui();
if ( is_user_logged_in() )
	$cpc_user = new cpc_user($cpc->get_current_user_page()); // default to current user, or pass a user ID

// Redirect if not on a mobile
if (!$mobile) {
	header('Location: ./..');
}

// Re-act to POSTed information *******************************************************************
if (isset($_POST['post_comment']) && $_POST['post_comment'] != '' && $current_user->ID > 0) {
	$new_status = $_POST['post_comment'] != 'What`s up?' ? $_POST['post_comment'] : '';


	// Don't allow HTML
	$new_status = str_replace("<", "&lt;", $new_status);
	$new_status = str_replace(">", "&gt;", $new_status);

	$wpdb->query( $wpdb->prepare( "
		INSERT INTO ".$wpdb->base_prefix."symposium_comments
		( 	subject_uid, 
			author_uid,
			comment_parent,
			comment_timestamp,
			comment,
			is_group
		)
		VALUES ( %d, %d, %d, %s, %s, %s )", 
			array(
				$cpc_user->id, 
		       	$current_user->ID, 
		       	0,
		       	date("Y-m-d H:i:s"),
		       	$new_status,
		       	''
		       	) 
		 ) );

	// New Post ID
	$new_id = $wpdb->insert_id;	
		 
	// Check for any pending uploads and copy to this post
	$directory = get_option(CPC_OPTIONS_PREFIX.'_img_path')."/members/".$current_user->ID."/m_upload/";
	if (file_exists($directory)) {
		$handler = opendir($directory);
		while ($image = readdir($handler)) {
			if ($image != "." && $image != ".." && $image != ".DS_Store" && $image != "thumbnail" ) {
				$targetDir = get_option(CPC_OPTIONS_PREFIX.'_img_path')."/members/".$current_user->ID;
				$targetActivityDir = get_option(CPC_OPTIONS_PREFIX.'_img_path')."/members/".$current_user->ID."/activity";
				$filename = $new_id.'.'.substr(strrchr($image,'.'),1);
				$targetActivityFile = $targetActivityDir.'/'.$filename;
				if (!file_exists($targetDir))
					@mkdir($targetDir);
				if (!file_exists($targetActivityDir))
					@mkdir($targetActivityDir);
	
				@copy($directory.'/'.$image, $targetActivityFile);
				@unlink($directory.'/'.$image);
				@unlink($directory.'/thumbnail/'.$image);
				$image_filename = $image;
			}
		}
	}	
	
	// Email the subject (if they want to know about it and not self-posting)		        
	if ($current_user->ID != $cpc_user->id) {

		if (__cpc__get_meta($cpc_user->id, 'notify_new_wall') == 'on') {
			// Filter to allow further actions to take place
			apply_filters ('__cpc__wall_newpost_filter', $cpc_user->id, $current_user->ID, $current_user->display_name );

			$sql = "SELECT u.user_email FROM ".$wpdb->base_prefix."users u WHERE u.ID = %d";
			$recipient = $wpdb->get_row($wpdb->prepare($sql, $cpc_user->id));	
	
			if ($recipient) {
				$body = "<p>".$current_user->display_name." ".__('has added a new post on your profile', 'cp-communitie').":</p>";
				$body .= "<p>".stripslashes($new_status)."</p>";
				$body .= "<p><a href='".__cpc__get_url('profile')."?uid=".$cpc_user->id."&post=".$new_id."'>".__('Go to the post', 'cp-communitie')."...</a></p>";
				__cpc__sendmail($recipient->user_email, __('New Profile Post', 'cp-communitie'), $body);
			}
		}
	}	

	// redirect to avoid multiple form posts
	?>
	<script>
	window.location.href = "<?php echo __cpc__pageURL(); ?>";
	</script>
	<?php
	exit;
	

} else {
	
	// Clean out any old uploaded activity images
	$directory = get_option(CPC_OPTIONS_PREFIX.'_img_path')."/members/".$current_user->ID."/m_upload/";
	recursive_remove_directory($directory, true);

}	

?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo get_bloginfo('name');?></title>
<meta charset="UTF-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
<?php if ($big_display) { ?>
	<link rel="stylesheet" type="text/css" href="bigdisplay.css" />
<?php } ?>

<link rel="stylesheet" href="http://blueimp.github.com/cdn/css/bootstrap.min.css">
<link rel="stylesheet" href="http://blueimp.github.com/cdn/css/bootstrap-responsive.min.css">
<!--[if lt IE 7]><link rel="stylesheet" href="http://blueimp.github.com/cdn/css/bootstrap-ie6.min.css"><![endif]-->
<link rel="stylesheet" href="http://blueimp.github.com/Bootstrap-Image-Gallery/css/bootstrap-image-gallery.min.css">
<link rel="stylesheet" href="css/jquery.fileupload-ui.css">
<noscript><link rel="stylesheet" href="upload/css/jquery.fileupload-ui-noscript.css"></noscript>
<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->

</head>
<body>


<?php

if ( !is_user_logged_in() ) {

	include_once('./header_loggedout.php');

	echo '<br /><br />';
	echo '<input type="submit" onclick="location.href=\'login.php?'.$a.'\'" class="submit small blue fullwidth" value="'.__('Login', 'cp-communitie').'" />';
	echo '<br /><br />';
	
} else {

	include_once('./header_loggedin.php');
	if ($cpc_user->id == $current_user->ID) {
		show_header('[reload,forum,topics,replies,friends,profile]');
	} else {
		show_header('[home,forum,topics,replies,profile]');
	}

	// Header
	echo '<div id="profile_header">';
	echo '<div id="profile_display_avatar">'.$cpc_user->get_avatar(256, false).'</div>';
	echo '<div id="profile_display_name">'.$cpc_user->display_name.'</div>';

	$privacy = __cpc__get_meta($cpc_user->id, 'share');
	$location = '';
	$city = '';

	if ( ($cpc_user->id == $current_user->ID) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && __cpc__friend_of($cpc_user->id, $current_user->ID)) ) {
			
		$city = __cpc__get_meta($cpc_user->id, 'extended_city');
		$country = __cpc__get_meta($cpc_user->id, 'extended_country');
		
		if ($city != '') { $location .= $city; }
		if ($city != '' && $country != '') { $location .= ", "; }
		if ($country != '') { $location .= $country; }

		$day = (int)__cpc__get_meta($cpc_user->id, 'dob_day');
		$month = __cpc__get_meta($cpc_user->id, 'dob_month');
		$year = (int)__cpc__get_meta($cpc_user->id, 'dob_year');

		if ($year > 0 || $month > 0 || $day > 0) {
			$monthname = __cpc__get_monthname($month);
			if ($day == 0) $day = '';
			if ($year == 0) $year = '';
			$born = get_option(CPC_OPTIONS_PREFIX.'_show_dob_format');
			$born = ( $born != '') ? $born : __('Born', 'cp-communitie').' %monthname %day%th, %year';
			$day0 = str_pad($day, 2, '0', STR_PAD_LEFT);
			$month = ($month > 0) ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
			$month0 = ($month > 0) ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
			$year = ($year > 0) ? $year : '';
			$born = str_replace('%0day', $day0, $born);
			$born = str_replace('%day', $day, $born);
			$born = str_replace('%monthname', $monthname, $born);
			$born = str_replace('%0month', $month0, $born);
			$born = str_replace('%month', $month, $born);
			$born = str_replace('%year', $year, $born);
			$th = 'th';
			if ($day == 1 || $day == 21 || $day == 31) $th = 'st';
			if ($day == 2 || $day == 22) $th = 'nd';
			if ($day == 3 || $day == 23) $th = 'rd';
			if (strpos($born, '%th')) {
				if ($day) {
					$born = str_replace('%th', $th, $born);
				} else {
					$born = str_replace('%th', '', $born);
				}
			}
			$born = str_replace(' ,', ',', $born);
			if ($year == '') $born = str_replace(', ', '', $born);
			$born = apply_filters ( '__cpc__profile_born', $born, $day, $month, $year );

			echo $born.'<br>';
			echo $location.'<br>';
		
		}
		
	} else {
	
		if (strtolower($privacy) == 'friends only')
			echo sprintf(__("Personal information only for %s.", 'cp-communitie'), get_option(CPC_OPTIONS_PREFIX.'_alt_friends'));	

		if (strtolower($privacy) == 'nobody')
			echo __("Personal information is private.", 'cp-communitie');
		
	}
		
	echo '</div>';
	echo '<div style="clear:both"></div>';

	// Status
	echo '<form action="" id="post_form" method="POST" onSubmit="document.getElementById(\'post_form\').style.display = \'none\'">';
	echo $cpc_ui->activity_post(__("What's up?", 'cp-communitie'), 'input_text');
	echo $cpc_ui->activity_post_button(__('Post', 'cp-communitie'), 'submit small red wide');
	echo '</form>';
    echo "<div >";

	// Upload image
	?>
    <!-- The file upload form used as target for the file upload widget -->
    <form id="fileupload" action="//jquery-file-upload.appspot.com/" method="POST" enctype="multipart/form-data">
        <!-- Redirect browsers with JavaScript disabled to the origin page -->
        <noscript><input type="hidden" name="redirect" value="http://blueimp.github.com/jQuery-File-Upload/"></noscript>
        <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
        <div class="row fileupload-buttonbar">
            <div class="span7">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class="btn btn-success fileinput-button" style="height:62px !important;">
                    <span style="font-size:36px !important;">Add Image</span>
                    <input type="file" name="files[]" multiple>
                </span>
            </div>
        </div>
        <!-- The loading indicator is shown during file processing -->
        <div class="fileupload-loading"></div>
        <!-- The table listing the files available for upload/download -->
        <table role="presentation" class="table table-striped" style="width:auto"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
        <input type="hidden" id="uploader_uid" name="uploader_uid" value="<?php echo $current_user->ID; ?>" />
    </form>
    <?php
    echo '</div>';

	$comments = $cpc_user->get_activity($uid1='', $version='friends_activity', 0, 100);
	
	// Build wall
	if ($comments) {	
		
		$forum_url = __cpc__get_url('forum');
									
		$cnt = 0;
		foreach ($comments as $comment) {
		
			$cnt++;
		
			// Check to avoid poke's (as private)								
			if  ( ($comment->type != 'poke') || ($comment->type == 'poke' && ($comment->author_uid == $uid2 || $comment->subject_uid == $uid2 )) ) {	
									
				echo "<div class='__cpc__profile_activity_div' id='post_".$comment->cid."'>";
				
					echo '<a href="index.php?'.$a.'&uid='.$comment->author_uid.'">'.stripslashes($comment->display_name).'</a> ';
					if ($comment->author_uid != $comment->subject_uid && !$comment->is_group) {
						echo ' &rarr; <a href="index.php?'.$a.'&uid='.$comment->subject_uid.'">'.stripslashes($comment->subject_name).'</a> ';
					}
					echo __cpc__time_ago($comment->comment_timestamp).".<br />";


					// Always show reply fields or not?
					$show_class = (get_option(CPC_OPTIONS_PREFIX.'_profile_comments')) ? '' : 'symposium_wall_replies';
					$show_field = (get_option(CPC_OPTIONS_PREFIX.'_profile_comments')) ? '' : 'display:none;';
					
					// $text = the comment
					if ($comment->comment != '') {
						$text = '<br />'.$comment->comment.'<br />';
					} else {
						$text = $comment->comment;
					}
					$text = str_replace(__('Started a new forum topic:', 'cp-communitie'), __('Started ', 'cp-communitie'), $text);

					// Change any forum links to mobile link
					// Skip this as mobile will soon be depricated and replaced with responsive CPC design
					if ( false && get_option(CPC_OPTIONS_PREFIX.'_permalink_structure') && strpos($text, $forum_url) !== false) {
						
						$s = substr($text, strpos($text, $forum_url));
						$s0 = $s[0];
						$e = explode('"', $s0);
						$stubs = explode('/', $e);
						$c = count($stubs);

						if (get_option(CPC_OPTIONS_PREFIX.'_permalinks_cats')) {
							// Two parameters, so go to topic
							$stub0 = $stubs[$c-2];
							$stub1 = $stubs[$c-1];
							$topic_id = __cpc__get_stub_id($stub1, 'forum-topic');
						} else {
							// One parameter, so look for a category first
							$stub1 = $stubs[$c-1];
							$topic_id = __cpc__get_stub_id($stub1, 'forum-topic');
							if (!$topic_id) $topic_id = '';
						}
						$b = explode('"', $text);
						// Get category
						$sql = "SELECT topic_category FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
						$text = $b[0].'forum.php?'.$a.'&catid='.$wpdb->get_var($wpdb->prepare($sql, $topic_id)).'&tid='.$topic_id.$b[2];

					}
										
					// Check for any associated uploaded images for activity
					$directory = WP_CONTENT_DIR."/cpc-content/members/".$comment->author_uid.'/activity/';
					if (file_exists($directory)) {
						$handler = opendir($directory);
						while ($image = readdir($handler)) {
							$path_parts = pathinfo($image);
							if ($path_parts['filename'] == $comment->cid) {
								$directoryURL = WP_CONTENT_URL."/cpc-content/members/".$comment->author_uid.'/activity/'.$image;
								$text .= '<br /><img style="max-width:100%" src="'.$directoryURL.'" /><br />';
							}
						}
					}											
					
					// Finally show comment...!
					echo '<div style="width:100%;">';
					echo __cpc__make_url(stripslashes($text));
					echo '</div>';
					
					// How many replies?
					echo '<br />';
					$replies = $cpc_user->get_replies($comment->cid);
					if ($replies) {
						echo '<input type="submit" onclick="location.href=\'activity_comments.php?'.$a.'&tid='.$comment->cid.'\'" class="submit nowidth small orange" value="'.sprintf(_n('%d comment', '%d comments', count($replies)), count($replies)).'" />';
					} else {
						echo '<input type="submit" onclick="location.href=\'activity_comments.php?'.$a.'&tid='.$comment->cid.'\'" class="submit nowidth small orange floatleft" value="'.__('Comment', 'cp-communitie').'" />';
					}


				echo "</div>";
		
			}
					
		}
	}

}

include_once(dirname(__FILE__).'/footer.php');	



function recursive_remove_directory($directory, $empty=FALSE)
{
	// if the path has a slash at the end we remove it here
	if(substr($directory,-1) == '/')
	{
		$directory = substr($directory,0,-1);
	}

	// if the path is not valid or is not a directory ...
	if(!file_exists($directory) || !is_dir($directory))
	{
		// ... we return false and exit the function
		return FALSE;

	// ... if the path is not readable
	}elseif(!is_readable($directory))
	{
		// ... we return false and exit the function
		return FALSE;

	// ... else if the path is readable
	}else{

		// we open the directory
		$handle = opendir($directory);

		// and scan through the items inside
		while (FALSE !== ($item = readdir($handle)))
		{
			// if the filepointer is not the current directory
			// or the parent directory
			if($item != '.' && $item != '..')
			{
				// we build the new path to delete
				$path = $directory.'/'.$item;

				// if the new path is a directory
				if(is_dir($path)) 
				{
					// we call this function with the new path
					recursive_remove_directory($path);

				// if the new path is a file
				}else{
					// we remove the file
					unlink($path);
				}
			}
		}
		// close the directory
		closedir($handle);

		// if the option to empty is not set to true
		if($empty == FALSE)
		{
			// try to delete the now empty directory
			if(!rmdir($directory))
			{
				// return false if not possible
				return FALSE;
			}
		}
		// return success
		return TRUE;
	}
}
?>

<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td class="preview"><span class="fade"></span></td>
<!--        <td class="name"><span>{%=file.name%}</span></td> -->
<!--        <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td> -->
        {% if (file.error) { %}
            <td class="error" colspan="2"><span class="label label-important">Error</span> {%=file.error%}</td>
        {% } else if (o.files.valid && !i) { %}
            <td>
                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
            </td>
            <td>{% if (!o.options.autoUpload) { %}
                <button class="btn btn-primary start">
                    <i class="icon-upload icon-white"></i>
                    <span>Start</span>
                </button>
            {% } %}</td>
        {% } else { %}
            <td colspan="2"></td>
        {% } %}
<!--        <td>{% if (!i) { %}
            <button class="btn btn-warning cancel">
                <i class="icon-ban-circle icon-white"></i>
                <span>Cancel</span>
            </button>
        {% } %}</td> -->
    </tr>
{% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
        {% if (file.error) { %}
            <td></td>
<!--            <td class="name"><span>{%=file.name%}</span></td> -->
<!--            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td> -->
            <td class="error" colspan="2"><span class="label label-important">Error</span> {%=file.error%}</td>
        {% } else { %}
            <td class="preview">{% if (file.thumbnail_url) { %}
                <a href="{%=file.url%}" title="{%=file.name%}" data-gallery="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"></a>
            {% } %}</td>
<!--            <td class="name"><a href="{%=file.url%}" title="{%=file.name%}" data-gallery="{%=file.thumbnail_url&&'gallery'%}" download="{%=file.name%}">{%=file.name%}</a></td> -->
<!--            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td> -->
<!--            <td colspan="2"></td> -->
        {% } %}
<!--        <td>
            <button class="btn btn-danger delete" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}"{% if (file.delete_with_credentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
                <i class="icon-trash icon-white"></i>
                <span>Delete</span>
            </button>
        </td> -->
    </tr>
{% } %}
</script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="js/vendor/jquery.ui.widget.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="http://blueimp.github.com/JavaScript-Templates/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="http://blueimp.github.com/JavaScript-Load-Image/load-image.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="http://blueimp.github.com/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS and Bootstrap Image Gallery are not required, but included for the demo -->
<script src="http://blueimp.github.com/cdn/js/bootstrap.min.js"></script>
<script src="http://blueimp.github.com/Bootstrap-Image-Gallery/js/bootstrap-image-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="js/jquery.fileupload.js"></script>
<!-- The File Upload file processing plugin -->
<script src="js/jquery.fileupload-fp.js"></script>
<!-- The File Upload user interface plugin -->
<script src="js/jquery.fileupload-ui.js"></script>
<!-- The main application script -->
<script src="js/main.js"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE8+ -->
<!--[if gte IE 8]><script src="js/cors/jquery.xdr-transport.js"></script><![endif]-->

</body>
</html>
