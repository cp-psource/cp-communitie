<?php


	// Set dynamic styles

	global $wpdb;
	
	echo "<!-- ".CPC_WL." styles -->";
	echo "<style>";
	
	echo '.mceStatusbar { display:none !important; }';
	
	if (get_option(CPC_OPTIONS_PREFIX.'_hide_news_list') == 'on')
		echo '#__cpc__news_items { display:none !important; }';

	$wp_width = get_option(CPC_OPTIONS_PREFIX.'_wp_width');
	if ($wp_width == '') { $wp_width = '100pc'; }
	$wp_alignment = get_option(CPC_OPTIONS_PREFIX.'_wp_alignment');

	echo ".__cpc__wrapper {";
	if ($wp_alignment == 'Center') {
		echo "margin: 0 auto;";
	}
	if ($wp_alignment == 'Left' || $wp_alignment == 'Right') {
		echo "clear: both;";
		echo "margin: 0;";
		echo "float: ".strtolower($wp_alignment).";";
	}
	echo "  width: ".str_replace('pc', '%', $wp_width).";";
	echo "}";

	if (get_option(CPC_OPTIONS_PREFIX.'_use_styles') == "on") {
	
		$border_radius = get_option(CPC_OPTIONS_PREFIX.'_border_radius');
		$bigbutton_background = get_option(CPC_OPTIONS_PREFIX.'_bigbutton_background');
		$bigbutton_color = get_option(CPC_OPTIONS_PREFIX.'_bigbutton_color');
		$bigbutton_background_hover = get_option(CPC_OPTIONS_PREFIX.'_bigbutton_background_hover');
		$bigbutton_color_hover = get_option(CPC_OPTIONS_PREFIX.'_bigbutton_color_hover');
		$primary_color = get_option(CPC_OPTIONS_PREFIX.'_bg_color_1');
		$row_color = get_option(CPC_OPTIONS_PREFIX.'_bg_color_2');
		$row_color_alt = get_option(CPC_OPTIONS_PREFIX.'_bg_color_3');
		$text_color = get_option(CPC_OPTIONS_PREFIX.'_text_color');
		$text_color_2 = get_option(CPC_OPTIONS_PREFIX.'_text_color_2');
		$link = get_option(CPC_OPTIONS_PREFIX.'_link');
		$underline = get_option(CPC_OPTIONS_PREFIX.'_underline');
		$link_hover = get_option(CPC_OPTIONS_PREFIX.'_link_hover');
		$table_rollover = get_option(CPC_OPTIONS_PREFIX.'_table_rollover');
		$table_border = get_option(CPC_OPTIONS_PREFIX.'_table_border');
		$replies_border_size = get_option(CPC_OPTIONS_PREFIX.'_replies_border_size');
		$row_border_style = get_option(CPC_OPTIONS_PREFIX.'_row_border_style');
		$row_border_size = get_option(CPC_OPTIONS_PREFIX.'_row_border_size');
		$label = get_option(CPC_OPTIONS_PREFIX.'_label');
		$__cpc__categories_background = get_option(CPC_OPTIONS_PREFIX.'_categories_background');
		$categories_color = get_option(CPC_OPTIONS_PREFIX.'_categories_color');
		$main_background = get_option(CPC_OPTIONS_PREFIX.'_main_background');
		$closed_opacity = get_option(CPC_OPTIONS_PREFIX.'_closed_opacity');
		$fontfamily = stripslashes(get_option(CPC_OPTIONS_PREFIX.'_fontfamily'));
		$fontsize = get_option(CPC_OPTIONS_PREFIX.'_fontsize');
		$headingsfamily = stripslashes(get_option(CPC_OPTIONS_PREFIX.'_headingsfamily'));
		$headingssize = get_option(CPC_OPTIONS_PREFIX.'_headingssize');

		$style = "";

		$style .= ".__cpc__wrapper, 
					.__cpc__wrapper p, 
					.__cpc__wrapper li, 
					.__cpc__wrapper td, 
					.__cpc__wrapper div,
					.__cpc__wrapper input[type=text], 
					.__cpc__wrapper input[type=password], 
					.__cpc__wrapper textarea, 
					.popup, 
					.ui-widget,
					.ui-dialog,
					 .__cpc__mail_recipient_list_option
				    {".PHP_EOL;
		$style .= "	font-size: ".$fontsize."px;".PHP_EOL;
		$style .= "	color: ".$text_color.";".PHP_EOL;
		$style .= " text-shadow: none;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper div, .widget-area  {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily.";".PHP_EOL;
		$style .= "	color: ".$text_color.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= "#profile_menu div, #profile_header_panel div, #profile_body_wrapper div, .child-reply-post p, .topic-post-post p {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily." !important;".PHP_EOL;
		$style .= "	color: ".$text_color."!important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper, #mail_recipient_list, .__cpc__mail_recipient_list_option {".PHP_EOL;
		$style .= "	background-color: ".$main_background.";".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ". {".PHP_EOL;
		$style .= "	color: ".$text_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper a:link, .__cpc__wrapper a:visited, .__cpc__wrapper a:active,
					.widget-area a:link, .widget-area a:visited, .widget-area a:active
					{".PHP_EOL;
		$style .= "	color: ".$link." !important;".PHP_EOL;
		$style .= "	font-weight: normal !important;".PHP_EOL;
		if ($underline == "on") {
			$style .= "	text-decoration: underline !important;".PHP_EOL;
		} else {
			$style .= "	text-decoration: none !important;".PHP_EOL;
		}
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper a:hover {".PHP_EOL;
		$style .= "	color: ".$link_hover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= "body img, body input, .corners, .__cpc__wrapper .row, .__cpc__wrapper .reply_div, .__cpc__wrapper .row_odd, .__cpc__wrapper #starting-post, .__cpc__wrapper .child-reply, .__cpc__wrapper #profile_label {".PHP_EOL;
		$style .= "	border-radius: ".$border_radius."px !important;".PHP_EOL;
		$style .= "	-moz-border-radius: ".$border_radius."px !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper .label {".PHP_EOL;
		$style .= "  color: ".$label." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		// Profile 
		$style .= ".__cpc__wrapper #__cpc__profile_right_column, .popup {".PHP_EOL;
		$style .= "	background-color: ".$main_background." !important;".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color." !important;".PHP_EOL;	
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper #__cpc__comment, .__cpc__wrapper .__cpc__reply {".PHP_EOL;
		$style .= "	border: 1px solid ".$primary_color." ;".PHP_EOL;	
		$style .= "	border-radius: ".$border_radius."px;".PHP_EOL;	
		$style .= "}".PHP_EOL;
		
		// Forum or Tables (layout)

		$style .= ".__cpc__wrapper #__cpc__table {".PHP_EOL;
		$style .= "	border: ".$table_border."px solid ".$primary_color.";".PHP_EOL;	
		$style .= "}".PHP_EOL;
	
		$style .= ".__cpc__wrapper .table_header {".PHP_EOL;
		$style .= "	background-color: ".$__cpc__categories_background.";".PHP_EOL;
		$style .= "  font-weight: bold;".PHP_EOL;
	 	$style .= "  border-radius:0px;".PHP_EOL;
		$style .= "  -moz-border-radius:0px;".PHP_EOL;
		$style .= "  border: 0px".PHP_EOL;
	 	$style .= "  border-top-left-radius:".($border_radius-5)."px;".PHP_EOL;
		$style .= "  -moz-border-radius-topleft:".($border_radius-5)."px;".PHP_EOL;
	 	$style .= "  border-top-right-radius:".($border_radius-5)."px;".PHP_EOL;
		$style .= "  -moz-border-radius-topright:".($border_radius-5)."px;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper .table_topic, .__cpc__wrapper #profile_name, .__cpc__wrapper .topic-post-header {".PHP_EOL;
		$style .= "	font-family: ".$headingsfamily." !important;".PHP_EOL;
		$style .= "	font-size: ".$headingssize." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper .table_topic {".PHP_EOL;
		$style .= "	color: ".$categories_color.";".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper .table_topic:hover {".PHP_EOL;
		$style .= "	background-color: ".$table_rollover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper .row a, .__cpc__wrapper .row_odd a {".PHP_EOL;
		if ($underline == "on") {
			$style .= "	text-decoration: underline;".PHP_EOL;
		} else {
			$style .= "	text-decoration: none;".PHP_EOL;
		}
		$style .= "}".PHP_EOL;
	
		$style .= ".__cpc__wrapper .new-topic-subject-input, .__cpc__wrapper .input-field, .__cpc__wrapper #mail_recipient_list {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily.";".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color.";".PHP_EOL;	
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper .new-topic-subject-text, .__cpc__wrapper .reply-topic-subject-text, .__cpc__wrapper .reply-topic-text {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily.";".PHP_EOL;
		$style .= "}".PHP_EOL;
	
		$style .= ".__cpc__wrapper #reply-topic {".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color.";".PHP_EOL;	
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper #reply-topic-bottom textarea {".PHP_EOL;
		$style .= "	border: 1px solid ".$primary_color.";".PHP_EOL;			
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper #new-topic-link, .__cpc__wrapper #reply-topic-link, .__cpc__wrapper .__cpc__button,  .__cpc__button, .__cpc__wrapper .__cpc__btn, .__cpc__btn {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily." !important;".PHP_EOL;
		$style .= "	font-size: ".$fontsize."px !important;".PHP_EOL;
		$style .= "	background-color: ".$bigbutton_background." !important;".PHP_EOL;
		$style .= "	color: ".$bigbutton_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
	
		$style .= ".__cpc__wrapper #new-topic-link:hover, .__cpc__wrapper #reply-topic-link:hover, .__cpc__wrapper .__cpc__button:hover,  .__cpc__button:hover, .__cpc__wrapper .__cpc__btn:hover,  .__cpc__btn:hover {".PHP_EOL;
		$style .= "	background-color: ".$bigbutton_background_hover." !important;".PHP_EOL;
		$style .= "	color: ".$bigbutton_color_hover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= ".__cpc__wrapper #new-topic-link:active, .__cpc__wrapper #reply-topic-link:active, .__cpc__wrapper .__cpc__button:active,  .__cpc__button:active, .__cpc__wrapper .__cpc__btn:active,  .__cpc__btn:active {".PHP_EOL;
		$style .= "	background-color: ".$bigbutton_background_hover." !important;".PHP_EOL;
		$style .= "	color: ".$bigbutton_color_hover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
						
		$style .= ".__cpc__wrapper .round_bottom_left {".PHP_EOL;
	 	$style .= "  border-bottom-left-radius:".($border_radius-5)."px;".PHP_EOL;
		$style .= "  -moz-border-radius-bottomleft:".($border_radius-5)."px;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper .round_bottom_right {".PHP_EOL;
	 	$style .= "  border-bottom-right-radius:".($border_radius-5)."px;".PHP_EOL;
		$style .= "  -moz-border-radius-bottomright:".($border_radius-5)."px;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper .categories_color {".PHP_EOL;
		$style .= "	color: ".$categories_color.";".PHP_EOL;
		$style .= "}";
		$style .= ".__cpc__wrapper .__cpc__categories_background {".PHP_EOL;
		$style .= "	background-color: ".$__cpc__categories_background.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper .row, .__cpc__wrapper .reply_div {".PHP_EOL;
		$style .= "	background-color: ".$row_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper .wall_reply, .__cpc__wrapper .__cpc__wall_reply_div, .__cpc__wrapper .wall_reply_avatar, .__cpc__wrapper a, ";
		$style .= ".__cpc__wrapper .mailbox_message_subject, .__cpc__wrapper .mailbox_message_from, .__cpc__wrapper .mail_item_age, .__cpc__wrapper .mailbox_message, ";
		$style .= ".__cpc__wrapper .row_views ";
		$style .= " {".PHP_EOL;
		$style .= "	background-color: transparent;".PHP_EOL;
		$style .= "}".PHP_EOL;
			
			
		$style .= ".__cpc__wrapper .row_odd {".PHP_EOL;
		$style .= "	background-color: ".$row_color_alt." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
	
		$style .= ".__cpc__wrapper .row:hover, .__cpc__wrapper .row_odd:hover {".PHP_EOL;
		$style .= "	background-color: ".$table_rollover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper .row_link, .__cpc__wrapper .edit, .__cpc__wrapper .delete {".PHP_EOL;
		$style .= "	font-size: ".$headingssize." !important;".PHP_EOL;
		$style .= "	color: ".$link." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
			
		$style .= ".__cpc__wrapper .row_link:hover {".PHP_EOL;
		$style .= "	color: ".$link_hover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
	
		$style .= ".__cpc__wrapper #starting-post {".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color.";".PHP_EOL;
		$style .= "	background-color: ".$row_color.";".PHP_EOL;
		$style .= "}".PHP_EOL;
							
		$style .= ".__cpc__wrapper #starting-post, .__cpc__wrapper #child-posts {".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper .child-reply {".PHP_EOL;
		$style .= "	border-bottom: ".$replies_border_size."px dotted ".$text_color_2.";".PHP_EOL;
		$style .= "	background-color: ".$row_color_alt.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__cpc__wrapper .sep, .__cpc__wrapper .sep_top {".PHP_EOL;
		$style .= "	clear:both;".PHP_EOL;
		$style .= "	width:100%;".PHP_EOL;
		$style .= "	border-bottom: ".$replies_border_size."px ".$row_border_style." ".$text_color_2.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= ".__cpc__wrapper .sep_top {".PHP_EOL;
		$style .= "	border-bottom: 0px ;".PHP_EOL;
		$style .= "	border-top: ".$replies_border_size."px ".$row_border_style." ".$text_color_2.";".PHP_EOL;
		$style .= "}".PHP_EOL;
			
		// Alerts
		
		$style .= ".__cpc__wrapper .alert {".PHP_EOL;
		$style .= "	clear:both;".PHP_EOL;
		$style .= "	padding:6px;".PHP_EOL;
		$style .= "	margin-bottom:15px;".PHP_EOL;
		$style .= "	border: 1px solid #666;".PHP_EOL;	
		$style .= "	background-color: #eee;".PHP_EOL;
		$style .= "	color: #000;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__cpc__wrapper .transparent {".PHP_EOL;
		$style .= '  -ms-filter: "progid: DXImageTransform.Microsoft.Alpha(Opacity='.($closed_opacity*100).')";'.PHP_EOL;
		$style .= "  filter: alpha(opacity=".($closed_opacity*100).");".PHP_EOL;
		$style .= "  -moz-opacity: ".$closed_opacity.";".PHP_EOL;
		$style .= "  -khtml-opacity: ".$closed_opacity.";".PHP_EOL;
		$style .= "  opacity: ".$closed_opacity.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		// Menu Tabs
		$style .= "ul.__cpc__dropdown li {".PHP_EOL;
		$style .= "  border-color: ".$text_color." !important;".PHP_EOL;
		$style .= "  color: ".$text_color." !important;".PHP_EOL;		
		$style .= "}".PHP_EOL;
		$style .= "ul.__cpc__dropdown ul {".PHP_EOL;
		$style .= "  background-color: ".$row_color." !important;".PHP_EOL;		
		$style .= "}".PHP_EOL;
		$style .= "ul.__cpc__dropdown ul li {".PHP_EOL;
		$style .= "  background-color: ".$row_color." !important;".PHP_EOL;		
		$style .= "  color: ".$text_color." !important;".PHP_EOL;		
		$style .= "}".PHP_EOL;
		$style .= "ul.__cpc__dropdown li.__cpc__dropdown_tab_on  {".PHP_EOL;
		$style .= "  border-bottom-color: ".$main_background." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= "ul.__cpc__dropdown li.__cpc__dropdown_tab_off  {".PHP_EOL;
		$style .= "  border-bottom-color: ".$text_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= "#__cpc__menu_tabs_wrapper {".PHP_EOL;
		$style .= "  border-top:1px solid ".$text_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		
					
		echo $style;
				
	}

	// Apply advanced CSS (via WP Admin Menu -> Styles -> CSS)	
	if (get_option(CPC_OPTIONS_PREFIX.'_css') != '') {
		echo "/* ".CPC_WL." custom styles */";
		echo str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_css')));
	}

	// Apply responsive CSS (via WP Admin Menu -> Styles -> Responsive)	
	if (get_option(CPC_OPTIONS_PREFIX.'_responsive') != '') {
		echo "/* ".CPC_WL." responsive styles */";
		echo str_replace("[]", chr(13), stripslashes(get_option(CPC_OPTIONS_PREFIX.'_responsive')));
	}



	echo "</style>";
	echo "<!-- End ".CPC_WL." styles -->";

?>
