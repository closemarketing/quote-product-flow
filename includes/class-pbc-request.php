<?php
/**
 * Class Admin
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

use Close\PBC\Helpers\CALC;
use Close\PBC\Helpers\PDF;

/**
 * Class for admin
 */
class PBC_Requests {
	/**
	 * Construct and intialize
	 */
	public function __construct() {
		add_action( 'wp_ajax_variation_selected', array( $this, 'variation_selected_action_callback' ) );
		add_action( 'wp_ajax_nopriv_variation_selected', array( $this, 'variation_selected_action_callback' ) );

		add_action( 'wp_ajax_configurator_submit', array( $this, 'configurator_submit_action_callback' ) );
		add_action( 'wp_ajax_nopriv_configurator_submit', array( $this, 'configurator_submit_action_callback' ) );

		add_action( 'wp_ajax_configurator_login', array( $this, 'configurator_login_action_callback' ) );
		add_action( 'wp_ajax_nopriv_configurator_login', array( $this, 'configurator_login_action_callback' ) );
	}

	/**
	 * AJAX Callback for variation selected
	 *
	 * @return void
	 */
	public function variation_selected_action_callback() {
		// Verify nonce for AJAX request if provided.
		if ( isset( $_REQUEST['nonce'] ) ) {
			check_ajax_referer( 'pbc-nonce', 'nonce', false );
		}

		$current_phase = isset( $_REQUEST['current_phase'] ) ? (int) $_REQUEST['current_phase'] : 0;
		$pbc_variation = isset( $_REQUEST['pbc_variation'] ) ? array_map( 'intval', (array) $_REQUEST['pbc_variation'] ) : array();
		$parent_phase  = isset( $_POST['pbc_parent_phase'] ) ? (int) $_POST['pbc_parent_phase'] : 0;
		$session_key   = 'pbc_variation_' . $parent_phase;
		$option        = '';

		if ( '' === session_id() ) {
			ob_start();
			session_start();
		}
		if ( '' === session_id() ) {
			echo ';;--;;' . wp_json_encode(
				array(
					'type' => 'error',
					'msg'  => 'Error: Unable to initialize Session!',
				)
			);
			die( 0 );
		}
		if ( ! empty( $pbc_variation ) && $current_phase && isset( $pbc_variation[ $current_phase ] ) ) {
			$svar = (int) $pbc_variation[ $current_phase ];
			if ( is_user_logged_in() ) {
				$user_id                 = get_current_user_id();
				$phase_param['var']      = $svar;
				$phase_param['pricevar'] = isset( $_REQUEST[ "pbc_pricevar_$svar" ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ "pbc_pricevar_$svar" ] ) ) : '';
				update_user_meta( $user_id, 'pbc_phase_' . $current_phase, $phase_param );
			}
			// Gets image variation with filter dependency.
			if ( isset( $_SESSION[ $session_key ] ) && is_array( $_SESSION[ $session_key ] ) ) {
				$session_data = $_SESSION[ $session_key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$imgprodurl   = CALC::get_image_variation_url( $session_data, $svar );
			}
			$pricevar = isset( $_REQUEST[ "pbc_pricevar_$svar" ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ "pbc_pricevar_$svar" ] ) ) : null;
			$price    = CALC::get_price_variation( $svar, $pricevar );

			$option_name = get_the_title( $svar );
			if ( ! empty( $option_name ) ) {
				$option .= ' [' . $option_name . ']';
			}

			$variations_images_flipped = get_option( 'variations_images_flipped' );
			if ( ! empty( $variations_images_flipped ) ) {
				for ( $j = 1; $j < (int) $current_phase; $j++ ) {
					if ( isset( $_SESSION[ $session_key ][ $j ]['var']['id'] ) && in_array( $_SESSION[ $session_key ][ $j ]['var']['id'], $variations_images_flipped, true ) ) {
						$flipped = true;
					}
				}
				if ( in_array( $svar, $variations_images_flipped, true ) ) {
					$flipped = true;
				}
			}
		}

		$price   = empty( $price ) ? '-' : number_format( $price, 2, ',', '.' ) . ' €';
		$option  = empty( $option ) ? '-' : $option;
		$flipped = empty( $flipped ) ? false : $flipped;
		echo ';;--;;' . wp_json_encode(
			array(
				'type'    => 'success',
				'url'     => $imgprodurl ?? '',
				'option'  => $option,
				'flipped' => $flipped,
				'price'   => $price,
			)
		);
		die( 0 );
	}

	/**
	 * AJAX Callback for variation selected
	 *
	 * @return void
	 */
	public function configurator_submit_action_callback() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'pbc_template_wizard_action', 'pbc_template_wizard_nonce', false ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Start or resume session.
		if ( empty( session_id() ) ) {
			if ( ! session_start() ) {
				wp_send_json_error( 'Session error' );
			}
		}

		$submit = isset( $_POST['submit'] ) ? sanitize_text_field( wp_unslash( $_POST['submit'] ) ) : '';
		$item   = array();
		if ( 'email_send' === $submit || 'generate_pdf' === $submit ) {
			$email_field    = ! empty( $_POST['email_field'] ) ? sanitize_email( wp_unslash( $_POST['email_field'] ) ) : '';
			$name_field     = ! empty( $_POST['name_field'] ) ? sanitize_text_field( wp_unslash( $_POST['name_field'] ) ) : '';
			$phone_field    = ! empty( $_POST['phone_field'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_field'] ) ) : '';
			$city_field     = ! empty( $_POST['city_field'] ) ? sanitize_text_field( wp_unslash( $_POST['city_field'] ) ) : '';
			$state_field    = ! empty( $_POST['state_field'] ) ? sanitize_text_field( wp_unslash( $_POST['state_field'] ) ) : '';
			$comments_field = ! empty( $_POST['comments_field'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comments_field'] ) ) : '';

			$item = array_merge(
				[
					'pbc_contact' => [
						'email'    => $email_field,
						'name'     => $name_field,
						'phone'    => $phone_field,
						'city'     => $city_field,
						'state'    => $state_field,
						'comments' => $comments_field,
					],
				],
				$_SESSION
			);

			$item['pbc_session_key']  = isset( $_POST['pbc_session_key'] ) ? sanitize_text_field( wp_unslash( $_POST['pbc_session_key'] ) ) : '';
			$item['pbc_parent_phase'] = isset( $_POST['pbc_parent_phase'] ) ? (int) $_POST['pbc_parent_phase'] : 0;
		}

		if ( 'email_send' === $submit ) {
			$_SESSION['pbc_output'] = CALC::configurator_result_email_send( $item );
		} elseif ( 'generate_pdf' === $submit ) {
			$item['pbc_enquiry']    = CALC::configurator_save_enquiry( $item );
			$pdf_url                = PDF::generate_engine_pdf( $item, 'url' );
			$_SESSION['pbc_output'] = $pdf_url;
		}

		ob_start();
		$parent_phase = isset( $_POST['pbc_parent_phase'] ) ? (int) $_POST['pbc_parent_phase'] : 0;
		$template     = isset( $_POST['pbc_template'] ) ? sanitize_text_field( wp_unslash( $_POST['pbc_template'] ) ) : 'wizard';
		PBC_Template::render( $parent_phase, $template );
		$all_details = ob_get_contents();
		ob_end_clean();
		echo wp_kses_post( $all_details );
		die( 0 );
	}

	/**
	 * AJAX Callback for configurator login
	 *
	 * @return void
	 */
	public function configurator_login_action_callback() {
		// Verify nonce for AJAX request if provided.
		if ( isset( $_REQUEST['nonce'] ) ) {
			check_ajax_referer( 'pbc-nonce', 'nonce', false );
		}

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$login    = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => true,
			),
			false
		);
		if ( isset( $login->ID ) && $login->ID ) {
			ob_start();
			if ( \locate_template( 'template-budget-configurator.php' ) ) {
				\locate_template( 'template-budget-configurator.php', true );
			}
			$all_details = ob_get_contents();
			ob_end_clean();
			echo wp_kses_post( $all_details );
		} elseif ( is_wp_error( $login ) ) {
			echo ';;-;;error;;-;;' . esc_html( $login->get_error_message() );
		}
		die( 0 );
	}
}
new PBC_Requests();
