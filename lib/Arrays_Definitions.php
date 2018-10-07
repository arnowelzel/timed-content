<?php
if (!isset($timed_content_rule_freq_array)) {
    $timed_content_rule_freq_array = array(
        0 => __('Hourly', 'timed-content'),
        1 => __('Daily', 'timed-content'),
        2 => __('Weekly', 'timed-content'),
        3 => __('Monthly', 'timed-content'),
        4 => __('Yearly', 'timed-content')
    );
}

if (!isset($timed_content_rule_days_array)) {
    $timed_content_rule_days_array = array(
        0 => __('Sundays', 'timed-content'),
        1 => __('Mondays', 'timed-content'),
        2 => __('Tuesdays', 'timed-content'),
        3 => __('Wednesdays', 'timed-content'),
        4 => __('Thursdays', 'timed-content'),
        5 => __('Fridays', 'timed-content'),
        6 => __('Saturdays', 'timed-content')
    );
}

if (!isset($timed_content_rule_ordinal_array)) {
    $timed_content_rule_ordinal_array = array(
        0 => __('first', 'timed-content'),
        1 => __('second', 'timed-content'),
        2 => __('third', 'timed-content'),
        3 => __('fourth', 'timed-content'),
        4 => __('last', 'timed-content')
    );
}

if (!isset($timed_content_rule_ordinal_days_array)) {
    $timed_content_rule_ordinal_days_array = array(
        0 => __('Sunday', 'timed-content'),
        1 => __('Monday', 'timed-content'),
        2 => __('Tuesday', 'timed-content'),
        3 => __('Wednesday', 'timed-content'),
        4 => __('Thursday', 'timed-content'),
        5 => __('Friday', 'timed-content'),
        6 => __('Saturday', 'timed-content'),
        7 => __('day', 'timed-content')
    );
}
?>