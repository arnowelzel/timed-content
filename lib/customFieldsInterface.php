<?php
if ( !class_exists('customFieldsInterface') ) {

	class customFieldsInterface {
		/**
		* @var  string  $handle  The handle for the box containing the custom fields
		*/
		var $handle = "";
		/**
		* @var  string  $label  The label for the box containing the custom fields
		*/
		var $label = "";
		/**
		* @var  string  $desc  The description/introduction for the box containing the custom fields
		*/
		var $desc = "";
		/**
		* @var  string  $prefix  The prefix for storing custom fields in the postmeta table
		*/
		var $prefix = "";
		/**
		* @var  array  $postTypes  An array of public custom post types, plus the standard "post" and "page" - add the custom types you want to include here
		*/
		var $postTypes = array();
		/**
		* @var  array  $customFields  Defines the custom fields available
		*/
		var $customFields =	array();
		/**
		* PHP 5 Constructor
		*/
		function __construct( $handle, $label, $desc, $prefix, $postTypes, $customFields ) {
	
			$this->handle = $handle;
			$this->label = $label;
			$this->desc = $desc;
			$this->prefix = $prefix;
			$this->postTypes = $postTypes ;
			$this->customFields = $customFields;
			add_action( 'admin_menu', array( &$this, 'createCustomFields' ) );
			add_action( 'save_post', array( &$this, 'saveCustomFields' ), 1, 2 );
			// Comment this line out if you want to keep default custom fields meta box
			add_action( 'do_meta_boxes', array( &$this, 'removeDefaultCustomFields' ), 10, 3 );
		}

        // Inspired by http://ca1.php.net/manual/en/function.timezone-identifiers-list.php#79284
        static function __generateTimezoneSelectOptions( $default_tz ) {
            $timezone_identifiers = timezone_identifiers_list();
            sort( $timezone_identifiers );
            $current_continent = "";
            $options_list = "";

            foreach ( $timezone_identifiers as $timezone_identifier ) {
                list( $continent, ) = explode( "/", $timezone_identifier, 2);
                if ( in_array( $continent, array( "Africa", "America", "Antarctica", "Arctic", "Asia", "Atlantic", "Australia", "Europe", "Indian", "Pacific" ) ) ) {
                    list( , $city ) = explode( "/", $timezone_identifier, 2);
                    if ( strlen( $current_continent ) === 0 ) {
                        $options_list .= "<optgroup label=\"" . $continent . "\">"; // Start first continent optgroup
                    }
                    elseif ( $current_continent != $continent ) {
                        $options_list .= "</optgroup><optgroup label=\"" . $continent . "\">"; // End old optgroup and start new continent optgroup
                    }
                    $options_list .= "<option" . ( ( $timezone_identifier == $default_tz ) ? " selected=\"selected\"" : "" )
                        . " value=\"" . $timezone_identifier . "\">" . str_replace( "_", " ", $city ). "</option>"; //Timezone
                }
                $current_continent = $continent;
            }
            $options_list .= "</optgroup>"; // End last continent optgroup

            return $options_list;
        }

        /**
		* Remove the default Custom Fields meta box
		*/
		function removeDefaultCustomFields( $type, $context, $post ) {
			foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
				foreach ( $this->postTypes as $postType ) {
					remove_meta_box( 'postcustom', $postType, $context );
				}
			}
		}
		/**
		* Create the new Custom Fields meta box
		*/
		function createCustomFields() {	
		
			// Let's figure out i18n for the date- and timepickers
			$dayNames = array( __( "Sunday", 'timed-content' ),
				__( "Monday", 'timed-content' ),
				__( "Tuesday", 'timed-content' ),
				__( "Wednesday", 'timed-content' ),
				__( "Thursday", 'timed-content' ),
				__( "Friday", 'timed-content' ),
				__( "Saturday", 'timed-content' ) );
			$dayNamesShort = array( _x( "Sun", "Three-letter abbreviation for 'Sunday'", 'timed-content' ),
    			_x( "Mon", "Three-letter abbreviation for Monday", 'timed-content' ),
				_x( "Tue", "Three-letter abbreviation for Tuesday", 'timed-content' ),
				_x( "Wed", "Three-letter abbreviation for Wednesday", 'timed-content' ),
				_x( "Thu", "Three-letter abbreviation for Thursday", 'timed-content' ),
				_x( "Fri", "Three-letter abbreviation for Friday", 'timed-content' ),
				_x( "Sat", "Three-letter abbreviation for Saturday", 'timed-content' ) );
			$dayNamesMin = array( _x( "Su", "Two-letter abbreviation for Sunday'", 'timed-content' ),
                _x( "Mo", "Two-letter abbreviation for Monday", 'timed-content' ),
                _x( "Tu", "Two-letter abbreviation for Tuesday", 'timed-content' ),
                _x( "We", "Two-letter abbreviation for Wednesday", 'timed-content' ),
                _x( "Th", "Two-letter abbreviation for Thursday", 'timed-content' ),
                _x( "Fr", "Two-letter abbreviation for Friday", 'timed-content' ),
                _x( "Sa", "Two-letter abbreviation for Saturday", 'timed-content' ) );
			$monthNames = array( __( "January", 'timed-content' ),
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
			$monthNamesShort = array( _x( "Jan", "Three-letter abbreviation for January", 'timed-content' ),
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
            $timePeriods = array( _x( "AM", "Abbreviation for first 12-hour period in a day", 'timed-content' ),
                _x( "PM", "Abbreviation for second 12-hour period in a day", 'timed-content' ) );
			$datepicker_i18n = array(
                "closeText" => _x( "Done", "jQuery UI Datepicker 'Close' label", "timed-content" ), // Display text for close link
                "prevText" => _x( "Prev", "jQuery UI Datepicker 'Previous' label", "timed-content" ), // Display text for previous month link
                "nextText" => _x( "Next", "jQuery UI Datepicker 'Next' label", "timed-content" ), // Display text for next month link
                "currentText" => _x( "Today", "jQuery UI Datepicker 'Today' label", "timed-content" ), // Display text for current month link
                "monthNames" => "['" . join( "','", $monthNames ) . "']", // Names of months for drop-down and formatting
                "monthNamesShort" => "['" . join( "','", $monthNamesShort ) . "']", // For formatting
                "dayNames" => "['" . join( "','", $dayNames ) . "']", // For formatting
                "dayNamesShort" => "['" . join( "','", $dayNamesShort ) . "']", // For formatting
                "dayNamesMin" => "['" . join( "','", $dayNamesShort ) . "']", // Column headings for days starting at Sunday
                "weekHeader" => _x( "Wk", "jQuery UI Datepicker 'Week' label", "timed-content" ), // Column header for week of the year
                "dateFormat" => _x( "MM d, yy", "jQuery UI Datepicker 'Date' format", 'timed-content' ),
                "firstDay" => 0, // The first day of the week, Sun = 0, Mon = 1, ...
                "isRTL" => "false", // True if right-to-left language, false if left-to-right
                "showMonthAfterYear" => "false", // True if the year select precedes month, false for month then year
                "yearSuffix" => '' // Additional text to append to the year in the month headers			
			);
			$timepicker_i18n = array(
                "hourText" => _x( "Hour", "jQuery UI Timepicker 'Hour' label", "timed-content" ),
                "minuteText" => _x( "Minute", "jQuery UI Timepicker 'Minute' label", "timed-content" ),
                "amPmText" => "['" . join( "','", $timePeriods ) . "']",
                "closeButtonText" => _x( "Done", "jQuery UI Timepicker 'Done' label", "timed-content" ),
                "nowButtonText" => _x( "Now", "jQuery UI Timepicker 'Now' label", "timed-content" ),
                "deselectButtonText" => _x( "Deselect", "jQuery UI Timepicker 'Deselect' label", "timed-content" ) );
					
			// Only enqueue scripts if we're dealing with 'timed-content-rule' pages
			foreach ( $this->postTypes as $a_postType ) {
				if ( ( isset( $_GET['post_type'] ) && $_GET['post_type'] == $a_postType ) 
					|| ( isset( $post_type ) && $post_type == $a_postType )
					|| ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == $a_postType ) ) {

					wp_enqueue_style( 'wp-color-picker' );
					wp_enqueue_script( 'wp-color-picker' );
                    wp_enqueue_style( 'timed-content-jquery-ui-css', TIMED_CONTENT_JQUERY_UI_CSS, false, TIMED_CONTENT_VERSION );
					wp_enqueue_script( 'jquery-ui-datepicker' );
					if( isset( $datepicker_i18n ) ) {
						wp_register_script( 'timed-content-jquery-ui-datepicker-i18n-js', TIMED_CONTENT_PLUGIN_URL . "/js/timed-content-datepicker-i18n.js", array( 'jquery', 'jquery-ui-datepicker' ), TIMED_CONTENT_VERSION );
						wp_enqueue_script( 'timed-content-jquery-ui-datepicker-i18n-js' );
						wp_localize_script( 'timed-content-jquery-ui-datepicker-i18n-js', 'TimedContentJQDatepickerI18n', $datepicker_i18n );
					}
						
					wp_enqueue_script( 'jquery-ui-spinner' ); 
					wp_register_style( 'timed-content-jquery-ui-timepicker-css', TIMED_CONTENT_JQUERY_UI_TIMEPICKER_CSS, array( TIMED_CONTENT_JQUERY_UI_CSS ), TIMED_CONTENT_VERSION );
					wp_enqueue_style( 'timed-content-jquery-ui-timepicker-css' );
					wp_register_script( 'timed-content-jquery-ui-timepicker-js', TIMED_CONTENT_JQUERY_UI_TIMEPICKER_JS, array('jquery', 'jquery-ui-datepicker'), TIMED_CONTENT_VERSION );
					wp_enqueue_script( 'timed-content-jquery-ui-timepicker-js' );
					if( isset( $timepicker_i18n ) ) {
						wp_register_script( 'timed-content-jquery-ui-timepicker-i18n-js', TIMED_CONTENT_PLUGIN_URL . "/js/timed-content-timepicker-i18n.js", array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-timepicker' ), TIMED_CONTENT_VERSION );
						wp_enqueue_script( 'timed-content-jquery-ui-timepicker-i18n-js' );
						wp_localize_script( 'timed-content-jquery-ui-timepicker-i18n-js', 'TimedContentJQTimepickerI18n', $timepicker_i18n );
					}
					if ( function_exists( 'add_meta_box' ) ) 
						add_meta_box( $this->handle, $this->label, array( &$this, 'displayCustomFields' ), $a_postType, 'normal', 'high' );
				}
			}
		}
		/**
		* Display the new Custom Fields meta box
		*/
		function displayCustomFields() {
			global $post;
			?>
			<p><?php echo $this->desc; ?></p>
			<div class="form-wrap">
				<?php
				wp_nonce_field( $this->handle, $this->handle . '_wpnonce', false, true );
				foreach ( $this->customFields as $customField ) {
					// Check scope
					$scope = $customField[ 'scope' ];
					$output = false;
					foreach ( $scope as $scopeItem ) {
						switch ( $scopeItem ) {
							default: {
								if ( $post->post_type == $scopeItem )
									$output = true;
								break;
							}
						}
						if ( $output ) break;
					}
					// Check capability
					if ( !current_user_can( $customField['capability'], $post->ID ) )
						$output = false;
					// Output if allowed
					if ( $output ) { ?>
						<div class="form-field form-required" id="<?php echo $this->prefix . $customField[ 'name' ]; ?>_div" style="display: <?php echo $customField[ 'display' ]; ?>">
							<?php
							switch ( $customField[ 'type' ] ) {
								case "radio": {
									// radio
									$checked_value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
									echo "<strong>" . $customField[ 'title' ] . "</strong><br />\n";
									foreach ( $customField['values'] as $value => $label ) {
										echo "<input type=\"radio\" name=\"" . $this->prefix . $customField[ 'name' ] . "\" id=\"" . $this->prefix . $customField[ 'name' ] . "_" . $value . "\" value=\"" . $value . "\"";
										if ( $checked_value == $value )
											echo " checked=\"checked\"";
										echo " style=\"width: 30px;\" /><label for=\"" . $this->prefix . $customField[ 'name' ] . "_" . $value . "\" style=\"display: inline;\">" . $label . "</label><br />\n";
									}
									break;
								}
								case "menu": {
									// menu
									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "\" style=\"display:inline;\"><strong>" . $customField[ 'title' ] . "</strong></label><br />\n";
									if ( sizeof ( $customField['values'] ) == 0 )
										echo "<em>" . __( "This menu is empty.", "timed-content" ) . "</em>\n";
									else {
										$selected_value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
										echo "<select name=\"" . $this->prefix . $customField['name'] . "\" id=\"" . $this->prefix . $customField['name'] . "\" style=\"width: auto; height: auto; padding: 3px;\" size=\"" . $customField['size']  . "\" multiple=\"multiple\">\n";
										foreach ( $customField['values'] as $value => $label ) {
											echo "\t<option value=\"" . $value . "\"";
											if ( $selected_value == $value )
												echo " selected=\"selected\"";
											echo ">" . $label . "</option>\n";
										}
										echo "</select>\n";
									}
									break;
								}
								case "list": {
									// list
									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "\" style=\"display:inline;\"><strong>" . $customField[ 'title' ] . "</strong></label>&nbsp;&nbsp;\n";
									if ( sizeof ( $customField['values'] ) == 0 )
										echo "<em>" . __( "This menu is empty.", "timed-content" ) . "</em>\n";
									else {
										$selected_value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
										echo "<select name=\"" . $this->prefix . $customField['name'] . "\" id=\"" . $this->prefix . $customField['name'] . "\" style=\"width: auto;\">\n";
										foreach ( $customField['values'] as $value => $label ) {
											echo "\t<option value=\"" . $value . "\"";
											if ( $selected_value == $value )
												echo " selected=\"selected\"";
											echo ">" . $label . "</option>\n";
										}
										echo "</select>\n";
									}
									break;
								}
								case "timezone-list": {
									// timezone list
									$selected_value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );

									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "\" style=\"display:inline;\"><strong>" . $customField[ 'title' ] . "</strong></label>&nbsp;&nbsp;\n";
									echo "<select name=\"" . $this->prefix . $customField['name'] . "\" id=\"" . $this->prefix . $customField['name'] . "\" style=\"width: auto;\">\n";
									echo customFieldsInterface::__generateTimezoneSelectOptions( $selected_value );
									echo "</select>\n";
									break;
								}
								case "checkbox": {
									// Checkbox
									$checked_value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );

									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "\" style=\"display:inline;\"><strong>" . $customField[ 'title' ] . "</strong></label>&nbsp;&nbsp;\n";
									echo "<input type=\"checkbox\" name=\"" . $this->prefix . $customField['name'] . "\" id=\"" . $this->prefix . $customField['name'] . "\" value=\"yes\"";
									if ( $checked_value == "yes" )
										echo " checked=\"checked\"";
									echo " style=\"width: 30px;\" />\n";
									break;
								}
								case "checkbox-list": {
									// Checkbox list
									$checked_value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );

									echo "<strong>" . $customField[ 'title' ] . "</strong><br />\n";
									if ( sizeof ( $customField['values'] ) == 0 )
										echo "<em>" . __( "This menu is empty.", "timed-content" ) . "</em>\n";
									else {
										foreach ( $customField['values'] as $value => $label ) {
											echo "<input type=\"checkbox\" name=\"" . $this->prefix . $customField['name'] . "[]\" id=\"" . $this->prefix . $customField['name'] . "_" . $value . "\" value=\"" . $value . "\"";
											if ( ( is_array( $checked_value ) ) && ( in_array( $value, $checked_value ) ) )
												echo " checked=\"checked\"";
											echo " style=\"width: 30px;\" /><label for=\"" . $this->prefix . $customField['name'] . "_" . $value . "\" style=\"display: inline;\" >" . $label . "</label><br />\n";
										}
									}
									break;
								}
								case "textarea":
								case "wysiwyg": {
									// Text area
									$value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] ."\"><strong>" . $customField[ 'title' ] . "</strong></label>\n";
									echo "<textarea name=\"" . $this->prefix . $customField[ 'name' ] . "\" id=\"" . $this->prefix . $customField[ 'name' ] . "\" columns=\"30\" rows=\"3\">" . htmlspecialchars( $value ) . "</textarea>\n";
									// WYSIWYG
									if ( $customField[ 'type' ] == "wysiwyg" ) { ?>
<script type="text/javascript">
//<![CDATA[
	jQuery( document ).ready( function() {
		jQuery( "<?php echo $this->prefix . $customField[ 'name' ]; ?>" ).addClass( "mceEditor" );
		if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
			tinyMCE.execCommand( "mceAddControl", false, "<?php echo $this->prefix . $customField[ 'name' ]; ?>" );
		}
	});
//]]>
</script>
									<?php }
									break;
								}
								case "color-picker": {
									$value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
									// Color picker using WP's built-in Iris jQuery plugin ?>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function() {
		var <?php echo $this->prefix . $customField[ 'name' ]; ?>Options = {
			defaultColor: false,
			hide: true,
			palettes: true
		};
		 
		jQuery("#<?php echo $this->prefix . $customField[ 'name' ]; ?>").wpColorPicker(<?php echo $this->prefix . $customField[ 'name' ]; ?>Options);
	});
//]]>
</script>
									<?php 
									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "\"><strong>" . $customField[ 'title' ] . "</strong></label>&nbsp;&nbsp;\n";
									echo "<input type=\"text\" name=\"" . $this->prefix . $customField[ 'name' ] . "\" id=\"" . $this->prefix . $customField[ 'name' ] . "\" value=\"" . $value . "\" size=\"7\" maxlength=\"7\" style=\"width: 100px;\" />\n";
									break;
								}
								case "date": {
                                    echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "\" style=\"display:inline;\"><strong>" . $customField[ 'title' ] . "</strong></label>&nbsp;&nbsp;\n";
//									echo "<span style=\"display:inline;\"><strong>" . $customField[ 'title' ] . "</strong></span>&nbsp;&nbsp;\n";
									$value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
									// Date picker using WP's built-in Datepicker jQuery plugin ?>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function() {
		jQuery( "#<?php echo $this->prefix . $customField[ 'name' ]; ?>" ).datepicker(
			{
				changeMonth: true,
				changeYear: true
			}
		);
	});
//]]>
</script>
									<?php
                                    echo "<input type=\"text\" name=\"" . $this->prefix . $customField[ 'name' ] . "\" id=\"" . $this->prefix . $customField[ 'name' ] . "\" value=\"" . htmlspecialchars( $value ) . "\" style=\"width: 175px;\" />\n";
									break;
								}
								case "datetime": {
									echo "<span style=\"display:inline;\"><strong>" . $customField[ 'title' ] . "</strong></span><br />\n";
									$value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
									foreach ( $value as $k => $v )
										$value[$k] = htmlspecialchars( $v );
                                    // Date picker using WP's built-in Datepicker jQuery plugin
                                    // Time picker using jQuery UI Timepicker: http://fgelinas.com/code/timepicker
                                    ?>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function() {
		jQuery( "#<?php echo $this->prefix . $customField[ 'name' ]; ?>_date" ).datepicker(
			{
				changeMonth: true,
				changeYear: true
			}
		);
		jQuery( "#<?php echo $this->prefix . $customField[ 'name' ]; ?>_time" ).timepicker(
			{
				showPeriod: true,
                defaultTime: 'now'
			}
		);
	});
//]]>
</script>
									<?php 
									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "_date\" style=\"display:inline;\"><em>" . _x( 'Date', 'Date field label', 'timed-content' ) . ":</em></label>&nbsp;&nbsp;\n";
									echo "<input type=\"text\" name=\"" . $this->prefix . $customField[ 'name' ] . "[date]\" id=\"" . $this->prefix . $customField[ 'name' ] . "_date\" value=\"" . $value['date'] . "\" style=\"width: 175px;\" />\n";
									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] ."_time\" style=\"display:inline;\"><em>" . _x( 'Time', 'Time field label', 'timed-content' ) . ":</em></label>&nbsp;&nbsp;\n";
									echo "<input type=\"text\" name=\"" . $this->prefix . $customField[ 'name' ] . "[time]\" id=\"" . $this->prefix . $customField[ 'name' ] . "_time\" value=\"" . $value['time'] . "\" style=\"width: 125px;\" />\n";
									break;
								}
								case "number": {
									(int)$value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
									// Number picker using WP's built-in Spinner jQuery plugin ?>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function() {
		jQuery( "#<?php echo $this->prefix . $customField[ 'name' ]; ?>" ).spinner(
			{
				stop: function( event, ui ) { jQuery( this ).trigger("change"); },
				<?php if ( isset( $customField[ 'min' ] ) ) { ?>min: <?php echo $customField[ 'min' ]; ?>, <?php } ?>
				<?php if ( isset( $customField[ 'max' ] ) ) { ?>max: <?php echo $customField[ 'max' ]; ?> <?php } ?>
			}
		);
	});
//]]>
</script>
									<?php 
									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "\" style=\"display:inline;\"><strong>" . $customField[ 'title' ] . "</strong></label>&nbsp;&nbsp;\n";
									echo "<input type=\"text\" name=\"" . $this->prefix . $customField[ 'name' ] . "\" id=\"" . $this->prefix . $customField[ 'name' ] . "\" value=\"" . $value . "\" size=\"2\" />\n";
									break;
								}
								case "hidden": {
									$value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
									// Hidden field
									echo "<input type=\"hidden\" name=\"" . $this->prefix . $customField[ 'name' ] . "\" id=\"" . $this->prefix . $customField[ 'name' ] . "\" value=\"" . $value . "\" />\n";
									break;
								}
								default: {
									$value = ( "" === get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) ? $customField['default'] : get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) );
									// Plain text field
									echo "<label for=\"" . $this->prefix . $customField[ 'name' ] . "\"><strong>" . $customField[ 'title' ] . "</strong></label>\n";
									echo "<input type=\"text\" name=\"" . $this->prefix . $customField[ 'name' ] . "\" id=\"" . $this->prefix . $customField[ 'name' ] . "\" value=\"" . $value . "\" />\n";
									break;
								}
							}
							?>
							<?php if ( isset( $customField[ 'description' ] ) ) echo "<p>" . $customField[ 'description' ] . "</p>\n"; ?>
						</div>
					<?php
					}
				} ?>
			</div>
			<?php
		}
		/**
		* Save the new Custom Fields values
		*/
		function saveCustomFields( $post_id, $post ) {
			// Only save if the user intends to save
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
				return;
			// Make sure the Save request is coming from the right place
			if ( !isset( $_POST[ $this->handle . '_wpnonce' ] ) || !wp_verify_nonce( $_POST[ $this->handle . '_wpnonce' ], $this->handle ) )
				return;
			// Make sure the user can edit this specific post
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
			// Make sure this meta box is associated with the right post types
			if ( ! in_array( $post->post_type, $this->postTypes ) )
				return;
			foreach ( $this->customFields as $customField ) {
				if ( current_user_can( $customField['capability'], $post_id ) ) {
					if ( isset( $_POST[ $this->prefix . $customField['name'] ] ) ) {
						if ( is_array( $_POST[ $this->prefix . $customField['name'] ] ) )  {
							foreach ( $_POST[ $this->prefix . $customField['name'] ] as $k => $v )
								$_POST[ $this->prefix . $customField['name'] ][$k] = trim( $v );
						} else
							$_POST[ $this->prefix . $customField['name'] ] = trim( $_POST[ $this->prefix . $customField['name'] ] );
						$value = $_POST[ $this->prefix . $customField['name'] ];
						// Auto-paragraphs for any WYSIWYG
						if ( $customField['type'] == "wysiwyg" ) $value = wpautop( $value );
						if ( $customField['type'] == "checkbox" ) $value = "yes";
						if ( $customField['type'] == "number" ) $value = intval( $value );
						update_post_meta( $post_id, $this->prefix . $customField[ 'name' ], $value );
					} else {
						delete_post_meta( $post_id, $this->prefix . $customField[ 'name' ] );
					}
				}
			}
		}
		

	} // End Class

} // End if class exists statement
?>