<?php
require_once("Arrays_Definitions.php");
global $post,
	   $timed_content_rule_occurrence_custom_fields,
	   $timed_content_rule_pattern_custom_fields,
	   $timed_content_rule_recurrence_custom_fields,
	   $timed_content_rule_exceptions_custom_fields;
$now_t = current_time( "timestamp" );
$post_id = ( isset( $_GET['post'] ) && ( TIMED_CONTENT_RULE_TYPE === get_post_type( $_GET['post'] ) ) ? intval( $_GET['post'] ) : intval( 0 ) );
$timed_content_rules_exceptions_dates = ( "" === get_post_meta( $post_id, TIMED_CONTENT_RULE_POSTMETA_PREFIX . "exceptions_dates", true ) ? array() : get_post_meta( $post_id, TIMED_CONTENT_RULE_POSTMETA_PREFIX . "exceptions_dates", true ) );
if ( empty( $timed_content_rules_exceptions_dates ) )
	$timed_content_rules_exceptions_dates_array = array( "0" => __( "- No exceptions set -", 'timed-content' ) );
else {
	sort( $timed_content_rules_exceptions_dates, SORT_NUMERIC );
	$timed_content_rules_exceptions_dates = array_unique( $timed_content_rules_exceptions_dates );
	$formatted_dates = array();
	foreach ( $timed_content_rules_exceptions_dates as $a_date ) {
		$a_date_idx = $a_date * 1000;
		$formatted_dates[$a_date_idx] = date(_x("F j, Y", "Date format for schedule description", 'timed-content'), $a_date);
	}
	$timed_content_rules_exceptions_dates_array = array_combine($timed_content_rules_exceptions_dates, $formatted_dates);
}

$timed_content_rule_occurrence_custom_fields = array(
					array(
						"name"			=> "action",
						"display"		=> "block",
						"title"			=> __( "Action", 'timed-content' ),
						"description"	=> __( "Sets the action to be performed when the rule is active.", 'timed-content' ),
						"type"			=> "radio",
						"values"		=>  array( 	1 => __( "Show the content", 'timed-content' ),
													0 => __( "Hide the content", 'timed-content' ) ),
						"default"		=>	1,
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "instance_start",
						"display"		=> "block",
						"title"			=> __( "Starting Date/Time", 'timed-content' ),
						"description"	=> __( "Sets the date and time for the beginning of the first active period for this rule.", 'timed-content' ),
						"type"			=> "datetime",
						"default"		=>  array( 	"date" => date_i18n( _x( "F jS, Y", "Starting Date/Time date format (http://ca2.php.net/manual/en/function.date.php)", 'timed-content'), strtotime( "+1 hour", $now_t ) ),
													"time" => date_i18n( _x( "g:i A", "Starting Date/Time time format (http://ca2.php.net/manual/en/function.date.php)", 'timed-content'), strtotime( "+1 hour", $now_t ) ) ),
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "instance_end",
						"display"		=> "block",
						"title"			=> __( "Ending Date/Time", 'timed-content' ),
						"description"	=> __( "Sets the date and time for the end of the first active period for this rule.", 'timed-content' ),
						"type"			=> "datetime",
						"default"		=>  array( 	"date" => date_i18n( _x( "F jS, Y", "Ending Date/Time date format (http://ca2.php.net/manual/en/function.date.php)", 'timed-content'), strtotime( "+2 hour", $now_t ) ),
													"time" => date_i18n( _x( "g:i A", "Ending Date/Time time format (http://ca2.php.net/manual/en/function.date.php)", 'timed-content'), strtotime( "+2 hour", $now_t ) ) ),
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "timezone",
						"display"		=> "block",
						"title"			=> __( "Timezone:", 'timed-content' ),
						"description"	=> __( "Select a city in the timezone you wish to use for this rule.", 'timed-content' ),
						"type"			=> "timezone-list",
						"default"		=>  get_option( 'timezone_string' ),
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					)
				);
$timed_content_rule_pattern_custom_fields = array(
					array(
						"name"			=> "frequency",
						"display"		=> "block",
						"title"			=> __( "Frequency:", 'timed-content' ),
						"description"	=> __( "Sets the frequency at which the action should be repeated.", 'timed-content' ),
						"type"			=> "list",
						"default"		=> "1",
						"values"		=>  $timed_content_rule_freq_array,
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "hourly_num_of_hours",
						"display"		=> "none",
						"title"			=> __( "Repeat How Often?", 'timed-content' ),
						"description"	=> __( "If the frequency is set to Hourly, repeat this action every X hours.", 'timed-content' ),
						"type"			=> "number",
						"default"		=> "1",
						"min"			=> "1",
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "daily_num_of_days",
						"display"		=> "none",
						"title"			=> __( "Repeat How Often?", 'timed-content' ),
						"description"	=> __( "If the frequency is set to Daily, repeat this action every X days.", 'timed-content' ),
						"type"			=> "number",
						"default"		=> "1",
						"min"			=> "1",
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "weekly_num_of_weeks",
						"display"		=> "none",
						"title"			=> __( "Repeat How Often?", 'timed-content' ),
						"description"	=> __( "If the frequency is set to Weekly, repeat this action every X weeks.", 'timed-content' ),
						"type"			=> "number",
						"default"		=> "1",
						"min"			=> "1",
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "weekly_days_of_week_to_repeat",
						"display"		=> "none",
						"title"			=> __( "Repeat On The Following Days", 'timed-content' ),
						"description"	=> __( "If the frequency is set to Weekly, repeat this action on these days of the week INSTEAD of the day of week the Starting Date/Time falls on.", 'timed-content' ),
						"type"			=> "checkbox-list",
						"default"		=> array(),
						"values"		=>  $timed_content_rule_days_array,
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "monthly_num_of_months",
						"display"		=> "none",
						"title"			=> __( "Repeat How Often?", 'timed-content' ),
						"description"	=> __( "If the frequency is set to Monthly, repeat this action every X months.", 'timed-content' ),
						"type"			=> "number",
						"default"		=> "1",
						"min"			=> "1",
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "monthly_nth_weekday_of_month",
						"display"		=> "none",
						"title"			=> __( "Repeat On The Nth Weekday Of The Month?", 'timed-content' ),
						"description"	=> __( "If the frequency is set to Monthly, repeat this action on the Nth weekday of the month (for example, 'every third Tuesday'). Check this box to select a pattern below.", 'timed-content' ),
						"type"			=> "checkbox",
						"default"		=> "no",
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "monthly_nth_weekday_of_month_nth",
						"display"		=> "none",
						"title"			=> __( "Nth Weekday Ordinal:", 'timed-content' ),
						"description"	=> __( "Select a value for the Nth (for example 'first', 'second', etc.) week of the month.", 'timed-content' ),
						"type"			=> "list",
						"default"		=> 0,
						"values"		=>  $timed_content_rule_ordinal_array,
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "monthly_nth_weekday_of_month_weekday",
						"display"		=> "none",
						"title"			=> __( "Nth Weekday Day Of Week:", 'timed-content' ),
						"description"	=> __( "Select the day of week to repeat on.", 'timed-content' ),
						"type"			=> "list",
						"default"		=> 0,
						"values"		=>  $timed_content_rule_ordinal_days_array,
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					),
					array(
						"name"			=> "yearly_num_of_years",
						"display"		=> "none",
						"title"			=> __( "Repeat How Often?", 'timed-content' ),
						"description"	=> __( "If the frequency is set to Yearly, repeat this action every X years.", 'timed-content' ),
						"type"			=> "number",
						"default"		=> "1",
						"min"			=> "1",
						"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
						"capability"	=> "edit_posts"
					)
				);
$timed_content_rule_recurrence_custom_fields = array(
	array(
		"name"			=> "recurrence_duration",
		"display"		=> "block",
		"title"			=> __( "How Often To Repeat This Action?", 'timed-content' ),
		"description"	=> "",
		"type"			=> "radio",
		"values"		=>  array( 	"recurrence_duration_end_date" => __( "Keep repeating until a given date", 'timed-content' ),
			"recurrence_duration_num_repeat" => __( "Repeat a set number of times", 'timed-content' ) ),
		"default"		=>	"recurrence_duration_end_date",
		"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
		"capability"	=> "edit_posts"
	),
	array(
		"name"			=> "recurrence_duration_end_date",
		"display"		=> "none",
		"title"			=> __( "End Date:", 'timed-content' ),
		"description"	=> __( "Using the settings above, repeat this action until this date.", 'timed-content' ),
		"type"			=> "date",
		"default"		=>  date_i18n( _x( "F jS, Y", "End Date date format (http://ca2.php.net/manual/en/function.date.php)", 'timed-content'), strtotime( "+1 year", $now_t ) ),
		"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
		"capability"	=> "edit_posts"
	),
	array(
		"name"			=> "recurrence_duration_num_repeat",
		"display"		=> "none",
		"title"			=> __( "Repeat How Many Times?", 'timed-content' ),
		"description"	=> __( "Using the settings above, repeat this action this many times.", 'timed-content' ),
		"type"			=> "number",
		"default"		=> "1",
		"min"			=> "1",
		"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
		"capability"	=> "edit_posts"
	)
);
$exceptions_dates_picker_on_select = <<<FUNC
onSelect: function (dateText, inst) {
                //alert(dateText);
                jQuery("#timed_content_rule_exceptions_dates option[value='0']" ).remove();
                jQuery("#timed_content_rule_exceptions_dates").append( '<option value="' + parseInt(Date.parse(dateText) / 1000) + '">' + dateText + '</option>' );
                jQuery(this).val("");
                jQuery(this).trigger("change");
            },
FUNC;

$timed_content_rule_exceptions_custom_fields = array(
	array(
		"name"			=> "exceptions_dates_picker",
		"display"		=> "block",
		"title"			=> __( "Add Exception Date:", 'timed-content' ),
		"description"	=> __( "Select a date to add to the Exception Dates List.", 'timed-content' ),
		"type"			=> "date",
		"default"		=>  "",
		"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
		"capability"	=> "edit_posts",
		"custom_functions"	=> $exceptions_dates_picker_on_select
	),
	array(
		"name"			=> "exceptions_dates",
		"display"		=> "block",
		"title"			=> __( "Exception Dates List", 'timed-content' ),
		"description"	=> __( "Dates that this Timed Content Rule will not be active.  Double-click on a date to remove it from the list.", 'timed-content' ),
		"type"			=> "menu",
		"values"		=>  $timed_content_rules_exceptions_dates_array,
		"size"			=>	"10",
		"default"		=>	array(),
		"scope"			=>	array( TIMED_CONTENT_RULE_TYPE ),
		"capability"	=> "edit_posts"
	)
);

?>