<?php
/*
 * Template Name: Budget Configurator
 */

global $pbc_helper_calc;

if ( session_id() == '' ) {
	ob_start();
	session_start();
}
if ( session_id() == '' ) {
	echo ';;--;;' . json_encode(
		array(
			'type'=>'error',
			'msg'=>'Error: Unable to initialize Session!'
		)
	);
	die( 'Error: Unable to initialize Session!' );
}
$cStep ='';
$phases = get_posts( 'numberposts=-1&post_type=phases&orderby=menu_order&order=ASC&fields=ids' );
if ( is_user_logged_in() ) {
	$user_id = get_current_user_id();
}

if ( isset( $_POST['submit'] ) ) {
	$submit = sanitize_text_field( $_POST['submit'] );
	if ( isset( $_POST[ $submit . '_phase' ] ) ) {
		$cStep = sanitize_text_field( $_POST[ $submit . '_phase' ] );
	} else {
		$cStep = 'calculate';
	}

	if ( isset( $_POST['pbc_variation'] ) && $_POST['submit']=='next' ) {
		if ( ! isset( $_SESSION['pbc_variation'] ) || ! is_array( $_SESSION['pbc_variation'] ) ) {
			$_SESSION['pbc_variation'] = array();
		}
		foreach ( $_POST['pbc_variation'] as $key => $pbc_variation ) {
			if ( ! empty( $phases ) ) {
				$price       = ''; 
				$option_name = '';
				$pricegroup  = get_post_meta( $pbc_variation, 'pbc_pricegroup', true );
				$price_var   = isset( $_POST[ 'pbc_pricevar_' . $pbc_variation ] ) ? sanitize_text_field(  $_POST[ 'pbc_pricevar_' . $pbc_variation ] ) : '';
				$meaprice    = isset( $details['pbc_meaprice'] ) ? trim( $details['pbc_meaprice'] ) : '';

				if ( isset( $user_id ) ) {
					$phase_param['var'] = $pbc_variation;
					$phase_param['pricevar'] = $price_var ? $price_var : '';
					update_user_meta( $user_id, 'pbc_phase_' . $key, $phase_param );
				}
				$price = array_search( $price_var, array_column( $pricegroup, 'pbc_meaprice', 'pbc_pricem' ) );
				if ( false === $price && isset( $pricegroup[0]['pbc_pricem'] ) ) {
					$price = $pricegroup[0]['pbc_pricem'];
				}
				$phase_id = $phases[ (int) $key - 1 ];
				$variation_title = get_the_title( $pbc_variation );
				if ( $price_var ) {
					$variation_title .= ' [' . $price_var . ']'; 
				}
				$_SESSION['pbc_variation'][ $key ]['phase']['id']   = $phase_id;
				$_SESSION['pbc_variation'][ $key ]['phase']['name'] = get_the_title( $phase_id );
				$_SESSION['pbc_variation'][ $key ]['var']['id']     = $pbc_variation;
				$_SESSION['pbc_variation'][ $key ]['var']['name']   = $variation_title;
				if ( $option_name ) {
					$_SESSION['pbc_variation'][ $key ]['var']['name'] .= ' [' . $option_name . ']';
				}
				$_SESSION['pbc_variation'][ $key ]['var']['price'] = $price;
			}
		}
		ksort( $_SESSION['pbc_variation'], SORT_NUMERIC );
	}
} elseif ( isset( $_GET['phase']) ) {
	$cStep = (int) sanitize_text_field( $_GET['phase'] );
}
if ( empty( $cStep ) ) {
	$cStep = 1;
}

if ( ! defined( 'DOING_AJAX' ) ) {
	get_header();
}
if ( ! defined( 'DOING_AJAX' ) ) {
	$preview_width = ! empty( get_option( 'pbc_preview_width' ) ) ? get_option( 'pbc_preview_width' ) : '570';
	?>
	<style>
		.btn{
			background: #c0c0c0;
			color: #333;
		}
		.btn-share{
			background: #c0c0c0;
			color: #333;
		}
		.btn {
			position: relative;
			margin: 0;
			padding-left: 14px;
			padding-right: 14px;
			padding-top: 2.8px;
			padding-bottom: 2.8px;
			background: #9a781f;
			color: white;
			font-size: 14px;
			border: none;
		}
		.btn::after {
			content: '';
			position: absolute;
			top: 0;
			width: 0;
			height: 0;
		}
		.btn:hover {
			background: black;
		}
		.next .btn::after,
		.prev .btn::after {
			border-style: solid;
		}
		.next .btn::after {
			right: -24px;
			border-width: 12px;
			border-color: transparent transparent transparent #9a781f;
		}
		.next .btn:hover::after {
			border-left-color: black;
		}
		.prev .btn::after {
			left: -24px;
			border-color: transparent #9a781f transparent transparent;
			border-width: 12px;
		}
		.prev .btn:hover::after {
			border-right-color: black;
		}
		.phase_variations select {
			padding: 5px 10px;
			border-radius: 3px;
			padding-right: 30px;
			position: relative;
			-moz-appearance: none;
			-webkit-appearance: none;
			appearance: none;
			border: none;
			background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007CB2%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
			background-repeat: no-repeat, repeat;
			background-position: right .7em top 50%, 0 0;
			background-size: .65em auto, 100%;
		}
		.variation_img img {
			min-width: 120px;
		}
		.hidden{display: none !important;}
		#content{max-width: 1200px;margin: 0 auto 40px;}
		.configurator_steps_nav{width: 100%;margin: 10px auto;float: left;padding-right: 15px;}
		.configurator_steps_nav ul{margin: 0;padding: 0; list-style: none;}
		.configurator_steps_nav li.configurator_steps{
			padding: 0;
			margin: 0;
			margin-bottom: 3px;
			background: #ededed;
			color: #000;
			position: relative;
			float: left;
			width: 12.5%;
			height: 33px;
			line-height: 40px;
			vertical-align: middle;
			text-align: center;
			word-wrap: break-word;
			padding: 5px 10px;
			border-right: 2px solid #fff;
		}
		.configurator_steps_nav li.configurator_steps .step-name{
			background: none;
			border: none;
			padding: 0;
			display: inline-block;
			height: auto;
			width: 100%;
			padding-right: 0;
			height: 30px;
			line-height: 30px;
			vertical-align: top;
			font-size: 14px;
		}
		.configurator_steps_nav li.configurator_steps .step-arrow-button {
			width: 30px;
			height: 30px;
			position: absolute;
			top: 5px;
			z-index: 1;
			right: -15px;
			-webkit-transform: rotate(-45deg);
			-moz-transform: rotate(-45deg);
			-ms-transform: rotate(-45deg);
			transform: rotate(-45deg);
			border-bottom: 2px solid #FFFFFF;
			border-right: 2px solid #FFFFFF;
			background: #EDEDED;
			display: inline-block;
		}
		.configurator_steps_nav li.configurator_steps.active {background: #949697;}
		.configurator_steps_nav li.configurator_steps.active .step-name{
			color: #fff;
			font-weight: bold;
		}
		.configurator_steps_nav li.configurator_steps.active .step-arrow-button{background: #949697;}
		.configurator-left {
			width: 50%;
			float: left;
		}
		.configurator-right {
			display: block;
			width: 49%;
			float: right;
			vertical-align: top;
		}
		.configurator-left .phase_title{text-transform: uppercase;font-size: 20px;}
		.phase_variations{
			margin-top: 40px;
		}
		.phase_variations ul{margin: 0; list-style: none;}
		.phase_variations ul li.variation_list {
			width: 24%;
			display: inline-block;
			font-size: 14px;
			margin-bottom: 20px;
			text-align: center;
			vertical-align: top;
		}
		.product_preview {
			position: relative;
			text-align: left;
			max-width: <?php echo esc_html( $preview_width ); ?>px;
			overflow: hidden;
			display: inline-block;
			vertical-align: top;
		}
		.product_preview.wrap-left{margin-right: 20px;}
		.product_preview .image-wrap img:first-child{position: relative;}
		.product_preview .image-wrap img{width: 100%;max-width: <?php echo esc_html( $preview_width ); ?>px;position: absolute;top: 0;left: 0;}
		.configurator_form_action {
			text-align: right;
			clear: both;
			margin: 20px 0;
		}
		.configurator_summary {
			clear: both;
			border: 1px solid #949697;
			padding: 10px;
			margin: 0 auto;
			max-width: 600px;
			min-width: 600px;
			display: inline-block;
		}
		.configurator_result_share{max-width: 400px;margin: 20px auto;text-align: center;}
		.email_submit_fields{margin-top: 20px;}
		.configurator_summary .title {
			text-transform: uppercase;
			font-size: 20px;
			margin-bottom: 10px;
		}
		.configurator_summary table{width: 100%;border: 0px;}
		.configurator_summary table td {
			font-size: 16px;
		}
		.configurator_summary table td.price {
			text-align: right;
		}
		.configurator_summary tr.phase-total_price td {
			padding-top: 15px;
		}
		.configurator_form_action .prev, .configurator_form_action .next{display: inline-block;}
		.status_loader.fixed{position: fixed;width: 100%;height: 100%;
			/*background: rgba(0, 0, 0, 0.8);*/
			vertical-align: middle;text-align: center;top: 0;z-index: 9999;left:0;}
		.status_loader.product_preview_status.fixed{position: absolute;}
		.status_loader.fixed > div {
			position: relative;
			top: 50%;
			transform: translateY(-50%);
			z-index: 9999;
			color: #fff;
			border-radius: 100%;
			display: inline-block;
			-webkit-animation: bouncedelay 1.4s infinite ease-in-out;
			animation: bouncedelay 1.4s infinite ease-in-out;
			-webkit-animation-fill-mode: both;
			animation-fill-mode: both;
		}
		img.flipped{
			-moz-transform: scaleX(-1);
			-o-transform: scaleX(-1);
			-webkit-transform: scaleX(-1);
			transform: scaleX(-1);
			filter: FlipH;
			-ms-filter: "FlipH";
		}
		.email_submit_fields input {
			width: 325px;
		}
		.configurator_login{
			clear: both;
			width: 49%;
			float: right;
			margin: 20px 0;
		}
		.configurator_login .form_wrapper{max-width: 400px;border: 1px solid;padding: 10px;}
		.configurator_login .et_pb_contact_submit{border: 2px solid transparent;background: rgba(0, 0, 0, 0.05);}
		.configurator_login .et_pb_contact_submit:hover{background: transparent;border: 2px solid #A08621;}
		@media (max-width:768px) {
			.configurator_steps_nav{padding-right: 0px;}
			.configurator_steps_nav li.configurator_steps{overflow: hidden;padding: 5px;}
			.configurator_steps_nav li.configurator_steps .step-arrow-button{display: none;}
		}
		.configurator_login .message,.configurator_login h2 {
			margin: 30px 20px;
		}
		.configurator_login h2 {
			margin: 20px 20px 0;
		}
		.configurator_login {
		text-align: center;
		}
		.configurator_login img {
			width: 300px;
		}
		.page-header {
		margin-top: 15px;
		}
	</style>
	<?php
	$queried_object = get_queried_object();
	?>
	<div id="content" class="clearfix row">
		<div id="main" class="col-sm-12 clearfix" role="main">
			<div class="page-header">
				<h1><?php echo esc_html( get_the_title( $queried_object->ID ) ); ?></h1>
			</div>
			<div class="page-content">
					<p><?php
						$post_object = get_post( $queried_object->ID );
						echo apply_filters( 'the_content', $post_object->post_content );
					?></p>
			</div>
		<div class="page-configurator">
	<?php
} //defined('DOING_AJAX')

if ( ! empty( $phases ) ) {
	?>
	<div class="configurator_steps_nav" id="configurator_steps_nav">
		<ul>
		<?php
		$steps = 1;
		foreach ( $phases as $phase ) {
			?>
			<li class="configurator_steps step-<?php echo esc_attr( $steps );?> <?php if ( $cStep == $steps ) { echo 'active'; } ?>">
				<div class="stepContainer">
						<div class="step-name"><?php echo esc_html( get_the_title( $phase ) ); ?></div>
						<span class="step-arrow-button"></span>
				</div>
			</li>
			<?php
			$steps++;
		}
		?>
		</ul>
	</div>
	<div class="phase_detail">
		<form action="" method="post" name="configurator-form" id="configurator-form">
		<?php
		if ( $cStep !='calculate' ) {
			$phase_id = $phases[((int)$cStep-1)];?>
			<div class="configurator-left">
				<div class="phase_title"><?php echo get_the_title( $phase_id ); ?></div>
				<div class="phase_variations">
					<?php
					$variations = get_posts( 'numberposts=-1&post_type=variation&meta_key=pbc_phase&meta_value=' .$phase_id . '&fields=ids&orderby=title&order=asc' );
					if ( ! empty( $variations ) ) {
						foreach ( $variations as $key => $variation ) {
							$pbc_depends = get_post_meta( $variation, 'pbc_depends', true );
							if ( ! empty( $pbc_depends ) ) {
								$prevVar = array();
								foreach ( $pbc_depends as $deps ) {
									$arr = explode('|', $deps['pbc_depvar']);
									if(!empty($arr[0]) && !empty($arr[1])){
										$prevVar[(int)$arr[0]][] = $arr[1];
									}
								}
								if ( $cStep != 1 && !empty($_SESSION['pbc_variation'] ) ) {
									foreach ( $_SESSION['pbc_variation'] as $sPhaseKey => $sVariations ) {
										if ( isset($prevVar[$sPhaseKey]) && ! in_array($_SESSION['pbc_variation'][$sPhaseKey]['var']['id'], $prevVar[$sPhaseKey] ) ) {
											unset($variations[$key]);
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
							$term_list = (array) wp_get_post_terms(
								$variation_id,
								'variation_tag',
								array(
									'fields' => 'all'
								)
							);
							$variations_section[] = array(
								'id'      => $variation_id,
								'section' => isset( $term_list[0]->name ) ? $term_list[0]->name : '',
								'title'   => get_the_title( $variation_id ),
							);
						}

						// Order by sections.
						$sections = array_column( $variations_section, 'section' );
						array_multisort( $sections, SORT_ASC, $variations_section );

						// Show public.
						if ( 
							isset( $_SESSION['pbc_variation'] ) && 
							is_array( $_SESSION['pbc_variation'] ) && 
							isset( $_SESSION['pbc_variation'][ $cStep ] ) && 
							in_array( $_SESSION['pbc_variation'][ $cStep ]['var']['id'], $variations )
						) {
							$sVar = $_SESSION['pbc_variation'][ $cStep ]['var']['id'];
						} else {
							if ( isset( $user_id ) ) {
								$pbc_phase = get_user_meta( $user_id, 'pbc_phase_' . $cStep, true );
								if ( ! empty( $pbc_phase ) && ! empty( $pbc_phase['var'] ) ) {
									$sVar = $pbc_phase['var'];
								}
							}
							if ( empty( $sVar ) ) {
								$sVar = $variations[current(array_keys($variations))];
							}
						}
						if ( ! empty( $variations_section ) ) {
							?>
							<ul>
								<?php
								$actual_variation_tag = '';
								foreach ( $variations_section as $variation_data ) {
									$variation_id = (int) $variation_data['id'];
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
											?>
											<input type="radio" class="pbc_variation" name="pbc_variation[<?php echo $cStep;?>]" value="<?php echo $variation_id; ?>" <?php if ( $variation_id == $sVar ) { echo 'checked="checked"'; } ?>/> <?php echo esc_html( $variation_data['title'] ); ?>
										</label>
											<?php
											$priceVar = array();
											$pricegroup = get_post_meta( $variation_id, 'pbc_pricegroup', true );
											if ( ! empty( $pricegroup ) && isset( $pricegroup[0]['pbc_meaprice'] ) ) { ?>
												<div class="pbc_pricevarwrap">
													<select class="pbc_pricevar" name="pbc_pricevar_<?php echo $variation_id;?>">
													<?php foreach($pricegroup as $key => $details){
														if ( ! empty( $details['pbc_meaprice'] ) && isset( $details['pbc_pricem'] ) ) {
															echo '<option value="' . $details["pbc_meaprice"] . '">';
															echo esc_html( $details["pbc_meaprice"] );
															echo '</option>';
														}
													}?>
													</select>
												</div>
												<?php
											}?>
											<?php
											$pbc_descopt = get_post_meta( $variation_id, 'pbc_descopt', true );
											if ( $pbc_descopt ) {
												?>
												<p class="pbc_descopt"><?php echo wpautop( $pbc_descopt ); ?></p>
												<?php
											}
											?>
									</li>
								<?php } ?>
							</ul>
							<?php
						}
					} else {
						?>
						<div class="error"><?php _e( 'No Variations Available', 'pbc' ); ?></div>
						<?php
					}
					?>
				</div>
				<?php // Variations Description.
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
						$index_var++;
					}
					echo '</div>';
				}
				?>

				<div class="phase_content">
					<?php
					$post_object = get_post( $phase_id );
					if ( ! empty( $post_object->post_content ) ) {
						echo $post_object->post_content;
					}
					?>
				</div>
			</div>
		<div class="configurator-right">
		<?php }//cStep!=calculate?>

		<?php if ( $cStep != 'calculate' ) {
			?>
			<script type="text/javascript">jQuery('.configurator_form_action').insertAfter('.product_preview');</script>
			<?php
		} elseif ( $cStep =='calculate' ) {
			?>
			<script type="text/javascript">jQuery('.configurator_form_action').insertBefore('.product_preview');</script>
			<?php
		}
		?>
		<div class="product_preview<?php if ( $cStep =='calculate'){ echo ' wrap-left'; } ?>">
			<div class="image-wrap">
					<?php
					if ( isset( $_SESSION['pbc_variation'] ) && ! empty( $_SESSION['pbc_variation'] ) ) {
						$to = (int) $cStep;
						if ( $cStep == 'calculate') {
							$to = count( $_SESSION['pbc_variation'] ) + 1;
						}
						for ( $i = 1; $i < $to; $i++ ) {
							$imgprodid = $imgprodurl = '';
							if ( isset( $_SESSION['pbc_variation'][ $i ] ) ) {
								$ssVar = $_SESSION['pbc_variation'][ $i ]['var']['id'];
								$imgprodgroup = get_post_meta( $ssVar, 'pbc_imgprodgroup', true );
								if ( ! empty( $imgprodgroup ) ) {
									foreach ( $imgprodgroup as $deps ) {
										if(isset($deps['pbc_depvarimgprod']) && !empty($deps['pbc_depvarimgprod']) && isset($deps['pbc_imgprod']) ) {
											$prevVar = array();
											foreach ( $deps['pbc_depvarimgprod'] as $depvarimgprod ) {
												$imgprod_arr = explode('|', $depvarimgprod);
												if ( ! empty( $imgprod_arr[0] ) && ! empty( $imgprod_arr[1] ) ) {
													$prevVar[ (int)$imgprod_arr[0] ][] = $imgprod_arr[1];
												}
											}
											if(!empty($_SESSION['pbc_variation']) && !empty($prevVar))
											{
												foreach($prevVar as $sPhaseKey => $sVariations)
												{
													if(isset($prevVar[$sPhaseKey]) &&
													isset($_SESSION['pbc_variation'][$sPhaseKey]) &&
													in_array($_SESSION['pbc_variation'][$sPhaseKey]['var']['id'], $prevVar[$sPhaseKey]))
													{
														$imgprodid = $deps['pbc_imgprod'][0];
													}else{
														$imgprodid = '';
														break;
													}
												}
											}
										}elseif((!isset($deps['pbc_depvarimgprod']) || empty($deps['pbc_depvarimgprod'])) && isset($deps['pbc_imgprod']) ){
											$imgprodid = $deps['pbc_imgprod'][0];
											break;
										}
										if($imgprodid)
											break;
									}
								}
								if ( isset( $imgprodid ) && $imgprodid ) {
									$imgprodurl = wp_get_attachment_image_src( $imgprodid, 'full', true );
								}
								if ( isset( $imgprodurl ) && $imgprodurl ) {
									$addclass = '';
									$variations_images_flipped = get_option( 'variations_images_flipped' );
									if ( ! empty( $variations_images_flipped ) ) {
										for ( $j = 1; $j <= $to; $j++ ) {
											if(isset($_SESSION['pbc_variation'][$j]) && in_array($_SESSION['pbc_variation'][$j]['var']['id'], $variations_images_flipped)){
												$addclass = 'flipped';
											}
										}
									}
									?>
									<img phaseid="<?php echo $i;?>" src="<?php echo $imgprodurl[0];?>" class="<?php echo $addclass;?>" alt="product image"/>
									<?php
								}
							}
						}
					}
					$imgprodurl = $pbc_helper_calc->get_image_variation_url( $_SESSION['pbc_variation'], $sVar );

					if ( $imgprodurl ) {
						$variations_images_flipped = get_option('variations_images_flipped');
						$addclass                  = '';
						if ( ! empty( $variations_images_flipped ) && in_array( $sVar, $variations_images_flipped ) ) {
							$addclass = 'flipped';
						}
						?>
						<img phaseid="<?php echo $cStep;?>" src="<?php echo $imgprodurl; ?>" class="<?php echo $addclass;?>" alt="product image"/>
						<?php
					}
					?>
			</div>
			<div class="status_loader product_preview_status fixed hidden"></div>
		</div>
			<div class="configurator_form_action">
				<?php
				if ( $cStep == 1 ) {
					$prev_step   = '';
					$prev_button = '';
				} elseif ( $cStep == 'calculate' ) {
					$prev_step   = count( $phases );
					$prev_button = __( 'Back', 'pbc' );
				} else {
					$prev_step   = $cStep - 1;
					$prev_button = __( 'Back', 'pbc' );
				}

				if ( $cStep == 'calculate' ) {
					$next_step   = 'calculate';
					$next_button = '';
				} elseif ( $cStep == count( $phases ) ) {
					$next_step   = 'calculate';
					$next_button = __( 'Calculate', 'pbc' );
				}else{
					$next_step   = $cStep + 1;
					$next_button = __( 'Next', 'pbc' );
				}
				?>
				<input type="hidden" name="pbc_current_phase" value="<?php echo $cStep;?>"/>
				<?php if($prev_step && $prev_button){?>
				<div class="prev <?php if(empty($prev_step)) echo 'hidden';?>">
					<input type="hidden" name="prev_phase" value="<?php echo $prev_step;?>"/>
					<button type="submit" name="submit" value="prev" class="btn btn-prev"><?php echo $prev_button;?></button>
				</div>
				<?php }?>
				<div class="next">
					<?php if($next_step){?><input type="hidden" name="next_phase" value="<?php echo $next_step;?>"/><?php }?>
					<?php if($next_button){?><button type="submit" name="submit" value="next" class="btn btn-next"><?php echo $next_button;?></button><?php }?>
				</div>
			</div>
			<div class="configurator_summary">
				<?php
				if ( isset( $_SESSION ) && isset($_SESSION['pbc_variation']) && is_array( $_SESSION['pbc_variation'] ) ) {
					?>
					<h2 class="title"><?php _e( 'Actual Configuration', 'pbc' ); ?></h2>
					<table>
						<?php
						$show_prices = get_option( 'pbc_budget_show_prices' );
						if( $cStep == 'calculate' ){
							$count       = count( $phases );
							$total_price = 0;
						} else {
							$count = $cStep;
						}
						for ( $i = 1; $i <= $count; $i++ ) {
							if ( ! isset( $_SESSION['pbc_variation'][ $i ] ) ) {
								continue;
							}
							$phaseKey  = $i;
							$varId     = $_SESSION['pbc_variation'][$i]['var']['id'];
							$varName   = $_SESSION['pbc_variation'][$i]['var']['name'];
							$varPrice  = ! empty( $_SESSION['pbc_variation'][$i]['var']['price'] ) ? $_SESSION['pbc_variation'][$i]['var']['price'] : 0;
							$phaseName = $_SESSION['pbc_variation'][$i]['phase']['name'];
							
							if ( $cStep == 'calculate' ) {
								$total_price += (double) $varPrice;
							}
							?>
							<tr class="variation_selected phase-<?php echo $phaseKey;?>">
								<td class="name"><?php echo $phaseKey.'. '.$phaseName.': '.$varName;?></td>
								<td class="price">
									<?php
									if ( $varPrice && 'no' !== $show_prices ) {
										echo number_format( $varPrice, 2, ',', '.' ) . ' €';
									}
									?>
								</td>
							</tr>
							<?php
						}
						if ( $cStep == 'calculate' && 'no' !== $show_prices ) { ?>
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
								<td class="name"><?php _e( 'VAT not included', 'pbc' );?></td>
								<td class="price"></td>
							</tr>
						<?php }?>
					</table>
					<?php
				}
				?>
			</div>
			<?php 
			if($cStep == 'calculate'){?>
				<div class="configurator_result_share">
					<?php if(!isset($_SESSION['pbc_output']) || $_SESSION['pbc_output']['type'] != 'success'){?>
					<h2><?php esc_html_e( 'Send budget to email', 'pbc' ); ?></h2>
					<div class="email_submit_fields">
						<h2><?php esc_html('Send the budget to an email:', 'pbc' ); ?></h2>
						<input type="text" name="email_field" placeholder="<?php _e('separate multiple email by comma','pbc');?>"/><br/>
						<input type="text" name="name_field" style="width:150px;" placeholder="<?php _e('Your name','pbc');?>"/>
						<input type="text" name="phone_field" style="width:150px;" placeholder="<?php _e('Phone number','pbc');?>"/><br/>
						<input type="text" name="city_field" style="width:150px;" placeholder="<?php _e('Your City','pbc');?>"/>
						<input type="text" name="state_field" style="width:150px;" placeholder="<?php _e('State','pbc');?>"/><br/>
						<button type="submit" name="submit" class="btn btn-submit" value="email_send"><?php _e('Send','pbc');?></button>
					</div>
					<?php }?>
					<?php if(isset($_SESSION['pbc_output'])){?>
						<div class="result_submit_action <?php echo $_SESSION['pbc_output']['type'];?>">
								<?php echo $_SESSION['pbc_output']['response'];?>
						</div>
					<?php unset($_SESSION['pbc_output']);}?>
				</div>
			<?php }?>
		<?php
		if ( $cStep != 'calculate') {
			// Banner.
			do_action( 'pbc_banner_after_setup' );
		?>
		</div>
		<?php }//$cStep != 'calculate'?>
		</form>
	</div>

	<?php
	if ( ! defined( 'DOING_AJAX' ) ) {
		$show_prices = get_option( 'pbc_budget_show_prices' );
	?>
	<script type="text/javascript">
	jQuery(function($){
			$(document).on('click', 'input[type=radio].pbc_variation', function(){
				$('.product_preview').find('.product_preview_status').removeClass('hidden').html('<div><img src="<?php echo WPPBC_PLUGIN_URL;?>/assets/loading.gif"/></div>').show();
				var cPhase = $('input[name=pbc_current_phase]').val();
				var show_prices = '<?php echo $show_prices; ?>';
				$('.phase_descvar .actived').addClass('hidden').removeClass('actived');
				$('.phase_descvar .descvar_' + $(this).val() ).addClass('actived').removeClass('hidden');
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php');?>',  //server script to process data
					type: 'POST',
					data: $('#configurator-form').serialize()+'&current_phase='+cPhase+'&action=variation_selected',
					dataType: "html",
					success: function(response) {
						var resArr = response.split(';;--;;');
						var obj = jQuery.parseJSON(resArr[1]);
						if(obj.type == 'error'){
							$('.product_preview').find('.product_preview_status').html('<div>'+obj.msg+'</div>').show().delay(4000, function(){
								window.setTimeout( function(){
									$('.product_preview').find('.product_preview_status').html('').addClass('hidden');
								}, 1000 );
							});
						} else
							if ( obj.type == 'success' ) {
							$('.product_preview').find('.product_preview_status').addClass('hidden');
							if(obj.url){
								if($('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').length != 0){
									$('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').attr('src',obj.url);
								} else {
									if ( obj.flipped ) {
										var className = 'flipped';
									} else {
										className = '';
									}
									$('.product_preview').find('.image-wrap').append('<img phaseid="'+cPhase+'" class="'+className+'" src="'+obj.url+'" alt="product image"/>').show();
								}
							}
							else
								if($('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').length != 0){
										$('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').remove();
								}
							if(obj.flipped){
								$('.product_preview').find('.image-wrap img').each(function(){
										if(!$(this).hasClass('flipped'))
										$(this).addClass('flipped');
								});
							}
							else{
								$('.product_preview').find('.image-wrap img').each(function(){
										if($(this).hasClass('flipped'))
										$(this).removeClass('flipped');
								});
							}
							if(obj.option || obj.price){
								if($('.variation_selected.phase-'+cPhase).length == 0){
									html_append = '<table><tr class="variation_selected phase-'+cPhase+'"><td class="name">'+obj.option+'</td><td class="price">';
									if ( show_prices !== 'no' ) {
										html_append += obj.price;
									}
									html_append += '</td></tr></table>';
									$('.configurator_summary').append( html_append );
								} else {
										$('.variation_selected.phase-'+cPhase+' td.name').html(obj.option);
									if ( show_prices !== 'no' ) {
										$('.variation_selected.phase-'+cPhase+' td.price').html(obj.price);
									}
								}
							}

						}
					}
				});
			});
			$(document).on('click', 'select[class=pbc_pricevar]', function(){
				$(this).parent().parent().find('input.pbc_variation').prop("checked", true);
			});
			$(document).on('change', 'select[class=pbc_pricevar]', function(){
				$('.product_preview').find('.product_preview_status').removeClass('hidden').html('<div><img src="<?php echo WPPBC_PLUGIN_URL;?>/assets/loading.gif"/></div>').show();
				var cPhase = $('input[name=pbc_current_phase]').val();
				var select_pricevar = $(this).parent().parent().find('input.pbc_variation');
				var show_prices = '<?php echo $show_prices; ?>';
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php');?>',  //server script to process data
					type: 'POST',
					data: $('#configurator-form').serialize()+'&current_phase='+cPhase+'&action=variation_selected',
					dataType: "html",
					success: function(response) {
							var resArr = response.split(';;--;;');
							var obj = jQuery.parseJSON(resArr[1]);
							if(obj.type == 'error'){
								$('.product_preview').find('.product_preview_status').html('<div>'+obj.msg+'</div>').show().delay(4000, function(){
									window.setTimeout( function(){
											$('.product_preview').find('.product_preview_status').html('').addClass('hidden');
									}, 1000 );
								});
							}else if(obj.type == 'success'){
								$('.product_preview').find('.product_preview_status').addClass('hidden');
								select_pricevar.prop("checked", true);
								if(obj.url){
									if($('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').length != 0){
											$('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').attr('src',obj.url);
									}else{
											if(obj.flipped)
												var className = 'flipped';
											else className = '';
											$('.product_preview').find('.image-wrap').append('<img phaseid="'+cPhase+'" class="'+className+'" src="'+obj.url+'" alt="product image"/>').show();
									}
								}
								else
									if($('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').length != 0){
											$('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').remove();
									}
								if(obj.flipped){
									$('.product_preview').find('.image-wrap img').each(function(){
											if(!$(this).hasClass('flipped'))
											$(this).addClass('flipped');
									});
								}
								else{
									$('.product_preview').find('.image-wrap img').each(function(){
											if($(this).hasClass('flipped'))
											$(this).removeClass('flipped');
									});
								}
								if(obj.option || obj.price){
									if($('.variation_selected.phase-'+cPhase).length == 0){
										html_price = '';
										if ( show_prices !== 'no' ) {
											html_price = '<td class="price">'+obj.price+'</td>';
										}
										$('.configurator_summary').append('<table><tr class="variation_selected phase-'+cPhase+'"><td class="name">'+obj.option+'</td>'+html_price+'</tr></table>');
									} else {
										$('.variation_selected.phase-'+cPhase+' td.name').html(obj.option);
										if ( show_prices !== 'no' ) {
											$('.variation_selected.phase-'+cPhase+' td.price').html(obj.price);
										}
									}
								}

							}
					}
				});
			});
			$(document).on('click', 'button[name=submit]', function(e){
				var submit_val = $(this).val();
				var form_id = 'configurator-form';
				e.preventDefault();
				$(document).find('.status_loader.phase_detail_loader').removeClass('hidden').html('<div><img src="<?php echo WPPBC_PLUGIN_URL;?>/assets/loading.gif"/></div>').show();
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php');?>',  //server script to process data
					type: 'POST',
					data: $('#'+form_id).serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&submit='+submit_val+'&action=configurator_submit',
					dataType: "html",
					success: function(response) {
						$('.page-configurator').html(response);
						if(
							'<?php echo $next_step;?>' != 'calculate' &&
							(submit_val == 'prev' || submit_val == 'next') && $(document).find('input[type=radio].pbc_variation').length == 0
						)
						{
							$(document).find('button[name=submit][value='+submit_val+']').trigger('click');
						}else{
							$(document).find('.status_loader.phase_detail_loader').html('').addClass('hidden');
							//$('.page-configurator').html(response);
							if($(document).find('.result_submit_action').length > 0){
								$(document).find('.result_submit_action').show().delay(3000).fadeOut(400);
							}
						}
					}
				});
			});
			$(document).on('submit', '#configurator_login_form', function(e){
			e.preventDefault();
			var form_id = 'configurator_login_form';
				$(document).find('.status_loader.phase_detail_loader').removeClass('hidden').html('<div><img src="<?php echo WPPBC_PLUGIN_URL;?>/assets/loading.gif"/></div>').show();
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php');?>',  //server script to process data
					type: 'POST',
					data: $('#'+form_id).serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&action=configurator_login',
					dataType: "html",
					success: function(response) {
							var arr = response.split(';;-;;');
							if(arr[1]=='error'){
								$(document).find('.status_loader.phase_detail_loader').html('').addClass('hidden');
								$('#'+form_id).find('.message').html(arr[2]).show().delay(3000).fadeOut(400);
							}else{
								location.reload(true);
								$('.page-configurator').html(response);
								$(document).find('.status_loader.phase_detail_loader').html('').addClass('hidden');
							}
					}
				});
			});
	});
	</script>
	<?php }//defined('DOING_AJAX')?>
	<div class="status_loader phase_detail_loader fixed hidden"></div>
<?php
}?>
<?php if(!defined('DOING_AJAX')){?>
		</div>
	</div>
</div>
<?php
}//defined('DOING_AJAX')

if ( ! defined( 'DOING_AJAX' ) ) {
	get_footer();
}
