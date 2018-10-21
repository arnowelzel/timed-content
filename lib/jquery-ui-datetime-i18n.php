<?php
global $wp_locale;

if (!function_exists("ca_aliencyborg_strip_array_indices")) {
    function ca_aliencyborg_strip_array_indices($ArrayToStrip)
    {
        foreach ($ArrayToStrip as $objArrayItem) {
            $NewArray[] = $objArrayItem;
        }

        return $NewArray;
    }
}

if (!function_exists("ca_aliencyborg_date_format_php_to_js")) {
    function ca_aliencyborg_date_format_php_to_js($sFormat)
    {
        return str_replace(
            array('d',  'j', 'l',  'm',  'n', 'F', 'Y'),
            array('dd', 'd', 'DD', 'mm', 'm', 'MM', 'yy'),
            $sFormat
        );
    }
}

if ( !isset( $jquery_ui_datetime_datepicker_i18n ) )
    $jquery_ui_datetime_datepicker_i18n = array(
        "closeText" => _x( "Done", "jQuery UI Datepicker Close label", "timed-content" ), // Display text for close link
        "prevText" => _x( "Prev", "jQuery UI Datepicker Previous label", "timed-content" ), // Display text for previous month link
        "nextText" => _x( "Next", "jQuery UI Datepicker Next label", "timed-content" ), // Display text for next month link
        "currentText" => _x( "Today", "jQuery UI Datepicker Today label", "timed-content" ), // Display text for current month link
        "weekHeader" => _x( "Wk", "jQuery UI Datepicker Week label", "timed-content" ), // Column header for week of the year
        // Replace the text indices for the following arrays with 0-based arrays
        "monthNames" => ca_aliencyborg_strip_array_indices( $wp_locale->month ), // Names of months for drop-down and formatting
        "monthNamesShort" => ca_aliencyborg_strip_array_indices( $wp_locale->month_abbrev ), // For formatting
        "dayNames" => ca_aliencyborg_strip_array_indices( $wp_locale->weekday ), // For formatting
        "dayNamesShort" => ca_aliencyborg_strip_array_indices( $wp_locale->weekday_abbrev ), // For formatting
        "dayNamesMin" => ca_aliencyborg_strip_array_indices( $wp_locale->weekday_initial ), // Column headings for days starting at Sunday
        "dateFormat" => ca_aliencyborg_date_format_php_to_js( get_option( 'date_format' ) ),
        "firstDay" => get_option( 'start_of_week' ),
        "isRTL" => $wp_locale->is_rtl(),
        "showMonthAfterYear" => false, // True if the year select precedes month, false for month then year
        "yearSuffix" => '' // Additional text to append to the year in the month headers
    );
$tf = get_option( 'time_format' );
if ( false !== strpos( $tf, "A") ) {
    $meridiem = array($wp_locale->meridiem['AM'], $wp_locale->meridiem['PM']);
    $show_period = true;
    $show_period_labels = true;
    $show_leading_zero = false;
} elseif ( false !== strpos( $tf, "a") ) {
    $meridiem = array($wp_locale->meridiem['am'], $wp_locale->meridiem['pm']);
    $show_period = true;
    $show_period_labels = true;
    $show_leading_zero = false;
} else {
    $meridiem = array('', '');
    $show_period = false;
    $show_period_labels = false;
    $show_leading_zero = true;
}

if ( !isset( $jquery_ui_datetime_timepicker_i18n ) )
    $jquery_ui_datetime_timepicker_i18n = array(
        "hourText" => _x( "Hour", "jQuery UI Timepicker 'Hour' label", "timed-content" ),
        "minuteText" => _x( "Minute", "jQuery UI Timepicker 'Minute' label", "content-protector" ),
        "timeSeparator" => _x( ":", "jQuery UI Datepicker: Character used to separate hours and minutes in translated language", 'timed-content' ),
        "closeButtonText" => _x( "Done", "jQuery UI Timepicker 'Done' label", "timed-content" ),
        "nowButtonText" => _x( "Now", "jQuery UI Timepicker 'Now' label", "timed-content" ),
        "deselectButtonText" => _x( "Deselect", "jQuery UI Timepicker 'Deselect' label", "timed-content" ),
        "amPmText" => $meridiem,
        "showPeriod" => $show_period,
        "showPeriodLabels" => $show_period_labels,
        "showLeadingZero" => $show_leading_zero );

?>