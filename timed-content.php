<?php
/*
Plugin Name: Timed Content
Text Domain: timed-content
Domain Path: /lang
Plugin URI: http://wordpress.org/plugins/timed-content/
Description: Plugin to show or hide portions of a Page or Post based on specific date/time characteristics.  These actions can either be processed either server-side or client-side, depending on the desired effect.
Author: K. Tough, Arno Welzel
Version: 2.10
Author URI: http://wordpress.org/plugins/timed-content/
*/
if (!class_exists("TimedContentPlugin")) {
    define("TIMED_CONTENT_VERSION", "2.10");
    define("TIMED_CONTENT_SLUG", "timed-content");
    define("TIMED_CONTENT_PLUGIN_URL", plugins_url() . '/' . TIMED_CONTENT_SLUG);
    define("TIMED_CONTENT_CLIENT_TAG", "timed-content-client");
    define("TIMED_CONTENT_SERVER_TAG", "timed-content-server");
    define("TIMED_CONTENT_RULE_TAG", "timed-content-rule");
    define("TIMED_CONTENT_ZERO_TIME", "1970-Jan-01 00:00:00 +000");  // Start of Unix Epoch
    define("TIMED_CONTENT_END_TIME", "2038-Jan-19 03:14:07 +000");   // End of Unix Epoch
    define("TIMED_CONTENT_RULE_TYPE", "timed_content_rule");
    define("TIMED_CONTENT_RULE_POSTMETA_PREFIX", TIMED_CONTENT_RULE_TYPE . "_");
    define("TIMED_CONTENT_CSS", TIMED_CONTENT_PLUGIN_URL . "/css/timed-content.css");
    define("TIMED_CONTENT_CSS_DASHICONS", TIMED_CONTENT_PLUGIN_URL . "/css/ca-aliencyborg-dashicons/style.css");
    // Required for styling the jQuery UI Datepicker and jQuery UI Timepicker
    define("TIMED_CONTENT_JQUERY_UI_CSS", TIMED_CONTENT_PLUGIN_URL . "/css/jqueryui/1.10.3/themes/smoothness/jquery-ui.css");
    define("TIMED_CONTENT_JQUERY_UI_TIMEPICKER_JS", TIMED_CONTENT_PLUGIN_URL . "/js/jquery-ui-timepicker-0.3.3/jquery.ui.timepicker.js");
    define("TIMED_CONTENT_JQUERY_UI_TIMEPICKER_CSS", TIMED_CONTENT_PLUGIN_URL . "/js/jquery-ui-timepicker-0.3.3/jquery.ui.timepicker.css");


    /**
     * Class TimedContentPlugin
     *
     * Class that contains all of the functions required to run the plugin.  This cuts down on
     * the possibility of name collisions with other plugins.
     */
    class TimedContentPlugin
    {
        /**
         * Constructor
         *
         * @return void
         */
        function __construct()
        { 
        }

        /**
         * Backward compatible constructor wrapper
         *
         * @return void
         */
        function TimedContentPlugin()
        { 
            self::__construct();
        }
        
        /**
         * Modified from the original patch to use the currently set
         * timezone from PHP, like PHP's date(), and to make the code
         * more readable.
         * https://core.trac.wordpress.org/ticket/25768
         *
         * @param string $j          Formatted date string
         * @param string $req_format Format to display the date
         * @param int    $i          Unix timestamp
         * @param bool   $gmt        Whether to convert to GMT for time
         *
         * @return void
         */
        function fixDateI18n($j, $req_format, $i = false, $gmt = false)
        {
            /* @var $wp_locale WP_Locale */
            global $wp_locale;
            $timestamp = $i;

            // get current timestamp if $i is false
            if (false === $timestamp) {
                if ($gmt)
                    $timestamp = time();
                else
                    $timestamp = current_time('timestamp');

                // use debug parameter if current user is allowed to edit the post
                if (isset($_GET['tctest']) && current_user_can("edit_post", $post->post_id)) {
                    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $_GET['tctest']);
                    if($dt != false) $timestamp = $dt->getTimestamp();
                }
            }
            
            // get components of the date (timestamp) as array
            $date_components = getdate($timestamp);

            // numeric representation of a month, with leading zeros
            $date_month = $wp_locale->get_month($date_components['mon']);
            $date_month_abbrev = $wp_locale->get_month_abbrev($date_month);
            // numeric representation of the day of the week
            $date_weekday = $wp_locale->get_weekday($date_components['wday']);
            $date_weekday_abbrev = $wp_locale->get_weekday_abbrev($date_weekday);
            // get if hour is Ante meridiem or Post meridiem
            $meridiem = $date_components['hours'] >= 12 ? 'pm' : 'am';
            // lowercase Ante meridiem and Post meridiem hours
            $date_meridiem = $wp_locale->get_meridiem($meridiem);
            // uppercase Ante meridiem and Post meridiem
            $date_meridiem_capital = $wp_locale->get_meridiem(strtoupper($meridiem));

            // escape literals
            $date_weekday_abbrev = backslashit($date_weekday_abbrev);
            $date_month = backslashit($date_month);
            $date_weekday = backslashit($date_weekday);
            $date_month_abbrev = backslashit($date_month_abbrev);
            $date_meridiem = backslashit($date_meridiem);
            $date_meridiem_capital = backslashit($date_meridiem_capital);

            // the translated format string
            $translated_date_format_string = '';
            // the 2 arrays map a format literal to its translation (e. g. 'F' to the escaped month translation)
            $translate_formats = array('D', 'F', 'l', 'M', 'a', 'A', 'c', 'r');
            $translations = array(
                $date_weekday_abbrev, // D
                $date_month, // F
                $date_weekday, // l
                $date_month_abbrev, // M
                $date_meridiem, // a
                $date_meridiem_capital, // A
                'Y-m-d\TH:i:sP', // c
                sprintf('%s, d %s Y H:i:s O', $date_weekday_abbrev, $date_month_abbrev), // r
            );

            // find each format literal that needs translation and replace it by its translation
            // respects the escaping
            // iterate $req_format from ending to beginning
            for ($i = strlen($req_format) - 1; $i > -1; $i--) {
                // test if current char is format literal that needs translation
                $translate_formats_index = array_search($req_format[$i], $translate_formats);

                if ($translate_formats_index !== false) {
                    // counts the slashes (the escape char) in front of the current char
                    $slashes_counter = 0;

                    // count all slashes left-hand side of the current char
                    for ($j = $i - 1; $j > -1; $j--) {
                        if ($req_format[$j] == '\\')
                            $slashes_counter++;
                        else
                            break;
                    }

                    // number of slashes is even
                    if ($slashes_counter % 2 == 0)
                        // current char is not escaped, therefore it is a format literal
                        $translated_date_format_string = $translations[$translate_formats_index] . $translated_date_format_string;
                    else
                        // current char is escaped, therefore it is not a format literal, just add it unchanged
                        $translated_date_format_string = $req_format[$i] . $translated_date_format_string;
                }
                else
                    // current char is no a format literal, just add it unchanged
                    $translated_date_format_string = $req_format[$i] . $translated_date_format_string;
            }

            $req_format = $translated_date_format_string;

            if ($gmt)
                // get GMT date string
                $date_formatted = gmdate($req_format, $timestamp);
            else {
                // get Wordpress time zone
                // $timezone_string = get_option('timezone_string');
                // Haha, just kidding. Let's get the currently set timezone, as God and Rasmus intended
                $timezone_string = date_default_timezone_get();

                if ($timezone_string) {
                    // create time zone object
                    $timezone_object = timezone_open($timezone_string);
                    // create date object from time zone object
                    $local_date_object = date_create(null, $timezone_object);
                    // set time and date of $local_date_object to $timestamp
                    $date_components = isset($date_components) ? $date_components : getdate($timestamp);
                    date_date_set($local_date_object, $date_components['year'], $date_components['mon'], $date_components['mday']);
                    date_time_set($local_date_object, $date_components['hours'], $date_components['minutes'], $date_components['seconds']);
                    // format date according to the Wordpress time zone
                    $date_formatted = date_format($local_date_object, $req_format);
                } else {
                    // fall back if no Wordpress time zone set
                    $date_formatted = date($req_format, $i);
                }
            }

            return $date_formatted;
        }

        /**
         * Creates the Timed Content Rule post type and registers it with Wordpress
         *
         * @return void
         */
        function timedContentRuleTypeInit()
        {
            $labels = array(
                'name' => _x('Timed Content Rules', 'post type general name', 'timed-content'),
                'singular_name' => _x('Timed Content Rule', 'post type singular name', 'timed-content'),
                'add_new' => _x('Add New', 'Menu item/button label on Timed Content Rules admin page', 'timed-content'),
                'add_new_item' => __('Add New Timed Content Rule', 'timed-content'),
                'edit_item' => __('Edit Timed Content Rule', 'timed-content'),
                'new_item' => __('New Timed Content Rule', 'timed-content'),
                'view_item' => __('View Timed Content Rule', 'timed-content'),
                'search_items' => __('Search Timed Content Rules', 'timed-content'),
                'not_found' =>  __('No Timed Content Rules found', 'timed-content'),
                'not_found_in_trash' => __('No Timed Content Rules found in Trash', 'timed-content'), 
                'parent_item_colon' => '',
                'menu_name' => _x('Timed Content Rules', 'post type general name', 'timed-content')
            );
            $args = array(
                'labels' => $labels,
                'description' => __('Create regular schedules to show or hide selected content in a Page or Post.', 'timed-content'),
                'public' => false,
                'publicly_queryable' => false,
                'exclude_from_search' => false,
                'show_ui' => true, 
                'show_in_menu' => true, 
                'show_in_nav_menus' => true, 
                'show_in_admin_bar' => true, 
                'query_var' => false,
                'rewrite' => false,
                'capability_type' => 'post',
                'has_archive' => false, 
                'hierarchical' => false,
                'menu_position' => 5,
                'supports' => array('title')
            ); 
            register_post_type(TIMED_CONTENT_RULE_TYPE, $args);
        }
        
        /**
         * Filter to change sort order to title
         *
         * @param array $query Array of currently defined messages for post types
         * 
         * @return mixed Array of messages with appropriate messages for Timed Content Rules added in
         */
        function timedContentPreGetPosts($query)
        {
            if ($query->is_admin) {
                if ($query->get('post_type') == TIMED_CONTENT_RULE_TYPE) {
                    $query->set('orderby', 'title');
                    $query->set('order', 'ASC');
                }
            }
            return $query;
        }

        /**
         * Filter to customize CRUD messages for Timed Content Rules
         *
         * @param array $messages Array of currently defined messages for post types
         * 
         * @return mixed Array of messages with appropriate messages for Timed Content Rules added in
         */
        function timedContentRuleUpdatedMessages($messages)
        {
            global $post;

            /* translators: date and time format to activate rule. http://ca2.php.net/manual/en/function.date.php*/
            $post_date = date_i18n(__('M j, Y @ G:i', 'timed-content'), strtotime($post->post_date));

            $messages[TIMED_CONTENT_RULE_TYPE] = array(
                0 => '', // Unused. Messages start at index 1.
                1 => __('Timed Content Rule updated.', 'timed-content'),
                2 => __('Custom field updated.', 'timed-content'),
                3 => __('Custom field deleted.', 'timed-content'),
                4 => __('Timed Content Rule updated.', 'timed-content'),
                /* translators: %s: date and time of the revision */
                5 => isset($_GET['revision']) ? sprintf(__('Timed Content Rule restored to revision from %s', 'timed-content'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
                6 => __('Timed Content Rule published.', 'timed-content'),
                7 => __('Timed Content Rule saved.', 'timed-content'),
                8 => __('Timed Content Rule submitted.', 'timed-content'),
                /* translators: %s: date and time to activate rule. */
                9 => sprintf(__('Timed Content Rule scheduled for: %s.', 'timed-content'), "<strong>" . $post_date . "</strong>"),
                10 => __('Timed Content Rule draft updated.', 'timed-content')
            );
            
            return $messages;
        }

        /**
         * Build a date/time string withEnglish month names
         *
         * @param string $date Date as string
         * @param string $time Time as string
         *
         * @return string Date and time as string with English month names 
         */
        function dateTimeToEnglish($date, $time = "")
        {
            $months = array("January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December");
            $monthsI18N = array(__("January", 'timed-content'),
                __("February", 'timed-content'),
                __("March", 'timed-content'),
                __("April", 'timed-content'),
                __("May", 'timed-content'),
                __("June", 'timed-content'),
                __("July", 'timed-content'),
                __("August", 'timed-content'),
                __("September", 'timed-content'),
                __("October", 'timed-content'),
                __("November", 'timed-content'),
                __("December", 'timed-content'));
            $english_date = str_replace($monthsI18N, $months, $date);
            return $english_date . " " . $time;
        }

        /**
         * Advances a date/time by a set number of days
         *
         * @param int $current             UNIX timestamp of the date/time before incrementing
         * @param int $interval_multiplier Number of days to advance
         * 
         * @return int Unix timestamp of the new date/time
         */
        function getNextDay($current, $interval_multiplier)
        {
            return strtotime($interval_multiplier . " day", $current);
        }

        /**
         * Advances a date/time by a set number of hours
         *
         * @param int $current             UNIX timestamp of the date/time before incrementing
         * @param int $interval_multiplier Number of hours to advance
         * 
         * @return int Unix timestamp of the new date/time
         */
        function getNextHour($current, $interval_multiplier)
        {
            return strtotime($interval_multiplier . " hour", $current);
        }

        /**
         * Advances a date/time by a set number of weeks
         *
         * Advances a date/time by a set number of weeks.  If given an array of days of the week, this function will
         * advance the date/time to the next day in that array in the jumped-to week. Use this function if you're
         * repeating an action on specific days of the week (i.e., on Weekdays, Tuesdays and Thursdays, etc.).
         *
         * @param int   $current             UNIX timestamp of the date/time before incrementing
         * @param int   $interval_multiplier Number of weeks to advance
         * @param array $days                Array of integers symbolizing the days of the week to
         *                                   repeat on (0 - Sunday, 1 - Monday, ..., 6 - Saturday).
         * 
         * @return int Unix timestamp of the new date/time
         */
        function getNextWeek($current, $interval_multiplier, $days = array())
        {
            // If $days is empty, advance $interval_multiplier weeks from $current and return the timestamp
            if (empty($days)) return strtotime($interval_multiplier . " week", $current);

            // Otherwise, set up an array combining the days of the week to repeat on and the current day
            // (keys and values of the array will be the same, and the array is sorted)
            $currentDayOfWeekIndex = date("w", $current);
            $days = array_merge(array($currentDayOfWeekIndex), $days);
            $days = array_unique($days);
            $days = array_values($days);
            sort($days);
            $daysOfWeek = array_combine($days, $days);

            // If the current day is the last one of the days of the week to repeat on, jump ahead to
            // the next week to be repeating on and get the earliest day in the array
            if ($currentDayOfWeekIndex == max($daysOfWeek))
                $pattern = ((7 - $currentDayOfWeekIndex) + (7 * ($interval_multiplier - 1)) + (min(array_keys($daysOfWeek)))) . " day";
            // Otherwise, cycle through the array until we find the next day to repeat on
            else  {
                $nextDayOfWeekIndex = $currentDayOfWeekIndex;
                do {
                } while (!isset($daysOfWeek[++$nextDayOfWeekIndex]));
                $pattern = ($nextDayOfWeekIndex - $currentDayOfWeekIndex) . " day";
            }
            return strtotime($pattern, $current);
        }

        /**
         * Advances a date/time by a set number of months
         *
         * Advances a date/time by a set number of months.  When the date/time of the first active period lies
         * on the 29th, 30th, or 31st of the month, this function will return a date/time on the the last day
         * of the month for those months not containing those days.
         *
         * @param int $current             UNIX timestamp of the date/time before incrementing
         * @param int $start               UNIX timestamp of the first active period's date/time
         * @param int $interval_multiplier Number of months to advance
         * 
         * @return int Unix timestamp of the new date/time
         */
        function getNextMonth($current, $start, $interval_multiplier)
        {
            // For most days in the month, it's pretty easy. Get the day of month of the starting date.
            $startDay = date("j", $start); 
            
            // If it's before or on the 28th, just jump the number of months and be done with it.
            if ($startDay <= 28)
                return strtotime($interval_multiplier . " month", $current);
                
            // If it's on the 29th, 30th, or 31st, it gets tricky.  Some months don't have those days - so on those
            // months we need to repeat on the last day of the month instead, but we also need to jump back to the
            // correct day the following month. Let's say we want to repeat something on the 31st every month: this
            // is what we expect to see for a pattern:
            //
            //   .
            //   .
            //   .
            // December 31st
            // January 31st
            // February 28th
            // March 31st
            // April 30th
            //   .
            //   .
            //   .
            //
            // Unfortunately, PHP relative date handling isn't that smart (add "+1 month" to January 31st, and you
            // end up in March), so we'll have to figure it out ourselves by figuring out how many days to jump instead.

            // We'll need to calculate this for each interval and return the timestamp after the last jump.
            $temp_current = $current;
            for ($i = 0; $i < $interval_multiplier; $i++) {
                // The pattern for jumping will be different in each interval.
                $temp_pattern = "";
                
                // Get the month number of the current date.
                //$currentMonth = date("n", $temp_current);
                
                // Get the number of days in the month of the current date.
                $lastDayThisMonth = date("t", strtotime("this month", $temp_current));  
                
                // Get the number of days for the next month relative to the current date .
                // Subtract 3 days from the next month to counter known month skipping bugs in PHP's relative date 
                // handling, that being the difference between the shortest possible month (non-leap February - 28 days)
                // and the longest (Jan., Mar., May, Jul., Aug., Oct., Dec. - 31 days).  This may be fixed in PHP 5.3.x
                // but this should be backwards-compatible anyway.
                $lastDayNextMonth = date("t", strtotime("-3 day next month", $temp_current));
                    
                if ($lastDayThisMonth > $lastDayNextMonth) {
                    // Current month is longer than next month...

                    // If we're repeating on the last day of this month, jump the number of days next month
                    if ($startDay == $lastDayThisMonth)                
                        $temp_pattern = $lastDayNextMonth . " days";
                    // If the start day doesn't exist in the next month (i.e., no "31st" in June), jump the
                    // number of days next month plus the difference between the start day and the number of days this month
                    elseif ($startDay > $lastDayNextMonth)
                        $temp_pattern = ($lastDayThisMonth + $lastDayNextMonth - $startDay). " days";
                    // Otherwise, jump ahead the number of days in this month
                    else
                        $temp_pattern = $lastDayThisMonth . " days";
                } elseif ($lastDayThisMonth < $lastDayNextMonth) {
                    // Current month is shorter than next month...

                    // If the start day doesn't exist in this month (i.e., no "31st" in June), jump the
                    // number of days next month plus the difference between the start day and the number of days this month
                    if ($startDay >= $lastDayThisMonth)
                        $temp_pattern = $startDay . " days";
                    // Otherwise, jump ahead the number of days in this month
                    else
                        $temp_pattern = $lastDayThisMonth . " days";
                } 
                // If the current month and next month are equally long, jumping by "1 month" is fine
                else
                    $temp_pattern = "1 month";
                
                $temp_current = strtotime($temp_pattern, $temp_current);
            }
            return $temp_current;

        }

        /**
         * Advances a date/time to the 'n'th weekday of the next month (eg., first Wednesday, third Monday, last Friday, etc.).
         *
         * NB: if $ordinal is set to '4' and $day is set to '7', it wil return the last day of the month.
         *
         * @param int $current UNIX timestamp of the date/time before incrementing
         * @param int $ordinal Integer symbolizing the ordinal (0 - first, 1 - second, 2 - third, 3 - fourth, 4 - last)
         * @param int $day     integers symbolizing the days of the week to
         *                     repeat on (0 - Sunday, 1 - Monday, ..., 6 - Saturday, 7 - day).
         * 
         * @return int Unix timestamp of the new date/time
         */
        function getNthWeekdayOfMonth($current, $ordinal, $day)
        {
            // First, get the month/year we need to work with
            $the_month = date("F", $current);
            $the_year = date("Y", $current);
            $lastDayThisMonth = date("t", $current);
            
            // Get the time for the $current timestamp
            $current_time = date("g:i A", $current);
            $the_day = "";
            
            if ($day == 7) { // If $day is "day of the month", get the day of month based on the ordinal
                if(0 == $ordinal) $the_day = "1";                     // First day of the month
                elseif (1 == $ordinal) $the_day = "2";                // Second day of the month
                elseif (2 == $ordinal) $the_day = "3";                // Third day of the month
                elseif (3 == $ordinal) $the_day = "4";                // Fourth day of the month
                elseif (4 == $ordinal) $the_day = $lastDayThisMonth;  // Last day of the month
                else $the_day = "1";
            } else { // If $day is one of the days of the week...
                $day_range = array();
                // ...get a 7-day range based on the ordinal...
                if(0 == $ordinal) $day_range = range(1, 7);                                          // First 7 days of the month
                elseif (1 == $ordinal) $day_range = range(8, 14);                                    // Second 7 days of the month
                elseif (2 == $ordinal) $day_range = range(15, 21);                                   // Third 7 days of the month
                elseif (3 == $ordinal) $day_range = range(22, 28);                                   // Fourth 7 days of the month
                elseif (4 == $ordinal) $day_range = range($lastDayThisMonth - 6, $lastDayThisMonth); // Last 7 days of the month
                else $day_range = range(1, 7);
                // ...and find the matching weekday in that range.
                foreach ($day_range as $a_day) { 
                    if ($day == date("w", strtotime($the_month . " " . $a_day . ", " . $the_year))) {
                        $the_day = $a_day;
                        break;
                    }
                }
            }
            
            // Build the date/time string for the correct day and return its timestamp 
            $pattern = $the_month . " " . $the_day . ", " . $the_year . ", " . $current_time;
            return strtotime($pattern); 
        }

        /**
         * Advances a date/time by a set number of years
         *
         * @param int $current             UNIX timestamp of the date/time before incrementing
         * @param int $interval_multiplier Number of years to advance
         * 
         * @return int Unix timestamp of the new date/time
         */
        function getNextYear($current, $interval_multiplier)
        {
            return strtotime($interval_multiplier . " year", $current);
        }

        /**
         * Validates the various Timed Content Rule parameters and returns a series of error messages.
         *
         * @param array $args Array of Timed Content Rule parameters
         * 
         * @return array Array of error messages
         */
        function validate($args)
        {
            $errors = array();
            
            $instance_start = strtotime($this->dateTimeToEnglish($args['instance_start']['date'], $args['instance_start']['time']) . ' ' . $args['timezone']);
            $instance_end = strtotime($this->dateTimeToEnglish($args['instance_end']['date'], $args['instance_end']['time']) . ' ' . $args['timezone']);
            $end_date = strtotime($this->dateTimeToEnglish($args['end_date'], $args['instance_start']['time']) . ' ' . $args['timezone']);
            
            if ($args['instance_start']['date'] == "")
                $errors[] = __("Date in Starting Date/Time must not be empty.", 'timed-content');        
            if ($args['instance_start']['time'] == "")
                $errors[] = __("Time in Starting Date/Time must not be empty.", 'timed-content');        
            if ($args['instance_end']['date'] == "")
                $errors[] = __("Date in Ending Date/Time must not be empty.", 'timed-content');        
            if ($args['instance_end']['time'] == "")
                $errors[] = __("Time in Ending Date/Time must not be empty.", 'timed-content');
            if ($args['interval_multiplier'] == "")
                $errors[] = __("Repeat How Often? must not be empty.", 'timed-content');
            if (!is_numeric($args['interval_multiplier']))
                $errors[] = __("Repeat How Often? must be a number.", 'timed-content');
            if (($args['num_repeat'] == "") && ($args['recurr_type'] == "recurrence_duration_num_repeat"))
                $errors[] = __("Repeat How Many Times? must not be empty.", 'timed-content');
            if ((!is_numeric($args['num_repeat'])) && ($args['recurr_type'] == "recurrence_duration_num_repeat"))
                $errors[] = __("Repeat How Many Times? must be a number.", 'timed-content');
            if (($args['end_date'] == "") && ($args['recurr_type'] == "recurrence_duration_end_date"))
                $errors[] = __("End Date must not be empty.", 'timed-content');        
            if (false === $instance_start) 
                $errors[] = __("Starting Date/Time must be valid.", 'timed-content');        
            if (false === $instance_end) 
                $errors[] = __("Ending Date/Time must be valid.", 'timed-content');        
            if ($instance_start > $instance_end) 
                $errors[] = __("Starting Date/Time must be before Ending Date/Time.", 'timed-content');        
            if (($instance_end > $end_date) && ($args['recurr_type'] == "recurrence_duration_end_date"))
                $errors[] = __("End Date must be after Ending Date/Time.", 'timed-content');        
        
            return $errors;
        }

        /**
         * Calculates the active periods for a Timed Content Rule
         *
         * @param array $args Array of Timed Content Rule parameters
         * 
         * @return array Array of active periods. Each value in the array describes an active period as
         *               an array itself with "start" and "end" keys and values that are either UNIX
         *               timestamps or human-readable dates, based on whether $args['human_readable']
         *               is set to true or false.
         */
        function getRulePeriods($args)
        {
            $active_periods = array();
            $period_count = 0;

            $human_readable = $args['human_readable'];
            $freq = $args['freq'];
            $timezone = $args['timezone'];
            $recurr_type = $args['recurr_type'];
            $num_repeat = intval($args['num_repeat']);
            $end_date = $args['end_date'];
            $days_of_week = $args['days_of_week'];
            $interval_multiplier = $args['interval_multiplier'];
            $instance_start_date = $args['instance_start']['date'];
            $instance_start_time = $args['instance_start']['time'];
            $instance_end_date = $args['instance_end']['date'];
            $instance_end_time = $args['instance_end']['time'];
            $monthly_pattern = $args['monthly_pattern'];
            $monthly_pattern_ord = $args['monthly_pattern_ord'];
            $monthly_pattern_day = $args['monthly_pattern_day'];
            $exceptions_dates = $args['exceptions_dates'];
            
            add_filter('date_i18n', array(&$this, "fixDateI18n"), 10, 4);
            $temp_tz = date_default_timezone_get();
            date_default_timezone_set($timezone);
            $right_now_t = current_time('timestamp', 1);

            // use debug parameter if current user is allowed to edit the post
            if (isset($_GET['tctest']) && current_user_can("edit_post", $post->post_id)) {
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $_GET['tctest']);
                if($dt != false) $right_now_t = $dt->getTimestamp();
            }
            
            $instance_start = strtotime($this->dateTimeToEnglish($instance_start_date, $instance_start_time) . " " . $timezone);    // Beginning of first occurrence
            $instance_end = strtotime($this->dateTimeToEnglish($instance_end_date, $instance_end_time) . " " . $timezone);            // End of first occurrence
            $current = $instance_start;
            $end_current = $instance_end;

            if ($recurr_type == "recurrence_duration_num_repeat")
                $last_occurrence_start = strtotime(TIMED_CONTENT_END_TIME);                            
            else
                $last_occurrence_start = strtotime($this->dateTimeToEnglish($end_date, $instance_start_time) . " " . $timezone);

            if ($human_readable == true) {
                $active_periods[$period_count]["start"] = date_i18n(TIMED_CONTENT_DT_FORMAT, $current);
                $active_periods[$period_count]["end"] = date_i18n(TIMED_CONTENT_DT_FORMAT, $end_current);
                if ($right_now_t < $current) {
                    $active_periods[$period_count]["status"] = "upcoming";
                    $active_periods[$period_count]["time"] = sprintf(_x('%s from now.', 'Human readable time difference', 'timed-content'), human_time_diff($right_now_t, $current));
                } elseif (($current <= $right_now_t) && ($right_now_t <= $end_current)) {
                    $active_periods[$period_count]["status"] = "active";
                    $active_periods[$period_count]["time"] = __("Right now!", 'timed-content');
                } else {
                    $active_periods[$period_count]["status"] = "expired";
                    $active_periods[$period_count]["time"] = sprintf(_x('%s ago.', 'Human readable time difference', 'timed-content'), human_time_diff($end_current, $right_now_t));
                }
            } else {
                $active_periods[$period_count]["start"] = $current;
                $active_periods[$period_count]["end"] = $end_current;
            }
            $period_count++;

            if ($recurr_type == "recurrence_duration_end_date")
                $loop_test = "return (\$current < \$last_occurrence_start);";
            else
                $loop_test = "return (\$period_count <= \$num_repeat);";
                
            while (eval($loop_test)) {
                $temp_current = "";
                if ($freq == 0)
                    $current = $this->getNextHour($current, $interval_multiplier);
                elseif ($freq == 1)
                    $current = $this->getNextDay($current, $interval_multiplier);
                elseif ($freq == 2)
                    $current = $this->getNextWeek($current, $interval_multiplier, $days_of_week);
                elseif ($freq == 3) {
                    $current = $this->getNextMonth($current, $instance_start, $interval_multiplier);
                    $temp_current = $current;
                    if ($monthly_pattern == "yes")
                        $current = $this->getNthWeekdayOfMonth($current, $monthly_pattern_ord, $monthly_pattern_day);
                    else
                        $current = $temp_current;
                } elseif ($freq == 4) 
                    $current = $this->getNextYear($current, $interval_multiplier);

                $exception_period = false;
                if (is_array($exceptions_dates)) {
                    foreach ($exceptions_dates as $date) {
                        if (($current >= $date) && ($current < strtotime("+1 day", $date))) {
                            $exception_period = true;
                            break;
                        }
                    }
                }

                if ((eval($loop_test)) && (!($exception_period))) {
                    $end_current = $current + ($instance_end - $instance_start);
                    if ($human_readable == true) {
                        $active_periods[$period_count]["start"] = date_i18n(TIMED_CONTENT_DT_FORMAT, $current);
                        $active_periods[$period_count]["end"] = date_i18n(TIMED_CONTENT_DT_FORMAT, $end_current);
                        if ($right_now_t < $current) {
                            $active_periods[$period_count]["status"] = "upcoming";
                            $active_periods[$period_count]["time"] = sprintf(_x('%s from now.', 'Human readable time difference', 'timed-content'), human_time_diff($current, $right_now_t));
                        } elseif (($current <= $right_now_t) && ($right_now_t <= $end_current)) {
                            $active_periods[$period_count]["status"] = "active";
                            $active_periods[$period_count]["time"] = __("Right now!", 'timed-content');
                        } else {
                            $active_periods[$period_count]["status"] = "expired";
                            $active_periods[$period_count]["time"] = sprintf(_x('%s ago.', 'Human readable time difference', 'timed-content'), human_time_diff($end_current, $right_now_t));
                        }
                    } else {
                        $active_periods[$period_count]["start"] = $current;
                        $active_periods[$period_count]["end"] = $end_current;
                    }
                    if (!($exception_period))
                        $period_count++;
                }

            }
            date_default_timezone_set($temp_tz);
            remove_filter('date_i18n', array(&$this, "fixDateI18n"), 10, 4);
            return $active_periods;
        }

        /**
         * Wrapper for calling TimedContentPlugin::getRulePeriods() by the ID of a Timed Content Rule
         *
         * @param int  $ID             ID of the Timed Content Rule
         * @param bool $human_readable If true, the active periods are returned as a human-readable date/time
         *                             as defined by the constant TIMED_CONTENT_DT_FORMAT; otherwise, they are
         *                             returned as UNIX timestamps.
         * 
         * @return array Array of active periods
         */
        function getRulePeriodsById($ID, $human_readable = false)
        {
            if (TIMED_CONTENT_RULE_TYPE != get_post_type($ID))
                return array();

            $prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
            $args = array();
            
            $args['human_readable'] = (bool) $human_readable;
            $args['freq'] = get_post_meta($ID, $prefix . 'frequency', true);
            $args['timezone'] = get_post_meta($ID, $prefix . 'timezone', true);
            $args['recurr_type'] = get_post_meta($ID, $prefix . 'recurrence_duration', true);
            $args['num_repeat'] = get_post_meta($ID, $prefix . 'recurrence_duration_num_repeat', true);
            $args['end_date'] = get_post_meta($ID, $prefix . 'recurrence_duration_end_date', true);
            $args['days_of_week'] = get_post_meta($ID, $prefix . 'weekly_days_of_week_to_repeat', true);
            if ($args['freq'] == 0) $args['interval_multiplier'] = get_post_meta($ID, $prefix . 'hourly_num_of_hours', true);
            if ($args['freq'] == 1) $args['interval_multiplier'] = get_post_meta($ID, $prefix . 'daily_num_of_days', true);
            if ($args['freq'] == 2) $args['interval_multiplier'] = get_post_meta($ID, $prefix . 'weekly_num_of_weeks', true);
            if ($args['freq'] == 3) $args['interval_multiplier'] = get_post_meta($ID, $prefix . 'monthly_num_of_months', true);
            if ($args['freq'] == 4) $args['interval_multiplier'] = get_post_meta($ID, $prefix . 'yearly_num_of_years', true);
            $args['instance_start'] = get_post_meta($ID, $prefix . 'instance_start', true);
            $args['instance_end'] = get_post_meta($ID, $prefix . 'instance_end', true);
            $args['monthly_pattern'] = get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month', true);
            $args['monthly_pattern_ord'] = get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month_nth', true);
            $args['monthly_pattern_day'] = get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month_weekday', true);
            $args['exceptions_dates'] = get_post_meta($ID, $prefix . 'exceptions_dates', true);

            return $this->getRulePeriods($args);
        }

        /**
         * Wrapper for calling TimedContentPlugin::getRulePeriods() based on the contents of the form fields
         * of the Add Timed Content Rule and Edit Timed Content Rule screens. Output is sent to output as JSON
         * 
         * @return void
         */
        function timedContentPluginGetRulePeriodsAjax()
        {
            if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
                $prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
                $args = array();
                
                $args['human_readable'] = (((isset($_POST[$prefix . 'human_readable'])) && ($_POST[$prefix . 'human_readable'] == 'true')) ? (bool)$_POST[$prefix . 'human_readable'] : false);
                $args['freq'] = $_POST[$prefix . 'frequency'];
                $args['timezone'] = $_POST[$prefix . 'timezone'];
                $args['recurr_type'] = $_POST[$prefix . 'recurrence_duration'];
                $args['num_repeat'] = $_POST[$prefix . 'recurrence_duration_num_repeat'];
                $args['end_date'] = $_POST[$prefix . 'recurrence_duration_end_date'];
                $args['days_of_week'] = (isset($_POST[$prefix . 'weekly_days_of_week_to_repeat']) ? $_POST[$prefix . 'weekly_days_of_week_to_repeat'] : array());
                $args['interval_multiplier'] = $_POST[$prefix . 'interval_multiplier'];
                $args['instance_start'] = $_POST[$prefix . 'instance_start'];
                $args['instance_end'] = $_POST[$prefix . 'instance_end'];
                $args['monthly_pattern'] = $_POST[$prefix . 'monthly_nth_weekday_of_month'];
                $args['monthly_pattern_ord'] = $_POST[$prefix . 'monthly_nth_weekday_of_month_nth'];
                $args['monthly_pattern_day'] = $_POST[$prefix . 'monthly_nth_weekday_of_month_weekday'];
                $args['exceptions_dates'] = (isset($_POST[$prefix . 'exceptions_dates']) ? $_POST[$prefix . 'exceptions_dates'] : array());

                $response = json_encode($this->getRulePeriods($args));
                
                // response output
                header("Content-Type: application/json");
                echo $response;
            }
            die();
        }

        /**
         * Returns a human-readable description of a Timed Content Rule
         *
         * @param array $args Array of Timed Content Rule parameters
         * 
         * @return string
         */
        function getScheduleDescription($args)
        {
            include "lib/Arrays_Definitions.php";

            $interval_multiplier = 1;
            $desc = "";
            
            $errors = $this->validate($args);
            if ($errors) {
                $messages = "<div class=\"tcr-warning\">\n";
                $messages .= "<p class=\"heading\">" . __("Warning!", 'timed-content') . "</p>\n";
                $messages .= "<p>" . __("Some problems have been detected.  Although you can still publish this rule, it may not work the way you expect.", 'timed-content') . "</p>\n";
                $messages .= "<ul>\n";
                foreach ($errors as $error)
                    $messages .= "    <li>" . $error . "</li>\n";
                $messages .= "</ul>\n";
                $messages .= "<p>" . __("Check that all of the conditions for this rule are correct, and use Show Projected Dates/Times to ensure your rule is working properly.", 'timed-content') . "</p>\n";
                $messages .= "</div>\n";
                return $messages;
            }
            
            if ($args['action'])
                $action = __("Show the content", 'timed-content');
            else
                $action = __("Hide the content", 'timed-content');
            $freq = $args['freq'];
            $timezone = $args['timezone'];
            $recurr_type = $args['recurr_type'];
            $num_repeat = intval($args['num_repeat']);
            $end_date = $args['end_date'];
            $days_of_week = $args['days_of_week'];
            $interval_multiplier = $args['interval_multiplier'];
            $instance_start_date = $args['instance_start']['date'];
            $instance_start_time = $args['instance_start']['time'];
            $instance_end_date = $args['instance_end']['date'];
            $instance_end_time = $args['instance_end']['time'];
            $monthly_pattern = $args['monthly_pattern'];
            $monthly_pattern_ord = $args['monthly_pattern_ord'];
            $monthly_pattern_day = $args['monthly_pattern_day'];
            $exceptions_dates = $args['exceptions_dates'];

            $desc = sprintf(_x('%1$s on %2$s @ %3$s until %4$s @ %5$s.', 'Perform action (%1$s) from date/time of first active period (%2$s @ %3$s) until date/time of last active period (%4$s @ %5$s).', 'timed-content'), $action, $instance_start_date, $instance_start_time, $instance_end_date, $instance_end_time);
            
            if ($freq == 0)
                $desc .= "&nbsp;" . sprintf(_n('Repeat this action every hour.', 'Repeat this action every %d hours.', $interval_multiplier, 'timed-content'), $interval_multiplier);
            elseif ($freq == 1)
                $desc .= "&nbsp;" . sprintf(_n('Repeat this action every day.', 'Repeat this action every %d days.', $interval_multiplier, 'timed-content'), $interval_multiplier);
            elseif ($freq == 2) {
                if (($days_of_week) && (is_array($days_of_week))) {
                    $days = array(); $days_list = "";
                    foreach ($days_of_week as $v)
                        $days[] = $timed_content_rule_days_array[$v];
                    switch (count($days))  {
                    case 1:
                        $days_list = sprintf(
                            _x('%1$s', 'List of one weekday', 'timed-content'),
                            $days[0]
                        );
                        break;
                    case 2:
                        $days_list = sprintf(
                            _x('%1$s and %2$s', 'List of two weekdays', 'timed-content'),
                            $days[0], $days[1]
                        );
                        break;
                    case 3:
                        $days_list = sprintf(
                            _x('%1$s, %2$s, and %3$s', 'List of three weekdays', 'timed-content'),
                            $days[0], $days[1], $days[2]
                        );
                        break;
                    case 4:
                        $days_list = sprintf(
                            _x('%1$s, %2$s, %3$s, and %4$s', 'List of four weekdays', 'timed-content'),
                            $days[0], $days[1], $days[2], $days[3]
                        );
                        break;
                    case 5:
                        $days_list = sprintf(
                            _x('%1$s, %2$s, %3$s, %4$s, and %5$s', 'List of five weekdays', 'timed-content'),
                            $days[0], $days[1], $days[2], $days[3], $days[4]
                        );
                        break;
                    case 6:
                        $days_list = sprintf(
                            _x('%1$s, %2$s, %3$s, %4$s, %5$s, and %6$s', 'List of six weekdays', 'timed-content'),
                            $days[0], $days[1], $days[2], $days[3], $days[4], $days[5]
                        );
                        break;
                    case 7:
                        $days_list = sprintf(
                            _x('%1$s, %2$s, %3$s, %4$s, %5$s, %6$s, and %7$s', 'List of all weekdays', 'timed-content'),
                            $days[0], $days[1], $days[2], $days[3], $days[4], $days[5], $days[6]
                        );
                        break;
                    }
                    if ($interval_multiplier == 1)
                        $desc .= "&nbsp;" . sprintf(_x('Repeat this action every week on %s.', 'List the weekdays to repeat the rule when frequency is every week. %s is the list of weekdays.', 'timed-content'), $days_list);
                    else
                        $desc .= "&nbsp;" . sprintf(_x('Repeat this action every %1$d weeks on %2$s.', 'List the weekdays to repeat the rule when frequency is every %1$d weeks. %2$s is the list of weekdays.', 'timed-content'), $interval_multiplier, $days_list);
                } else
                    $desc .= "&nbsp;" . sprintf(_n('Repeat this action every week.', 'Repeat this action every %d weeks.', $interval_multiplier, 'timed-content'), $interval_multiplier);
            } elseif ($freq == 3) {
                if ($monthly_pattern == "yes") {
                    if ($interval_multiplier == 1)
                        $desc .= "&nbsp;" . sprintf(_x('Repeat this action every month on the %1$s %2$s of the month.', "Example: 'Repeat this action every month on the second Friday of the month.'", 'timed-content'), $timed_content_rule_ordinal_array[$monthly_pattern_ord], $timed_content_rule_ordinal_days_array[$monthly_pattern_day]);
                    else
                        $desc .= "&nbsp;" . sprintf(_x('Repeat this action every %1$d months on the %2$s %3$s of the month.', "Example: 'Repeat this action every 2 months on the second Friday of the month.'", 'timed-content'), $interval_multiplier, $timed_content_rule_ordinal_array[$monthly_pattern_ord], $timed_content_rule_ordinal_days_array[$monthly_pattern_day]);
                } else
                    $desc .=  "&nbsp;" . sprintf(_n('Repeat this action every month.', 'Repeat this action every %d months.', $interval_multiplier, 'timed-content'), $interval_multiplier);
            } elseif ($freq == 4)
                $desc .= "&nbsp;" . sprintf(_n('Repeat this action every year.', 'Repeat this action every %d years.', $interval_multiplier, 'timed-content'), $interval_multiplier);

            if ($recurr_type == "recurrence_duration_num_repeat")
                $desc .= "&nbsp;" . sprintf(_n('This rule will be active for 1 repetition.', 'This rule will be active for %d repetitions.', $num_repeat, 'timed-content'), $num_repeat);
            elseif ($recurr_type == "recurrence_duration_end_date")
                $desc .=  "&nbsp;" . sprintf(__('This rule will be active until %s.', 'timed-content'), $end_date);

            if (($exceptions_dates) && (is_array($exceptions_dates))) {
                sort($exceptions_dates, SORT_NUMERIC);
                $exceptions_dates = array_unique($exceptions_dates);
                if ($exceptions_dates[0] == 0)
                    array_shift($exceptions_dates);
                if (!empty($exceptions_dates)) {
                    $formatted_dates = array();
                    foreach ($exceptions_dates as $a_date)
                        $formatted_dates[] = date(_x("F j, Y", "Date format for schedule description", 'timed-content'), $a_date);
                    $desc .=  "&nbsp;" . sprintf(__('This rule will be inactive on the following dates: %s.', 'timed-content'), join(", ", $formatted_dates));
                }
            }

            $desc .=  "&nbsp;" . sprintf(__('All times are in the %s timezone.', 'timed-content'), $timezone);
            return $desc;
        }

        /**
         * Wrapper for calling TimedContentPlugin::getScheduleDescription() by the ID of a Timed Content Rule
         *
         * @param int $ID ID of the Timed Content Rule
         * 
         * @return string Description of the schedule
         */
        function getScheduleDescriptionById($ID)
        {
            global $timed_content_rule_occurrence_custom_fields,
                   $timed_content_rule_pattern_custom_fields,
                   $timed_content_rule_recurrence_custom_fields,
                   $timed_content_rule_exceptions_custom_fields;
            $defaults = array();

            foreach ($timed_content_rule_occurrence_custom_fields as $field) {
                $defaults[$field['name']] = $field['default'];
            }
            foreach ($timed_content_rule_pattern_custom_fields as $field) {
                $defaults[$field['name']] = $field['default'];
            }
            foreach ($timed_content_rule_recurrence_custom_fields as $field) {
                $defaults[$field['name']] = $field['default'];
            }
            foreach ($timed_content_rule_exceptions_custom_fields as $field) {
                $defaults[$field['name']] = $field['default'];
            }

            $prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
            $args = array();
            
            $args['action'] = (false === get_post_meta($ID, $prefix . 'action', true) ? $defaults['action'] : get_post_meta($ID, $prefix . 'action', true));
            $args['freq'] = (false === get_post_meta($ID, $prefix . 'frequency', true) ? $defaults['frequency'] : get_post_meta($ID, $prefix . 'frequency', true));
            $args['timezone'] = (false === get_post_meta($ID, $prefix . 'timezone', true) ? $defaults['timezone'] : get_post_meta($ID, $prefix . 'timezone', true));
            $args['recurr_type'] = (false === get_post_meta($ID, $prefix . 'recurrence_duration', true) ? $defaults['recurrence_duration'] : get_post_meta($ID, $prefix . 'recurrence_duration', true));
            $args['num_repeat'] = (false === get_post_meta($ID, $prefix . 'recurrence_duration_num_repeat', true) ? $defaults['recurrence_duration_num_repeat'] : get_post_meta($ID, $prefix . 'recurrence_duration_num_repeat', true));
            $args['end_date'] = (false === get_post_meta($ID, $prefix . 'recurrence_duration_end_date', true) ? $defaults['recurrence_duration_end_date'] : get_post_meta($ID, $prefix . 'recurrence_duration_end_date', true));
            $args['days_of_week'] = (false === get_post_meta($ID, $prefix . 'weekly_days_of_week_to_repeat', true) ? $defaults['weekly_days_of_week_to_repeat'] : get_post_meta($ID, $prefix . 'weekly_days_of_week_to_repeat', true));
            if ($args['freq'] == 0) $args['interval_multiplier'] = (false === get_post_meta($ID, $prefix . 'hourly_num_of_hours', true) ? $defaults['hourly_num_of_hours'] : get_post_meta($ID, $prefix . 'hourly_num_of_hours', true));
            if ($args['freq'] == 1) $args['interval_multiplier'] = (false === get_post_meta($ID, $prefix . 'daily_num_of_days', true) ? $defaults['daily_num_of_days'] : get_post_meta($ID, $prefix . 'daily_num_of_days', true));
            if ($args['freq'] == 2) $args['interval_multiplier'] = (false === get_post_meta($ID, $prefix . 'weekly_num_of_weeks', true) ? $defaults['weekly_num_of_weeks'] : get_post_meta($ID, $prefix . 'weekly_num_of_weeks', true));
            if ($args['freq'] == 3) $args['interval_multiplier'] = (false === get_post_meta($ID, $prefix . 'monthly_num_of_months', true) ? $defaults['monthly_num_of_months'] : get_post_meta($ID, $prefix . 'monthly_num_of_months', true));
            if ($args['freq'] == 4) $args['interval_multiplier'] = (false === get_post_meta($ID, $prefix . 'yearly_num_of_years', true) ? $defaults['yearly_num_of_years'] : get_post_meta($ID, $prefix . 'yearly_num_of_years', true));
            $args['instance_start'] = (false === get_post_meta($ID, $prefix . 'instance_start', true) ? $defaults['instance_start'] : get_post_meta($ID, $prefix . 'instance_start', true));
            $args['instance_end'] = (false === get_post_meta($ID, $prefix . 'instance_end', true) ? $defaults['instance_end'] : get_post_meta($ID, $prefix . 'instance_end', true));
            $args['monthly_pattern'] = (false === get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month', true) ? $defaults['monthly_nth_weekday_of_month'] : get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month', true));
            $args['monthly_pattern_ord'] = (false === get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month_nth', true) ? $defaults['monthly_nth_weekday_of_month_nth'] : get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month_nth', true));
            $args['monthly_pattern_day'] = (false === get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month_weekday', true) ? $defaults['monthly_nth_weekday_of_month_weekday'] : get_post_meta($ID, $prefix . 'monthly_nth_weekday_of_month_weekday', true));
            $args['exceptions_dates'] = (false === get_post_meta($ID, $prefix . 'exceptions_dates', true) ? $defaults['exceptions_dates'] : get_post_meta($ID, $prefix . 'exceptions_dates', true));

            return $this->getScheduleDescription($args);
        }

        /**
         * Wrapper for calling TimedContentPlugin::getRulePeriods() based on the contents of the form fields
         * of the Add Timed Content Rule and Edit Timed Content Rule screens.  Output is sent to output as plain text
         * 
         * @return void
         */
        function timedContentPluginGetScheduleDescriptionAjax()
        {
            if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
                $prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
                $args = array();
                
                $args['action'] = $_POST[$prefix . 'action'];
                $args['freq'] = $_POST[$prefix . 'frequency'];
                $args['timezone'] = $_POST[$prefix . 'timezone'];
                $args['recurr_type'] = $_POST[$prefix . 'recurrence_duration'];
                $args['num_repeat'] = $_POST[$prefix . 'recurrence_duration_num_repeat'];
                $args['end_date'] = $_POST[$prefix . 'recurrence_duration_end_date'];
                $args['days_of_week'] = (isset($_POST[$prefix . 'weekly_days_of_week_to_repeat']) ? $_POST[$prefix . 'weekly_days_of_week_to_repeat'] : array());
                $args['interval_multiplier'] = $_POST[$prefix . 'interval_multiplier'];
                $args['instance_start'] = $_POST[$prefix . 'instance_start'];
                $args['instance_end'] = $_POST[$prefix . 'instance_end'];
                $args['monthly_pattern'] = $_POST[$prefix . 'monthly_nth_weekday_of_month'];
                $args['monthly_pattern_ord'] = $_POST[$prefix . 'monthly_nth_weekday_of_month_nth'];
                $args['monthly_pattern_day'] = $_POST[$prefix . 'monthly_nth_weekday_of_month_weekday'];
                $args['exceptions_dates'] = (isset($_POST[$prefix . 'exceptions_dates']) ? $_POST[$prefix . 'exceptions_dates'] : array());

                $response = $this->getScheduleDescription($args);
                
                // response output
                header("Content-Type: text/plain");
                echo $response;
            }
            die();
        }

        /**
         * Processes the [timed-content-client] shortcode.
         *
         * @param array $atts    Attributes of the shortcode
         * @param mixed $content Content inside the shortcode
         * 
         * @return string The generated HTML output
         */
        function clientShowHTML($atts, $content = null)
        {
            $show_attr = "";
            $hide_attr = "";
            extract(shortcode_atts(array('show' => '0:00:000' , 'hide' => '0:00:000' , 'display' => 'div'), $atts));
            
            // Initialize show/hide arguments 
            $s_min = 0; $s_sec = 0; $s_fade = 0;
            $h_min = 0; $h_sec = 0; $h_fade = 0;
            @list($s_min, $s_sec, $s_fade) = explode(":", $show);
            @list($h_min, $h_sec, $h_fade) = explode(":", $hide);

            if (((int)$s_min + (int)$s_sec) > 0)
                $show_attr = "_show_" . $s_min . "_" . $s_sec . "_" . $s_fade;
            if (((int)$h_min + (int)$h_sec) > 0)
                $hide_attr = "_hide_" . $h_min . "_" . $h_sec . "_" . $h_fade;
            
            $the_class = TIMED_CONTENT_CLIENT_TAG . $show_attr . $hide_attr ;
            $the_tag = ($display == "div" ? "div" : "span");

            $the_filter = "timed_content_filter";
            $the_filter = apply_filters("timed_content_filter_override", $the_filter);

            $the_HTML = "<"
                . $the_tag
                . " class='"
                . $the_class
                . "'"
                . (($show_attr != "") ? " style='display: none;'" : "") .">"
                . str_replace(']]>', ']]&gt;', apply_filters($the_filter, $content))
                . "</" . $the_tag . ">";

            return $the_HTML;
        }

        /**
         * Processes the [timed-content-server] shortcode.
         *
         * @param array $atts    Attributes of the shortcode
         * @param mixed $content Content inside the shortcode
         * 
         * @return string
         */
        function serverShowHTML($atts, $content = null)
        {
            global $post;
            extract(shortcode_atts(array('show' => TIMED_CONTENT_ZERO_TIME, 'hide' => TIMED_CONTENT_END_TIME, 'debug' => 'false'), $atts));
            $show_t = strtotime($this->dateTimeToEnglish($show));
            $hide_t = strtotime($this->dateTimeToEnglish($hide));
            $right_now_t = current_time('timestamp', 1);
            $debug_message = "";
            
            // use debug parameter if current user is allowed to edit the post
            if (isset($_GET['tctest']) && current_user_can("edit_post", $post->post_id)) {
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $_GET['tctest']);
                if($dt != false) $right_now_t = $dt->getTimestamp();
            }
            
            $the_filter = "timed_content_filter";
            $the_filter = apply_filters("timed_content_filter_override", $the_filter);

            if (($debug == "true") && (current_user_can("edit_post", $post->post_id))) {
                add_filter('date_i18n', array(&$this, "fixDateI18n"), 10, 4);
                $temp_tz = date_default_timezone_get();
                date_default_timezone_set(get_option('timezone_string'));

                $right_now = date_i18n(TIMED_CONTENT_DT_FORMAT, $right_now_t);

                if ($show_t > $right_now_t)
                    $show_diff_str = sprintf(_x('%s from now.', 'Human readable time difference', 'timed-content'), human_time_diff($show_t, $right_now_t));
                else
                    $show_diff_str = sprintf(_x('%s ago.', 'Human readable time difference', 'timed-content'), human_time_diff($show_t, $right_now_t));
                if ($hide_t > $right_now_t)
                    $hide_diff_str = sprintf(_x('%s from now.', 'Human readable time difference', 'timed-content'), human_time_diff($hide_t, $right_now_t));
                else
                    $hide_diff_str = sprintf(_x('%s ago.', 'Human readable time difference', 'timed-content'), human_time_diff($hide_t, $right_now_t));

                $debug_message = "<div class=\"tcr-warning\">\n";
                $debug_message .= "<p class=\"heading\">" . _x("Notice", "Noun", 'timed-content') . "</p>\n";
                $debug_message .= "<p>" . sprintf(__('Debugging has been turned on for a %1$s shortcode on this Post/Page. Only website users who are currently logged in and can edit this Post/Page will see this.  To turn off this message, remove the %2$s attribute from the shortcode.', 'timed-content'), "<code>[timed-content-server]</code>", "<code>debug</code>") . "</p>\n";

                if ($show == TIMED_CONTENT_ZERO_TIME)
                    $debug_message .= "<p>" . sprintf(__('The %s attribute is not set.', 'timed-content'), "<code>show</code>") . "</p>\n";
                else
                    $debug_message .= "<p>" . sprintf(__('The %s attribute is currently set to', 'timed-content'), "<code>show</code>") . ": " . $show . ",<br />\n "
                                    . __('The Timed Content plugin thinks the intended date/time is', 'timed-content') . ": " . date_i18n(TIMED_CONTENT_DT_FORMAT, $show_t)
                                    . " (" . $show_diff_str . ")</p>\n";

                if ($hide == TIMED_CONTENT_END_TIME)
                    $debug_message .= "<p>" . sprintf(__('The %s attribute is not set.', 'timed-content'), "<code>hide</code>") . "</p>\n";
                else
                    $debug_message .= "<p>" . sprintf(__('The %s attribute is currently set to', 'timed-content'), "<code>hide</code>") . ": " . $hide . ",<br />\n"
                                    . __('The Timed Content plugin thinks the intended date/time is', 'timed-content') . ": " . date_i18n(TIMED_CONTENT_DT_FORMAT, $hide_t)
                                    . " (" . $hide_diff_str . ").</p>\n";

                $debug_message .= "<p>" . __('Current Date/Time:', 'timed-content') . "&nbsp;" . $right_now . "<br />\n";
                $debug_message .= __('Content Filter:', 'timed-content') . "&nbsp;" . $the_filter . "</p>\n";
                $debug_message .= "<p>" . _x('Content:', "Noun", 'timed-content') . "&nbsp;" . $content . "</p>\n";

                $debug_message .= "</div>\n";

                date_default_timezone_set($temp_tz);
                remove_filter('date_i18n', array(&$this, "fixDateI18n"), 10, 4);
            }


            if (($show_t <= $right_now_t) && ($right_now_t <= $hide_t)) {
                do_action("timed_content_server_show", $post->ID, $show, $hide, $content);
                return $debug_message . str_replace(']]>', ']]&gt;', apply_filters($the_filter, $content)) . "\n";
            } else {
                do_action("timed_content_server_hide", $post->ID, $show, $hide, $content);
                return $debug_message . "\n";
            }

        }

        /**
         * Processes the [timed-content-rule] shortcode.
         *
         * @param array $atts    Attributes of the shortcode
         * @param null  $content Content inside the shortcode
         * 
         * @return string
         */
        function rulesShowHTML($atts, $content = null)
        {
            global $post;
            extract(shortcode_atts(array('id' => 0), $atts));
            if (!is_numeric($id)) {
                $page = get_page_by_title($id, OBJECT, TIMED_CONTENT_RULE_TYPE);
                if ($page == null) return;
                $id = $page->ID;
            }
            if (TIMED_CONTENT_RULE_TYPE != get_post_type($id)) return;

            $prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
            $right_now_t = current_time('timestamp', 1);
            $rule_is_active = false;

            // use debug parameter if current user is allowed to edit the post
            if (isset($_GET['tctest']) && current_user_can("edit_post", $post->post_id)) {
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $_GET['tctest']);
                if($dt != false) $right_now_t = $dt->getTimestamp();
            }
            
            $active_periods = $this->getRulePeriodsById($id, false);
            $action_is_show = (bool) get_post_meta($id, $prefix . 'action', true);
            
            foreach ($active_periods as $period) {
                if (($period['start'] <= $right_now_t) && ($right_now_t <= $period['end'])) {
                    $rule_is_active = true;
                    break;
                }
            }

            $the_filter = "timed_content_filter";
            $the_filter = apply_filters("timed_content_filter_override", $the_filter);

            if ((($rule_is_active == true) && ($action_is_show == true)) || (($rule_is_active == false) && ($action_is_show == false))) {
                do_action("timed_content_rule_show", $post->ID, $id, $content);
                return str_replace(']]>', ']]&gt;', apply_filters($the_filter, $content));
            } else {
                do_action("timed_content_rule_hide", $post->ID, $id, $content);
                return "";
            }
        }

        /**
         * Enqueues the JavaScript code necessary for the functionality of the [timed-content-client] shortcode.
         * 
         * @return void
         */
        function addHeaderCode()
        {
            if (!is_admin()) {
                wp_enqueue_style('timed-content-css', TIMED_CONTENT_CSS, false, TIMED_CONTENT_VERSION);
                wp_enqueue_script('timed-content_js', TIMED_CONTENT_PLUGIN_URL . '/js/timed-content.js', array('jquery'), TIMED_CONTENT_VERSION);
            }
        }

        /**
         * Enqueues the CSS code necessary for custom icons for the Timed Content Rules
         * management screens for WP 3.7.1 and under.  Echo'd to output.
         * 
         * @return void
         */
        function addPostTypeIcons37()
        {
            ?>
            <style type="text/css" media="screen">
                #menu-posts-<?php echo TIMED_CONTENT_RULE_TYPE; ?> .wp-menu-image {
                    background: url(<?php echo TIMED_CONTENT_PLUGIN_URL; ?>/img/clock_icon.png) no-repeat 6px 6px !important;
                }
                #menu-posts-<?php echo TIMED_CONTENT_RULE_TYPE; ?>:hover .wp-menu-image, #menu-posts-<?php echo TIMED_CONTENT_RULE_TYPE; ?>.wp-has-current-submenu .wp-menu-image {
                    background-position: -22px 6px !important;
                }
                #icon-edit.icon32-posts-<?php echo TIMED_CONTENT_RULE_TYPE; ?> {background: url(<?php echo TIMED_CONTENT_PLUGIN_URL; ?>/img/clock_32x32.png) no-repeat;}
            </style>
            <?php
        }

        /**
         * Enqueues the CSS code necessary for custom icons for the Timed Content Rules management screens
         * and the TinyMCE editor.  Echo'd to output.
         * 
         * @return void
         */
        function addPostTypeIcons()
        {
            wp_enqueue_style('ca-aliencyborg-dashicons', TIMED_CONTENT_CSS_DASHICONS, false, TIMED_CONTENT_VERSION);
            ?>
            <style type="text/css" media="screen">
                #adminmenu #menu-posts-<?php echo TIMED_CONTENT_RULE_TYPE; ?>.menu-icon-post div.wp-menu-image:before {
                    font-family: 'ca-aliencyborg-dashicons' !important;
                    content: '\e601';
                }
                #dashboard_right_now li.<?php echo TIMED_CONTENT_RULE_TYPE; ?>-count a:before {
                    font-family: 'ca-aliencyborg-dashicons' !important;
                    content: '\e601';
                }
                .mce-i-timed_content:before {
                    font: 400 24px/1 'ca-aliencyborg-dashicons' !important;
                    padding: 0;
                    vertical-align: top;
                    margin-left: -2px;
                    padding-right: 2px;
                    content: '\e601';
                }
            </style>
            <?php
        }
 
        /**
         * Enqueues the JavaScript code necessary for the functionality of the Timed Content Rules management screens.
         * 
         * @return void
         */
        function addAdminHeaderCode()
        {
            if ((isset($_GET['post_type']) && $_GET['post_type'] == TIMED_CONTENT_RULE_TYPE)
                || (isset($post_type) && $post_type == TIMED_CONTENT_RULE_TYPE)
                || (isset($_GET['post']) && get_post_type($_GET['post']) == TIMED_CONTENT_RULE_TYPE)
            ) {
                wp_enqueue_style('thickbox');
                wp_enqueue_style('timed-content-css', TIMED_CONTENT_CSS, false, TIMED_CONTENT_VERSION);
                // Enqueue the JavaScript file that manages the meta box UI
                wp_enqueue_script('timed-content-admin_js', TIMED_CONTENT_PLUGIN_URL . '/js/timed-content-admin.js', array('jquery'), TIMED_CONTENT_VERSION);
                // Enqueue the JavaScript file that makes AJAX requests
                wp_enqueue_script('timed-content-ajax_js', TIMED_CONTENT_PLUGIN_URL . '/js/timed-content-ajax.js', array('jquery', 'thickbox'), TIMED_CONTENT_VERSION);

                // Set up local variables used in the Admin JavaScript file
                wp_localize_script(
                    'timed-content-admin_js',
                    'timedContentRuleAdmin',
                    array('no_exceptions_label' => __("- No exceptions set -", 'timed-content'))
                );

                // Set up local variables used in the AJAX JavaScript file
                wp_localize_script(
                    'timed-content-ajax_js',
                    'timedContentRuleAjax',
                    array(
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'start_label' => _x('Start', 'Scheduled Dates/Times dialog - Beginning of active period table header', 'timed-content'),
                        'end_label' => _x('End', 'Scheduled Dates/Times dialog - End of active period table header', 'timed-content'),
                        'dialog_label' => _x('Scheduled Dates/Times', 'Scheduled Dates/Times dialog - dialog header', 'timed-content'),
                        'button_loading_label' => __('Calculating Dates/Times', 'timed-content'),
                        'button_finished_label' => __('Show Projected Dates/Times', 'timed-content'),
                        'dialog_width' => 800,
                        'dialog_height' => 500,
                        'error' => __("Error", 'timed-content'),
                        'error_desc' => __("Something unexpected has happened along the way.  The specific details are below:", 'timed-content')
                    )
                );
            }
        }

        /**
         *  Initializes the TinyMCE plugin bundled with this Wordpress plugin
         * 
         * @return void
         */
        function initTinyMCEPlugin()
        {
            if ((!current_user_can('edit_posts')) && (!current_user_can('edit_pages')))
                return;
                     
            // Add only in Rich Editor mode
            if (get_user_option('rich_editing') == 'true') {
                add_filter("mce_external_plugins", array(&$this, "addTimedContentTinyMCEPlugin"));
                add_filter("mce_buttons", array(&$this, "registerTinyMCEButton"));
            }
        }

        /**
         * Sets up variables to use in the TinyMCE plugin's plugin.js.
         *
         * @return void
         */
        function setTinyMCEPluginVars()
        {
            global $wp_version;
            if ((!current_user_can('edit_posts')) && (!current_user_can('edit_pages')))
                return;

            // Add only in Rich Editor mode
            if (get_user_option('rich_editing') == 'true') {
                if (version_compare($wp_version, "3.8", "<"))
                    $image = "/clock.gif";
                else
                    $image = "";
                wp_localize_script(
                    'editor',
                    'timedContentAdminTinyMCEOptions',
                    array(
                        'version' => TIMED_CONTENT_VERSION,
                        'desc' => __("Add Timed Content shortcodes", 'timed-content'),
                        'image' => $image
                    )
                );
            }
        }

        /**
         * Sets up the button for the associated TinyMCE plugin for use in the editor menubar.
         * 
         * @param array $buttons Array of menu buttons already registered with TinyMCE
         * 
         * @return array The array of TinyMCE menu buttons with ours now loaded in as well
         */
        function registerTinyMCEButton($buttons)
        {
            array_push($buttons, "|", "timed_content");
            return $buttons;
        }

        /**
         * Loads the associated TinyMCE plugin into TinyMCE's plugin array
         *
         * @param array $plugin_array Array of plugins already registered with TinyMCE
         * 
         * @return array The array of TinyMCE plugins with ours now loaded in as well
         */
        function addTimedContentTinyMCEPlugin($plugin_array)
        {
            $plugin_array['timed_content'] = TIMED_CONTENT_PLUGIN_URL . "/tinymce_plugin/plugin.js";
            return $plugin_array;
        }

        /**
         * Generates JavaScript array of objects describing Timed Content Rules.  Used in the dialog box created by
         * TimedContentPlugin::timedContentPluginGetTinyMCEDialog().
         *
         * @return string Generated JavaScript array
         */
        function getRulesJS()
        {
            $the_js = "var rules = [\n";
            $args = array('post_type' => TIMED_CONTENT_RULE_TYPE, 'posts_per_page' => -1, 'post_status' => 'publish');
            $the_rules = get_posts($args);
            foreach ($the_rules as $rule) {
                $desc = $this->getScheduleDescriptionById($rule->ID);
                // Only add a rule if there's no errors or warnings
                if (false === strpos($desc, "tcr-warning"))
                    $the_js .= "    { 'ID': " . $rule->ID . ", 'title': '" . esc_js(((strlen($rule->post_title) > 0) ? $rule->post_title : _x("(no title)", "No Timed Content Rule title", "timed-content"))) . "', 'desc': '" . esc_js($desc) . "' },\n";
            }
            if (empty($the_rules))
                $the_js .= "    { 'ID': -999, 'title': ' ---- ', 'desc': '" .  __('No Timed Content Rules found', 'timed-content') . "' }\n";

            $the_js .= "];\n";
            return $the_js;
        }
        /**
         * Display a dialog box for this plugin's associated TinyMCE plugin.
         * Called from TinyMCE via AJAX.
         *
         * @return void
         */
        function timedContentPluginGetTinyMCEDialog()
        {
            include "lib/jquery-ui-datetime-i18n.php";

            wp_enqueue_style(TIMED_CONTENT_SLUG . '-jquery-ui-css', TIMED_CONTENT_JQUERY_UI_CSS);
            wp_enqueue_script('jquery-ui-datepicker');
            wp_register_style(TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-css', TIMED_CONTENT_JQUERY_UI_TIMEPICKER_CSS);
            wp_enqueue_style(TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-css');
            wp_register_script(TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-js', TIMED_CONTENT_JQUERY_UI_TIMEPICKER_JS, array('jquery', 'jquery-ui-datepicker'), TIMED_CONTENT_VERSION);
            wp_enqueue_script(TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-js');
            if (!(wp_script_is(TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js', 'registered'))) {
                wp_register_script(TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js', TIMED_CONTENT_PLUGIN_URL . "/js/content-protector-datetime-i18n.js", array('jquery', 'jquery-ui-datepicker', TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-js'), TIMED_CONTENT_VERSION);
                wp_enqueue_script(TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js');
                wp_localize_script(TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js', 'TimedContentJQDatepickerI18n', $jquery_ui_datetime_datepicker_i18n);
                wp_localize_script(TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js', 'TimedContentJQTimepickerI18n', $jquery_ui_datetime_timepicker_i18n);
            }

            ob_start();
            include "tinymce_plugin/dialog.php";
            $content = ob_get_contents();
            ob_end_clean();            
            echo $content;
            die();
        }

        /**
         * Adds support for i18n (internationalization)
         * 
         * @return void
         */
        function i18nInit()
        {
            $plugin_dir = basename(dirname(__FILE__)) . "/lang/";
            load_plugin_textdomain('timed-content', false, $plugin_dir);
        }
        
        /**
         * Add custom columns to the Timed Content Rules overview page
         *
         * @param array $defaults List of rules
         *
         * @return array Extended list of rules
         */
        function addDescColumnHead($defaults)
        {
            unset($defaults['date']);
            $defaults['description'] = __('Description', 'timed-content');
            $defaults['shortcode'] = __('Shortcode', 'timed-content');
            return $defaults;
        }

        /**
         * Display content associated with custom columns on the Timed Content Rules overview page
         *
         * @param string $column_name Name of the column to be displayed
         * @param int    $post_ID     ID of the Timed Content Rule being listed
         * 
         * @return void
         */
        function addDescColumnContent($column_name, $post_ID)
        {
            if ($column_name == 'shortcode') {
                echo '<code>[' . TIMED_CONTENT_RULE_TAG . ' id="' . $post_ID . '"]...[/' . TIMED_CONTENT_RULE_TAG . ']</code>';
            }
            if ($column_name == 'description') {
                $desc = $this->getScheduleDescriptionById($post_ID);
                if ($desc) {
                    echo '<em>' . $desc . '</em>';
                }
            }
        }

        /**
         * Display a count of Timed Content Rules in the Dashboard's Right Now
         * widget for Wordpress versions 3.7.1 and below
         *
         * @return void
         */
        function addRulesCount37()
        {
            if (!post_type_exists(TIMED_CONTENT_RULE_TYPE)) {
                return;
            }

            $num_posts = wp_count_posts(TIMED_CONTENT_RULE_TYPE);
            $num = number_format_i18n($num_posts->publish);
            $text = _n('Timed Content Rule', 'Timed Content Rules', intval($num_posts->publish), 'timed-content');
            if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
                $num = "<a href='edit.php?post_type=" . TIMED_CONTENT_RULE_TYPE . "'>" . $num . "</a>";
                $text = "<a href='edit.php?post_type=" . TIMED_CONTENT_RULE_TYPE . "'>" . $text . "</a>";
            }
            echo '<tr>';
            echo '<td class="first b b-' . TIMED_CONTENT_RULE_TYPE . '">' . $num . '</td>';
            echo '<td class="t ' . TIMED_CONTENT_RULE_TYPE . '">' . $text . '</td>';
            echo '</tr>';

            if ($num_posts->pending > 0) {
                $num = number_format_i18n($num_posts->pending);
                $text = _n('Timed Content Rule Pending', 'Timed Content Rules Pending', intval($num_posts->pending), 'timed-content');
                if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
                    $num = "<a href='edit.php?post_status=pending&post_type=" . TIMED_CONTENT_RULE_TYPE . "'>" . $num . "</a>";
                    $text = "<a href='edit.php?post_status=pending&post_type=" . TIMED_CONTENT_RULE_TYPE . "'>" . $text . "</a>";
                }
                echo '<tr>';
                echo '<td class="first b b-' . TIMED_CONTENT_RULE_TYPE . '">' . $num . '</td>';
                echo '<td class="t ' . TIMED_CONTENT_RULE_TYPE . '">' . $text . '</td>';
                echo '</tr>';
            }
        }

        /**
         * Display a count of Timed Content Rules in the Dashboard's Right Now widget
         *
         * @return void
         */
        function addRulesCount()
        {
            if (!post_type_exists(TIMED_CONTENT_RULE_TYPE)) {
                 return;
            }

            $num_posts = wp_count_posts(TIMED_CONTENT_RULE_TYPE);
            $num = number_format_i18n($num_posts->publish);
            $text = _n('Timed Content Rule', 'Timed Content Rules', intval($num_posts->publish), 'timed-content');
            if (current_user_can('edit_posts') || current_user_can('edit_pages'))
                echo "<a href='edit.php?post_type=" . TIMED_CONTENT_RULE_TYPE . "'>"
                    . '<li class="' . TIMED_CONTENT_RULE_TYPE . '-count">'
                    . $num
                    . ' '
                    . $text
                    . '</a></li>';

            if ($num_posts->pending > 0) {
                $num = number_format_i18n($num_posts->pending);
                $text = _n('Timed Content Rule Pending', 'Timed Content Rules Pending', intval($num_posts->pending), 'timed-content');
                if (current_user_can('edit_posts') || current_user_can('edit_pages'))
                    echo "<a href='edit.php?post_status=pending&post_type=" . TIMED_CONTENT_RULE_TYPE . "'>"
                        . '<li class="' . TIMED_CONTENT_RULE_TYPE . '-count">'
                        . $num
                        . ' '
                        . $text
                        . '</a></li>';
            }
        }
        
        /**
         * Setup custom fields
         *
         * @return void
         */
        function setUpCustomFields()
        {
            include "lib/customFields-settings.php";
            include "lib/customFieldsInterface.php";
            
            $scf = new customFieldsInterface(
                "timed_content_rule_schedule",
                __('Rule Description/Schedule', 'timed-content'), 
                "<div id=\"schedule_desc\" style=\"font-style: italic;\">"
                . (isset($_GET['post']) && (TIMED_CONTENT_RULE_TYPE === get_post_type($_GET['post'])) ? $this->getScheduleDescriptionById(intval($_GET['post'])) : $this->getScheduleDescriptionById(intval(0)))
                . "</div>"
                . "<div id=\"tcr-dialogHolder\" style=\"display:none;\"></div>"
                . "<div style=\"padding-top: 10px;\"><input type=\"button\" class=\"button button-primary\" id=\"timed_content_rule_test\" value=\"" . __('Show Projected Dates/Times', 'timed-content') . "\" /></div>",
                TIMED_CONTENT_RULE_POSTMETA_PREFIX,
                array(TIMED_CONTENT_RULE_TYPE),
                array()
            ); 
            $ocf = new customFieldsInterface(
                "timed_content_rule_initial_event",
                __('Action/Initial Event', 'timed-content'), 
                __('Set the action to be taken and when it should first run.', 'timed-content'), 
                TIMED_CONTENT_RULE_POSTMETA_PREFIX,
                array(TIMED_CONTENT_RULE_TYPE),
                $timed_content_rule_occurrence_custom_fields
            );
            $pcf = new customFieldsInterface(
                "timed_content_rule_recurrence",
                __('Repeating Pattern', 'timed-content'), 
                __('Set how often the action should repeat.', 'timed-content'), 
                TIMED_CONTENT_RULE_POSTMETA_PREFIX,
                array(TIMED_CONTENT_RULE_TYPE),
                $timed_content_rule_pattern_custom_fields
            );
            $rcf = new customFieldsInterface(
                "timed_content_rule_stop_condition",
                __('Stopping Condition', 'timed-content'),
                __('Set how long or how many times the action should occur.', 'timed-content'),
                TIMED_CONTENT_RULE_POSTMETA_PREFIX,
                array(TIMED_CONTENT_RULE_TYPE),
                $timed_content_rule_recurrence_custom_fields
            );
            $ecf = new customFieldsInterface(
                "timed_content_rule_exceptions",
                __('Exceptions', 'timed-content'),
                __('Set up any exceptions to this Timed Content Rule.', 'timed-content'),
                TIMED_CONTENT_RULE_POSTMETA_PREFIX,
                array(TIMED_CONTENT_RULE_TYPE),
                $timed_content_rule_exceptions_custom_fields
            );

            // Initially loaded at the top; defining this constant here means it can get i18n'd
            /* translators:  date/time format for debugging messages. http://ca2.php.net/manual/en/function.date.php */
            define("TIMED_CONTENT_DT_FORMAT", __("l, F jS, Y, g:i A T", 'timed-content'));
        }

    }

} //End Class TimedContentPlugin

// Initialize plugin
if (class_exists("TimedContentPlugin")) {
    $TimedContentPluginInstance = new TimedContentPlugin();
}

// Actions and Filters
if (isset($TimedContentPluginInstance)) {
    global $wp_version;

    add_filter('timed_content_filter', 'convert_smilies');
    add_filter('timed_content_filter', 'convert_chars');
    add_filter('timed_content_filter', 'prepend_attachment');
    add_filter('timed_content_filter', 'do_shortcode');
    
    add_action("plugins_loaded", array(&$TimedContentPluginInstance, "i18nInit"), 1);
    add_action("init", array(&$TimedContentPluginInstance, "timedContentRuleTypeInit"), 2);
    add_action("init", array(&$TimedContentPluginInstance, "setUpCustomFields"), 2);
    add_action("wp_head", array(&$TimedContentPluginInstance, "addHeaderCode"), 1);
    add_filter("manage_" . TIMED_CONTENT_RULE_TYPE . "_posts_columns", array(&$TimedContentPluginInstance, "addDescColumnHead"));
    add_action("manage_" . TIMED_CONTENT_RULE_TYPE . "_posts_custom_column", array(&$TimedContentPluginInstance, "addDescColumnContent"), 10, 2);
    add_action("admin_enqueue_scripts", array(&$TimedContentPluginInstance, "addAdminHeaderCode"), 1);
    add_action("admin_init", array(&$TimedContentPluginInstance, "setTinyMCEPluginVars"), 1);
    add_action("admin_init", array(&$TimedContentPluginInstance, "initTinyMCEPlugin"), 2);
    add_action('wp_ajax_timedContentPluginGetTinyMCEDialog', array(&$TimedContentPluginInstance, "timedContentPluginGetTinyMCEDialog"), 1);
    add_action('wp_ajax_timedContentPluginGetRulePeriodsAjax', array(&$TimedContentPluginInstance, "timedContentPluginGetRulePeriodsAjax"), 1);
    add_action('wp_ajax_timedContentPluginGetScheduleDescriptionAjax', array(&$TimedContentPluginInstance, "timedContentPluginGetScheduleDescriptionAjax"), 1);
    add_filter("post_updated_messages", array(&$TimedContentPluginInstance, "timedContentRuleUpdatedMessages"), 1);
    if (version_compare($wp_version, "3.8", ">=")) {
        add_action("dashboard_glance_items", array(&$TimedContentPluginInstance, "addRulesCount"));
        add_action("admin_head", array(&$TimedContentPluginInstance, "addPostTypeIcons"), 1);
    } else {
        add_action("right_now_content_table_end", array(&$TimedContentPluginInstance, "addRulesCount37"));
        add_action("admin_head", array(&$TimedContentPluginInstance, "addPostTypeIcons37"), 1);
    }

    add_shortcode(TIMED_CONTENT_CLIENT_TAG, array(&$TimedContentPluginInstance, "clientShowHTML"), 1);
    add_shortcode(TIMED_CONTENT_SERVER_TAG, array(&$TimedContentPluginInstance, "serverShowHTML"), 1);
    add_shortcode(TIMED_CONTENT_RULE_TAG, array(&$TimedContentPluginInstance, "rulesShowHTML"), 1);
    
    add_filter('pre_get_posts', array(&$TimedContentPluginInstance, "timedContentPreGetPosts"));
}
?>