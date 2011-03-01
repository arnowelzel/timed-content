tinyMCEPopup.requireLangPack();

var TimedContentDialog = {
	init : function() {
		jQuery('#do_client_show').click(
			 function()  {
				 if (jQuery(this).is(':checked'))  {
					 jQuery('#client_show_minutes').removeAttr('disabled');
					 jQuery('#client_show_seconds').removeAttr('disabled');
					 jQuery('#client_show_fade').removeAttr('disabled');
				 }  else  {
					 jQuery('#client_show_minutes').attr('disabled', 'disabled');
					 jQuery('#client_show_seconds').attr('disabled', 'disabled');
					 jQuery('#client_show_fade').attr('disabled', 'disabled');
				 }
			 }
		);
		
		jQuery('#do_client_hide').click(
			 function()  {
				 if (jQuery(this).attr('checked'))  {
					 jQuery('#client_hide_minutes').removeAttr('disabled');
					 jQuery('#client_hide_seconds').removeAttr('disabled');
					 jQuery('#client_hide_fade').removeAttr('disabled');
				 }  else  {
					 jQuery('#client_hide_minutes').attr('disabled', 'disabled');
					 jQuery('#client_hide_seconds').attr('disabled', 'disabled');
					 jQuery('#client_hide_fade').attr('disabled', 'disabled');
				 }
			 }
		);

		var today = new Date();

		jQuery('#do_server_show').click(
			 function()  {
				 if (jQuery(this).is(':checked'))  {
					jQuery('#server_show_dt').removeAttr('disabled');
					jQuery('#server_show_dt').AnyTime_picker(
						{
							earliest: today,
							baseYear: today.getUTCFullYear(),
							format: "%Y-%b-%d %T %+",
							formatUtcOffset: "%+ (%@)"
						}
					);
				 }  else  {
					jQuery('#server_show_dt').AnyTime_noPicker();
					jQuery('#server_show_dt').attr('disabled', 'disabled');
				 }
			 }
		);
		
		jQuery('#do_server_hide').click(
			 function()  {
				 if (jQuery(this).attr('checked'))  {
					jQuery('#server_hide_dt').removeAttr('disabled');
					jQuery('#server_hide_dt').AnyTime_picker(
						{
							earliest: today,
							baseYear: today.getUTCFullYear(),
							format: "%Y-%b-%d %T %+",
							formatUtcOffset: "%+ (%@)"
						}
					);
				 }  else  {
					jQuery('#server_hide_dt').AnyTime_noPicker();
					jQuery('#server_hide_dt').attr('disabled', 'disabled');
				 }
			 }
		);
		
	},

	client_action : function() {
		// Get settings from the dialog and build the arguments for the shortcode
		var show_args = "";
		var hide_args = "";

		var sm = Math.abs(parseInt(jQuery('#client_show_minutes').val()));
		var ss = Math.abs(parseInt(jQuery('#client_show_seconds').val()));
		var sf = Math.abs(parseInt(jQuery('#client_show_fade').val()));
		if (isNaN(sm)) sm = 0; if (isNaN(ss)) ss = 0; if (isNaN(sf)) sf = 0; 

		var hm = Math.abs(parseInt(jQuery('#client_hide_minutes').val()));
		var hs = Math.abs(parseInt(jQuery('#client_hide_seconds').val()));
		var hf = Math.abs(parseInt(jQuery('#client_hide_fade').val()));
		if (isNaN(hm)) hm = 0; if (isNaN(hs)) hs = 0; if (isNaN(hf)) hf = 0; 

		st = (sm * 60) + ss;  ht = (hm * 60) + hs;
		
		if (jQuery('#do_client_show').attr('checked')) { 
			if (st > 0)
				show_args = " show=\"" + sm + ":" + ss + ":" + sf + "\"";
			else  {
				tinyMCEPopup.alert('When using the Show action, the Show time must be at least 1 second.');
				return;
			}
		}
		if (jQuery('#do_client_hide').attr('checked')) {
			if (ht > 0)
				hide_args = " hide=\"" + hm + ":" + hs + ":" + hf + "\"";
			else  {
				tinyMCEPopup.alert('When using the Hide action, the Hide time must be at least 1 second.');
				return;
			}
		}
		
		if (jQuery('#do_client_show').attr('checked') && jQuery('#do_client_hide').attr('checked') && (st >= ht)) {
			tinyMCEPopup.alert('When using both Show and Hide actions, the Hide time must be later than the Show time.');
			return;
		}
		
		if (!(jQuery('#do_client_show').attr('checked') || jQuery('#do_client_hide').attr('checked'))) {
			tinyMCEPopup.alert('Please select an action to perform.');
			return;
		}
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceReplaceContent', false, '[timed-content-client' + show_args + hide_args + ']{$selection}[/timed-content-client]');
		tinyMCEPopup.close();
	},

	server_action : function() {
		// Get settings from the dialog and build the arguments for the shortcode
		var show_args = "";
		var hide_args = "";
		var debug_args = "";

		var sdt = jQuery('#server_show_dt').val();
		var hdt = jQuery('#server_hide_dt').val();
		var s_Date = new Date(sdt); 
		var h_Date = new Date(hdt); 

		if (jQuery('#do_server_show').attr('checked')) { 
			show_args = " show=\"" + sdt + "\"";
		}
		if (jQuery('#do_server_hide').attr('checked')) {
			hide_args = " hide=\"" + hdt + "\"";
		}
		if (jQuery('#server_debug').attr('checked')) {
			debug_args = " debug=\"true\"";
		}
		
		if (jQuery('#do_server_show').attr('checked') && jQuery('#do_server_hide').attr('checked') && (s_Date >= h_Date)) {
			tinyMCEPopup.alert('When using both Show and Hide actions, the Hide time must be later than the Show time.');
			return;
		}
		
		if (!(jQuery('#do_server_show').attr('checked') || jQuery('#do_server_hide').attr('checked'))) {
			tinyMCEPopup.alert('Please select an action to perform.');
			return;
		}
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceReplaceContent', false, '[timed-content-server' + show_args + hide_args + debug_args + ']{$selection}[/timed-content-server]');
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(TimedContentDialog.init, TimedContentDialog);
