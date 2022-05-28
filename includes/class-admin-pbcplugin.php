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

/**
 * Class for admin
 */
class Admin_PBCPlugin {
	/**
	 * Construct and intialize
	 */
	public function __construct() {
		// Initial stuff.
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_footer', array( $this, 'pbc_admin_scripts' ) );

		// Custom Post types stuff.
		add_action( 'admin_menu', array( $this, 'pbc_add_admin_menus' ), 1 );
		add_action( 'init', array( $this, 'pbc_register_cpt' ) );
		add_filter( 'rwmb_meta_boxes', array( $this, 'pbc_metabox_variation' ) );
		add_filter( 'add_meta_boxes_enquiry', array( $this, 'pbc_metabox_enquiry' ) );

		add_filter( 'disable_months_dropdown', array( $this, 'disable_months_dropdown' ), 10, 2 );
		add_filter( 'manage_edit-phases_columns', array( $this, 'add_new_phases_columns' ) );
		add_action( 'manage_phases_posts_custom_column', array( $this, 'manage_phases_columns' ), 10, 2 );

		add_filter( 'manage_edit-variation_columns', array( $this, 'add_new_var_columns' ) );
		add_action( 'manage_variation_posts_custom_column', array( $this, 'manage_var_columns' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'pbc_admin_posts_filter' ) );
		add_filter( 'parse_query', array( $this, 'pbc_posts_filter' ) );

		add_filter( 'template_include', array( $this, 'pbc_custom_page_template' ), 99 );
		add_action( 'wp_ajax_variation_selected', array( $this, 'variation_selected_action_callback' ) );
		add_action( 'wp_ajax_nopriv_variation_selected', array( $this, 'variation_selected_action_callback' ) );
		add_action( 'wp_ajax_configurator_submit', array( $this, 'configurator_submit_action_callback' ) );
		add_action( 'wp_ajax_nopriv_configurator_submit', array( $this, 'configurator_submit_action_callback' ) );
		add_action( 'wp_ajax_configurator_login', array( $this, 'configurator_login_action_callback' ) );
		add_action( 'wp_ajax_nopriv_configurator_login', array( $this, 'configurator_login_action_callback' ) );

		// On variation-lists admin screen.
		add_filter( 'views_edit-variation', array( $this, 'pbc_add_print_pdf_button' ) );
		add_action( 'admin_head-edit.php', array( $this, 'pbc_move_print_pdf_button' ) );
		add_action( 'wp_ajax_print_pdf', array( $this, 'print_pdf_action_callback' ) );

		// Creates license activation.
		register_activation_hook( WPPBC_PLUGIN, array( $this, 'license_instance_activation' ) );
		// Check for external connection blocking.
		add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );

		if ( 'Activated' !== get_site_option( 'pbc_license_activated' ) ) {
			add_action( 'admin_notices', array( $this, 'inactive_notice' ) );
		}
	}

	/**
	 * Initialize
	 */
	public function init() {
		/**
		* Composer Library dependencies
		*/
		require_once WPPBC_PLUGIN_PATH . 'vendor/autoload.php';

		/**
		* Image Sizes
		*/
		add_image_size( 'pbc_icon', 150, 230, false );
		add_image_size( 'pbc_product', 570, 460, true );
	}
	/**
	 * PBC Admin Scripts
	 */
	public function pbc_admin_scripts() {
		$screen = get_current_screen();
		if ( ! empty( $screen ) && ( 'pbc_menu' === $screen->parent_base ) ) {
			wp_enqueue_script( 'post' );
			$this->plugin_admin_scripts();
		}
		return null;
	}

	public function plugin_admin_scripts() {
		?>
		<style>
			.postcontent-left, .postcontent-right{width: 46%;margin-right: 2%;display: inline-block;vertical-align: top;}
			fieldset{ padding: 2px 0;margin-bottom:5px;}
			fieldset label.inline{min-width: 140px;display: inline-block;}
			fieldset label.block{width: 100%;display: block;margin-bottom: 2px;}
			.save_bar{padding: 0 10px;text-align: right;margin-bottom: 20px;}
			.content.left-content, .content.right-content {width: 45%;display: inline-block;vertical-align: top;}
			.content.left-content { border-right: 1px solid #EEEEEE;margin-right: 2%;}
			.phases-lists-table th, .phases-lists-table td{text-align: left;}
			.phases-col{width: 60%;}
			.order-col, .variations-col{width: 20%;}
		</style>

		<script type="text/javascript">
		jQuery(function($){
			$(document).on('click', '.select-image', function(event){
				var current_button = $(this);
	            event.preventDefault();

	            // check for media manager instance
	            if(wp.media.frames.pbc) {
	                wp.media.frames.pbc.open();
	                return;
	            }
	            // configuration of the media manager new instance
	            wp.media.frames.pbc = wp.media({
	                title: 'Select image',
	                multiple: false,
	                library: {
	                    type: 'image'
	                },
	                button: {
	                    text: 'Use selected image'
	                }
	            });

	            // Function used for the image selection and media manager closing
	            var gk_media_set_image = function() {
	                var selection = wp.media.frames.pbc.state().get('selection');

	                // no selection
	                if (!selection) {
	                    return;
	                }

	                // iterate through selected elements
	                selection.each(function(attachment) {
	                    var url = attachment.attributes.url;
	                    current_button.prev('[name=pdf_image_selected]').val(url);
	                });
	            };

	            // closing event for media manger
	            wp.media.frames.pbc.on('close', gk_media_set_image);
	            // image selection event
	            wp.media.frames.pbc.on('select', gk_media_set_image);
	            // showing media manager
	            wp.media.frames.pbc.open();
			});
		});
		</script>
		<?php
	}

	/**
	 * Registers menu admin
	 *
	 * @return void
	 */
	public function pbc_add_admin_menus() {
		// Add custom admin menu.
		add_menu_page(
			__( 'Product Budget Configurator', 'pbc' ),
			'PBC',
			'manage_options',
			'pbc_menu',
			array( $this, 'pbc_display_admin_page' ),
			'dashicons-tagcloud',
			2
		);

		$submenu_pages = array(
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Product Budget Configurator', 'pbc' ),
				'menu_title'  => __( 'Settings', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'pbc_menu',
				'function'    => array( $this, 'pbc_display_admin_page' ),
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Phases of Configurator', 'pbc' ),
				'menu_title'  => __( 'Phases', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=phases',
				'function'    => null,
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Variations in Phases', 'pbc' ),
				'menu_title'  => __( 'Variations', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=variation',
				'function'    => null,
			),
			// Post Type :: View All Posts.
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Enquiries Received', 'pbc' ),
				'menu_title'  => __( 'Enquiries', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=enquiry',
				'function'    => null, // Doesn't need a callback function.
			),

		);

		// Add each submenu item to custom admin menu.
		foreach ( $submenu_pages as $submenu ) {
			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$submenu['menu_slug'],
				$submenu['function']
			);
		}
   }

	/**
	 * Page settings with metaboxes
	 *
	 * @return void
	 */
   public function pbc_display_admin_page(){

		if ( isset( $_POST['form_submit'] ) ) {
			if ( isset( $_POST['select_budget_page'] ) ) {
				update_option( 'pbc_budget_configurator_page', $_POST['select_budget_page'] );
				$update = __( 'Successfully Saved!', 'pbc' );
			}
			if ( isset( $_POST['option_show_prices'] ) ) {
				update_option( 'pbc_budget_show_prices', $_POST['option_show_prices'] );
				$update = __( 'Successfully Saved!', 'pbc' );
			}
			if ( isset( $_POST['pdf_image_selected'] ) ){
				update_option( 'pbc_pdf_image_selected', $_POST['pdf_image_selected'] );
				$update = __( 'Successfully Saved!', 'pbc' );
			}
			$variations_images_flipped = isset( $_POST['variations_images_flipped'] ) ? $_POST['variations_images_flipped'] : array('');
			update_option( 'variations_images_flipped', $variations_images_flipped );
			$admin_email_notification = isset( $_POST['admin_email_notification'] ) ? $_POST['admin_email_notification'] : array('');
			update_option( 'pbc_admin_email_notification', $admin_email_notification );
		}

		if ( isset( $_POST['submit_license'] ) ) {
			$license_apikey = isset( $_POST['pbc_license_apikey'] ) ? sanitize_text_field(  $_POST['pbc_license_apikey'] ) : '';
			$license_product_id = isset( $_POST['pbc_license_product_id'] ) ? sanitize_text_field(  $_POST['pbc_license_product_id'] ) : '';

			update_option( 'pbc_license_apikey', $license_apikey );
			update_option( 'pbc_license_product_id', $license_product_id );
			$this->validate_license( $_POST );
		}
		?>
		<div class='wrap'>
			<h2><?php echo $GLOBALS['title'] ?> - <?php esc_html_e( 'Global Settings', 'pbc' ); ?></h2>

			<?php if(isset($update)){?>
				<div id="message" class="updated fade"><?php echo $update;?></div>
			<?php }?>
			<?php if(isset($error)){?>
				<div id="message" class="error"><?php echo $error;?></div>
			<?php }?>

			<div id="poststuff">
				<div id="post-body">
					<div class="postcontent-left">
						<?php
						add_meta_box(
							'phases_lists_meta_box',
							__( 'All Phases Lists', 'pbc' ),
							array(
								$this,
								'phases_lists_meta_box_callback',
							),
							'pbc_import_left'
						);

						add_meta_box(
							'license_meta_box',
							__( 'License', 'pbc' ),
							array(
								$this,
								'license_meta_box_callback',
							),
							'pbc_import_left'
						);

						do_meta_boxes(
							'pbc_import_left',
							'advanced',
							null
						);
						?>
					</div>
					<div class="postcontent-right">
						<?php
						add_meta_box(
							'general_settings_meta_box',
							__( 'General Settings', 'pbc' ),
							array(
								$this,
								'general_settings_meta_box_callback',
							),
							'pbc_import_right'
						);
						do_meta_boxes(
							'pbc_import_right',
							'advanced',
							null
						);
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Import Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function phases_lists_meta_box_callback() {
		?>
		<table class="phases-lists-table">
			<tr>
				<th class="order-col"><?php esc_html_e( 'Order', 'pbc' ); ?></th>
				<th class="phases-col"><?php esc_html_e( 'Phases', 'pbc' ); ?></th>
				<th class="variations-col"><?php esc_html_e( 'Number of Variations', 'pbc' ); ?></th>
			</tr>
			<?php
			$phases = get_posts( 'posts_per_page=-1&post_type=phases&orderby=menu_order&order=ASC' );
			if ( ! empty( $phases ) ) {
				foreach ( $phases as $phase ) {
					?>
					<tr>
						<td class="order-col"><?php echo esc_html( $phase->menu_order ); ?></td>
						<td class="phases-col"><?php echo esc_html( $phase->post_title ); ?></td>
						<td class="variations-col">
							<?php
							$variations = get_posts( 'posts_per_page=-1&post_type=variation&meta_key=pbc_phase&meta_value=' .$phase->ID . '&fields=ids' );
							if ( ! empty( $variations ) ) {
								echo count( $variations );
							}
							?>
						</td>
					</tr>
					<?php
				}
			}
			?>
		</table>
		<?php
	}

	/**
	 * General Settings Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function general_settings_meta_box_callback() {
		?>
		<form action="" method="post" enctype="multipart/form-data" id="pbc_general_settings_form">
			<div class="content">
				<fieldset>
					<label class="block" for="select_budget_page"><?php esc_html_e( 'Budget Configurator Page', 'pbc' ); ?></label>
					<?php
					$budget_configurator = get_option( 'pbc_budget_configurator_page' );
					$pages = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="select_budget_page">';
						echo '<option value="">' . __('Select a Page', 'pbc' ) . '</option>';
						foreach ( $pages as $page ) {
							$option = '<option value="' .( $page->ID ) . '"';
							$option .= ($page->ID == $budget_configurator) ? " selected='selected'" : "";
							$option .= '>' . $page->post_title . '</option>';
							echo $option;
						}
						echo '</select>';
					}
					?>
					&nbsp;&nbsp;<?php _e('or','pbc');?>&nbsp;<a class="create_page_link" href="<?php echo admin_url( 'post-new.php?post_type=page' );?>" title="<?php _e('Create New Page', 'pbc');?>"><?php _e('Create Page', 'pbc');?></a>
				</fieldset>
				<fieldset>
					<label class="block" for="select_PDF_image"><?php _e("Set PDF Image", 'pbc');?></label>
					<?php
						wp_enqueue_media();
						$pdf_image_selected = get_option( 'pbc_pdf_image_selected' );
					?>
					<input type="text" name="pdf_image_selected" value="<?php if ( $pdf_image_selected ) echo $pdf_image_selected;?>" /><button class="select-image button"><?php _e('Select image','pbc');?></button>
				</fieldset>
				<fieldset>
					<br/>
					<label class="block" for="variations_images_flipped"><?php _e("Flip Images Horizontal", 'pbc');?></label>
					<?php
						$variations_images_flipped = get_option('variations_images_flipped');
						$phases =  get_posts(array('post_type'=>'phases','posts_per_page'=>-1,'orderby'=>'menu_order','order'=>'ASC'));
						if(!empty($phases)){
							echo '<select multiple="multiple" name="variations_images_flipped[]" size="6" style="width:100%;">';
							foreach($phases as $phase){
								$variations = get_posts(array('post_type'=>'variation','posts_per_page'=>-1,'meta_key'=>'pbc_phase', 'meta_value'=>$phase->ID,'orderby'=>'title','order'=>'ASC'));
								if(!empty($variations)){
									foreach($variations as $var){
										if(!empty($variations_images_flipped) && in_array($var->ID, $variations_images_flipped))
											$selected = 'selected="selected"';
										else $selected = '';
										echo '<option value="'.$var->ID.'" '.$selected.'>'.str_pad($phase->menu_order, 2, '0', STR_PAD_LEFT).' - '.$phase->post_title.' - '.$var->post_title.'</option>';
									}
								}
							}
							echo '</select>';
						}
					?>
				</fieldset>
				<fieldset>
					<label class="block" for="admin_email_notification"><?php _e("Email Notification", 'pbc');?></label>
					<?php
						$admin_email_notification = get_option( 'pbc_admin_email_notification' );
					?>
					<input style="width:100%;" type="text" name="admin_email_notification" value="<?php if( $admin_email_notification ) echo $admin_email_notification; ?>" placeholder="<?php _e( 'separate multiple emails by comma', 'pbc' ); ?>" />
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_prices"><?php esc_html_e( 'Show prices?', 'pbc' ); ?></label>
					<?php
					$show_prices = get_option( 'pbc_budget_show_prices' );
					$pages = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_prices">';
						echo '<option value="yes" ' . selected( $show_prices, 'yes' ) . '>' . __( 'Yes', 'pbc' ) . '</option>';
						echo '<option value="no" ' . selected( $show_prices, 'no' ) . '>' . __( 'No', 'pbc' ) . '</option>';
						echo '</select>';
					}
					?>
				</fieldset>
			</div>

			<div class="save_bar">
				<input type="hidden" name="form_submit" value="true"/>
				<input type="submit" value="<?php esc_html_e( 'Save', 'pbc' ); ?>" class="button button-primary submit-button" />
			</div>
		</form>
		<?php
	}
	/**
	 * General Settings Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function license_meta_box_callback() {
		?>
		<form action="" method="post" enctype="multipart/form-data" id="pbc_license_form">
			<div class="content">
				<fieldset>
					<label class="block" for="pbc_license_apikey"><?php esc_html_e( 'License API Key', 'pbc' ); ?></label>
					<?php
					$license_apikey = get_option( 'pbc_license_apikey' );
					?>
					<input style="width:100%;" type="text" name="pbc_license_apikey" value="<?php if( $license_apikey ) { echo $license_apikey; } ?>" placeholder="<?php esc_html_e( 'License API Key', 'pbc' ); ?>" />
				</fieldset>
				<fieldset>
					<label class="block" for="pbc_license_product_id"><?php esc_html_e( 'License Product ID', 'pbc' ); ?></label>
					<?php
					$license_product_id = get_option( 'pbc_license_product_id' );
					?>
					<input style="width:100%;" type="text" name="pbc_license_product_id" value="<?php if( $license_product_id ) { echo $license_product_id; } ?>" placeholder="<?php esc_html_e( 'License Product ID', 'pbc' ); ?>" />
				</fieldset>
				<fieldset>
					<label class="block" for="pbc_license_status"><?php esc_html_e( 'Status:', 'pbc' ); ?></label>
					<p><strong><?php $this->license_status_callback(); ?></strong></p>
				</fieldset>
			</div>

			<div class="save_bar">
				<input type="hidden" name="submit_license" value="true"/>
				<input type="submit" value="<?php esc_html_e( 'Save license', 'pbc' ); ?>" class="button button-primary submit-button" />
			</div>
		</form>
		<?php
		echo '<div class="settings">';
		echo '<h2>' . esc_html__( 'What is the license for?', 'pbc' ) . '</h2>';
		echo '<p>';
		echo sprintf(
			__( 'With the <a href="%s" target="_blank">Product Budget Configurator</a> license, you\'ll have updates and automatic fixes to what\'s new or change in your system, so you\'ll always have automatic translations working.', 'pbc' ),
			'https://close.technology/wordpress-plugins/product-budget-configurator/?utm_source=WordPress-Settings'
		);
		echo '</p>';
		echo '</div><div class="help">';
		echo '<h2>' . esc_html__( 'How do I get a license?', 'pbc' ) . '</h2>';
		echo '<p>';
		echo sprintf(
			__( 'Visit the <a href="%s" target="_blank">Product Budget Configurator</a> page and purchase the licenses you need, depending on the number of WordPress MultiSites you\'re using.', 'pbc' ),
			'https://close.technology/wordpress-plugins/product-budget-configurator/?utm_source=WordPress-Settings'
		);
		echo '</p>';
		echo '</div>';
	}

    /**
     * Post Type Phases
     */
    public function pbc_register_cpt() {
        $nametype = __('Phase','pbc');

        $labels = array(
         'name' => $nametype.'s',
         'singular_name' => $nametype,
         'add_new' => __('Add','pbc').' '.$nametype,
         'add_new_item' => __('Add New','pbc').' '.$nametype,
         'edit_item' => __('Edit','pbc').' '.$nametype,
         'new_item' => __('New','pbc').' '.$nametype,
         'view_item' => __('View','pbc').' '.$nametype,
         'search_items' => __('Search for','pbc').' '.$nametype.'s',
         'not_found' =>  __("We didn't find any",'pbc').' '.$nametype,
         'not_found_in_trash' => __("We didn't find any",'pbc').' '.$nametype.' '.__("in the trash",'pbc'),
        );
        $args = array(
         'labels' => $labels,
         'public' => false,
         'show_in_menu' => false,
         'publicly_queryable' => false,
         'show_ui' => true,
         'query_var' => true,
         'rewrite' => array( 'slug' => _x('phase','phase','pbc'),'with_front' => 'true' ),
         'has_archive' => false,
         'capability_type' => 'page',
         'hierarchical' => false,
         'menu_position' => 5,
         'supports' => array('title','editor','page-attributes'),
         'menu_icon' => 'dashicons-tagcloud'
        );
        register_post_type('phases',$args);

        $labels = array(
         'name' =>__('Variations','pbc'),
         'singular_name' => __('Variation','pbc'),
         'add_new' => __('Add Variation','pbc'),
         'add_new_item' => __('Add New variation','pbc'),
         'edit_item' => __('Edit Variation','pbc'),
         'new_item' => __('New Variation','pbc'),
         'view_item' => __('View Variation','pbc'),
         'search_items' => __('Search for variations','pbc').'s',
         'not_found' =>  __("We didn't find any Variation",'pbc'),
         'not_found_in_trash' => __("We didn't find any Variation in the trash",'pbc'),
        );
        $args = array(
         'labels' => $labels,
         'public' => false,
         'show_in_menu' => false,
         'publicly_queryable' => false,
         'show_ui' => true,
         'query_var' => true,
         'rewrite' => array( 'slug' => _x('variation','variation','pbc'),'with_front' => 'true' ),
         'has_archive' => false,
         'capability_type' => 'post',
         'hierarchical' => false,
         'menu_position' => 5,
         'supports' => array('title'),
         'menu_icon' => 'dashicons-tagcloud'
        );
        register_post_type('variation',$args);

		$labels = array(
         'name' =>__('Enquiries','pbc'),
         'singular_name' => __('Enquiry','pbc'),
         'add_new' => __('Add Enquiry','pbc'),
         'add_new_item' => __('Add New Enquiry','pbc'),
         'edit_item' => __('Edit Enquiry','pbc'),
         'new_item' => __('New Enquiry','pbc'),
         'view_item' => __('View Enquiry','pbc'),
         'search_items' => __('Search for Enquiry','pbc').'s',
         'not_found' =>  __("We didn't find any Enquiry",'pbc'),
         'not_found_in_trash' => __("We didn't find any Enquiry in the trash",'pbc'),
        );
        $args = array(
         'labels' => $labels,
         'public' => false,
         'show_in_menu' => false,
         'publicly_queryable' => false,
         'show_ui' => true,
         'query_var' => true,
         'rewrite' => array( 'slug' => _x('Enquiry','enquiry','pbc'),'with_front' => 'true' ),
         'has_archive' => false,
         'capability_type' => 'post',
         'hierarchical' => false,
         'menu_position' => 5,
         'supports' => array('title'),
         'menu_icon' => 'dashicons-tagcloud'
        );
        register_post_type('enquiry',$args);

		$labels = array(
		  'name' => __('Price Options','pbc'),
		  'singular_name' => __('Price Option','pbc'),
		  'search_items' =>  __('Search Price Option','pbc'),
		  'all_items' => __('All Price Options','pbc'),
		  'edit_item' => __('Edit Price Option','pbc'),
		  'update_item' => __('Update Price Option','pbc'),
		  'add_new_item' => __('Add New Price Option','pbc'),
		  'new_item_name' => __('New Price Option','pbc'),
		);
    }

	/**
	 * Metabox variations
	 *
	 * @param array $meta_boxes Metaboxes.
	 * @return array
	 */
	public function pbc_metabox_variation( $meta_boxes ) {
		// Phase options
		if ( is_admin() ) {
			$phase_options = array();
			$phasescpt = get_posts(array(
				'post_type' => 'phases',
				'posts_per_page' => -1,
				'post_parent'=> 0,
				'orderby' => 'menu_order',
				'order' => 'ASC'
			));
			$phasescpt_item = array();
			foreach ($phasescpt as $phasescpt_item) {
				$phase_options[$phasescpt_item->ID] = $phasescpt_item->menu_order.' - '.$phasescpt_item->post_title;
			}
			//Variations Options
			$var_options = array();
			$variationscpt = get_posts(array(
				'post_type' => 'variation',
				'posts_per_page' => -1,
				'orderby' => 'name',
				'order' => 'ASC'
			));
			$variationscpt_item = array();
			foreach ($variationscpt as $var_item) {
			$phase_id = get_post_meta($var_item->ID, 'pbc_phase', true);
			$phase_post = get_post($phase_id);
			if($phase_post->menu_order<10) $phase_order = '0'.$phase_post->menu_order; else $phase_order = $phase_post->menu_order;
			$var_value = $phase_order.'|'.$var_item->ID;
			$var_sku = get_post_meta($var_item->ID, 'pbc_sku', true);
			if($var_sku)
				$var_options[$var_value] = $phase_order.' - '.$phase_post->post_title.' - '.$var_item->post_title.'('.$var_sku.')';
			else
				$var_options[$var_value] = $phase_order.' - '.$phase_post->post_title.' - '.$var_item->post_title;
			}
			asort($var_options);
		}

    	$prefix = 'pbc_';
    	// 1st meta box
    	$meta_boxes[] = array(
    		'id'         => 'standard',
    		'title'      => __( 'Options for variation', 'pbc' ),
    		'post_types' => array( 'variation' ),
    		'context'    => 'normal',
    		'priority'   => 'high',
    		'autosave'   => true,

    		'fields'     => array(
    			// SELECT BOX PHASE
    			array(
    				'name'        => __( 'Phase', 'pbc' ),
    				'id'          => "{$prefix}phase",
    				'type'        => 'select',
    				'options'     => $phase_options,
    				'multiple'    => false,
    				'std'         => '',
    				'placeholder' => __( 'Select a phase', 'pbc' ),
    			),
    			// TEXT
    			array(
    				'name'  => __( 'Reference', 'pbc' ),
    				'id'    => "{$prefix}sku",
    				'desc'  => '',
    				'type'  => 'text',
    				'std'   => '',
    				'clone' => false,
    			),
    			// IMAGE ADVANCED (WP 3.5+)
    			array(
    				'name'             => __( 'Icon image', 'pbc' ),
    				'id'               => "{$prefix}imgicon",
    				'type'             => 'image_advanced',
    				'max_file_uploads' => 1,
    			),

				array(
    				'name'   => __( 'Depends of', 'pbc' ),
					'id'     => "{$prefix}depends",
					'type'   => 'group',
					'clone'  => true,
					'sort_clone' => true,
					'fields' => array(
		    			// SELECT BOX VARIATIONS
		    			array(
		    				'name'        => __( 'Variation', 'pbc' ),
		    				'id'          => "{$prefix}depvar",
		    				'type'        => 'select',
		    				'options'     => $var_options,
		    				'multiple'    => false,
		    				'std'         => '',
		    				'placeholder' => __( 'Not depends of variation', 'pbc' ),
		    			),
					),
				), //array

				array(
    			'name'   => __( 'Product group image', 'pbc' ),
					'id'     => "{$prefix}imgprodgroup",
					'type'   => 'group',
					'clone'  => true,
					'sort_clone' => true,
					'fields' => array(
		    			// SELECT BOX VARIATIONS
		    			array(
		    				'name'        => __( 'Variation', 'pbc' ),
		    				'id'          => "{$prefix}depvarimgprod",
		    				'type'        => 'select_advanced',
		    				'options'     => $var_options,
		    				'multiple'    => true,
		    				'std'         => '',
		    				'placeholder' => 'No depende de una variación',
		    			),
		    			// IMAGE ADVANCED (WP 3.5+)
		    			array(
		    				'name'             => __( 'Product image', 'pbc' ),
		    				'id'               => "{$prefix}imgprod",
		    				'type'             => 'image_advanced',
		    				'max_file_uploads' => 1,
		    			),
					),
				), //array
				array(
    				'name'   => __( 'Price', 'pbc' ),
					'id'     => "{$prefix}pricegroup",
					'type'   => 'group',
					'clone'  => true,
					'sort_clone' => true,
					'fields' => array(
		    			// TEXT
		    			array(
		    				'name'  => __( 'Option price', 'pbc' ),
		    				'id'    => "{$prefix}meaprice",
		    				'desc'  => '',
		    				'type'  => 'text',
		    				'std'   => '',
		    				'clone' => false,
		               'columns' => 3,
		    			),
		    			// TEXT
		    			array(
		    				'name'  => __( 'Price (VAT not included)', 'pbc' ),
		    				'id'    => "{$prefix}pricem",
		    				'desc'  => '',
		    				'type'  => 'text',
		    				'std'   => '',
		    				'clone' => false,
		               'columns' => 1,
		    			),
					),
				), //array
    		)
    	);

    	return $meta_boxes;
    }

	public function pbc_metabox_enquiry(){

		add_meta_box(
	        'enquiry-details',
	        __( 'Enquiry Details','pbc' ),
	        array($this,'render_enquiry_details'),
	        'enquiry',
	        'normal',
	        'default'
	    );

		add_meta_box(
	        'configuration-details',
	        __( 'Budget Configuration','pbc' ),
	        array($this,'render_budget_configuration'),
	        'enquiry',
	        'normal',
	        'default'
	    );
	}
	public function render_enquiry_details($post){?>
		<div><label><?php _e('Name:', 'pbc');?> <?php echo get_post_meta($post->ID, 'pbc_enquiry_name',true);?></label></div>
		<div><label><?php _e('Phone:', 'pbc');?> <?php echo get_post_meta($post->ID, 'pbc_enquiry_phone',true);?></label></div>
		<div><label><?php _e('Email:', 'pbc');?> <?php echo get_post_meta($post->ID, 'pbc_enquiry_email',true);?></label></div>
		<div><label><?php _e('City:', 'pbc');?> <?php echo get_post_meta($post->ID, 'pbc_enquiry_city',true);?></label></div>
		<div><label><?php _e('State:', 'pbc');?> <?php echo get_post_meta($post->ID, 'pbc_enquiry_state',true);?></label></div>
	<?php
	}
	public function render_budget_configuration($post){?>
		<table>
			<thead>
				<tr>
					<th style="width:20%" class="sn">#</th>
					<th style="width:50%" class="phase-variation"><?php _e('Phase/Variation','pbc');?></th>
					<th style="width:30%" class="price"><?php _e('Price','pbc');?></th>
				</tr>
			</thead>
			<tbody>
				<?php for($i=0;$i<16;$i++){?>
				<tr>
					<td class="sn"><?php echo $i;?></td>
					<td class="phase-variation"><?php echo get_post_meta($post->ID, 'pbc_phase_var_'.$i,true);?></td>
					<td class="price"><?php echo get_post_meta($post->ID,'pbc_price_'.$i,true);?></td>
				</tr>
				<?php }?>
			</tbody>
		</table>
	<?php
	}

	/*
	 * Disables dropdown dates
	 */
	public function disable_months_dropdown( $false , $post_type ) {

		$disable_months_dropdown = $false;

		$disable_post_types = array( 'variation' , 'phases' );

		if( in_array( $post_type , $disable_post_types ) ) {

			$disable_months_dropdown = true;

		}

		return $disable_months_dropdown;

	}

	/** Add columns for Phases **/
	// Add to admin_init function
	public function add_new_phases_columns($phases_columns) {
	    $new_columns['cb'] = '<input type="checkbox" />';
	    $new_columns['title'] = __('Phase','pbc');
	    $new_columns['menu_order'] = __('Order','pbc');

	    return $new_columns;
	}



	public function manage_phases_columns($column_name, $id) {
	    global $wpdb, $post;

	    switch ($column_name) {

	    case 'menu_order':
	        echo $post->menu_order;
	        break;
	    default:
	        break;
	    } // end switch
	}

	/** Add columns for Variations **/
	// Add to admin_init function
	public function add_new_var_columns($phases_columns) {
	    $new_columns['cb'] = '<input type="checkbox" />';
	    $new_columns['title'] = __('Variation','pbc');
	    $new_columns['phase'] = __('Phase','pbc');
	    $new_columns['price'] = __('Price','pbc');
	    $new_columns['depends'] = __('Depends of','pbc');
	    $new_columns['imgicon'] = __('Icon','pbc');
	    $new_columns['imgprod'] = __('Product Images','pbc');

	    return $new_columns;
	}


	public function manage_var_columns($column_name, $id) {
	    global $wpdb, $post;

		//* Price group
		$price_group = rwmb_meta( 'pbc_pricegroup' );
		$price_column = '';
		foreach($price_group as $price_item) {
			if(isset($price_item['pbc_meaprice'])) {
        	$price_column .= $price_item['pbc_meaprice'].' - '.$price_item['pbc_pricem'].' €';
			} else { // Price without any option
			$price_column .= $price_item['pbc_pricem'].' €';
			}
			$price_column .= '<br/>';
		}
		//* Depends group
		$depends_group = rwmb_meta( 'pbc_depends' );
		$depends_column = '';
		foreach($depends_group as $depends_item) {
			$variation_id = substr($depends_item['pbc_depvar'], 3);
			$variation_post = get_post($variation_id);
			$phase_id_dp = get_post_meta($variation_id, 'pbc_phase', true);
			$phase_post_dp = get_post($phase_id_dp);
			if($phase_post_dp->menu_order<10) $phase_order = '0'.$phase_post_dp->menu_order; else $phase_order = $phase_post_dp->menu_order;
        	$depends_column .= $phase_order.' - '.$phase_post_dp->post_title.' - '.$variation_post->post_title;
			$depends_column .= '<br/>';
		}
		$phase_id = get_post_meta(get_the_id(),'pbc_phase',true);

		//* Image icon
		$imgicon = get_post_meta(get_the_id(), 'pbc_imgicon', true);
        if($imgicon){
            $icon_image = wp_get_attachment_image_src($imgicon, array(120,120), true);
		}

		//* Image Group Product
		$image_group = rwmb_meta( 'pbc_imgprodgroup' );

	    switch ($column_name) {

	    case 'phase':
			$phase_post = get_post($phase_id);
	        echo $phase_post->menu_order.' - '.$phase_post->post_title;
	        break;
	    case 'price':
	        echo $price_column;
	        break;
	    case 'depends':
			echo $depends_column;
	        break;
	    case 'imgicon':
			if(isset($icon_image) ) echo '<img src="'.$icon_image[0].'" />';
	        break;
	    case 'imgprod':
			if(isset($image_group)) {
				if(count($image_group)>0) echo count($image_group).'<br>';
				foreach($image_group as $imageg_item) {
					if(isset($imageg_item['pbc_imgprod'][0])) {
					$icon_imageprod = wp_get_attachment_image_src($imageg_item['pbc_imgprod'][0], array(57,46), true);
					echo '<img src="'.$icon_imageprod[0].'" width="57" height="46"/>';
					}
				}
			}
	        break;
	    default:
	        break;
	    } // end switch
	}

	/**
	* Filters columns in variation post type
	**/

	public function pbc_admin_posts_filter(){
	    $type = 'variation';
	    if (isset($_GET['post_type'])) {
	        $type = $_GET['post_type'];
	    }

	    //only add filter to post type you want
	    if ('variation' == $type){
	        //change this to the list of values you want to show
	        //in 'label' => 'value' format

			// Phase Filter
	        $phase_options = array();
	        $phasescpt = get_posts(array(
	            'post_type' => 'phases',
	            'posts_per_page' => -1,
	            'post_parent'=> 0,
	            'orderby' => 'menu_order',
	            'order' => 'ASC'
	        ));
	        $phasescpt_item = array();
	        foreach ($phasescpt as $phasescpt_item) {
	           $phase_options[$phasescpt_item->menu_order.' - '.$phasescpt_item->post_title] = $phasescpt_item->ID;
	        }

	        ?>
	        <select name="pbc_filter_phase">
	        <option value=""><?php _e('All Phases', 'pbc'); ?></option>
	        <?php
	            $current_v = isset($_GET['pbc_filter_phase'])? $_GET['pbc_filter_phase']:'';
	            foreach ($phase_options as $label => $value) {
	                printf
	                    (
	                        '<option value="%s"%s>%s</option>',
	                        $value,
	                        $value == $current_v? ' selected="selected"':'',
	                        $label
	                    );
	                }
	        ?>
	        </select>
	<?php
	    } //variation type
	}
	/**
	 * if submitted filter by post meta
	 *
	 * make sure to change META_KEY to the actual meta key
	 * and variation to the name of your custom post type
	 * @author Ohad Raz
	 * @param  (wp_query object) $query
	 *
	 * @return Void
	 */
	public function pbc_posts_filter( $query ){
	    global $pagenow;
	    $type = 'post';
	    if (isset($_GET['post_type'])) {
	        $type = $_GET['post_type'];
	    }

			if ( 'variation' == $type && is_admin() && $pagenow=='edit.php' ) {
				if(isset($_GET['pbc_filter_phase']) && $_GET['pbc_filter_phase'] != '') {
	        $query->query_vars['meta_key'] = 'pbc_phase';
	        $query->query_vars['meta_value'] = $_GET['pbc_filter_phase'];
				}

			}
	}

	public function pbc_custom_page_template( $template ) {
		$budget_configurator = get_option('pbc_budget_configurator_page');
		if ( !empty($budget_configurator) && \is_page( $budget_configurator )  ) {
			if(isset($_POST) && isset($_GET['submit']) && $_POST['submit'] == 'email_send'){
				if(session_id() == ''){
				    session_start();
				}
				$_SESSION['pbc_output'] = $this->configurator_result_email_send($_POST);
			}
			if(isset($_GET) && isset($_GET['configurator']) && $_GET['configurator'] == 'pdf')
            {
                if (is_file(WPPBC_PLUGIN_DIR.
                    "/lib/html2pdf/html2pdf.class.php")
                )
                {
                    require_once(WPPBC_PLUGIN_DIR.
                        '/lib/html2pdf/html2pdf.class.php');
					if(session_id() == ''){
						ob_start();
					    session_start();
					}
					$content = $this->configurator_result_generate_pdf();
					if($content['type'] == 'error'){
						echo $content['response'];
					}else{
					    try {
					        $width_mm = 710 * 0.2646;   //1px = 0.2646mm
					        $height_mm = 900 * 0.2646;
					        $html2pdf = new \HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(2.5, 2.5, 2.5, 2.5));
					        $html2pdf->setTestTdInOnePage(false);
					        $html2pdf->writeHTML($content['response']);
					        $html2pdf->Output("Budget Configurator ".date('Y-m-d H:i').".pdf");
					        //$html2pdf->close();
					    } catch (Html2PdfException $e) {
					        $formatter = new ExceptionFormatter($e);
					        echo "Unexpected Error!<br>Can't load PDF this time!<br>".$formatter->getHtmlMessage();
					    }
					}
                }else{
					echo 'Error: PDF Library Not Present';
				}
                exit(0);
            }
			if ( \locate_template( 'budget-configurator.php' ) )
				$new_template =  \get_stylesheet_directory().'budget-configurator.php';
	        else
				$new_template = WPPBC_PLUGIN_DIR. '/budget-configurator.php';
			if ( '' != $new_template ) {
				return $new_template ;
			}
		}
		return $template;
	}

	public function variation_selected_action_callback(){
		extract($_REQUEST);
		if(session_id() == ''){
			ob_start();
			session_start();
		}
	    if(session_id() == ''){
	       echo ';;--;;'.json_encode(array('type'=>'error', 'msg'=>'Error: Unable to initialize Session!'));
	       die(0);
	    }
		if(!empty($pbc_variation) && $current_phase && $pbc_variation[$current_phase]){
			$sVar = $pbc_variation[$current_phase];
			if(is_user_logged_in()){
				$user_id = get_current_user_id();
				$phase_param['var'] = $sVar;
				$phase_param['pricevar'] = ($_REQUEST["pbc_pricevar_$sVar"])?($_REQUEST["pbc_pricevar_$sVar"]):'';
				update_user_meta($user_id, 'pbc_phase_'.$current_phase,$phase_param);
			}

			$imgprodgroup = get_post_meta($sVar, 'pbc_imgprodgroup', true);
			$imgprodid = '';
			if(!empty($imgprodgroup)){
				foreach($imgprodgroup as $deps)
				{
					if(isset($deps['pbc_depvarimgprod']) && !empty($deps['pbc_depvarimgprod']) && isset($deps['pbc_imgprod']) )
					{
						$prevVar = array();
						foreach($deps['pbc_depvarimgprod'] as $depvarimgprod)
						{
							$arr = explode('|', $depvarimgprod);
							if(!empty($arr[0]) && !empty($arr[1])){
								$prevVar[(int)$arr[0]][] = $arr[1];
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
			if($imgprodid){ $imgprodurl = wp_get_attachment_image_src($imgprodid, 'full', true);}
			$pricegroup = get_post_meta($sVar, 'pbc_pricegroup', true);
			$pricevar = $_REQUEST["pbc_pricevar_$sVar"];
			if($pricevar){
				foreach($pricegroup as $details){
					if($details['pbc_meaprice'] == $pricevar){
						$option_name = $pricevar;
						$price = $details['pbc_pricem'];
					}
				}
			}else{
				if(isset($pricegroup[0]['pbc_pricem']))
					$price = $pricegroup[0]['pbc_pricem'];
			}
			$option = get_the_title($sVar);
			if(isset($option_name) && $option_name) $option .= ' ['.$option_name.']';

			$variations_images_flipped = get_option('variations_images_flipped');
			if(!empty($variations_images_flipped)) {
				for ($j = 1; $j < (int)$current_phase; $j++)
				{
					if(isset($_SESSION['pbc_variation'][$j]) && in_array($_SESSION['pbc_variation'][$j]['var']['id'], $variations_images_flipped)){
						$flipped = true;
					}
				}
				if(in_array($sVar, $variations_images_flipped))
					$flipped = true;
			}
		}
		if(isset($imgprodurl) && $imgprodurl){
			$url = $imgprodurl[0];
		}else{
			//$return = WPPBC_PLUGIN_URL.'preview-img.jpg';
			$url = '';
		}
		if(!isset($price) || empty($price)){
			$price = '-';
		}else $price .= ' €';

		if(!isset($option))
			$option = '-';
		if(!isset($flipped))
			$flipped = false;
		echo ';;--;;'.json_encode(array('type'=>'success', 'url'=>$url, 'option'=>$option, 'flipped'=>$flipped, 'price'=>$price));
		die(0);
	}
	public function configurator_submit_action_callback(){
		extract($_POST);
		if(isset($submit) && $submit == 'email_send'){
			if(session_id() == ''){
			    session_start();
			}
			$_SESSION['pbc_output'] = $this->configurator_result_email_send($_POST);
		}
		ob_start();
		if ( \locate_template( 'budget-configurator.php' ) )
			\locate_template('budget-configurator.php', true);
		else
			include(WPPBC_PLUGIN_DIR. '/budget-configurator.php');
		$all_details = ob_get_contents();
		ob_end_clean();
		echo $all_details;
		die(0);
	}
	public function configurator_login_action_callback(){
		extract($_POST);
		$login = wp_signon( array( 'user_login' => $username, 'user_password' => $password, 'remember' => true ), false );
		if( $login->ID ) {
			ob_start();
			if ( \locate_template( 'budget-configurator.php' ) )
				\locate_template('budget-configurator.php', true);
			else
				include(WPPBC_PLUGIN_DIR. '/budget-configurator.php');
			$all_details = ob_get_contents();
			ob_end_clean();
			echo $all_details;
		}elseif ( is_wp_error( $login ) ){
			echo ';;-;;error;;-;;'.$login->get_error_message();
		}
		die(0);
	}
	public function configurator_result_email_send( $post_requests ){
		extract($post_requests);
		if(!$email_field){
			$result = array('type'=>'error', 'response'=>__('Email field empty!','pbc') );
		}elseif(!$name_field){
			$result = array('type'=>'error', 'response'=>__('Name field is empty!','pbc') );
		}elseif(!$phone_field){
			$result = array('type'=>'error', 'response'=>__('Phone field is empty!','pbc') );
		}else{
			$emails = explode(',', $email_field);
			$admin_emails = get_option('pbc_admin_email_notification');
			if($admin_emails){
				$admin_emails = explode(',', $admin_emails);
				$emails = array_merge($emails, $admin_emails);
			}
			$emails = array_map('trim', $emails);
			if(!isset($_SESSION['pbc_variation'])){
				$result = array('type'=>'error', 'response'=>__('Configurator not ready!','pbc') );
			}else{
				$subject = __('Budget Configurator','pbc').' - '.get_option('blogname');
				$message .= '<div><h2>'.__('Enquiry details:','pbc').'</h2><br/><strong>'.__('Name:','pbc').'</strong>'.$name_field.'<br/><strong>'.__('Email:','pbc').'</strong>'.$email_field.'<br/><strong>'.__('Phone:','pbc').'</strong>'.$phone_field.'<br/><strong>'.__('City:','pbc').'</strong>'.$city_field.'<br/><strong>'.__('State:','pbc').'</strong>'.$state_field.'<br/><br/></div>';
				$message = '<h4>'.__('Configuration details:','pbc').'</h4>'.'<br>';
				$message .= '<table><tr><th>'.__('Phase','pbc').'</th><th>'.__('Variation','pbc').'</th><th>'.__('Price','pbc').'</th></tr>';
				$total_price = '';
				$logged_in = is_user_logged_in();
				$enquiry_entries = array();
				$i=0;
				foreach($_SESSION['pbc_variation'] as $phaseKey => $details){
					$total_price += $details['var']['price'];
					$message .= '<tr>';
					$message .= '<td>'.$details['phase']['name'].'</td>';
					$message .= '<td>'.$details['var']['name'].'</td>';
					$message .= '<td>';
					if($logged_in) $message .= $details['var']['price'].' €'; else $message .= '-';
					$message .= '</td>';
					$message .= '</tr>';
					$enquiry_entries[$i]['phase_var'] = $details['phase']['name'].': '.$details['var']['name'];
					$enquiry_entries[$i]['price'] = $details['var']['price'];
					$i++;
				}
				if($total_price && $logged_in) $total_price = $total_price.' €';
				else $total_price = '-';
				$message .= '<tr>';
				$message .= '<td>&nbsp;</td><td>'.__('Total:','pbc').'</td>';
				$message .= '<td>'.$total_price.'</td>';
				$message .= '</tr>';
				$message .= '</table>';
				$message .= '<br>'.get_option('blogname');
				$headers = array('Content-Type: text/html; charset=UTF-8');
				$attachments = array('');

				if ( is_file( WPPBC_PLUGIN_DIR . "/lib/html2pdf/html2pdf.class.php" ) ) {
               require_once ( WPPBC_PLUGIN_DIR . '/lib/html2pdf/html2pdf.class.php' );
					if(session_id() == ''){
						ob_start();
					    session_start();
					}
					$filename   = __( 'budget', 'pbc' ) . '-' . get_bloginfo( 'name' ) . '-' . date( 'Y-m-d-H-i' ) . '.pdf';
					$dirname = $this->get_budget_path();
					$filename_path = $dirname . $filename;

					$content = $this->configurator_result_generate_pdf();
					if ( $content['type'] == 'error' ) {
						//error echo $content['response'];
					} else {
					    try {
					        $width_mm = 710 * 0.2646;   //1px = 0.2646mm
					        $height_mm = 900 * 0.2646;
					        $html2pdf = new \HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(2.5, 2.5, 2.5, 2.5));
					        $html2pdf->setTestTdInOnePage(false);
					        $html2pdf->writeHTML($content['response']);
					        $html2pdf->Output( $filename_path, "F");
					        //$html2pdf->close();
					    } catch ( Html2PdfException $e ) {
							//error
					        //$formatter = new ExceptionFormatter($e);
					        //echo "Unexpected Error!<br>Can't load PDF this time!<br>".$formatter->getHtmlMessage();
					    }
					}
					if ( is_file( $filename_path ) ) {
						$attachments = array( $filename_path );
					}
            }

				//insert_enquiry Post
				$my_post = array(
				    'post_title'    => $name_field.'-'.$phone_field,
				    'post_status'   => 'publish',
					'post_type'		=> 'enquiry'
				);
				$post_id = wp_insert_post( $my_post );
				if ( $post_id ) {
					update_post_meta( $post_id, 'pbc_enquiry_name', $name_field );
					update_post_meta( $post_id, 'pbc_enquiry_phone', $phone_field );
					update_post_meta( $post_id, 'pbc_enquiry_email', $email_field );
					update_post_meta( $post_id, 'pbc_enquiry_city', $city_field );
					update_post_meta( $post_id, 'pbc_enquiry_state', $state_field );
					if ( ! empty( $enquiry_entries ) ) {
						$i=0;
						foreach ( $enquiry_entries as $entries ) {
							update_post_meta( $post_id, 'pbc_phase_var_' . $i, $entries['phase_var'] );
							update_post_meta( $post_id, 'pbc_price_' . $i, $entries['price'] );
							$i++;
						}
					}
				}

				function set_html_content_type() {
					return 'text/html';
				}
				add_filter( 'wp_mail_content_type', 'set_html_content_type' );
			    if(!wp_mail( $emails, $subject, $message, $headers, $attachments)){
					$result = array('type'=>'error', 'response'=>__('Error in sending mail. Please try again!','pbc'));
				}else{
					if(!empty($attachments)) unlink(plugin_dir_path( __FILE__)."$filename");
					$result = array('type'=>'success', 'response'=>'Mail sent!');
				}
				remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
			}
		}
		return $result;
	}

	/**
	 * Returns the filename created in folder
	 *
	 * @return string Filename and path
	 */
	private function get_budget_path() {
		$upload_dir = wp_upload_dir();
		$dir_name   = $upload_dir['basedir'] . '/pbc/';
		if ( ! file_exists( $dir_name ) ) {
			wp_mkdir_p( $dir_name );
		}

		return $dir_name;
	}

	public function configurator_result_generate_pdf(){
		if(!isset($_SESSION['pbc_variation'])){
			$result = array('type'=>'error', 'response'=>__('Configurator not ready!','pbc'));
		}else{
			$output = '';
		    $output .= "<page backcolor='#fff'>";
			$output .= "<style>
			.header, .product .product-title {margin-left: 20px;}
			.product .product-title{ width:400px;text-align:left;vertical-align:bottom; }
			.product .product-preview{ width:300px; }
			.product .image-wrap{ position:relative; }
		    .product .image-wrap img:first-child{ position:relative; }
		    .product .image-wrap img{ width:100%;max-width:300px;position:absolute;top:0;left:0; }
			table.summary, table.summary-total{ width:600px;border-collapse:collapse;border:0; margin-left:50px;}
			table td.title{ width:500px;padding:5px 0 5px 15px; }
			table td.value{ width:70px;padding:5px 15px 5px 0; }
			table td.right{text-align:right;}
			table.summary td.background, table.summary td.background{ background-color:#ffebcb; }
			table.summary-total td.empty{width:450px;}
			table.summary-total td.title{width:50px;}
			img.header_image{ width:700px;height:120px; }
			img.footer_image{ width:700px;height:70px; margin: 50px 0 0 30px;}
			</style>";
			// $pdf_image_selected = get_option('pbc_pdf_image_selected');
			// if($pdf_image_selected)
			// 	$output .="<img src='".$pdf_image_selected."' width='200'/>";
			$output .="<table class='header'><tr><td><img src='".WPPBC_PLUGIN_DIR."/assets/pdf-header.png' class='header_image'/></td></tr></table><br/>";
			$output .= "<table class='product'><tr><td class='product-title'><img width='350' src='".WPPBC_PLUGIN_DIR."/assets/pdf-title.png' class='title_image'/><p>Relaci&oacute;n de caracter&iacute;sticas del modelo seleccionado.</p></td><td class='product-preview'><div class='image-wrap'>";
			$flipped = false;
			$variations_images_flipped = get_option('variations_images_flipped');
			if(!empty($variations_images_flipped)) {
				for ($j = 1; $j <= count($_SESSION['pbc_variation']); $j++)
				{
					if(isset($_SESSION['pbc_variation'][$j]) && in_array($_SESSION['pbc_variation'][$j]['var']['id'], $variations_images_flipped)){
						$flipped = true;
					}
				}
			}

			$outputImage = imagecreatetruecolor(300, 243);
			$black = imagecolorallocate($outputImage, 0, 0, 0);
			// Make the background transparent
			imagecolortransparent($outputImage, $black);
			for ($i = 1; $i <= count($_SESSION['pbc_variation']); $i++)
			{
				$imgprodid = $imgprodurl = '';
				if(isset($_SESSION['pbc_variation'][$i]))
				{
					$ssVar = $_SESSION['pbc_variation'][$i]['var']['id'];
					$imgprodgroup = get_post_meta($ssVar, 'pbc_imgprodgroup', true);
					if(!empty($imgprodgroup)){
						foreach($imgprodgroup as $deps)
						{
							if(isset($deps['pbc_depvarimgprod']) && !empty($deps['pbc_depvarimgprod']) && isset($deps['pbc_imgprod']) )
							{
								$prevVar = array();
								foreach($deps['pbc_depvarimgprod'] as $depvarimgprod)
								{
									$arr = explode('|', $depvarimgprod);
									if(!empty($arr[0]) && !empty($arr[1])){
										$prevVar[(int)$arr[0]][] = $arr[1];
									}
								}
								if(!empty($_SESSION['pbc_variation']))
								{
									foreach($_SESSION['pbc_variation'] as $sPhaseKey => $sVariations)
									{
										if(isset($prevVar[$sPhaseKey]) &&
										isset($_SESSION['pbc_variation'][$sPhaseKey]) && in_array($_SESSION['pbc_variation'][$sPhaseKey]['var']['id'], $prevVar[$sPhaseKey]))
										{
											$imgprodid = $deps['pbc_imgprod'][0];
											break;
										}
									}
								}
							}elseif((!isset($deps['pbc_depvarimgprod']) || empty($deps['pbc_depvarimgprod'])) && isset($deps['pbc_imgprod']) ){
								$imgprodid = $deps['pbc_imgprod'][0];
								break;
							}
						}
					}
					if(isset($imgprodid) && $imgprodid){ $imgprodurl = wp_get_attachment_image_src($imgprodid, 'full', true);}
					if(isset($imgprodurl) && $imgprodurl){
						$extension = pathinfo($imgprodurl[0], PATHINFO_EXTENSION);
						switch ($extension) {
						    case 'png':
						       $img = imagecreatefrompng($imgprodurl[0]);
							   list($width, $height) = getimagesize($imgprodurl[0]);
						    break;
							default:
								//jpg, jpeg, gif others
								$dirname = $this->get_budget_path();
								$image = imagepng(imagecreatefromstring(file_get_contents($imgprodurl[0])), $dirname . 'product-image-for-pdf.png' );
								list($width, $height) = getimagesize( $dirname . 'product-image-for-pdf.png' );
								$img = imagecreatefrompng( $dirname . 'product-image-for-pdf.png' );
						}

						// Flip it vertically
						if ( $flipped ) {
							imageflip($img, IMG_FLIP_HORIZONTAL);
						}
						imagecopyresized($outputImage,$img,0,0,0,0,300,243,$width,$height);
						//$output .= '<img phaseid="'.$i.'" src="'.$imgprodurl[0].'" alt="product image"/>';
					}
				}
			}
			$filename = 'pdfimage'.round(microtime(true) * 1000).'.png';
			imagepng($outputImage, WPPBC_PLUGIN_DIR."/product-image-for-pdf.png");
			imagedestroy($outputImage);
			$output .= '<img phaseid="'.$i.'" src="'.WPPBC_PLUGIN_DIR.'/product-image-for-pdf.png" alt="product image"/>';

			$output .= "</div></td></tr></table><br/><br/>";

			$output .= '<table class="summary">';
			$total_price = '';
			$logged_in = is_user_logged_in();
			$i = 0;
			foreach($_SESSION['pbc_variation'] as $phaseKey => $details){
				if(($i%2) == 0) $bg = 'background';
				else $bg = '';
				$price = (double)($details['var']['price']);
				$total_price += $price;
				$output .= '<tr>';
				$output .= '<td class="title '.$bg.'">'.$details['phase']['name'].' '.$details['var']['name'].'</td>';
				$output .= '<td class="value right '.$bg.'">';
				if($logged_in) $output .= number_format($price, 2, ',', ' ').' €'; else $output .= '-';
				$output .= '</td>';
				$output .= '</tr>';
				$i++;
			}
			if(!$total_price){
				$total_price = 0;
				$tax = 0;
			}
			$tax = $total_price*0.21;
			$total_pricevat = $total_price + $total_price*0.21;

			$output .= '</table>';
			$output .= '<table class="summary-total"><tr>';
			$output .= '<td class="empty">&nbsp;</td><td class="title right">IVA 21%</td>';
			$output .= '<td class="value right">';
			if($logged_in) $output .= number_format($tax, 2, ',', '.').' €'; else $output .= '-';
			$output .= '</td>';
			$output .= '</tr>';
			$output .= '<tr><td class="empty">&nbsp;</td><td class="title right">Subtotal</td>';
			$output .= '<td class="value right">';
			if($logged_in) $output .= number_format($total_price, 2, ',', '.').' €';else $output .= '-';
			$output .= '</td>';
			$output .= '</tr>';
			$output .= '<tr>';
			$output .= '<td class="empty">&nbsp;</td><td class="title right" style="background-color:#835536;color:#fff;">Total</td>';

			$output .= '<td class="value right" style="background-color:#835536;color:#fff;">';
			if($logged_in) $output .= number_format($total_pricevat, 2, ',', '.').' €'; else $output .= '-';
			$output .= '</td>';
			$output .= '</tr>';
			$output .= '</table><br/>';

			$output .="<table class='footer'><tr><td><img src='".WPPBC_PLUGIN_DIR."/assets/pdf-footer.png' class='footer_image'/></td></tr></table>";

			$output .= '</page>';
			$result = array('type'=>'success', 'response'=>$output);
		}
		return $result;
	}

	//add print-pdf button
	public function pbc_add_print_pdf_button( $views )
	{
		$views['pdf-button'] = '<button id="print-pdf" type="button" class="button" title="Print PDF" style="margin:0 5px"><span class="dashicons dashicons-media-spreadsheet"></span> '.__('Create List Price', 'pbc').'</button><span id="print-message"></span>';
		return $views;
	}
	public function pbc_move_print_pdf_button( )
	{
		global $current_screen;
		// only variation post type, exit earlier
		if( 'variation' != $current_screen->post_type )
			return;
		?>
		<script type="text/javascript">
			var ids = new Array();
			jQuery(function($){
				$('#print-pdf').insertAfter('#post-query-submit');
				$('#print-message').insertAfter('#print-pdf');
				$("#print-pdf").click(function(){
					$("input[name='post[]']:checked").each(function (index, element){
						ids.push($(element).val());
					});
				// if(ids ==''){
				// 	$('#print-message').html('Please select a post!').show().delay(3000).fadeOut(500);
				// 	return false;
				// }
				$('#print-message').html('<img src="<?php echo WPPBC_PLUGIN_URL;?>/assets/loading.gif"/>');
				$.ajax({
					type: "POST",
					url: '<?php echo admin_url('admin-ajax.php');?>',
					data: 'action=print_pdf&ids='+ids,
					dataType: "html",
					success: function(result) {
						var resArr = result.split(';;--;;');
						var obj = jQuery.parseJSON(resArr[1]);
						if(obj.type=='success'){
							//window.prompt('PDF is generated, Please copy the link below!',obj.msg);
							window.open(obj.msg, '_blank');
							ids =[];
							$('#print-message').html('');
						}else{
							$('#print-message').html(obj.msg).show().delay(3000).fadeOut(500);
						}
					}
				});
				return false;
				});
			});

		</script>
		<?php
	}
	public function print_pdf_action_callback(){
		extract($_REQUEST);
		/*if(empty($ids)){
			$ids = get_posts('posts_per_page=-1&post_type=variation&fields=ids');
		}else{
			$ids = explode(',',$ids);
		}*/

		ob_start();
		/*Content of PDF file*/
		?>
		<style>
			table {
				border-collapse: collapse;
				width: 112%;
				font-size: 11pt;
			}
			table, th, td {
				border: 1px solid black;
				padding: 10px;
			}
			tr.table_header {
				background-color: black;
				color: white;
			}
			.imagepdf {
				width: 60px;
			}
		</style>
		<?php
		$pdf_image_selected = get_option('pbc_pdf_image_selected');
		if($pdf_image_selected){?>
			<img src="<?php echo $pdf_image_selected;?>" width='200'/>
		<?php }?>
		<h1><?php _e('List Price for','pbc'); echo ' '.get_bloginfo( 'name');?></h1>
		<p><strong><?php _e('Date','pbc'); echo ': '.date('d-m-Y');?></strong></p>
		<?php $phases = get_posts('posts_per_page=-1&post_type=phases&orderby=menu_order&order=ASC');
		foreach ($phases as $phase) { ?>
			<table>
			<tr class="table_header">
				<td style="width: 30%; text-align: left"><?php echo $phase->menu_order.' . '.$phase->post_title;?></td>
				<td style="width: 10%; text-align: left"><?php _e('Price','pbc');?></td>
				<td style="width: 30%; text-align: left"><?php _e('Depends of','pbc');?></td>
				<td style="width: 10%; text-align: left"><?php _e('Icon','pbc');?></td>
				<td style="width: 10%; text-align: left"><?php _e('Product','pbc');?></td>
			</tr>
			<?php
			$args = array(
			  'numberposts' => -1,
			  'post_type' => 'variation',
			  'meta_query' => array (
				array (
				  'key' => 'pbc_phase',
				  'value' => $phase->ID,
				)
			  ) );

			$variation_in_phase = new WP_Query( $args ); ?>
			<?php if ( $variation_in_phase->have_posts() ) : ?>


			<!-- the loop -->
			<?php while ( $variation_in_phase->have_posts() ) : $variation_in_phase->the_post(); ?>
				<tr>
					<td style="width: 30%; text-align: left"><?php //* Title ?>
						<strong><?php the_title(); ?></strong>
					</td>
					<td style="width: 10%; text-align: left"><?php //* Price group
						$price_group = rwmb_meta( 'pbc_pricegroup' );
						$price_column = '';
						foreach($price_group as $price_item) {
							if(isset($price_item['pbc_meaprice'])) {
							$price_column .= $price_item['pbc_meaprice'].' - '.$price_item['pbc_pricem'].' €';
							} else { // Price without any option
							$price_column .= $price_item['pbc_pricem'].' €';
							}
							$price_column .= '<br/>';
						}
						echo $price_column;
						?>
					</td>
					<td style="width: 30%; text-align: left; font-size: 9pt;"><?php //* Depends of
						$depends_group = rwmb_meta( 'pbc_depends' );
						$depends_column = '';
						foreach($depends_group as $depends_item) {
							$variation_id = substr($depends_item['pbc_depvar'], 3);
							$variation_post = get_post($variation_id);
							$phase_id_dp = get_post_meta($variation_id, 'pbc_phase', true);
							$phase_post_dp = get_post($phase_id_dp);
							if($phase_post_dp->menu_order<10) $phase_order = '0'.$phase_post_dp->menu_order; else $phase_order = $phase_post_dp->menu_order;
				        	$depends_column .= $phase_order.' - '.$phase_post_dp->post_title.' - '.$variation_post->post_title;
							$depends_column .= '<br/>';
						}
						echo $depends_column;
						?>
					</td>
					<td style="width: 10%; text-align: left"><?php //* Image Icon
						$imgicon = get_post_meta(get_the_id(), 'pbc_imgicon', true);
						if($imgicon){
							$icon_image = wp_get_attachment_image_src($imgicon, array(105,75), true);
							echo '<img class="imagepdf" src="'.$icon_image[0].'" />';
						}
						?>
					</td>
					<td style="width: 10%; text-align: left"><?php //* Image Product
						$imgprod = get_post_meta(get_the_id(), 'pbc_imgprod', true);
						if($imgprod){
							$icon_image = wp_get_attachment_image_src($imgprod, array(105,75), true);
							echo '<img class="imagepdf" src="'.$icon_image[0].'" />';
						}
						?>
					</td>
				</tr>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>

			<?php endif; ?>
			</table>
		<?php }
		$content = ob_get_contents();
		ob_end_clean();

		if (is_file(WPPBC_PLUGIN_DIR.
			"/lib/html2pdf/html2pdf.class.php")
		)
		{
			require_once(WPPBC_PLUGIN_DIR.
				'/lib/html2pdf/html2pdf.class.php');
			try {
				$files = glob(WPPBC_PLUGIN_DIR."/pdf/*"); // get all file names
				foreach($files as $file){ // iterate files
				  if(is_file($file))
				    unlink($file); // delete file
				}
				$filename = __('List Price','pbc').' '.get_bloginfo('name').' '.date('Y-m-d H:i');
				$width_mm = 710 * 0.2646;   //1px = 0.2646mm
				$height_mm = 900 * 0.2646;
				$html2pdf = new \HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(2.5, 2.5, 2.5, 2.5));
				$html2pdf->setTestTdInOnePage(false);
				$html2pdf->writeHTML($content);
				$html2pdf->Output(WPPBC_PLUGIN_DIR."/pdf/$filename.pdf", 'F');
				//$html2pdf->close();
				$return = array('type'=>'success', 'msg'=>WPPBC_PLUGIN_URL."pdf/$filename.pdf");
			} catch (Html2PdfException $e) {
				$formatter = new ExceptionFormatter($e);
				$return = array('type'=>'error', 'msg'=>"Unexpected Error!<br>Can't load PDF this time!<br>".$formatter->getHtmlMessage());
			}
		}else{
			$return = array('type'=>'error', 'msg'=>'Error: PDF Library Not Present');
		}
		echo ';;--;;'.json_encode($return);
		die(0);
	}
	/**
	 * # LICENSE
	 * ---------------------------------------------------------------------------------------------------- */

	/**
	 * Displays an inactive notice when the software is inactive.
	 */
	public function inactive_notice() {
		/**
		 * @since 2.5.1
		 *
		 * Filter wc_am_client_inactive_notice_override
		 * If set to false inactive_notice() method will be disabled.
		 */
		if ( apply_filters( 'wpat_client_inactive_notice_override', true ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			if ( isset( $_GET['page'] ) && 'wpautotranslate' == $_GET['page'] ) {
				return;
			}
			echo '<div class="notice notice-error">';
			echo '<p>';
			printf(
				__( 'The <strong>%1$s</strong> License has not been activated, so the plugin is inactive! %2$sClick here%3$s to activate it.', 'wpautotranslate' ),
				esc_attr( WPPBC_ITEM_NAME ),
				'<a href="' . esc_url( admin_url( 'network/admin.php?page=wpautotranslate&tab=license' ) ) . '">',
				'</a>'
			);
			echo '</p></div>';
		}
	}
	/**
	 * Callback for Setting license API key
	 *
	 * @return void
	 */
	public function license_status_callback() {
		if ( $this->get_api_key_status( true ) ) {
			$license_status_check = esc_html__( 'Activated', 'wpautotranslate' );
			update_option( 'pbc_license_activated', 'Activated' );
			update_option( 'pbc_license_deactivate_checkbox', 'off' );
		} else {
			$license_status_check = esc_html__( 'Deactivated', 'wpautotranslate' );
		}

		echo esc_attr( $license_status_check );
	}
	/**
	 * Validates license option
	 *
	 * @param array $input Settings input option.
	 * @return mixed|string
	 */
	public function validate_license( $input ) {
		// Load existing options, validate, and update with changes from input before returning.
		$api_key           = trim( $input['pbc_license_apikey'] );
		$activation_status = get_option( 'pbc_license_activated' );
		$checkbox_status   = get_option( 'pbc_license_deactivate_checkbox' );
		$current_api_key   = ! empty( get_option( 'pbc_license_apikey' ) ) ? get_option( 'pbc_license_apikey' ) : '';

		/**
		* @since 2.3
		*/
		if ( isset( $input['pbc_license_product_id'] ) ) {
			$new_product_id = absint( $input['pbc_license_product_id'] );

			if ( ! empty( $new_product_id ) ) {
				update_option( 'pbc_license_product_id', $new_product_id );
			}
		}

		// Deactivates API Key key activation.
		if ( isset( $input['pbc_license_deactivate_checkbox'] ) && 'on' === $input['pbc_license_deactivate_checkbox'] ) {
			$args = array(
				'api_key' => ! empty( $api_key ) ? $api_key : '',
			);
			$deactivation_result = $this->license_deactivate( $args );

			if ( ! empty( $deactivation_result ) ) {

				if ( true === $deactivation_result['success'] && true === $deactivation_result['deactivated'] ) {
					update_option( 'pbc_license_activated', 'Deactivated' );
					update_option( 'pbc_license_apikey', '' );
					update_option( 'pbc_license_product_id', '' );
					add_settings_error( 'wc_am_deactivate_text', 'deactivate_msg', esc_html__( 'License AutoTranslate deactivated. ', 'pbc' ) . esc_attr( "{$deactivation_result['activations_remaining']}." ), 'updated' );

					return;
				}

				if ( isset( $deactivation_result['data']['error_code'] ) && ! empty( $this->data ) && ! empty( 'pbc_license_activated' ) ) {
					add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', esc_attr( "{$deactivation_result['data']['error']}" ), 'error' );
					update_option( 'pbc_license_activated', 'Deactivated' );
				}
			}
			return;
		}

		// Should match the settings_fields() value.
		if ( 'Deactivated' == $activation_status || '' == $activation_status || '' == $api_key || 'on' == $checkbox_status || $current_api_key != $api_key ) {

			/**
			* If this is a new key, and an existing key already exists in the database,
			* try to deactivate the existing key before activating the new key.
			*/
			if ( ! empty( $current_api_key ) && $current_api_key != $api_key ) {
				$this->replace_license_key( $current_api_key );
			}

			$activation_result = $this->license_activate( $api_key );

			if ( ! empty( $activation_result ) ) {
				$activate_results = json_decode( $activation_result, true );

				if ( true === $activate_results['success'] && true === $activate_results['activated'] ) {
					add_settings_error( 'activate_text', 'activate_msg', __( 'AutoTranslate activated. ', 'pbc' ) . esc_attr( "{$activate_results['message']}." ), 'updated' );

					update_option( 'pbc_license_apikey', $api_key );
					update_option( 'pbc_license_activated', 'Activated' );
					update_option( 'pbc_license_deactivate_checkbox', 'off' );
				}

				if ( false == $activate_results && ! empty( get_option( 'pbc_license_activated' ) ) ) {
					add_settings_error( 'api_key_check_text', 'api_key_check_error', esc_html__( 'Connection failed to the License Key API server. Try again later. There may be a problem on your server preventing outgoing requests, or the store is blocking your request to activate the plugin/theme.', 'pbc' ), 'error' );
					update_option( 'pbc_license_activated', 'Deactivated' );
				}

				if ( isset( $activate_results['data']['error_code'] ) && ! empty( get_option( 'pbc_license_activated' ) ) ) {
					add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', esc_attr( "{$activate_results['data']['error']}" ), 'error' );
					update_option( 'pbc_license_activated', 'Deactivated' );
				}
			} else {
				add_settings_error( 'not_activated_empty_response_text', 'not_activated_empty_response_error', esc_html__( 'The API Key activation could not be commpleted due to an unknown error possibly on the store server The activation results were empty.', 'pbc' ), 'updated' );
			}
		} // End Plugin Activation
	}
	/**
	 * Sends the request to activate to the API Manager.
	 *
	 * @param array $api_key API Key to activate.
	 *
	 * @return string
	 */
	public function license_activate( $api_key ) {
		if ( empty( $api_key ) ) {
			add_settings_error( 'not_activated_text', 'not_activated_error', esc_html__( 'The API Key is missing from the deactivation request.', 'wpautotranslate' ), 'updated' );

			return '';
		}

		$defaults            = $this->get_license_defaults( 'activate', true );
		$defaults['api_key'] = $api_key;
		$target_url          = esc_url_raw( $this->create_software_api_url( $defaults ) );
		$request             = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			// Request failed.
			return '';
		}

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Sends the request to deactivate to the API Manager.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function license_deactivate( $args ) {
		if ( empty( $args ) ) {
			add_settings_error( 'not_deactivated_text', 'not_deactivated_error', esc_html__( 'The API Key is missing from the deactivation request.', 'wpautotranslate' ), 'updated' );

			return '';
		}

		$defaults   = $this->get_license_defaults( 'deactivate' );
		$args       = wp_parse_args( $defaults, $args );
		$target_url = esc_url_raw( $this->create_software_api_url( $args ) );
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );
		$body_json  = wp_remote_retrieve_body( $request );
		$result_api = json_decode( $body_json, true );

		$error = ! empty( $result_api['error'] ) ? $result_api['error'] : '';

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 || $error ) {
			// Request failed.
			add_settings_error(
				'not_deactivated_empty_response_text',
				'not_deactivated_empty_response_error',
				$error,
				'error'
			);
			return;
		}

		return $result_api;
	}
	/**
	 * Returns true if the API Key status is Activated.
	 *
	 * @since 2.1
	 *
	 * @param bool $live Do not set to true if using to activate software. True is for live status checks after activation.
	 *
	 * @return bool
	 */
	public function get_api_key_status( $live = false ) {
		/**
		 * Real-time result.
		 *
		 * @since 2.5.1
		 */
		if ( $live ) {
			$license_status = $this->license_key_status();

			return ! empty( $license_status ) && ! empty( $license_status['data'][ 'activated' ] ) && $license_status['data'][ 'activated' ];
		}

		/**
		 * If $live === false.
		 *
		 * Stored result when first activating software.
		 */
		return get_option('pbc_license_activated') == 'Activated';
	}

	/**
	 * Returns the API Key status by querying the Status API function from the WooCommerce API Manager on the server.
	 *
	 * @return array|mixed|object
	 */
	public function license_key_status() {
		$status = $this->status();

		return ! empty( $status ) ? json_decode( $this->status(), true ) : $status;
	}

	/**
	 * Sends the status check request to the API Manager.
	 *
	 * @return bool|string
	 */
	public function status() {
		if ( empty( get_option( 'pbc_license_apikey' ) ) ) {
			return '';
		}

		$defaults   = $this->get_license_defaults( 'status' );
		$target_url = esc_url_raw( $this->create_software_api_url( $defaults ) );
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			// Request failed.
			return '';
		}

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Get license defaults
	 *
	 * @param [type] $action
	 * @return array
	 */
	private function get_license_defaults( $action, $software_version = false ) {
		$api_key    = get_option( 'pbc_license_apikey' );
		$product_id = get_option( 'pbc_license_product_id' );

		$defaults = array(
			'wc_am_action' => $action,
			'api_key'      => $api_key,
			'product_id'   => $product_id,
			'instance'     => get_option( 'pbc_license_instance' ),
			'object'       => str_ireplace( array( 'http://', 'https://' ), '', home_url() ),
		);

		if ( $software_version ) {
			$defaults['software_version'] = WPPBC_VERSION;
		}

		return $defaults;

	}

	/**
	 * Builds the URL containing the API query string for activation, deactivation, and status requests.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function create_software_api_url( $args ) {
		return add_query_arg( 'wc-api', 'wc-am-api', WPPBC_URL_API ) . '&' . http_build_query( $args );
	}

	/**
	 * Generate the default data.
	 */
	public function license_instance_activation() {
		$instance_exists = get_option( 'pbc_license_instance' );

		if ( false === $instance_exists ) {
			update_option( 'pbc_license_instance', wp_generate_password( 12, false ) );
		}
	}

	/**
	 * Deactivate the current API Key before activating the new API Key
	 *
	 * @param string $current_api_key
	 */
	public function replace_license_key( $current_api_key ) {
		$args = array(
			'api_key' => $current_api_key,
		);

		$this->license_deactivate( $args );
	}

	/**
	 * Sends and receives data to and from the server API
	 *
	 * @since  2.0
	 *
	 * @param array $args
	 *
	 * @return bool|string $response
	 */
	public function send_query( $args ) {
		$target_url = esc_url_raw( add_query_arg( 'wc-api', 'wc-am-api', WPPBC_URL_API ) . '&' . http_build_query( $args ) );
		error_log( 'target_url:'.$target_url);
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			return false;
		}

		$response = wp_remote_retrieve_body( $request );

		return ! empty( $response ) ? $response : false;
	}

	/**
	 * Check for updates against the remote server.
	 *
	 * @since  2.0
	 *
	 * @param object $transient Transient plugins.
	 *
	 * @return object
	 */
	public function update_check( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$args = array(
			'wc_am_action' => 'update',
			'slug'         => 'pbc',
			'plugin_name'  => 'pbc',
			'version'      => WPPBC_VERSION,
			'product_id'   => get_option( 'pbc_license_product_id' ),
			'api_key'      => get_option( 'pbc_license_apikey' ),
			'instance'     => get_option( 'pbc_license_instance' ),
		);

		// Check for a plugin update.
		$response = json_decode( $this->send_query( $args ), true );

		if ( isset( $response['data']['error_code'] ) ) {
			add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', "{$response['data']['error']}", 'error' );
		}

		if ( false !== $response && true === $response['success'] ) {
			$new_version  = (string) $response['data']['package']['new_version'];
			$curr_version = (string) WPPBC_VERSION;

			$package = array(
				'id'             => $response['data']['package']['id'],
				'slug'           => $response['data']['package']['slug'],
				'plugin'         => $response['data']['package']['plugin'],
				'new_version'    => $response['data']['package']['new_version'],
				'url'            => $response['data']['package']['url'],
				'tested'         => $response['data']['package']['tested'],
				'package'        => $response['data']['package']['package'],
				'upgrade_notice' => $response['data']['package']['upgrade_notice'],
			);

			if ( isset( $new_version ) && isset( $curr_version ) ) {
				if ( version_compare( $new_version, $curr_version, '>' ) ) {
					$transient->response['pbc'] = (object) $package;
					unset( $transient->no_update['pbc'] );
				}
			}
		}

		return $transient;
	}

	/**
	 * API request for informatin.
	 *
	 * If `$action` is 'query_plugins' or 'plugin_information', an object MUST be passed.
	 * If `$action` is 'hot_tags` or 'hot_categories', an array should be passed.
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Install API.
	 * @param object             $args
	 *
	 * @return object
	 */
	public function information_request( $result, $action, $args ) {
		// Check if this plugins API is about this plugin.
		if ( isset( $args->slug ) ) {
			if ( 'pbc' !== $args->slug ) {
				return $result;
			}
		} else {
			return $result;
		}

		$args = array(
			'wc_am_action' => 'plugininformation',
			'plugin_name'  => 'pbc',
			'version'      => WPPBC_VERSION,
			'product_id'   => get_option( 'pbc_license_product_id' ),
			'api_key'      => get_option( 'pbc_license_apikey' ),
			'instance'     => get_option( 'pbc_license_instance' ),
			'object'       => str_ireplace( array( 'http://', 'https://' ), '', home_url() ),
		);

		$response = unserialize( $this->send_query( $args ) );

		if ( isset( $response ) && is_object( $response ) && false !== $response ) {
			return $response;
		}

		return $result;
	}

	/**
	 * Check for external blocking contstant.
	 */
	public function check_external_blocking() {
		// show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant.
		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && true === WP_HTTP_BLOCK_EXTERNAL ) {
			// check if our API endpoint is in the allowed hosts.
			$host = parse_url( WPPBC_URL_API, PHP_URL_HOST );

			if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							esc_html__( '<b>Warning!</b> You\'re blocking external requests which means you won\'t be able to get %s updates. Please add %s to %s.', 'wpautotranslate' ),
							'AutoTranslate',
							'<strong>' . esc_html( $host ) . '</strong>',
							'<code>WP_ACCESSIBLE_HOSTS</code>'
						);
						?>
					</p>
				</div>
				<?php
			}
		}
	}
}

new Admin_PBCPlugin();
