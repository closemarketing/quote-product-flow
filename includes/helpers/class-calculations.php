<?php
/**
 * Class for Calculations
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

namespace Close\PBC\Helpers;

defined( 'ABSPATH' ) || exit;

use Close\PBC\Helpers\PDF;

/**
 * Helper Calculate PBC.
 *
 * All helpers calculations.
 *
 * @since 1.1
 */
class CALC {
	/**
	 * Get variation image url with filter dependency
	 *
	 * @param array   $session_variation Session variation.
	 * @param integer $variation_id Variation ID.
	 * @return string
	 */
	public static function get_image_variation_url( $session_variation, $variation_id = 0 ) {
		if ( ! isset( $variation_id ) ) {
			return '';
		}
		$imgprodgroup = get_post_meta( $variation_id, 'pbc_imgprodgroup', true );
		if ( ! empty( $imgprodgroup ) ) {
			foreach ( $imgprodgroup as $deps ) {
				if ( ! empty( $deps['pbc_depvarimgprod'] ) ) {
					$prevVar = array();
					foreach ( $deps['pbc_depvarimgprod'] as $depvarimgprod ) {
						$imgprod_arr = explode( '|', $depvarimgprod );
						if ( ! empty( $imgprod_arr[0] ) && ! empty( $imgprod_arr[1] ) ) {
							$prevVar[(int)$imgprod_arr[0]][] = $imgprod_arr[1];
						}
					}
					if ( ! empty( $session_variation ) && ! empty( $prevVar ) ) {
						foreach ( $prevVar as $sPhaseKey => $sVariations ) {
							if ( isset( $prevVar[ $sPhaseKey ]) &&
							isset( $session_variation[ $sPhaseKey ] ) &&
							in_array( $session_variation[ $sPhaseKey ]['var']['id'], $prevVar[ $sPhaseKey ] ) ) {
								$imgprod_id = $deps['pbc_imgprod'][0];
							} else {
								$imgprod_id = '';
								break;
							}
						}
					}
				} elseif ( ( ! isset( $deps['pbc_depvarimgprod'] ) || empty($deps['pbc_depvarimgprod'])) && isset($deps['pbc_imgprod']) ) {
						$imgprod_id = $deps['pbc_imgprod'][0];
						break;
				}
				if ( ! empty( $imgprod_id ) ) {
					break;
				}
			}
		}
		if ( isset( $imgprod_id ) && $imgprod_id ) {
			$imgprodurl = wp_get_attachment_image_src( $imgprod_id, 'full', true );
		}

		return isset( $imgprodurl[0] ) ? $imgprodurl[0] : '';
	}

	/**
	 * Gets total price from enquiry
	 *
	 * @param integer $post_id
	 * @return float
	 */
	public static function get_total_from_enquiry( $post_id ) {
		$metas = get_post_meta( $post_id );
		$total_price = 0;
		foreach ( $metas as $key => $value ) {
			if ( false !== strpos( $key, 'pbc_price_' ) ) {
				$price = isset( $value[0] ) ? (double) str_replace( ',', '.', $value[0] ) : 0;
				$total_price = $total_price + $price;
			}
		}

		return $total_price;
	}

	/**
	 * Calculates the color of the text based on the background color.
	 *
	 * @param string $background_hex Background color in hex.
	 * @return float
	 */
	public static function calculate_color_text( $background_hex ) {
		list($r1, $g1, $b1) = sscanf( $background_hex, "#%02x%02x%02x" );

		// Black.
		$r2 = 0;
		$g2 = 0;
		$b2 = 0;

		$contrast = max( $r1, $r2) - min( $r1, $r2 ) + max( $g1, $g2 ) - min( $g1, $g2 ) + max( $b1, $b2) - min( $b1, $b2 );

		return $contrast > 500 ? '#000000' : '#ffffff';
	}
	/**
	 * Adjusts the brightness of a hex color.
	 *
	 * @param string $hex   Hex color code.
	 * @param int    $steps Steps to adjust brightness.
	 * @return string Adjusted hex color code.
	 */
	public static function adjust_brightness( $hex, $steps ) {
		// Remove hash if present.
		$hex = str_replace( '#', '', $hex );

		// Convert to RGB.
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		// Adjust brightness.
		$r = max( 0, min( 255, $r + $steps ) );
		$g = max( 0, min( 255, $g + $steps ) );
		$b = max( 0, min( 255, $b + $steps ) );

		// Convert back to hex.
		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}
	/**
	 * Returns if product is multiple
	 *
	 * @return boolean
	 */
	public static function is_multiple_products() {
		$args          = array(
			'post_type'      => 'phases',
			'posts_per_page' => -1,
			'post_parent'    => 0,
			'fields'         => 'ids',
		);
		$parent_phases = get_posts( $args );
		$total_phases  = (int) wp_count_posts( 'phases' )->publish;

		return count( $parent_phases ) !== $total_phases;
	}

	/**
	 * Gets default parent phase
	 *
	 * @return int
	 */
	public static function get_default_parent_phase() {
		$args          = array(
			'post_type'   => 'phases',
			'numberposts' => 1,
			'post_parent' => 0,
			'fields'      => 'ids',
		);
		$default_phase = get_posts( $args );

		return ! empty( $default_phase[0] ) ? (int) $default_phase[0] : 0;
	}

	/**
	 * Adds zero to number
	 *
	 * @param int $number Number to add zero.
	 * @return string
	 */
	public static function adds_zero( $number ) {
		return $number < 10 ? '0' . $number : $number;
	}

	/**
	 * Gets the phases options
	 *
	 * @return array
	 */
	public static function get_phases_options() {
		// Phase Filter.
		$phase_options = array();
		$phasescpt     = get_posts(
			array(
				'post_type'      => 'phases',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'post_parent'    => 0,
			)
		);
		foreach ( $phasescpt as $item ) {
			$children     = get_posts(
				array(
					'post_type'      => 'phases',
					'post_parent'    => $item->ID,
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
				)
			);
			$has_children = ! empty( $children );
			if ( $has_children ) {
				foreach ( $children as $child ) {
					$label = $item->post_title . ' - ' . self::adds_zero( $child->menu_order ) . ' - ';

					$phase_options[ $child->ID ] = $label . $child->post_title;
				}
			} else {
				$phase_options[ $item->ID ] = self::adds_zero( $item->menu_order ) . ' - ' . $item->post_title;
			}
		}
		return $phase_options;
	}

	/**
	 * Gets the user discount and role
	 *
	 * @return array
	 */
	public static function get_user_discount_and_role() {
		$user = wp_get_current_user();
		if ( ! empty( $user->roles ) ) {
			$role_slug     = $user->roles[0];
			$role_discount = (int) get_option( 'pbc_discount_user_' . $role_slug, true );
			if ( ! empty( $role_discount ) ) {
				return [
					'role'     => $role_slug,
					'discount' => $role_discount,
				];
			}
		}
		return [
			'role'     => '',
			'discount' => 0,
		];
	}

	/**
	 * Gets the price variation with roles
	 *
	 * @param int    $variation_id Variation ID.
	 * @param string $price_var Price variation.
	 * @return array
	 */
	public static function get_price_variation( $variation_id, $price_var ) {
		$user  = wp_get_current_user();
		$price = null;

		$pricegroup = get_post_meta( $variation_id, 'pbc_pricegroup', true );
		if ( ! empty( $pricegroup ) && is_array( $pricegroup ) ) {
			$price = array_search( $price_var, array_column( $pricegroup, 'pbc_meaprice', 'pbc_pricem' ), true );
			if ( false === $price && isset( $pricegroup[0]['pbc_pricem'] ) ) {
				$price = $pricegroup[0]['pbc_pricem'];
				if ( ! empty( $user->roles ) ) {
					$role_slug     = $user->roles[0];
					$role_discount = (int) get_option( 'pbc_discount_user_' . $role_slug, true );
					if ( ! empty( $role_discount ) ) {
						$price = $price - ( $price * $role_discount / 100 );
					}
				}
			}
		}
		return $price;
	}

	/**
	 * Save enquiry post
	 *
	 * @param array $item Data to save.
	 * @return int
	 */
	public static function configurator_save_enquiry( $item ) {
		$contact          = $item['pbc_contact'] ?? [];
		$email_field      = ! empty( $contact['email_field'] ) ? sanitize_text_field( $contact['email_field'] ) : '';
		$name_field       = ! empty( $contact['name_field'] ) ? sanitize_text_field( $contact['name_field'] ) : '';
		$phone_field      = ! empty( $contact['phone_field'] ) ? sanitize_text_field( $contact['phone_field'] ) : '';
		$city_field       = ! empty( $contact['city_field'] ) ? sanitize_text_field( $contact['city_field'] ) : '';
		$state_field      = ! empty( $contact['state_field'] ) ? sanitize_text_field( $contact['state_field'] ) : '';
		$comments_field   = ! empty( $contact['comments_field'] ) ? sanitize_textarea_field( $contact['comments_field'] ) : '';
		$pbc_session_key  = ! empty( $item['pbc_session_key'] ) ? sanitize_text_field( $item['pbc_session_key'] ) : '';
		$pbc_parent_phase = ! empty( $item['pbc_parent_phase'] ) ? (int) $item['pbc_parent_phase'] : 0;

		$meta = [
			'pbc_enquiry_name'     => $name_field,
			'pbc_enquiry_phone'    => $phone_field,
			'pbc_enquiry_email'    => $email_field,
			'pbc_enquiry_city'     => $city_field,
			'pbc_enquiry_state'    => $state_field,
			'pbc_parent_phase'     => $pbc_parent_phase,
			'pbc_enquiry_comments' => $comments_field,
		];
		// Calculate enquiry entries.
		$i = 0;
		foreach ( $item[ $pbc_session_key ] as $details ) { // phpcs:ignore
			if ( ! is_array( $details ) ) {
				continue;
			}
			$phase_name      = isset( $details['phase']['name'] ) ? sanitize_text_field( $details['phase']['name'] ) : '';
			$variation_name  = isset( $details['var']['name'] ) ? sanitize_text_field( $details['var']['name'] ) : '';
			$price           = (float) $details['var']['price'];

			$meta[ 'pbc_phase_name_' . $i ] = $phase_name;
			$meta[ 'pbc_phase_var_' . $i ]  = $variation_name;
			$meta[ 'pbc_price_' . $i ]      = number_format( $price, 2, ',', '.' );
			$meta[ 'pbc_type_' . $i ]       = isset( $details['var']['type'] ) ? sanitize_text_field( $details['var']['type'] ) : '';
			++$i;
		}
		$meta['pbc_total_var'] = count( $item[ $pbc_session_key ] );

		$title  = __( 'Enquiry', 'pbc' ) . ' - ' . gmdate( 'Y-m-d H:i:s' );
		$title .= ! empty( $name_field ) ? ' - ' . $name_field . '-' . $phone_field : '';

		$enquiry_post = array(
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_type'   => 'enquiry',
			'meta_input'  => $meta,
		);
		return wp_insert_post( $enquiry_post );
	}

	/**
	 * Sends email with configurator result
	 *
	 * @param array $item Post data.
	 * @return array
	 */
	public static function configurator_result_email_send( $item ) {
		$email_field     = $item['pbc_contact']['email'] ?? '';
		$name_field      = $item['pbc_contact']['name'] ?? '';
		$phone_field     = $item['pbc_contact']['phone'] ?? '';
		$city_field      = $item['pbc_contact']['city'] ?? '';
		$state_field     = $item['pbc_contact']['state'] ?? '';
		$comments_field  = $item['pbc_contact']['comments'] ?? '';
		$pbc_session_key = $item['pbc_session_key'] ?? '';
		$user            = wp_get_current_user();
		$user_role       = ! empty( $user->roles ) && isset( $user->roles[0] ) ? $user->roles[0] : '';
		$show_prices     = PBC_Admin_Plugin::get_show_prices_for_user( $user_role );
		$show_prices     = 'yes' === $show_prices ? true : false;

		if ( ! $email_field ) {
			$result = array(
				'type'     => 'error',
				'response' => __( 'Email field empty!', 'pbc' ),
			);
		} elseif ( ! $name_field ) {
			$result = array(
				'type'     => 'error',
				'response' => __( 'Name field is empty!', 'pbc' ),
			);
		} elseif ( ! $phone_field ) {
			$result = array(
				'type'     => 'error',
				'response' => __( 'Phone field is empty!', 'pbc' ),
			);
		} else {
			$emails       = explode( ',', $email_field );
			$admin_emails = get_option( 'pbc_admin_email_notification' );
			if ( $admin_emails ) {
				$admin_emails = explode( ',', $admin_emails );
				$emails       = array_merge( $emails, $admin_emails );
			}
			$emails = array_map( 'trim', $emails );
			$emails = array_unique( $emails );
			$emails = array_filter( $emails );
			if ( ! isset( $_SESSION[ $pbc_session_key ] ) ) {
				$result = array(
					'type'     => 'error',
					'response' => __( 'Configurator not ready!', 'pbc' ),
				);
			} else {
				$subject        = __( 'Budget Configurator', 'pbc' ) . ' - ' . get_option( 'blogname' );
				$message        = '<div><h2>' . __( 'Enquiry details:', 'pbc' ) . '</h2><br/>';
				$message       .= '<strong>' . __( 'Name:', 'pbc' ) . '</strong>' . $name_field . '<br/>';
				$message       .= '<strong>' . __( 'Email:', 'pbc' ) . '</strong>' . $email_field . '<br/>';
				$message       .= '<strong>' . __( 'Phone:', 'pbc' ) . '</strong>' . $phone_field . '<br/>';
				$message       .= '<strong>' . __( 'City:', 'pbc' ) . '</strong>' . $city_field . '<br/>';
				$message       .= '<strong>' . __( 'State:', 'pbc' ) . '</strong>' . $state_field . '<br/>';
				$message       .= '<strong>' . __( 'Comments:', 'pbc' ) . '</strong>' . $comments_field . '<br/>';
				$message       .= '<br/></div>';
				$message       .= '<h4>' . __( 'Configuration details:', 'pbc' ) . '</h4>' . '<br>';
				$message       .= '<table><tr><th>' . __( 'Phase', 'pbc' ) . '</th><th>' . __( 'Variation', 'pbc' ) . '</th><th>' . __( 'Price', 'pbc' ) . '</th></tr>';
				$subtotal_price = 0;

				$i = 0;
				foreach ( $item[ $pbc_session_key ] as $details ) { // phpcs:ignore
					if ( ! is_array( $details ) ) {
						continue;
					}
					$phase_name      = isset( $details['phase']['name'] ) ? sanitize_text_field( $details['phase']['name'] ) : '';
					$variation_name  = isset( $details['var']['name'] ) ? sanitize_text_field( $details['var']['name'] ) : '';
					$price           = (float) $details['var']['price'];
					$subtotal_price += $price;
					$message        .= '<tr>';
					$message        .= '<td>' . $phase_name . '</td>';
					$message        .= '<td>' . $variation_name . '</td>';
					$message        .= '<td>';
					if ( $price > 0 && $show_prices ) {
						$message .= number_format( $price, 2, ',', '.' ) . ' €';
					}
					$message .= '</td>';
					$message .= '</tr>';
					++$i;
				}
				$message .= '</table><br/>';
				// Subtotal.
				if ( $show_prices ) {
					$message .= '<table>';
					$message .= '<tr>';
					$message .= '<td>' . __( 'Subtotal:', 'pbc' ) . '</td>';
					$message .= '<td>' . number_format( $subtotal_price, 2, ',', '.' ) . ' €' . '</td>';
					$message .= '</tr>';
					$message .= '<tr>';
					$message .= '<td>' . __( 'Tax:', 'pbc' ) . '</td>';
					$vat      = $subtotal_price * 0.21;
					$message .= '<td>' . number_format( $vat, 2, ',', '.' ) . ' €</td>';
					$message .= '</tr>';
					$message .= '<tr>';
					$message .= '<td>' . __( 'Total:', 'pbc' ) . '</td>';
					$message .= '<td>' . number_format( $subtotal_price + $vat, 2, ',', '.' ) . ' €</td>';
					$message .= '</tr>';
					$message .= '</table>';
				}

				$message .= '<br>' . get_option( 'blogname' );
				$headers  = array( 'Content-Type: text/html; charset=UTF-8' );

				// Insert_enquiry Post.
				$post_id = self::configurator_save_enquiry( $item );
				if ( $post_id ) {
					$item['pbc_enquiry'] = $post_id;
					$attachments         = array( PDF::generate_engine_pdf( $item ) );
				}

				if ( ! wp_mail( $emails, $subject, $message, $headers, $attachments ) ) {
					$result = array(
						'type'     => 'error',
						'response' => __( 'Error in sending mail. Please try again!', 'pbc' ),
					);
				} else {
					$filename = __( 'budget', 'pbc' ) . '-' . sanitize_title( get_bloginfo( 'name' ) ) . '-' . date( 'Y-m-d-H-i' ) . '.pdf';
					$file_pdf = PDF::get_budget_base_dir() . $filename;
					if ( ! empty( $attachments ) && file_exists( $file_pdf ) ) {
						unlink( $file_pdf );
					}
					$result = array(
						'type'     => 'success',
						'response' => __( 'Mail sent!', 'pbc' ),
					);
				}
				remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
			}
		}
		return $result;
	}
}
