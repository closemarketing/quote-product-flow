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
class PBC_Template {
	/**
	 * Render for Wizard.
	 *
	 * @param integer $parent_phase Parent Phase.
	 * @return void
	 */
	public static function render( $parent_phase, $template ) {
		$cstep   = 1;
		$user_id = get_current_user_id();

		// Makes default parent phase.
		$default_post_parent = CALC::get_default_parent_phase();
		$is_multiple_prods   = CALC::is_multiple_products();
		$phase_pid           = $is_multiple_prods && empty( $parent_phase ) ? (int) $default_post_parent : (int) $parent_phase;
		if ( isset( $_GET['pbc_parent'] ) ) {
			$phase_pid = (int) $_GET['pbc_parent'];
		}
		$pbc_session_key     = 'pbc_variation_' . $phase_pid;

		$shared_session_loaded = false;
		
		// Check if we have direct URL parameters (v1, v2, etc.).
		$has_share_params = false;
		foreach ( $_GET as $key => $value ) {
			if ( preg_match( '/^v\d+$/', $key ) ) {
				$has_share_params = true;
				break;
			}
		}
		
		if ( $has_share_params ) {
			// Reconstruct session from URL parameters.
			$reconstructed_session = array();
			
			// Get role and discount.
			$role_discount = CALC::get_user_discount_and_role();
			$reconstructed_session['role_slug']     = $role_discount['role'] ?? '';
			$reconstructed_session['role_discount'] = $role_discount['discount'] ?? '';
			
			// Check if prices should be shown - IMPORTANT: Set this first!
			$force_show_prices = false;
			if ( isset( $_GET['pbc_show_prices'] ) && '1' === $_GET['pbc_show_prices'] ) {
				$force_show_prices = true;
				$reconstructed_session['force_show_prices'] = 'yes';
			}
			
			// Extract variations from URL.
			$variations_data = array();
			foreach ( $_GET as $key => $value ) {
				if ( preg_match( '/^v(\d+)$/', $key, $matches ) ) {
					$step                        = (int) $matches[1];
					$variations_data[ $step ]    = array();
					$variations_data[ $step ]['var_id'] = (int) $value;
					
					// Check for price variation.
					$price_key = 'p' . $step;
					if ( isset( $_GET[ $price_key ] ) ) {
						$variations_data[ $step ]['price_var'] = sanitize_text_field( wp_unslash( $_GET[ $price_key ] ) );
					}
				}
			}
			
			// Get phases to match step numbers.
			$temp_args   = array(
				'numberposts' => -1,
				'post_type'   => 'phases',
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'post_parent' => $phase_pid,
			);
			$temp_phases = get_posts( $temp_args );
			$phase_map   = array();
			foreach ( $temp_phases as $index => $phase_post ) {
				$phase_map[ $index + 1 ] = $phase_post->ID;
			}
			
			// Build session from variations.
			foreach ( $variations_data as $step => $var_data ) {
				$variation_id = $var_data['var_id'];
				$price_var    = isset( $var_data['price_var'] ) ? $var_data['price_var'] : '';
				
				// Get the phase from the variation's meta.
				$phase_id_from_var = get_post_meta( $variation_id, 'pbc_phase', true );
				$phase_id          = ! empty( $phase_id_from_var ) ? (int) $phase_id_from_var : ( isset( $phase_map[ $step ] ) ? $phase_map[ $step ] : 0 );
				$phase_title       = $phase_id ? get_the_title( $phase_id ) : '';
				
				$variation_title = get_the_title( $variation_id );
				if ( $price_var ) {
					$variation_title .= ' [' . $price_var . ']';
				}
				
				$price      = CALC::get_price_variation( $variation_id, $price_var );
				$field_type = get_post_meta( $variation_id, 'pbc_field_type', true );
				
				$var_data_array = array(
					'id'    => $variation_id,
					'name'  => $variation_title,
					'type'  => $field_type ? $field_type : 'price',
					'price' => $price,
				);
				
				// Add price_var if it exists.
				if ( $price_var ) {
					$var_data_array['price_var'] = $price_var;
				}
				
				$reconstructed_session[ $step ] = array(
					'phase' => array(
						'id'   => $phase_id,
						'name' => $phase_title,
					),
					'var'   => $var_data_array,
				);
			}
			
			// Re-add force_show_prices after building session to ensure it's not lost.
			if ( $force_show_prices ) {
				$reconstructed_session['force_show_prices'] = 'yes';
			}
			
			$_SESSION[ $pbc_session_key ] = $reconstructed_session;
			$shared_session_loaded        = true;
			
			// Debug: Log what was reconstructed.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PBC: Reconstructed session with ' . count( $variations_data ) . ' variations' );
				error_log( 'PBC: Force show prices: ' . ( $force_show_prices ? 'YES' : 'NO' ) );
				error_log( 'PBC: Session key: ' . $pbc_session_key );
				error_log( 'PBC: Session steps: ' . implode( ', ', array_keys( $_SESSION[ $pbc_session_key ] ) ) );
			}
			
			// Add debug comment in HTML.
			echo '<!-- PBC Debug: Loaded ' . count( $variations_data ) . ' variations from URL -->';
			echo '<!-- PBC Debug: Show prices = ' . ( $force_show_prices ? 'YES' : 'NO' ) . ' -->';
			echo '<!-- PBC Debug: Session steps: ' . implode( ', ', array_keys( $_SESSION[ $pbc_session_key ] ) ) . ' -->';
		}

		$args   = array(
			'numberposts' => -1,
			'post_type'   => 'phases',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
			'post_parent' => $phase_pid,
		);
		$post_phases  = get_posts( $args );
		$phases       = array();
		$phases_order = array();
		
		// If loading from shared session, build phases from session data.
		if ( $shared_session_loaded && isset( $_SESSION[ $pbc_session_key ] ) && is_array( $_SESSION[ $pbc_session_key ] ) ) {
			foreach ( $_SESSION[ $pbc_session_key ] as $step_key => $session_details ) {
				if ( ! is_numeric( $step_key ) ) {
					continue;
				}
				if ( isset( $session_details['phase']['id'] ) ) {
					$phase_id = (int) $session_details['phase']['id'];
					if ( ! in_array( $phase_id, $phases, true ) ) {
						$phases[]       = $phase_id;
						$phases_order[] = (int) $step_key;
					}
				}
			}
		} else {
			// Normal loading: use all phases from database.
			foreach ( $post_phases as $post_phase ) {
				$phases[]       = $post_phase->ID;
				$phases_order[] = $post_phase->menu_order;
			}
		}
		
		// Fallback: if phases is still empty, try to rebuild from session.
		if ( empty( $phases ) && isset( $_SESSION[ $pbc_session_key ] ) && is_array( $_SESSION[ $pbc_session_key ] ) ) {
			foreach ( $_SESSION[ $pbc_session_key ] as $step_key => $session_details ) {
				if ( ! is_numeric( $step_key ) ) {
					continue;
				}
				if ( isset( $session_details['phase']['id'] ) ) {
					$phase_id = (int) $session_details['phase']['id'];
					if ( ! in_array( $phase_id, $phases, true ) ) {
						$phases[]       = $phase_id;
						$phases_order[] = (int) $step_key;
					}
				}
			}
		}

		if ( empty( $_POST ) && ! $shared_session_loaded ) {
			$_SESSION[ $pbc_session_key ] = array();
			// Get role and discount.
			$role_discount = CALC::get_user_discount_and_role();

			$_SESSION[ $pbc_session_key ]['role_slug']     = $role_discount['role'] ?? '';
			$_SESSION[ $pbc_session_key ]['role_discount'] = $role_discount['discount'] ?? '';
		}

		// Add inline style for the template.
		$color_main = get_option( 'pbc_pdf_color_total' );

		$custom_css = '
		.page-configurator .btn, .page-configurator button[type="submit"] {
			background-color: ' . esc_attr( $color_main ) . ';
			color: ' . esc_attr( CALC::calculate_color_text( $color_main ) ) . ';);
		}
		.page-configurator .prev .btn {
			background-color: ' . esc_attr( CALC::adjust_brightness( $color_main, -20 ) ) . ';
		}
		.page-configurator .btn:hover, .page-configurator .btn:focus, .page-configurator button[type="submit"]:hover, .page-configurator button[type="submit"]:focus {
			background-color: ' . esc_attr( CALC::adjust_brightness( $color_main, -20 ) ) . ';
		}';

		// Output the inline style.
		wp_add_inline_style( 'pbc-public', $custom_css );

		// If shared session loaded, go directly to calculate.
		if ( $shared_session_loaded && empty( $_POST ) ) {
			$cstep = 'calculate';
		}

		if ( isset( $_POST['submit'] ) && isset( $_POST['pbc_template_wizard_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pbc_template_wizard_nonce'] ) ), 'pbc_template_wizard_action' ) ) {
			$submit = sanitize_text_field( wp_unslash( $_POST['submit'] ) );
			if ( isset( $_POST[ $submit . '_phase' ] ) && is_numeric( $_POST[ $submit . '_phase' ] ) ) {
				$cstep = (int) $_POST[ $submit . '_phase' ];
			} elseif ( 'generate_pdf' === $submit && isset( $_SESSION['pbc_output'] ) ) {
				echo '<script>window.open("' . esc_url( sanitize_url( $_SESSION['pbc_output'] ) ) . '", "_blank");</script>';
				$cstep = 'calculate';
			} else {
				$cstep = 'calculate';
			}

			if ( isset( $_POST['pbc_variation'] ) && 'next' === $_POST['submit'] ) {
				if ( ! isset( $_SESSION[ $pbc_session_key ] ) || ! is_array( $_SESSION[ $pbc_session_key ] ) ) {
					$_SESSION[ $pbc_session_key ] = array();
				}
				foreach ( $_POST['pbc_variation'] as $key => $variation_id ) { // phpcs:ignore
					if ( empty( $phases ) ) {
						break;
					}
					$variation_id = (int) $variation_id;
					$price        = '';
					$option_name  = '';
					$price_var    = isset( $_POST[ 'pbc_pricevar_' . $variation_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'pbc_pricevar_' . $variation_id ] ) ) : '';
					$field_type   = 'price';

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
						$price      = $option_qty_value;
						$field_type = 'qty';
					}

					$phase_id        = $phases[ (int) $key - 1 ];
					$phase_title     = get_the_title( $phase_id );
					$variation_title = get_the_title( $variation_id );
					if ( $price_var ) {
						$variation_title .= ' [' . $price_var . ']';
					}

					$_SESSION[ $pbc_session_key ][ $key ]['phase']['id']   = $phase_id;
					$_SESSION[ $pbc_session_key ][ $key ]['phase']['name'] = $phase_title;
					$_SESSION[ $pbc_session_key ][ $key ]['var']['id']     = $variation_id;
					$_SESSION[ $pbc_session_key ][ $key ]['var']['name']   = $variation_title;
					$_SESSION[ $pbc_session_key ][ $key ]['var']['type']   = $field_type;
					if ( $option_name ) {
						$_SESSION[ $pbc_session_key ][ $key ]['var']['name'] .= ' [' . $option_name . ']';
					}
					$_SESSION[ $pbc_session_key ][ $key ]['var']['price'] = $price;
					if ( $price_var ) {
						$_SESSION[ $pbc_session_key ][ $key ]['var']['price_var'] = $price_var;
					}
				}
				ksort( $_SESSION[ $pbc_session_key ], SORT_NUMERIC );
			} elseif ( 'next' === $_POST['submit'] ) {
				// If no variation was selected (empty form), check if there's only one option and save it automatically.
				$current_phase_num = isset( $_POST['pbc_current_phase'] ) ? (int) $_POST['pbc_current_phase'] : 0;
				if ( $current_phase_num > 0 && $current_phase_num <= count( $phases ) ) {
					$phase_id = $phases[ $current_phase_num - 1 ];
					
					// Check if this step hasn't been saved yet.
					if ( ! isset( $_SESSION[ $pbc_session_key ][ $current_phase_num ] ) || ! isset( $_SESSION[ $pbc_session_key ][ $current_phase_num ]['var']['id'] ) ) {
						// Get available variations for this phase.
						$prev_variations_ids = array();
						if ( isset( $_SESSION[ $pbc_session_key ] ) ) {
							foreach ( $_SESSION[ $pbc_session_key ] as $step_key => $prev_var ) {
								if ( isset( $prev_var['var']['id'] ) ) {
									$var_id                              = (int) $prev_var['var']['id'];
									$prev_variations_ids[ $step_key - 1 ] = $var_id;
								}
							}
						}
						
						$variations = get_posts( 'numberposts=-1&post_type=variation&meta_key=pbc_phase&meta_value=' . $phase_id . '&fields=ids&orderby=title&order=asc' );
						
						if ( ! empty( $variations ) && isset( $_SESSION[ $pbc_session_key ] ) ) {
							$variations_depends = array();
							foreach ( $variations as $variation_id ) {
								$depends = get_post_meta( $variation_id, 'pbc_depends', true );
								if ( ! empty( $depends ) ) {
									$variations_depends[ $variation_id ] = array();
									foreach ( $depends as $depend ) {
										$arr = explode( '|', $depend['pbc_depvar'] );
										if ( isset( $arr[0] ) && isset( $arr[1] ) ) {
											$order = array_search( (int) $arr[0], $phases_order, true );
											$variations_depends[ $variation_id ][ $order ][] = (int) $arr[1];
										}
									}
								}
							}
							
							$variations = array_filter(
								$variations,
								function ( $variation_id ) use ( $prev_variations_ids, $variations_depends, $current_phase_num ) {
									if ( ! isset( $variations_depends[ $variation_id ] ) ) {
										return true;
									}
									$depends_ids = $variations_depends[ $variation_id ];
									for ( $i = 0; $i < $current_phase_num - 1; $i++ ) {
										if ( isset( $prev_variations_ids[ $i ] ) && isset( $depends_ids[ $i ] ) ) {
											if ( ! in_array( $prev_variations_ids[ $i ], $depends_ids[ $i ], true ) ) {
												return false;
											}
										}
									}
									return true;
								}
							);
						}
						
						// If there's exactly one variation available, save it automatically.
						// OR if there are 0 variations (phase should be skipped), mark it as skipped.
						if ( ! empty( $variations ) && 1 === count( $variations ) ) {
							$auto_variation_id = reset( $variations );
							$auto_price        = CALC::get_price_variation( $auto_variation_id, '' );
							$auto_field_type   = get_post_meta( $auto_variation_id, 'pbc_field_type', true );
							
							$phase_title     = get_the_title( $phase_id );
							$variation_title = get_the_title( $auto_variation_id );
							
							$_SESSION[ $pbc_session_key ][ $current_phase_num ]['phase']['id']   = $phase_id;
							$_SESSION[ $pbc_session_key ][ $current_phase_num ]['phase']['name'] = $phase_title;
							$_SESSION[ $pbc_session_key ][ $current_phase_num ]['var']['id']     = $auto_variation_id;
							$_SESSION[ $pbc_session_key ][ $current_phase_num ]['var']['name']   = $variation_title;
							$_SESSION[ $pbc_session_key ][ $current_phase_num ]['var']['type']   = $auto_field_type ? $auto_field_type : 'price';
							$_SESSION[ $pbc_session_key ][ $current_phase_num ]['var']['price']  = $auto_price;
							
							// Debug log.
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( 'PBC: Auto-saved step ' . $current_phase_num . ' with variation ' . $auto_variation_id );
							}
						} elseif ( empty( $variations ) ) {
							// Mark as skipped - phase with no valid options.
							$_SESSION[ $pbc_session_key ][ $current_phase_num ]['skipped'] = true;
							
							// Debug log.
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( 'PBC: Skipped step ' . $current_phase_num . ' - no valid variations' );
							}
						} else {
							// Multiple variations available but none selected - this shouldn't happen with auto-select JS.
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( 'PBC: Step ' . $current_phase_num . ' has ' . count( $variations ) . ' variations but none selected' );
							}
						}
					}
				}
			}
		} elseif ( isset( $_GET['phase'] ) ) {
			$cstep = (int) $_GET['phase'];
		}

		if ( $shared_session_loaded ) {
			$cstep = 'calculate';
		}

		if ( ! defined( 'DOING_AJAX' ) ) {
			?>
			<div class="page-configurator <?php echo 'page-configurator-' . esc_attr( $template ); ?>">
			<?php
		} //defined('DOING_AJAX')

		if ( empty( $phases ) ) {
			?>
			<div class="error"><?php esc_html_e( 'No Phases Available', 'pbc' ); ?></div>
			</div>
			<?php
		}

		if ( 'wizard' === $template ) {
			SHOW::wizard_phases( $phases, $cstep );
		}
		?>
		<?php $parent_phase_slug = sanitize_title( get_the_title( $parent_phase ) ); ?>
		<div class="phase_detail product-<?php echo esc_html( $parent_phase_slug ); ?>">
			<form action="" method="post" name="configurator-form" id="configurator-form" data-template="<?php echo esc_html( $template ); ?>">
			<input type="hidden" name="pbc_parent_phase" value="<?php echo (int) $phase_pid; ?>">
			<?php
			wp_nonce_field( 'pbc_template_wizard_action', 'pbc_template_wizard_nonce' );
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
						if ( isset( $_SESSION[ $pbc_session_key ] ) ) {
							foreach ( $_SESSION[ $pbc_session_key ] as $step_key => $prev_var ) {
								if ( isset( $prev_var['var']['id'] ) ) {
									$var_id = (int) $prev_var['var']['id'];
									// Use step_key - 1 as index to match with 0-based dependency checking.
									$prev_variations_ids[ $step_key - 1 ] = $var_id;
								}
							}
						}

						$variations = get_posts( 'numberposts=-1&post_type=variation&meta_key=pbc_phase&meta_value=' . $phase_id . '&fields=ids&orderby=title&order=asc' );
						if ( ! empty( $variations ) && isset( $_SESSION[ $pbc_session_key ] ) ) {
							$variations_depends = array();
							foreach ( $variations as $variation_id ) {
								$depends = get_post_meta( $variation_id, 'pbc_depends', true );
								if ( ! empty( $depends ) ) {
									$variations_depends[ $variation_id ] = array();
									foreach ( $depends as $depend ) {
										$arr = explode( '|', $depend['pbc_depvar'] );
										if ( isset( $arr[0] ) && isset( $arr[1] ) ) {
											$order = array_search( (int) $arr[0], $phases_order, true );
											$variations_depends[ $variation_id ][ $order ][] = (int) $arr[1];
										}
									}
								}
							}

							$variations = array_filter(
								$variations,
								function ( $variation_id ) use ( $prev_variations_ids, $variations_depends, $cstep ) {
									if ( ! isset( $variations_depends[ $variation_id ] ) ) {
										return true;
									}
									$depends_ids = $variations_depends[ $variation_id ];
									for ( $i = 0; $i < $cstep - 1; $i++ ) {
										if ( isset( $prev_variations_ids[ $i ] ) && isset( $depends_ids[ $i ] ) ) {
											if ( ! in_array( $prev_variations_ids[ $i ], $depends_ids[ $i ], true ) ) {
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
							$selected_var = 0;
							if (
								isset( $_SESSION[ $pbc_session_key ] ) &&
								is_array( $_SESSION[ $pbc_session_key ] ) &&
								isset( $_SESSION[ $pbc_session_key ][ $cstep ] ) &&
								in_array( $_SESSION[ $pbc_session_key ][ $cstep ]['var']['id'], $variations )
							) {
								$selected_var = isset( $_SESSION[ $pbc_session_key ][ $cstep ]['var']['id'] ) ? (int) $_SESSION[ $pbc_session_key ][ $cstep ]['var']['id'] : 0;
							} else {
								$selected_var = $variations[ current( array_keys( $variations ) ) ];
							}
							if ( ! empty( $variations_section ) ) {
								SHOW::variations_content( $variations_section, $selected_var, $cstep, $template );
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
				<?php
				if ( 'vertical' === $template ) {
					SHOW::action_buttons( $phases, $cstep, $template, $shared_session_loaded );
					SHOW::calculation_summary( $pbc_session_key, $cstep, $phases );
				}
					?>
				</div>
				<div class="configurator-<?php echo 'wizard' === $template ? 'right' : 'left'; ?>">
				<?php
			} //cStep!=calculate

			if ( 'calculate' !== $cstep && 'wizard' === $template ) {
				?>
				<script type="text/javascript">
				(function() {
					function moveConfiguratorAction() {
						if (typeof jQuery !== 'undefined') {
							jQuery(document).ready(function($) {
								$('.configurator_form_action').insertAfter('.product_preview');
							});
						} else {
							// Fallback: try again after a short delay
							setTimeout(moveConfiguratorAction, 100);
						}
					}
					moveConfiguratorAction();
				})();
				</script>
				<?php
			} elseif ( 'calculate' === $cstep && 'wizard' === $template ) {
				?>
				<script type="text/javascript">
				(function() {
					function moveConfiguratorAction() {
						if (typeof jQuery !== 'undefined') {
							jQuery(document).ready(function($) {
								$('.configurator_form_action').insertBefore('.product_preview');
							});
						} else {
							// Fallback: try again after a short delay
							setTimeout(moveConfiguratorAction, 100);
						}
					}
					moveConfiguratorAction();
				})();
				</script>
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
					if ( ! empty( $_SESSION[ $pbc_session_key ] ) ) {
						$to = (int) $cstep;
						if ( 'calculate' === $cstep ) {
							$to = count( $_SESSION[ $pbc_session_key ] ) + 1;
						}
						for ( $i = 1; $i < $to; $i++ ) {
							$imgprodid = $imgprodurl = '';
							if ( isset( $_SESSION[ $pbc_session_key ][ $i ] ) ) {
								$ssVar        = $_SESSION[ $pbc_session_key ][ $i ]['var']['id'];
								$imgprodgroup = get_post_meta( $ssVar, 'pbc_imgprodgroup', true );
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
											if ( ! empty( $_SESSION[ $pbc_session_key ] ) && ! empty( $prev_var ) ) {
												foreach ( $prev_var as $s_phase_key => $sVariations ) {
													if ( isset( $prev_var[ $s_phase_key ] ) &&
													isset( $_SESSION[ $pbc_session_key ][ $s_phase_key ] ) &&
													in_array( $_SESSION[ $pbc_session_key ][ $s_phase_key ]['var']['id'], $prev_var[ $s_phase_key ] ) ) {
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
								if ( $imgprodurl ) {
									$addclass                  = '';
									$variations_images_flipped = get_option( 'variations_images_flipped' );
									if ( ! empty( $variations_images_flipped ) ) {
										for ( $j = 1; $j <= $to; $j++ ) {
											if ( isset( $_SESSION[ $pbc_session_key ][ $j ] ) && in_array( $_SESSION[ $pbc_session_key ][ $j ]['var']['id'], $variations_images_flipped ) ) {
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
					$imgprodurl = isset( $ssVar ) && ! empty( $ssVar ) ? CALC::get_image_variation_url( $_SESSION[ $pbc_session_key ], $ssVar ) : '';

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
		<?php
		if ( 'wizard' === $template ) {
			SHOW::action_buttons( $phases, $cstep, $template, $shared_session_loaded );
		}
		if ( 'wizard' === $template || ( 'vertical' === $template && 'calculate' === $cstep ) ) {
				SHOW::calculation_summary( $pbc_session_key, $cstep, $phases );
			}
			if ( 'calculate' === $cstep ) {
				?>
				<div class="configurator_result_share">
					<?php
					$session_type = isset( $_SESSION['pbc_output']['type'] ) ? sanitize_text_field( $_SESSION['pbc_output']['type'] ) : '';
					if ( ! isset( $_SESSION['pbc_output'] ) || 'success' !== $session_type ) {
						?>
						<h2><?php esc_html_e( 'Share Configuration', 'pbc' ); ?></h2>
						<div class="share_buttons">
							<button type="button" class="btn btn-share btn-whatsapp" id="pbc-share-whatsapp">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 5px;">
									<path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
								</svg>
								<?php esc_html_e( 'Share via WhatsApp', 'pbc' ); ?>
							</button>
							<button type="button" class="btn btn-share btn-email" id="pbc-share-email">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 5px;">
									<path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
								</svg>
								<?php esc_html_e( 'Share via Email', 'pbc' ); ?>
							</button>
						</div>
						<h2><?php esc_html_e( 'Client Details', 'pbc' ); ?></h2>
						<div class="email_submit_fields">
							<input type="hidden" name="pbc_session_key" value="<?php echo esc_attr( $pbc_session_key ); ?>">
							<input type="hidden" name="pbc_parent_phase" value="<?php echo (int) $phase_pid; ?>">
							<input type="text" name="email_field" placeholder="<?php esc_html_e( 'separate multiple email by comma', 'pbc' ); ?>"/>
							<input type="text" name="name_field" placeholder="<?php esc_html_e( 'Your name', 'pbc' ); ?>"/>
							<input type="text" name="phone_field" placeholder="<?php esc_html_e( 'Phone number', 'pbc' ); ?>"/>
							<input type="text" name="city_field" placeholder="<?php esc_html_e( 'Your City', 'pbc' ); ?>"/>
							<input type="text" name="state_field" placeholder="<?php esc_html_e( 'State', 'pbc' ); ?>"/>
							<textarea name="comments_field" placeholder="<?php esc_html_e( 'Your comments', 'pbc' ); ?>"></textarea>
							<?php
							$show_button_email = get_option( 'pbc_budget_show_button_email' );
							if ( 'no' !== $show_button_email ) {
								?>
								<button type="submit" name="submit" class="btn btn-submit" value="email_send"><?php esc_html_e( 'Send', 'pbc' ); ?></button>
								<?php
							}
							$show_button_pdf = get_option( 'pbc_budget_show_button_pdf' );
							if ( 'no' !== $show_button_pdf ) {
								?>
								<button type="submit" name="submit" class="btn btn-submit" value="generate_pdf"><?php esc_html_e( 'Generate Budget', 'pbc' ); ?></button>
							<?php } ?>
						</div>
						<?php
					}
					if ( isset( $_SESSION['pbc_output']['response'] ) ) {
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
