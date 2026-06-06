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
		$pbc_variation = isset( $_REQUEST['pbc_variation'] ) ? wp_unslash( $_REQUEST['pbc_variation'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$parent_phase  = isset( $_POST['pbc_parent_phase'] ) ? (int) $_POST['pbc_parent_phase'] : 0;
		$session_key   = 'pbc_variation_' . $parent_phase;
		$option        = '';

		if ( PHP_SESSION_NONE === session_status() && ! headers_sent() ) {
			session_start();
		}
		if ( PHP_SESSION_NONE === session_status() ) {
			echo ';;--;;' . wp_json_encode(
				array(
					'type' => 'error',
					'msg'  => 'Error: Unable to initialize Session!',
				)
			);
			die( 0 );
		}
		if ( ! empty( $pbc_variation ) && $current_phase && isset( $pbc_variation[ $current_phase ] ) ) {
			$variation_data = $pbc_variation[ $current_phase ];
			$svar           = 0; // Initialize default value.

			// Check if it's multiple selection (array) or single selection.
			if ( is_array( $variation_data ) ) {
				// Multiple selection (checkboxes).
				$selected_variations = array_map( 'intval', $variation_data );
				$option_names        = array();
				$total_price         = 0;

				// Filter out empty values.
				$selected_variations = array_filter( $selected_variations );

				if ( ! empty( $selected_variations ) ) {
					foreach ( $selected_variations as $var_id ) {
						$pricevar     = isset( $_REQUEST[ "pbc_pricevar_$var_id" ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ "pbc_pricevar_$var_id" ] ) ) : null;
						$var_price    = CALC::get_price_variation( $var_id, $pricevar );
						$total_price += (float) $var_price;

						$var_title = get_the_title( $var_id );
						if ( $var_title && 'Auto Draft' !== $var_title ) {
							$option_names[] = $var_title;
						}
					}

					$option = implode( ', ', $option_names );
					$price  = $total_price;
					$svar   = $selected_variations[0]; // Use first for image reference.
				}
} else {
				// Single selection (radio button or dropdown).
				$svar = (int) $variation_data;

				if ( is_user_logged_in() ) {
					$user_id                 = get_current_user_id();
					$phase_param['var']      = $svar;
					$phase_param['pricevar'] = isset( $_REQUEST[ "pbc_pricevar_$svar" ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ "pbc_pricevar_$svar" ] ) ) : '';
					update_user_meta( $user_id, 'pbc_phase_' . $current_phase, $phase_param );
							}

				$pricevar = isset( $_REQUEST[ "pbc_pricevar_$svar" ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ "pbc_pricevar_$svar" ] ) ) : null;
				$price    = CALC::get_price_variation( $svar, $pricevar );

				$option_name = get_the_title( $svar );
				if ( ! empty( $option_name ) ) {
					$option = $option_name;
							}
			}

			// Gets image variation with filter dependency.
			if ( $svar > 0 && isset( $_SESSION[ $session_key ] ) && is_array( $_SESSION[ $session_key ] ) ) {
				$session_data = $_SESSION[ $session_key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$imgprodurl   = CALC::get_image_variation_url( $session_data, $svar );
			}

			$variations_images_flipped = get_option( 'variations_images_flipped' );
			if ( $svar > 0 && ! empty( $variations_images_flipped ) ) {
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
		// Verify nonce - check both possible nonce fields.
		$nonce_verified = false;

		// Try to verify the form nonce first.
		if ( isset( $_POST['pbc_template_wizard_nonce'] ) ) {
			$nonce_verified = wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['pbc_template_wizard_nonce'] ) ),
				'pbc_template_wizard_action'
			);
		}

		// If form nonce fails, try the AJAX nonce.
		if ( ! $nonce_verified && isset( $_POST['nonce'] ) ) {
			$nonce_verified = wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
				'pbc-nonce'
			);
		}

		if ( ! $nonce_verified ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Start or resume session.
		if ( PHP_SESSION_NONE === session_status() && ! headers_sent() ) {
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
			if ( apply_filters( 'pbc_save_enquiry', false, $item ) ) {
				$item['pbc_enquiry'] = CALC::configurator_save_enquiry( $item );
			}
			$pdf_url                = PDF::generate_engine_pdf( $item, 'url' );
			$_SESSION['pbc_output'] = $pdf_url;
		}

		ob_start();
		$parent_phase = isset( $_POST['pbc_parent_phase'] ) ? (int) $_POST['pbc_parent_phase'] : 0;
		$template     = isset( $_POST['pbc_template'] ) ? sanitize_text_field( wp_unslash( $_POST['pbc_template'] ) ) : 'wizard';

		PBC_Template::render( $parent_phase, $template );
		$all_details = ob_get_contents();
		ob_end_clean();

		// Don't use wp_kses_post as it strips scripts needed for AJAX response.
		// The content is already escaped in PBC_Template::render().
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $all_details;
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
