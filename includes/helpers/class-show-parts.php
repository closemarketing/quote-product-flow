<?php
/**
 * Class for show parts.
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

namespace Close\PBC\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Helper Calculate PBC.
 *
 * All helpers calculations.
 *
 * @since 1.1
 */
class SHOW {
	/**
	 * Variations sections.
	 *
	 * @param array  $variations_section Variations sections.
	 * @param int    $s_var Selected variation.
	 * @param int    $cstep Current step.
	 * @param string $template Template.
	 * @param bool   $allow_multiple Allow multiple selections.
	 *
	 * @return void
	 */
	public static function variations_content( $variations_section, $s_var, $cstep, $template = 'wizard', $allow_multiple = false ) {
		$actual_variation_tag  = '';
		$variations_with_input = array(); // Store variations that need custom input.

		// Get selected variations for multiple selection mode.
		$selected_vars = array();
		if ( $allow_multiple ) {
			if ( is_array( $s_var ) ) {
				$selected_vars = $s_var;
			} elseif ( ! empty( $s_var ) ) {
				$selected_vars = array( $s_var );
			}
		}

		if ( 'wizard' === $template ) {
			echo '<ul>';
		} elseif ( $allow_multiple ) {
				echo '<div class="pbc-multiple-selections">';
			} else {
			echo '<select name="pbc_variation[' . esc_attr( $cstep ) . ']" class="pbc_variation">';
		}

		foreach ( $variations_section as $variation_data ) {
			$variation_id = (int) $variation_data['id'];
			$field_type   = get_post_meta( $variation_id, 'pbc_field_type', true );
			$is_question  = get_post_meta( $variation_id, 'pbc_is_question', true );

			if ( 'wizard' === $template ) {
				if ( $actual_variation_tag !== $variation_data['section'] ) {
					echo '</ul><h2>' . esc_html( $variation_data['section'] ) . '</h2><ul>';
					$actual_variation_tag = $variation_data['section'];
				}
				$is_choice_row = ! $is_question && empty( $field_type );
				?>
				<li class="variation_list <?php echo $is_question ? 'is-question' : ''; ?><?php echo $is_choice_row ? ' pbc-choice-row' : ''; ?>">
					<label class="<?php echo $is_choice_row ? 'pbc-choice-label' : ''; ?>">
						<?php
						$imgicon = get_post_meta( $variation_id, 'pbc_imgicon', true );
						if ( $imgicon && ! $is_choice_row ) {
							echo '<div class="variation_img">';
							echo wp_get_attachment_image( $imgicon, 'pbc_icon', false );
							echo '</div>';
						}

		// Check if this is a question type variation.
		if ( $is_question ) {
			$question_key         = get_post_meta( $variation_id, 'pbc_question_key', true );
			$question_input_type  = get_post_meta( $variation_id, 'pbc_question_input_type', true );
			$question_placeholder = get_post_meta( $variation_id, 'pbc_question_placeholder', true );
			$question_required    = get_post_meta( $variation_id, 'pbc_question_required', true );

			// Get saved answer from session if exists.
			$saved_answer = '';
			if ( isset( $_SESSION['pbc_questions'][ $question_key ] ) ) {
				$saved_answer = $_SESSION['pbc_questions'][ $question_key ];
			}

			$input_type  = 'number' === $question_input_type ? 'number' : 'text';
			$is_required = ! empty( $question_required ) && '1' === $question_required;

			echo '<div class="variation-question-label">';
			echo esc_html( $variation_data['title'] );
			if ( $is_required ) {
				echo ' <span class="required-asterisk" style="color: #d32f2f;">*</span>';
			}
			echo '</div>';
			?>
			<input
				type="<?php echo esc_attr( $input_type ); ?>"
				class="pbc_question_input"
				name="pbc_question[<?php echo esc_attr( $question_key ); ?>]"
				id="pbc_question_<?php echo esc_attr( $question_key ); ?>"
				value="<?php echo esc_attr( $saved_answer ); ?>"
				placeholder="<?php echo esc_attr( $question_placeholder ); ?>"
				data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
				data-step="<?php echo esc_attr( $cstep ); ?>"
				data-question-key="<?php echo esc_attr( $question_key ); ?>"
				<?php echo $is_required ? 'required="required"' : ''; ?>
				<?php echo 'number' === $input_type ? 'step="any"' : ''; ?>
			/>
			<input type="hidden" name="pbc_question_variation_id[<?php echo esc_attr( $question_key ); ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
			<?php
		} elseif ( empty( $field_type ) ) {
			if ( $allow_multiple ) {
				?>
				<input type="checkbox" class="pbc_variation pbc_variation_multiple pbc-option-native" name="pbc_variation[<?php echo esc_attr( $cstep ); ?>][]" value="<?php echo esc_attr( $variation_id ); ?>" <?php checked( in_array( $variation_id, $selected_vars, true ), true, true ); ?> />
				<span class="pbc-option-card">
					<?php
					if ( $imgicon ) {
						echo '<div class="variation_img">';
						echo wp_get_attachment_image( $imgicon, 'pbc_icon', false );
						echo '</div>';
					}
					?>
					<span class="pbc-option-title"><?php echo esc_html( $variation_data['title'] ); ?></span>
				</span>
				<?php
			} else {
				?>
				<input type="radio" class="pbc_variation pbc-option-native" name="pbc_variation[<?php echo esc_attr( $cstep ); ?>]" value="<?php echo esc_attr( $variation_id ); ?>" <?php checked( $variation_id, $s_var, true ); ?> />
				<span class="pbc-option-card">
					<?php
					if ( $imgicon ) {
						echo '<div class="variation_img">';
						echo wp_get_attachment_image( $imgicon, 'pbc_icon', false );
						echo '</div>';
					}
					?>
					<span class="pbc-option-title"><?php echo esc_html( $variation_data['title'] ); ?></span>
				</span>
				<?php
			}
						} elseif ( 'qty' === $field_type ) {
							$s_var = $s_var === $variation_id ? 1 : $s_var;
							?>
							<input type="number" class="pbc_variation" name="pbc_variation[<?php echo esc_attr( $cstep ); ?>]" value="<?php echo (int) $s_var; ?>" />
							<input type="hidden" name="pbc_variation_id[<?php echo esc_attr( $cstep ); ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
							<?php
							echo esc_html( $variation_data['title'] );
						}
						?>
					</label>
					<?php
					$pricegroup = get_post_meta( $variation_id, 'pbc_pricegroup', true );
					if ( ! empty( $pricegroup ) && isset( $pricegroup[0]['pbc_meaprice'] ) ) {
						?>
						<div class="pbc_pricevarwrap">
							<select class="pbc_pricevar" name="pbc_pricevar_<?php echo (int) $variation_id; ?>">
								<?php
								foreach ( $pricegroup as $key => $details ) {
									if ( ! empty( $details['pbc_meaprice'] ) && isset( $details['pbc_pricem'] ) ) {
										echo '<option value="' . esc_attr( $details['pbc_meaprice'] ) . '">';
										echo esc_html( $details['pbc_meaprice'] );
										echo '</option>';
									}
								}
								?>
							</select>
						</div>
						<?php
					}
					$pbc_descopt = get_post_meta( $variation_id, 'pbc_descopt', true );
					if ( $pbc_descopt ) {
						?>
						<p class="pbc_descopt"><?php echo wp_kses_post( wpautop( $pbc_descopt ) ); ?></p>
						<?php
					}
					// Check if this variation needs custom input and store it.
					$show_custom_input = get_post_meta( $variation_id, 'pbc_show_custom_input', true );
					if ( $show_custom_input ) {
						$variations_with_input[] = $variation_id;
					}
					?>
				</li>
				<?php
			} elseif ( 'vertical' === $template ) {
				// Check if this is a question type variation.
				if ( $is_question ) {
					$question_key         = get_post_meta( $variation_id, 'pbc_question_key', true );
					$question_input_type  = get_post_meta( $variation_id, 'pbc_question_input_type', true );
					$question_placeholder = get_post_meta( $variation_id, 'pbc_question_placeholder', true );
					$question_required    = get_post_meta( $variation_id, 'pbc_question_required', true );

					// Get saved answer from session if exists.
					$saved_answer = '';
					if ( isset( $_SESSION['pbc_questions'][ $question_key ] ) ) {
						$saved_answer = $_SESSION['pbc_questions'][ $question_key ];
					}

					$input_type  = 'number' === $question_input_type ? 'number' : 'text';
					$is_required = ! empty( $question_required ) && '1' === $question_required;
					?>
					<div class="variation-question-item">
						<label class="variation-question-label" for="pbc_question_<?php echo esc_attr( $question_key ); ?>">
							<?php
				echo esc_html( $variation_data['title'] );
				if ( $is_required ) {
					echo ' <span class="required-asterisk" style="color: #d32f2f;">*</span>';
				}
				?>
			</label>
			<input
				type="<?php echo esc_attr( $input_type ); ?>"
				class="pbc_question_input"
				name="pbc_question[<?php echo esc_attr( $question_key ); ?>]"
				id="pbc_question_<?php echo esc_attr( $question_key ); ?>"
				value="<?php echo esc_attr( $saved_answer ); ?>"
				placeholder="<?php echo esc_attr( $question_placeholder ); ?>"
				data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
				data-step="<?php echo esc_attr( $cstep ); ?>"
				data-question-key="<?php echo esc_attr( $question_key ); ?>"
				<?php echo $is_required ? 'required="required"' : ''; ?>
				<?php echo 'number' === $input_type ? 'step="any"' : ''; ?>
			/>
			<input type="hidden" name="pbc_question_variation_id[<?php echo esc_attr( $question_key ); ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
		</div>
		<?php
	} elseif ( empty( $field_type ) ) {
		if ( $allow_multiple ) {
			?>
			<label class="pbc-checkbox-option">
				<input type="checkbox" class="pbc_variation pbc_variation_multiple pbc-option-native" name="pbc_variation[<?php echo esc_attr( $cstep ); ?>][]" value="<?php echo esc_attr( $variation_id ); ?>" <?php checked( in_array( $variation_id, $selected_vars, true ), true, true ); ?> />
				<span class="pbc-checkbox-option-text"><?php echo esc_html( $variation_data['title'] ); ?></span>
			</label>
			<?php
		} else {
			if ( $actual_variation_tag !== $variation_data['section'] ) {
							?>
							<optgroup label="<?php echo esc_html( $variation_data['section'] ); ?>">
							<?php
							$actual_variation_tag = $variation_data['section'];
						}
						?>
						<option value="<?php echo esc_attr( $variation_id ); ?>" <?php checked( $variation_id, $s_var, true ); ?>><?php echo esc_html( $variation_data['title'] ); ?></option>
						<?php
					}
				} elseif ( 'qty' === $field_type ) {
					$s_var = $s_var === $variation_id ? 1 : $s_var;
					?>
					<input type="number" class="pbc_variation" name="pbc_variation[<?php echo esc_attr( $cstep ); ?>]" value="<?php echo (int) $s_var; ?>" />
					<input type="hidden" name="pbc_variation_id[<?php echo esc_attr( $cstep ); ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
					<?php
					echo esc_html( $variation_data['title'] );
				}
			}
		}
		if ( 'wizard' === $template ) {
			echo '</ul>';
		} elseif ( $allow_multiple ) {
				echo '</div>';
			} else {
			echo '</select>';
		}

		// Show custom input after all variations if any variation needs it.
		if ( ! empty( $variations_with_input ) && 'wizard' === $template ) {
			// Get saved value from session if available.
			$saved_value           = '';
			$selected_variation_id = 0;
			$should_show           = false;

			if ( PHP_SESSION_NONE !== session_status() && isset( $_SESSION ) ) {
				// Try to get from session - we need to check all possible session keys.
				foreach ( $_SESSION as $session_key => $session_data ) {
					if ( is_string( $session_key ) && 0 === strpos( $session_key, 'pbc_variation_' ) ) {
						if ( isset( $session_data[ $cstep ]['var']['id'] ) ) {
							$selected_variation_id = (int) $session_data[ $cstep ]['var']['id'];
							if ( in_array( $selected_variation_id, $variations_with_input, true ) ) {
								$should_show = true;
								if ( isset( $session_data[ $cstep ]['custom_input'][ $selected_variation_id ] ) ) {
									$saved_value = sanitize_textarea_field( $session_data[ $cstep ]['custom_input'][ $selected_variation_id ] );
								}
							}
						}
					}
				}
			}
			// Also check if current selected variation needs input.
			if ( (int) $s_var > 0 && in_array( (int) $s_var, $variations_with_input, true ) ) {
				$should_show           = true;
				$selected_variation_id = (int) $s_var;
				// Get saved value for current selection if not already set.
				if ( empty( $saved_value ) && PHP_SESSION_NONE !== session_status() && isset( $_SESSION ) ) {
					foreach ( $_SESSION as $session_key => $session_data ) {
						if ( is_string( $session_key ) && 0 === strpos( $session_key, 'pbc_variation_' ) ) {
							if ( isset( $session_data[ $cstep ]['custom_input'][ $selected_variation_id ] ) ) {
								$saved_value = sanitize_textarea_field( $session_data[ $cstep ]['custom_input'][ $selected_variation_id ] );
							}
						}
					}
				}
			}

			// Show input wrapper for all variations that need it, JavaScript will show/hide the correct one.
			?>
			<div class="pbc-custom-input-wrapper" data-variation-ids="<?php echo esc_attr( implode( ',', $variations_with_input ) ); ?>" style="<?php echo $should_show ? '' : 'display: none;'; ?>">
				<textarea 
					class="pbc-custom-input" 
					data-step="<?php echo esc_attr( $cstep ); ?>"
					rows="3"
					placeholder="<?php echo esc_attr__( 'Escribe aquí...', 'pbc' ); ?>"
				><?php echo esc_textarea( $saved_value ); ?></textarea>
			</div>
			<?php
		}
	}

	/**
	 * Calculate summary and show.
	 *
	 * @param string $pbc_session_key Session key.
	 * @param int    $cstep Current step.
	 * @param array  $phases Phases.
	 *
	 * @return void
	 */
	public static function calculation_summary( $pbc_session_key, $cstep, $phases ) {
		if ( ! isset( $_SESSION ) && ! isset( $_SESSION[ $pbc_session_key ] ) && is_array( $_SESSION[ $pbc_session_key ] ) ) {
			return;
		}
		?>
		<div class="configurator_summary">
			<?php
			$role = isset( $_SESSION[ $pbc_session_key ]['role_slug'] ) ? sanitize_key( $_SESSION[ $pbc_session_key ]['role_slug'] ) : '';
			if ( $role ) {
				$role_names = wp_roles()->get_names();
				$role_name  = isset( $role_names[ $role ] ) ? $role_names[ $role ] : $role;
				?>
				<div class="role"><?php echo esc_html( $role_name ); ?></div>
				<?php
			}
			?>
			<h2 class="title"><?php esc_html_e( 'Actual Configuration', 'pbc' ); ?></h2>
		<table>
			<?php
			$user            = wp_get_current_user();
			$user_role       = ! empty( $user->roles ) && isset( $user->roles[0] ) ? $user->roles[0] : '';
			$show_prices     = CALC::get_show_prices_for_user( $user_role );
			$show_price_ui   = ( 'yes' === $show_prices );

			$count = ( 'calculate' === $cstep ) ? count( $phases ) : (int) $cstep;

			$total_price = 0;
			for ( $i = 1; $i <= $count; $i++ ) {
				if ( ! isset( $_SESSION[ $pbc_session_key ][ $i ] ) ) {
					continue;
				}
				$var_price    = ! empty( $_SESSION[ $pbc_session_key ][ $i ]['var']['price'] ) ? (float) $_SESSION[ $pbc_session_key ][ $i ]['var']['price'] : 0;
				$variation_id = isset( $_SESSION[ $pbc_session_key ][ $i ]['var']['id'] ) ? (int) $_SESSION[ $pbc_session_key ][ $i ]['var']['id'] : 0;
				$var_type     = isset( $_SESSION[ $pbc_session_key ][ $i ]['var']['type'] ) ? sanitize_text_field( wp_unslash( $_SESSION[ $pbc_session_key ][ $i ]['var']['type'] ) ) : '';
				$field_type   = 'direct_input' === $var_type ? '' : get_post_meta( $variation_id, 'pbc_field_type', true );
				if ( 'calculate' === $cstep && empty( $field_type ) && 'direct_input' !== $var_type ) {
					$total_price += (float) $var_price;
				} elseif ( 'calculate' === $cstep && 'qty' === $field_type ) {
					$total_price = (float) $var_price * $total_price;
				}
			}

			$show_price_column = false;
			if ( $show_price_ui ) {
				if ( 'calculate' === $cstep ) {
					$show_price_column = ( $total_price > 0.00001 );
				} else {
					for ( $j = 1; $j <= $count; $j++ ) {
						if ( ! isset( $_SESSION[ $pbc_session_key ][ $j ] ) ) {
							continue;
						}
						if ( isset( $_SESSION[ $pbc_session_key ][ $j ]['questions'] ) && is_array( $_SESSION[ $pbc_session_key ][ $j ]['questions'] ) ) {
							continue;
						}
						$step_price = isset( $_SESSION[ $pbc_session_key ][ $j ]['var']['price'] ) ? (float) $_SESSION[ $pbc_session_key ][ $j ]['var']['price'] : 0;
						if ( $step_price > 0.00001 ) {
							$show_price_column = true;
							break;
						}
					}
				}
			}
			$show_price_column = (bool) apply_filters( 'pbc_summary_show_price_column', $show_price_column, $pbc_session_key, $cstep, $total_price, $show_price_ui );

			for ( $i = 1; $i <= $count; $i++ ) {
				if ( ! isset( $_SESSION[ $pbc_session_key ][ $i ] ) ) {
					continue;
				}
				$phase_key    = $i;
				$var_name     = isset( $_SESSION[ $pbc_session_key ][ $i ]['var']['name'] ) ? sanitize_text_field( wp_unslash( $_SESSION[ $pbc_session_key ][ $i ]['var']['name'] ) ) : '';
				$var_price    = ! empty( $_SESSION[ $pbc_session_key ][ $i ]['var']['price'] ) ? (float) $_SESSION[ $pbc_session_key ][ $i ]['var']['price'] : 0;
				$phase_name   = isset( $_SESSION[ $pbc_session_key ][ $i ]['phase']['name'] ) ? sanitize_text_field( wp_unslash( $_SESSION[ $pbc_session_key ][ $i ]['phase']['name'] ) ) : '';
				$variation_id = isset( $_SESSION[ $pbc_session_key ][ $i ]['var']['id'] ) ? (int) $_SESSION[ $pbc_session_key ][ $i ]['var']['id'] : 0;
				$var_type     = isset( $_SESSION[ $pbc_session_key ][ $i ]['var']['type'] ) ? sanitize_text_field( wp_unslash( $_SESSION[ $pbc_session_key ][ $i ]['var']['type'] ) ) : '';
				$field_type   = 'direct_input' === $var_type ? '' : get_post_meta( $variation_id, 'pbc_field_type', true );

				$has_multiple_questions = isset( $_SESSION[ $pbc_session_key ][ $i ]['questions'] ) && is_array( $_SESSION[ $pbc_session_key ][ $i ]['questions'] );

				if ( $has_multiple_questions ) {
					foreach ( $_SESSION[ $pbc_session_key ][ $i ]['questions'] as $question_data ) {
						?>
					<tr class="variation_selected phase-<?php echo esc_attr( $phase_key ); ?> question-row">
						<td class="name">
							<?php echo esc_html( $phase_key . '. ' . $question_data['variation_title'] . ': ' . $question_data['answer'] ); ?>
						</td>
						<?php if ( $show_price_column ) { ?>
						<td class="price">-</td>
						<?php } ?>
					</tr>
						<?php
					}
				} else {
					?>
					<tr class="variation_selected phase-<?php echo esc_attr( $phase_key ); ?>">
						<td class="name">
							<?php
							if ( 'direct_input' === $var_type ) {
								echo esc_html( $phase_key . '. ' . $phase_name . ': ' . $var_name );
							} elseif ( 'qty' === $field_type ) {
								echo esc_html( $phase_key . '. ' . $var_name . ' x ' . $var_price );
							} else {
								echo esc_html( $phase_key . '. ' . $phase_name . ': ' . $var_name );
							}
							?>
						</td>
						<?php if ( $show_price_column ) { ?>
						<td class="price">
							<?php
							if ( $var_price && $show_price_ui ) {
								echo esc_html( (string) $var_price );
								echo 'qty' === $field_type ? '' : ' €';
							}
							?>
						</td>
						<?php } ?>
					</tr>
					<?php
				}
			}
			if ( 'calculate' === $cstep && $show_price_ui && $total_price > 0.00001 ) {
				?>
					<tr class="variation_selected phase-total_price">
						<td class="name"><?php esc_html_e( 'Total', 'pbc' ); ?></td>
						<td class="price"><?php echo esc_html( number_format( $total_price, 2, ',', '.' ) . ' €' ); ?></td>
					</tr>
					<tr class="variation_selected phase-total_price">
						<td class="name"><?php esc_html_e( 'VAT not included', 'pbc' ); ?></td>
						<td class="price"></td>
					</tr>
				<?php } ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Show wizard phases.
	 *
	 * @param array $phases Phases.
	 * @param int   $cstep Current step.
	 * @return void
	 */
	public static function wizard_phases( $phases, $cstep ) {
		?>
		<div class="configurator_steps_nav" id="configurator_steps_nav">
			<ul>
			<?php
			$steps = 1;
			foreach ( $phases as $phase ) {
				?>
				<li class="configurator_steps step-<?php echo esc_attr( $steps ); ?><?php echo $steps === (int) $cstep ? ' active' : ''; ?>">
					<div class="stepContainer">
						<div class="step-name"><?php echo esc_html( get_the_title( $phase ) ); ?></div>
						<span class="step-arrow-button"></span>
					</div>
				</li>
				<?php
				++$steps;
			}
			?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Show action buttons.
	 *
	 * @param array  $phases Phases.
	 * @param int    $cstep Current step.
	 * @param string $template Template (unused parameter).
	 * @return void
	 */
	public static function action_buttons( $phases, $cstep, $template = 'wizard' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Template parameter kept for backward compatibility.
		unset( $template );
		?>
		<div class="configurator_form_action">
			<?php
			if ( 1 === $cstep ) {
				$prev_step   = '';
				$prev_button = '';
			} elseif ( 'calculate' === $cstep ) {
				$prev_step   = count( $phases );
				$prev_button = __( 'Back', 'pbc' );
			} else {
				$prev_step   = $cstep - 1;
				$prev_button = __( 'Back', 'pbc' );
			}

			if ( 'calculate' === $cstep ) {
				$next_step   = 'calculate';
				$next_button = '';
			} elseif ( count( $phases ) === $cstep ) {
				$next_step   = 'calculate';
				$next_button = __( 'Calculate', 'pbc' );
			} else {
				$next_step   = $cstep + 1;
				$next_button = __( 'Next', 'pbc' );
			}

			// Check if there are recommended variations configured.
			$variations_recommended = get_option( 'pbc_variations_recommended', array() );
			$has_recommendations    = ! empty( $variations_recommended );
			?>
			<input type="hidden" name="pbc_current_phase" value="<?php echo esc_attr( $cstep ); ?>"/>
			<?php if ( $prev_step && $prev_button ) { ?>
			<div class="prev">
				<input type="hidden" name="prev_phase" value="<?php echo esc_attr( $prev_step ); ?>"/>
				<button type="submit" name="submit" value="prev" class="btn btn-prev"><?php echo esc_attr( $prev_button ); ?></button>
			</div>
			<?php } ?>
			<?php if ( $cstep > 1 ) { ?>
			<div class="restart">
				<button type="button" id="pbc-restart-process" class="btn btn-restart"><?php esc_html_e( 'Restart', 'pbc' ); ?></button>
			</div>
			<?php } ?>
			<?php if ( 1 === $cstep && $has_recommendations ) { ?>
			<div class="recommendation" style="display:none;">
				<button type="button" id="pbc-load-recommendation" class="btn btn-recommendation"><?php esc_html_e( 'Recommendation', 'pbc' ); ?></button>
			</div>
			<?php } ?>
			<div class="next">
				<?php
				if ( $next_step ) {
					?>
					<input type="hidden" name="next_phase" value="<?php echo esc_attr( $next_step ); ?>"/><?php } ?>
				<?php
				if ( $next_button ) {
					?>
					<button type="submit" name="submit" value="next" class="btn btn-next"><?php echo esc_attr( $next_button ); ?></button><?php } ?>
			</div>
		</div>
		<?php
	}
}
