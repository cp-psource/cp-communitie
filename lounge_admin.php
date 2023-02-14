<?php
// Update any POSTed updates
if (isset($_POST['cpcommunitie_lounge_max_rows'])) {
	update_option(CPC_OPTIONS_PREFIX.'_lounge_max_rows', $_POST['cpcommunitie_lounge_max_rows']);
}

?>


<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>
<?php
echo '<h2>'.sprintf(__('%s Einstellungen', 'cp-communitie'), CPC_WL).'</h2><br />';

__cpc__show_tabs_header('lounge');
?>

<table class="form-table"><tr><td colspan="2">
<h2><?php echo __('Einstellungen', 'cp-communitie'); ?></h2>


<div>
<form action="" method="POST">
	
	<p>
	<?php _e('Das Lounge-Plug-in bietet einen seitenweiten Chatroom ("Shoutbox").', 'cp-communitie'); ?>
	</p>
	<?php _e('Maximale Anzahl zurückgegebener Zeilen', 'cp-communitie'); ?>: <input type="text" name="cpcommunitie_lounge_max_rows" style="width:40px" value="<?php echo get_option(CPC_OPTIONS_PREFIX."_lounge_max_rows")+0; ?>" />
	<br /><br />
	<input type="submit" class="button-primary" value="<?php _e('Speichern', 'cp-communitie'); ?>">
	
</form>
</div>
</td></tr></table>

<table class="form-table __cpc__admin_table"><tr><td colspan="2">
<h2>Entwicklungsdemonstrator</h2>

<div>
<p>
Der Hauptzweck des Plugins besteht darin, als Demonstrator und/oder Vorlage für die Entwicklung von Plugins zu dienen, die mit <?php echo CPC_WL; ?> sind.
</p>

Es enthält viele Funktionen wie die Verwendung von:
<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
<li><?php echo CPC_WL; ?> Hooks und Filter</li>
<li><?php echo CPC_WL; ?> Funktionen</li>
<li><?php echo CPC_WL; ?> Javascript-Variablen</li>
</ul>

<p>
Es zeigt auch, wie man:
</p>

<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
<li>Ein Profilseitenmenü hinzufügt und Inhalte innerhalb der Profilseite anzeigt, ohne die ganze Seite neu zu laden</li>
<li>AJAX verwendet, um Informationen in ClassicPress zu präsentieren und teilweise Inhalte ohne Seitenaktualisierung neu zu laden</li>
<li>Plug in <?php echo CPC_WL; ?> Installationsseite</li>
<li>Einen Kurzcode hinzufügt, um Inhalte als Teil einer ClassicPress-Seite einzuschließen</li>
</ul>
</div>
</td></tr></table>


<table class="form-table __cpc__admin_table">
	<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
	<tr><td width="165px">[<?php echo CPC_SHORTCODE_PREFIX; ?>-lounge]</td>
		<td><?php echo __('Shortcode zum Anzeigen von Die Lounge auf einer Seite.', 'cp-communitie'); ?></td></tr>
</table>



<?php __cpc__show_tabs_header_end(); ?>

</div>
