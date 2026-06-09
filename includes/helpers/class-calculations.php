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
		if ( empty( $variation_id ) ) {
			return '';
		}
		$imgprodgroup = get_post_meta( $variation_id, 'pbc_imgprodgroup', true );
		if ( ! empty( $imgprodgroup ) ) {
			foreach ( $imgprodgroup as $deps ) {
				if ( ! empty( $deps['pbc_depvarimgprod'] ) ) {
					$prev_var = array();
					foreach ( $deps['pbc_depvarimgprod'] as $depvarimgprod ) {
						$imgprod_arr = explode( '|', $depvarimgprod );
						if ( ! empty( $imgprod_arr[0] ) && ! empty( $imgprod_arr[1] ) ) {
							$prev_var[ (int) $imgprod_arr[0] ][] = $imgprod_arr[1];
						}
					}
					if ( ! empty( $session_variation ) && ! empty( $prev_var ) ) {
						foreach ( $prev_var as $s_phase_key => $s_variations ) {
							if ( isset( $prev_var[ $s_phase_key ] ) &&
							isset( $session_variation[ $s_phase_key ] ) &&
							in_array( $session_variation[ $s_phase_key ]['var']['id'], $prev_var[ $s_phase_key ], true ) ) {
								$imgprod_id = $deps['pbc_imgprod'][0];
							} else {
								$imgprod_id = '';
								break;
							}
						}
					}
				} elseif ( ( ! isset( $deps['pbc_depvarimgprod'] ) || empty( $deps['pbc_depvarimgprod'] ) ) && isset( $deps['pbc_imgprod'] ) ) {
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
	 * Whether the calculate step will render at least one product preview image (same logic as template).
	 *
	 * @param string $session_key Session key e.g. pbc_variation_{id}.
	 * @return bool
	 */
	public static function calculate_has_product_preview_image( $session_key ) {
		if ( empty( $_SESSION[ $session_key ] ) || ! is_array( $_SESSION[ $session_key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return false;
		}
		$sess = $_SESSION[ $session_key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$to   = count( $sess ) + 1;
		$ss_var = 0;

		for ( $i = 1; $i < $to; $i++ ) {
			$imgprodid  = '';
			$imgprodurl = '';
			if ( isset( $sess[ $i ]['var']['id'] ) ) {
				$ss_var       = (int) $sess[ $i ]['var']['id'];
				$imgprodgroup = get_post_meta( $ss_var, 'pbc_imgprodgroup', true );
				if ( ! empty( $imgprodgroup ) ) {
					foreach ( $imgprodgroup as $deps ) {
						if ( isset( $deps['pbc_depvarimgprod'] ) && ! empty( $deps['pbc_depvarimgprod'] ) && isset( $deps['pbc_imgprod'] ) ) {
							$prev_var = array();
							foreach ( $deps['pbc_depvarimgprod'] as $depvarimgprod ) {
								$imgprod_arr = explode( '|', $depvarimgprod );
								if ( ! empty( $imgprod_arr[0] ) && ! empty( $imgprod_arr[1] ) ) {
									$prev_var[ (int) $imgprod_arr[0] ][] = $imgprod_arr[1];
								}
							}
							if ( ! empty( $prev_var ) ) {
								foreach ( $prev_var as $s_phase_key => $s_variations ) {
									if ( isset( $prev_var[ $s_phase_key ] ) &&
										isset( $sess[ $s_phase_key ]['var']['id'] ) &&
										in_array( $sess[ $s_phase_key ]['var']['id'], $prev_var[ $s_phase_key ], true ) ) {
										$imgprodid = $deps['pbc_imgprod'][0];
									} else {
										$imgprodid = '';
										break;
									}
								}
							}
						} elseif ( ( ! isset( $deps['pbc_depvarimgprod'] ) || empty( $deps['pbc_depvarimgprod'] ) ) && isset( $deps['pbc_imgprod'] ) ) {
							$imgprodid = $deps['pbc_imgprod'][0];
							break;
						}
						if ( $imgprodid ) {
							break;
						}
					}
				}
				if ( $imgprodid ) {
					$src = wp_get_attachment_image_src( $imgprodid, 'full', true );
					if ( ! empty( $src[0] ) ) {
						return true;
					}
				}
			}
		}

		$fallback = ! empty( $ss_var ) ? self::get_image_variation_url( $sess, $ss_var ) : '';

		return ! empty( $fallback );
	}

	/**
	 * Gets total price from enquiry
	 *
	 * @param integer $post_id Post ID of the enquiry.
	 * @return float
	 */
	public static function get_total_from_enquiry( $post_id ) {
		$metas       = get_post_meta( $post_id );
		$total_price = 0;
		foreach ( $metas as $key => $value ) {
			if ( false !== strpos( $key, 'pbc_price_' ) ) {
				$price       = isset( $value[0] ) ? (float) str_replace( ',', '.', $value[0] ) : 0;
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
		list($r1, $g1, $b1) = sscanf( $background_hex, '#%02x%02x%02x' );

		// Black.
		$r2 = 0;
		$g2 = 0;
		$b2 = 0;

		$contrast = max( $r1, $r2 ) - min( $r1, $r2 ) + max( $g1, $g2 ) - min( $g1, $g2 ) + max( $b1, $b2 ) - min( $b1, $b2 );

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
		$counts       = wp_count_posts( 'phases' );
		$total_phases  = isset( $counts->publish ) ? (int) $counts->publish : 0;

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
	 * Validates multiple phase options
	 *
	 * This function allows checking if phases meet specified conditions.
	 * Useful for validating phase availability, dependencies, and status.
	 *
	 * @param array $phase_ids Array of phase IDs to check.
	 * @param array $options {
	 *     Optional. Array of options to check.
	 *
	 *     @type bool   $published       Check if phases are published. Default true.
	 *     @type int    $parent          Check if phases have specific parent. Default null.
	 *     @type array  $meta_conditions Array of meta key => value conditions. Default empty.
	 *     @type bool   $all_must_pass   If true, all phases must pass. If false, at least one. Default true.
	 * }
	 * @return array {
	 *     Results of validation.
	 *
	 *     @type bool  $valid       True if validation passes according to all_must_pass option.
	 *     @type array $passed_ids  Array of phase IDs that passed validation.
	 *     @type array $failed_ids  Array of phase IDs that failed validation.
	 *     @type array $details     Detailed results per phase ID.
	 * }
	 */
	public static function check_phases_options( $phase_ids, $options = array() ) {
		// Default options.
		$defaults = array(
			'published'       => true,
			'parent'          => null,
			'meta_conditions' => array(),
			'all_must_pass'   => true,
		);

		$options = wp_parse_args( $options, $defaults );

		$results = array(
			'valid'      => false,
			'passed_ids' => array(),
			'failed_ids' => array(),
			'details'    => array(),
		);

		// Ensure phase_ids is an array.
		if ( ! is_array( $phase_ids ) ) {
			$phase_ids = array( $phase_ids );
		}

		foreach ( $phase_ids as $phase_id ) {
			$phase_id = (int) $phase_id;
			$passed   = true;
			$reasons  = array();

			// Check if phase exists and is published.
			if ( $options['published'] ) {
				$phase = get_post( $phase_id );
				if ( ! $phase || 'publish' !== $phase->post_status || 'phases' !== $phase->post_type ) {
					$passed    = false;
					$reasons[] = 'not_published';
				}
			}

			// Check parent if specified.
			if ( $passed && null !== $options['parent'] ) {
				$phase = isset( $phase ) ? $phase : get_post( $phase_id );
				if ( $phase && (int) $phase->post_parent !== (int) $options['parent'] ) {
					$passed    = false;
					$reasons[] = 'parent_mismatch';
				}
			}

			// Check meta conditions.
			if ( $passed && ! empty( $options['meta_conditions'] ) ) {
				foreach ( $options['meta_conditions'] as $meta_key => $expected_value ) {
					$meta_value = get_post_meta( $phase_id, $meta_key, true );

					// Support for array of possible values.
				if ( is_array( $expected_value ) ) {
						if ( ! in_array( $meta_value, $expected_value, true ) ) {
							$passed    = false;
							$reasons[] = "meta_{$meta_key}_not_in_expected";
							}
				} elseif ( $meta_value !== $expected_value ) {
						// Direct comparison.
						$passed    = false;
						$reasons[] = "meta_{$meta_key}_mismatch";
				}
				}
			}

			// Store results.
			if ( $passed ) {
				$results['passed_ids'][] = $phase_id;
			} else {
				$results['failed_ids'][] = $phase_id;
			}

			$results['details'][ $phase_id ] = array(
				'passed'  => $passed,
				'reasons' => $reasons,
			);
		}

		// Determine overall validity.
		if ( $options['all_must_pass'] ) {
			$results['valid'] = empty( $results['failed_ids'] );
		} else {
			$results['valid'] = ! empty( $results['passed_ids'] );
		}

		return $results;
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
		$price = 0;

		$pricegroup = get_post_meta( $variation_id, 'pbc_pricegroup', true );
		if ( ! empty( $pricegroup ) && is_array( $pricegroup ) ) {
			$found = array_search( $price_var, array_column( $pricegroup, 'pbc_meaprice', 'pbc_pricem' ), true );
			if ( false !== $found && isset( $pricegroup[ $found ]['pbc_pricem'] ) ) {
				$price = (float) $pricegroup[ $found ]['pbc_pricem'];
			} elseif ( isset( $pricegroup[0]['pbc_pricem'] ) ) {
				$price = (float) $pricegroup[0]['pbc_pricem'];
				if ( ! empty( $user->roles ) ) {
					$role_slug     = $user->roles[0];
					$role_discount = (int) get_option( 'pbc_discount_user_' . $role_slug, true );
					if ( ! empty( $role_discount ) ) {
						$price = $price - ( $price * $role_discount / 100 );
					}
				}
			}
		}
		return (float) $price;
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
			$phase_name = isset( $details['phase']['name'] ) ? sanitize_text_field( $details['phase']['name'] ) : '';

			// Check if this phase has multiple questions.
			$has_multiple_questions = isset( $details['questions'] ) && is_array( $details['questions'] );

			if ( $has_multiple_questions ) {
				// Save all questions from this phase.
				foreach ( $details['questions'] as $question_data ) {
					$variation_name = $question_data['variation_title'] . ': ' . $question_data['answer'];

					$meta[ 'pbc_phase_name_' . $i ] = $phase_name;
					$meta[ 'pbc_phase_var_' . $i ]  = $variation_name;
					$meta[ 'pbc_price_' . $i ]      = '-';
					$meta[ 'pbc_type_' . $i ]       = 'question';
					++$i;
				}
			} else {
				// Save single variation or single question (old format).
				$variation_name = isset( $details['var']['name'] ) ? sanitize_text_field( $details['var']['name'] ) : '';
				$price          = (float) $details['var']['price'];

				$meta[ 'pbc_phase_name_' . $i ] = $phase_name;
				$meta[ 'pbc_phase_var_' . $i ]  = $variation_name;
				$meta[ 'pbc_price_' . $i ]      = number_format( $price, 2, ',', '.' );
				$meta[ 'pbc_type_' . $i ]       = isset( $details['var']['type'] ) ? sanitize_text_field( $details['var']['type'] ) : '';
				++$i;
			}
		}
		$meta['pbc_total_var'] = $i;

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

		$user        = wp_get_current_user();
		$user_role   = ! empty( $user->roles ) && isset( $user->roles[0] ) ? $user->roles[0] : '';
		$show_prices = self::get_show_prices_for_user( $user_role );
		$show_prices = 'yes' === $show_prices ? true : false;

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
				$message       .= '<h4>' . __( 'Configuration details:', 'pbc' ) . '</h4><br>';
				$message       .= '<table><tr><th>' . __( 'Phase', 'pbc' ) . '</th><th>' . __( 'Variation', 'pbc' ) . '</th><th>' . __( 'Price', 'pbc' ) . '</th></tr>';
				$subtotal_price = 0;

				$i = 0;
			foreach ( $item[ $pbc_session_key ] as $details ) { // phpcs:ignore
					if ( ! is_array( $details ) ) {
						continue;
						}
					$phase_name = isset( $details['phase']['name'] ) ? sanitize_text_field( $details['phase']['name'] ) : '';

					// Check if this phase has multiple questions.
					$has_multiple_questions = isset( $details['questions'] ) && is_array( $details['questions'] );

					if ( $has_multiple_questions ) {
						// Show all questions from this phase.
						foreach ( $details['questions'] as $question_data ) {
							$variation_name = $question_data['variation_title'] . ': ' . $question_data['answer'];
							$message       .= '<tr>';
							$message       .= '<td>' . $phase_name . '</td>';
							$message       .= '<td>' . $variation_name . '</td>';
							$message       .= '<td>-</td>';
							$message       .= '</tr>';
						}
						} else {
						// Show single variation or single question (old format).
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
						}
					++$i;
			}
				$message .= '</table><br/>';
				// Subtotal.
				if ( $show_prices ) {
					$message .= '<table>';
					$message .= '<tr>';
					$message .= '<td>' . __( 'Subtotal:', 'pbc' ) . '</td>';
					$message .= '<td>' . number_format( $subtotal_price, 2, ',', '.' ) . ' €</td>';
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
				$post_id     = self::configurator_save_enquiry( $item );
				$attachments = array();
				$pdf_path    = null;

				if ( $post_id ) {
					$item['pbc_enquiry']     = $post_id;
					$item['pbc_budget_date'] = gmdate( 'd-m-Y' );

					// Generate PDF and get the file path.
					$pdf_path = PDF::generate_engine_pdf( $item, 'path' );

					if ( $pdf_path && file_exists( $pdf_path ) ) {
						$attachments = array( $pdf_path );
					}
				}

				// Send email.
				$mail_sent = wp_mail( $emails, $subject, $message, $headers, $attachments );

				// Clean up PDF file after sending.
				if ( $pdf_path && file_exists( $pdf_path ) ) {
					wp_delete_file( $pdf_path );
				}

				if ( ! $mail_sent ) {
					$result = array(
						'type'     => 'error',
						'response' => __( 'Error in sending mail. Please try again!', 'pbc' ),
					);
				} else {
					$result = array(
						'type'     => 'success',
						'response' => __( 'Mail sent!', 'pbc' ),
					);
				}
			}
		}
		return $result;
	}

	/**
	 * Get show prices setting for user.
	 *
	 * Checks user role setting first, then falls back to global setting.
	 *
	 * @param string $user_role User role slug.
	 * @return string 'yes' or 'no'
	 */
	public static function get_show_prices_for_user( $user_role = '' ) {
		if ( ! empty( $user_role ) ) {
			$role_setting = get_option( 'pbc_show_prices_user_' . $user_role );

			if ( ! empty( $role_setting ) && 'yes' === $role_setting ) {
				return 'yes';
			}
			if ( ! empty( $role_setting ) && 'no' === $role_setting ) {
				return 'no';
			}
		}

		$global_setting = get_option( 'pbc_show_prices_global', 'yes' );
		return 'yes' === $global_setting ? 'yes' : 'no';
	}
}
