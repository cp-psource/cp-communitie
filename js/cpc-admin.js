	/*
	   +------------------------------------------------------------------------------------------+
	   |                                       FUNCTIONS USED                                     |
	   +------------------------------------------------------------------------------------------+
	*/
	
this.tooltip = function(){	
		xOffset = 10;
		yOffset = 20;		
	jQuery(".__cpc__tooltip").hover(function(e){											  
		this.t = this.title;
		this.title = "";									  
		jQuery("body").append("<p id='__cpc__tooltip' style='position:absolute; border:1px solid #333;border-radius:3px;background:#f7f5d1;padding:2px 5px;color:#333;display:none;' >"+ this.t +"</p>");
	  jQuery("#__cpc__tooltip") 
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px")
			.fadeIn("fast");		
    },
	function(){
		this.title = this.t;		
		jQuery("#__cpc__tooltip").remove();
    });	
	jQuery(".__cpc__tooltip").mousemove(function(e){
		jQuery("#__cpc__tooltip")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	});			
};
	
  /*
	   +------------------------------------------------------------------------------------------+
	   |                                          ADMIN                                           |
	   +------------------------------------------------------------------------------------------+
	*/

jQuery(document).ready(function() {

	tooltip();

	if (jQuery("#__cpc__areyousure").length) {
		var areyousure = jQuery("#__cpc__areyousure").html();
	} else {
		var areyousure = 'Bist du dir sicher?';
	}

	// Check if you are sure
	jQuery(".__cpc__are_you_sure").on('click', function() {
		var answer = confirm(areyousure);
		return answer // answer is a boolean
	});

	// Reset profile page menus
	jQuery('#__cpc__reset_profile_menu').on('click', function(e) {
		var default_menu_structure = '[Profil]\nProfil anzeigen=viewprofile\nProfildetails=details\nCommunity-Einstellungen=settings\nAvatar hochladen=avatar\n[Aktivität]\nMeine Aktivität=activitymy\nFreunde-Aktivität=activityfriends\nAlle Aktivitäten=activityall\n[Social%f]\nMeine Freunde=myfriends\nMeine Gruppen=mygroups\nDie Lounge=lounge\nMeine @Erwähnungen=mentions\nWem ich folge=following\nMeine Abonnenten=followers\n[Mehr]\nMeine Events=events\nMeine Gallerie=gallery';
		jQuery('#profile_menu_structure').val(default_menu_structure);
	})
	jQuery('#__cpc__reset_profile_menu_other').on('click', function(e) {
		var default_menu_structure_other = '[Profil]\nProfil anzeigen=viewprofile\nProfildetails=details\nCommunity-Einstellungen=settings\nAvatar hochladen=avatar\n[Aktivität]\nAktivität=activitymy\nFreunde-Aktivität=activityfriends\nAlle Aktivitäten=activityall\n[Social]\nFreunde=myfriends\nGruppen=mygroups\nDie Lounge=lounge\n@Erwähnungen=mentions\nIch folge=following\nMir folgen=followers\n[Mehr]\nEvents=events\nGalerie=gallery';
		jQuery('#profile_menu_structure_other').val(default_menu_structure_other);
	})
	
	// Reset group page menus
	jQuery('#__cpc__reset_group_menu').on('click', function(e) {
		var default_menu_structure = '[Gruppe]\nWillkommen=welcome\nEinstellungen=settings\nEinladen=invites\n[Aktivität]\nGruppenaktivität=activity\nGruppenforum=forum\n[Mitglieder]\nMitglieder=members';
		jQuery('#group_menu_structure').val(default_menu_structure);
	})
	

	// Notice on settings page
	
	jQuery('#cpcommunitie_long_menu').on('click', function(e) {
		alert('Besuche nach dem Speichern bitte das Dashboard, um das neue Menü anzuzeigen.');
	});
	
	// Colorpicker
	jQuery('.colorpicker').hide();
	if (jQuery(".cpc_pickColor").length) {
		jQuery('.cpc_pickColor').each(function(i, obj) {

			var bg = jQuery(this).val();
			if (bg.length == 7) {
				var r = parseInt(bg.substr(1, 2), 16);
				var g = parseInt(bg.substr(3, 2), 16);
				var b = parseInt(bg.substr(5, 2), 16);
				var yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
				if (yiq < 128) {
					jQuery(this).css('color', '#fff');
				} else {
					jQuery(this).css('color', '#000');
				}
			}
			if (bg.length == 4) {
				var r = parseInt(bg.substr(1, 1), 16);
				var g = parseInt(bg.substr(2, 1), 16);
				var b = parseInt(bg.substr(3, 1), 16);
				var yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
				if (yiq < 128) {
					jQuery(this).css('color', '#fff');
				} else {
					jQuery(this).css('color', '#000');
				}
			}
			jQuery(this).css('background-color', jQuery(this).val());

		});
	}
	jQuery('.cpc_pickColor').on('click', function(e) {
		var colorPicker = jQuery(this).next('div');
		var input = jQuery(this);
		jQuery(colorPicker).farbtastic(input);
		colorPicker.show();
		e.preventDefault();
		jQuery(document).mousedown(function() {
			jQuery(colorPicker).hide();
		});
	});

	// Deactivate debug mode
	jQuery("#cpcommunitie_deactivate_debug").on('click', function() {
		jQuery.ajax({
			url: __cpc__.plugin_url + "ajax/ajax_functions.php",
			type: "POST",
			data: ({
				action: "deactivate_debug"
			}),
			dataType: "html",
			async: false,
			success: function(str) {
				if ((str) != '') {
					jQuery("#dialog").html(str).dialog({
						title: __cpc__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					location.reload();
				}
			}
		});
	})

	// Show mail message content (Mail Messages admin menu)
	jQuery(".show_full_message").on('click', function() {
		var mail_mid = jQuery(this).attr("id");
		jQuery("#cpcommunitie_dialog").html('Bitte warten, Nachricht wird abgerufen...').dialog({
			'dialogClass': 'wp-dialog'
		});

		jQuery.ajax({
			url: __cpc__.plugin_url + "ajax/ajax_functions.php",
			type: "POST",
			data: ({
				action: "get_mail_message",
				mail_mid: mail_mid
			}),
			dataType: "html",
			async: false,
			success: function(str) {
				jQuery("#cpcommunitie_dialog").html(str).dialog({
					'dialogClass': 'wp-dialog'
				});
			}
		});

		jQuery('.mail_message_dialog').dialog({
			bgiframe: true,
			height: 400,
			width: 600,
			modal: true,
			overlay: {
				backgroundColor: '#000',
				opacity: 0.5
			},
			title: 'Mail Message'
		});


	});

	// Reset Editor Toolbars
	jQuery("#use_wysiwyg_reset").on('click', function() {
		if (confirm(areyousure)) {
			jQuery("#use_wysiwyg_1").val('bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect');
			jQuery("#use_wysiwyg_2").val('cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,youtubeIframe,|,forecolor,backcolor');
			jQuery("#use_wysiwyg_3").val('hr,removeformat,|,sub,sup,|,charmap,emotions,media,advhr,|,ltr,rtl,|,search,replace,|,code');
			jQuery("#use_wysiwyg_4").val('tablecontrols');
		}
	});
	jQuery("#use_wysiwyg_reset_min").on('click', function() {
		if (confirm(areyousure)) {
			jQuery("#use_wysiwyg_1").val('bold,italic,|,fontselect,fontsizeselect,forecolor,backcolor,|,bullist,numlist,|,link,unlink,|,youtubeIframe,|,emotions');
			jQuery("#use_wysiwyg_2").val('');
			jQuery("#use_wysiwyg_3").val('');
			jQuery("#use_wysiwyg_4").val('');
		}
	});

	// Forum categories (check/uncheck all roles)
	jQuery(".cpcommunitie_cats_check").on('click', function() {
		var forum_cat = jQuery(this).attr("title");
		if (jQuery(".cpc_forum_cat_" + forum_cat).prop("checked")) {
			jQuery(".cpc_forum_cat_" + forum_cat).each(function(index) {
				jQuery(this).prop("checked", false);
			});
		} else {
			jQuery(".cpc_forum_cat_" + forum_cat).each(function(index) {
				jQuery(this).prop("checked", true);
			});
		}

	});

	// Styles (clear save as name if loading stored style)
	jQuery("#style_save_as_button").on('click', function() {
		jQuery("#style_save_as").val('');
	});

	// Installation Page (Add to new)
	jQuery(".cpcommunitie_addnewpage").on('click', function() {
		var shortcode = jQuery(this).attr("title");
		var name = jQuery(this).attr("id");
		jQuery(this).attr('value', 'Working...').attr("disabled", true);
		jQuery.ajax({
			url: __cpc__.plugin_url + "ajax/ajax_functions.php",
			type: "POST",
			data: ({
				action: "add_new_page",
				shortcode: shortcode,
				name: name
			}),
			dataType: "html",
			async: false,
			success: function(str) {
				location.reload();
			}
		});
	});

	// Installation Page (Add to existing)
	jQuery(".cpcommunitie_addtopage").on('click', function() {
		var shortcode = jQuery(this).attr("title");
		var value = jQuery('#cpcommunitie_pagechoice_' + shortcode).val();

		jQuery(this).attr('value', 'Working...').attr("disabled", true);
		jQuery.ajax({
			url: __cpc__.plugin_url + "ajax/ajax_functions.php",
			type: "POST",
			data: ({
				action: "add_to_page",
				shortcode: shortcode,
				id: value
			}),
			dataType: "html",
			async: false,
			success: function(str) {
				if (str != 'OK') {
					alert('Problem adding to page, please add manually (' + str + ')');
				} else {
					location.reload();
				}
			}
		});
	});

	// Show moderation post in full
	if (jQuery(".show_full_post").length) {
		jQuery(".show_full_post").on('click', function() {
			alert(jQuery(this).attr("title"));
		});
	}

	// Hide DIVs after showing for 1.5 seconds
	jQuery(".slideaway").delay(1500).slideUp("slow");

	if (jQuery("#jstest").length) {
		jQuery("#jstest").hide();
	}

	// Hidden column on installation page
	jQuery(".cpcommunitie_url").hide();
	jQuery("#cpcommunitie_url").on('click', function() {
		jQuery(".cpcommunitie_url").toggle();
	});

	// Import/Export Templates
	jQuery("#cpcommunitie_import_templates").on('click', function() {
		jQuery("#cpcommunitie_import_templates_form").show();
		jQuery("#cpcommunitie_templates_values").hide();
	});

	jQuery("#cpcommunitie_export_templates").on('click', function() {
		jQuery("#cpcommunitie_export_templates_form").show();
		jQuery("#cpcommunitie_templates_values").hide();
	});

	jQuery(".cpcommunitie_templates_cancel").on('click', function() {
		jQuery("#cpcommunitie_import_templates_form").hide();
		jQuery("#cpcommunitie_export_templates_form").hide();
		jQuery("#cpcommunitie_templates_values").show();
	});
	jQuery("#cpcommunitie_import_file_button").on('click', function() {
		if (confirm(areyousure)) {
			var import_file = jQuery("#cpcommunitie_import_file").val();
			jQuery('#cpcommunitie_import_file_pleasewait').show().html("<img src='" + __cpc__.images_url + "/busy.gif' />");
			jQuery.ajax({
				url: __cpc__.plugin_url + "ajax/ajax_functions.php",
				type: "POST",
				data: ({
					action: "import_template_file",
					import_file: import_file
				}),
				dataType: "html",
				async: false,
				success: function(str) {
					if (str != 'OK') {
						alert('Problem importing, please check format of import file: '+str);
					}
					location.reload();
				}
			});
		}
	});


	// Templates
	jQuery("#reset_profile_header").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div id='profile_header_div'>[]<div id='profile_label'>[profile_label]</div>[]<div id='profile_header_panel'>[]<div id='profile_photo' class='corners'>[avatar,200]</div>[]<div id='profile_details'>[]<div id='profile_name'>[display_name]</div>[]<p>[location]<br />[born]</p>[]</div>[]</div>[]</div>[]<div id='profile_actions_div'>[actions][poke][follow]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#profile_header_textarea").val(reset);
		}
	});
	jQuery("#reset_profile_body").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div id='profile_wrapper'>[]<div id='force_profile_page' style='display:none'>[default]</div>[]<div id='profile_body_wrapper'>[]<div id='profile_body'>[page]</div>[]</div>[]<div id='profile_menu'>[menu]</div>[]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#profile_body_textarea").val(reset);
		}
	});
	jQuery("#reset_profile_body_tabs").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div id='force_profile_page' style='display:none'>[default]</div>[]<div id='profile_body_tabs_wrapper'>[][menu_tabs][]<div id='profile_body' class='profile_body_no_menu'>[page]</div>[]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#profile_body_textarea").val(reset);
		}
	});
	jQuery("#reset_page_footer").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div id='powered_by_cpc'>[]<a href='https://cp-community.n3rds.work/' target='_blank'>[powered_by_message] v[version]</a>[]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#page_footer_textarea").val(reset);
		}
	});
	jQuery("#reset_mail_tray").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div class='bulk_actions'>[bulk_action]</div>[]<div id='mail_mid' class='mail_item mail_read'>[]<div class='mailbox_message_from'>[mail_from]</div>[]<div class='mail_item_age'>[mail_sent]</div>[]<div class='mailbox_message_subject'>[mail_subject]</div>[]<div class='mailbox_message'>[mail_message]</div>[]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#template_mail_tray_textarea").val(reset);
		}
	});
	jQuery("#reset_mail_message").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div id='message_header'><div id='message_header_delete'>[reply_button][delete_button]</div><div id='message_header_avatar'>[avatar,44]</div>[mail_subject]<br />[mail_recipient] [mail_sent]</div><div id='message_mail_message'>[message]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#template_mail_message_textarea").val(reset);
		}
	});
	jQuery("#reset_email").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<style> body { background-color: #fff; } </style>[]<div style='margin: 20px;'>[][message][]<br /><hr />[][footer]<br />[]<a href='https://cp-community.n3rds.work/' target='_blank'>[powered_by_message] v[version]</a>[]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#email_textarea").val(reset);
		}
	});
	jQuery("#reset_forum_header").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "[breadcrumbs][new_topic_button][new_topic_form][][digest][subscribe][][forum_options][][sharing]";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#template_forum_header_textarea").val(reset);
		}
	});
	jQuery("#reset_group").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div id='group_header_div'><div id='group_header_panel'>[]<div id='group_details'>[]<div id='group_name'>[group_name]</div>[]<div id='group_description'>[group_description]</div>[]<div style='padding-top: 15px;padding-bottom: 15px;'>[actions]</div>[]</div></div>[]<div id='group_photo' class='corners'>[avatar,200]</div>[]</div>[]<div id='group_wrapper'>[]<div id='force_group_page' style='display:none'>[default]</div>[]<div id='group_body_wrapper'>[]<div id='group_body'>[page]</div>[]</div>[]<div id='group_menu'>[menu]</div>[]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#template_group_textarea").val(reset);
		}
	});
	jQuery("#reset_group_tabs").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div id='group_header_div'><div id='group_header_panel'>[]<div id='group_details'>[]<div id='group_name'>[group_name]</div>[]<div id='group_description'>[group_description]</div>[]<div style='padding-top: 15px;padding-bottom: 15px;'>[actions]</div>[]</div>[]</div>[]<div id='group_photo' class='corners'>[avatar,170]</div>[]</div>[]<div id='group_wrapper'>[]<div id='force_group_page' style='display:none'>[default]</div>[]<div id='group_body_wrapper'>[][menu_tabs][]<div id='group_body' class='group_body_full'>[page]</div>[]</div>[]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#template_group_textarea").val(reset);
		}
	});	
	jQuery("#reset_template_forum_category").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div class='row_topic'>[category_title]<br />[category_desc]</div>[]<div class='row_startedby'>[]<div class='row_views'>[post_count]</div>[]<div class='row_topic row_replies'>[topic_count]</div>[]<div class='avatar avatar_last_topic'>[avatar,64]</div>[]<div class='last_topic_text'>[replied][subject][ago]<br />[subject_text]</div>[]</div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#template_forum_category_textarea").val(reset);
		}
	});
	jQuery("#reset_template_forum_topic").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div class='avatar avatar_first_topic'>[avatarfirst,64]</div>[]<div class='first_topic'>[]<div class='row_views'>[views]</div>[]<div class='row_replies'>[replies]</div>[]<div class='row_topic'>[topic_title]</div>[]<div class='first_topic_text'>[startedby][started]</div>[]<div class='row_startedby'>[]<div class='last_reply'>[]<div class='avatar avatar_last_topic'>[avatar,48]</div>[]<div class='last_topic_text'>[replied][ago].<br />[topic]</div>[]</div>[]</div></div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#template_forum_topic_textarea").val(reset);
		}
	});
	jQuery("#reset_template_group_forum_topic").on('click', function() {
		if (confirm(areyousure)) {
			var reset = "<div class='avatar avatar_first_topic'>[avatarfirst,64]</div>[]<div class='first_topic'>[]<div class='row_views'>[views]</div>[]<div class='row_replies'>[replies]</div>[]<div class='row_topic'>[topic_title]</div>[]<div class='first_topic_text'>[startedby][started]</div>[]<div class='row_startedby'>[]<div class='last_reply'>[]<div class='avatar avatar_last_topic'>[avatar,48]</div>[]<div class='last_topic_text'>[replied][ago].<br />[topic]</div>[]</div>[]</div></div>";
			reset = reset.replace(/\[\]/g, String.fromCharCode(13));
			jQuery("#template_group_forum_topic_textarea").val(reset);
		}
	});

	// Test AJAX
	jQuery("#testAJAX").on('click', function() {
		random = Math.floor(Math.random() * 10) + 1;
		alert("The random number being sent is " + random);

		jQuery.ajax({
			url: __cpc__.plugin_url + "ajax/menu_functions.php",
			type: "POST",
			data: ({
				action: "cpcommunitie_test",
				postID: random
			}),
			dataType: "html",
			async: false,
			success: function(str_test) {
				jQuery("#testAJAX_results").val('Value of ' + str_test + ' returned!');
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

	});

    /*
	   +------------------------------------------------------------------------------------------+
	   |                                       GROUP ADMIN                                        |
	   +------------------------------------------------------------------------------------------+
	*/
	
	// Search for members
	jQuery("#user_list_search_button").on('click', function() {

		var gid = jQuery('#group_list').val();

		if (gid == 0) {

			jQuery("#dialog").html('Please select a group');
			jQuery("#dialog").dialog({
				title: __cpc__.site_title,
				width: 400,
				height: 220,
				modal: true,
				buttons: {
					"OK": function() {
						jQuery(this).dialog("close");
					}
				}
			});

		} else {

			jQuery('#user_list').html("<img src='" + __cpc__.images_url + "/busy.gif' />");

			var term = jQuery("#user_list_search").val();

			jQuery.ajax({
				url: __cpc__.plugin_url + "ajax/groups_functions.php",
				type: "POST",
				data: ({
					action: "get_user_list",
					term: term,
					gid: gid
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					jQuery('#user_list').html(str);
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});
		}

	});

	// Select new group	
	jQuery('#group_list').on('change', function() {

		jQuery("#group_list_delete").show();
		jQuery('#group_order_update').show();

		jQuery('#user_list').html('');
		jQuery('#selected_users').html('');

		var gid = jQuery(this).val();
		if (gid > 0) {

			jQuery('#selected_users').html("<img src='" + __cpc__.images_url + "/busy.gif' />");

			jQuery.ajax({
				url: __cpc__.plugin_url + "ajax/groups_functions.php",
				type: "POST",
				data: ({
					action: "get_group_members",
					gid: gid
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					jQuery('#selected_users').html(str);
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});

		}

	});

	// Delete a group
	jQuery('#group_list_delete').on('click', function() {
		var answer = confirm("This cannot be un-done - are you really sure?");

		if (answer) {

			jQuery.ajax({
				url: __cpc__.plugin_url + "ajax/group_functions.php",
				type: "POST",
				data: ({
					action: "deleteGroup",
					gid: jQuery('#group_list').val()
				}),
				dataType: "html",
				async: false,
				success: function(str) {
					window.location.href = 'admin.php?page=cp-communitie/groups_admin.php';
				}
			});

		}
	});

	// Change group's order
	jQuery('#group_order_update').on('click', function() {
		var answer = prompt("Enter order number (lower shown first)");

		if (answer) {

			jQuery.ajax({
				url: __cpc__.plugin_url + "ajax/group_functions.php",
				type: "POST",
				data: ({
					action: "changeGroupOrder",
					order: answer,
					gid: jQuery('#group_list').val()
				}),
				dataType: "html",
				async: false,
				success: function(str) {
					window.location.href = 'admin.php?page=cp-communitie/groups_admin.php';
				}
			});

		}
	});

	
	// Add or remove a user to/from a group selection
	jQuery(".user_list_item").on('click', function() {

		var id = jQuery(this).attr("id");
		var parent_id = jQuery(this).parent().attr("id");
		if (parent_id == 'user_list') {

			// Add a user to the selected list
			jQuery(this).clone().appendTo('#selected_users');
			jQuery(this).remove();
			var html = jQuery('#selected_users #' + id).html();
			html = html.replace('add', 'cross');
			jQuery('#selected_users #' + id).html(html);

		} else {
			// Remove a user to the selected list
			jQuery(this).clone().appendTo('#user_list');
			jQuery(this).remove();
			var html = jQuery('#user_list #' + id).html();
			html = html.replace('cross', 'add');
			jQuery('#user_list #' + id).html(html);

		}

	});

	// Add button
	jQuery("#users_add_button").on('click', function() {

		if (jQuery('#group_list').val() > 0) {

			var id = '';
			jQuery('#selected_users').children('div').each(function() {
				id += jQuery(this).attr('id') + ',';
			});

			jQuery('#selected_users').html("<img src='" + __cpc__.images_url + "/busy.gif' /> Please wait...");

			jQuery.ajax({
				url: __cpc__.plugin_url + "ajax/groups_functions.php",
				type: "POST",
				data: ({
					action: "add_group_members",
					gid: jQuery('#group_list').val(),
					ids: id
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					if (str != '') {
						alert(str);
					} else {
						location.reload();
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});

		}

		return void(0);
	});


	
});
