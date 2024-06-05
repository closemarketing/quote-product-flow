<?php
/**
 * Public methods
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public classes.
 *
 * @since 1.4.0
 */
class PBC_Public {

	/**
	 * Construct of Class
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'pbc_configurator_session' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_shortcode( 'pbc', array( $this, 'pbc_configurator' ) );
	}
	/**
	 * Creates session
	 *
	 * @return void
	 */
	public function pbc_configurator_session() {
		if ( empty( session_id() ) ) {
			ob_start();
			session_start();
		}
		if ( empty( session_id() ) ) {
			echo ';;--;;' . json_encode(
				array(
					'type' => 'error',
					'msg'  => 'Error: Unable to initialize Session!',
				)
			);
			die( 'Error: Unable to initialize Session!' );
		}
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'pbc-public',
			WPPBC_PLUGIN_URL . 'assets/css/pbc-configurator.css',
			array(),
			WPPBC_VERSION
		);
	}

	/**
	 * Renders shortcode
	 *
	 * @return void
	 */
	public function pbc_configurator() {
		if ( is_admin() ) {
			return;
		}
		PBC_Template_Wizard::render();
	}
}

new PBC_Public();
