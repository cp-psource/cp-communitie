<?php
/**
 * Template Name: Demo forum page 3
 * Description: A Forum Page Template to demonstrate using classes
 *
 * @package ClassicPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 


// include the PHP class files, the path should match your server, the following will probably do!
require_once(CPC_PLUGIN_DIR.'/class.cpc.php');
require_once(CPC_PLUGIN_DIR.'/class.cpc_user.php');
require_once(CPC_PLUGIN_DIR.'/class.cpc_ui.php');
require_once(CPC_PLUGIN_DIR.'/class.cpc_forum.php');

$cpc = new cpc(); 
$cpc_ui = new cpc_ui(); 
$cpc_forum = new cpc_forum(); // Defaults to top level, can pass a category ID to set root level

/*
First we over-ride settings for forum page to ensure links to this page across go to
the correct page. Note that you will need to visit/reload this page
the first time the script is run, as various constants are set prior to this page template
loading. If you visit Admin->Installation the default values will be reset, 
which includes after upgrading CPC, so re-visit this page at least once after visiting 
the Installation page, to put things back to the new page. Alternatively, create a 
page that updates this (and maybe other) URLs that you can visit as admin once after upgrading CPC.

This is hardcoded to a particular page for now. If distributing to other user's this will
need to be dynamically set! Change it to make the URL of your new forum page, mine is as
per the tutorial (ie. a page called "AA Forum").
*/
$cpc->set_forum_url('/aa-forum');
?>

<!--
Links to styles used in this page template - shouldn't be included in the page template really,
but is included here to keep things simple for the tutorial at www.cpcymposium.com/blog.
Should be included in the theme header.php in the <HEAD> ... </HEAD> tags.
This also assumes the .css file is also in the current theme folder along with this page file. 
-->
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/cpc_forum_page.css" />

<div id="primary">
	<div id="content" role="main">
	
	<!-- ClassicPress page content components -->
	<?php the_post(); ?>
	<?php get_template_part( 'content', 'page' ); ?>
	<!-- End ClassicPress page content components -->

	<?php	
	// Get passed parameters
	$catid = isset($_GET['catid']) ? $_GET['catid'] : 0;
	$tid = isset($_GET['tid']) ? $_GET['tid'] : 0;

	// If tid passed, show the topic
	if ($tid) {

		// Add breadcrumbs (including last level as a link)
		echo '<div id="my-topic-breadcrumbs">';
			echo $cpc_ui->get_breadcrumbs($catid, 'catid', true, '&rarr;', true);
		echo '</div>';

		// Get the topic post
		$topic = $cpc_forum->get_topic($tid);
		
		// Show avatar
		echo '<div id="my-topic-avatar">';
			$cpc_user = new cpc_user($tid->topic_author);
			echo $cpc_user->get_avatar(100);
		echo '</div>';
		
		// Show initial topic post
		echo '<div id="my-forum-topic">';
			echo '<div id="my-topic-subject">';
			echo stripslashes($topic->topic_owner);
			echo '</div>';
			echo '<div id="my-topic-author">';
			echo 'Gestartet von '.$topic->display_name.' '.__cpc__time_ago($topic->topic_started);
			echo '</div>';
			echo '<div id="my-topic-first-post">';
				$post_text = __cpc__make_url(stripslashes($topic->topic_post));
				$post_text = __cpc__bbcode_replace($post_text);
				if (!get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg')) $post_text = str_replace(chr(13), "<br />", $post_text);
				echo $post_text;
			echo '</div>';
		echo '</div>';
		
		// Show replies
		$replies = $cpc_forum->get_replies($tid);
		foreach ($replies as $reply) {
		// Show avatar
			echo '<div class="my-forum-reply">';
				echo '<div class="my-reply-avatar">';
					$cpc_user = new cpc_user($reply->topic_owner);
					echo $cpc_user->get_avatar(60);
				echo '</div>';
				echo '<div class="my-reply-author">';
					echo 'Reply by '.$reply->display_name.' '.__cpc__time_ago($reply->topic_started);
				echo '</div>';
				$post_text = __cpc__make_url(stripslashes($reply->topic_post));
				$post_text = __cpc__bbcode_replace($post_text);
				if (!get_option(CPC_OPTIONS_PREFIX.'_use_wysiwyg')) $post_text = str_replace(chr(13), "<br />", $post_text);
				echo $post_text;
			echo '</div>';
		}
		
	} else {

		// Add breadcrumbs
		echo $cpc_ui->get_breadcrumbs($catid, 'catid');
		
		// Start forum table
		echo '<div id="my-forum-table">';
				
			// Shows categories from the current level
			$categories = $cpc_forum->get_categories($catid);
			if ($categories) {
				echo '<div class="my-forum-row my-forum-row my-forum-row-header">';
					echo '<div class="my-forum-title">';
						echo '<strong>KATEGORIE</strong>';
					echo '</div>';
					echo '<div class="my-forum-title-topic">';
						echo '<strong>LETZTES THEMA</strong>';
					echo '</div>';
				echo '</div>';
				foreach ($categories as $category) {
					echo '<div class="my-forum-row">';
						echo '<div class="my-forum-row-title">';
							$title = stripslashes($category->title);	
							echo '<a href="'.$cpc->get_forum_url().$cpc->get_url_q($cpc->get_forum_url()).'catid='.$category->cid.'">'.$title.'</a>';
						echo '</div>';
						echo '<div class="my-forum-row-last-topic">';
							$last_topic = $cpc_forum->get_topics($category->cid, 0, 1);
							if ($last_topic) {
								echo '<div class="my-forum-row-last-topic-avatar">';
									$cpc_user = new cpc_user($last_topic->topic_owner);
									echo '<a href="'.$cpc->get_profile_url().$cpc->get_url_q($cpc->get_profile_url()).'uid='.$last_topic->topic_owner.'">';
									echo $cpc_user->get_avatar(48);
									echo '</a>';
								echo '</div>';
								echo "<a href='".$cpc->get_forum_url().$cpc->get_url_q($cpc->get_forum_url())."catid=".$category->cid."&tid=".$last_topic->tid."'>".stripslashes($last_topic->topic_subject)."</a> ";
								echo '<span class="my-forum-row-last-topic-owner">'.$last_topic->display_name.',</span> ';
								echo '<span class="my-forum-row-last-topic-started">'.__cpc__time_ago($last_topic->topic_started).'</span>';
							}
						echo '</div>';
					echo '</div>';
				}
			}
		
			// Show topics in this category
			$topics = $cpc_forum->get_topics($catid);
			if ($topics) {
				// Header
				echo '<div class="my-topic-row-header">';
					echo '<div class="my-forum-title">';
						echo '<strong>THEMA</strong>';
					echo '</div>';
					echo '<div class="my-topics-title-topic">';
						echo '<strong>LETZTE ANTWORT</strong>';
					echo '</div>';
					echo '<div class="my-topics-title-replies">';
						echo '<strong>ANTWORTEN</strong>';
					echo '</div>';
				echo '</div>';
				foreach ($topics as $topic) {
					echo '<div class="my-topic-row">';
						// Topic subject
						echo '<div class="my-topic-row-title">';
							$topic_subject = stripslashes($topic->topic_subject);
							if (strlen($topic_subject) > 60) $topic_subject = substr($topic_subject, 0, 60).'...';
							echo "<a href='".$cpc->get_forum_url().__cpc__string_query($cpc->get_forum_url())."catid=".$catid."&tid=".$topic->tid."'>".$topic_subject."</a>";
						echo '</div>';
						// Last reply
						$last_reply = $cpc_forum->get_replies($topic->tid, 0, 1);
						echo '<div class="my-forum-row-last-topic">';
							if ($last_reply) {
								$reply = stripslashes($last_reply->topic_post);
								$reply = str_replace('<br />', ' ', $reply);
								$reply = str_replace('<p>', '', $reply);
								$reply = str_replace('</p>', ' ', $reply);
								if (strlen($reply) > 60) $reply = substr(strip_tags($reply), 0, 60).'...';
								echo '<div class="my-topic-row-last-topic-avatar">';
									$cpc_user = new cpc_user($last_reply->topic_owner);
									echo '<a href="'.$cpc->get_profile_url().'?uid='.$last_reply->topic_owner.'">';
									echo $cpc_user->get_avatar(48);
									echo '</a>';
								echo '</div>';
								echo '<span class="my-forum-row-last-topic-owner">'.$last_reply->display_name.',</span> ';
								echo '<span class="my-forum-row-last-topic-started">'.__cpc__time_ago($last_reply->topic_started).'</span>';
								if ($topic->topic_views > 0) {
									echo '<span class="my-forum-row-last-num-views">, '.$topic->topic_views.' views</span> ';
								}
								echo '<br />'.$reply;
							}
						echo '</div>';
						echo '<div class="my-forum-row-num-replies">';
							echo $topic->topic_replies;
						echo '</div>';
					echo '</div>';
				}
			}	
	
		echo '</div>';

	}
	?>
				
	</div><!-- #content -->
</div><!-- #primary -->
<?php get_footer(); ?>
