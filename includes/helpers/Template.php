<?php
/**
 * Show Template Wizard
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2024 Closemarketing
 * @version    1.0
 */

namespace CLOSE\QProductFlow\Helpers;

defined( 'ABSPATH' ) || exit;

use CLOSE\QProductFlow\Helpers\CALC;
use CLOSE\QProductFlow\Helpers\SHOW;

/**
 * Template Wizard.
 *
 * @since 1.4.0
 */
class Template {
	/**
	 * Render for Wizard.
	 *
	 * @param integer $parent_phase Parent Phase.
	 * @param string  $template      Template type (wizard or vertical).
	 * @return void
	 */
	public static function render( $parent_phase, $template ) {
		$cstep   = 1;
		$user_id = get_current_user_id();

		// Makes default parent phase.
		$default_post_parent = CALC::get_default_parent_phase();
		$is_multiple_prods   = CALC::is_multiple_products();
		$phase_pid           = $is_multiple_prods && empty( $parent_phase ) ? (int) $default_post_parent : (int) $parent_phase;
		$qpfw_session_key     = 'qpfw_variation_' . $phase_pid;

		$args         = array(
			'numberposts' => -1,
			'post_type' => 'qpfw_phases',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
			'post_parent' => $phase_pid,
		);
		$post_phases  = get_posts( $args );
		$phases       = array();
		$phases_order = array();
		foreach ( $post_phases as $post_phase ) {
			$phases[]       = $post_phase->ID;
			$phases_order[] = $post_phase->menu_order;
		}

			if ( empty( $_POST ) ) {
			if ( ! isset( $_SESSION[ $qpfw_session_key ] ) || ! is_array( $_SESSION[ $qpfw_session_key ] ) ) {
				$_SESSION[ $qpfw_session_key ] = array();
			}
			// Get role and discount.
			$role_discount = CALC::get_user_discount_and_role();

			$_SESSION[ $qpfw_session_key ]['role_slug']     = $role_discount['role'] ?? '';
			$_SESSION[ $qpfw_session_key ]['role_discount'] = $role_discount['discount'] ?? '';
		}

		// Add inline style for the template.
		$color_main = apply_filters( 'qpfw_primary_color', '#835536' );

		$custom_css = '
		.page-configurator,
		.page-configurator-vertical {
			--qpfw-primary: ' . esc_attr( $color_main ) . ';
			--qpfw-primary-contrast: ' . esc_attr( CALC::calculate_color_text( $color_main ) ) . ';
			--qpfw-primary-hover: ' . esc_attr( CALC::adjust_brightness( $color_main, -20 ) ) . ';
			--qpfw-primary-active: ' . esc_attr( CALC::adjust_brightness( $color_main, -40 ) ) . ';
		}
		.page-configurator .btn,
		.page-configurator button.btn,
		.page-configurator button[type="submit"].btn,
		.page-configurator .btn-next,
		.page-configurator .btn-prev,
		.page-configurator .btn-share,
		.page-configurator .btn-pdf {
			background-color: ' . esc_attr( $color_main ) . ' !important;
			color: ' . esc_attr( CALC::calculate_color_text( $color_main ) ) . ' !important;
		}
		.page-configurator .prev .btn,
		.page-configurator .prev button.btn {
			background-color: ' . esc_attr( CALC::adjust_brightness( $color_main, -20 ) ) . ' !important;
		}
		.page-configurator .btn:hover,
		.page-configurator .btn:focus,
		.page-configurator button.btn:hover,
		.page-configurator button.btn:focus,
		.page-configurator button[type="submit"].btn:hover,
		.page-configurator button[type="submit"].btn:focus {
			background-color: ' . esc_attr( CALC::adjust_brightness( $color_main, -20 ) ) . ' !important;
		}
		.phase_note_top {
			background-color: ' . esc_attr( $color_main ) . ' !important;
			color: ' . esc_attr( CALC::calculate_color_text( $color_main ) ) . ' !important;
		}
		.phase_note_top p,
		.phase_note_top a {
			color: ' . esc_attr( CALC::calculate_color_text( $color_main ) ) . ' !important;
		}';

		// Output the inline style.
		wp_add_inline_style( 'qpfw-public', $custom_css );

		// Verify nonce - check both possible nonce fields for AJAX compatibility.
		$nonce_verified = false;
		if ( isset( $_POST['qpfw_template_wizard_nonce'] ) ) {
			$nonce_verified = wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['qpfw_template_wizard_nonce'] ) ),
				'qpfw_template_wizard_action'
			);
		}
		if ( ! $nonce_verified && isset( $_POST['nonce'] ) ) {
			$nonce_verified = wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
				'qpfw-nonce'
			);
		}

		if ( isset( $_POST['submit'] ) && $nonce_verified ) {
			$submit = sanitize_text_field( wp_unslash( $_POST['submit'] ) );

			// Get current step from form.
			$current_step_from_form = isset( $_POST['qpfw_current_phase'] ) ? (int) $_POST['qpfw_current_phase'] : 1;

			// Check if we're advancing and if current phase has question variations or normal variations.
			$should_advance = true;
			$validation_error = '';

			if ( 'next' === $submit ) {
				$current_phase_id = isset( $phases[ $current_step_from_form - 1 ] ) ? $phases[ $current_step_from_form - 1 ] : 0;
				if ( $current_phase_id ) {
					// Only require choices for variations that are actually visible (same rules as render).
					$visible_variation_ids = self::get_visible_variation_ids_for_step(
						$current_phase_id,
						$current_step_from_form,
						$qpfw_session_key,
						$phases,
						$phases_order
					);

					$has_required_questions   = false;
					$has_normal_variations    = false;
					$required_question_keys   = array();

					foreach ( $visible_variation_ids as $var_id ) {
						$is_question = get_post_meta( $var_id, 'qpfw_is_question', true );
						if ( $is_question ) {
							$is_required = get_post_meta( $var_id, 'qpfw_question_required', true );
							if ( $is_required ) {
								$has_required_questions = true;
								$question_key           = get_post_meta( $var_id, 'qpfw_question_key', true );
								if ( $question_key ) {
									$required_question_keys[] = $question_key;
								}
							}
						} else {
							$has_normal_variations = true;
						}
					}

					// Phase with no visible options: optional direct input, else allow (e.g. note-only step).
					if ( empty( $visible_variation_ids ) ) {
						$show_direct_input = get_post_meta( $current_phase_id, 'qpfw_show_direct_input', true );
						if ( $show_direct_input ) {
							$input_type = get_post_meta( $current_phase_id, 'qpfw_direct_input_type', true );
							if ( empty( $input_type ) ) {
								$input_type = 'textarea';
							}
							$di_raw = isset( $_POST['qpfw_direct_input'][ $current_step_from_form ] )
								? wp_unslash( $_POST['qpfw_direct_input'][ $current_step_from_form ] )
								: '';
							if ( 'textarea' === $input_type ) {
								$di_val = sanitize_textarea_field( $di_raw );
							} else {
								$di_val = sanitize_text_field( $di_raw );
							}
							if ( 'number' !== $input_type && '' === trim( (string) $di_val ) ) {
								$should_advance   = false;
								$validation_error = __( 'Por favor, completa el campo antes de continuar.', 'quote-product-flow' );
							}
						}
					} else {
						if ( $has_required_questions && ! empty( $required_question_keys ) ) {
							if ( empty( $_POST['qpfw_question'] ) ) {
								$should_advance   = false;
								$validation_error = __( 'Por favor, responde todas las preguntas requeridas antes de continuar.', 'quote-product-flow' );
							} else {
								foreach ( $required_question_keys as $req_key ) {
									$answer = isset( $_POST['qpfw_question'][ $req_key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST['qpfw_question'][ $req_key ] ) ) ) : '';
									if ( '' === $answer ) {
										$should_advance   = false;
										$validation_error = __( 'Por favor, responde todas las preguntas requeridas antes de continuar.', 'quote-product-flow' );
										break;
									}
								}
							}
						}
						if ( $should_advance && $has_normal_variations && ! $has_required_questions ) {
							if ( empty( $_POST['qpfw_variation'] ) || ! isset( $_POST['qpfw_variation'][ $current_step_from_form ] ) ) {
								$should_advance   = false;
								$validation_error = __( 'Por favor, selecciona una opción antes de continuar.', 'quote-product-flow' );
							}
						}
					}
				}
			}

			if ( $should_advance ) {
				if ( isset( $_POST[ $submit . '_phase' ] ) && is_numeric( $_POST[ $submit . '_phase' ] ) ) {
					$cstep = (int) $_POST[ $submit . '_phase' ];
				} elseif ( 'generate_pdf' === $submit && isset( $_SESSION['qpfw_output'] ) ) {
					wp_print_inline_script_tag( 'window.open("' . esc_url( sanitize_url( $_SESSION['qpfw_output'] ) ) . '", "_blank");' );
					$cstep = 'calculate';
				} else {
					$cstep = 'calculate';
				}
			} else {
				// Don't advance - stay on current step and show error.
				$cstep = $current_step_from_form;
				// Set error message in session to display.
				if ( ! isset( $_SESSION['qpfw_output'] ) ) {
					$_SESSION['qpfw_output'] = array();
				}
				$error_message = ! empty( $validation_error ) ? $validation_error : __( 'Por favor, completa todos los campos requeridos antes de continuar.', 'quote-product-flow' );
				$_SESSION['qpfw_output']['response'] = '<div class="error">' . esc_html( $error_message ) . '</div>';
				$_SESSION['qpfw_output']['type']     = 'error';
			}

		// Process questions if present.
		if ( isset( $_POST['qpfw_question'] ) && 'next' === $_POST['submit'] ) {
			if ( ! isset( $_SESSION['qpfw_questions'] ) ) {
				$_SESSION['qpfw_questions'] = array();
			}

			$question_variation_ids = isset( $_POST['qpfw_question_variation_id'] ) ? $_POST['qpfw_question_variation_id'] : array(); // phpcs:ignore

			// Group questions by phase to save them all.
			$questions_by_phase = array();

			foreach ( $_POST['qpfw_question'] as $question_key => $answer ) { // phpcs:ignore
				$question_key = sanitize_key( $question_key );
				$answer = sanitize_text_field( wp_unslash( $answer ) );

				// Save answer in global questions array.
				$_SESSION['qpfw_questions'][ $question_key ] = $answer;

				// If we have the variation ID, also save in the standard format.
				if ( isset( $question_variation_ids[ $question_key ] ) ) {
					$variation_id = (int) $question_variation_ids[ $question_key ];
					// Find which step this belongs to.
					foreach ( $phases as $step_idx => $phase_id ) {
						$phase_variations = get_posts( 'numberposts=-1&post_type=variation&meta_key=qpfw_phase&meta_value=' . $phase_id . '&fields=ids' );
						if ( in_array( $variation_id, $phase_variations, true ) ) {
							$step = $step_idx + 1;
							$phase_title = get_the_title( $phase_id );
							$variation_title = get_the_title( $variation_id );

							// Group questions by step to save them all later.
							if ( ! isset( $questions_by_phase[ $step ] ) ) {
								$questions_by_phase[ $step ] = array(
									'phase_id'    => $phase_id,
									'phase_title' => $phase_title,
									'questions'   => array(),
								);
							}

							// Add this question to the phase group.
							$questions_by_phase[ $step ]['questions'][] = array(
								'variation_id'    => $variation_id,
								'variation_title' => $variation_title,
								'question_key'    => $question_key,
								'answer'          => $answer,
							);
							break;
						}
					}
				}
			}

		// Now save all questions for each phase.
		foreach ( $questions_by_phase as $step => $phase_data ) {
			if ( ! isset( $_SESSION[ $qpfw_session_key ][ $step ] ) ) {
				$_SESSION[ $qpfw_session_key ][ $step ] = array();
			}

			// Save phase data.
			$_SESSION[ $qpfw_session_key ][ $step ]['phase']['id']   = $phase_data['phase_id'];
			$_SESSION[ $qpfw_session_key ][ $step ]['phase']['name'] = $phase_data['phase_title'];

			// Save all questions from this phase.
			$_SESSION[ $qpfw_session_key ][ $step ]['questions'] = $phase_data['questions'];

			// For backwards compatibility, also save the first question in the old format.
			if ( ! empty( $phase_data['questions'] ) ) {
				$first_question = $phase_data['questions'][0];
				$_SESSION[ $qpfw_session_key ][ $step ]['var']['id']       = $first_question['variation_id'];
				$_SESSION[ $qpfw_session_key ][ $step ]['var']['name']     = $first_question['variation_title'] . ': ' . $first_question['answer'];
				$_SESSION[ $qpfw_session_key ][ $step ]['var']['type']     = 'question';
				$_SESSION[ $qpfw_session_key ][ $step ]['var']['price']    = 0;
				$_SESSION[ $qpfw_session_key ][ $step ]['question_key']    = $first_question['question_key'];
				$_SESSION[ $qpfw_session_key ][ $step ]['question_answer'] = $first_question['answer'];
			}
		}
		}

			if ( isset( $_POST['qpfw_variation'] ) && 'next' === $_POST['submit'] ) {
				if ( ! isset( $_SESSION[ $qpfw_session_key ] ) || ! is_array( $_SESSION[ $qpfw_session_key ] ) ) {
					$_SESSION[ $qpfw_session_key ] = array();
				}
				foreach ( $_POST['qpfw_variation'] as $key => $variation_data ) { // phpcs:ignore
					if ( empty( $phases ) ) {
						break;
					}

					$phase_id       = $phases[ (int) $key - 1 ];
					$phase_title    = get_the_title( $phase_id );
					$allow_multiple = get_post_meta( $phase_id, 'qpfw_allow_multiple_selections', true );

				if ( $allow_multiple && is_array( $variation_data ) ) {
						// Multiple selection mode.
						$selected_variation_ids = array_map( 'intval', $variation_data );
						$total_price            = 0;
						$variation_names        = array();

					foreach ( $selected_variation_ids as $variation_id ) {
							// Save original variation ID for getting title.
							$original_variation_id = $variation_id;

							$price_var        = isset( $_POST[ 'qpfw_pricevar_' . $variation_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'qpfw_pricevar_' . $variation_id ] ) ) : '';
							$field_type       = 'price';
							$option_qty_value = 0;

							// Gets variation ID in quantity input.
							if ( isset( $_POST['qpfw_variation_id'][ $key ] ) && is_array( $_POST['qpfw_variation_id'][ $key ] ) ) {
								$qty_key = array_search( $variation_id, array_map( 'intval', $_POST['qpfw_variation_id'][ $key ] ), true );
								if ( false !== $qty_key && isset( $_POST['qpfw_variation_id'][ $key ][ $qty_key ] ) ) {
									$option_qty_value = (int) $_POST['qpfw_variation_id'][ $key ][ $qty_key ];
									$variation_id     = (int) $_POST['qpfw_variation_id'][ $key ][ $qty_key ];
								}
							}

							if ( empty( $option_qty_value ) ) {
								$price = CALC::get_price_variation( $original_variation_id, $price_var );
							} else {
								$price      = $option_qty_value;
								$field_type = 'qty';
							}

								// Use original variation ID to get the correct title.
								$variation_title = get_the_title( $original_variation_id );
							if ( $price_var ) {
									$variation_title .= ' [' . $price_var . ']';
							}
								$variation_names[] = $variation_title;
								$total_price      += (float) $price;
						}

						// Check if selection changed - if so, clear all subsequent steps.
						$prev_vars        = isset( $_SESSION[ $qpfw_session_key ][ $key ]['vars'] ) && is_array( $_SESSION[ $qpfw_session_key ][ $key ]['vars'] ) ? array_map( 'intval', $_SESSION[ $qpfw_session_key ][ $key ]['vars'] ) : array();
						$prev_vars_sorted = $prev_vars;
						sort( $prev_vars_sorted );
						$selected_vars_sorted = $selected_variation_ids;
						sort( $selected_vars_sorted );
					if ( $prev_vars_sorted !== $selected_vars_sorted ) {
							// Selection changed, clear all subsequent steps from session.
						foreach ( array_keys( $_SESSION[ $qpfw_session_key ] ) as $step_key ) {
								$step_key = (int) $step_key;
								if ( $step_key > (int) $key ) {
									unset( $_SESSION[ $qpfw_session_key ][ $step_key ] );
									// Also clear user meta for logged in users.
									if ( ! empty( $user_id ) ) {
										delete_user_meta( $user_id, 'qpfw_phase_' . $step_key );
									}
								}
							}
						}

						$_SESSION[ $qpfw_session_key ][ $key ]['phase']['id']   = $phase_id;
						$_SESSION[ $qpfw_session_key ][ $key ]['phase']['name'] = $phase_title;
						$_SESSION[ $qpfw_session_key ][ $key ]['vars'] = $selected_variation_ids;
						$_SESSION[ $qpfw_session_key ][ $key ]['var']['name']   = implode( ', ', $variation_names );
						$_SESSION[ $qpfw_session_key ][ $key ]['var']['type']   = 'multiple';
						$_SESSION[ $qpfw_session_key ][ $key ]['var']['price']  = $total_price;
					} else {
						// Single selection mode (existing code).
						$variation_id = is_array( $variation_data ) ? (int) $variation_data[0] : (int) $variation_data;
						$price        = '';
						$option_name  = '';
						$price_var    = isset( $_POST[ 'qpfw_pricevar_' . $variation_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'qpfw_pricevar_' . $variation_id ] ) ) : '';
						$field_type   = 'price';

						// Gets variation ID in quantity input.
						$option_qty_value = 0;
					if ( isset( $_POST['qpfw_variation_id'][ $key ] ) ) {
						$option_qty_value = (int) $variation_id;
						$variation_id     = (int) $_POST['qpfw_variation_id'][ $key ];
					}

					// Check if selection changed - if so, clear all subsequent steps.
					$prev_var_id = isset( $_SESSION[ $qpfw_session_key ][ $key ]['var']['id'] ) ? (int) $_SESSION[ $qpfw_session_key ][ $key ]['var']['id'] : 0;
				if ( $prev_var_id > 0 && $prev_var_id !== $variation_id ) {
						// Selection changed, clear all subsequent steps from session.
					foreach ( array_keys( $_SESSION[ $qpfw_session_key ] ) as $step_key ) {
						$step_key = (int) $step_key;
						if ( $step_key > (int) $key ) {
								unset( $_SESSION[ $qpfw_session_key ][ $step_key ] );
								// Also clear user meta for logged in users.
								if ( ! empty( $user_id ) ) {
									delete_user_meta( $user_id, 'qpfw_phase_' . $step_key );
								}
							}
						}
					}

					if ( ! empty( $user_id ) ) {
						$phase_param['var']      = $variation_id;
						$phase_param['pricevar'] = $price_var ? $price_var : '';
						update_user_meta( $user_id, 'qpfw_phase_' . $key, $phase_param );
					}
					if ( empty( $option_qty_value ) ) {
						$price = CALC::get_price_variation( $variation_id, $price_var );
					} else {
						$price      = $option_qty_value;
						$field_type = 'qty';
					}

					$variation_title = get_the_title( $variation_id );
					if ( $price_var ) {
						$variation_title .= ' [' . $price_var . ']';
					}

				$safe_key = (int) $key;
				$_SESSION[ $qpfw_session_key ][ $safe_key ]['phase']['id']   = $phase_id;
				$_SESSION[ $qpfw_session_key ][ $safe_key ]['phase']['name'] = sanitize_text_field( $phase_title );
				$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['id']     = $variation_id;
				$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['name']   = sanitize_text_field( $variation_title );
				$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['type']      = $field_type;
				$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['price_var'] = $price_var;
				if ( $option_name ) {
					$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['name'] .= ' [' . sanitize_text_field( $option_name ) . ']';
				}
				$_SESSION[ $qpfw_session_key ][ $key ]['var']['price'] = $price;

				// Save custom input value if exists.
				if ( isset( $_POST['qpfw_custom_input'][ $key ][ $variation_id ] ) ) {
					$custom_input_value = sanitize_textarea_field( wp_unslash( $_POST['qpfw_custom_input'][ $key ][ $variation_id ] ) );
					if ( ! isset( $_SESSION[ $qpfw_session_key ][ $key ]['custom_input'] ) ) {
						$_SESSION[ $qpfw_session_key ][ $key ]['custom_input'] = array();
					}
					$_SESSION[ $qpfw_session_key ][ $key ]['custom_input'][ $variation_id ] = $custom_input_value;
					// Also append custom input to variation name if not empty.
					if ( ! empty( $custom_input_value ) ) {
						$_SESSION[ $qpfw_session_key ][ $key ]['var']['name'] .= ' (' . sanitize_text_field( $custom_input_value ) . ')';
					}
				}
				}
			}
			if ( isset( $_SESSION[ $qpfw_session_key ] ) && is_array( $_SESSION[ $qpfw_session_key ] ) ) {
				$session_data = wp_unslash( $_SESSION[ $qpfw_session_key ] );
				ksort( $session_data, SORT_NUMERIC );
				$_SESSION[ $qpfw_session_key ] = $session_data;
			}
		} elseif ( isset( $_POST['qpfw_direct_input'] ) && 'next' === $_POST['submit'] ) {
				// Handle direct input when there are no variations.
				if ( ! isset( $_SESSION[ $qpfw_session_key ] ) || ! is_array( $_SESSION[ $qpfw_session_key ] ) ) {
					$_SESSION[ $qpfw_session_key ] = array();
				}

				// Save direct input values.
				$direct_input_data = wp_unslash( $_POST['qpfw_direct_input'] );
				foreach ( $direct_input_data as $key => $direct_input_value ) {
					$safe_key   = (int) $key;
					// Get phase info to determine input type.
					$phase_id   = isset( $phases[ ( $safe_key - 1 ) ] ) ? $phases[ ( $safe_key - 1 ) ] : 0;
					$input_type = get_post_meta( $phase_id, 'qpfw_direct_input_type', true );
					if ( empty( $input_type ) ) {
						$input_type = 'textarea';
					}

					// Sanitize based on input type.
					if ( 'number' === $input_type ) {
						$direct_input_value = sanitize_text_field( wp_unslash( $direct_input_value ) );
						// For number inputs, allow 0 as valid value.
						$is_valid = ( '' !== $direct_input_value && null !== $direct_input_value );
					} else {
						$direct_input_value = sanitize_textarea_field( wp_unslash( $direct_input_value ) );
						// For text/textarea, empty string is not valid.
						$is_valid = ! empty( $direct_input_value );
					}

					if ( $is_valid ) {
						$phase_title = sanitize_text_field( get_the_title( $phase_id ) );

						if ( ! isset( $_SESSION[ $qpfw_session_key ][ $safe_key ] ) ) {
							$_SESSION[ $qpfw_session_key ][ $safe_key ] = array();
						}

						$_SESSION[ $qpfw_session_key ][ $safe_key ]['phase']['id']   = $phase_id;
						$_SESSION[ $qpfw_session_key ][ $safe_key ]['phase']['name'] = $phase_title;
						$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['id']     = 0;
						$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['name']   = $direct_input_value;
						$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['type']   = 'direct_input';
						$_SESSION[ $qpfw_session_key ][ $safe_key ]['var']['price']  = 0;
						$_SESSION[ $qpfw_session_key ][ $safe_key ]['direct_input']  = $direct_input_value;
					}
				}

				// Sort session data.
				if ( isset( $_SESSION[ $qpfw_session_key ] ) && is_array( $_SESSION[ $qpfw_session_key ] ) ) {
					$session_data = wp_unslash( $_SESSION[ $qpfw_session_key ] );
					ksort( $session_data, SORT_NUMERIC );
					$_SESSION[ $qpfw_session_key ] = $session_data;
				}
			}
		} elseif ( isset( $_GET['phase'] ) ) {
			$cstep = (int) $_GET['phase'];
		}

		// Support contact buttons - provided by Pro via action hook.
		do_action( 'qpfw_configurator_support_buttons', $phase_pid );

		if ( ! defined( 'DOING_AJAX' ) ) {
			?>
			<div class="page-configurator <?php echo 'page-configurator-' . esc_attr( $template ); ?>">
			<?php
		} // End if ! defined( 'DOING_AJAX' ).

		if ( empty( $phases ) ) {
			?>
			<div class="error"><?php esc_html_e( 'No Phases Available', 'quote-product-flow' ); ?></div>
			</div>
			<?php
		}

		// Show phase note if exists and we're not in calculate step - BEFORE wizard menu.
		if ( 'calculate' !== $cstep ) {
			$phase_id   = isset( $phases[ ( (int) $cstep - 1 ) ] ) ? $phases[ ( (int) $cstep - 1 ) ] : 0;
			$phase_note = get_post_meta( $phase_id, 'qpfw_phase_note', true );
			if ( ! empty( $phase_note ) ) :
				?>
				<div class="phase_note_top">
					<?php echo wp_kses_post( $phase_note ); ?>
				</div>
				<?php
			endif;
		}

		if ( 'wizard' === $template ) {
			SHOW::wizard_phases( $phases, $cstep );
		}
		?>
		<?php $parent_phase_slug = sanitize_title( get_the_title( $parent_phase ) ); ?>
		<div class="phase_detail product-<?php echo esc_html( $parent_phase_slug ); ?>">
			<form action="" method="post" name="configurator-form" id="configurator-form" data-template="<?php echo esc_html( $template ); ?>">
			<input type="hidden" name="qpfw_parent_phase" value="<?php echo (int) $phase_pid; ?>">
			<?php
			wp_nonce_field( 'qpfw_template_wizard_action', 'qpfw_template_wizard_nonce' );
			if ( 'calculate' !== $cstep ) {
				$phase_id    = isset( $phases[ ( (int) $cstep - 1 ) ] ) ? $phases[ ( (int) $cstep - 1 ) ] : 0;
				$phase_title = get_the_title( $phase_id );
				$phase_slug  = sanitize_title( get_the_title( $phase_id ) );
				?>
				<div class="configurator-<?php echo 'wizard' === $template ? 'left' : 'right'; ?>">
					<div class="phase_title"><?php echo esc_html( $phase_title ); ?></div>
					<div class="phase_variations phase-<?php echo esc_html( $phase_slug ); ?>">
					<?php
					$prev_variations_ids = array();
					if ( isset( $_SESSION[ $qpfw_session_key ] ) && is_array( $_SESSION[ $qpfw_session_key ] ) ) {
						$session_data = wp_unslash( $_SESSION[ $qpfw_session_key ] );
						foreach ( $session_data as $step_key => $prev_var ) {
							if ( isset( $prev_var['var']['id'] ) ) {
								$var_id = (int) $prev_var['var']['id'];
								// Use step_key - 1 as index to match with 0-based dependency checking.
								$prev_variations_ids[ (int) $step_key - 1 ] = $var_id;
							}
						}
					}

					$variations_posts = get_posts(
						array(
							'numberposts' => -1,
							'post_type'   => 'qpfw_variation',
							'meta_key'    => 'qpfw_phase',
							'meta_value'  => $phase_id,
							'orderby'     => 'title',
							'order'       => 'asc',
						)
					);
					// Preload posts, meta and terms into cache to avoid N+1 queries.
					if ( ! empty( $variations_posts ) ) {
						$variations = array_map( 'intval', wp_list_pluck( $variations_posts, 'ID' ) );
						// Prime WP_Post object cache so get_the_title() hits no DB.
						foreach ( $variations_posts as $vpost ) {
							wp_cache_set( $vpost->ID, $vpost, 'posts' );
						}
						// Preload all post meta in one query.
						update_postmeta_cache( $variations );
						// Preload taxonomy terms in batch so wp_get_post_terms() hits no DB.
						update_object_term_cache( $variations, 'variation' );
					} else {
						$variations = array();
					}
					if ( ! empty( $variations ) && isset( $_SESSION[ $qpfw_session_key ] ) ) {
						$variations_depends = array();
						$variations_question_depends = array();

						foreach ( $variations as $variation_id ) {
							// Get variation dependencies.
							$depends = get_post_meta( $variation_id, 'qpfw_depends', true );
							if ( ! empty( $depends ) ) {
								$variations_depends[ $variation_id ] = array();
								foreach ( $depends as $depend ) {
									$expanded = CALC::expand_depend_row( $depend['qpfw_depvar'], $phases_order );
									foreach ( $expanded as $order => $ids ) {
										foreach ( $ids as $depend_id ) {
											$variations_depends[ $variation_id ][ $order ][] = $depend_id;
										}
									}
								}
							}

							// Get question dependencies.
							$question_depends = get_post_meta( $variation_id, 'qpfw_question_depends', true );
							if ( ! empty( $question_depends ) && is_array( $question_depends ) ) {
								$variations_question_depends[ $variation_id ] = $question_depends;
							}
						}

						// Get all question answers from global session.
						$all_question_answers = isset( $_SESSION['qpfw_questions'] ) && is_array( $_SESSION['qpfw_questions'] )
							? array_map( 'sanitize_text_field', wp_unslash( $_SESSION['qpfw_questions'] ) )
							: array();

						$variations = array_filter(
							$variations,
							function ( $variation_id ) use ( $prev_variations_ids, $variations_depends, $variations_question_depends, $all_question_answers, $cstep ) {
								// Check variation dependencies.
								if ( isset( $variations_depends[ $variation_id ] ) ) {
									$depends_ids = $variations_depends[ $variation_id ];
									for ( $i = 0; $i < $cstep - 1; $i++ ) {
										if ( isset( $prev_variations_ids[ $i ] ) && isset( $depends_ids[ $i ] ) ) {
											if ( ! in_array( $prev_variations_ids[ $i ], $depends_ids[ $i ], true ) ) {
												return false;
											}
										}
									}
								}

								// Check question dependencies.
								if ( isset( $variations_question_depends[ $variation_id ] ) ) {
									foreach ( $variations_question_depends[ $variation_id ] as $question_depend ) {
										$question_key = isset( $question_depend['qpfw_question_key_ref'] ) ? $question_depend['qpfw_question_key_ref'] : '';
										$operator     = isset( $question_depend['qpfw_question_operator'] ) ? $question_depend['qpfw_question_operator'] : '>';
										$compare_value = isset( $question_depend['qpfw_question_value'] ) ? $question_depend['qpfw_question_value'] : '';

										if ( empty( $question_key ) || ! isset( $all_question_answers[ $question_key ] ) ) {
											continue;
										}

										$answer_value = $all_question_answers[ $question_key ];

										// Perform comparison.
										$condition_met = false;
										if ( is_numeric( $answer_value ) && is_numeric( $compare_value ) ) {
											$answer_value = (float) $answer_value;
											$compare_value = (float) $compare_value;

											switch ( $operator ) {
												case '>':
													$condition_met = $answer_value > $compare_value;
													break;
												case '>=':
													$condition_met = $answer_value >= $compare_value;
													break;
												case '<':
													$condition_met = $answer_value < $compare_value;
													break;
												case '<=':
													$condition_met = $answer_value <= $compare_value;
													break;
												case '=':
													$condition_met = $answer_value == $compare_value;
													break;
												case '!=':
													$condition_met = $answer_value != $compare_value;
													break;
											}
										} else {
											// String comparison.
											switch ( $operator ) {
												case '=':
													$condition_met = $answer_value === $compare_value;
													break;
												case '!=':
													$condition_met = $answer_value !== $compare_value;
													break;
											}
										}

										if ( ! $condition_met ) {
											return false;
										}
									}
								}

								return true;
							}
						);

							// Order variations per section.
							$variations_section = array();
							foreach ( $variations as $variation_id ) {
								$term_list            = (array) wp_get_post_terms(
									$variation_id,
									'qpfw_variation_tag',
									array(
										'fields' => 'all',
									)
								);
								$variation_title       = get_the_title( $variation_id );
								$variations_section[] = array(
									'id'      => $variation_id,
									'section' => isset( $term_list[0]->name ) ? $term_list[0]->name : '',
									'title'   => $variation_title,
									'order'   => CALC::get_variation_display_order( $variation_id, $variation_title ),
								);
							}

							// Order by sections and title.
							foreach ( $variations_section as $key => $val ) {
									$temp_arr['section'][ $key ] = $val['section'];
									$temp_arr['order'][ $key ]   = $val['order'];
									$temp_arr['title'][ $key ]   = $val['title'];
							}
							// Sort by section asc, then display order asc, then title asc as a tie-break.
							if ( ! empty( $temp_arr['section'] ) && ! empty( $temp_arr['order'] ) && ! empty( $temp_arr['title'] ) ) {
								array_multisort( $temp_arr['section'], SORT_ASC, $temp_arr['order'], SORT_ASC, $temp_arr['title'], SORT_ASC, $variations_section );
				}

					// Check if phase allows multiple selections.
					$allow_multiple = get_post_meta( $phase_id, 'qpfw_allow_multiple_selections', true );

					// Show public.
					$selected_var  = 0;
					$selected_vars = array();

					// Check if there are any non-question variations to auto-select.
					$non_question_variations = array();
					foreach ( $variations as $var_id ) {
						$is_question = get_post_meta( $var_id, 'qpfw_is_question', true );
						if ( ! $is_question ) {
							$non_question_variations[] = $var_id;
						}
					}

					if ( $allow_multiple ) {
						// Multiple selection mode.
						if (
							isset( $_SESSION[ $qpfw_session_key ] ) &&
							is_array( $_SESSION[ $qpfw_session_key ] ) &&
							isset( $_SESSION[ $qpfw_session_key ][ $cstep ]['vars'] ) &&
							is_array( $_SESSION[ $qpfw_session_key ][ $cstep ]['vars'] )
						) {
									$selected_vars = array_map( 'intval', $_SESSION[ $qpfw_session_key ][ $cstep ]['vars'] );
							// Filter to only include valid variations.
							$selected_vars = array_intersect( $selected_vars, $variations );
						}
					} elseif (
						// Single selection mode.
						isset( $_SESSION[ $qpfw_session_key ] ) &&
						is_array( $_SESSION[ $qpfw_session_key ] ) &&
						isset( $_SESSION[ $qpfw_session_key ][ $cstep ]['var']['id'] ) &&
							in_array( (int) $_SESSION[ $qpfw_session_key ][ $cstep ]['var']['id'], $variations, true )
					) {
						$selected_var = isset( $_SESSION[ $qpfw_session_key ][ $cstep ]['var']['id'] ) ? (int) $_SESSION[ $qpfw_session_key ][ $cstep ]['var']['id'] : 0;
					} elseif ( ! empty( $non_question_variations ) ) {
						// Only auto-select if there are non-question variations.
						$selected_var = $non_question_variations[0];
					} else {
						// Auto-select first variation if no session data and no questions.
						$first_key = current( array_keys( $variations ) );
						if ( false !== $first_key ) {
							$selected_var = $variations[ $first_key ];
						}
					}
					// If all variations are questions, $selected_var remains 0 (no auto-selection).

					if ( ! empty( $variations_section ) ) {
						$legacy_style = (bool) get_post_meta( $phase_id, 'qpfw_legacy_style', true );
						if ( $allow_multiple ) {
							SHOW::variations_content( $variations_section, $selected_vars, $cstep, $template, true, $legacy_style );
						} else {
							SHOW::variations_content( $variations_section, $selected_var, $cstep, $template, false, $legacy_style );
						}
					}
					} else {
							// Check if phase has direct input enabled.
							$show_direct_input = get_post_meta( $phase_id, 'qpfw_show_direct_input', true );
							if ( $show_direct_input ) {
								// Get input type (textarea, text, or number).
								$input_type = get_post_meta( $phase_id, 'qpfw_direct_input_type', true );
								if ( empty( $input_type ) ) {
									$input_type = 'textarea'; // Default to textarea.
								}

								// Show direct input field.
								$saved_value = '';
								if ( isset( $_SESSION[ $qpfw_session_key ][ $cstep ]['direct_input'] ) ) {
									if ( 'number' === $input_type ) {
										$saved_value = sanitize_text_field( $_SESSION[ $qpfw_session_key ][ $cstep ]['direct_input'] );
									} else {
										$saved_value = sanitize_textarea_field( $_SESSION[ $qpfw_session_key ][ $cstep ]['direct_input'] );
									}
								}
								// Default value for number input is 0.
								if ( 'number' === $input_type && empty( $saved_value ) ) {
									$saved_value = '0';
								}
								?>
								<div class="qpfw-direct-input-wrapper">
									<?php if ( 'textarea' === $input_type ) { ?>
										<textarea
											class="qpfw-direct-input qpfw-direct-input-textarea"
											name="qpfw_direct_input[<?php echo esc_attr( $cstep ); ?>]"
											rows="5"
											placeholder="<?php echo esc_attr__( 'Write here...', 'quote-product-flow' ); ?>"
										><?php echo esc_textarea( $saved_value ); ?></textarea>
									<?php } elseif ( 'number' === $input_type ) { ?>
										<div class="qpfw-number-input-wrapper">
											<button type="button" class="qpfw-number-btn qpfw-number-decrease" data-step="<?php echo esc_attr( $cstep ); ?>" aria-label="<?php echo esc_attr__( 'Decrease', 'quote-product-flow' ); ?>">
												<span class="qpfw-number-arrow">←</span>
											</button>
											<input
												type="number"
												class="qpfw-direct-input qpfw-direct-input-number"
												name="qpfw_direct_input[<?php echo esc_attr( $cstep ); ?>]"
												value="<?php echo esc_attr( $saved_value ? $saved_value : '0' ); ?>"
												min="0"
												step="1"
												data-step="<?php echo esc_attr( $cstep ); ?>"
											/>
											<button type="button" class="qpfw-number-btn qpfw-number-increase" data-step="<?php echo esc_attr( $cstep ); ?>" aria-label="<?php echo esc_attr__( 'Increase', 'quote-product-flow' ); ?>">
												<span class="qpfw-number-arrow">→</span>
											</button>
										</div>
									<?php } else { // Text input. ?>
										<input
											type="text"
											class="qpfw-direct-input qpfw-direct-input-text"
											name="qpfw_direct_input[<?php echo esc_attr( $cstep ); ?>]"
											value="<?php echo esc_attr( $saved_value ); ?>"
											placeholder="<?php echo esc_attr__( 'Write here...', 'quote-product-flow' ); ?>"
										/>
									<?php } ?>
								</div>
								<?php
							} else {
								?>
								<div class="error"><?php esc_html_e( 'No Variations Available', 'quote-product-flow' ); ?></div>
								<?php
							}
						}
						?>
					</div>
					<?php
					// Variations Description.
					if ( ! empty( $variations ) ) {
						$index_var = 1;
						echo '<div class="phase_descvar">';
						foreach ( $variations as $variation_id ) {
							$descvar = get_post_meta( $variation_id, 'qpfw_descvar', true );
							if ( ! empty( $descvar ) ) {
								echo '<div class="descvar descvar_' . esc_attr( $variation_id );
								if ( $index_var > 1 ) {
									echo ' hidden';
								} else {
									echo ' actived';
								}
								echo '">';
								echo wp_kses_post( wpautop( $descvar ) );
								echo '</div>';
							}
							++$index_var;
						}
						echo '</div>';
					}
					?>
					<div class="phase_content">
						<?php
						$phase_post = get_post( $phase_id );
						if ( ! empty( $phase_post->post_content ) ) {
							echo wp_kses_post( $phase_post->post_content );
						}
						?>
					</div>
					<?php
					if ( 'vertical' === $template ) {
						SHOW::action_buttons( $phases, $cstep, $template );
						SHOW::calculation_summary( $qpfw_session_key, $cstep, $phases );
					}
					?>
				</div>
				<div class="configurator-<?php echo 'wizard' === $template ? 'right' : 'left'; ?>">
				<?php
			} //cStep!=calculate

			if ( 'calculate' !== $cstep && 'wizard' === $template ) {
				wp_add_inline_script(
					'qpfw-public',
					'(function(){function moveConfiguratorAction(){if(typeof jQuery!=="undefined"){jQuery(document).ready(function($){$(".configurator_form_action").insertAfter(".product_preview");});}else{setTimeout(moveConfiguratorAction,100);}}moveConfiguratorAction();})();'
				);
			} elseif ( 'calculate' === $cstep && 'wizard' === $template ) {
				wp_add_inline_script(
					'qpfw-public',
					'(function(){function moveConfiguratorAction(){if(typeof jQuery!=="undefined"){jQuery(document).ready(function($){var $c=$(".qpfw-calculate-container");var $act=$(".configurator_form_action");var $pv=$c.find(".product_preview");if($pv.length){$act.insertBefore($pv);}else{$act.prependTo($c);}});} else{setTimeout(moveConfiguratorAction,100);}}moveConfiguratorAction();})();'
				);
			}
			$qpfw_skip_calculate_empty_preview = ( 'calculate' === $cstep && ! CALC::calculate_has_product_preview_image( $qpfw_session_key ) );
			?>
			<?php if ( 'calculate' === $cstep ) { ?>
			<div class="qpfw-calculate-container<?php echo $qpfw_skip_calculate_empty_preview ? ' qpfw-calculate-no-preview' : ''; ?>">
			<?php } ?>
			<?php if ( ! $qpfw_skip_calculate_empty_preview ) { ?>
			<div class="product_preview
			<?php
			if ( 'calculate' === $cstep ) {
				echo ' wrap-left'; }
			?>
			">
				<div class="image-wrap">
					<?php
					$ss_var = '';
					if ( ! empty( $_SESSION[ $qpfw_session_key ] ) ) {
						$to = (int) $cstep;
						if ( 'calculate' === $cstep ) {
							$to = count( $_SESSION[ $qpfw_session_key ] ) + 1;
						}
						for ( $i = 1; $i < $to; $i++ ) {
							$imgprodid  = '';
							$imgprodurl = '';
							if ( isset( $_SESSION[ $qpfw_session_key ][ $i ] ) && isset( $_SESSION[ $qpfw_session_key ][ $i ]['var']['id'] ) ) {
								$ss_var       = (int) $_SESSION[ $qpfw_session_key ][ $i ]['var']['id'];
								$imgprodgroup = get_post_meta( $ss_var, 'qpfw_imgprodgroup', true );
								if ( ! empty( $imgprodgroup ) ) {
									foreach ( $imgprodgroup as $deps ) {
										if ( isset( $deps['qpfw_depvarimgprod'] ) && ! empty( $deps['qpfw_depvarimgprod'] ) && isset( $deps['qpfw_imgprod'] ) ) {
											$prev_var = array();
											foreach ( $deps['qpfw_depvarimgprod'] as $depvarimgprod ) {
												$imgprod_arr = explode( '|', $depvarimgprod );
												if ( ! empty( $imgprod_arr[0] ) && ! empty( $imgprod_arr[1] ) ) {
													$prev_var[ (int) $imgprod_arr[0] ][] = $imgprod_arr[1];
												}
											}
											if ( ! empty( $_SESSION[ $qpfw_session_key ] ) && ! empty( $prev_var ) ) {
												foreach ( $prev_var as $s_phase_key => $s_variations ) {
													if ( isset( $prev_var[ $s_phase_key ] ) &&
													isset( $_SESSION[ $qpfw_session_key ][ $s_phase_key ]['var']['id'] ) &&
													in_array( $_SESSION[ $qpfw_session_key ][ $s_phase_key ]['var']['id'], $prev_var[ $s_phase_key ], true ) ) {
														$imgprodid = $deps['qpfw_imgprod'][0];
													} else {
														$imgprodid = '';
														break;
													}
												}
											}
										} elseif ( ( ! isset( $deps['qpfw_depvarimgprod'] ) || empty( $deps['qpfw_depvarimgprod'] ) ) && isset( $deps['qpfw_imgprod'] ) ) {
											$imgprodid = $deps['qpfw_imgprod'][0];
											break;
										}
										if ( $imgprodid ) {
											break;
										}
									}
								}
								if ( isset( $imgprodid ) && $imgprodid ) {
									$imgprodurl = wp_get_attachment_image_src( $imgprodid, 'full', true );
								}
								if ( $imgprodurl ) {
									$addclass                  = '';
									$variations_images_flipped = get_option( 'variations_images_flipped' );
									if ( ! empty( $variations_images_flipped ) ) {
										for ( $j = 1; $j <= $to; $j++ ) {
											if ( isset( $_SESSION[ $qpfw_session_key ][ $j ]['var']['id'] ) && in_array( $_SESSION[ $qpfw_session_key ][ $j ]['var']['id'], $variations_images_flipped, true ) ) {
												$addclass = 'flipped';
											}
										}
									}
									?>
									<img phaseid="<?php echo (int) $i; ?>" src="<?php echo esc_url( $imgprodurl[0] ); ?>" class="<?php echo esc_html( $addclass ); ?>" alt="product image"/>
									<?php
								}
							}
						}
					}
					$session_var_for_image = isset( $_SESSION[ $qpfw_session_key ] ) && is_array( $_SESSION[ $qpfw_session_key ] ) ? wp_unslash( $_SESSION[ $qpfw_session_key ] ) : array();
					$imgprodurl            = ! empty( $ss_var ) ? CALC::get_image_variation_url( $session_var_for_image, $ss_var ) : '';

					if ( $imgprodurl ) {
						$variations_images_flipped = get_option( 'variations_images_flipped' );
						$addclass                  = '';
						if ( ! empty( $variations_images_flipped ) && in_array( $ss_var, $variations_images_flipped, true ) ) {
							$addclass = 'flipped';
						}
						?>
						<img phaseid="<?php echo (int) $cstep; ?>" src="<?php echo esc_url( $imgprodurl ); ?>" class="<?php echo esc_html( $addclass ); ?>" alt="product image"/>
						<?php
					}
					?>
				</div>
				<div class="status_loader product_preview_status fixed hidden"></div>
			</div>
			<?php } ?>
			<?php
			if ( 'wizard' === $template ) {
				SHOW::action_buttons( $phases, $cstep );
			}
			if ( 'wizard' === $template || ( 'vertical' === $template && 'calculate' === $cstep ) ) {
				SHOW::calculation_summary( $qpfw_session_key, $cstep, $phases );
			}
			if ( 'calculate' === $cstep ) {
			?>
		</div><!-- .qpfw-calculate-container -->
		<?php } ?>
		<?php
		if ( 'calculate' === $cstep ) {
			?>
			<div class="configurator_result_share">
					<?php
					$session_type = isset( $_SESSION['qpfw_output']['type'] ) ? sanitize_text_field( wp_unslash( $_SESSION['qpfw_output']['type'] ) ) : '';
					if ( ! isset( $_SESSION['qpfw_output'] ) || 'success' !== $session_type ) {
						?>
						<h2><?php esc_html_e( 'Share Configuration', 'quote-product-flow' ); ?></h2>
						<div class="share_buttons">
							<button type="button" class="btn btn-share btn-whatsapp" id="qpfw-share-whatsapp">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 5px;">
									<path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
								</svg>
								<?php esc_html_e( 'Share via WhatsApp', 'quote-product-flow' ); ?>
							</button>
							<button type="button" class="btn btn-share btn-email" id="qpfw-share-email">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 5px;">
									<path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
								</svg>
								<?php esc_html_e( 'Share via Email', 'quote-product-flow' ); ?>
							</button>
						</div>
						<h2 style="margin-top: 30px;"><?php esc_html_e( 'Client Details', 'quote-product-flow' ); ?></h2>
						<div class="email_submit_fields">
							<input type="hidden" name="qpfw_session_key" value="<?php echo esc_attr( $qpfw_session_key ); ?>">
							<input type="hidden" name="qpfw_parent_phase" value="<?php echo (int) $phase_pid; ?>">
							<input type="text" name="email_field" placeholder="<?php esc_html_e( 'separate multiple email by comma', 'quote-product-flow' ); ?>"/>
							<input type="text" name="name_field" placeholder="<?php esc_html_e( 'Your name', 'quote-product-flow' ); ?>"/>
							<input type="text" name="phone_field" placeholder="<?php esc_html_e( 'Phone number', 'quote-product-flow' ); ?>"/>
							<input type="text" name="city_field" placeholder="<?php esc_html_e( 'Your City', 'quote-product-flow' ); ?>"/>
							<input type="text" name="state_field" placeholder="<?php esc_html_e( 'State', 'quote-product-flow' ); ?>"/>
							<textarea name="comments_field" placeholder="<?php esc_html_e( 'Your comments', 'quote-product-flow' ); ?>"></textarea>
							<?php
							$show_button_email = get_option( 'qpfw_budget_show_button_email' );
							if ( 'no' !== $show_button_email ) {
								?>
								<button type="submit" name="submit" class="btn btn-submit" value="email_send"><?php esc_html_e( 'Send', 'quote-product-flow' ); ?></button>
								<?php
							}
							$show_button_pdf = get_option( 'qpfw_budget_show_button_pdf' );
							if ( 'no' !== $show_button_pdf ) {
								?>
								<button type="submit" name="submit" class="btn btn-submit" value="generate_pdf"><?php esc_html_e( 'Generate Budget', 'quote-product-flow' ); ?></button>
							<?php } ?>
						</div>
						<?php
					}
					if ( isset( $_SESSION['qpfw_output']['response'] ) ) {
						?>
						<div class="result_submit_action <?php echo esc_html( $session_type ); ?>">
							<?php
							echo wp_kses_post( $_SESSION['qpfw_output']['response'] );
							?>
						</div>
						<?php
						unset( $_SESSION['qpfw_output'] );
					}
					?>
				</div>
				<?php
			}
			if ( 'calculate' !== $cstep ) {
				// Banner.
				do_action( 'qpfw_banner_after_setup' );
				?>
				</div>
				<?php
			}//$cstep != 'calculate'
			?>
			</form>
		</div>
		<div class="status_loader phase_detail_loader fixed hidden"></div>

		<?php
		if ( ! defined( 'DOING_AJAX' ) ) {
			?>
			</div>
			<?php
		}
	}

	/**
	 * Variation IDs visible on a step (dependency filters match frontend render).
	 *
	 * @param int    $phase_id          Phase post ID.
	 * @param int    $step_number       1-based step.
	 * @param string $qpfw_session_key Session key.
	 * @param array  $phases            Phase IDs in order.
	 * @param array  $phases_order      menu_order per phase (parallel).
	 * @return array<int>
	 */
	public static function get_visible_variation_ids_for_step( $phase_id, $step_number, $qpfw_session_key, $phases, $phases_order ) {
		if ( empty( $phase_id ) || ! isset( $_SESSION[ $qpfw_session_key ] ) || ! is_array( $_SESSION[ $qpfw_session_key ] ) ) {
			return array();
		}
		$variations = get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'qpfw_variation',
				'meta_key'    => 'qpfw_phase',
				'meta_value'  => (int) $phase_id,
				'fields'      => 'ids',
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
		if ( empty( $variations ) ) {
			return array();
		}
		$session_data = wp_unslash( $_SESSION[ $qpfw_session_key ] );
		$prev_variations_ids = array();
		foreach ( $session_data as $step_key => $prev_var ) {
			if ( isset( $prev_var['var']['id'] ) ) {
				$prev_variations_ids[ (int) $step_key - 1 ] = (int) $prev_var['var']['id'];
			}
		}
		$variations_depends          = array();
		$variations_question_depends = array();
		foreach ( $variations as $variation_id ) {
			$depends = get_post_meta( $variation_id, 'qpfw_depends', true );
			if ( ! empty( $depends ) ) {
				$variations_depends[ $variation_id ] = array();
				foreach ( $depends as $depend ) {
					$expanded = CALC::expand_depend_row( $depend['qpfw_depvar'], $phases_order );
					foreach ( $expanded as $order => $ids ) {
						foreach ( $ids as $depend_id ) {
							$variations_depends[ $variation_id ][ $order ][] = $depend_id;
						}
					}
				}
			}
			$question_depends = get_post_meta( $variation_id, 'qpfw_question_depends', true );
			if ( ! empty( $question_depends ) && is_array( $question_depends ) ) {
				$variations_question_depends[ $variation_id ] = $question_depends;
			}
		}
		$all_question_answers = isset( $_SESSION['qpfw_questions'] ) ? $_SESSION['qpfw_questions'] : array();
		$cstep                = (int) $step_number;
		$filtered             = array_filter(
			$variations,
			function ( $variation_id ) use ( $prev_variations_ids, $variations_depends, $variations_question_depends, $all_question_answers, $cstep ) {
				if ( isset( $variations_depends[ $variation_id ] ) ) {
					$depends_ids = $variations_depends[ $variation_id ];
					for ( $i = 0; $i < $cstep - 1; $i++ ) {
						if ( isset( $prev_variations_ids[ $i ] ) && isset( $depends_ids[ $i ] ) ) {
							if ( ! in_array( $prev_variations_ids[ $i ], $depends_ids[ $i ], true ) ) {
								return false;
							}
						}
					}
				}
				if ( isset( $variations_question_depends[ $variation_id ] ) ) {
					foreach ( $variations_question_depends[ $variation_id ] as $question_depend ) {
						$question_key  = isset( $question_depend['qpfw_question_key_ref'] ) ? $question_depend['qpfw_question_key_ref'] : '';
						$operator      = isset( $question_depend['qpfw_question_operator'] ) ? $question_depend['qpfw_question_operator'] : '>';
						$compare_value = isset( $question_depend['qpfw_question_value'] ) ? $question_depend['qpfw_question_value'] : '';
						if ( empty( $question_key ) || ! isset( $all_question_answers[ $question_key ] ) ) {
							continue;
						}
						$answer_value  = $all_question_answers[ $question_key ];
						$condition_met = false;
						if ( is_numeric( $answer_value ) && is_numeric( $compare_value ) ) {
							$answer_value  = (float) $answer_value;
							$compare_value = (float) $compare_value;
							switch ( $operator ) {
								case '>':
									$condition_met = $answer_value > $compare_value;
									break;
								case '>=':
									$condition_met = $answer_value >= $compare_value;
									break;
								case '<':
									$condition_met = $answer_value < $compare_value;
									break;
								case '<=':
									$condition_met = $answer_value <= $compare_value;
									break;
								case '=':
									$condition_met = $answer_value == $compare_value;
									break;
								case '!=':
									$condition_met = $answer_value != $compare_value;
									break;
							}
						} else {
							switch ( $operator ) {
								case '=':
									$condition_met = $answer_value === $compare_value;
									break;
								case '!=':
									$condition_met = $answer_value !== $compare_value;
									break;
							}
						}
						if ( ! $condition_met ) {
							return false;
						}
					}
				}
				return true;
			}
		);
		return array_values( array_map( 'intval', $filtered ) );
	}
}
