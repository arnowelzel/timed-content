<?php

class CustomFieldsInterface {
	/**
	 * @var  string $handle The handle for the box containing the custom fields
	 */
	var $handle = '';
	/**
	 * @var  string $label The label for the box containing the custom fields
	 */
	var $label = '';
	/**
	 * @var  string $desc The description/introduction for the box containing the custom fields
	 */
	var $desc = '';
	/**
	 * @var  string $prefix The prefix for storing custom fields in the postmeta table
	 */
	var $prefix = '';
	/**
	 * @var  array $post_types An array of public custom post types, plus the standard "post" and "page" - add the custom types you want to include here
	 */
	var $post_types = array();
	/**
	 * @var  array $custom_fields Defines the custom fields available
	 */
	var $custom_fields = array();
	/**
	 * @var array $jquery_ui_datetime_datepicker_i18n Array for jQuery datepicker UI localization
	 */
	var $jquery_ui_datetime_datepicker_i18n;
	/**
	 * @var array $jquery_ui_datetime_timepicker_i18n Array for jQuery timepicker UI localization
	 */
	var $jquery_ui_datetime_timepicker_i18n;

	/**
	 * @var string $rule_description_as_html Description of the rule as HTML
	 */
	var $rule_description_as_html;

	/**
	 * Constructor
	 */
	function __construct( $handle, $label, $desc, $prefix, $post_types, $custom_fields, $jquery_ui_datetime_datepicker_i18n, $jquery_ui_datetime_timepicker_i18n, $rule_description_as_html = '' ) {
		$this->handle                             = $handle;
		$this->label                              = $label;
		$this->desc                               = $desc;
		$this->prefix                             = $prefix;
		$this->post_types                         = $post_types;
		$this->custom_fields                      = $custom_fields;
		$this->jquery_ui_datetime_datepicker_i18n = $jquery_ui_datetime_datepicker_i18n;
		$this->jquery_ui_datetime_timepicker_i18n = $jquery_ui_datetime_timepicker_i18n;
		$this->rule_description_as_html           = $rule_description_as_html;

		add_action( 'admin_menu', array( &$this, 'create_custom_fields' ) );
		add_action( 'save_post', array( &$this, 'save_custom_fields' ), 1, 2 );
		// Comment this line out if you want to keep default custom fields meta box
		add_action( 'do_meta_boxes', array( &$this, 'remove_default_custom_fields' ), 10, 3 );
	}

	// Inspired by http://ca1.php.net/manual/en/function.timezone-identifiers-list.php#79284
	static function generate_timezone_select_options( $default_tz ) {
		$timezone_identifiers = timezone_identifiers_list();
		sort( $timezone_identifiers );
		$current_continent = '';
		$options_list      = '';

		foreach ( $timezone_identifiers as $timezone_identifier ) {
			list( $continent, ) = explode( '/', $timezone_identifier, 2 );
			if ( in_array(
				$continent,
				array(
					'Africa',
					'America',
					'Antarctica',
					'Arctic',
					'Asia',
					'Atlantic',
					'Australia',
					'Europe',
					'Indian',
					'Pacific',
				),
				true
			) ) {
				list(, $city) = explode( '/', $timezone_identifier, 2 );
				if ( strlen( $current_continent ) === 0 ) {
					// Start first continent optgroup
					$options_list .= '<optgroup label=\'' . $continent . '\'>';
				} elseif ( $current_continent !== $continent ) {
					// End old optgroup and start new continent optgroup
					$options_list .= '</optgroup><optgroup label=\'' . $continent . '\'>';
				}
				// Timezone
				$options_list .= '<option' .
					( ( $timezone_identifier === $default_tz ) ? ' selected=\'selected\'' : '' ) .
					' value=\'' . $timezone_identifier . '\'>' .
					str_replace( '_', ' ', $city ) . '</option>';
			}
			$current_continent = $continent;
		}
		$options_list .= '</optgroup>'; // End last continent optgroup

		return $options_list;
	}

	/**
	 * Remove the default Custom Fields meta box
	 */
	function remove_default_custom_fields( $type, $context, $post ) {
		foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
			foreach ( $this->post_types as $post_type ) {
				remove_meta_box( 'postcustom', $post_type, $context );
			}
		}
	}

	/**
	 * Create the new Custom Fields meta box
	 */
	function create_custom_fields() {
		// Only enqueue scripts if we're dealing with 'timed-content-rule' pages
		foreach ( $this->post_types as $a_post_type ) {
			if ( ( isset( $_GET['post_type'] ) && $_GET['post_type'] === $a_post_type )
				|| ( isset( $post_type ) && $post_type === $a_post_type )
				|| ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) === $a_post_type ) ) {

				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_script( 'jquery-ui-spinner' );

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

				if ( function_exists( 'add_meta_box' ) ) {
					add_meta_box(
						$this->handle,
						$this->label,
						array( &$this, 'display_custom_fields' ),
						$a_post_type,
						'normal',
						'high'
					);
				}
			}
		}
	}

	/**
	 * Display the new Custom Fields meta box
	 */
	function display_custom_fields() {
		global $post;

		if ( '' !== $this->rule_description_as_html ) {
			// If there is a rule description, then use this as we are not allowed to use generated
			// HTML code as variable without escaping or sanitizing but we need the button element

			echo '<div id="schedule_desc" style="font-style: italic; overflow-y: auto;">';
			echo wp_kses_post( $this->rule_description_as_html );
			echo '</div>';
			echo '<div id="tcr-dialogHolder" style="display:none;"></div>';
			echo '<div style="padding-top: 10px;">';
			echo '<input type="button" class="button button-primary" id="timed_content_rule_test" value="' .
				__( 'Show projected dates/times', 'timed-content' ) .
				'" />';
			echo '</div>';
		} else {
			// Otherwise output the description as text

			echo '<p>' . esc_html( $this->desc ) . '</p>';
		}
		echo '<div class="form-wrap">';
		wp_nonce_field( $this->handle, $this->handle . '_wpnonce', false, true );
		foreach ( $this->custom_fields as $custom_field ) {
			// Check scope
			$scope  = $custom_field['scope'];
			$output = false;
			foreach ( $scope as $scope_item ) {
				switch ( $scope_item ) {
					default:
						if ( $post->post_type === $scope_item ) {
							$output = true;
						}
						break;
				}
				if ( $output ) {
					break;
				}
			}
			// Check capability
			if ( ! current_user_can( $custom_field['capability'], $post->ID ) ) {
				$output = false;
			}
			// Output if allowed
			if ( $output ) {
				$field_name  = $this->prefix . $custom_field['name'];
				$field_title = $custom_field['title'];
				echo '<div class="form-field form-required"';
				echo ' id="' . esc_attr( $field_name ) . '_div"';
				echo ' style="display: ' . esc_attr( $custom_field['display'] ) . '">';
				switch ( $custom_field['type'] ) {
					case 'radio':
						$checked_value = get_post_meta(
							$post->ID,
							$field_name,
							true
						);
						if ( '' === $checked_value || false === $checked_value ) {
							$checked_value = $custom_field['default'];
						}
						echo '<strong>' . esc_html( $field_title ) . '</strong><br />';
						foreach ( $custom_field['values'] as $value => $label ) {
							echo '<input type="radio" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '_' . esc_attr( $value ) . '" value="' . esc_attr( $value ) . '"';
							if ( $checked_value === $value || intval( $checked_value ) === $value ) {
								echo ' checked="checked"';
							}
							echo ' /><label for="' . esc_attr( $field_name ) . '_' . esc_attr( $value ) . '" style="display: inline;">' . esc_html( $label ) . '</label><br />';
						}
						break;
					case 'menu':
						echo '<label for="' . esc_attr( $field_name ) . '" style="display:inline;"><strong>' . esc_html( $field_title ) . '</strong></label><br />';
						if ( sizeof( $custom_field['values'] ) === 0 ) {
							echo '<em>' . __( 'This menu is empty.', 'timed-content' ) . '</em>';
						} else {
							$selected_value = ( '' === get_post_meta(
								$post->ID,
								$field_name,
								true
							) ? $custom_field['default'] : get_post_meta(
								$post->ID,
								$field_name,
								true
							) );
							echo '<select name="' . esc_attr( $field_name ) . '[]" id="' . esc_attr( $field_name ) . '" style="width: auto; height: auto; padding: 3px;" size="' . esc_attr( $custom_field['size'] ) . '" multiple="multiple">';
							foreach ( $custom_field['values'] as $value => $label ) {
								echo '<option value="' . esc_attr( $value ) . '"';
								if ( $selected_value === $value || intval( $selected_value ) === $value ) {
									echo ' selected="selected"';
								}
								echo '>' . esc_html( $label ) . '</option>';
							}
							echo '</select>';
						}
						break;
					case 'list':
						// list
						echo '<label for="' . esc_attr( $field_name ) . '" style="display:inline;"><strong>' . esc_html( $field_title ) . '</strong></label><br />';
						if ( sizeof( $custom_field['values'] ) === 0 ) {
							echo '<em>' . __( 'This menu is empty.', 'timed-content' ) . '</em>';
						} else {
							$selected_value = ( '' === get_post_meta(
								$post->ID,
								$field_name,
								true
							) ? $custom_field['default'] : get_post_meta(
								$post->ID,
								$field_name,
								true
							) );
							echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" style="width: auto;">';
							foreach ( $custom_field['values'] as $value => $label ) {
								echo '<option value="' . esc_attr( $value ) . '"';
								if ( $selected_value === $value || intval( $selected_value ) === $value ) {
									echo ' selected="selected"';
								}
								echo '>' . esc_html( $label ) . '</option>';
							}
							echo '</select>';
						}
						break;
					case 'timezone-list':
						// timezone list
						$selected_value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );

						echo '<label for="' . esc_attr( $field_name ) . '" style="display:inline;"><strong>' . esc_html( $field_title ) . '</strong></label><br />';
						echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" style="width: auto;">';
						echo CustomFieldsInterface::generate_timezone_select_options( $selected_value );
						echo '</select>';
						break;
					case 'checkbox':
						// Checkbox
						$checked_value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );

						echo '<label for="' . esc_attr( $field_name ) . '" style="display:inline;"><strong>' . esc_html( $field_title ) . '</strong><br />';
						echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="yes"';
						if ( 'yes' === $checked_value ) {
							echo ' checked="checked"';
						}
						echo ' />' . __( 'Yes', 'timed-content' ) . '</label>';
						break;
					case 'checkbox-list':
						// Checkbox list
						$checked_value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );

						echo '<strong>' . esc_html( $field_title ) . '</strong><br />';
						if ( sizeof( $custom_field['values'] ) === 0 ) {
							echo '<em>' . __( 'This menu is empty.', 'timed-content' ) . '</em>';
						} else {
							foreach ( $custom_field['values'] as $value => $label ) {
								echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '[]" id="' . esc_attr( $field_name ) . '_' . esc_attr( $value ) . '" value="' . esc_attr( $value ) . '"';
								if ( ( is_array( $checked_value ) ) && ( in_array(
									(string) $value,
									$checked_value,
									true
								) ) ) {
									echo ' checked="checked"';
								}
								echo ' /><label for="' . esc_attr( $field_name ) . '_' . esc_attr( $value ) . '" style="display: inline;" >' . esc_html( $label ) . '</label><br />';
							}
						}
						break;
					case 'textarea':
					case 'wysiwyg':
						// Text area
						$value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );
						echo '<label for="' . esc_attr( $field_name ) . '"><strong>' . esc_html( $field_title ) . '</strong></label><br />';
						echo '<textarea name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" columns="30" rows="3">' . esc_html( $value ) . '</textarea>';
						// WYSIWYG
						if ( 'wysiwyg' === $custom_field['type'] ) {
							echo '<script type="text/javascript">' . PHP_EOL;
							echo '//<![CDATA[' . PHP_EOL;
							echo 'jQuery(document).ready(function () {' . PHP_EOL;
							echo '  jQuery("' . esc_js( $field_name ) . '").addClass("mceEditor");' . PHP_EOL;
							echo '  if (typeof (tinyMCE) == "object" && typeof (tinyMCE.execCommand) == "function") {' . PHP_EOL;
							echo '    tinyMCE.execCommand("mceAddControl", false, "' . esc_js( $field_name ) . '");' . PHP_EOL;
							echo '  }' . PHP_EOL;
							echo '});' . PHP_EOL;
							echo '//]]>' . PHP_EOL;
							echo '</script>' . PHP_EOL;
						}
						break;
					case 'color-picker':
						$value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );
						// Color picker using WP's built-in Iris jQuery plugin
						echo '<script type="text/javascript">' . PHP_EOL;
						echo '//<![CDATA[' . PHP_EOL;
						echo 'jQuery(document).ready(function () {' . PHP_EOL;
						echo '  var ' . esc_js( $field_name ) . 'Options = {' . PHP_EOL;
						echo '    defaultColor: false,' . PHP_EOL;
						echo '    hide: true,' . PHP_EOL;
						echo '    palettes: true' . PHP_EOL;
						echo '  };' . PHP_EOL;
						echo '  jQuery("#' . esc_js( $field_name ) . '").wpColorPicker(' . esc_js( $field_name ) . 'Options);' . PHP_EOL;
						echo '});' . PHP_EOL;
						echo '//]]>' . PHP_EOL;
						echo '</script>' . PHP_EOL;
						echo '<label for="' . esc_attr( $field_name ) . '"><strong>' . esc_html( $field_title ) . '</strong></label><br />';
						echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" size="7" maxlength="7" style="width: 100px;" />';
						break;
					case 'date':
						echo '<label for="' . esc_attr( $field_name ) . '" style="display:inline;"><strong>' . esc_html( $field_title ) . '</strong></label><br />';
						$value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );
						// Date picker using WP's built-in Datepicker jQuery plugin
						echo '<script type="text/javascript">' . PHP_EOL;
						echo '//<![CDATA[' . PHP_EOL;
						echo 'jQuery(document).ready(function () {' . PHP_EOL;
						echo '  jQuery("#' . esc_js( $field_name ) . '").datepicker(' . PHP_EOL;
						echo '    {' . PHP_EOL;
						echo '      onSelect: function (dateText, inst) {' . PHP_EOL;
						echo '        jQuery("#timed_content_rule_exceptions_dates option[value=\'0\']").remove();' . PHP_EOL;
						echo '        jQuery("#timed_content_rule_exceptions_dates").append(\'<option value="\' + dateText + \'">\' + dateText + \'</option>\');' . PHP_EOL;
						echo '        jQuery(this).val("");' . PHP_EOL;
						echo '        jQuery(this).trigger("change");' . PHP_EOL;
						echo '      },' . PHP_EOL;
						echo '      changeMonth: true,' . PHP_EOL;
						echo '      changeYear: true' . PHP_EOL;
						echo '    }' . PHP_EOL;
						echo '  );' . PHP_EOL;
						echo '});' . PHP_EOL;
						echo '//]]>' . PHP_EOL;
						echo '</script>' . PHP_EOL;
						echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" style="width: 175px;" />';
						break;
					case 'datetime':
						echo '<span style="display:inline;"><strong>' . esc_html( $field_title ) . '</strong></span><br />';
						$value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );
						foreach ( $value as $k => $v ) {
							$value[ $k ] = esc_html( $v );
						}
						// Date picker using WP's built-in Datepicker jQuery plugin
						// Time picker using jQuery UI Timepicker: http://fgelinas.com/code/timepicker
						echo '<script type="text/javascript">' . PHP_EOL;
						echo '//<![CDATA[' . PHP_EOL;
						echo 'jQuery(document).ready(function () {' . PHP_EOL;
						echo '  jQuery("#' . esc_js( $field_name ) . '_date").datepicker({' . PHP_EOL;
						echo '    changeMonth: true,' . PHP_EOL;
						echo '    changeYear: true' . PHP_EOL;
						echo '  });' . PHP_EOL;
						echo '  jQuery("#' . esc_js( $field_name ) . '_time").timepicker({' . PHP_EOL;
						echo '    defaultTime: \'now\'' . PHP_EOL;
						echo '  });' . PHP_EOL;
						echo '});' . PHP_EOL;
						echo '//]]>' . PHP_EOL;
						echo '</script>' . PHP_EOL;
						echo '<label for="' . esc_attr( $field_name ) . '_date" style="display:inline;"><em>' . _x(
							'Date',
							'Date field label',
							'timed-content'
						) . ':</em></label>';
						echo '<input type="text" name="' . esc_attr( $field_name ) . '[date]" id="' . esc_attr( $field_name ) . '_date" value="' . esc_attr( $value['date'] ) . '" style="width: 175px;" />';
						echo '<label for="' . esc_attr( $field_name ) . '_time" style="display:inline;"><em>' . _x(
							'Time',
							'Time field label',
							'timed-content'
						) . ":</em></label>\n";
						echo '<input type="text" name="' . esc_attr( $field_name ) . '[time]" id="' . esc_attr( $field_name ) . '_time" value="' . esc_attr( $value['time'] ) . '" style="width: 125px;" />';
						break;
					case 'number':
						$value = get_post_meta(
							$post->ID,
							$field_name,
							true
						);
						if ( '' === $value ) {
							$value = $custom_field['default'];
						} else {
							$value = intval( $value );
						}
						// Number picker using WP's built-in Spinner jQuery plugin
						echo '<script type="text/javascript">' . PHP_EOL;
						echo '//<![CDATA[' . PHP_EOL;
						echo 'jQuery(document).ready(function () {' . PHP_EOL;
						echo '  jQuery("#' . esc_js( $field_name ) . '").spinner({' . PHP_EOL;
						echo '    stop: function (event, ui) {' . PHP_EOL;
						echo '      jQuery(this).trigger("change");' . PHP_EOL;
						echo '    },' . PHP_EOL;
						if ( isset( $custom_field['min'] ) ) {
							echo '    min: ' . esc_js( $custom_field['min'] ) . ', ';
						}
						if ( isset( $custom_field['max'] ) ) {
							echo '	  max: ' . esc_js( $custom_field['max'] ) . ', ';
						}
						echo '  });' . PHP_EOL;
						echo '});' . PHP_EOL;
						echo '//]]>' . PHP_EOL;
						echo '</script>' . PHP_EOL;
						echo '<label for="' . esc_attr( $field_name ) . '" style="display:inline;"><strong>' . esc_html( $field_title ) . '</strong></label><br />';
						echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" size="2" />';
						break;
					case 'hidden':
						$value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );
						// Hidden field
						echo '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . ' />';
						break;
					default:
						$value = ( '' === get_post_meta(
							$post->ID,
							$field_name,
							true
						) ? $custom_field['default'] : get_post_meta(
							$post->ID,
							$field_name,
							true
						) );
						// Plain text field
						echo '<label for="' . esc_attr( $field_name ) . '"><strong>' . esc_html( $field_title ) . '</strong></label><br/>';
						echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . ' />';
						break;
				}
				if ( isset( $custom_field['description'] ) ) {
					echo '<p>' . esc_html( $custom_field['description'] ) . '</p>';
				}
				echo '</div>';
			}
		}
		echo '</div>';
	}

	/**
	 * Save the new Custom Fields values
	 */
	function save_custom_fields( $post_id, $post ) {
		// Only save if the user intends to save
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// Make sure the Save request is coming from the right place
		if ( ! isset( $_POST[ $this->handle . '_wpnonce' ] ) || ! wp_verify_nonce(
			$_POST[ $this->handle . '_wpnonce' ],
			$this->handle
		) ) {
			return;
		}
		// Make sure the user can edit this specific post
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// Make sure this meta box is associated with the right post types
		if ( ! in_array( $post->post_type, $this->post_types, true ) ) {
			return;
		}
		foreach ( $this->custom_fields as $custom_field ) {
			if ( current_user_can( $custom_field['capability'], $post_id ) ) {
				$field_name = $this->prefix . $custom_field['name'];
				if ( isset( $_POST[ $field_name ] ) ) {
					if ( is_array( $_POST[ $field_name ] ) ) {
						foreach ( $_POST[ $field_name ] as $k => $v ) {
							$_POST[ $field_name ][ $k ] = trim( sanitize_text_field( $v ) );
						}
					} else {
						$_POST[ $field_name ] = trim( sanitize_text_field( $_POST[ $field_name ] ) );
					}
					$value = $_POST[ $field_name ];
					// Auto-paragraphs for any WYSIWYG
					if ( 'wysiwyg' === $custom_field['type'] ) {
						$value = wpautop( $value );
					}
					if ( 'checkbox' === $custom_field['type'] ) {
						$value = 'yes';
					}
					if ( 'number' === $custom_field['type'] ) {
						$value = intval( $value );
					}
					update_post_meta( $post_id, $field_name, $value );
				} else {
					delete_post_meta( $post_id, $field_name );
				}
			}
		}
	}
}
