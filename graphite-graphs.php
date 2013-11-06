<?php
/*
Plugin Name: Graphite Graphs
Plugin URI: http://rcollier.me/software/graphite-graphs/
Description: Display externally hosted graphite graphs in admin or on your theme
Version: 1.0
Author: Rich Collier
Author URI: http://rcollier.me
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Prevent execution of this file directly
if ( ! defined( 'ABSPATH' ) )	die( 'No cheating allowed :-)' );

// Singleton class for plugin functions
final class WP_Graphite_Graphs {
	
	// Singleton init
	public static function init() {
		static $instance = null;
		
		if ( $instance === null )
			$instance = new WP_Graphite_Graphs();
		
		return $instance;
	}
	
	// PHP5 Constructor
	private function __construct() {
		// Activation and deactivation hooks, for creating and destroying WP options
		register_activation_hook( __FILE__, array( 'WP_Graphite_Graphs', 'plugin_activate' ) );
		register_deactivation_hook( __FILE__, array( 'WP_Graphite_Graphs', 'plugin_deactivate' ) );
		
		// Choreograph the show
		add_action( 'wp_dashboard_setup', array( 'WP_Graphite_Graphs', 'action__wp_dashboard_setup' ) );
		add_action( 'wp_ajax_wpggSaveDashOptions', array( 'WP_Graphite_Graphs', 'action__save_dash_options' ) );
		add_action( 'widgets_init', array( 'WP_Graphite_Graphs', 'action__widgets_init' ) );
		add_action( 'admin_enqueue_scripts', array( 'WP_Graphite_Graphs', 'action__admin_enqueue_scripts' ) );
	}
	
	// Register our sidebar widget
	function action__widgets_init() {
		register_widget( 'Graphite_Graph_Sidebar_Widget' );
	}
	
	// Activation hook
	function plugin_activate() {
		wpgg_update_option( 'install_date', date( 'Y-m-d' ) );
		wpgg_update_option( 'install_version', '1.0' );
	}
	
	// Deactivation hook
	function plugin_deactivate() {
		delete_option( 'wpgg_options' );
	}
	
	// Enqueue admin scripts
	function action__admin_enqueue_scripts( $hook ) {
		// No need to load javascript anywhere except the dashboard
		if ( 'index.php' == $hook )
			wp_enqueue_script( 'graphite_graphs_javascript', plugin_dir_url( __FILE__ ) . 'graphite-graphs-admin.js' );
	}
	
	// Add the dashboard widget
	function action__wp_dashboard_setup() {
		$widget_title = wpgg_get_option( 'dash_title' );
		
		// Title the widget if blank
		if ( empty( $widget_title ) )
			$widget_title = 'Untitled Graph';
		
		// Add the dashboard widget
		wp_add_dashboard_widget( 'graphite_dashboard_graph', $widget_title, array( 'WP_Graphite_Graphs', 'ui__dashboard_graph' ) );
	}
	
	// Save dashboard options sent from AJAX
	function action__save_dash_options() {
		// Sanitize and store the saved fields
		wpgg_update_option( 'dash_title', sanitize_text_field( $_POST['wpgg_dash_title'] ) );
		wpgg_update_option( 'dash_server', sanitize_text_field( $_POST['wpgg_dash_server'] ) );
		wpgg_update_option( 'dash_metrics', sanitize_text_field( $_POST['wpgg_dash_metrics'] ) );
		wpgg_update_option( 'dash_duration', intval( $_POST['wpgg_dash_duration'] ) );
		
		// Default to "Dark" color scheme
		if ( 'Light' == $_POST['wpgg_dash_colorscheme'] )
			wpgg_update_option( 'dash_colorscheme', 'Light' );
		else
			wpgg_update_option( 'dash_colorscheme', 'Dark' );
		
		// All done
		die( 'success' );
	}
	
	// Create the dashboard widget UI
	function ui__dashboard_graph() {
		?>
		<img width="100%" height="100%" id="dash_graph_img" />
		<div style="display:none;margin-bottom:10px;" id="dash_graph_message">Configure a new graph by clicking "Settings" below.</div>
		<img id="graph_loading_overlay" style="display:none;position:absolute;bottom:10px;right:12px;" src="<?php echo get_admin_url( '', 'images/loading.gif', 'admin' ); ?>" />
		
		<a style="cursor:pointer;" id="wpgg_dashboard_settings_button">Settings</a> | <a style="cursor:pointer;" id="wpgg_dashboard_refresh_button">Refresh</a>
		<div id="wpgg_dashboard_settings" style="display:none;">
			
			<table class="form-table">
				<tr>
					<td>Graph Title</td>
					<td><input type="text" name="wpgg_dash_title" id="wpgg_dash_title" value="<?php echo esc_html( wpgg_get_option( 'dash_title' ) ); ?>" /><br /><span class="description">Title at the top of this widget</span></td>
				<tr>
					<td>Graphite Server URL</td>
					<td><input type="text" name="wpgg_dash_server" id="wpgg_dash_server" value="<?php echo esc_url( wpgg_get_option( 'dash_server' ) ); ?>" /><br /><span class="description">Example: http://graphite.somedomain.com:8080</span></td>
				</tr>
				<tr>
					<td>Display Metrics</td>
					<td><input type="text" name="wpgg_dash_metrics" id="wpgg_dash_metrics" value="<?php echo esc_html( wpgg_get_option( 'dash_metrics' ) ); ?>" /><br /><span class="description">Example: system.test.pageloadspeed or system.test.*</span></td>
				</tr>
				<tr>
					<td>Duration</td>
					<td>
						<select name="wpgg_dash_duration" id="wpgg_dash_duration" />
							<option value="<?php echo intval( wpgg_get_option( 'dash_duration' ) ); ?>"><?php echo intval( wpgg_get_option( 'dash_duration' ) ); ?></option>
							<option value="-1">-1</option>
							<option value="-2">-2</option>
							<option value="-4">-4</option>
							<option value="-8">-8</option>
							<option value="-12">-12</option>
							<option value="-24">-24</option>
						</select> hours
					</td>
				</tr>
				<tr>
					<td>Color Scheme</td>
					<td>
						<select name="wpgg_dash_colorscheme" id="wpgg_dash_colorscheme" />
							<?php 
							// Show current color scheme as option 1
							$current_scheme = esc_html( wpgg_get_option( 'dash_colorscheme' ) );
							if ( empty( $current_scheme ) )
								$current_scheme = 'Dark';
							?>
							<option value="<?php echo $current_scheme; ?>"><?php echo $current_scheme; ?></option>
							<option value="Dark">Dark</option>
							<option value="Light">Light</option>
							<option value="Aqua">Aqua</option>
							<option value="Fire">Fire</option>
						</select>
					</td>
				</tr>
			</table>
			<br />
			<input type="button" id="graphite_dash_cancel" value="Cancel" class="button button-primary" />
			<input type="submit" id="graphite_dash_submit" value="Save Changes" class="button button-primary" />
		</div>
		<?php
	}
	
}

// Widget class for WP_Widget
class Graphite_Graph_Sidebar_Widget extends WP_Widget {
	
	// Widget constructor
	public function __construct() {
		parent::__construct( 'graphite_graph_widget', 'Graphite Graph Widget' );
	}
	
	// Widget output
	public function widget( $args, $instance ) {
		// Do we have the necessary clearance to continue?
		if ( $instance['graph_private'] && ! is_user_logged_in() )
			return false;
		
		// Get our baggage
		extract( $args );
		
		// Give the widget some styling and display the title
		echo $before_widget . $before_title . esc_attr( $instance['graph_title'] ) . $after_title;
		
		// Create the query string for the color scheme
		if ( 'Light' == $instance['graph_colorscheme'] )
			$colorscheme = '&bgcolor=white&fgcolor=black';
		elseif ( 'Aqua' == $instance['graph_colorscheme'] )
			$colorscheme = '&bgcolor=03aeff&fgcolor=0000aa';
		elseif ( 'Fire' == $instance['graph_colorscheme'] )
			$colorscheme = '&bgcolor=ffb84d&fgcolor=a32900';
		else
			$colorscheme = '&bgcolor=black&fgcolor=white';
		
		// Dump out a safe happy image
		echo '<img class="graphite_graph_image" width="100%" height="100%" src="' . trailingslashit( esc_url( $instance['graph_server_url'] ) ) . 'render?target=' . esc_attr( $instance['graph_metrics'] ) . '&from=' . intval( $instance['graph_duration'] ) . 'hours&width=480&height=270&lineMode=connected&lineWidth=4&hideLegend=true&fontSize=18' . $colorscheme . '" />';
		
		// Close up the styling
		echo $after_widget;
	}
	
	// Widget setup
	public function form( $instance ) {
		?>
		<p>
			<label>Graph Title<br />
			<input class="widefat" id="<?php echo $this->get_field_id( 'graph_title' ); ?>" name="<?php echo $this->get_field_name( 'graph_title' ); ?>" type="text" value="<?php echo esc_attr( $instance['graph_title'] ); ?>" />
			</label>
		</p>
		<p>
			<label>Graphite Server URL<br />
			<input class="widefat" id="<?php echo $this->get_field_id( 'graph_server_url' ); ?>" name="<?php echo $this->get_field_name( 'graph_server_url' ); ?>" type="text" value="<?php echo esc_url( $instance['graph_server_url'] ); ?>" />
			</label>
		</p>
		<p>
			<label>Display Metrics<br />
			<input class="widefat" id="<?php echo $this->get_field_id( 'graph_metrics' ); ?>" name="<?php echo $this->get_field_name( 'graph_metrics' ); ?>" type="text" value="<?php echo esc_attr( $instance['graph_metrics'] ); ?>" />
			</label>
		</p>
		<p>
			<label>Duration<br />
			<select id="<?php echo $this->get_field_id( 'graph_duration' ); ?>" name="<?php echo $this->get_field_name( 'graph_duration' ); ?>">
				<?php if ( 0 !== intval( $instance['graph_duration'] ) ) : ?><option value="<?php echo intval( $instance['graph_duration'] ); ?>"><?php echo intval( $instance['graph_duration'] ); ?></option><?php endif; ?>
				<option value="-1">-1</option>
				<option value="-2">-2</option>
				<option value="-4">-4</option>
				<option value="-8">-8</option>
				<option value="-12">-12</option>
				<option value="-24">-24</option>
			</select> hours
		</p>
		<p>
			<label>Color Scheme<br />
			<select id="<?php echo $this->get_field_id( 'graph_colorscheme' ); ?>" name="<?php echo $this->get_field_name( 'graph_colorscheme' ); ?>">
				<option value="<?php echo esc_attr( $instance['graph_colorscheme'] ); ?>"><?php echo esc_attr( $instance['graph_colorscheme'] ); ?></option>
				<option value="Dark">Dark</option>
				<option value="Light">Light</option>
				<option value="Aqua">Aqua</option>
				<option value="Fire">Fire</option>
			</select>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" id="<?php echo $this->get_field_id( 'graph_private' ); ?>" name="<?php echo $this->get_field_name( 'graph_private' ); ?>" <?php checked( $instance['graph_private'] ); ?> />
				Make this graph private
			</label>
		</p>
		
		<?php
	}
	
	// Sanitize function
	public function update( $new_instance ) {
		$safe_instance = array();
		
		// Sanitize our values
		$safe_instance['graph_title'] = ( ! empty( $new_instance['graph_title'] ) ) ? sanitize_text_field( $new_instance['graph_title'] ) : '';
		$safe_instance['graph_server_url'] = ( ! empty( $new_instance['graph_server_url'] ) ) ? sanitize_text_field( $new_instance['graph_server_url'] ) : '';
		$safe_instance['graph_metrics'] = ( ! empty( $new_instance['graph_metrics'] ) ) ? sanitize_text_field( $new_instance['graph_metrics'] ) : '';
		$safe_instance['graph_duration'] = ( ! empty( $new_instance['graph_duration'] ) ) ? intval( $new_instance['graph_duration'] ) : '';
		
		// Make sure our color scheme is whitelisted
		$allowed_colors = array( 'Light', 'Dark', 'Aqua', 'Fire' );
		if ( in_array( $new_instance['graph_colorscheme'], $allowed_colors ) )
			$safe_instance['graph_colorscheme'] = $new_instance['graph_colorscheme'];
		
		$safe_instance['graph_private'] = ( isset( $new_instance['graph_private'] ) ) ? true : false;
		
		return $safe_instance;
	}

}

// Get an option from our array
function wpgg_get_option( $option_key ) {
	$wpgg_options = get_option( 'wpgg_options' );
	return $wpgg_options[$option_key];
}

// Save an option to our array
function wpgg_update_option( $option_key, $option_value ) {
	$wpgg_options = get_option( 'wpgg_options' );
	$wpgg_options[$option_key] = $option_value;
	update_option( 'wpgg_options', $wpgg_options );
}

// Let the fun and games begin ...
WP_Graphite_Graphs::init();

// omit