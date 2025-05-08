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
	 * @param array $variations_section Variations sections.
	 * @param int   $s_var Selected variation.
	 * @param int   $cstep Current step.
	 * @return void
	 */
	public static function variations_content( $variations_section, $s_var, $cstep, $template = 'wizard' ) {
		$actual_variation_tag = '';

		if ( 'wizard' === $template ) {
			echo '<ul>';
		} else {
			echo '<select name="pbc_variation[' . esc_attr( $cstep ) . ']" class="pbc_variation">';
		}

		foreach ( $variations_section as $variation_data ) {
			$variation_id = (int) $variation_data['id'];
			$field_type   = get_post_meta( $variation_id, 'pbc_field_type', true );

			if ( 'wizard' === $template ) {
				if ( $actual_variation_tag !== $variation_data['section'] ) {
					echo '</ul><h2>' . esc_html( $variation_data['section'] ) . '</h2><ul>';
					$actual_variation_tag = $variation_data['section'];
				}
				?>
				<li class="variation_list">
					<label>
						<?php
						$imgicon = get_post_meta( $variation_id, 'pbc_imgicon', true );
						if ( $imgicon ) {
							echo '<div class="variation_img">';
							echo wp_get_attachment_image( $imgicon, 'pbc_icon', false );
							echo '</div>';
						}
						if ( empty( $field_type ) ) {
							?>
							<input type="radio" class="pbc_variation" name="pbc_variation[<?php echo esc_attr( $cstep ); ?>]" value="<?php echo esc_attr( $variation_id ); ?>" <?php checked( $variation_id, $s_var, true ); ?> />
							<?php
							echo esc_html( $variation_data['title'] );
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
						<p class="pbc_descopt"><?php echo wpautop( $pbc_descopt ); ?></p>
						<?php
					}
					?>
				</li>
				<?php
			} elseif ( 'vertical' === $template ) {
				if ( empty( $field_type ) ) {
					if ( $actual_variation_tag !== $variation_data['section'] ) {
						?>
						<optgroup label="<?php echo esc_html( $variation_data['section'] ); ?>">
						<?php
						$actual_variation_tag = $variation_data['section'];
					}
					?>
					<option value="<?php echo esc_attr( $variation_id ); ?>" <?php checked( $variation_id, $s_var, true ); ?>><?php echo esc_html( $variation_data['title'] ); ?></option>
					<?php
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
		} else {
			echo '</select>';
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
			$role = isset( $_SESSION[ $pbc_session_key ]['role'] ) ? sanitize_key( $_SESSION[ $pbc_session_key ]['role'] ) : '';
			if ( $role ) {
				$role_name = $role ? wp_roles()->get_names()[ $role ] : $role;
				?>
				<div class="role"><?php echo esc_html( $role_name ); ?></div>
				<?php
			}
			?>
			<h2 class="title"><?php esc_html_e( 'Actual Configuration', 'pbc' ); ?></h2>
			<table>
				<?php
				$show_prices = get_option( 'pbc_budget_show_prices' );
				if ( 'calculate' === $cstep ) {
					$count       = count( $phases );
					$total_price = 0;
				} else {
					$count = $cstep;
				}
				for ( $i = 1; $i <= $count; $i++ ) {
					if ( ! isset( $_SESSION[ $pbc_session_key ][ $i ] ) ) {
						continue;
					}
					$phase_key    = $i;
					$var_name     = isset( $_SESSION[ $pbc_session_key ][ $i ]['var']['name'] ) ? sanitize_text_field( $_SESSION[ $pbc_session_key ][ $i ]['var']['name'] ) : '';
					$var_price    = ! empty( $_SESSION[ $pbc_session_key ][ $i ]['var']['price'] ) ? (float) $_SESSION[ $pbc_session_key ][ $i ]['var']['price'] : 0;
					$phase_name   = isset( $_SESSION[ $pbc_session_key ][ $i ]['phase']['name'] ) ? sanitize_text_field( $_SESSION[ $pbc_session_key ][ $i ]['phase']['name'] ) : '';
					$variation_id = isset( $_SESSION[ $pbc_session_key ][ $i ]['var']['id'] ) ? (int) $_SESSION[ $pbc_session_key ][ $i ]['var']['id'] : 0;
					$field_type   = get_post_meta( $variation_id, 'pbc_field_type', true );

					if ( 'calculate' === $cstep && empty( $field_type ) ) {
						$total_price += (float) $var_price;
					} elseif ( 'calculate' === $cstep && 'qty' === $field_type ) {
						$total_price = (float) $var_price * $total_price;
					}

					?>
					<tr class="variation_selected phase-<?php echo esc_attr( $phase_key ); ?>">
						<td class="name">
							<?php
							if ( 'qty' === $field_type ) {
								echo esc_html( $phase_key . '. ' . $var_name . ' x ' . $var_price );
							} else {
								echo esc_html( $phase_key . '. ' . $phase_name . ': ' . $var_name );
							}
							?>
						</td>
						<td class="price">
							<?php
							if ( $var_price && 'no' !== $show_prices ) {
								echo esc_html( $var_price );
								echo 'qty' === $field_type ? '' : ' €';
							}
							?>
						</td>
					</tr>
					<?php
				}
				if ( 'calculate' === $cstep && 'no' !== $show_prices ) {
					?>
					<tr class="variation_selected phase-total_price">
						<td class="name"><?php esc_html_e( 'Total', 'pbc' ); ?></td>
						<td class="price">
							<?php
							if ( $total_price ) {
								echo number_format( $total_price, 2, ',', '.' ) . ' €';
							}
							?>
						</td>
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
	 * @param string $template Template.
	 * @return void
	 */
	public static function action_buttons( $phases, $cstep, $template = 'wizard' ) {
		?>
		<div class="configurator_form_action">
			<?php
			if ( $cstep == 1 ) {
				$prev_step   = '';
				$prev_button = '';
			} elseif ( $cstep == 'calculate' ) {
				$prev_step   = count( $phases );
				$prev_button = __( 'Back', 'pbc' );
			} else {
				$prev_step   = $cstep - 1;
				$prev_button = __( 'Back', 'pbc' );
			}

			if ( $cstep == 'calculate' ) {
				$next_step   = 'calculate';
				$next_button = '';
			} elseif ( $cstep == count( $phases ) ) {
				$next_step   = 'calculate';
				$next_button = __( 'Calculate', 'pbc' );
			} else {
				$next_step   = $cstep + 1;
				$next_button = __( 'Next', 'pbc' );
			}
			?>
			<input type="hidden" name="pbc_current_phase" value="<?php echo esc_attr( $cstep ); ?>"/>
			<?php if ( $prev_step && $prev_button ) { ?>
			<div class="prev<?php if ( empty( $prev_step ) ) { echo ' hidden'; } ?>">
				<input type="hidden" name="prev_phase" value="<?php echo esc_attr( $prev_step ); ?>"/>
				<button type="submit" name="submit" value="prev" class="btn btn-prev"><?php echo esc_attr( $prev_button ); ?></button>
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
