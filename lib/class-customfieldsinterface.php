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
	 * Constructor
	 */
	function __construct( $handle, $label, $desc, $prefix, $post_types, $custom_fields, $jquery_ui_datetime_datepicker_i18n, $jquery_ui_datetime_timepicker_i18n ) {
		$this->handle                             = $handle;
		$this->label                              = $label;
		$this->desc                               = $desc;
		$this->prefix                             = $prefix;
		$this->post_types                         = $post_types;
		$this->custom_fields                      = $custom_fields;
		$this->jquery_ui_datetime_datepicker_i18n = $jquery_ui_datetime_datepicker_i18n;
		$this->jquery_ui_datetime_timepicker_i18n = $jquery_ui_datetime_timepicker_i18n;

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
		?>
		<p><?php echo $this->desc; ?></p>
		<div class="form-wrap">
			<?php
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
					?>
					<div class="form-field form-required"
						id="<?php echo $this->prefix . $custom_field['name']; ?>_div"
						style="display: <?php echo $custom_field['display']; ?>">
						<?php
						switch ( $custom_field['type'] ) {
							case 'radio':
									$checked_value = get_post_meta(
										$post->ID,
										$this->prefix . $custom_field['name'],
										true
									);
								if ( '' === $checked_value || false === $checked_value ) {
									$checked_value = $custom_field['default'];
								}
									echo '<strong>' . $custom_field['title'] . "</strong><br />\n";
								foreach ( $custom_field['values'] as $value => $label ) {
									echo '<input type="radio" name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . '_' . $value . '" value="' . $value . '"';
									if ( $checked_value === $value || intval( $checked_value ) === $value ) {
										echo ' checked="checked"';
									}
									echo ' /><label for="' . $this->prefix . $custom_field['name'] . '_' . $value . '" style="display: inline;">' . $label . "</label><br />\n";
								}
								break;
							case 'menu':
									// menu
									echo '<label for="' . $this->prefix . $custom_field['name'] . '" style="display:inline;"><strong>' . $custom_field['title'] . "</strong></label><br />\n";
								if ( sizeof( $custom_field['values'] ) === 0 ) {
									echo '<em>' . __( 'This menu is empty.', 'timed-content' ) . "</em>\n";
								} else {
									$selected_value = ( '' === get_post_meta(
										$post->ID,
										$this->prefix . $custom_field['name'],
										true
									) ? $custom_field['default'] : get_post_meta(
										$post->ID,
										$this->prefix . $custom_field['name'],
										true
									) );
									echo '<select name="' . $this->prefix . $custom_field['name'] . '[]" id="' . $this->prefix . $custom_field['name'] . '" style="width: auto; height: auto; padding: 3px;" size="' . $custom_field['size'] . "\" multiple=\"multiple\">\n";
									foreach ( $custom_field['values'] as $value => $label ) {
										echo "\t<option value=\"" . $value . '"';
										if ( $selected_value === $value || intval( $selected_value ) === $value ) {
											echo ' selected="selected"';
										}
										echo '>' . $label . "</option>\n";
									}
									echo "</select>\n";
								}
								break;
							case 'list':
								// list
								echo '<label for="' . $this->prefix . $custom_field['name'] . '" style="display:inline;"><strong>' . $custom_field['title'] . "</strong></label><br />\n";
								if ( sizeof( $custom_field['values'] ) === 0 ) {
									echo '<em>' . __( 'This menu is empty.', 'timed-content' ) . "</em>\n";
								} else {
									$selected_value = ( '' === get_post_meta(
										$post->ID,
										$this->prefix . $custom_field['name'],
										true
									) ? $custom_field['default'] : get_post_meta(
										$post->ID,
										$this->prefix . $custom_field['name'],
										true
									) );
									echo '<select name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . "\" style=\"width: auto;\">\n";
									foreach ( $custom_field['values'] as $value => $label ) {
										echo "\t<option value=\"" . $value . '"';
										if ( $selected_value === $value || intval( $selected_value ) === $value ) {
											echo ' selected="selected"';
										}
										echo '>' . $label . "</option>\n";
									}
									echo "</select>\n";
								}
								break;
							case 'timezone-list':
								// timezone list
								$selected_value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );

								echo '<label for="' . $this->prefix . $custom_field['name'] . '" style="display:inline;"><strong>' . $custom_field['title'] . "</strong></label><br />\n";
								echo '<select name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . "\" style=\"width: auto;\">\n";
								echo CustomFieldsInterface::generate_timezone_select_options( $selected_value );
								echo "</select>\n";
								break;
							case 'checkbox':
								// Checkbox
								$checked_value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );

								echo '<label for="' . $this->prefix . $custom_field['name'] . '" style="display:inline;"><strong>' . $custom_field['title'] . "</strong><br />\n";
								echo '<input type="checkbox" name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . '" value="yes"';
								if ( 'yes' === $checked_value ) {
									echo ' checked="checked"';
								}
								echo ' />' . __( 'Yes', 'timed-content' ) . "</label>\n";
								break;
							case 'checkbox-list':
								// Checkbox list
								$checked_value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );

								echo '<strong>' . $custom_field['title'] . "</strong><br />\n";
								if ( sizeof( $custom_field['values'] ) === 0 ) {
									echo '<em>' . __( 'This menu is empty.', 'timed-content' ) . "</em>\n";
								} else {
									foreach ( $custom_field['values'] as $value => $label ) {
										echo '<input type="checkbox" name="' . $this->prefix . $custom_field['name'] . '[]" id="' . $this->prefix . $custom_field['name'] . '_' . $value . '" value="' . $value . '"';
										if ( ( is_array( $checked_value ) ) && ( in_array(
											$value,
											$checked_value,
											true
										) ) ) {
											echo ' checked="checked"';
										}
										echo ' /><label for="' . $this->prefix . $custom_field['name'] . '_' . $value . '" style="display: inline;" >' . $label . "</label><br />\n";
									}
								}
								break;
							case 'textarea':
							case 'wysiwyg':
								// Text area
								$value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );
								echo '<label for="' . $this->prefix . $custom_field['name'] . '"><strong>' . $custom_field['title'] . "</strong></label><br />\n";
								echo '<textarea name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . '" columns="30" rows="3">' . htmlspecialchars( $value ) . "</textarea>\n";
								// WYSIWYG
								if ( 'wysiwyg' === $custom_field['type'] ) {
									?>
							<script type="text/javascript">
								//<![CDATA[
								jQuery(document).ready(function () {
									jQuery("<?php echo $this->prefix . $custom_field['name']; ?>").addClass("mceEditor");
									if (typeof (tinyMCE) == "object" && typeof (tinyMCE.execCommand) == "function") {
										tinyMCE.execCommand("mceAddControl", false, "<?php echo $this->prefix . $custom_field['name']; ?>");
									}
								});
								//]]>
							</script>
									<?php
								}
								break;
							case 'color-picker':
								$value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );
								// Color picker using WP's built-in Iris jQuery plugin
								?>
							<script type="text/javascript">
								//<![CDATA[
								jQuery(document).ready(function () {
									var <?php echo $this->prefix . $custom_field['name']; ?>Options = {
										defaultColor: false,
										hide: true,
										palettes: true
									};

									jQuery("#<?php echo $this->prefix . $custom_field['name']; ?>").wpColorPicker(<?php echo $this->prefix . $custom_field['name']; ?>Options);
								});
								//]]>
							</script>
								<?php
								echo '<label for="' . $this->prefix . $custom_field['name'] . '"><strong>' . $custom_field['title'] . "</strong></label><br />\n";
								echo '<input type="text" name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . '" value="' . $value . "\" size=\"7\" maxlength=\"7\" style=\"width: 100px;\" />\n";
								break;
							case 'date':
								echo '<label for="' . $this->prefix . $custom_field['name'] . '" style="display:inline;"><strong>' . $custom_field['title'] . "</strong></label><br />\n";
								//									echo "<span style=\"display:inline;\"><strong>" . $custom_field[ 'title' ] . "</strong></span>&nbsp;&nbsp;\n";
								$value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );
								// Date picker using WP's built-in Datepicker jQuery plugin
								?>
							<script type="text/javascript">
								//<![CDATA[
								jQuery(document).ready(function () {
									jQuery("#<?php echo $this->prefix . $custom_field['name']; ?>").datepicker(
										{
												<?php
												if ( isset( $custom_field['custom_functions'] ) ) {
													echo $custom_field['custom_functions'];
												}
												?>
											changeMonth: true,
											changeYear: true
										}
									);
								});
								//]]>
							</script>
								<?php
								echo '<input type="text" name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . '" value="' . htmlspecialchars( $value ) . "\" style=\"width: 175px;\" />\n";
								break;
							case 'datetime':
								echo '<span style="display:inline;"><strong>' . $custom_field['title'] . "</strong></span><br />\n";
								$value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );
								foreach ( $value as $k => $v ) {
									$value[ $k ] = htmlspecialchars( $v );
								}
								// Date picker using WP's built-in Datepicker jQuery plugin
								// Time picker using jQuery UI Timepicker: http://fgelinas.com/code/timepicker
								?>
							<script type="text/javascript">
								//<![CDATA[
								jQuery(document).ready(function () {
									jQuery("#<?php echo $this->prefix . $custom_field['name']; ?>_date").datepicker(
										{
											changeMonth: true,
											changeYear: true
										}
									);
									jQuery("#<?php echo $this->prefix . $custom_field['name']; ?>_time").timepicker(
										{
											defaultTime: 'now'
										}
									);
								});
								//]]>
							</script>
								<?php
								echo '<label for="' . $this->prefix . $custom_field['name'] . '_date" style="display:inline;"><em>' . _x(
									'Date',
									'Date field label',
									'timed-content'
								) . ":</em></label>\n";
								echo '<input type="text" name="' . $this->prefix . $custom_field['name'] . '[date]" id="' . $this->prefix . $custom_field['name'] . '_date" value="' . $value['date'] . "\" style=\"width: 175px;\" />\n";
								echo '<label for="' . $this->prefix . $custom_field['name'] . '_time" style="display:inline;"><em>' . _x(
									'Time',
									'Time field label',
									'timed-content'
								) . ":</em></label>\n";
								echo '<input type="text" name="' . $this->prefix . $custom_field['name'] . '[time]" id="' . $this->prefix . $custom_field['name'] . '_time" value="' . $value['time'] . "\" style=\"width: 125px;\" />\n";
								break;
							case 'number':
								$value = get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								);
								if ( '' === $value ) {
									$value = $custom_field['default'];
								} else {
									$value = intval( $value );
								}
								// Number picker using WP's built-in Spinner jQuery plugin
								?>
							<script type="text/javascript">
								//<![CDATA[
								jQuery(document).ready(function () {
									jQuery("#<?php echo $this->prefix . $custom_field['name']; ?>").spinner(
										{
											stop: function (event, ui) {
												jQuery(this).trigger("change");
											},
												<?php
												if ( isset( $custom_field['min'] ) ) {
													?>
													min: <?php echo $custom_field['min']; ?>, <?php } ?>
												<?php
												if ( isset( $custom_field['max'] ) ) {
													?>
													max: <?php echo $custom_field['max']; ?> <?php } ?>
										}
									);
								});
								//]]>
							</script>
								<?php
								echo '<label for="' . $this->prefix . $custom_field['name'] . '" style="display:inline;"><strong>' . $custom_field['title'] . "</strong></label><br />\n";
								echo '<input type="text" name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . '" value="' . $value . "\" size=\"2\" />\n";
								break;
							case 'hidden':
								$value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );
								// Hidden field
								echo '<input type="hidden" name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . '" value="' . $value . "\" />\n";
								break;
							default:
								$value = ( '' === get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) ? $custom_field['default'] : get_post_meta(
									$post->ID,
									$this->prefix . $custom_field['name'],
									true
								) );
								// Plain text field
								echo '<label for="' . $this->prefix . $custom_field['name'] . '"><strong>' . $custom_field['title'] . "</strong></label><br/>\n";
								echo '<input type="text" name="' . $this->prefix . $custom_field['name'] . '" id="' . $this->prefix . $custom_field['name'] . '" value="' . $value . "\" />\n";
								break;
						}
						?>
						<?php
						if ( isset( $custom_field['description'] ) ) {
							echo '<p>' . $custom_field['description'] . "</p>\n";
						}
						?>
					</div>
					<?php
				}
			}
			?>
		</div>
		<?php
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
				if ( isset( $_POST[ $this->prefix . $custom_field['name'] ] ) ) {
					if ( is_array( $_POST[ $this->prefix . $custom_field['name'] ] ) ) {
						foreach ( $_POST[ $this->prefix . $custom_field['name'] ] as $k => $v ) {
							$_POST[ $this->prefix . $custom_field['name'] ][ $k ] = trim( $v );
						}
					} else {
						$_POST[ $this->prefix . $custom_field['name'] ] = trim( $_POST[ $this->prefix . $custom_field['name'] ] );
					}
					$value = $_POST[ $this->prefix . $custom_field['name'] ];
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
					update_post_meta( $post_id, $this->prefix . $custom_field['name'], $value );
				} else {
					delete_post_meta( $post_id, $this->prefix . $custom_field['name'] );
				}
			}
		}
	}
}
