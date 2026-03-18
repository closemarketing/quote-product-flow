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

		add_action( 'wp_ajax_pbc_share_budget_pdf', array( $this, 'pbc_share_budget_pdf_callback' ) );
		add_action( 'wp_ajax_nopriv_pbc_share_budget_pdf', array( $this, 'pbc_share_budget_pdf_callback' ) );

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
		if ( PHP_SESSION_NONE === session_status() && ! headers_sent() ) {
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
	 * Save enquiry, generate PDF on disk; used by WhatsApp share and share-by-email.
	 *
	 * @return array{item: array, pdf_path: string, pdf_url: string}|\WP_Error
	 */
	private function pbc_create_share_budget_pdf_data() {
		$session_key  = isset( $_POST['session_key'] ) ? sanitize_text_field( wp_unslash( $_POST['session_key'] ) ) : '';
		$parent_phase = isset( $_POST['parent_phase'] ) ? (int) $_POST['parent_phase'] : 0;

		if ( empty( $session_key ) || ! isset( $_SESSION[ $session_key ] ) || ! is_array( $_SESSION[ $session_key ] ) ) {
			return new \WP_Error(
				'pbc_no_config',
				__( 'Configuration not found. Complete the configurator first.', 'pbc' )
			);
		}

		if ( preg_match( '/pbc_variation_(\d+)/', $session_key, $matches ) ) {
			$parent_phase = (int) $matches[1];
		} elseif ( empty( $parent_phase ) ) {
			$parent_phase = CALC::get_default_parent_phase();
		}

		$item                = $_SESSION;
		$item['pbc_contact'] = array(
			'email'    => isset( $_POST['email_field'] ) ? sanitize_email( wp_unslash( $_POST['email_field'] ) ) : '',
			'name'     => isset( $_POST['name_field'] ) ? sanitize_text_field( wp_unslash( $_POST['name_field'] ) ) : '',
			'phone'    => isset( $_POST['phone_field'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_field'] ) ) : '',
			'city'     => isset( $_POST['city_field'] ) ? sanitize_text_field( wp_unslash( $_POST['city_field'] ) ) : '',
			'state'    => isset( $_POST['state_field'] ) ? sanitize_text_field( wp_unslash( $_POST['state_field'] ) ) : '',
			'comments' => isset( $_POST['comments_field'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comments_field'] ) ) : '',
		);
		$item['pbc_session_key']  = $session_key;
		$item['pbc_parent_phase'] = $parent_phase;

		$item['pbc_enquiry'] = CALC::configurator_save_enquiry( $item );
		$pdf_path            = PDF::generate_engine_pdf( $item, 'path' );

		if ( empty( $pdf_path ) || ! is_readable( $pdf_path ) ) {
			return new \WP_Error(
				'pbc_pdf_fail',
				__( 'Could not generate the PDF. Try again or use “Generate budget”.', 'pbc' )
			);
		}

		$upload_dir = wp_upload_dir();
		$pdf_url    = $upload_dir['baseurl'] . '/pbc/' . basename( $pdf_path );

		return array(
			'item'     => $item,
			'pdf_path' => $pdf_path,
			'pdf_url'  => $pdf_url,
		);
	}

	/**
	 * AJAX: generate budget PDF and return URL for WhatsApp (or similar) sharing.
	 *
	 * WhatsApp cannot attach files from the browser; the message includes the direct PDF link.
	 *
	 * @return void
	 */
	public function pbc_share_budget_pdf_callback() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'pbc-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
		}

		if ( PHP_SESSION_NONE === session_status() && ! headers_sent() ) {
			if ( ! session_start() ) {
				wp_send_json_error( array( 'message' => __( 'Session error.', 'pbc' ) ) );
			}
		}

		$data = $this->pbc_create_share_budget_pdf_data();
		if ( is_wp_error( $data ) ) {
			wp_send_json_error( array( 'message' => $data->get_error_message() ) );
		}

		$item    = $data['item'];
		$pdf_url = $data['pdf_url'];

		$whatsapp_text = sprintf(
			/* translators: %s: URL to download the budget PDF */
			__( 'Budget (PDF): %s', 'pbc' ),
			$pdf_url
		);
		$whatsapp_text = apply_filters( 'pbc_whatsapp_share_pdf_message', $whatsapp_text, $pdf_url, $item );

		wp_send_json_success(
			array(
				'pdf_url'       => esc_url_raw( $pdf_url ),
				'whatsapp_text' => $whatsapp_text,
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
		if ( PHP_SESSION_NONE === session_status() && ! headers_sent() ) {
			if ( ! session_start() ) {
				wp_send_json_error( 'Session error' );
			}
		}

		$recipient_email = isset( $_POST['recipient_email'] ) ? sanitize_email( wp_unslash( $_POST['recipient_email'] ) ) : '';

		if ( empty( $recipient_email ) || ! is_email( $recipient_email ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid email address.', 'pbc' ),
				)
			);
		}

		$data = $this->pbc_create_share_budget_pdf_data();
		if ( is_wp_error( $data ) ) {
			wp_send_json_error( array( 'message' => $data->get_error_message() ) );
		}

		$item     = $data['item'];
		$pdf_path = $data['pdf_path'];

		$subject = apply_filters(
			'pbc_share_email_pdf_subject',
			__( 'Your budget (PDF)', 'pbc' ),
			$item,
			$recipient_email
		);
		$message = apply_filters(
			'pbc_share_email_pdf_message',
			__( 'Please find your budget attached as a PDF.', 'pbc' ) . "\n\n" . sprintf(
				/* translators: %s: site name */
				__( 'Regards, %s', 'pbc' ),
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
			),
			$item,
			$recipient_email
		);

		$headers     = array( 'Content-Type: text/plain; charset=UTF-8' );
		$attachments = array( $pdf_path );

		/**
		 * Adjust share-email mail (e.g. extra headers or replace attachments).
		 *
		 * @param array $args {
		 *     @type string   $to
		 *     @type string   $subject
		 *     @type string   $message
		 *     @type string[] $headers
		 *     @type string[] $attachments
		 *     @type array    $item Budget item data.
		 * }
		 */
		$mail_args = apply_filters(
			'pbc_share_email_pdf_mail',
			array(
				'to'          => $recipient_email,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $headers,
				'attachments' => $attachments,
				'item'        => $item,
			),
			$item,
			$recipient_email
		);

		$sent = wp_mail(
			$mail_args['to'],
			$mail_args['subject'],
			$mail_args['message'],
			$mail_args['headers'],
			$mail_args['attachments']
		);

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
