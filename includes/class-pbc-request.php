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

		add_action( 'wp_ajax_get_shareable_config', array( $this, 'get_shareable_config_callback' ) );
		add_action( 'wp_ajax_nopriv_get_shareable_config', array( $this, 'get_shareable_config_callback' ) );

		add_action( 'wp_ajax_send_config_email', array( $this, 'send_config_email_callback' ) );
		add_action( 'wp_ajax_nopriv_send_config_email', array( $this, 'send_config_email_callback' ) );
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

		// Don't use wp_kses_post as it strips scripts needed for AJAX response.
		// The content is already escaped in PBC_Template::render().
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $all_details;
		die( 0 );
	}

	/**
	 * AJAX Callback to get shareable configuration URL.
	 *
	 * @return void
	 */
	public function get_shareable_config_callback() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'pbc-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
		}

		// Start or resume session.
		if ( empty( session_id() ) ) {
			if ( ! session_start() ) {
				wp_send_json_error( 'Session error' );
			}
		}

		$session_key  = isset( $_POST['session_key'] ) ? sanitize_text_field( wp_unslash( $_POST['session_key'] ) ) : '';
		$parent_phase = isset( $_POST['parent_phase'] ) ? (int) $_POST['parent_phase'] : 0;
		$template     = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'wizard';
		$current_url  = isset( $_POST['current_url'] ) ? esc_url_raw( wp_unslash( $_POST['current_url'] ) ) : '';

		if ( empty( $session_key ) ) {
			wp_send_json_error( 'Invalid session key' );
		}

		if ( empty( $current_url ) ) {
			$current_url = home_url( add_query_arg( array() ) );
		}

		if ( empty( $parent_phase ) ) {
			if ( preg_match( '/pbc_variation_(\d+)/', $session_key, $matches ) ) {
				$parent_phase = isset( $matches[1] ) ? (int) $matches[1] : 0;
			}
		}

		if ( empty( $parent_phase ) ) {
			$parent_phase = CALC::get_default_parent_phase();
		}

		if ( empty( $parent_phase ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Parent phase missing.', 'pbc' ),
				)
			);
		}

		if ( ! isset( $_SESSION[ $session_key ] ) || ! is_array( $_SESSION[ $session_key ] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Configuration not found.', 'pbc' ),
				)
			);
		}

		// Build URL parameters from session data.
		$url_params = array(
			'pbc_parent' => $parent_phase,
		);

		// Check if user can see prices.
		$current_user = wp_get_current_user();
		$roles        = (array) $current_user->roles;
		$user_role    = ! empty( $roles ) ? $roles[0] : '';
		$show_prices  = CALC::get_show_prices_for_user( $user_role );

		if ( 'yes' === $show_prices ) {
			$url_params['pbc_show_prices'] = '1';
		}

		// Add each variation to URL parameters - only numeric steps.
		$session_data = isset( $_SESSION[ $session_key ] ) ? $_SESSION[ $session_key ] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( $session_data as $step => $data ) {
			// Only process numeric step keys (1, 2, 3, etc.).
			if ( ! is_numeric( $step ) ) {
				continue;
			}
			if ( ! is_array( $data ) || ! isset( $data['var']['id'] ) ) {
				continue;
			}
			$url_params[ 'v' . $step ] = (int) $data['var']['id'];

			// Add price variation name (dropdown value) if exists, not the calculated price.
			if ( isset( $data['var']['price_var'] ) && ! empty( $data['var']['price_var'] ) ) {
				$url_params[ 'p' . $step ] = sanitize_text_field( $data['var']['price_var'] );
			}
		}

		$base_url = remove_query_arg( array( 'pbc_share', 'pbc_parent', 'pbc_show_prices' ), $current_url );
		// Remove any existing v and p params (and old pbc_v/pbc_p for backwards compatibility).
		$base_url = preg_replace( '/[&?]pbc_v\d+=[^&]*/', '', $base_url );
		$base_url = preg_replace( '/[&?]pbc_p\d+=[^&]*/', '', $base_url );
		$base_url = preg_replace( '/[&?]v\d+=[^&]*/', '', $base_url );
		$base_url = preg_replace( '/[&?]p\d+=[^&]*/', '', $base_url );
		$base_url = rtrim( $base_url, '?&' );

		$share_url = add_query_arg( $url_params, $base_url );

		if ( empty( $share_url ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to generate share URL.', 'pbc' ),
				)
			);
		}

		// Debug: log params count.
		$variation_count = 0;
		foreach ( $url_params as $key => $value ) {
			if ( preg_match( '/^v\d+$/', $key ) ) {
				++$variation_count;
			}
		}

		wp_send_json_success(
			array(
				'url'              => esc_url_raw( $share_url ),
				'variations_count' => $variation_count,
			)
		);
	}

	/**
	 * AJAX Callback to send configuration via email.
	 *
	 * @return void
	 */
	public function send_config_email_callback() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'pbc-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
		}

		// Start or resume session.
		if ( empty( session_id() ) ) {
			if ( ! session_start() ) {
				wp_send_json_error( 'Session error' );
			}
		}

		$session_key     = isset( $_POST['session_key'] ) ? sanitize_text_field( wp_unslash( $_POST['session_key'] ) ) : '';
		$parent_phase    = isset( $_POST['parent_phase'] ) ? (int) $_POST['parent_phase'] : 0;
		$template        = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'wizard';
		$current_url     = isset( $_POST['current_url'] ) ? esc_url_raw( wp_unslash( $_POST['current_url'] ) ) : '';
		$recipient_email = isset( $_POST['recipient_email'] ) ? sanitize_email( wp_unslash( $_POST['recipient_email'] ) ) : '';

		if ( empty( $session_key ) ) {
			wp_send_json_error( 'Invalid session key' );
		}

		if ( empty( $recipient_email ) || ! is_email( $recipient_email ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid email address.', 'pbc' ),
				)
			);
		}

		if ( empty( $current_url ) ) {
			$current_url = home_url( add_query_arg( array() ) );
		}

		if ( empty( $parent_phase ) ) {
			if ( preg_match( '/pbc_variation_(\d+)/', $session_key, $matches ) ) {
				$parent_phase = isset( $matches[1] ) ? (int) $matches[1] : 0;
			}
		}

		if ( empty( $parent_phase ) ) {
			$parent_phase = CALC::get_default_parent_phase();
		}

		if ( empty( $parent_phase ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Parent phase missing.', 'pbc' ),
				)
			);
		}

		if ( ! isset( $_SESSION[ $session_key ] ) || ! is_array( $_SESSION[ $session_key ] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Configuration not found.', 'pbc' ),
				)
			);
		}

		// Build URL parameters from session data.
		$url_params = array(
			'pbc_parent' => $parent_phase,
		);

		// Check if user can see prices.
		$current_user = wp_get_current_user();
		$roles        = (array) $current_user->roles;
		$user_role    = ! empty( $roles ) ? $roles[0] : '';
		$show_prices  = CALC::get_show_prices_for_user( $user_role );

		if ( 'yes' === $show_prices ) {
			$url_params['pbc_show_prices'] = '1';
		}

		// Add each variation to URL parameters - only numeric steps.
		$session_data = isset( $_SESSION[ $session_key ] ) ? $_SESSION[ $session_key ] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( $session_data as $step => $data ) {
			// Only process numeric step keys (1, 2, 3, etc.).
			if ( ! is_numeric( $step ) ) {
				continue;
			}
			if ( ! is_array( $data ) || ! isset( $data['var']['id'] ) ) {
				continue;
			}
			$url_params[ 'v' . $step ] = (int) $data['var']['id'];

			// Add price variation name (dropdown value) if exists, not the calculated price.
			if ( isset( $data['var']['price_var'] ) && ! empty( $data['var']['price_var'] ) ) {
				$url_params[ 'p' . $step ] = sanitize_text_field( $data['var']['price_var'] );
			}
		}

		$base_url = remove_query_arg( array( 'pbc_share', 'pbc_parent', 'pbc_show_prices' ), $current_url );
		// Remove any existing v and p params (and old pbc_v/pbc_p for backwards compatibility).
		$base_url = preg_replace( '/[&?]pbc_v\d+=[^&]*/', '', $base_url );
		$base_url = preg_replace( '/[&?]pbc_p\d+=[^&]*/', '', $base_url );
		$base_url = preg_replace( '/[&?]v\d+=[^&]*/', '', $base_url );
		$base_url = preg_replace( '/[&?]p\d+=[^&]*/', '', $base_url );
		$base_url = rtrim( $base_url, '?&' );

		$share_url = add_query_arg( $url_params, $base_url );

		if ( empty( $share_url ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to generate share URL.', 'pbc' ),
				)
			);
		}

		// Prepare email.
		$subject  = __( 'Budget Configuration', 'pbc' );
		$message  = __( 'You can view the configuration here:', 'pbc' ) . "\n\n";
		$message .= esc_url_raw( $share_url );

		// Send email.
		$sent = wp_mail( $recipient_email, $subject, $message );

		if ( $sent ) {
			wp_send_json_success(
				array(
					'message' => __( 'Email sent successfully.', 'pbc' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to send email.', 'pbc' ),
				)
			);
		}
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
