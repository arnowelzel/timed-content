<?php
/*
Plugin Name: Timed Content
Text Domain: timed-content
Plugin URI: http://www.notabenemarketing.com/resources/wordpress-plugins/timed-content-plugin
Description: Plugin to show or hide portions of a Page or Post at a specified time after loading.  These actions can either be processed either server-side or client-side, depending on the desired effect.
Author: K. Tough
Version: 1.0
Author URI: http://www.notabenemarketing.com/resources/wordpress-plugins/timed-content-plugin
*/

define( "TIMED_CONTENT_PLUGIN_URL", WP_PLUGIN_URL . '/timed-content' );
define( "TIMED_CONTENT_CLIENT_TAG", "timed-content-client" );
define( "TIMED_CONTENT_SERVER_TAG", "timed-content-server" );
define( "TIMED_CONTENT_ZERO_TIME", "1970-Jan-01 00:00:00 +000" );  // Start of Unix Epoch
define( "TIMED_CONTENT_END_TIME", "2038-Jan-19 03:14:07 +000" );   // End of Unix Epoch
/* translators:  date/time format for debugging messages. */
define( "TIMED_CONTENT_DT_FORMAT", __( "l, F jS, Y, g:i:s A T (e)" , 'timed-content') );


if ( !class_exists( "timedContentPlugin" ) ) {
	class timedContentPlugin {
				
		function timedContentPlugin() { 
			//constructor

		}

		function _date_diff( $date_1 = 0, $date_2 = 0 ) {
		//
		// Compares two timestamps and returns array with period differences (year, month, day, hour, minute, second)
		// http://ca2.php.net/manual/en/function.mktime.php#93914
		//
		
		  //check higher timestamp and switch if neccessary
		  if ($date_1 < $date_2){
			$temp = $date_2;
			$date_2 = $date_1;
			$date_1 = $temp;
			$diff['before'] = true;
		  }
		  else {
			$temp = $date_1; //temp can be used for day count if required
			$diff['before'] = false;
		  }
		  $date_1 = date_parse( date( "Y-m-d H:i:s", $date_1 ) );
		  $date_2 = date_parse( date( "Y-m-d H:i:s", $date_2 ) );
		  //seconds
		  if ( $date_1['second'] >= $date_2['second'] ) {
			$diff['second'] = $date_1['second'] - $date_2['second'];
		  }
		  else {
			$date_1['minute']--;
			$diff['second'] = 60 - $date_2['second'] + $date_1['second'];
		  }
		  //minutes
		  if ( $date_1['minute'] >= $date_2['minute'] ) {
			$diff['minute'] = $date_1['minute'] - $date_2['minute'];
		  }
		  else {
			$date_1['hour']--;
			$diff['minute'] = 60 - $date_2['minute'] + $date_1['minute'];
		  }
		  //hours
		  if ( $date_1['hour'] >= $date_2['hour'] ) {
			$diff['hour'] = $date_1['hour'] - $date_2['hour'];
		  }
		  else {
			$date_1['day']--;
			$diff['hour'] = 24 - $date_2['hour'] + $date_1['hour'];
		  }
		  //days
		  if ( $date_1['day'] >= $date_2['day'] ) {
			$diff['day'] = $date_1['day'] - $date_2['day'];
		  }
		  else {
			$date_1['month']--;
			$diff['day'] = date( "t", $temp ) - $date_2['day'] + $date_1['day'];
		  }
		  //months
		  if ( $date_1['month'] >= $date_2['month'] ) {
			$diff['month'] = $date_1['month'] - $date_2['month'];
		  }
		  else {
			$date_1['year']--;
			$diff['month'] = 12 - $date_2['month'] + $date_1['month'];
		  }
		  //years
		  $diff['year'] = $date_1['year'] - $date_2['year'];
		  return $diff;   
		}		

		function clientShowHTML( $atts, $content = null ) {
			$show_attr = "";
			$hide_attr = "";
			extract( shortcode_atts( array( 'show' => '0:00:000' , 'hide' => '0:00:000'  ), $atts ) );
			
			// Initialize show/hide arguments 
			$s_min = 0; $s_sec = 0; $s_fade = 0;
			$h_min = 0; $h_sec = 0; $h_fade = 0;
			@list( $s_min, $s_sec, $s_fade ) = explode( ":", $show );
			@list( $h_min, $h_sec, $h_fade ) = explode( ":", $hide );

			if ( ( (int)$s_min + (int)$s_sec ) > 0 )
				$show_attr = "_show_" . $s_min . "_" . $s_sec . "_" . $s_fade;
			if ( ( (int)$h_min + (int)$h_sec ) > 0 )
				$hide_attr = "_hide_" . $h_min . "_" . $h_sec . "_" . $h_fade;
			
			$the_class = TIMED_CONTENT_CLIENT_TAG . $show_attr . $hide_attr ;
			//die($the_class);
			$the_HTML = "<div class='" . $the_class . "'" . ( ( $show_attr != "" ) ? " style='display: none;'" : "" ) .">" . do_shortcode($content) . "</div>";

			return $the_HTML;
		}
		
		function serverShowHTML( $atts, $content = null ) {
			extract( shortcode_atts( array( 'show' => TIMED_CONTENT_ZERO_TIME , 'hide' => TIMED_CONTENT_END_TIME, 'debug' => 'false'  ), $atts ) );
			$show_t = strtotime( $show );
			$hide_t = strtotime( $hide );
			$right_now_t = time();
			$debug_header = "";
			
			if ( $debug == "true" ) {
				$temp_tz = date_default_timezone_get();
				date_default_timezone_set( get_option( 'timezone_string' ) );

				$right_now = date_i18n( TIMED_CONTENT_DT_FORMAT, $right_now_t );
	
				$show_diff = $this->_date_diff( $show_t, $right_now_t );
				$show_diff_str = sprintf( _n( "%d year", "%d years", $show_diff['year'] , 'timed-content'), $show_diff['year'] ) . ", ";
				$show_diff_str .= sprintf( _n( "%d month", "%d months", $show_diff['month'] , 'timed-content'), $show_diff['month'] ) . ", ";
				$show_diff_str .= sprintf( _n( "%d day", "%d days", $show_diff['day'] , 'timed-content'), $show_diff['day'] ) . ", ";
				$show_diff_str .= sprintf( _n( "%d hour", "%d hours", $show_diff['hour'] , 'timed-content'), $show_diff['hour'] ) . ", ";
				$show_diff_str .= sprintf( _n( "%d minute", "%d minutes", $show_diff['minute'] , 'timed-content'), $show_diff['minute'] ) . ", ";
				$show_diff_str .= sprintf( _n( "%d second", "%d seconds", $show_diff['second'] , 'timed-content'), $show_diff['second'] ) . " ";
				$show_diff_str .= ( $show_diff['before'] == true ? __( "ago" , 'timed-content') : __( "from now" , 'timed-content') );
				
				$hide_diff = $this->_date_diff( $hide_t, $right_now_t );
				$hide_diff_str = sprintf( _n( "%d year", "%d years", $hide_diff['year'] , 'timed-content'), $hide_diff['year'] ) . ", " ;
				$hide_diff_str .= sprintf( _n( "%d month", "%d months", $hide_diff['month'] , 'timed-content'), $hide_diff['month'] ) . ", ";
				$hide_diff_str .= sprintf( _n( "%d day", "%d days", $hide_diff['day'] , 'timed-content'), $hide_diff['day'] ) . ", ";
				$hide_diff_str .= sprintf( _n( "%d hour", "%d hours", $hide_diff['hour'] , 'timed-content'), $hide_diff['hour'] ) . ", ";
				$hide_diff_str .= sprintf( _n( "%d minute", "%d minutes", $hide_diff['minute'] , 'timed-content'), $hide_diff['minute'] ) . ", ";
				$hide_diff_str .= sprintf( _n( "%d second", "%d seconds", $hide_diff['second'] , 'timed-content'), $hide_diff['second'] ) . " ";
				$hide_diff_str .= ( $hide_diff['before'] == true ? __( "ago" , 'timed-content') : __( "from now" , 'timed-content') );
	
				$debug_header = "<!-- " . __( "START TIMED-CONTENT-SERVER DEBUGGING" , 'timed-content') . " -->\n<!--\n\n";

				if ( $show == TIMED_CONTENT_ZERO_TIME )
					$debug_header .= " " . __( "'Show' attribute not set." , 'timed-content') . "\n";
				else
					$debug_header .= " " . __( "'Show' attribute" , 'timed-content') . " : " . $show . ",\n " . __( "Timestamp derived from 'Show' attribute" , 'timed-content') . " = " . $show_t . ",\n " . __( "Date/time derived from timestamp" , 'timed-content') . " : " . date_i18n(TIMED_CONTENT_DT_FORMAT, $show_t) . ". " . $show_diff_str . ". \n\n";

				if ( $hide == TIMED_CONTENT_END_TIME )
					$debug_header .= " " . __( "'Hide' attribute not set." , 'timed-content') . "\n";
				else
					$debug_header .= " " . __( "'Hide' attribute" , 'timed-content') . " : " . $hide . ",\n " . __( "Timestamp derived from 'Hide' attribute" , 'timed-content') . " = " . $hide_t . ",\n " . __( "Date/time derived from timestamp" , 'timed-content') . " : " . date_i18n(TIMED_CONTENT_DT_FORMAT, $hide_t) . ". " . $hide_diff_str . ". \n\n";

				$debug_header .= " " . __( "Current time" , 'timed-content') . " : " . $right_now . ", " . __( "Timestamp derived from current time" , 'timed-content') . " = " . $right_now_t . "\n";
					
				$debug_header .= " " . __( "Wordpress option" , 'timed-content') . " 'gmt_offset' : " . get_option( 'gmt_offset' ) . "\n";
				$debug_header .= " " . __( "Wordpress option" , 'timed-content') . " 'timezone_string' : " . get_option( 'timezone_string' ) . "\n\n";

	
				$debug_header .= "-->\n<!-- " . __( "END TIMED-CONTENT-SERVER DEBUGGING" , 'timed-content') . " -->\n";
				date_default_timezone_set( $temp_tz );

			}
			$content_header = "<!-- " . __( "START TIMED-CONTENT-SERVER CONTENT" , 'timed-content') . " -->\n";
			$content_footer = "<!-- " . __( "END TIMED-CONTENT-SERVER CONTENT" , 'timed-content') . " -->\n";
			
			if ( ( $show_t <= $right_now_t ) && ( $right_now_t <= $hide_t ) )
				$the_HTML = $debug_header . do_shortcode($content) . "\n";
			else
				$the_HTML = $debug_header;	

			return $content_header . $the_HTML . $content_footer;
		}
		
		function addHeaderCode()  {
			global $wp_query;
			if ( ! is_admin() ) 
			{
				foreach ( $wp_query->posts as $post ) {
					$pattern = get_shortcode_regex();
					preg_match_all( '/' . $pattern . '/s', $post->post_content, $matches, PREG_SET_ORDER );
					if ( is_array( $matches ) ) {
						foreach ( $matches as $m ) {
							if ( $m[2] == TIMED_CONTENT_CLIENT_TAG ) {
								//shortcode is being used; enqueue script and get out (script can handle multiple uses of the shortcode already)
								wp_enqueue_script( 'timed-content_js', TIMED_CONTENT_PLUGIN_URL . '/js/timed-content.js', array( 'jquery' ), '1.0' );
								break 2;
							}
						}
					}
				} 
			}
		}
		
		function initTinyMCEPlugin()  {
			if ( ( ! current_user_can( 'edit_posts' ) ) && ( ! current_user_can( 'edit_pages' ) ) )
				return;
					 
			// Add only in Rich Editor mode
			if ( get_user_option( 'rich_editing' ) == 'true' ) {
				add_filter( "mce_external_plugins", array( &$this, "addTimedContentTinyMCEPlugin" ) );
				add_filter( "mce_buttons", array( &$this, "registerTinyMCEButton" ) );
			}
		
		
		}
		
		function registerTinyMCEButton( $buttons ) {
			array_push( $buttons, "|", "timed_content" );
			return $buttons;
		}
		 
		// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
		function addTimedContentTinyMCEPlugin( $plugin_array ) {
			$plugin_array['timed_content'] = TIMED_CONTENT_PLUGIN_URL . "/tinymce_plugin/editor_plugin_src.js";
			return $plugin_array;
		}
		
		function i18nInit() {
			$plugin_dir = basename( dirname( __FILE__ ) ) . "/lang/";
			load_plugin_textdomain( 'timed-content', null, $plugin_dir );
		}

	}

} //End Class timedContentPlugin

// Initialize plugin
if ( class_exists( "timedContentPlugin" ) ) {
	$timedContentPluginInstance = new timedContentPlugin();
}

// Actions and Filters
if ( isset( $timedContentPluginInstance ) ) {
	add_action( "init", array( &$timedContentPluginInstance, "i18nInit" ), 1 );
	add_action( "wp_head", array( &$timedContentPluginInstance, "addHeaderCode" ), 1 );
	add_action( "admin_init", array( &$timedContentPluginInstance, "initTinyMCEPlugin" ), 1 );
	add_shortcode( TIMED_CONTENT_CLIENT_TAG, array( &$timedContentPluginInstance, "clientShowHTML" ), 1 );
	add_shortcode( TIMED_CONTENT_SERVER_TAG, array( &$timedContentPluginInstance, "serverShowHTML" ), 1 );
}
?>