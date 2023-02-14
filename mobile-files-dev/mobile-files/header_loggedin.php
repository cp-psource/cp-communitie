<?php
function show_header($buttons) {

	global $current_user;
	$u = isset($_GET['uid']) ? $_GET['uid'] : $current_user->ID;
	if ($u == '') $u = $current_user->ID;
	$a = isset($_GET['a']) ? 'a='.$_GET['a'] : '';

	echo '<div id="buttons_div">';

	echo '<input type="submit" onclick="location.href=\'login.php?'.$a.'\'" class="submit small black floatright" style="margin-right:6px" value="&nbsp;&#10008;" />';
	if (strpos($buttons, 'home'))
		echo '<input type="submit" onclick="location.href=\'index.php?'.$a.'\'" class="submit small black floatleft" value="'.__('Home', 'cp-communitie').'" />';
	if (strpos($buttons, 'reload'))
		echo '<input type="submit" onclick="location.href=\'index.php?'.$a.'\'" class="submit small brown floatleft" value="'.__('Reload', 'cp-communitie').'" />';
	
	if (function_exists('__cpc__forum')) {
		
		if (strpos($buttons, 'forum'))
			echo '<input type="submit" onclick="location.href=\'forum.php?'.$a.'\'" class="submit small blue floatleft" value="'.__('Forum', 'cp-communitie').'" />';
		if (strpos($buttons, 'topics'))
			echo '<input type="submit" onclick="location.href=\'forum_threads.php?'.$a.'\'" class="submit small blue floatleft" value="'.__('Topics', 'cp-communitie').'" />';
		if (strpos($buttons, 'replies'))
			echo '<input type="submit" onclick="location.href=\'forum_replies.php?'.$a.'\'" class="submit small blue floatleft" value="'.__('Replies', 'cp-communitie').'" />';
		if (strpos($buttons, 'gotop'))
			echo '<input type="submit" onclick="location.href=\'forum.php?'.$a.'\'" class="submit small rosy floatleft" value="'.__('Top', 'cp-communitie').'" />';
		if (strpos($buttons, 'new'))
			echo '<input type="submit" onclick="location.href=\'forum_new_topic.php?'.$a.'\'" class="submit small blue floatleft" value="'.__('New Topic', 'cp-communitie').'" />';
			
	}
	
	if (strpos($buttons, 'friends'))
		echo '<input type="submit" onclick="location.href=\'friends.php?'.$a.'\'" class="submit small purple floatleft" value="'.__('Friends', 'cp-communitie').'" />';
	if (strpos($buttons, 'profile'))
		echo '<input type="submit" onclick="location.href=\'profile.php?'.$a.'&uid='.$u.'\'" class="submit small yellow floatleft" value="'.__('Profile', 'cp-communitie').'" />';
	if (strpos($buttons, 'back'))
		echo '<input type="submit" onclick="location.href=\'index.php?'.$a.'\'" class="submit small orange floatleft" value="'.__('Back...', 'cp-communitie').'" />';
	echo '</div>';

	echo '<div style="clear:both"></div>';
	echo '<div class="line">';
	
	
}


?>

