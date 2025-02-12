<?php
/**
 * Show Template Wizard
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2024 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

use Close\PBC\Helpers\CALC;
use Close\PBC\Helpers\SHOW;

/**
 * Template Wizard.
 *
 * @since 1.4.0
 */
class PBC_Template_Wizard {
	/**
	 * Render for Wizard.
	 *
	 * @param integer $parent_phase Parent Phase.
	 * @return void
	 */
	public static function render( $parent_phase = 0 ) {
		$cstep   = 1;
		$user_id = get_current_user_id();

		// Makes default parent phase.
		$default_post_parent = CALC::get_default_parent_phase();
		$is_multiple_prods   = ! empty( $default_post_parent ) ? true : false;
		$base_parent         = $is_multiple_prods && empty( $parent_phase ) ? $default_post_parent : $parent_phase;

		$args   = array(
			'numberposts' => -1,
			'post_type'   => 'phases',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
			'post_parent' => $base_parent,
			'fields'      => 'ids',
		);
		$phases = get_posts( $args );

		if ( empty( $_POST ) ) {
			$_SESSION['pbc_variation'] = array();
		}

		if ( isset( $_POST['submit'] ) && isset( $_POST['pbc_template_wizard_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pbc_template_wizard_nonce'] ) ), 'pbc_template_wizard_action' ) ) {
			$submit = sanitize_text_field( wp_unslash( $_POST['submit'] ) );
			if ( isset( $_POST[ $submit . '_phase' ] ) ) {
				$cstep = sanitize_text_field( wp_unslash( $_POST[ $submit . '_phase' ] ) );
			} else {
				$cstep = 'calculate';
			}

			if ( isset( $_POST['pbc_variation'] ) && $_POST['submit'] == 'next' ) {
				if ( ! isset( $_SESSION['pbc_variation'] ) || ! is_array( $_SESSION['pbc_variation'] ) ) {
					$_SESSION['pbc_variation'] = array();
				}
				foreach ( $_POST['pbc_variation'] as $key => $variation_id ) {
					if ( empty( $phases ) ) {
						break;
					}
					$variation_id = (int) $variation_id;
					$price        = '';
					$option_name  = '';
					$price_var    = isset( $_POST[ 'pbc_pricevar_' . $variation_id ] ) ? sanitize_text_field( $_POST[ 'pbc_pricevar_' . $variation_id ] ) : '';

					// Gets variation ID in quantity input.
					$option_qty_value = 0;
					if ( isset( $_POST['pbc_variation_id'][ $key ] ) ) {
						$option_qty_value = (int) $variation_id;
						$variation_id     = (int) $_POST['pbc_variation_id'][ $key ];
					}

					if ( ! empty( $user_id ) ) {
						$phase_param['var']      = $variation_id;
						$phase_param['pricevar'] = $price_var ? $price_var : '';
						update_user_meta( $user_id, 'pbc_phase_' . $key, $phase_param );
					}
					if ( empty( $option_qty_value ) ) {
						$price = CALC::get_price_variation( $variation_id, $price_var );
					} else {
						$price = $option_qty_value;
					}

					$phase_id        = $phases[ (int) $key - 1 ];
					$variation_title = get_the_title( $variation_id );
					if ( $price_var ) {
						$variation_title .= ' [' . $price_var . ']';
					}
					$_SESSION['pbc_variation'][ $key ]['phase']['id']   = $phase_id;
					$_SESSION['pbc_variation'][ $key ]['phase']['name'] = get_the_title( $phase_id );
					$_SESSION['pbc_variation'][ $key ]['var']['id']     = $variation_id;
					$_SESSION['pbc_variation'][ $key ]['var']['name']   = $variation_title;
					if ( $option_name ) {
						$_SESSION['pbc_variation'][ $key ]['var']['name'] .= ' [' . $option_name . ']';
					}
					$_SESSION['pbc_variation'][ $key ]['var']['price'] = $price;
				}
				ksort( $_SESSION['pbc_variation'], SORT_NUMERIC );
			}
		} elseif ( isset( $_GET['phase'] ) ) {
			$cstep = (int) $_GET['phase'];
		}

		if ( ! defined( 'DOING_AJAX' ) ) {
			?>
			<div class="page-configurator">
			<?php
		} //defined('DOING_AJAX')

		if ( empty( $phases ) ) {
			?>
			<div class="error"><?php esc_html_e( 'No Phases Available', 'pbc' ); ?></div>
			</div>
			<?php
		}
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
		<?php $parent_phase_slug = sanitize_title( get_the_title( $parent_phase ) ); ?>
		<div class="phase_detail product-<?php echo esc_html( $parent_phase_slug ); ?>">
			<form action="" method="post" name="configurator-form" id="configurator-form">
			<?php
			wp_nonce_field( 'pbc_template_wizard_action', 'pbc_template_wizard_nonce' );
			if ( 'calculate' !== $cstep ) {
				$phase_id    = isset( $phases[ ( (int) $cstep - 1 ) ] ) ? $phases[ ( (int) $cstep - 1 ) ] : 0;
				$phase_title = get_the_title( $phase_id );
				$phase_slug  = sanitize_title( get_the_title( $phase_id ) );
				?>
				<div class="configurator-left">
					<div class="phase_title"><?php echo esc_html( $phase_title ); ?></div>
					<div class="phase_variations phase-<?php echo esc_html( $phase_slug ); ?>">
						<?php
						$variations = get_posts( 'numberposts=-1&post_type=variation&meta_key=pbc_phase&meta_value=' . $phase_id . '&fields=ids&orderby=title&order=asc' );
						if ( ! empty( $variations ) ) {
							foreach ( $variations as $key => $variation ) {
								$pbc_depends = get_post_meta( $variation, 'pbc_depends', true );
								if ( ! empty( $pbc_depends ) ) {
									$prevVar = array();
									foreach ( $pbc_depends as $deps ) {
										$arr = explode( '|', $deps['pbc_depvar'] );
										if ( ! empty( $arr[0] ) && ! empty( $arr[1] ) ) {
											$prevVar[ (int) $arr[0] ][] = $arr[1];
										}
									}
									if ( $cstep != 1 && ! empty( $_SESSION['pbc_variation'] ) ) {
										foreach ( $_SESSION['pbc_variation'] as $sPhaseKey => $sVariations ) {
											if ( isset( $prevVar[ $sPhaseKey ] ) && ! in_array( $_SESSION['pbc_variation'][ $sPhaseKey ]['var']['id'], $prevVar[ $sPhaseKey ] ) ) {
												unset( $variations[ $key ] );
												break;
											}
										}
									}
								}
							}
							$variations = array_values( $variations );

							// Order variations per section.
							$variations_section = array();
							foreach ( $variations as $variation_id ) {
								$term_list            = (array) wp_get_post_terms(
									$variation_id,
									'variation_tag',
									array(
										'fields' => 'all',
									)
								);
								$variations_section[] = array(
									'id'      => $variation_id,
									'section' => isset( $term_list[0]->name ) ? $term_list[0]->name : '',
									'title'   => get_the_title( $variation_id ),
								);
							}

							// Order by sections and title.
							foreach ( $variations_section as $key => $val ) {
									$temp_arr['section'][ $key ] = $val['section'];
									$temp_arr['title'][ $key ]   = $val['title'];
							}
							// Sort by section asc and then title asc.
							if ( ! empty( $temp_arr['section'] ) && ! empty( $temp_arr['title'] ) ) {
								array_multisort( $temp_arr['section'], SORT_ASC, $temp_arr['title'], SORT_ASC, $variations_section );
							}

							// Show public.
							$s_var = 0;
							if (
								isset( $_SESSION['pbc_variation'] ) &&
								is_array( $_SESSION['pbc_variation'] ) &&
								isset( $_SESSION['pbc_variation'][ $cstep ] ) &&
								in_array( $_SESSION['pbc_variation'][ $cstep ]['var']['id'], $variations )
							) {
								$s_var = isset( $_SESSION['pbc_variation'][ $cstep ]['var']['id'] ) ? (int) $_SESSION['pbc_variation'][ $cstep ]['var']['id'] : '';
							} else {
								if ( isset( $user_id ) ) {
									$pbc_phase = get_user_meta( $user_id, 'pbc_phase_' . $cstep, true );
									if ( ! empty( $pbc_phase ) && ! empty( $pbc_phase['var'] ) ) {
										$s_var = $pbc_phase['var'];
									}
								}
								if ( empty( $s_var ) ) {
									$s_var = $variations[ current( array_keys( $variations ) ) ];
								}
							}
							if ( ! empty( $variations_section ) ) {
								SHOW::variations_content( $variations_section, $s_var, $cstep );
							}
						} else {
							?>
							<div class="error"><?php esc_html_e( 'No Variations Available', 'pbc' ); ?></div>
							<?php
						}
						?>
					</div>
					<?php
					// Variations Description.
					if ( ! empty( $variations ) ) {
						$index_var = 1;
						echo '<div class="phase_descvar">';
						foreach ( $variations as $variation_id ) {
							$descvar = get_post_meta( $variation_id, 'pbc_descvar', true );
							if ( ! empty( $descvar ) ) {
								echo '<div class="descvar descvar_' . esc_attr( $variation_id );
								if ( $index_var > 1 ) {
									echo ' hidden';
								} else {
									echo ' actived';
								}
								echo '">';
								echo wpautop( $descvar );
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
							echo $phase_post->post_content;
						}
						?>
					</div>
				</div>
				<div class="configurator-right">
				<?php
			} //cStep!=calculate

			if ( 'calculate' !== $cstep ) {
				?>
				<script type="text/javascript">jQuery('.configurator_form_action').insertAfter('.product_preview');</script>
				<?php
			} elseif ( 'calculate' === $cstep ) {
				?>
				<script type="text/javascript">jQuery('.configurator_form_action').insertBefore('.product_preview');</script>
				<?php
			}
			?>
			<div class="product_preview
			<?php
			if ( 'calculate' === $cstep ) {
				echo ' wrap-left'; }
			?>
			">
				<div class="image-wrap">
					<?php
					$ssVar = '';
					if ( ! empty( $_SESSION['pbc_variation'] ) ) {
						$to = (int) $cstep;
						if ( 'calculate' === $cstep ) {
							$to = count( $_SESSION['pbc_variation'] ) + 1;
						}
						for ( $i = 1; $i < $to; $i++ ) {
							$imgprodid = $imgprodurl = '';
							if ( isset( $_SESSION['pbc_variation'][ $i ] ) ) {
								$ssVar        = $_SESSION['pbc_variation'][ $i ]['var']['id'];
								$imgprodgroup = get_post_meta( $ssVar, 'pbc_imgprodgroup', true );
								if ( ! empty( $imgprodgroup ) ) {
									foreach ( $imgprodgroup as $deps ) {
										if ( isset( $deps['pbc_depvarimgprod'] ) && ! empty( $deps['pbc_depvarimgprod'] ) && isset( $deps['pbc_imgprod'] ) ) {
											$prevVar = array();
											foreach ( $deps['pbc_depvarimgprod'] as $depvarimgprod ) {
												$imgprod_arr = explode( '|', $depvarimgprod );
												if ( ! empty( $imgprod_arr[0] ) && ! empty( $imgprod_arr[1] ) ) {
													$prevVar[ (int) $imgprod_arr[0] ][] = $imgprod_arr[1];
												}
											}
											if ( ! empty( $_SESSION['pbc_variation'] ) && ! empty( $prevVar ) ) {
												foreach ( $prevVar as $sPhaseKey => $sVariations ) {
													if ( isset( $prevVar[ $sPhaseKey ] ) &&
													isset( $_SESSION['pbc_variation'][ $sPhaseKey ] ) &&
													in_array( $_SESSION['pbc_variation'][ $sPhaseKey ]['var']['id'], $prevVar[ $sPhaseKey ] ) ) {
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
								if ( isset( $imgprodid ) && $imgprodid ) {
									$imgprodurl = wp_get_attachment_image_src( $imgprodid, 'full', true );
								}
								if ( isset( $imgprodurl ) && $imgprodurl ) {
									$addclass                  = '';
									$variations_images_flipped = get_option( 'variations_images_flipped' );
									if ( ! empty( $variations_images_flipped ) ) {
										for ( $j = 1; $j <= $to; $j++ ) {
											if ( isset( $_SESSION['pbc_variation'][ $j ] ) && in_array( $_SESSION['pbc_variation'][ $j ]['var']['id'], $variations_images_flipped ) ) {
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
					$imgprodurl = isset( $s_var ) ? CALC::get_image_variation_url( $_SESSION['pbc_variation'], $s_var ) : '';

					if ( $imgprodurl ) {
						$variations_images_flipped = get_option( 'variations_images_flipped' );
						$addclass                  = '';
						if ( ! empty( $variations_images_flipped ) && in_array( $ssVar, $variations_images_flipped ) ) {
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
				?>
				<input type="hidden" name="pbc_current_phase" value="<?php echo $cstep; ?>"/>
				<?php if ( $prev_step && $prev_button ) { ?>
				<div class="prev 
					<?php
					if ( empty( $prev_step ) ) {
						echo 'hidden';}
					?>
				">
					<input type="hidden" name="prev_phase" value="<?php echo esc_attr( $prev_step ); ?>"/>
					<button type="submit" name="submit" value="prev" class="btn btn-prev"><?php echo esc_html( $prev_button ); ?></button>
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
						<button type="submit" name="submit" value="next" class="btn btn-next"><?php echo esc_html( $next_button ); ?></button><?php } ?>
				</div>
			</div>
			<?php
			SHOW::calculation_summary( $cstep, $phases );
			if ( 'calculate' === $cstep ) {
				?>
				<div class="configurator_result_share">
					<?php
					$session_type = isset( $_SESSION['pbc_output']['type'] ) ? sanitize_text_field( $_SESSION['pbc_output']['type'] ) : '';
					if ( ! isset( $_SESSION['pbc_output'] ) || 'success' !== $session_type ) {
						?>
						<h2><?php esc_html_e( 'Send budget to email', 'pbc' ); ?></h2>
						<div class="email_submit_fields">
							<h2><?php esc_html( 'Send the budget to an email:', 'pbc' ); ?></h2>
							<input type="text" name="email_field" placeholder="<?php _e( 'separate multiple email by comma', 'pbc' ); ?>"/><br/>
							<input type="text" name="name_field" style="width:150px;" placeholder="<?php _e( 'Your name', 'pbc' ); ?>"/>
							<input type="text" name="phone_field" style="width:150px;" placeholder="<?php _e( 'Phone number', 'pbc' ); ?>"/><br/>
							<input type="text" name="city_field" style="width:150px;" placeholder="<?php _e( 'Your City', 'pbc' ); ?>"/>
							<input type="text" name="state_field" style="width:150px;" placeholder="<?php _e( 'State', 'pbc' ); ?>"/><br/>
							<button type="submit" name="submit" class="btn btn-submit" value="email_send"><?php _e( 'Send', 'pbc' ); ?></button>
							<?php
							$show_button_pdf = get_option( 'pbc_budget_show_button_pdf' );
							if ( 'no' !== $show_button_pdf ) {
								?>
								<a href="?phase=calculate&configurator=pdf" class="btn btn-pdf" title="Generate PDF"><?php _e( 'PDF', 'pbc' ); ?></a>
							<?php } ?>
						</div>
						<?php
					}

					if ( isset( $_SESSION['pbc_output'] ) ) {
						?>
						<div class="result_submit_action <?php echo esc_html( $session_type ); ?>">
							<?php
							echo $_SESSION['pbc_output']['response'];
							?>
						</div>
						<?php
						unset( $_SESSION['pbc_output'] );
					}
					?>
				</div>
				<?php
			}
			if ( 'calculate' !== $cstep ) {
				// Banner.
				do_action( 'pbc_banner_after_setup' );
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
}
