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
		if ( PHP_SESSION_NONE === session_status() ) {
			ob_start();
			session_start();
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

		$current_user = wp_get_current_user();
		$roles        = (array) $current_user->roles;
		$user_role    = ! empty( $roles ) ? $roles[0] : '';
		$show_prices  = CALC::get_show_prices_for_user( $user_role );

		wp_localize_script(
			'pbc-public',
			'PBCAjaxAction',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'assets_loading' => WPPBC_PLUGIN_URL . 'includes/assets/img/loading.gif',
				'show_prices'    => $show_prices,
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
		// Don't render if we're in the Gutenberg editor.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && $screen->is_block_editor() ) {
				return;
			}
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
