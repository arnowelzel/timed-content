<?php

class TimedContentPlugin {
	var $rule_freq_array;
	var $rule_days_array;
	var $rule_ordinal_array;
	var $rule_ordinal_days_array;
	var $rule_occurrence_custom_fields;
	var $rule_pattern_custom_fields;
	var $rule_recurrence_custom_fields;
	var $rule_exceptions_custom_fields;
	var $meridiem;
	var $show_period;
	var $show_period_labels;
	var $show_leading_zero;
	var $jquery_ui_datetime_datepicker_i18n;
	var $jquery_ui_datetime_timepicker_i18n;
	var $current_timezone;

	/**
	 * Constructor
	 */
	function __construct() {
		add_filter( 'timed_content_filter', 'convert_smilies' );
		add_filter( 'timed_content_filter', 'convert_chars' );
		add_filter( 'timed_content_filter', 'prepend_attachment' );
		add_filter( 'timed_content_filter', 'do_shortcode' );
		add_filter( 'manage_' . TIMED_CONTENT_RULE_TYPE . '_posts_columns', array( $this, 'add_desc_column_head' ) );
		add_filter( 'pre_get_posts', array( $this, 'timed_content_pre_get_posts' ) );
		add_filter( 'post_updated_messages', array( $this, 'timed_content_rule_updated_messages' ), 1 );

		add_action( 'init', array( $this, 'init' ), 2 );
		add_action( 'wp_head', array( $this, 'add_header_code' ), 1 );
		add_action( 'manage_' . TIMED_CONTENT_RULE_TYPE . '_posts_custom_column', array( $this, 'add_desc_column_content' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_header_code' ), 1 );
		add_action( 'admin_init', array( $this, 'set_tinymce_plugin_vars' ), 1 );
		add_action( 'admin_init', array( $this, 'init_tinymce_plugin' ), 2 );
		add_action( 'wp_ajax_timedContentPluginGetTinyMCEDialog', array( $this, 'timed_content_plugin_get_tinymce_dialog' ), 1 );
		add_action( 'wp_ajax_timedContentPluginGetRulePeriodsAjax', array( $this, 'timed_content_plugin_get_rule_periods_ajax' ), 1 );
		add_action( 'wp_ajax_timedContentPluginGetScheduleDescriptionAjax', array( $this, 'timed_content_plugin_get_schedule_description_ajax' ), 1 );
		add_action( 'dashboard_glance_items', array( $this, 'add_rules_count' ) );
		add_action( 'admin_head', array( $this, 'add_post_type_icons' ), 1 );

		add_shortcode( TIMED_CONTENT_SHORTCODE_CLIENT, array( $this, 'client_show_html' ) );
		add_shortcode( TIMED_CONTENT_SHORTCODE_SERVER, array( $this, 'server_show_html' ) );
		add_shortcode( TIMED_CONTENT_SHORTCODE_RULE, array( $this, 'rules_show_html' ) );

		$this->set_format_timezone( date_default_timezone_get() );
	}

	/**
	 * Initialise plugin
	 */
	function init() {
		global $wp_locale;

		load_plugin_textdomain( 'timed-content', false, '/timed-content/lang/' );

		$this->rule_freq_array = array(
			0 => __( 'hourly', 'timed-content' ),
			1 => __( 'daily', 'timed-content' ),
			2 => __( 'weekly', 'timed-content' ),
			3 => __( 'monthly', 'timed-content' ),
			4 => __( 'yearly', 'timed-content' ),
		);

		$this->rule_days_array = array(
			0 => __( 'Sunday', 'timed-content' ),
			1 => __( 'Monday', 'timed-content' ),
			2 => __( 'Tuesday', 'timed-content' ),
			3 => __( 'Wednesday', 'timed-content' ),
			4 => __( 'Thursday', 'timed-content' ),
			5 => __( 'Friday', 'timed-content' ),
			6 => __( 'Saturday', 'timed-content' ),
		);

		$this->rule_ordinal_array = array(
			0 => __( 'first', 'timed-content' ),
			1 => __( 'second', 'timed-content' ),
			2 => __( 'third', 'timed-content' ),
			3 => __( 'fourth', 'timed-content' ),
			4 => __( 'last', 'timed-content' ),
		);

		$this->rule_ordinal_days_array = array(
			0 => __( 'Sunday', 'timed-content' ),
			1 => __( 'Monday', 'timed-content' ),
			2 => __( 'Tuesday', 'timed-content' ),
			3 => __( 'Wednesday', 'timed-content' ),
			4 => __( 'Thursday', 'timed-content' ),
			5 => __( 'Friday', 'timed-content' ),
			6 => __( 'Saturday', 'timed-content' ),
			7 => __( 'day', 'timed-content' ),
		);

		$this->jquery_ui_datetime_datepicker_i18n = array(
			'closeText'          => _x( 'Done', 'jQuery UI Datepicker Close label', 'timed-content' ), // Display text for close link
			'prevText'           => _x( 'Prev', 'jQuery UI Datepicker Previous label', 'timed-content' ), // Display text for previous month link
			'nextText'           => _x( 'Next', 'jQuery UI Datepicker Next label', 'timed-content' ), // Display text for next month link
			'currentText'        => _x( 'Today', 'jQuery UI Datepicker Today label', 'timed-content' ), // Display text for current month link
			'weekHeader'         => _x( 'Wk', 'jQuery UI Datepicker Week label', 'timed-content' ), // Column header for week of the year
			// Replace the text indices for the following arrays with 0-based arrays
			'monthNames'         => $this->strip_array_indices( $wp_locale->month ), // Names of months for drop-down and formatting
			'monthNamesShort'    => $this->strip_array_indices( $wp_locale->month_abbrev ), // For formatting
			'dayNames'           => $this->strip_array_indices( $wp_locale->weekday ), // For formatting
			'dayNamesShort'      => $this->strip_array_indices( $wp_locale->weekday_abbrev ), // For formatting
			'dayNamesMin'        => $this->strip_array_indices( $wp_locale->weekday_initial ), // Column headings for days starting at Sunday
			'dateFormat'         => 'yy-mm-dd',
			'firstDay'           => get_option( 'start_of_week' ),
			'isRTL'              => $wp_locale->is_rtl(),
			'showMonthAfterYear' => false, // True if the year select precedes month, false for month then year
			'yearSuffix'         => '', // Additional text to append to the year in the month headers
		);

		$tf = get_option( 'time_format' );
		if ( false !== strpos( $tf, 'A' ) ) {
			$this->meridiem           = array( $wp_locale->meridiem['AM'], $wp_locale->meridiem['PM'] );
			$this->show_period        = true;
			$this->show_period_labels = true;
			$this->show_leading_zero  = false;
		} elseif ( false !== strpos( $tf, 'a' ) ) {
			$this->meridiem           = array( $wp_locale->meridiem['am'], $wp_locale->meridiem['pm'] );
			$this->show_period        = true;
			$this->show_period_labels = true;
			$this->show_leading_zero  = false;
		} else {
			$this->meridiem           = array( '', '' );
			$this->show_period        = false;
			$this->show_period_labels = false;
			$this->show_leading_zero  = true;
		}

		$this->jquery_ui_datetime_timepicker_i18n = array(
			'hourText'           => _x( 'Hour', "jQuery UI Timepicker 'Hour' label", 'timed-content' ),
			'minuteText'         => _x( 'Minute', "jQuery UI Timepicker 'Minute' label", 'timed-content' ),
			'timeSeparator'      => _x( ':', 'jQuery UI Datepicker: Character used to separate hours and minutes in translated language', 'timed-content' ),
			'closeButtonText'    => _x( 'Done', "jQuery UI Timepicker 'Done' label", 'timed-content' ),
			'nowButtonText'      => _x( 'Now', "jQuery UI Timepicker 'Now' label", 'timed-content' ),
			'deselectButtonText' => _x( 'Deselect', "jQuery UI Timepicker 'Deselect' label", 'timed-content' ),
			'amPmText'           => array( '', '' ),
			'showPeriod'         => false,
			'showPeriodLabels'   => false,
			'showLeadingZero'    => false,
			'timeFormat'         => 'G:i',
		);

		$this->timed_content_rule_type_init();
		$this->setup_custom_fields();
	}

	/**
	 * Creates the Timed Content Rule post type and registers it with WordPress
	 */
	function timed_content_rule_type_init() {
		$labels = array(
			'name'               => _x( 'Timed Content rules', 'post type general name', 'timed-content' ),
			'singular_name'      => _x( 'Timed Content rule', 'post type singular name', 'timed-content' ),
			'add_new'            => _x(
				'Add new',
				'Menu item/button label on Timed Content Rules admin page',
				'timed-content'
			),
			'add_new_item'       => __( 'Add new Timed Content rule', 'timed-content' ),
			'edit_item'          => __( 'Edit Timed Content rule', 'timed-content' ),
			'new_item'           => __( 'New Timed Content rule', 'timed-content' ),
			'view_item'          => __( 'View Timed Content rule', 'timed-content' ),
			'search_items'       => __( 'Search Timed Content rules', 'timed-content' ),
			'not_found'          => __( 'No Timed Content rules found', 'timed-content' ),
			'not_found_in_trash' => __( 'No Timed Content rules found in trash', 'timed-content' ),
			'parent_item_colon'  => '',
			'menu_name'          => _x( 'Timed Content rules', 'post type general name', 'timed-content' ),
		);
		$args   = array(
			'labels'              => $labels,
			'description'         => __(
				'Create regular schedules to show or hide selected content in a page or post.',
				'timed-content'
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 5,
			'supports'            => array( 'title' ),
		);
		register_post_type( TIMED_CONTENT_RULE_TYPE, $args );
	}


	/**
	 * Filter to change sort order to title
	 */
	function timed_content_pre_get_posts( $query ) {
		if ( $query->is_admin ) {
			if ( $query->get( 'post_type' ) === TIMED_CONTENT_RULE_TYPE ) {
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
			}
		}

		return $query;
	}

	/**
	 * Filter to customize CRUD messages for Timed Content Rules
	 */
	function timed_content_rule_updated_messages( $messages ) {
		global $post;

		if ( ! empty( $post ) ) {
			/* translators: date and time format to activate rule. http://ca2.php.net/manual/en/function.date.php*/
			$post_date = date_i18n( __( 'M j, Y @ G:i', 'timed-content' ), strtotime( $post->post_date ) );
		} else {
			$post_date = '';
		}

		$messages[ TIMED_CONTENT_RULE_TYPE ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Timed Content Rule updated.', 'timed-content' ),
			2  => __( 'Custom field updated.', 'timed-content' ),
			3  => __( 'Custom field deleted.', 'timed-content' ),
			4  => __( 'Timed Content Rule updated.', 'timed-content' ),
			5  => isset( $_GET['revision'] ) ? sprintf(
			/* translators: %s: date and time of the revision */
				__(
					'Timed Content Rule restored to revision from %s',
					'timed-content'
				),
				wp_post_revision_title( (int) $_GET['revision'], false )
			) : false,
			6  => __( 'Timed Content Rule published.', 'timed-content' ),
			7  => __( 'Timed Content Rule saved.', 'timed-content' ),
			8  => __( 'Timed Content Rule submitted.', 'timed-content' ),
			9  => sprintf(
			/* translators: %s: date and time to activate rule. */
				__( 'Timed Content Rule scheduled for: %s.', 'timed-content' ),
				'<strong>' . $post_date . '</strong>'
			),
			10 => __( 'Timed Content Rule draft updated.', 'timed-content' ),
		);

		return $messages;
	}

	/**
	 * Convert localized date/time string to English
	 */
	function date_time_to_english( $date, $time = '' ) {
		$months       = array(
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December',
		);
		$months_i18n  = array(
			__( 'January', 'timed-content' ),
			__( 'February', 'timed-content' ),
			__( 'March', 'timed-content' ),
			__( 'April', 'timed-content' ),
			__( 'May', 'timed-content' ),
			__( 'June', 'timed-content' ),
			__( 'July', 'timed-content' ),
			__( 'August', 'timed-content' ),
			__( 'September', 'timed-content' ),
			__( 'October', 'timed-content' ),
			__( 'November', 'timed-content' ),
			__( 'December', 'timed-content' ),
		);
		$english_date = str_replace( $months_i18n, $months, $date );

		return $english_date . ' ' . $time;
	}

	/**
	 * Advances a date by a set number of days
	 */
	function get_next_day( $current, $interval_multiplier ) {
		return strtotime( $interval_multiplier . ' day', $current );
	}

	/**
	 * Advances a date/time by a set number of hours
	 */
	function get_next_hour( $current, $interval_multiplier ) {
		return strtotime( $interval_multiplier . ' hour', $current );
	}

	/**
	 * Advances a date/time by a set number of weeks.  If given an array of days of the week, this function will
	 * advance the date/time to the next day in that array in the jumped-to week. Use this function if you're
	 * repeating an action on specific days of the week (i.e., on Weekdays, Tuesdays and Thursdays, etc.).
	 */
	function get_next_week( $current, $interval_multiplier, $days = array() ) {
		// If $days is empty, advance $interval_multiplier weeks from $current and return the timestamp
		if ( empty( $days ) ) {
			return strtotime( $interval_multiplier . ' week', $current );
		}

		// Otherwise, set up an array combining the days of the week to repeat on and the current day
		// (keys and values of the array will be the same, and the array is sorted)
		$current_day_of_week_index = $this->format_timestamp( 'w', $current );
		$days                      = array_merge( array( $current_day_of_week_index ), $days );
		$days                      = array_unique( $days );
		$days                      = array_values( $days );
		sort( $days );
		$days_of_week = array_combine( $days, $days );

		// If the current day is the last one of the days of the week to repeat on, jump ahead to
		// the next week to be repeating on and get the earliest day in the array
		if ( max( $days_of_week ) === $current_day_of_week_index ) {
			$pattern = ( ( 7 - $current_day_of_week_index ) + ( 7 * ( $interval_multiplier - 1 ) ) + ( min( array_keys( $days_of_week ) ) ) ) . ' day';
		} else {
			// Otherwise, cycle through the array until we find the next day to repeat on
			$next_day_of_week_index = $current_day_of_week_index;
			do {
			} while ( ! isset( $days_of_week[ ++$next_day_of_week_index ] ) );
			$pattern = ( $next_day_of_week_index - $current_day_of_week_index ) . ' day';
		}

		return strtotime( $pattern, $current );
	}

	/**
	 * Advances a date by a set number of months.  When the date of the first active period lies
	 * on the 29th, 30th, or 31st of the month, this function will return a date on the the last day
	 * of the month for those months not containing those days.
	 */
	function get_next_month( $current, $start, $interval_multiplier ) {
		// For most days in the month, it's pretty easy. Get the day of month of the starting date.
		$start_day = $this->format_timestamp( 'j', $start );

		// If it's before or on the 28th, just jump the number of months and be done with it.
		if ( $start_day <= 28 ) {
			return strtotime( $interval_multiplier . ' month', $current );
		}

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
		for ( $i = 0; $i < $interval_multiplier; $i++ ) {
			// The pattern for jumping will be different in each interval.
			/** @noinspection PhpUnusedLocalVariableInspection */
			$temp_pattern = '';

			// Get the number of days in the month of the current date.
			$last_day_this_month = $this->format_timestamp( 't', strtotime( 'this month', $temp_current ) );

			// Get the number of days for the next month relative to the current date .
			// Subtract 3 days from the next month to counter known month skipping bugs in PHP's relative date
			// handling, that being the difference between the shortest possible month (non-leap February - 28 days)
			// and the longest (Jan., Mar., May, Jul., Aug., Oct., Dec. - 31 days).  This may be fixed in PHP 5.3.x
			// but this should be backwards-compatible anyway.
			$last_day_next_month = $this->format_timestamp( 't', strtotime( '-3 day next month', $temp_current ) );

			// If the current month is longer than next month, follow this block
			if ( $last_day_this_month > $last_day_next_month ) {
				// If we're repeating on the last day of this month, jump the number of days next month
				if ( $start_day === $last_day_this_month ) {
					$temp_pattern = $last_day_next_month . ' days';
				} elseif ( $start_day > $last_day_next_month ) {
					// If the start day doesn't exist in the next month (i.e., no "31st" in June), jump the
					// number of days next month plus the difference between the start day and the number of days this month
					$temp_pattern = ( $last_day_this_month + $last_day_next_month - $start_day ) . ' days';
				} else {
					// Otherwise, jump ahead the number of days in this month
					$temp_pattern = $last_day_this_month . ' days';
				}
			} elseif ( $last_day_this_month < $last_day_next_month ) {
				// Or, if the current month is shorter than next month

				// If the start day doesn't exist in this month (i.e., no "31st" in June), jump the
				// number of days next month plus the difference between the start day and the number of days this month
				if ( $start_day >= $last_day_this_month ) {
					$temp_pattern = $start_day . ' days';
				} else {
					// Otherwise, jump ahead the number of days in this month
					$temp_pattern = $last_day_this_month . ' days';
				}
			} else {
				// If the current month and next month are equally long, jumping by "1 month" is fine
				$temp_pattern = '1 month';
			}

			$temp_current = strtotime( $temp_pattern, $temp_current );
		}

		return $temp_current;
	}

	/**
	 * Advances a date to the 'n'th weekday of the next month (eg., first Wednesday, third Monday, last Friday, etc.).
	 *
	 * Note: If $ordinal is set to '4' and $day is set to '7', it wil return the last day of the month.
	 */
	function get_nth_weekday_of_month( $current, $ordinal, $day ) {
		// First, get the month/year we need to work with
		$the_month           = $this->format_timestamp( 'F', $current );
		$the_year            = $this->format_timestamp( 'Y', $current );
		$last_day_this_month = $this->format_timestamp( 't', $current );

		// Get the time for the $current timestamp
		$current_time = $this->format_timestamp( 'g:i A', $current );
		$the_day      = '';

		if ( 7 === $day ) { // If $day is "day of the month", get the day of month based on the ordinal
			switch ( $ordinal ) {
				case 0:
					$the_day = '1';
					break;                    // First day of the month    //
				case 1:
					$the_day = '2';
					break;                    // Second day of the month    //
				case 2:
					$the_day = '3';
					break;                    // Third day of the month    //
				case 3:
					$the_day = '4';
					break;                    // Fourth day of the month    //
				case 4:
					$the_day = $last_day_this_month;
					break;    // Last day of the month    //
				default:
					$the_day = '1';
					break;
			}
		} else {            // If $day is one of the days of the week...
			$day_range = array();
			switch ( $ordinal ) {    // ...get a 7-day range based on the ordinal...
				case 0:
					$day_range = range( 1, 7 );
					break;                                        // First 7 days of the month    //
				case 1:
					$day_range = range( 8, 14 );
					break;                                        // Second 7 days of the month    //
				case 2:
					$day_range = range( 15, 21 );
					break;                                    // Third 7 days of the month    //
				case 3:
					$day_range = range( 22, 28 );
					break;                                    // Fourth 7 days of the month    //
				case 4:
					$day_range = range( $last_day_this_month - 6, $last_day_this_month );
					break;    // Last 7 days of the month        //
				default:
					$day_range = range( 1, 7 );
					break;
			}
			foreach ( $day_range as $a_day ) { // ...and find the matching weekday in that range.
				if ( $this->format_timestamp( 'w', strtotime( $the_month . ' ' . $a_day . ', ' . $the_year ) ) === $day ) {
					$the_day = $a_day;
					break;
				}
			}
		}

		// Build the date string for the correct day and return its timestamp
		$pattern = $the_month . ' ' . $the_day . ', ' . $the_year . ', ' . $current_time;

		return strtotime( $pattern );

	}

	/**
	 * Advances a date by a set number of years
	 */
	function get_next_year( $current, $interval_multiplier ) {
		return strtotime( $interval_multiplier . ' year', $current );
	}

	/**
	 * Validates the various Timed Content Rule parameters and returns a series of error messages.
	 */
	function validate( $args ) {
		$errors = array();

		$instance_start = DateTime::createFromFormat(
			'Y-m-d H:i',
			$args['instance_start']['date'] . ' ' . $args['instance_start']['time']
		);
		if ( false !== $instance_start ) {
			$instance_start = $instance_start->getTimestamp();
		}
		$instance_end = DateTime::createFromFormat(
			'Y-m-d H:i',
			$args['instance_end']['date'] . ' ' . $args['instance_end']['time']
		);
		if ( false !== $instance_end ) {
			$instance_end = $instance_end->getTimestamp();
		}
		$end_date = DateTime::createFromFormat( 'Y-m-d', $args['end_date'] );
		if ( false !== $end_date ) {
			$end_date = $end_date->getTimestamp();
		}

		if ( empty( $args['instance_start']['date'] ) ) {
			$errors[] = __( 'Starting date must not be empty.', 'timed-content' );
		}
		if ( empty( $args['instance_start']['time'] ) ) {
			$errors[] = __( 'Starting time must not be empty.', 'timed-content' );
		}
		if ( empty( $args['instance_end']['date'] ) ) {
			$errors[] = __( 'Ending date must not be empty.', 'timed-content' );
		}
		if ( empty( $args['instance_end']['time'] ) ) {
			$errors[] = __( 'Ending time must not be empty.', 'timed-content' );
		}
		if ( empty( $args['interval_multiplier'] ) ) {
			$errors[] = __( 'Interval must not be empty.', 'timed-content' );
		} elseif ( ! is_numeric( $args['interval_multiplier'] ) ) {
			$errors[] = __( 'Number of recurrences must be a number.', 'timed-content' );
		}
		if ( 'recurrence_duration_num_repeat' === $args['recurr_type'] ) {
			if ( empty( $args['num_repeat'] ) ) {
				$errors[] = __( 'Number of repetitions must not be empty.', 'timed-content' );
			} elseif ( ! is_numeric( $args['num_repeat'] ) ) {
				$errors[] = __( 'Number of repetitions must be a number.', 'timed-content' );
			}
		} elseif ( 'recurrence_duration_end_date' === $args['recurr_type'] ) {
			if ( empty( $args['end_date'] ) ) {
				$errors[] = __( 'End date must not be empty.', 'timed-content' );
			}
			if ( $instance_end > $end_date ) {
				$errors[] = __( 'Recurrence end date must be after ending date/time.', 'timed-content' );
			}
		}
		if ( empty( $args['instance_start'] ) ) {
			$errors[] = __( 'Starting date/time must be valid.', 'timed-content' );
		}
		if ( empty( $args['instance_end'] ) ) {
			$errors[] = __( 'Ending date/time must be valid.', 'timed-content' );
		}
		if ( $instance_start > $instance_end ) {
			$errors[] = __( 'Starting date/time must be before ending date/time.', 'timed-content' );
		}

		return $errors;
	}

	/**
	 * Helper to determine if the current rule loop is already finished
	 */
	function loop_test( $recurr_type, $current, $last_occurrence_start, $period_count, $num_repeat ) {
		if ( 'recurrence_duration_end_date' === $recurr_type ) {
			return $current <= $last_occurrence_start;
		}

		return $period_count < $num_repeat;
	}

	/**
	 * Calculates the active periods for a Timed Content Rule
	 *
	 * Note: if $args['human_readable'] is set to true, then the result will be in human readable form
	 */
	function get_rule_periods( $args ) {
		global $post;

		$active_periods = array();
		$period_count   = 0;

		$human_readable      = (bool) $args['human_readable'];
		$freq                = $args['freq'];
		$timezone            = $args['timezone'];
		$recurr_type         = $args['recurr_type'];
		$num_repeat          = intval( $args['num_repeat'] );
		$end_date            = $args['end_date'];
		$days_of_week        = $args['days_of_week'];
		$interval_multiplier = $args['interval_multiplier'];
		$instance_start_date = $args['instance_start']['date'];
		$instance_start_time = $args['instance_start']['time'];
		$instance_end_date   = $args['instance_end']['date'];
		$instance_end_time   = $args['instance_end']['time'];
		$monthly_pattern     = $args['monthly_pattern'];
		$monthly_pattern_ord = $args['monthly_pattern_ord'];
		$monthly_pattern_day = $args['monthly_pattern_day'];
		$exceptions_dates    = $args['exceptions_dates'];

		$this->set_format_timezone( $timezone );
		$right_now_t = time();

		// use debug parameter if current user is allowed to edit the post
		if ( isset( $_GET['tctest'] ) && ! empty( $post ) && current_user_can( 'edit_post', $post->post_id ) ) {
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', sanitize_text_field( $_GET['tctest'] ), $this->current_timezone );
			if ( false !== $dt ) {
				$right_now_t = $dt->getTimestamp();
			}
		}

		// Beginning of first occurrence
		$instance_start = strtotime( $this->date_time_to_english( $instance_start_date, $instance_start_time ) . ' ' . $timezone );
		$start_has_dst  = $this->format_timestamp( 'I', $instance_start );

		// End of first occurrence
		$instance_end = strtotime( $this->date_time_to_english( $instance_end_date, $instance_end_time ) . ' ' . $timezone );

		$current     = $instance_start;
		$end_current = $instance_end;

		if ( 'recurrence_duration_num_repeat' === $recurr_type ) {
			$last_occurrence_start = strtotime( TIMED_CONTENT_TIME_END );
		} else {
			$last_occurrence_start = strtotime(
				$this->date_time_to_english(
					$end_date,
					$instance_start_time
				) . ' ' . $timezone
			);
		}

		switch ( $freq ) {
			case TIMED_CONTENT_FREQ_HOURLY:
				$day_limit = 1;
				break;
			case TIMED_CONTENT_FREQ_DAILY:
				$day_limit = 7;
				break;
			case TIMED_CONTENT_FREQ_WEEKLY:
				$day_limit = 21;
				break;
			case TIMED_CONTENT_FREQ_MONTHLY:
				$day_limit = 80;
				break;
			default: // TIMED_CONTENT_FREQ_YEARLY:
				$day_limit = 380;
				break;
		}
		$future_repeats = 0;
		while (
			$this->loop_test( $recurr_type, $current, $last_occurrence_start, $period_count, $num_repeat ) &&
			( $current < $right_now_t || $future_repeats < 20 )
		) {
			$exception_period = false;
			$current_date     = $this->format_timestamp( 'Y-m-d', $current );
			if ( is_array( $exceptions_dates ) ) {
				foreach ( $exceptions_dates as $exceptions_date ) {
					if ( is_numeric( $exceptions_date ) ) {
						$exceptions_date = $this->format_timestamp( 'Y-m-d', $exceptions_date );
					}
					if ( $current_date === $exceptions_date ) {
						$exception_period = true;
						break;
					}
				}
			}

			if ( ! $exception_period && $current > $right_now_t - $day_limit * 86400 ) {
				// Adjust current date offset if start DST differs from current DST
				$current_adjusted = $current;
				$current_has_dst  = $this->format_timestamp( 'I', $current );
				if ( '1' === $start_has_dst && '0' === $current_has_dst ) {
					$current_adjusted += 3600;
				} if ( '0' === $start_has_dst && '1' === $current_has_dst ) {
					$current_adjusted -= 3600;
				}

				if ( $current > $right_now_t ) {
					$future_repeats++;
				}
				$end_current = $current_adjusted + ( $instance_end - $instance_start );
				if ( true === $human_readable ) {
					$active_periods[ $period_count ]['start']    = $this->format_timestamp( TIMED_CONTENT_DATE_FORMAT_OUTPUT, $current_adjusted );
					$active_periods[ $period_count ]['end']      = $this->format_timestamp( TIMED_CONTENT_DATE_FORMAT_OUTPUT, $end_current );
					$active_periods[ $period_count ]['timezone'] = $timezone;
					if ( $right_now_t < $current ) {
						$active_periods[ $period_count ]['status'] = 'upcoming';
						$active_periods[ $period_count ]['time']   = sprintf(
						/* translators: %s: time difference */
							_x(
								'%s from now.',
								'Human readable time difference',
								'timed-content'
							),
							human_time_diff( $current, $right_now_t )
						);
					} elseif ( ( $current <= $right_now_t ) && ( $right_now_t <= $end_current ) ) {
						$active_periods[ $period_count ]['status'] = 'active';
						$active_periods[ $period_count ]['time']   = __( 'Right now!', 'timed-content' );
					} else {
						$active_periods[ $period_count ]['status'] = 'expired';
						$active_periods[ $period_count ]['time']   = sprintf(
						/* translators: %s: time difference */
							_x(
								'%s ago.',
								'Human readable time difference',
								'timed-content'
							),
							human_time_diff( $end_current, $right_now_t )
						);
					}
				} else {
					$active_periods[ $period_count ]['start']    = $current_adjusted;
					$active_periods[ $period_count ]['end']      = $end_current;
					$active_periods[ $period_count ]['timezone'] = $timezone;
				}

				$period_count++;
			}

			switch ( $freq ) {
				case TIMED_CONTENT_FREQ_HOURLY:
					$current = $this->get_next_hour( $current, $interval_multiplier );
					break;
				case TIMED_CONTENT_FREQ_DAILY:
					$current = $this->get_next_day( $current, $interval_multiplier );
					break;
				case TIMED_CONTENT_FREQ_WEEKLY:
					$current = $this->get_next_week( $current, $interval_multiplier, $days_of_week );
					break;
				case TIMED_CONTENT_FREQ_MONTHLY:
					$current      = $this->get_next_month( $current, $instance_start, $interval_multiplier );
					$temp_current = $current;
					if ( 'yes' === $monthly_pattern ) {
						$current = $this->get_nth_weekday_of_month(
							$current,
							$monthly_pattern_ord,
							$monthly_pattern_day
						);
					} else {
						$current = $temp_current;
					}
					break;
				default: // TIMED_CONTENT_FREQ_YEARLY:
					$current = $this->get_next_year( $current, $interval_multiplier );
					break;
			}
		}

		return $active_periods;
	}

	/**
	 * Get stored rule periods by ID
	 */
	function get_rule_periods_by_id( $id, $human_readable = false ) {
		if ( TIMED_CONTENT_RULE_TYPE !== get_post_type( $id ) ) {
			return array();
		}

		$prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
		$args   = array();

		$args['human_readable'] = (bool) $human_readable;
		$args['freq']           = get_post_meta( $id, $prefix . 'frequency', true );
		$args['timezone']       = get_post_meta( $id, $prefix . 'timezone', true );
		$args['recurr_type']    = get_post_meta( $id, $prefix . 'recurrence_duration', true );
		$args['num_repeat']     = get_post_meta( $id, $prefix . 'recurrence_duration_num_repeat', true );
		$args['end_date']       = get_post_meta( $id, $prefix . 'recurrence_duration_end_date', true );
		$args['days_of_week']   = get_post_meta( $id, $prefix . 'weekly_days_of_week_to_repeat', true );
		switch ( intval( $args['freq'] ) ) {
			case 0:
				$args['interval_multiplier'] = get_post_meta( $id, $prefix . 'hourly_num_of_hours', true );
				break;
			case 1:
				$args['interval_multiplier'] = get_post_meta( $id, $prefix . 'daily_num_of_days', true );
				break;
			case 2:
				$args['interval_multiplier'] = get_post_meta( $id, $prefix . 'weekly_num_of_weeks', true );
				break;
			case 3:
				$args['interval_multiplier'] = get_post_meta( $id, $prefix . 'monthly_num_of_months', true );
				break;
			case 4:
				$args['interval_multiplier'] = get_post_meta( $id, $prefix . 'yearly_num_of_years', true );
				break;
			default:
				$args['interval_multiplier'] = 1;
		}
		$args['instance_start']      = get_post_meta( $id, $prefix . 'instance_start', true );
		$args['instance_end']        = get_post_meta( $id, $prefix . 'instance_end', true );
		$args['monthly_pattern']     = get_post_meta( $id, $prefix . 'monthly_nth_weekday_of_month', true );
		$args['monthly_pattern_ord'] = get_post_meta( $id, $prefix . 'monthly_nth_weekday_of_month_nth', true );
		$args['monthly_pattern_day'] = get_post_meta( $id, $prefix . 'monthly_nth_weekday_of_month_weekday', true );

		$exceptions_dates = get_post_meta( $id, $prefix . 'exceptions_dates' );
		if ( false !== $exceptions_dates && isset( $exceptions_dates[0] ) && is_array( $exceptions_dates[0] ) ) {
			$args['exceptions_dates'] = $exceptions_dates[0];
		} else {
			$args['exceptions_dates'] = false;
		}

		$args = $this->convert_date_time_parameters_to_iso( $args );

		return $this->get_rule_periods( $args );
	}

	/**
	 * Helper to get a sanitized post parameter
	 */
	function get_post_param( $name ) {
		$full_name = TIMED_CONTENT_RULE_POSTMETA_PREFIX . $name;
		if ( ! isset( $_POST[ $full_name ] ) ) {
			return '';
		}

		return sanitize_text_field( $_POST[ $full_name ] );
	}

	/**
	 * Helper to get a sanitized post array parameter
	 */
	function get_post_array_param( $name ) {
		$full_name = TIMED_CONTENT_RULE_POSTMETA_PREFIX . $name;

		if ( ! isset( $_POST[ $full_name ] ) || ! is_array( $_POST[ $full_name ] ) ) {
			return array();
		}

		$array = array();
		foreach ( $_POST[ $full_name ] as $key => $value ) {
			$array[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
		}

		return $array;
	}

	/**
	 * Get rule periods based on the contents of the form fields of the Add Timed Content Rule and
	 * Edit Timed Content Rule screens. Output is sent as JSON.
	 */
	function timed_content_plugin_get_rule_periods_ajax() {
		if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
			$prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
			$args   = array();

			$args['human_readable']      = $this->get_post_param( 'human_readable' );
			$args['freq']                = $this->get_post_param( 'frequency' );
			$args['timezone']            = $this->get_post_param( 'timezone' );
			$args['recurr_type']         = $this->get_post_param( 'recurrence_duration' );
			$args['num_repeat']          = $this->get_post_param( 'recurrence_duration_num_repeat' );
			$args['end_date']            = $this->get_post_param( 'recurrence_duration_end_date' );
			$args['days_of_week']        = $this->get_post_array_param( 'weekly_days_of_week_to_repeat' );
			$args['interval_multiplier'] = $this->get_post_param( 'interval_multiplier' );
			$args['instance_start']      = $this->get_post_array_param( 'instance_start' );
			$args['instance_end']        = $this->get_post_array_param( 'instance_end' );
			$args['monthly_pattern']     = $this->get_post_param( 'monthly_nth_weekday_of_month' );
			$args['monthly_pattern_ord'] = $this->get_post_param( 'monthly_nth_weekday_of_month_nth' );
			$args['monthly_pattern_day'] = $this->get_post_param( 'monthly_nth_weekday_of_month_weekday' );
			$args['exceptions_dates']    = $this->get_post_array_param( 'exceptions_dates' );

			$errors = $this->validate( $args );
			if ( count( $errors ) === 0 ) {
				header( 'Content-Type: application/json' );
				echo json_encode( $this->get_rule_periods( $args ) );
			}
		}
		die();
	}

	/**
	 * Returns a human-readable description of a Timed Content Rule
	 */
	function get_schedule_description( $args ) {
		$interval_multiplier = 1;
		$desc                = '';

		$errors = $this->validate( $args );
		if ( $errors ) {
			$message = sprintf(
				'<div class="tcr-warning">' .
				'<p class="heading">%s</p>' .
				'<p>%s</p>' .
				'<ul><li>%s</li></ul>' .
				'<p>%s</p>',
				__( 'Warning!', 'timed-content' ),
				__( 'Some problems have been detected.  Although you can still publish this rule, it may not work the way you expect.', 'timed-content' ),
				implode( '</li><li>', $errors ),
				__( 'Check that all of the conditions for this rule are correct, and use <b>Show projected dates/times</b> to ensure your rule is working properly.', 'timed-content' )
			);

			return $message;
		}

		if ( $args['action'] ) {
			$action = __( 'Show the content', 'timed-content' );
		} else {
			$action = __( 'Hide the content', 'timed-content' );
		}
		$freq                = $args['freq'];
		$timezone            = $args['timezone'];
		$recurr_type         = $args['recurr_type'];
		$num_repeat          = intval( $args['num_repeat'] );
		$end_date            = $args['end_date'];
		$days_of_week        = $args['days_of_week'];
		$interval_multiplier = $args['interval_multiplier'];
		$instance_start_date = $args['instance_start']['date'];
		$instance_start_time = $args['instance_start']['time'];
		$instance_end_date   = $args['instance_end']['date'];
		$instance_end_time   = $args['instance_end']['time'];
		$monthly_pattern     = $args['monthly_pattern'];
		$monthly_pattern_ord = $args['monthly_pattern_ord'];
		$monthly_pattern_day = $args['monthly_pattern_day'];
		$exceptions_dates    = $args['exceptions_dates'];

		$desc = sprintf(
		/* translators: %1$s: action, %2$s: start date, %3$s: start time, %4$s: end date, %5$s: end time */
			_x(
				'%1$s on %2$s @ %3$s until %4$s @ %5$s.',
				'Perform action (%1$s) from date/time of first active period (%2$s @ %3$s) until date/time of last active period (%4$s @ %5$s).',
				'timed-content'
			),
			$action,
			$instance_start_date,
			$instance_start_time,
			$instance_end_date,
			$instance_end_time
		);

		if ( 0 === $freq ) {
			$desc .= '<br />' . sprintf(
					/* translators: %d numerical hours value */
				_n(
					'Repeat this action every %d hour.',
					'Repeat this action every %d hours.',
					$interval_multiplier,
					'timed-content'
				),
				$interval_multiplier
			);
		} elseif ( 1 === $freq ) {
			$desc .= '<br />' . sprintf(
					/* translators: %d numerical days value */
				_n(
					'Repeat this action every %d day.',
					'Repeat this action every %d days.',
					$interval_multiplier,
					'timed-content'
				),
				$interval_multiplier
			);
		} elseif ( 2 === $freq ) {
			if ( ( $days_of_week ) && ( is_array( $days_of_week ) ) ) {
				$days      = array();
				$days_list = '';
				foreach ( $days_of_week as $v ) {
					$days[] = $this->rule_days_array[ $v ];
				}
				switch ( count( $days ) ) {
					case 1:
						$days_list = $days[0];
						break;
					case 2:
						$days_list = sprintf(
							/* translators: %1$s, %2$s: weekday */
							_x( '%1$s and %2$s', 'List of two weekdays', 'timed-content' ),
							$days[0],
							$days[1]
						);
						break;
					case 3:
						$days_list = sprintf(
							/* translators: %1$s, %2$s, %3$s: weekday */
							_x(
								'%1$s, %2$s and %3$s',
								'List of three weekdays',
								'timed-content'
							),
							$days[0],
							$days[1],
							$days[2]
						);
						break;
					case 4:
						$days_list = sprintf(
						/* translators: %1$s, %2$s, %3$s, %4$s: weekday */
							_x(
								'%1$s, %2$s, %3$s and %4$s',
								'List of four weekdays',
								'timed-content'
							),
							$days[0],
							$days[1],
							$days[2],
							$days[3]
						);
						break;
					case 5:
						$days_list = sprintf(
						/* translators: %1$s, %2$s, %3$s, %4$s, %5$s: weekday */
							_x(
								'%1$s, %2$s, %3$s, %4$s and %5$s',
								'List of five weekdays',
								'timed-content'
							),
							$days[0],
							$days[1],
							$days[2],
							$days[3],
							$days[4]
						);
						break;
					case 6:
						$days_list = sprintf(
						/* translators: %1$s, %2$s, %3$s, %4$s, %5$s, %6$s: weekday */
							_x(
								'%1$s, %2$s, %3$s, %4$s, %5$s and %6$s',
								'List of six weekdays',
								'timed-content'
							),
							$days[0],
							$days[1],
							$days[2],
							$days[3],
							$days[4],
							$days[5]
						);
						break;
					case 7:
						$days_list = sprintf(
						/* translators: %1$s, %2$s, %3$s, %4$s, %5$s, %6$s, %7$s: weekday */
							_x(
								'%1$s, %2$s, %3$s, %4$s, %5$s, %6$s and %7$s',
								'List of all weekdays',
								'timed-content'
							),
							$days[0],
							$days[1],
							$days[2],
							$days[3],
							$days[4],
							$days[5],
							$days[6]
						);
						break;
				}
				if ( 1 === $interval_multiplier ) {
					$desc .= '<br />' . sprintf(
						/* translators: %s: weekday */
						_x(
							'Repeat this action every week on %s.',
							'List the weekdays to repeat the rule when frequency is every week. %s is the list of weekdays.',
							'timed-content'
						),
						$days_list
					);
				} else {
					$desc .= '<br />' . sprintf(
						/* translators: %1$d: number of weeks, %2$s: weekday */
						_x(
							'Repeat this action every %1$d weeks on %2$s.',
							'List the weekdays to repeat the rule when frequency is every %1$d weeks. %2$s is the list of weekdays.',
							'timed-content'
						),
						$interval_multiplier,
						$days_list
					);
				}
			} else {
				$desc .= '<br />' . sprintf(
					/* translators: %d: number of weeks */
					_n(
						'Repeat this action every %d week.',
						'Repeat this action every %d weeks.',
						$interval_multiplier,
						'timed-content'
					),
					$interval_multiplier
				);
			}
		} elseif ( 3 === $freq ) {
			if ( 'yes' === $monthly_pattern ) {
				if ( 1 === $interval_multiplier ) {
					$desc .= '<br />' . sprintf(
						/* translators: %1$s: recurrence, %2$s: weekday */
						_x(
							'Repeat this action every month on the %1$s %2$s of the month.',
							"Example: 'Repeat this action every month on the second Friday of the month.'",
							'timed-content'
						),
						$this->rule_ordinal_array[ $monthly_pattern_ord ],
						$this->rule_ordinal_days_array[ $monthly_pattern_day ]
					);
				} else {
					$desc .= '<br />' . sprintf(
						/* translators: %1$d: month count, %2$s: recurrence, %3$s: weekday */
						_x(
							'Repeat this action every %1$d months on the %2$s %3$s of the month.',
							"Example: 'Repeat this action every 2 months on the second Friday of the month.'",
							'timed-content'
						),
						$interval_multiplier,
						$this->rule_ordinal_array[ $monthly_pattern_ord ],
						$this->rule_ordinal_days_array[ $monthly_pattern_day ]
					);
				}
			} else {
				$desc .= '<br />' . sprintf(
					/* translators: %d: month count */
					_n(
						'Repeat this action every %d month.',
						'Repeat this action every %d months.',
						$interval_multiplier,
						'timed-content'
					),
					$interval_multiplier
				);
			}
		} elseif ( 4 === $freq ) {
			$desc .= '<br />' . sprintf(
				/* translators: %d: year count */
				_n(
					'Repeat this action every %d year.',
					'Repeat this action every %d years.',
					$interval_multiplier,
					'timed-content'
				),
				$interval_multiplier
			);
		}

		if ( 'recurrence_duration_num_repeat' === $recurr_type ) {
			$desc .= '<br />' . sprintf(
				/* translators: %d: number of recurrences */
				_n(
					'This rule will be active for %d recurrence.',
					'This rule will be active for %d recurrences.',
					$num_repeat,
					'timed-content'
				),
				$num_repeat
			);
		} elseif ( 'recurrence_duration_end_date' === $recurr_type ) {
			$desc .= '<br />' . sprintf( /* translators: %s: end date */ __( 'This rule will be active until %s.', 'timed-content' ), $end_date );
		}

		if ( ( $exceptions_dates ) && ( is_array( $exceptions_dates ) ) ) {
			sort( $exceptions_dates, SORT_NUMERIC );
			$exceptions_dates = array_unique( $exceptions_dates );
			if ( 0 === $exceptions_dates[0] ) {
				array_shift( $exceptions_dates );
			}
			if ( ! empty( $exceptions_dates ) ) {
				$desc .= '<br />' . sprintf(
					/* translators: %s: list of dates */
					__(
						'This rule will be inactive on the following dates: %s.',
						'timed-content'
					),
					join( ', ', $exceptions_dates )
				);
			}
		}

		$desc .= '<br />' . sprintf(
			/* translators: %s: timezone */
			__( 'All times are in the %s timezone.', 'timed-content' ),
			$timezone
		);

		return $desc;
	}

	/**
	 * Get the description of a rule
	 */
	function get_schedule_description_by_id( $id ) {
		$defaults = array();

		foreach ( $this->rule_occurrence_custom_fields as $field ) {
			$defaults[ $field['name'] ] = $field['default'];
		}
		foreach ( $this->rule_pattern_custom_fields as $field ) {
			$defaults[ $field['name'] ] = $field['default'];
		}
		foreach ( $this->rule_recurrence_custom_fields as $field ) {
			$defaults[ $field['name'] ] = $field['default'];
		}
		foreach ( $this->rule_exceptions_custom_fields as $field ) {
			$defaults[ $field['name'] ] = $field['default'];
		}

		$prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
		$args   = array();

		$args['action']       = ( false === get_post_meta(
			$id,
			$prefix . 'action',
			true
		) ? $defaults['action'] : get_post_meta( $id, $prefix . 'action', true ) );
		$args['freq']         = ( false === get_post_meta(
			$id,
			$prefix . 'frequency',
			true
		) ? $defaults['frequency'] : get_post_meta( $id, $prefix . 'frequency', true ) );
		$args['timezone']     = ( false === get_post_meta(
			$id,
			$prefix . 'timezone',
			true
		) ? $defaults['timezone'] : get_post_meta( $id, $prefix . 'timezone', true ) );
		$args['recurr_type']  = ( false === get_post_meta(
			$id,
			$prefix . 'recurrence_duration',
			true
		) ? $defaults['recurrence_duration'] : get_post_meta(
			$id,
			$prefix . 'recurrence_duration',
			true
		) );
		$args['num_repeat']   = ( false === get_post_meta(
			$id,
			$prefix . 'recurrence_duration_num_repeat',
			true
		) ? $defaults['recurrence_duration_num_repeat'] : get_post_meta(
			$id,
			$prefix . 'recurrence_duration_num_repeat',
			true
		) );
		$args['end_date']     = ( false === get_post_meta(
			$id,
			$prefix . 'recurrence_duration_end_date',
			true
		) ? $defaults['recurrence_duration_end_date'] : get_post_meta(
			$id,
			$prefix . 'recurrence_duration_end_date',
			true
		) );
		$args['days_of_week'] = ( false === get_post_meta(
			$id,
			$prefix . 'weekly_days_of_week_to_repeat',
			true
		) ? $defaults['weekly_days_of_week_to_repeat'] : get_post_meta(
			$id,
			$prefix . 'weekly_days_of_week_to_repeat',
			true
		) );
		switch ( $args['freq'] ) {
			case '0':
				$args['interval_multiplier'] = ( false === get_post_meta(
					$id,
					$prefix . 'hourly_num_of_hours',
					true
				) ? $defaults['hourly_num_of_hours'] : get_post_meta(
					$id,
					$prefix . 'hourly_num_of_hours',
					true
				) );
				break;
			case '1':
				$args['interval_multiplier'] = ( false === get_post_meta(
					$id,
					$prefix . 'daily_num_of_days',
					true
				) ? $defaults['daily_num_of_days'] : get_post_meta(
					$id,
					$prefix . 'daily_num_of_days',
					true
				) );
				break;
			case '2':
				$args['interval_multiplier'] = ( false === get_post_meta(
					$id,
					$prefix . 'weekly_num_of_weeks',
					true
				) ? $defaults['weekly_num_of_weeks'] : get_post_meta(
					$id,
					$prefix . 'weekly_num_of_weeks',
					true
				) );
				break;
			case '3':
				$args['interval_multiplier'] = ( false === get_post_meta(
					$id,
					$prefix . 'monthly_num_of_months',
					true
				) ? $defaults['monthly_num_of_months'] : get_post_meta(
					$id,
					$prefix . 'monthly_num_of_months',
					true
				) );
				break;
			case '4':
				$args['interval_multiplier'] = ( false === get_post_meta(
					$id,
					$prefix . 'yearly_num_of_years',
					true
				) ? $defaults['yearly_num_of_years'] : get_post_meta(
					$id,
					$prefix . 'yearly_num_of_years',
					true
				) );
				break;
		}
		$args['instance_start'] = ( false === get_post_meta(
			$id,
			$prefix . 'instance_start',
			true
		) ? $defaults['instance_start'] : get_post_meta( $id, $prefix . 'instance_start', true ) );
		if ( ! is_array( $args['instance_start'] ) ) {
			$args['instance_start'] = array(
				'date' => '',
				'time' => '',
			);
		}
		$args['instance_end'] = ( false === get_post_meta(
			$id,
			$prefix . 'instance_end',
			true
		) ? $defaults['instance_end'] : get_post_meta( $id, $prefix . 'instance_end', true ) );
		if ( ! is_array( $args['instance_end'] ) ) {
			$args['instance_end'] = array(
				'date' => '',
				'time' => '',
			);
		}
		$args['monthly_pattern']     = ( false === get_post_meta(
			$id,
			$prefix . 'monthly_nth_weekday_of_month',
			true
		) ? $defaults['monthly_nth_weekday_of_month'] : get_post_meta(
			$id,
			$prefix . 'monthly_nth_weekday_of_month',
			true
		) );
		$args['monthly_pattern_ord'] = ( false === get_post_meta(
			$id,
			$prefix . 'monthly_nth_weekday_of_month_nth',
			true
		) ? $defaults['monthly_nth_weekday_of_month_nth'] : get_post_meta(
			$id,
			$prefix . 'monthly_nth_weekday_of_month_nth',
			true
		) );
		$args['monthly_pattern_day'] = ( false === get_post_meta(
			$id,
			$prefix . 'monthly_nth_weekday_of_month_weekday',
			true
		) ? $defaults['monthly_nth_weekday_of_month_weekday'] : get_post_meta(
			$id,
			$prefix . 'monthly_nth_weekday_of_month_weekday',
			true
		) );
		$exceptions_dates            = get_post_meta( $id, $prefix . 'exceptions_dates' );
		if ( false !== $exceptions_dates && isset( $exceptions_dates[0] ) && is_array( $exceptions_dates[0] ) ) {
			$args['exceptions_dates'] = $exceptions_dates[0];
		} else {
			$args['exceptions_dates'] = $defaults['exceptions_dates'];
		}

		$args = $this->convert_date_time_parameters_to_iso( $args );

		return $this->get_schedule_description( $args );
	}

	/**
	 * Get the descroption based on the contents of the form fields of the Add Timed Content Rule and
	 * Edit Timed Content Rule screens.  Output is sent to output as plain text.
	 */
	function timed_content_plugin_get_schedule_description_ajax() {
		if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
			$prefix = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
			$args   = array();

			$args['action']              = $this->get_post_param( $prefix . 'action' );
			$args['freq']                = $this->get_post_param( 'frequency' );
			$args['timezone']            = $this->get_post_param( 'timezone' );
			$args['recurr_type']         = $this->get_post_param( 'recurrence_duration' );
			$args['num_repeat']          = $this->get_post_param( 'recurrence_duration_num_repeat' );
			$args['end_date']            = $this->get_post_param( 'recurrence_duration_end_date' );
			$args['days_of_week']        = $this->get_post_array_param( 'weekly_days_of_week_to_repeat' );
			$args['interval_multiplier'] = $this->get_post_param( 'interval_multiplier' );
			$args['instance_start']      = $this->get_post_array_param( 'instance_start' );
			$args['instance_end']        = $this->get_post_array_param( 'instance_end' );
			$args['monthly_pattern']     = $this->get_post_param( 'monthly_nth_weekday_of_month' );
			$args['monthly_pattern_ord'] = $this->get_post_param( 'monthly_nth_weekday_of_month_nth' );
			$args['monthly_pattern_day'] = $this->get_post_param( 'monthly_nth_weekday_of_month_weekday' );
			$args['exceptions_dates']    = $this->get_post_array_param( 'exceptions_dates' );

			// response output
			header( 'Content-Type: text/plain' );
			echo wp_kses_post( $this->get_schedule_description( $args ) );
		}
		die();
	}

	/**
	 * Processes the [timed-content-client] shortcode.
	 */
	function client_show_html( $atts, $content = null ) {
		$show_attr   = '';
		$hide_attr   = '';
		$atts_parsed = shortcode_atts(
			array(
				'show'    => '0:00:000',
				'hide'    => '0:00:000',
				'display' => 'div',
			),
			$atts
		);

		$show_min  = 0;
		$show_sec  = 0;
		$show_fade = 0;
		$hide_min  = 0;
		$hide_sec  = 0;
		$hide_fade = 0;

		$show_values = explode( ':', $atts_parsed['show'] );
		if ( false !== $show_values ) {
			if ( isset( $show_values[0] ) ) {
				$show_min = intval( $show_values[0] );
			}
			if ( isset( $show_values[1] ) ) {
				$show_sec = intval( $show_values[1] );
			}
			if ( isset( $show_values[2] ) ) {
				$show_fade = intval( $show_values[2] );
			}
		}
		$hide_values = explode( ':', $atts_parsed['hide'] );
		if ( false !== $hide_values ) {
			if ( isset( $hide_values[0] ) ) {
				$hide_min = intval( $hide_values[0] );
			}
			if ( isset( $hide_values[1] ) ) {
				$hide_sec = intval( $hide_values[1] );
			}
			if ( isset( $hide_values[2] ) ) {
				$hide_fade = intval( $hide_values[2] );
			}
		}

		if ( ( $show_min + $show_sec ) > 0 ) {
			$show_attr = sprintf( '_show_%d_%d_%d', $show_min, $show_sec, $show_fade );
		}
		if ( ( $hide_min + $hide_sec ) > 0 ) {
			$show_attr = sprintf( '_hide_%d_%d_%d', $hide_min, $hide_sec, $hide_fade );
		}

		$the_class = TIMED_CONTENT_SHORTCODE_CLIENT . $show_attr . $hide_attr;
		$the_tag   = ( 'div' === $atts_parsed['display'] ? 'div' : 'span' );

		$the_filter = 'timed_content_filter';
		$the_filter = apply_filters( 'timed_content_filter_override', $the_filter );

		return '<'
			. $the_tag
			. " class='"
			. $the_class
			. "'"
			. ( ( '' !== $show_attr ) ? " style='display: none;'" : '' ) . '>'
			. str_replace( ']]>', ']]&gt;', apply_filters( $the_filter, $content ) )
			. '</' . $the_tag . '>';
	}

	/**
	 * Processes the [timed-content-server] shortcode.
	 */
	function server_show_html( $atts, $content = null ) {
		global $post;

		$atts_parsed = shortcode_atts(
			array(
				'show'  => 0,
				'hide'  => 0,
				'debug' => 'false',
			),
			$atts
		);
		$show        = $atts_parsed['show'];
		$hide        = $atts_parsed['hide'];
		$debug       = $atts_parsed['debug'];

		// Get time and timezone object for "show" time
		$pos = strrpos( $show, ' ' );
		if ( false !== $pos ) {
			$show_time   = substr( $show, 0, $pos );
			$show_tzname = substr( $show, $pos + 1 );
		} else {
			$show_time   = $show;
			$show_tzname = date_default_timezone_get();
		}
		try {
			$show_tz = new DateTimeZone( $show_tzname );
		} catch ( Exception $e ) {
			$show_tz = new DateTimeZone( 'UTC' );
		}

		// Create time and timezone object for "hide" time
		$pos = strrpos( $hide, ' ' );
		if ( false !== $pos ) {
			$hide_time   = substr( $hide, 0, $pos );
			$hide_tzname = substr( $hide, $pos + 1 );
		} else {
			$hide_time   = $hide;
			$hide_tzname = date_default_timezone_get();
		}
		try {
			$hide_tz = new DateTimeZone( $hide_tzname );
		} catch ( Exception $e ) {
			$hide_tz = new DateTimeZone( 'UTC' );
		}

		// Try to parse date as ISO first
		$show_dt = DateTime::createFromFormat( 'Y-m-d G:i', $show_time, $show_tz );
		// Fallback to American format
		if ( false === $show_dt ) {
			$show_dt = DateTime::createFromFormat( 'm/d/Y G:i', $show_time, $show_tz );
		}

		if ( false !== $show_dt ) {
			$show_t = $show_dt->getTimeStamp();
		} else {
			// If nothing else worked so far, try strtotime()
			// as it was before version 2.50
			$show_t = strtotime( $show );
			if ( false === $show_t ) {
				$show_t = 0;
			}
			$show_dt = new DateTime();
			$show_dt->setTimeStamp( $show_t );
			$show_dt->setTimezone( $show_tz );
		}

		// Try to parse date as ISO first
		$hide_dt = DateTime::createFromFormat( 'Y-m-d G:i', $hide_time, $hide_tz );
		if ( false === $hide_dt ) {
			$hide_dt = DateTime::createFromFormat( 'm/d/Y G:i', $hide_time, $hide_tz );
		}
		if ( false !== $hide_dt ) {
			$hide_t = $hide_dt->getTimeStamp();
		} else {
			// If nothing else worked so far, try strtotime()
			// as it was before version 2.50
			$hide_t = strtotime( $hide );
			if ( false === $hide_t ) {
				$hide_t = 0;
			}
			$hide_dt = new DateTime();
			$hide_dt->setTimeStamp( $hide_t );
			$hide_dt->setTimezone( $hide_tz );
		}

		$right_now_t   = time();
		$debug_message = '';

		// use debug parameter if current user is allowed to edit the post
		if ( isset( $_GET['tctest'] ) && ! empty( $post ) && current_user_can( 'edit_post', $post->post_id ) ) {
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', sanitize_text_field( $_GET['tctest'] ) );
			if ( false !== $dt ) {
				$right_now_t = $dt->getTimestamp();
			}
		}

		$the_filter = 'timed_content_filter';
		$the_filter = apply_filters( 'timed_content_filter_override', $the_filter );

		$show_content = false;
		if ( ( $show_t <= $right_now_t ) && ( $right_now_t <= $hide_t || 0 === $hide_t ) ) {
			$show_content = true;
		}

		if ( ( ( 'true' === $debug ) || ( ( ! $show_content ) && ( 'when_hidden' === $debug ) ) ) && ( ! empty( $post ) && current_user_can( 'edit_post', $post->post_id ) ) ) {

			$right_now = $this->format_timestamp( TIMED_CONTENT_DATE_FORMAT_OUTPUT, $right_now_t );

			if ( $show_t > $right_now_t ) {
				$show_diff_str = sprintf(
				/* translators: %s: time difference */
					_x( '%s from now.', 'Human readable time difference', 'timed-content' ),
					human_time_diff( $show_t, $right_now_t )
				);
			} else {
				$show_diff_str = sprintf(
				/* translators: %s: time difference */
					_x( '%s ago.', 'Human readable time difference', 'timed-content' ),
					human_time_diff( $show_t, $right_now_t )
				);
			}
			if ( $hide_t > $right_now_t ) {
				$hide_diff_str = sprintf(
				/* translators: %s: time difference */
					_x( '%s from now.', 'Human readable time difference', 'timed-content' ),
					human_time_diff( $hide_t, $right_now_t )
				);
			} else {
				$hide_diff_str = sprintf(
				/* translators: %s: time difference */
					_x( '%s ago.', 'Human readable time difference', 'timed-content' ),
					human_time_diff( $hide_t, $right_now_t )
				);
			}

			$debug_message  = "<div class=\"tcr-warning\">\n";
			$debug_message .= '<p class="heading">' . _x( 'Notice', 'Noun', 'timed-content' ) . "</p>\n";
			$debug_message .= '<p>' . sprintf(
				/* translators: %1$s: shortcode, %2$s attribute in shortcode */
				__(
					'Debugging has been turned on for a %1$s shortcode on this post/page. Only website users who are currently logged in and can edit this post/page will see this.  To turn off this message, remove the %2$s attribute from the shortcode.',
					'timed-content'
				),
				'<code>[timed-content-server]</code>',
				'<code>debug</code>'
			) . "</p>\n";

			if ( 0 === $show_t ) {
				$debug_message .= '<p>' . sprintf(
					/* translators: %s: attribute name */
					__( 'The %s attribute is not set or invalid.', 'timed-content' ),
					'<code>show</code>'
				) . "</p>\n";
			} else {
				$debug_message .= '<p>' . sprintf(
					/* translators: %s: attribute name */
					__( 'The %s attribute is currently set to', 'timed-content' ),
					'<code>show</code>'
				) . ': ' . $show . ",<br />\n "
					. __(
						'The Timed Content plugin thinks the intended date/time is',
						'timed-content'
					) . ': ' . $show_dt->format( TIMED_CONTENT_DATE_FORMAT_OUTPUT )
					. ' (' . $show_diff_str . ")</p>\n";
			}

			if ( 0 === $hide ) {
				$debug_message .= '<p>' . sprintf(
					/* translators: %s: attribute name */
					__( 'The %s attribute is not set or invalid.', 'timed-content' ),
					'<code>hide</code>'
				) . "</p>\n";
			} else {
				$debug_message .= '<p>' . sprintf(
					/* translators: %s: attribute name */
					__( 'The %s attribute is currently set to', 'timed-content' ),
					'<code>hide</code>'
				) . ': ' . $hide . ",<br />\n"
					. __(
						'The Timed Content plugin thinks the intended date/time is',
						'timed-content'
					) . ': ' . $hide_dt->format( TIMED_CONTENT_DATE_FORMAT_OUTPUT )
					. ' (' . $hide_diff_str . ").</p>\n";
			}

			$debug_message .= '<p>' . __(
				'Current date:',
				'timed-content'
			) . '&nbsp;' . $right_now . "</p>\n";
			$debug_message .= '<p>' . __( 'Content filter:', 'timed-content' ) . '&nbsp;' . $the_filter . "</p>\n";
			$debug_message .= '<p>' . _x( 'Content:', 'Noun', 'timed-content' ) . '</p><p>' . $content . "</p>\n";

			if ( true === $show_content ) {
				$debug_message .= '<p>' . __( 'The plugin will show the content.', 'timed-content' ) . '</p>';
			} else {
				$debug_message .= '<p>' . __( 'The plugin will hide the content.', 'timed-content' ) . '</p>';
			}

			$debug_message .= "</div>\n";
		}

		if ( true === $show_content ) {
			if ( ! empty( $post ) ) {
				do_action( 'timed_content_server_show', $post->ID, $show, $hide, $content );
			} else {
				do_action( 'timed_content_server_show', null, $show, $hide, $content );
			}

			return $debug_message . str_replace( ']]>', ']]&gt;', apply_filters( $the_filter, $content ) ) . "\n";
		} else {
			if ( ! empty( $post ) ) {
				do_action( 'timed_content_server_hide', $post->ID, $show, $hide, $content );
			} else {
				do_action( 'timed_content_server_show', null, $show, $hide, $content );
			}

			return $debug_message . "\n";
		}

	}

	/**
	 * Processes the [timed-content-rule] shortcode.
	 */
	function rules_show_html( $atts, $content = null ) {
		global $post;

		$atts_parsed = shortcode_atts( array( 'id' => 0 ), $atts );
		$id          = $atts_parsed['id'];

		if ( ! is_numeric( $id ) ) {
			$page = get_page_by_title( $id, OBJECT, TIMED_CONTENT_RULE_TYPE );
			if ( null === $page ) {
				return '';
			}
			$id = $page->ID;
		}
		if ( TIMED_CONTENT_RULE_TYPE !== get_post_type( $id ) ) {
			return '';
		}

		$prefix         = TIMED_CONTENT_RULE_POSTMETA_PREFIX;
		$right_now_t    = time();
		$rule_is_active = false;

		// use debug parameter if current user is allowed to edit the post
		if ( isset( $_GET['tctest'] ) && current_user_can( 'edit_post', $post->post_id ) ) {
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', sanitize_text_field( $_GET['tctest'] ) );
			if ( false !== $dt ) {
				$right_now_t = $dt->getTimestamp();
			}
		}

		$active_periods = $this->get_rule_periods_by_id( $id, false );
		$action_is_show = (bool) get_post_meta( $id, $prefix . 'action', true );

		foreach ( $active_periods as $period ) {
			if ( ( $period['start'] <= $right_now_t ) && ( $right_now_t <= $period['end'] ) ) {
				$rule_is_active = true;
				break;
			}
		}

		$the_filter = 'timed_content_filter';
		$the_filter = apply_filters( 'timed_content_filter_override', $the_filter );

		if ( ( true === $rule_is_active && true === $action_is_show ) || ( false === $rule_is_active && false === $action_is_show ) ) {
			if ( ! empty( $post ) ) {
				do_action( 'timed_content_rule_show', $post->ID, $id, $content );
			} else {
				do_action( 'timed_content_rule_show', null, $id, $content );
			}

			return str_replace( ']]>', ']]&gt;', apply_filters( $the_filter, $content ) );
		} else {
			if ( ! empty( $post ) ) {
				do_action( 'timed_content_rule_hide', $post->ID, $id, $content );
			} else {
				do_action( 'timed_content_rule_hide', null, $id, $content );
			}

			return '';
		}
	}

	/**
	 * Enqueues the JavaScript code necessary for the functionality of the [timed-content-client] shortcode.
	 */
	function add_header_code() {
		if ( ! is_admin() ) {
			wp_enqueue_style( 'timed-content-css', TIMED_CONTENT_CSS, false, TIMED_CONTENT_VERSION );
			wp_enqueue_script(
				'timed-content_js',
				TIMED_CONTENT_PLUGIN_URL . '/js/timed-content.js',
				array( 'jquery' ),
				TIMED_CONTENT_VERSION
			);
		}
	}

	/**
	 * Enqueues the CSS code necessary for custom icons for the Timed Content Rules management screens
	 * and the TinyMCE editor.
	 */
	function add_post_type_icons() {
		wp_enqueue_style( 'timed-content-dashicons', TIMED_CONTENT_CSS_DASHICONS, false, TIMED_CONTENT_VERSION );
		?>
		<style type="text/css" media="screen">
			#adminmenu #menu-posts-<?php echo TIMED_CONTENT_RULE_TYPE; ?>.menu-icon-post div.wp-menu-image:before {
				font-family: 'timed-content-dashicons' !important;
				content: '\e601';
			}

			#dashboard_right_now li.<?php echo TIMED_CONTENT_RULE_TYPE; ?>-count a:before {
				font-family: 'timed-content-dashicons' !important;
				content: '\e601';
			}

			.mce-i-timed_content:before {
				font: 400 24px/1 'timed-content-dashicons' !important;
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
	 */
	function add_admin_header_code() {
		if ( ( isset( $_GET['post_type'] ) && TIMED_CONTENT_RULE_TYPE === $_GET['post_type'] )
			|| ( isset( $post_type ) && TIMED_CONTENT_RULE_TYPE === $post_type )
			|| ( isset( $_GET['post'] ) && TIMED_CONTENT_RULE_TYPE === get_post_type( sanitize_text_field( $_GET['post'] ) ) ) ) {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'timed-content-css', TIMED_CONTENT_CSS, false, TIMED_CONTENT_VERSION );
			// Enqueue the JavaScript file that manages the meta box UI
			wp_enqueue_script(
				'timed-content-admin_js',
				TIMED_CONTENT_PLUGIN_URL . '/js/timed-content-admin.js',
				array( 'jquery' ),
				TIMED_CONTENT_VERSION
			);
			// Enqueue the JavaScript file that makes AJAX requests
			wp_enqueue_script(
				'timed-content-ajax_js',
				TIMED_CONTENT_PLUGIN_URL . '/js/timed-content-ajax.js',
				array( 'jquery', 'thickbox' ),
				TIMED_CONTENT_VERSION
			);

			// Set up local variables used in the Admin JavaScript file
			wp_localize_script(
				'timed-content-admin_js',
				'timedContentRuleAdmin',
				array(
					'no_exceptions_label' => __( '- No exceptions set -', 'timed-content' ),
				)
			);

			// Set up local variables used in the AJAX JavaScript file
			wp_localize_script(
				'timed-content-ajax_js',
				'timedContentRuleAjax',
				array(
					'ajaxurl'               => admin_url( 'admin-ajax.php' ),
					'start_label'           => _x(
						'Start',
						'Scheduled Dates/Times dialog - Beginning of active period table header',
						'timed-content'
					),
					'end_label'             => _x(
						'End',
						'Scheduled Dates/Times dialog - End of active period table header',
						'timed-content'
					),
					'dialog_label'          => _x(
						'Scheduled dates/times',
						'Scheduled Dates/Times dialog - dialog header',
						'timed-content'
					),
					'button_loading_label'  => __( 'Calculating dates/times', 'timed-content' ),
					'button_finished_label' => __( 'Show projected dates/times', 'timed-content' ),
					'dialog_width'          => 800,
					'dialog_height'         => 500,
					'error'                 => __( 'Error', 'timed-content' ),
					'error_desc'            => __(
						'Something unexpected has happened along the way. The specific details are below:',
						'timed-content'
					),
				)
			);
		}
	}

	/**
	 * Initializes the TinyMCE plugin bundled with this WordPress plugin
	 */
	function init_tinymce_plugin() {
		if ( ( ! current_user_can( 'edit_posts' ) ) && ( ! current_user_can( 'edit_pages' ) ) ) {
			return;
		}

		// Add only in Rich Editor mode
		if ( get_user_option( 'rich_editing' ) === 'true' ) {
			add_filter( 'mce_external_plugins', array( &$this, 'add_timed_content_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( &$this, 'register_tinymce_button' ) );
		}
	}

	/**
	 * Sets up variables to use in the TinyMCE plugin's plugin.js.
	 */
	function set_tinymce_plugin_vars() {
		global $wp_version;
		if ( ( ! current_user_can( 'edit_posts' ) ) && ( ! current_user_can( 'edit_pages' ) ) ) {
			return;
		}

		// Add only in Rich Editor mode
		if ( get_user_option( 'rich_editing' ) === 'true' ) {
			if ( version_compare( $wp_version, '3.8', '<' ) ) {
				$image = '/clock.gif';
			} else {
				$image = '';
			}
			wp_localize_script(
				'editor',
				'timedContentAdminTinyMCEOptions',
				array(
					'version' => TIMED_CONTENT_VERSION,
					'desc'    => __( 'Add Timed Content shortcodes', 'timed-content' ),
					'image'   => $image,
				)
			);
		}
	}

	/**
	 * Sets up the button for the associated TinyMCE plugin for use in the editor menubar.
	 */
	function register_tinymce_button( $buttons ) {
		array_push( $buttons, '|', 'timed_content' );

		return $buttons;
	}

	/**
	 * Loads the associated TinyMCE plugin into TinyMCE's plugin array
	 */
	function add_timed_content_tinymce_plugin( $plugin_array ) {
		$plugin_array['timed_content'] = TIMED_CONTENT_PLUGIN_URL . '/tinymce_plugin/plugin.js';

		return $plugin_array;
	}

	/**
	 * Generates JavaScript array of objects describing Timed Content rules.  Used in the dialog box created by
	 * timedContentPlugin::timedContentPluginGetTinyMCEDialog().
	 */
	function get_rules_js() {
		$the_js    = "var rules = [\n";
		$args      = array(
			'post_type'      => TIMED_CONTENT_RULE_TYPE,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
		$the_rules = get_posts( $args );
		foreach ( $the_rules as $rule ) {
			$desc = $this->get_schedule_description_by_id( $rule->ID );
			$desc = str_replace( '<br />', ' ', $desc );
			// Only add a rule if there's no errors or warnings
			if ( false === strpos( $desc, 'tcr-warning' ) ) {
				$the_js .= "    { 'ID': " . $rule->ID . ", 'title': '" . esc_js(
					( ( strlen( $rule->post_title ) > 0 ) ? $rule->post_title : _x(
						'(no title)',
						'No Timed Content Rule title',
						'timed-content'
					) )
				) . "', 'desc': '" . esc_js( $desc ) . "' },\n";
			}
		}
		if ( empty( $the_rules ) ) {
			$the_js .= "    { 'ID': -999, 'title': ' ---- ', 'desc': '" . __(
				'No Timed Content Rules found',
				'timed-content'
			) . "' }\n";
		}

		$the_js .= "];\n";

		return $the_js;
	}

	/**
	 * Display a dialog box for this plugin's associated TinyMCE plugin.  Called from TinyMCE via AJAX.
	 */
	function timed_content_plugin_get_tinymce_dialog() {
		wp_enqueue_style( TIMED_CONTENT_SLUG . '-jquery-ui-css', TIMED_CONTENT_JQUERY_UI_CSS );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_register_style(
			TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-css',
			TIMED_CONTENT_JQUERY_UI_TIMEPICKER_CSS
		);
		wp_enqueue_style( TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-css' );
		wp_register_script(
			TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-js',
			TIMED_CONTENT_JQUERY_UI_TIMEPICKER_JS,
			array( 'jquery', 'jquery-ui-datepicker' ),
			TIMED_CONTENT_VERSION
		);
		wp_enqueue_script( TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-js' );
		if ( ! ( wp_script_is( TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js', 'registered' ) ) ) {
			wp_register_script(
				TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js',
				TIMED_CONTENT_PLUGIN_URL . '/js/timed-content-datetime-i18n.js',
				array( 'jquery', 'jquery-ui-datepicker', TIMED_CONTENT_SLUG . '-jquery-ui-timepicker-js' ),
				TIMED_CONTENT_VERSION
			);
			wp_enqueue_script( TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js' );
			wp_localize_script(
				TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js',
				'TimedContentJQDatepickerI18n',
				$this->jquery_ui_datetime_datepicker_i18n
			);
			wp_localize_script(
				TIMED_CONTENT_SLUG . '-jquery-ui-datetime-i18n-js',
				'TimedContentJQTimepickerI18n',
				$this->jquery_ui_datetime_timepicker_i18n
			);
		}

		wp_register_script(
			'TIMED_CONTENT_SLUG' . '-tinymce-popup-js',
			includes_url() . '/js/tinymce/tiny_mce_popup.js',
			null,
			TIMED_CONTENT_VERSION
		);
		wp_enqueue_script( 'TIMED_CONTENT_SLUG' . '-tinymce-popup-js' );
		wp_register_script(
			'TIMED_CONTENT_SLUG' . '-tinymce-mctabs-js',
			includes_url() . '/js/tinymce/utils/mctabs.js',
			null,
			TIMED_CONTENT_VERSION
		);
		wp_enqueue_script( 'TIMED_CONTENT_SLUG' . '-tinymce-mctabs-js' );

		ob_start();
		require __DIR__ . '/../tinymce_plugin/dialog.php';
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
		die();
	}

	/**
	 * Add custom columns to the Timed Content Rules overview page
	 */
	function add_desc_column_head( $defaults ) {
		unset( $defaults['date'] );
		$defaults['description'] = __( 'Description', 'timed-content' );
		$defaults['shortcode']   = __( 'Shortcode', 'timed-content' );

		return $defaults;
	}

	/**
	 * Display content associated with custom columns on the Timed Content rules overview page
	 */
	function add_desc_column_content( $column_name, $post_id ) {
		if ( 'shortcode' === $column_name ) {
			echo sprintf(
				'<code>[%s id="%s"]...[/%s]</code>',
				TIMED_CONTENT_SHORTCODE_RULE,
				$post_id,
				TIMED_CONTENT_SHORTCODE_RULE
			);
		}
		if ( 'description' === $column_name ) {
			$desc = $this->get_schedule_description_by_id( $post_id );
			if ( $desc ) {
				echo sprintf( '<em>%s</em>', $desc );
			}
		}
	}

	/**
	 * Display a count of Timed Content rules in the Dashboard's Right Now widget
	 */
	function add_rules_count() {
		if ( ! post_type_exists( TIMED_CONTENT_RULE_TYPE ) ) {
			return;
		}

		$num_posts = wp_count_posts( TIMED_CONTENT_RULE_TYPE );
		$num       = number_format_i18n( $num_posts->publish );
		$text      = _n(
			'Timed Content rule',
			'Timed Content rules',
			intval( $num_posts->publish ),
			'timed-content'
		);
		if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
			echo "<a href='edit.php?post_type=" . TIMED_CONTENT_RULE_TYPE . "'>"
				. '<li class="' . TIMED_CONTENT_RULE_TYPE . '-count">'
				. $num
				. ' '
				. $text
				. '</a></li>';
		}

		if ( $num_posts->pending > 0 ) {
			$num  = number_format_i18n( $num_posts->pending );
			$text = _n(
				'Timed Content rule pending',
				'Timed Content rules pending',
				intval( $num_posts->pending ),
				'timed-content'
			);
			if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
				echo "<a href='edit.php?post_status=pending&post_type=" . TIMED_CONTENT_RULE_TYPE . "'>"
					. '<li class="' . TIMED_CONTENT_RULE_TYPE . '-count">'
					. $num
					. ' '
					. $text
					. '</a></li>';
			}
		}
	}

	/**
	 * Setup custom fields for Timed Content rules
	 */
	function setup_custom_fields() {
		global $post;

		$now_ts        = time();
		$now_plus1h_dt = new DateTime();
		$now_plus2h_dt = new DateTime();
		$now_plus1y_dt = new DateTime();
		$now_plus1h_dt->setTimeStamp( $now_ts );
		$now_plus2h_dt->setTimeStamp( $now_ts );
		$now_plus1y_dt->setTimeStamp( $now_ts );
		$now_plus1h_dt->add( new DateInterval( 'PT1H' ) );
		$now_plus2h_dt->add( new DateInterval( 'PT2H' ) );
		$now_plus1y_dt->add( new DateInterval( 'P1Y' ) );

		$post_id          = ( isset( $_GET['post'] ) && ( TIMED_CONTENT_RULE_TYPE === get_post_type( $_GET['post'] ) ) ? intval( $_GET['post'] ) : intval( 0 ) );
		$exceptions_dates = get_post_meta( $post_id, TIMED_CONTENT_RULE_POSTMETA_PREFIX . 'exceptions_dates' );
		if ( false !== $exceptions_dates && isset( $exceptions_dates[0] ) && is_array( $exceptions_dates[0] ) ) {
			$timed_content_rules_exceptions_dates = $exceptions_dates[0];
			sort( $timed_content_rules_exceptions_dates, SORT_NUMERIC );
			$timed_content_rules_exceptions_dates = array_unique( $timed_content_rules_exceptions_dates );

			// If the exceptions are stored as timestamps, convert them to ISO first
			$num = 0;
			while ( $num < count( $timed_content_rules_exceptions_dates ) ) {
				if ( is_numeric( $timed_content_rules_exceptions_dates[ $num ] ) ) {
					$timed_content_rules_exceptions_dates[ $num ] = $this->format_timestamp( 'Y-m-d', $timed_content_rules_exceptions_dates[ $num ] );
				}
				$num++;
			}

			$timed_content_rules_exceptions_dates_array = array_combine( $timed_content_rules_exceptions_dates, $timed_content_rules_exceptions_dates );
		} else {
			$timed_content_rules_exceptions_dates_array = array( '0' => __( '- No exceptions set -', 'timed-content' ) );
		}

		$this->rule_occurrence_custom_fields = array(
			array(
				'name'        => 'action',
				'display'     => 'block',
				'title'       => __( 'Action', 'timed-content' ),
				'description' => __( 'Sets the action to be performed when the rule is active.', 'timed-content' ),
				'type'        => 'radio',
				'values'      => array(
					'1' => __( 'Show the content', 'timed-content' ),
					'0' => __( 'Hide the content', 'timed-content' ),
				),
				'default'     => '1',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'instance_start',
				'display'     => 'block',
				'title'       => __( 'Starting date/time', 'timed-content' ),
				'description' => __( 'Sets the date and time for the beginning of the first active period for this rule.', 'timed-content' ),
				'type'        => 'datetime',
				'default'     => array(
					'date' => $this->format_timestamp( 'Y-m-d', $now_plus1h_dt->getTimeStamp() ),
					'time' => $this->format_timestamp( 'H:i', $now_plus1h_dt->getTimeStamp() ),
				),
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'instance_end',
				'display'     => 'block',
				'title'       => __( 'Ending date/time', 'timed-content' ),
				'description' => __( 'Sets the date and time for the end of the first active period for this rule.', 'timed-content' ),
				'type'        => 'datetime',
				'default'     => array(
					'date' => $this->format_timestamp( 'Y-m-d', $now_plus2h_dt->getTimeStamp() ),
					'time' => $this->format_timestamp( 'H:i', $now_plus2h_dt->getTimeStamp() ),
				),
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'timezone',
				'display'     => 'block',
				'title'       => __( 'Timezone', 'timed-content' ),
				'description' => __( 'Select the timezone you wish to use for this rule.', 'timed-content' ),
				'type'        => 'timezone-list',
				'default'     => get_option( 'timezone_string' ),
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
		);

		$this->rule_pattern_custom_fields = array(
			array(
				'name'        => 'frequency',
				'display'     => 'block',
				'title'       => __( 'Frequency', 'timed-content' ),
				'description' => __( 'Sets the frequency at which the action should be repeated.', 'timed-content' ),
				'type'        => 'list',
				'default'     => '1',
				'values'      => $this->rule_freq_array,
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'hourly_num_of_hours',
				'display'     => 'none',
				'title'       => __( 'Interval of recurrences', 'timed-content' ),
				'description' => __( 'Repeat this action every X hours.', 'timed-content' ),
				'type'        => 'number',
				'default'     => '1',
				'min'         => '1',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'daily_num_of_days',
				'display'     => 'none',
				'title'       => __( 'Interval of recurrences', 'timed-content' ),
				'description' => __( 'Repeat this action every X days.', 'timed-content' ),
				'type'        => 'number',
				'default'     => '1',
				'min'         => '1',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'weekly_num_of_weeks',
				'display'     => 'none',
				'title'       => __( 'Interval of recurrences', 'timed-content' ),
				'description' => __( 'Repeat this action every X weeks.', 'timed-content' ),
				'type'        => 'number',
				'default'     => '1',
				'min'         => '1',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'weekly_days_of_week_to_repeat',
				'display'     => 'none',
				'title'       => __( 'Repeat on the following days', 'timed-content' ),
				'description' => __( 'Repeat this action on these days of the week <strong>instead</strong> of the day of week the starting date/time falls on.', 'timed-content' ),
				'type'        => 'checkbox-list',
				'default'     => array(),
				'values'      => $this->rule_days_array,
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'monthly_num_of_months',
				'display'     => 'none',
				'title'       => __( 'Interval of recurrences', 'timed-content' ),
				'description' => __( 'Repeat this action every X months.', 'timed-content' ),
				'type'        => 'number',
				'default'     => '1',
				'min'         => '1',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'monthly_nth_weekday_of_month',
				'display'     => 'none',
				'title'       => __( 'Repeat on a specific weekday of the month', 'timed-content' ),
				'description' => __( 'Repeat this action on a specific weekday of the month (for example, "every third Tuesday"). Check this box to select a pattern below.', 'timed-content' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'monthly_nth_weekday_of_month_nth',
				'display'     => 'none',
				'title'       => __( 'Weekday ordinal', 'timed-content' ),
				'description' => __( 'Select a value for week of the month (for example "first", "second", etc.).', 'timed-content' ),
				'type'        => 'list',
				'default'     => 0,
				'values'      => $this->rule_ordinal_array,
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'monthly_nth_weekday_of_month_weekday',
				'display'     => 'none',
				'title'       => __( 'Day of the week', 'timed-content' ),
				'description' => __( 'Select the day of week.', 'timed-content' ),
				'type'        => 'list',
				'default'     => 0,
				'values'      => $this->rule_ordinal_days_array,
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'yearly_num_of_years',
				'display'     => 'none',
				'title'       => __( 'Interval of recurrences', 'timed-content' ),
				'description' => __( 'Repeat this action every X years.', 'timed-content' ),
				'type'        => 'number',
				'default'     => '1',
				'min'         => '1',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
		);

		$this->rule_recurrence_custom_fields = array(
			array(
				'name'        => 'recurrence_duration',
				'display'     => 'block',
				'title'       => __( 'How often to repeat this action', 'timed-content' ),
				'description' => '',
				'type'        => 'radio',
				'values'      => array(
					'recurrence_duration_end_date'   => __( 'Keep repeating until a given date', 'timed-content' ),
					'recurrence_duration_num_repeat' => __( 'Repeat a set number of times', 'timed-content' ),
				),
				'default'     => 'recurrence_duration_end_date',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'recurrence_duration_end_date',
				'display'     => 'none',
				'title'       => __( 'End Date', 'timed-content' ),
				'description' => __( 'Using the settings above, repeat this action until this date.', 'timed-content' ),
				'type'        => 'date',
				'default'     => $this->format_timestamp( 'Y-m-d', $now_plus1y_dt->getTimeStamp() ),
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'recurrence_duration_num_repeat',
				'display'     => 'none',
				'title'       => __( 'Number of repetitions', 'timed-content' ),
				'description' => __( 'Using the settings above, repeat this action this many times.', 'timed-content' ),
				'type'        => 'number',
				'default'     => '1',
				'min'         => '1',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
		);

		$this->rule_exceptions_custom_fields = array(
			array(
				'name'        => 'exceptions_dates_picker',
				'display'     => 'block',
				'title'       => __( 'Add exception date:', 'timed-content' ),
				'description' => __( 'Select a date to add to the exception dates list.', 'timed-content' ),
				'type'        => 'date',
				'default'     => '',
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
			array(
				'name'        => 'exceptions_dates',
				'display'     => 'block',
				'title'       => __( 'Exception dates list', 'timed-content' ),
				'description' => __( 'Dates that this Timed Content rule will not be active.  Double-click on a date to remove it from the list.', 'timed-content' ),
				'type'        => 'menu',
				'values'      => $timed_content_rules_exceptions_dates_array,
				'size'        => '10',
				'default'     => array(),
				'scope'       => array( TIMED_CONTENT_RULE_TYPE ),
				'capability'  => 'edit_posts',
			),
		);

		$rule_description = '';
		if ( isset( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
			if ( get_post_type( $post_id ) === TIMED_CONTENT_RULE_TYPE ) {
				$rule_description = $this->get_schedule_description_by_id( $post_id );
			}
		}

		$scf = new CustomFieldsInterface(
			'timed_content_rule_schedule',
			__( 'Rule description/schedule', 'timed-content' ),
			'',
			TIMED_CONTENT_RULE_POSTMETA_PREFIX,
			array( TIMED_CONTENT_RULE_TYPE ),
			array(),
			$this->jquery_ui_datetime_datepicker_i18n,
			$this->jquery_ui_datetime_timepicker_i18n,
			$rule_description,
		);
		$ocf = new CustomFieldsInterface(
			'timed_content_rule_initial_event',
			__( 'Action/Initial Event', 'timed-content' ),
			__( 'Set the action to be taken and when it should first run.', 'timed-content' ),
			TIMED_CONTENT_RULE_POSTMETA_PREFIX,
			array( TIMED_CONTENT_RULE_TYPE ),
			$this->rule_occurrence_custom_fields,
			$this->jquery_ui_datetime_datepicker_i18n,
			$this->jquery_ui_datetime_timepicker_i18n
		);
		$pcf = new CustomFieldsInterface(
			'timed_content_rule_recurrence',
			__( 'Repeating Pattern', 'timed-content' ),
			__( 'Set how often the action should repeat.', 'timed-content' ),
			TIMED_CONTENT_RULE_POSTMETA_PREFIX,
			array( TIMED_CONTENT_RULE_TYPE ),
			$this->rule_pattern_custom_fields,
			$this->jquery_ui_datetime_datepicker_i18n,
			$this->jquery_ui_datetime_timepicker_i18n
		);
		$rcf = new CustomFieldsInterface(
			'timed_content_rule_stop_condition',
			__( 'Stopping Condition', 'timed-content' ),
			__( 'Set how long or how many times the action should occur.', 'timed-content' ),
			TIMED_CONTENT_RULE_POSTMETA_PREFIX,
			array( TIMED_CONTENT_RULE_TYPE ),
			$this->rule_recurrence_custom_fields,
			$this->jquery_ui_datetime_datepicker_i18n,
			$this->jquery_ui_datetime_timepicker_i18n
		);
		$ecf = new CustomFieldsInterface(
			'timed_content_rule_exceptions',
			__( 'Exceptions', 'timed-content' ),
			__( 'Set up any exceptions to this Timed Content Rule.', 'timed-content' ),
			TIMED_CONTENT_RULE_POSTMETA_PREFIX,
			array( TIMED_CONTENT_RULE_TYPE ),
			$this->rule_exceptions_custom_fields,
			$this->jquery_ui_datetime_datepicker_i18n,
			$this->jquery_ui_datetime_timepicker_i18n
		);
	}

	/**
	 * Strips indices from an array
	 */
	function strip_array_indices( $array_to_strip ) {
		foreach ( $array_to_strip as $array_item ) {
			$new_array[] = $array_item;
		}

		return $new_array;
	}

	/**
	 * Convert dates and times to ISO format if needed
	 */
	function convert_date_time_parameters_to_iso( $args ) {
		$date_parsed = date_create_from_format( 'Y-m-d', $args['instance_start']['date'] );
		if ( false === $date_parsed ) {
			$date_source                    = strtotime( $this->date_time_to_english( $args['instance_start']['date'] ) );
			$args['instance_start']['date'] = $this->format_timestamp( 'Y-m-d', $date_source );
		}

		$date_parsed = date_create_from_format( 'Y-m-d', $args['instance_end']['date'] );
		if ( false === $date_parsed ) {
			$date_source                  = strtotime( $this->date_time_to_english( $args['instance_end']['date'] ) );
			$args['instance_end']['date'] = $this->format_timestamp( 'Y-m-d', $date_source );
		}

		$args['instance_start']['time'] = $this->convert_time_to_iso( $args['instance_start']['time'] );

		$args['instance_end']['time'] = $this->convert_time_to_iso( $args['instance_end']['time'] );

		$date_parsed = date_create_from_format( 'Y-m-d', $args['end_date'] );
		if ( false === $date_parsed ) {
			$date_source      = strtotime( $this->date_time_to_english( $args['end_date'] ) );
			$args['end_date'] = $this->format_timestamp( 'Y-m-d', $date_source );
		}

		if ( is_array( $args['exceptions_dates'] ) ) {
			foreach ( $args['exceptions_dates'] as $key => $value ) {
				$date_parsed = date_create_from_format( 'Y-m-d', $value );
				if ( false === $date_parsed ) {
					$date_source                      = strtotime( $this->date_time_to_english( $args['end_date'] ) );
					$args['exceptions_dates'][ $key ] = $this->format_timestamp( 'Y-m-d', $date_source );
				}
			}
		}

		return $args;
	}

	/**
	 * Convert time to ISO format if needed
	 */
	function convert_time_to_iso( $time ) {
		if ( strpos( $time, 'AM' ) !== false ) {
			$time_base = trim( substr( $time, 0, strlen( $time ) - 2 ) );
			$time_dt   = date_create_from_format( 'G:i', $time_base );
			if ( false !== $time_dt ) {
				$time = $this->format_timestamp( 'H:i', $time_dt->getTimestamp() );
			}
		} elseif ( strpos( $time, 'PM' ) !== false ) {
			$time_base = trim( substr( $time, 0, strlen( $time ) - 2 ) );
			$time_dt   = date_create_from_format( 'G:i', $time_base );
			if ( false !== $time_dt ) {
				$time = $this->format_timestamp( 'H:i', $time_dt->getTimestamp() + 43200 );
			}
		}

		return $time;
	}

	/**
	 * Set the timezone to be used for format_date()
	 */
	function set_format_timezone( $timezone ) {
		$this->current_timezone = new DateTimeZone( $timezone );
		if ( false === $this->current_timezone ) {
			$this->current_timezone = new DateTimeZone( 'UTC' );
		}
	}
	/**
	 * Format a given timestamp with the specified timezone
	 */
	function format_timestamp( $format, $timestamp ) {
		try {
			$dt = new DateTime();
			$dt->setTimezone( $this->current_timezone );
			$dt->setTimestamp( $timestamp );
		} catch ( Exception $e ) {
			return '';
		}

		return $dt->format( $format );
	}
}
