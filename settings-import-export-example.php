<?php
/**
 * Plugin Name: Settings Import / Export Example Plugin
 * Plugin URI: http://pippinsplugins.com/building-a-settings-import-and-export-feature
 * Description: An example plugin that shows how to create a settings import and export feature
 * Author: Pippin Williamson
 * Author URI: http://pippinsplugins.com
 * Version: 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the plugin options
 */
function pwsix_register_settings() {
	register_setting( 'pwsix_settings_group', 'pwsix_settings' );
}
add_action( 'admin_init', 'pwsix_register_settings' );

/**
 * Register the settings page
 */
function pwsix_settings_menu() {
	add_options_page( __( 'Sample Settings Import and Export' ), __( 'Sample Settings Import and Export' ), 'manage_options', 'pwsix_settings', 'pwsix_settings_page' );
}
add_action( 'admin_menu', 'pwsix_settings_menu' );

/**
 * Render the settings page
 */
function pwsix_settings_page() {

	$options = get_option( 'pwsix_settings' );

	?>
	<div class="wrap">
		<h2><?php screen_icon(); _e('Sample Plugin Settings'); ?></h2>
		<form method="post" action="options.php" class="options_form">
			<?php settings_fields( 'pwsix_settings_group' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scop="row">
						<label for="pwsix_settings[text]"><?php _e( 'Plugin Text' ); ?></label>
					</th>
					<td>
						<input class="regular-text" type="text" id="pwsix_settings[text]" style="width: 300px;" name="pwsix_settings[text]" value="<?php if( isset( $options['text'] ) ) { echo esc_attr( $options['text'] ); } ?>"/>
						<p class="description"><?php _e( 'Enter some text for the plugin here.'); ?></p>
					</td>
				</tr>
				<tr>
					<th scop="row">
						<label for="pwsix_settings[label]"><?php _e( 'Label Text' ); ?></label>
					</th>
					<td>
						<input class="regular-text" type="text" id="pwsix_settings[label]" style="width: 300px;" name="pwsix_settings[label]" value="<?php if( isset( $options['label'] ) ) { echo esc_attr( $options['label'] ); } ?>"/>
						<p class="description"><?php _e( 'Enter some text for the label here.' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scop="row">
						<span><?php _e( 'Enable Feature' ); ?></span>
					</th>
					<td>
						<input class="checkbox" type="checkbox" id="pwsix_settings[enabled]" name="pwsix_settings[enabled]" value="1" <?php checked( 1, isset( $options['enabled'] ) ); ?>/>
						<label for="pwsix_settings[enabled]"><?php _e( 'Enable some feature in this plugin?' ); ?></label>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php _e( 'Export Settings' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Export the plugin settings for this site as a .json file. This allows you to easily import the configuration into another site.' ); ?></p>
					<form method="post">
						<p><input type="hidden" name="pwsix_action" value="export_settings" /></p>
						<p>
							<?php wp_nonce_field( 'pwsix_export_nonce', 'pwsix_export_nonce' ); ?>
							<?php submit_button( __( 'Export' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php _e( 'Import Settings' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.' ); ?></p>
					<form method="post" enctype="multipart/form-data">
						<p>
							<input type="file" name="import_file"/>
						</p>
						<p>
							<input type="hidden" name="pwsix_action" value="import_settings" />
							<?php wp_nonce_field( 'pwsix_import_nonce', 'pwsix_import_nonce' ); ?>
							<?php submit_button( __( 'Import' ), 'secondary', 'submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
		</div><!-- .metabox-holder -->

	</div><!--end .wrap-->
	<?php
}

/**
 * Process a settings export that generates a .json file of the shop settings
 */
function pwsix_process_settings_export() {

	if( empty( $_POST['pwsix_action'] ) || 'export_settings' != $_POST['pwsix_action'] )
		return;

	if( ! wp_verify_nonce( $_POST['pwsix_export_nonce'], 'pwsix_export_nonce' ) )
		return;

	if( ! current_user_can( 'manage_options' ) )
		return;

	$settings = get_option( 'pwsix_settings' );

	ignore_user_abort( true );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=pwsix-settings-export-' . date( 'm-d-Y' ) . '.json' );
	header( "Expires: 0" );

	echo json_encode( $settings );
	exit;
}
add_action( 'admin_init', 'pwsix_process_settings_export' );

/**
 * Process a settings import from a json file
 */
function pwsix_process_settings_import() {

	if( empty( $_POST['pwsix_action'] ) || 'import_settings' != $_POST['pwsix_action'] )
		return;

	if( ! wp_verify_nonce( $_POST['pwsix_import_nonce'], 'pwsix_import_nonce' ) )
		return;

	if( ! current_user_can( 'manage_options' ) )
		return;

	$extension = end( explode( '.', $_FILES['import_file']['name'] ) );

    if( $extension != 'json' ) {
        wp_die( __( 'Please upload a valid .json file' ) );
    }

	$import_file = $_FILES['import_file']['tmp_name'];

	if( empty( $import_file ) ) {
		wp_die( __( 'Please upload a file to import' ) );
	}

	// Retrieve the settings from the file and convert the json object to an array.
	$settings = (array) json_decode( file_get_contents( $import_file ) );

	update_option( 'pwsix_settings', $settings );

	wp_safe_redirect( admin_url( 'options-general.php?page=pwsix_settings' ) ); exit;

}
add_action( 'admin_init', 'pwsix_process_settings_import' );
