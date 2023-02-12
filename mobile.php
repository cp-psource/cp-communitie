<?php
/*
CP Community Mobile
Description: Mobile, SEO and Accessibility plugin compatible with CP Community. Activate and read instructions on Mobile tab on the <a href='admin.php?page=__cpc__mobile_menu'>options page</a>.
*/


/* ***************************************************** GROUP PAGE ***************************************************** */

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

global $wpdb;

// Added to page load to check for mobile
add_filter('__cpc__profile_header_filter', '__cpc__mobile_check', 10, 2);
function __cpc__mobile_check($html, $uid1='') {

	require_once(dirname(__FILE__).'/mobile-files/mobile_check.php');
	if (get_option(CPC_OPTIONS_PREFIX.'_mobile_useragent'))
		echo $useragent.'<br>';
	if (get_option(CPC_OPTIONS_PREFIX.'_mobile_useragent') && $mobile)
		echo 'Mobile/tablet detected<br>';
	
	$forum = __cpc__get_url('forum').'/';
	$profile = __cpc__get_url('profile').'/';
	$url = $_SERVER["REQUEST_URI"];
	
	if (strpos($profile, $url) || strpos($forum, $url)) {
		if ($mobile) {
			if (get_option(CPC_OPTIONS_PREFIX.'_mobile_notice') != 'hide') {
				$html = '<div id="mobile_notice">'.get_option(CPC_OPTIONS_PREFIX.'_mobile_notice').'</div>'.$html;
			}
		}
	}
	
	return $html;
	
}


// Function to ClassicPress knows this plugin is activated
function __cpc__mobile()  
{  

	// Add to WP admin menu
	return 'cp-communitie';
	exit;
		
}

// Add plugin to admin menu via hook
function __cpc__add_mobile_to_admin_menu()
{
	$hidden = get_option(CPC_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('cpcommunitie_debug'.$hidden, __('Mobile', 'cp-communitie'), __('Mobil', 'cp-communitie'), 'manage_options', '__cpc__mobile_menu', '__cpc__mobile_menu');
}
add_action('__cpc__admin_menu_hook', '__cpc__add_mobile_to_admin_menu');

function __cpc__mobile_menu() {

		global $wpdb;
		
    	// See if the user has posted Mobile settings
		if( isset($_POST[ 'cpcommunitie_update' ]) && $_POST[ 'cpcommunitie_update' ] == '__cpc__mobile_menu' ) {
    	    	        
			update_option(CPC_OPTIONS_PREFIX.'_mobile_topics', $_POST['mobile_topics']);
			update_option(CPC_OPTIONS_PREFIX.'_mobile_notice', stripslashes($_POST['mobile_notice']));
			update_option(CPC_OPTIONS_PREFIX.'_mobile_useragent', isset($_POST['mobile_useragent']) ? $_POST['mobile_useragent'] : '');
			echo "<div class='updated slideaway'><p>".__('Gespeichert', 'cp-communitie').".</p></div>";

	    }
	 
	 	// Check for default values
	 	if (!get_option(CPC_OPTIONS_PREFIX.'_mobile_notice'))
	 		update_option(CPC_OPTIONS_PREFIX.'_mobile_notice', "<a href='/m'>Go Mobile!</a>");
	    // Get values from database  
		$mobile_topics = get_option(CPC_OPTIONS_PREFIX.'_mobile_topics');
		$mobile_notice = get_option(CPC_OPTIONS_PREFIX.'_mobile_notice');
		$mobile_useragent = get_option(CPC_OPTIONS_PREFIX.'_mobile_useragent');

	  	echo '<div class="wrap">';

		  	echo '<div id="icon-themes" class="icon32"><br /></div>';
		  	echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';
		
			__cpc__show_tabs_header('mobile');

			?>
	
					<form method="post" action=""> 
					<input type="hidden" name="cpcommunitie_update" value="__cpc__mobile_menu">
		
					<table class="form-table"> 
		
						<tr><td colspan="2"><h2><?php _e('Einstellungen', 'cp-communitie') ?></h2></td></tr>

						<tr valign="top"> 
						<td scope="row"><label for="mobile_notice"><?php _e('Hinweis für Mobilgeräte/Tablets', 'cp-communitie'); ?></label></td> 
						<td><input name="mobile_notice" type="text" id="mobile_notice"  value="<?php echo $mobile_notice; ?>" style="width:300px" /> <br />
						<span class="description">
							<?php echo __('Text, der oben auf der relevanten Seite angezeigt wird, wenn ein Handy/Tablet erkannt wird (HTML/Links erlaubt).<br />Geben Sie \'hide\' ein, um die Anzeige zu vermeiden.', 'cp-communitie'); ?>
						</span></td> 
						</tr> 
	
						<tr valign="top"> 
						<td scope="row"><label for="mobile_topics"><?php _e('Maximale Anzahl von Themen', 'cp-communitie'); ?></label></td> 
						<td><input name="mobile_topics" type="text" id="mobile_topics"  value="<?php echo $mobile_topics; ?>" style="width:50px" /> 
						<span class="description"><?php echo __('Die Thread-Ansicht ist ebenfalls auf die letzten 7 Tage beschränkt', 'cp-communitie'); ?></td> 
						</tr> 

						<tr valign="top"> 
						<td scope="row"><label for="mobile_useragent"><?php _e('Benutzeragent anzeigen', 'cp-communitie'); ?></label></td>
						<td>
						<input type="checkbox" name="mobile_useragent" id="mobile_useragent" <?php if ($mobile_useragent == "on") { echo "CHECKED"; } ?>/>
						<span class="description"><?php echo __('Nur zur Verwendung durch den Administrator, um den Benutzeragenten für mobile Geräte zu überprüfen', 'cp-communitie'); ?></span></td> 
						</tr> 
					
					</table>
		 					
				<p class="submit" style="margin-left:6px;">
				<input type="submit" name="Submit" class="button-primary" value="<?php _e('Änderungen speichern', 'cp-communitie'); ?>" />
				</p>
				</form>

				<table class="form-table"><tr><td colspan="2">
				<h2><?php _e('Installationsschritte', 'cp-communitie') ?></h2>

				<div style="margin:10px">
				<p><?php _e("So installierst Du das Mobile/SEO/Accessibility-Plugin auf Deiner Webseite:", 'cp-communitie') ?></p>

				<ol>
					<li>Erstelle in dem Verzeichnis, in dem ClassicPress installiert ist, einen Ordner für Deine mobile Version, zum Beispiel „/mobil“..</li>
					<li>Zum Beispiel für /mobil create <?php echo str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'); ?>mobil zum Erstellen von <?php echo str_replace('//', '/', get_bloginfo('wpurl').'/'); ?>mobil</li>
					<li>Entpacke <strong>mobile-files.zip</strong> (zu finden in Deinem <?php echo CPC_WL; ?> Plugin-Ordner).</li>
					<li>Kopiere den <strong>extrahierten Inhalt</strong> in den Ordner aus Schritt 2.</li>
				</ol>
				</div>
				</td></tr></table>

				<table class="form-table"><tr><td colspan="2">
				<h2><?php echo sprintf(__('Mobile Version von %s', 'cp-communitie'), CPC_WL); ?></h2>
		
				<div style="margin:10px">
				<p>Navigiere auf Deinem Mobilgerät/Telefon zu (zum Beispiel) <a target='_blank' href='<?php echo str_replace('http:/', 'http://', str_replace('//', '/', get_bloginfo('wpurl').'/')); ?>mobil'><?php echo str_replace('http:/', 'http://', str_replace('//', '/', get_bloginfo('wpurl').'/')); ?>mobil</a></p>
				</div>

				</td></tr></table>

				<table class="form-table"><tr><td colspan="2">
				<h2><?php echo sprintf(__('Zugängliche Version von %s', 'cp-communitie'), CPC_WL); ?></h2>
		
				<div style="margin:10px">
				<p>Um zu erzwingen, dass die mobile Version in einem normalen Browser angezeigt wird, füge ?a=1 hinzu. Beispiel: <a target='_blank' href='<?php echo get_bloginfo('wpurl'); ?>/mobil?a=1'><?php echo get_bloginfo('wpurl'); ?>/mobil?a=1</a></p>
				</div>
				</td></tr></table>

				<table class="form-table"><tr><td colspan="2">
				<h2><?php echo __('Suchmaschinen', 'cp-communitie'); ?></h2>
		
				<div style="margin:10px">
				<p>Sende die URL (zum Beispiel) <?php echo get_bloginfo('wpurl'); ?>/mobil an Suchmaschinen. Wenn Personen den indizierten Link besuchen, werden sie automatisch zur vollständigen Webseite weitergeleitet (außer auf einem mobilen Gerät).</p>
				</div>
				</td></tr></table>

			<?php __cpc__show_tabs_header_end(); ?>
		</div>
	<?php
}


?>
