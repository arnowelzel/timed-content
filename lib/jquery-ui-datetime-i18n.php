<?php
// Let's figure out i18n for the date- and timepickers
if ( !isset( $jquery_ui_datetime_dayNames ) )
    $jquery_ui_datetime_dayNames = array( __( "Sunday", 'timed-content' ),
        __( "Monday", 'timed-content' ),
        __( "Tuesday", 'timed-content' ),
        __( "Wednesday", 'timed-content' ),
        __( "Thursday", 'timed-content' ),
        __( "Friday", 'timed-content' ),
        __( "Saturday", 'timed-content' ) );

if ( !isset( $jquery_ui_datetime_dayNamesShort ) )
    $jquery_ui_datetime_dayNamesShort = array( _x( "Sun", "Three-letter abbreviation for Sunday", 'timed-content' ),
        _x( "Mon", "Three-letter abbreviation for Monday", 'timed-content' ),
        _x( "Tue", "Three-letter abbreviation for Tuesday", 'timed-content' ),
        _x( "Wed", "Three-letter abbreviation for Wednesday", 'timed-content' ),
        _x( "Thu", "Three-letter abbreviation for Thursday", 'timed-content' ),
        _x( "Fri", "Three-letter abbreviation for Friday", 'timed-content' ),
        _x( "Sat", "Three-letter abbreviation for Saturday", 'timed-content' ) );

if ( !isset( $jquery_ui_datetime_dayNamesMin ) )
    $jquery_ui_datetime_dayNamesMin = array( _x( "Su", "Two-letter abbreviation for Sunday", 'timed-content' ),
        _x( "Mo", "Two-letter abbreviation for Monday", 'timed-content' ),
        _x( "Tu", "Two-letter abbreviation for Tuesday", 'timed-content' ),
        _x( "We", "Two-letter abbreviation for Wednesday", 'timed-content' ),
        _x( "Th", "Two-letter abbreviation for Thursday", 'timed-content' ),
        _x( "Fr", "Two-letter abbreviation for Friday", 'timed-content' ),
        _x( "Sa", "Two-letter abbreviation for Saturday", 'timed-content' ) );

if ( !isset( $jquery_ui_datetime_monthNames ) )
    $jquery_ui_datetime_monthNames = array( __( "January", 'timed-content' ),
        __( "February", 'timed-content' ),
        __( "March", 'timed-content' ),
        __( "April", 'timed-content' ),
        __( "May", 'timed-content' ),
        __( "June", 'timed-content' ),
        __( "July", 'timed-content' ),
        __( "August", 'timed-content' ),
        __( "September", 'timed-content' ),
        __( "October", 'timed-content' ),
        __( "November", 'timed-content' ),
        __( "December", 'timed-content' ) );

if ( !isset( $jquery_ui_datetime_monthNamesShort ) )
    $jquery_ui_datetime_monthNamesShort = array( _x( "Jan", "Three-letter abbreviation for January", 'timed-content' ),
        _x( "Feb", "Three-letter abbreviation for February", 'timed-content' ),
        _x( "Mar", "Three-letter abbreviation for March", 'timed-content' ),
        _x( "Apr", "Three-letter abbreviation for April", 'timed-content' ),
        _x( "May", "Three-letter abbreviation for May", 'timed-content' ),
        _x( "Jun", "Three-letter abbreviation for June", 'timed-content' ),
        _x( "Jul", "Three-letter abbreviation for July", 'timed-content' ),
        _x( "Aug", "Three-letter abbreviation for August", 'timed-content' ),
        _x( "Sep", "Three-letter abbreviation for September", 'timed-content' ),
        _x( "Oct", "Three-letter abbreviation for October", 'timed-content' ),
        _x( "Nov", "Three-letter abbreviation for November", 'timed-content' ),
        _x( "Dec", "Three-letter abbreviation for December", 'timed-content' ) );

if ( !isset( $jquery_ui_datetime_timePeriods ) )
    $jquery_ui_datetime_timePeriods = array( _x( "AM", "Abbreviation for first 12-hour period in a day", 'timed-content' ),
        _x( "PM", "Abbreviation for second 12-hour period in a day", 'timed-content' ) );

if ( !isset( $jquery_ui_datetime_datepicker_i18n ) )
    $jquery_ui_datetime_datepicker_i18n = array(
        "closeText" => _x( "Done", "jQuery UI Datepicker Close label", "timed-content" ), // Display text for close link
        "prevText" => _x( "Prev", "jQuery UI Datepicker Previous label", "timed-content" ), // Display text for previous month link
        "nextText" => _x( "Next", "jQuery UI Datepicker Next label", "timed-content" ), // Display text for next month link
        "currentText" => _x( "Today", "jQuery UI Datepicker Today label", "timed-content" ), // Display text for current month link
        "monthNames" => $jquery_ui_datetime_monthNames, // Names of months for drop-down and formatting
        "monthNamesShort" => $jquery_ui_datetime_monthNamesShort, // For formatting
        "dayNames" => $jquery_ui_datetime_dayNames, // For formatting
        "dayNamesShort" => $jquery_ui_datetime_dayNamesShort, // For formatting
        "dayNamesMin" => $jquery_ui_datetime_dayNamesShort, // Column headings for days starting at Sunday
        "weekHeader" => _x( "Wk", "jQuery UI Datepicker Week label", "timed-content" ), // Column header for week of the year
        /* translators:  http://jqueryui.com/datepicker/#date-formats */
        "dateFormat" => _x( "MM d, yy", "jQuery UI Datepicker Date format", 'timed-content' ),
        "firstDay" => ( int )( _x( "0", "jQuery UI Datepicker 'First day of week' as integer ( Sunday = 0 ( 'zero' ), Monday = 1, ... )", 'timed-content' ) ), // The first day of the week, Sun = 0, Mon = 1, ...
        "isRTL" => ( _x( "false", "jQuery UI Datepicker: Is translated language read right-to-left ( Value must be either English 'true' or 'false' )?", 'timed-content' ) == "false" ? false : true ), // True if right-to-left language, false if left-to-right
        "showMonthAfterYear" => false, // True if the year select precedes month, false for month then year
        "yearSuffix" => '' // Additional text to append to the year in the month headers
     );

if ( !isset( $jquery_ui_datetime_timepicker_i18n ) )
    $jquery_ui_datetime_timepicker_i18n = array(
        "hourText" => _x( "Hour", "jQuery UI Timepicker 'Hour' label", "timed-content" ),
        "minuteText" => _x( "Minute", "jQuery UI Timepicker 'Minute' label", "timed-content" ),
        "amPmText" => $jquery_ui_datetime_timePeriods,
        "showPeriod" => ( _x( "true", "jQuery UI Datepicker: Does translated language show 'AM' or 'PM' (or equivalent) when displaying a time  ( Value must be either English 'true' or 'false' )?", 'timed-content' ) == "true" ? true : false ),
        "timeSeparator" => _x( ":", "jQuery UI Datepicker: Character used to separate hours and minutes in translated language", 'timed-content' ),
        "closeButtonText" => _x( "Done", "jQuery UI Timepicker 'Done' label", "timed-content" ),
        "nowButtonText" => _x( "Now", "jQuery UI Timepicker 'Now' label", "timed-content" ),
        "deselectButtonText" => _x( "Deselect", "jQuery UI Timepicker 'Deselect' label", "timed-content" ) );

?>