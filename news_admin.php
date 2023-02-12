<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>

<?php
echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';

__cpc__show_tabs_header('alerts');

if (isset($_POST['__cpc__alerts_update'])) {
	// React to POSTed information
	if (isset($_POST['__cpc__news_polling'])) {
		update_option(CPC_OPTIONS_PREFIX.'_news_polling', $_POST['__cpc__news_polling']);
	}
	if (isset($_POST['__cpc__news_x_offset'])) {
		update_option(CPC_OPTIONS_PREFIX.'_news_x_offset', $_POST['__cpc__news_x_offset']);
	}
	if (isset($_POST['__cpc__news_y_offset'])) {
		update_option(CPC_OPTIONS_PREFIX.'_news_y_offset', $_POST['__cpc__news_y_offset']);
	}
	update_option(CPC_OPTIONS_PREFIX.'_hide_news_list', isset($_POST['hide_news_list']) ? $_POST['hide_news_list'] : '');					
}
?>


<form action="" method="POST">

	<input type="hidden" name="__cpc__alerts_update" value="yes" />

	<table class="form-table __cpc__admin_table">

		<tr><td colspan="2"><h2><?php _e('Einstellungen', 'cp-communitie') ?></h2></td></tr>
	
		<tr><td colspan="2">
				<?php _e('Das Alerts-Plugin aktualisiert ein DIV (das sich in einem ClassicPress-Menüelement befindet oder in ein Design eingebettet sein kann), das das Mitglied über Neuigkeiten/Benachrichtigungen wie neue E-Mails/Freunde/Aktivitäten/usw. benachrichtigt – Benachrichtigungen können von anderen Plugins hinzugefügt werden.', 'cp-communitie'); ?><br />
				<?php _e('Je nach verwendetem Theme ist die Position der Liste der Warnungen möglicherweise nicht genau so, wie Du es wünschst.', 'cp-communitie'); ?><br />
				<?php _e('Um die Liste der Warnungen nach links/rechts oder oben/unten zu verschieben, ändere die Offset-Werte unten. Verwende negative Werte, um sie nach links/oben zu bewegen, und positive Werte, um sie nach rechts/unten zu bewegen.', 'cp-communitie'); ?>
		</td></tr>

		<tr valign="top"> 
		<td scope="row"><label for="__cpc__news_x_offset"><?php _e('Horizontaler Versatz', 'cp-communitie'); ?></label></td>
		<td>
		<input type="text" name="__cpc__news_x_offset" id="use_chat" value="<?php echo get_option(CPC_OPTIONS_PREFIX."_news_x_offset"); ?>"/>
		<span class="description"><?php echo __('Verschiebe die Position der Liste der Warnungen nach links/rechts', 'cp-communitie'); ?></span></td> 
		</tr> 	
		
		<tr valign="top"> 
		<td scope="row"><label for="__cpc__news_y_offset"><?php _e('Vertikaler Versatz', 'cp-communitie'); ?></label></td>
		<td>
		<input type="text" name="__cpc__news_y_offset" id="use_chat" value="<?php echo get_option(CPC_OPTIONS_PREFIX."_news_y_offset"); ?>"/>
		<span class="description"><?php echo __('Verschiebe die Position der Liste der Warnungen nach oben/unten', 'cp-communitie'); ?></span></td> 
		</tr> 	
		
		<tr valign="top"> 
		<td scope="row"><label for="__cpc__news_polling"><?php _e('Abfrageintervall (Sekunden)', 'cp-communitie'); ?></label></td>
		<td>
		<input type="text" name="__cpc__news_polling" id="use_chat" value="<?php echo get_option(CPC_OPTIONS_PREFIX."_news_polling"); ?>"/>
		<span class="description"><?php echo __('Ändere das Abrufintervall, um die Belastung Deines Servers zu verringern', 'cp-communitie'); ?></span></td> 
		</tr> 	

		<tr><td colspan="2">
				<?php _e('Wenn Dein Theme Probleme mit der Dropdown-Liste verursacht, besteht eine Alternative darin, die Dropdown-Liste auszublenden, und der Benutzer wird zur Seite „Warnungen“ weitergeleitet, wenn auf das Menüelement geklickt wird:', 'cp-communitie'); ?><br />
		</td></tr>

		<tr valign="top"> 
		<td scope="row"><label for="hide_news_list"><?php echo __('Benachrichtigungsliste ausblenden', 'cp-communitie'); ?></label></td>
		<td>
		<input type="checkbox" name="hide_news_list" id="hide_news_list" <?php if (get_option(CPC_OPTIONS_PREFIX.'_hide_news_list') == "on") { echo "CHECKED"; } ?>/>
		<span class="description"><?php echo __('Ausblenden, wenn Probleme auftreten, der Menüpunkt wechselt stattdessen zur Seite „Warnungen“.', 'cp-communitie'); ?></span></td> 
		</tr> 
		
		<tr><td colspan="2"><h2><?php _e('Implementieren', 'cp-communitie') ?></h2></td></tr>
		
		<tr><td colspan="2">
			<strong><?php _e('Als Menüpunkt hinzufügen', 'cp-communitie'); ?></strong>
			<ol>
				<li><a href="post-new.php?post_type=page">Erstelle eine ClassicPress-Seite</a> (um den Verlauf anzuzeigen, wenn auf das Menüelement selbst geklickt wird) und mache den Seitentitel „Benachrichtigungen“ (oder wie Du es nennen möchtest). Dies ist nicht das, was im Menü erscheint, kann aber als Dein Seitentitel erscheinen, wenn die Seite angezeigt wird.</li>
				<li>Gib den Shortcode [cpcommunitie-alerts] auf der Seite ein (Achtung: Bindestrich, kein Unterstrich)</li>
				<li>Besuche die <a href="admin.php?page=cpcommunitie_debug">Installationsseite</a>, um die Einrichtung der neuen Seite abzuschließen.</li>
				<li><a href="nav-menus.php">Bearbeite Dein Webseiten-Menü</a> und füge die neu erstellte Seite zum Menü hinzu. Ändere die Navigationsbezeichnung Deines neuen Menüpunkts in die im gelben Feld unten angezeigte.</li>
			</ol>
			
			<div style="border:1px dotted #333; padding:6px; border-radius:3px; width: 400px; font-family: courier; text-align: center; margin: 20px auto 20px; background-color: #ff9">
			&lt;div id='__cpc__alerts'&gt;Benachrichtigungen&lt;/div&gt;
			</div>
			
			<strong><?php _e('Zum Hinzufügen zu einer Theme-Vorlage', 'cp-communitie'); ?></strong>
			<ol>
				<li>Bearbeite Dein Theme, Deine Seitenleiste usw. und füge den im gelben Feld oben angezeigten Code an der Position ein, an der die Warnungen angezeigt werden.</li>
			</ol>
		</td></tr>

		<tr><td colspan="2"><h2><?php _e('Shortcodes', 'cp-communitie') ?></h2></td></tr>

        <tr valign="top"> 
            <td scope="row">
                [<?php echo CPC_SHORTCODE_PREFIX; ?>-alerts]
            </td>
            <td>
            <?php echo __('Zeigt die neuesten Benachrichtigungen eines Mitglieds an.', 'cp-communitie').'<br />'; ?>
            <?php echo '<strong>'.__('Parameter', 'cp-communitie').'</strong><br />'; ?>
            <?php echo __('<div style="width:75px;float:left;">count:</div>Überschreibe die Standardanzahl der angezeigten Warnungen (50)', 'cp-communitie').'<br />'; ?>
            <?php echo '<strong>'.__('Beispiel', 'cp-communitie').'</strong><br />'; ?>
            <?php echo sprintf(__('[%s-alerts count=200]', 'cp-communitie'), CPC_SHORTCODE_PREFIX); ?>
            </td>
        </tr>
            
	</table> 
	
	<p style="margin-left:6px"> 
	<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Änderungen speichern', 'cp-communitie'); ?>" /> 
	</p> 
	
</form> 

<?php __cpc__show_tabs_header_end(); ?>

</div>
