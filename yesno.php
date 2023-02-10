<?php
/*
Yes/No Widget for CP Community
Adds a CP Community Widget to display a Yes/No vote with chart (bar or pie). Requires a licence from http://www.jscharts.com to remove small JS Charts logo. Requires CP Community core plugin to be activated.   
*/


/** Add our function to the widgets_init hook. **/

add_action( 'widgets_init', '__cpc__load_widget_yesno_vote' );

function __cpc__load_widget_yesno_vote() {
	register_widget( '__cpc__vote_Widget' );
}


/** Vote ************************************************************************* **/
class __cpc__vote_Widget extends WP_Widget {

	function __cpc__vote_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => '__cpc__widget_vote', 'description' => 'Ermöglicht den Mitgliedern, über eine JA/NEIN-Frage abzustimmen.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => '__cpc__vote_widget' );
		
		/* Create the widget. */
		parent::__construct( 
		    '__cpc__vote_widget', 
		    CPC_WL_SHORT.': '.__('Abstimmung', CPC_TEXT_DOMAIN),
		    $widget_ops, 
		    $control_ops 
		);
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		
		global $wpdb, $current_user;
		wp_get_current_user();
			
		extract( $args );

		// Get options
		$__cpc__vote_question = apply_filters('__cpc__widget_vote_question', $instance['cpcommunitie_vote_question'] );
		$__cpc__vote_forum = apply_filters('widget___cpc__vote_forum', $instance['__cpc__vote_forum'] );
		$__cpc__vote_counts = apply_filters('__cpc__widget_vote_counts', $instance['cpcommunitie_vote_counts'] );
		$__cpc__vote_type = apply_filters('__cpc__widget_vote_type', $instance['cpcommunitie_vote_type'] );
		$__cpc__vote_key = apply_filters('__cpc__widget_vote_key', $instance['cpcommunitie_vote_key'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $__cpc__vote_question . $after_title;
		
		// Content of widget

		echo '<div id="__cpc__chartcontainer">Chart of results</div>';
		echo '<div id="cpcommunitie_chart_type" style="display:none">'.$__cpc__vote_type.'</div>';
		echo '<div id="cpcommunitie_chart_counts" style="display:none">'.$__cpc__vote_counts.'</div>';
		echo '<div id="cpcommunitie_chart_key" style="display:none">'.$__cpc__vote_key.'</div>';

		// Store values
		$__cpc__vote_yes = get_option(CPC_OPTIONS_PREFIX."_vote_yes");
		if ($__cpc__vote_yes != false) {
			$__cpc__vote_yes = (int) $__cpc__vote_yes;
		} else {
		    update_option(CPC_OPTIONS_PREFIX."_vote_yes", 0);	    	   	
			$__cpc__vote_yes = 0;
		}
		$__cpc__vote_no = get_option(CPC_OPTIONS_PREFIX."_vote_no");
		if ($__cpc__vote_no != false) {
			$__cpc__vote_no = (int) $__cpc__vote_no;
		} else {
		    update_option(CPC_OPTIONS_PREFIX."_vote_no", 0);	    	   	
			$__cpc__vote_no = 0;
		}

		echo '<div id="__cpc__chart_yes" style="display:none">'.$__cpc__vote_yes.'</div>';
		echo '<div id="cpcommunitie_chart_no" style="display:none">'.$__cpc__vote_no.'</div>';
			
		if (is_user_logged_in()) {
			
			$voted = __cpc__get_meta($current_user->ID, 'widget_voted');
			if ($voted == "on") {
				
				echo "<p>";
				echo __('Danke für Deine Stimme', CPC_TEXT_DOMAIN).".";
				if ($__cpc__vote_forum != '') {
					echo "<br /><a href='".$__cpc__vote_forum."'>".__('Diskutiere dies im Forum', CPC_TEXT_DOMAIN)."...</a>";
				}
				echo "</p>";

			} else {
			
			
				echo "<div id='__cpc__vote_forum'>";
					echo "<p>".__('Your vote', CPC_TEXT_DOMAIN).": ";
					echo "<a href='javascript:void(0)' title='yes' class='cpcommunitie_answer' value='".__("Ja", CPC_TEXT_DOMAIN)."'>".__("Ja", CPC_TEXT_DOMAIN)."</a> ".__('oder', CPC_TEXT_DOMAIN)." ";
					echo "<a href='javascript:void(0)' title='no' class='cpcommunitie_answer' value='".__("Nein", CPC_TEXT_DOMAIN)."'>".__("Nein", CPC_TEXT_DOMAIN)."</a>";
					if ($__cpc__vote_forum != '') {
						echo "<br /><a href='".$__cpc__vote_forum."'>".__('Diskutiere dies im Forum', CPC_TEXT_DOMAIN)."...</a>";
					}
					echo "</p>";
				echo "</div>";
				
				echo "<div id='__cpc__vote_thankyou'>";
					echo "<p>".__("Vielen Dank für Deine Stimmabgabe. Aktualisiere die Seite für die neuesten Ergebnisse", CPC_TEXT_DOMAIN);
					if ($__cpc__vote_forum != '') {
						echo "<br /><a href='".$__cpc__vote_forum."'>".__('Diskutiere dies im Forum', CPC_TEXT_DOMAIN)."...</a>";
					}
					echo "</p>";
				echo "</div>";
		
			}
			
		} else {
			
			echo "<p>".__("Melde Dich an, um abzustimmen...", CPC_TEXT_DOMAIN)."</p>";
			
		}
				
		// End content
		
		echo $after_widget;
		// End widget
	}
	
	// This updates the stored values
	function update( $new_instance, $old_instance ) {

		global $wpdb;

		$instance = $old_instance;

		// Reset
		if (strip_tags( $new_instance['cpcommunitie_reset_votes'] ) == 'on' ) {
			update_option( "cpcommunitie_vote_yes", 0 );
			update_option( "cpcommunitie_vote_no", 0 );
			$users = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->base_prefix."users", ''));
			foreach ($users as $user) {
				__cpc__update_meta($user->ID, 'widget_voted', '');
			}
		}
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['cpcommunitie_vote_question'] = strip_tags( $new_instance['cpcommunitie_vote_question'] );
		$instance['__cpc__vote_forum'] = strip_tags( $new_instance['__cpc__vote_forum'] );
		$instance['cpcommunitie_vote_counts'] = strip_tags( $new_instance['cpcommunitie_vote_counts'] );
		$instance['cpcommunitie_vote_type'] = strip_tags( $new_instance['cpcommunitie_vote_type'] );
		$instance['cpcommunitie_vote_key'] = strip_tags( $new_instance['cpcommunitie_vote_key'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'cpcommunitie_vote_question' => __('Eine Ja/Nein-Frage...', CPC_TEXT_DOMAIN), '__cpc__vote_forum' => '', 'cpcommunitie_vote_counts' => '', 'cpcommunitie_vote_type' => 'bar', 'cpcommunitie_vote_key' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults ); 

		$__cpc__vote_yes = get_option(CPC_OPTIONS_PREFIX."_vote_yes");
		$__cpc__vote_no = get_option(CPC_OPTIONS_PREFIX."_vote_no");

		echo "<p><span style='font-weight:bold'>".__('Bisherige Ergebnisse', CPC_TEXT_DOMAIN)."</span><br />";
		echo __("Yes", CPC_TEXT_DOMAIN).": ".$__cpc__vote_yes."<br />";
		echo __("No", CPC_TEXT_DOMAIN).": ".$__cpc__vote_no."</p>";
		?>		
		<p>
			<?php
			$msg = 'Die neueste kostenlose Version von JSChart hat ein Wasserzeichen eingeführt - das JSChart-Logo.<br />
			Um das Logo zu entfernen, musst Du eine Domänenlizenz von Jumpeye Components erwerben.<br />
			Nach dem Kauf erhältst Du einen Domänenschlüssel, der in etwa so aussieht: 5322c8a55773740e665f7ecb627d9373 (dies ist nur ein Beispiel).<br />
			Kopiere diesen Domainschlüssel und füge ihn unten ein. 
			Stelle sicher, dass die Domain, die Du mit Jumpeye Components festlegst, mit der Domain Deiner Webseite übereinstimmt!';
			?>
			<span class="__cpc__tooltip" title="<?php echo $msg ?>">?</span>
			<label 	for="<?php echo $this->get_field_id( 'cpcommunitie_vote_key' ); ?>"><?php echo __('Domänenschlüssel', CPC_TEXT_DOMAIN); ?>:
			<br /></label>
			<input 	id="<?php echo $this->get_field_id( 'cpcommunitie_vote_key' ); ?>" 
					name="<?php echo $this->get_field_name( 'cpcommunitie_vote_key' ); ?>" 
					value="<?php echo $instance['cpcommunitie_vote_key']; ?>" />
		<br /><br />
		<label 	for="<?php echo $this->get_field_id( 'cpcommunitie_vote_question' ); ?>"><?php echo __('Frage', CPC_TEXT_DOMAIN); ?>:<br /></label>
			<input 	id="<?php echo $this->get_field_id( 'cpcommunitie_vote_question' ); ?>" 
					name="<?php echo $this->get_field_name( 'cpcommunitie_vote_question' ); ?>" 
					value="<?php echo $instance['cpcommunitie_vote_question']; ?>" />
		<br /><br />
			<label 	for="<?php echo $this->get_field_id( '__cpc__vote_forum' ); ?>"><?php echo __('Forum-Link', CPC_TEXT_DOMAIN); ?>:<br /></label>
			<input 	id="<?php echo $this->get_field_id( '__cpc__vote_forum' ); ?>" 
					name="<?php echo $this->get_field_name( '__cpc__vote_forum' ); ?>" 
					value="<?php echo $instance['__cpc__vote_forum']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'cpcommunitie_vote_counts' ); ?>"><?php echo __('Ergebnisse anzeigen', CPC_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'cpcommunitie_vote_counts' ); ?>" name="<?php echo $this->get_field_name( 'cpcommunitie_vote_counts' ); ?>"
			<?php if ($instance['cpcommunitie_vote_counts'] == 'on') { echo " CHECKED"; } ?>
			/>
			<br /><em>(if not, percentages shown)</em>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'cpcommunitie_vote_type' ); ?>"><?php echo __('Diagramm Typ', CPC_TEXT_DOMAIN); ?>:</label>
			<select type="checkbox" id="<?php echo $this->get_field_id( 'cpcommunitie_vote_type' ); ?>" name="<?php echo $this->get_field_name( 'cpcommunitie_vote_type' ); ?>">
				<option value="pie" <?php if ($instance['cpcommunitie_vote_type'] == 'pie') { echo " SELECTED"; } ?> >Pie</option>
				<option value="bar" <?php if ($instance['cpcommunitie_vote_type'] == 'bar') { echo " SELECTED"; } ?> >Bar</option>
			</select>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'cpcommunitie_reset_votes' ); ?>"><?php echo __('Stimmen zurücksetzen?', CPC_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'cpcommunitie_reset_votes' ); ?>" name="<?php echo $this->get_field_name( 'cpcommunitie_reset_votes' ); ?>"
			 />
		</p>
		<?php
	}

}

?>
