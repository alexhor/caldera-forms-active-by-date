<?php defined( 'ABSPATH' ) or die( 'Please return to the main page!' );
/**
 * Plugin Name: Caldera Forms Active by Date
 * Description: Pick a timespan a caldera from is active
 * Version: 1.0
 * Author: Alexander Hornig
 * Author URI: https://h-software.de
 * Text Domain: caldera-forms-active-by-date
 * License: APGL-3.0
 */

class CalderaFormsActiveByDate {
	function __construct() {
		add_action( 'caldera_forms_general_settings_panel', [ $this, 'settings' ] );
		
		
		if( isset( $_GET['preview'] ) ) {
			add_action( 'caldera_forms_submit_start', [ $this, 'submit_check' ], 10, 2 );
			add_filter( 'caldera_forms_render_get_form', [ $this, 'render_check' ], 10, 2 );
			add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		}
	}
	
	public function settings( array $element ) {
		?>
		<div class="caldera-config-field">
			<?php
			$start = '<span>
						<input type="date" name="config[active_start_date_day]" id="active_start_date_day" value="' . esc_attr( @$element['active_start_date_day'] ) . '">
						<input type="time" name="config[active_start_date_time]" id="active_start_date_time" value="' . esc_attr( @$element['active_start_date_time'] ) . '">
					</span>';
			
			$end = '<span>
						<input type="date" name="config[active_end_date_day]" id="active_end_date_day" value="' . esc_attr( @$element['active_end_date_day'] ) . '">
						<input type="time" name="config[active_end_date_time]" id="active_end_date_time" value="' . esc_attr( @$element['active_end_date_time'] ) . '">
					</span>';
			
			printf( __( 'Form should only be active from %s to %s', 'caldera-forms-active-by-date' ), $start, $end );
			?>
		</div>
		
		<div class="caldera-config-group" style="width:500px;">
			<label for="active_start_date_message"><?php _e( 'Message to show before active period', 'caldera-forms-active-by-date' ); ?></label>
			
			<div class="caldera-config-field">
				<textarea class="field-config block-input" name="config[active_start_date_message]" id="active_start_date_message"><?php echo esc_attr( @$element['active_start_date_message'] ); ?></textarea>
				<p class="description"><?php _e( 'Use %date% to insert the start date.', 'caldera-forms-active-by-date' ); ?></p>
			</div>
		</div>
		
		<div class="caldera-config-group" style="width:500px;">
			<label for="active_end_date_message"><?php _e( 'Message to show after active period', 'caldera-forms-active-by-date' ); ?></label>
			
			<div class="caldera-config-field">
				<textarea class="field-config block-input" name="config[active_end_date_message]" id="active_end_date_message"><?php echo esc_attr( @$element['active_end_date_message'] ); ?></textarea>
				<p class="description"><?php _e( 'Use %date% to insert the end date.', 'caldera-forms-active-by-date' ); ?></p>
			</div>
		</div>
		<?php
	}
	
	public function submit_check( array $form, $process_id ) {
		if( ( $message = $this->check_date( $form ) ) === false ) return;
		
		echo $message;
		// stop further output
		wp_die();
	}
	
	public function render_check( array $form ) {
		if( ( $message = $this->check_date( $form ) ) === false ) return $form;
		
		echo '<div class="caldera-grid"><div class="alert alert-warning">' . $message . '</div></div>';
		return [];
	}
	
	protected function check_date( array $form, string $time='start' ) {
		// check time is correct
		if( !in_array( $time, [ 'start', 'end' ] ) ) return false;
		
		$message = false;
		// check data is given and valid
		if( isset( $form['active_' . $time . '_date_day'] ) && strtotime( $form['active_' . $time . '_date_day'] ) ) {
			// get the current periods time
			$date_str = $form['active_' . $time . '_date_day'];
			if( isset( $form['active_' . $time . '_date_time'] ) && strtotime( $form['active_' . $time . '_date_time'] ) ) $date_str .= ' ' . $form['active_' . $time . '_date_time'];
			else if( $time == 'end' ) $date_str .= ' 24:00';
			
			// check if we are in the active time period
			switch( $time ) {
				case 'start':
					if( strtotime( $date_str ) > time() ) {
						if( isset( $form['active_start_date_message'] ) && !empty( $form['active_start_date_message'] ) ) $message = $form['active_start_date_message'];
						else $message = __( "This form isn't active until %date%", 'caldera-forms-active-by-date' );
					}
					break;
				case 'end':
					if( strtotime( $date_str ) < time() ) {
						if( isset( $form['active_start_date_message'] ) && !empty( $form['active_start_date_message'] ) ) $message = $form['active_start_date_message'];
						else $message = __( 'This form is disabled since %date%', 'caldera-forms-active-by-date' );
					}
					break;
			}
			
			// put the periods date in the message
			if( $message !== false ) $message = str_replace( '%date%', $date_str, $message );
		}
		
		// if the form is not currently active, return the message
		if( $message !== false ) return $message;
		// if we just checked the before time, check after time now
		else if( $time === 'start' ) return $this->check_date( $form, 'end' );
		// form is currently activek
		else return false;
	}
	
	public function load_textdomain() {
		load_plugin_textdomain( 'caldera-forms-active-by-date', false, basename( dirname( __FILE__ ) ) . '/lang' );
	}
}

new CalderaFormsActiveByDate();
