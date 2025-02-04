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
		add_action( 'wp_loaded', array( $this, 'pbc_configurator_session' ) );
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
		wp_register_style(
			'pbc-public',
			WPPBC_PLUGIN_URL . 'includes/assets/pbc-configurator.css',
			array(),
			WPPBC_VERSION
		);

		wp_register_script(
			'pbc-public',
			WPPBC_PLUGIN_URL . 'includes/assets/pbc-configurator.js',
			array( 'jquery' ),
			WPPBC_VERSION,
			true
		);

		wp_localize_script(
			'pbc-public',
			'AjaxAction',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'assets_loading' => WPPBC_PLUGIN_URL . 'includes/assets/img/loading.gif',
				'show_prices'    => get_option( 'pbc_show_prices' ),
				'nonce'          => wp_create_nonce( 'pbc-nonce' ),
			)
		);
	}

	/**
	 * Renders shortcode
	 *
	 * @return void
	 */
	public function pbc_configurator( $atts = array() ) {
		if ( is_admin() ) {
			return;
		}
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );
		wp_enqueue_style( 'pbc-public' );
		wp_enqueue_script( 'pbc-public' );

		$pbc_atts = shortcode_atts(
			array(
				'pid'      => 0,
				'template' => 'wizard', // wizard, vertical.
			),
			$atts,
		);
		PBC_Template::render( $pbc_atts['pid'], $pbc_atts['template'] );
	}
}

new PBC_Public();
