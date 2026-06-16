<?php
namespace CLOSE\QProductFlow;

use CLOSE\QProductFlow\Helpers\Template;

/**
 * Public methods
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

use CLOSE\QProductFlow\Helpers\CALC;

/**
 * Public classes.
 *
 * @since 1.4.0
 */
class PublicFront {

	/**
	 * Construct of Class
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'qpfw_configurator_session' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_shortcode( 'quote-product-flow', array( $this, 'qpfw_configurator' ) );
	}

	/**
	 * Creates session
	 *
	 * @return void
	 */
	public function qpfw_configurator_session() {
		// Skip session on REST API requests to avoid blocking HTTP requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( PHP_SESSION_NONE === session_status() && ! headers_sent() ) {
			session_start();
			// Release session lock immediately so other requests are not blocked.
			session_write_close();
		}
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_style(
			'qpfw-public',
			QPFW_PLUGIN_URL . 'includes/assets/qpfw-configurator.css',
			array(),
			QPFW_VERSION
		);

		// Support buttons styles.
		wp_register_style(
			'qpfw-support-buttons',
			QPFW_PLUGIN_URL . 'includes/assets/support-buttons-sticky.css',
			array( 'qpfw-public' ),
			QPFW_VERSION
		);

		wp_register_script(
			'qpfw-public',
			QPFW_PLUGIN_URL . 'includes/assets/qpfw-configurator.js',
			array( 'jquery' ),
			QPFW_VERSION,
			true
		);

		$current_user = wp_get_current_user();
		$roles        = (array) $current_user->roles;
		$user_role    = ! empty( $roles ) ? $roles[0] : '';
		$show_prices  = CALC::get_show_prices_for_user( $user_role );

		wp_localize_script(
			'qpfw-public',
			'QPFWAjaxAction',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'assets_loading'       => QPFW_PLUGIN_URL . 'includes/assets/img/loading.gif',
				'show_prices'          => $show_prices,
				'nonce'                => wp_create_nonce( 'qpfw-nonce' ),
				'recommendation_nonce' => wp_create_nonce( 'qpfw_recommendation_nonce' ),
				'debug'                => defined( 'WP_DEBUG' ) && WP_DEBUG,
			)
		);
	}

	/**
	 * Renders shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return void
	 */
	public function qpfw_configurator( $atts = array() ) {
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
		wp_enqueue_style( 'qpfw-public' );
		wp_enqueue_style( 'qpfw-support-buttons' );
		wp_enqueue_script( 'qpfw-public' );

		$qpfw_atts = shortcode_atts(
			array(
				'pid'      => 0,
				'template' => 'wizard', // wizard, vertical.
			),
			$atts,
		);
		Template::render( $qpfw_atts['pid'], $qpfw_atts['template'] );
	}
}

