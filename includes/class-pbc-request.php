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
use Close\PBC\Helpers\SHOW;
use Spipu\Html2Pdf\Html2Pdf;

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
		$current_phase = isset( $_REQUEST['current_phase'] ) ? (int) $_REQUEST['current_phase'] : 0;
		$pbc_variation = isset( $_REQUEST['pbc_variation'] ) ? $_REQUEST['pbc_variation'] : [];
		$parent_phase  = isset( $_POST['pbc_parent_phase'] ) ? (int) $_POST['pbc_parent_phase'] : 0;
		$session_key   = 'pbc_variation_' . $parent_phase;

		if ( session_id() == '' ) {
			ob_start();
			session_start();
		}
		if ( session_id() == '' ) {
			echo ';;--;;' . json_encode(
				array(
					'type' => 'error',
					'msg'  => 'Error: Unable to initialize Session!',
				)
			);
			die( 0 );
		}
		if ( ! empty( $pbc_variation ) && $current_phase && $pbc_variation[ $current_phase ] ) {
			$svar = $pbc_variation[ $current_phase ];
			if ( is_user_logged_in() ) {
				$user_id                 = get_current_user_id();
				$phase_param['var']      = $svar;
				$phase_param['pricevar'] = isset( $_REQUEST[ "pbc_pricevar_$svar" ] ) ? ( $_REQUEST[ "pbc_pricevar_$svar" ] ) : '';
				update_user_meta( $user_id, 'pbc_phase_' . $current_phase, $phase_param );
			}
			// Gets image variation with filter dependency.
			if ( isset( $_SESSION[ $session_key ] ) ) {
				$imgprodurl = CALC::get_image_variation_url( sanitize_text_field( wp_unslash( $_SESSION[ $session_key ] ) ), $svar );
			}
			$pricevar = isset( $_REQUEST[ "pbc_pricevar_$svar" ] ) ? (float) $_REQUEST[ "pbc_pricevar_$svar" ] : null;
			$price    = CALC::get_price_variation( $svar, $pricevar );

			$option = get_the_title( $svar );
			if ( isset( $option_name ) && $option_name ) {
				$option .= ' [' . $option_name . ']';
			}

			$variations_images_flipped = get_option( 'variations_images_flipped' );
			if ( ! empty( $variations_images_flipped ) ) {
				for ( $j = 1; $j < (int) $current_phase; $j++ ) {
					if ( isset( $_SESSION[ $session_key ][ $j ] ) && in_array( $_SESSION[ $session_key ][ $j ]['var']['id'], $variations_images_flipped ) ) {
						$flipped = true;
					}
				}
				if ( in_array( $svar, $variations_images_flipped ) ) {
					$flipped = true;
				}
			}
		}

		$price   = empty( $price ) ? '-' : number_format( $price, 2, ',', '.' ) . ' €';
		$option  = ! isset( $option ) ? '-' : $option;
		$flipped = ! isset( $flipped ) ? false : $flipped;
		echo ';;--;;' . json_encode(
			array(
				'type'    => 'success',
				'url'     => $imgprodurl,
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
		if ( ! check_ajax_referer( 'pbc_template_wizard_action', 'pbc_template_wizard_nonce', false ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}
		$submit = isset( $_POST['submit'] ) ? sanitize_text_field( wp_unslash( $_POST['submit'] ) ) : '';
		if ( 'email_send' === $submit ) {
			if ( empty( session_id() ) ) {
				session_start();
			}
			$_SESSION['pbc_output'] = CALC::configurator_result_email_send( $_POST );
		}

		ob_start();
		$parent_phase = isset( $_POST['pbc_parent_phase'] ) ? (int) $_POST['pbc_parent_phase'] : 0;
		$template     = isset( $_POST['pbc_template'] ) ? sanitize_text_field( wp_unslash( $_POST['pbc_template'] ) ) : 'wizard';
		PBC_Template::render( $parent_phase, $template );
		$all_details = ob_get_contents();
		ob_end_clean();
		echo $all_details;
		die( 0 );
	}

	public function configurator_login_action_callback() {
		extract( $_POST );
		$login = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => true,
			),
			false
		);
		if ( $login->ID ) {
			ob_start();
			if ( \locate_template( 'template-budget-configurator.php' ) ) {
				\locate_template( 'template-budget-configurator.php', true );
			} else {
				include WPPBC_PLUGIN_DIR . '/includes/template-budget-configurator.php';
			}
			$all_details = ob_get_contents();
			ob_end_clean();
			echo $all_details;
		} elseif ( is_wp_error( $login ) ) {
			echo ';;-;;error;;-;;' . $login->get_error_message();
		}
		die( 0 );
	}
}
new PBC_Requests();