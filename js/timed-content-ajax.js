jQuery(document).ready( function() {

    function getArgs()  {
        var args = {};

        args.fsel = jQuery( 'select#timed_content_rule_frequency option:selected' ).val();
        args.tz = jQuery( 'select#timed_content_rule_timezone option:selected' ).val();
        args.nr = jQuery( 'input#timed_content_rule_recurrence_duration_num_repeat' ).val();
        args.ed = jQuery( 'input#timed_content_rule_recurrence_duration_end_date' ).val();
        if ( jQuery( "#timed_content_rule_monthly_nth_weekday_of_month" ).prop( "checked" ) ) {
            args.nth = "yes";
        } else {
            args.nth = "no";
        }
        args.ntho = jQuery( 'select#timed_content_rule_monthly_nth_weekday_of_month_nth option:selected' ).val();
        args.nthw = jQuery( 'select#timed_content_rule_monthly_nth_weekday_of_month_weekday option:selected' ).val();

        args.im = 1;
        if ( 0 === args.fsel ) { args.im = jQuery( 'input#timed_content_rule_hourly_num_of_hours' ).val(); }
        if ( 1 == args.fsel ) { args.im = jQuery( 'input#timed_content_rule_daily_num_of_days' ).val(); }
        if ( 2 == args.fsel ) { args.im = jQuery( 'input#timed_content_rule_weekly_num_of_weeks' ).val(); }
        if ( 3 == args.fsel ) { args.im = jQuery( 'input#timed_content_rule_monthly_num_of_months' ).val(); }
        if ( 4 == args.fsel ) { args.im = jQuery( 'input#timed_content_rule_yearly_num_of_years' ).val(); }
        args.days = {};
        var days_num = 0;
        jQuery( 'input[id^="timed_content_rule_weekly_days_of_week_to_repeat"]' ).each( function() {
            if ( jQuery( this ).prop( "checked" ) ) {
                args.days[days_num] = jQuery( this ).val();
            }
            days_num++;
        });
        args.start = { "date": jQuery( 'input#timed_content_rule_instance_start_date' ).val(), "time": jQuery( 'input#timed_content_rule_instance_start_time' ).val() };
        args.end = { "date": jQuery( 'input#timed_content_rule_instance_end_date' ).val(), "time": jQuery( 'input#timed_content_rule_instance_end_time' ).val() };
        if ( "recurrence_duration_end_date" == jQuery("input[name=timed_content_rule_recurrence_duration]:checked").val() ) {
            args.rd = "recurrence_duration_end_date";
        } else {
            args.rd = "recurrence_duration_num_repeat";
        }
        return args;
    }

	jQuery('#timed_content_rule_test').click( function() {
		var args = getArgs();
		//This tag will the hold the dialog content.
		var tag =  jQuery("<div id='tcr-dialogHolder'></div>");
        var dialogHTML;
        tag.append('<div id="tcr-loading"><img src="' + timedContentRuleAjax.loadingimg + '" /> Loading...</div>');
        tag.dialog({modal: 'true',
            title: timedContentRuleAjax.dialog_label,
            width: timedContentRuleAjax.dialog_width,
            height: timedContentRuleAjax.dialog_height,
            buttons: [{	"text": timedContentRuleAjax.close_label,
                "click": function() {
                    jQuery( this ).dialog( "close" );
                }
            }]
        }).dialog('open');

		jQuery.post(
			timedContentRuleAjax.ajaxurl,
			{
				// additional data to be included along with the form fields
				action: 'timedContentPluginGetRulePeriodsAjax',
				timed_content_rule_human_readable: 'true',
				timed_content_rule_frequency: args.fsel,
				timed_content_rule_timezone: args.tz,
				timed_content_rule_recurrence_duration: args.rd,
				timed_content_rule_recurrence_duration_num_repeat: args.nr,
				timed_content_rule_recurrence_duration_end_date: args.ed,
				timed_content_rule_weekly_days_of_week_to_repeat: args.days,
				timed_content_rule_interval_multiplier: args.im,
				timed_content_rule_instance_start: args.start,
				timed_content_rule_instance_end: args.end,
				timed_content_rule_monthly_nth_weekday_of_month: args.nth,
				timed_content_rule_monthly_nth_weekday_of_month_nth: args.ntho,
				timed_content_rule_monthly_nth_weekday_of_month_weekday: args.nthw
				
			},
			function(data, textStatus, jqXHR) {
				// code that's executed when the request is processed successfully
                dialogHTML = "<table style='width: 100%;'>";
                dialogHTML += "<tr class='heading_row'><th>" + timedContentRuleAjax.start_label + "</th><th>" + timedContentRuleAjax.end_label + "</th></tr>";
                dialogHTML += '</table>';

                tag.find("div#tcr-loading").remove();
                tag.append(dialogHTML);
				jQuery.each(data, function(key, value)  {
					tag.find("table").append('<tr class="day_row ' + value.status + '" title="' + value.time + '"><td>' + value.start + '</td><td>' + value.end + '</td></tr>');
				});
			},
			'json'
		).fail( function(xhr, textStatus, errorThrown) {
                tag.find("div#tcr-loading").remove();
                var errorText = '<div class="tcr-error"><p><strong>' + timedContentRuleAjax.error + '</strong></p>';
                errorText += '<p class="heading">' + timedContentRuleAjax.error_desc + '</p>';
                errorText += '<div>' + xhr.responseText + '</div></div>';
                tag.append(errorText);
        });
	});
	
	jQuery( 'input[id^="timed_content_rule_"], select[id^="timed_content_rule_"]' ).change(function(e) {
        var args = getArgs();
        var tag = jQuery("div#schedule_desc");
        var button = jQuery('input#timed_content_rule_test');

        button.prop('disabled', 'disabled');
        tag.html('<img src="' + timedContentRuleAjax.loadingimg + '" /> Loading...');

		jQuery.post(
			timedContentRuleAjax.ajaxurl,
			{
				// additional data to be included along with the form fields
				action: 'timedContentPluginGetScheduleDescriptionAjax',
				timed_content_rule_action: jQuery( "input[name=timed_content_rule_action]:checked" ).val(),
                timed_content_rule_frequency: args.fsel,
                timed_content_rule_timezone: args.tz,
                timed_content_rule_recurrence_duration: args.rd,
                timed_content_rule_recurrence_duration_num_repeat: args.nr,
                timed_content_rule_recurrence_duration_end_date: args.ed,
                timed_content_rule_weekly_days_of_week_to_repeat: args.days,
                timed_content_rule_interval_multiplier: args.im,
                timed_content_rule_instance_start: args.start,
                timed_content_rule_instance_end: args.end,
                timed_content_rule_monthly_nth_weekday_of_month: args.nth,
                timed_content_rule_monthly_nth_weekday_of_month_nth: args.ntho,
                timed_content_rule_monthly_nth_weekday_of_month_weekday: args.nthw
				
			},
			function(data, textStatus, jqXHR) {
				// code that's executed when the request is processed successfully
				tag.html(data);
				button.removeAttr('disabled');
			},
			'text'
        ).fail( function(xhr, textStatus, errorThrown) {
                var errorText = '<div class="tcr-error"><p><strong>' + timedContentRuleAjax.error + '</strong></p>';
                errorText += '<p class="heading">' + timedContentRuleAjax.error_desc + '</p>';
                errorText += '<div>' + xhr.responseText + '</div></div>';
                tag.append(errorText);
        });
    });
});