<?php
/*
Plugin Name: Product Budget Configurator
Plugin URI: https://www.closemarketing.es
Description: Creates a configurator for products and create a budget
Version: 1.0
Author: closemarketing
Author URI: https://www.closemarketing.es

License: GPL
*/

define( 'WPPBC_PLUGIN', __FILE__ );
define( 'WPPBC_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'WPPBC_PLUGIN_DIR', untrailingslashit( dirname( WPPBC_PLUGIN ) ) );

class PBCPlugin
{
	/**
	 * The plugin file
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Construct and intialize
	 */
	public function __construct( $file )
	{
		$this->file = $file;

		register_activation_hook( $this->file, array( $this, 'pbc_install' ) );

        // Initial stuff
		add_action('init', array( $this, 'init' ) );
		add_action('admin_init', array( $this, 'init' ) );
		add_action('admin_footer', array($this,'pbc_admin_scripts') );
		add_filter( 'mb_settings_pages', array($this, 'pbc_settings_pages') );
		add_filter( 'rwmb_meta_boxes', array($this, 'pbc_options_meta_boxes') );

        //Custom Post types stuff
 		add_action('admin_menu', array($this, 'pbc_add_admin_menus'), 1);
		add_action('init', array( $this, 'pbc_register_cpt') );
    add_filter('rwmb_meta_boxes', array( $this, 'pbc_metabox_variation') );

		add_filter( 'disable_months_dropdown' , array($this,'disable_months_dropdown') , 10 , 2 );
		add_filter('manage_edit-phases_columns', array($this,'add_new_phases_columns') );
		add_action('manage_phases_posts_custom_column', array($this,'manage_phases_columns'), 10, 2);

		add_filter('manage_edit-variation_columns', array($this,'add_new_var_columns') );
		add_action('manage_variation_posts_custom_column', array($this,'manage_var_columns'), 10, 2);
		add_action('restrict_manage_posts', array($this, 'pbc_admin_posts_filter') );
		add_filter('parse_query', array($this, 'pbc_posts_filter') );

		add_filter( 'template_include', array($this,'pbc_custom_page_template'), 99 );
		add_action('wp_ajax_variation_selected', array($this,'variation_selected_action_callback') );
		add_action('wp_ajax_nopriv_variation_selected', array($this,'variation_selected_action_callback') );
		add_action('wp_ajax_configurator_submit', array($this,'configurator_submit_action_callback') );
		add_action('wp_ajax_nopriv_configurator_submit', array($this,'configurator_submit_action_callback') );
		//on variation-lists admin screen
		add_filter( 'views_edit-variation', array($this,'pbc_add_print_pdf_button') );
		add_action( 'admin_head-edit.php', array($this,'pbc_move_print_pdf_button') );
		add_action('wp_ajax_print_pdf', array($this,'print_pdf_action_callback') );
	}

	////////////////////////////////////////////////////////////

	/**
	 * PBC Install
	 *
	 * Hook after plugin installed
	 */
	public function pbc_install()
	{
	}

	/**
	 * Initialize
	 */
	public function init()
	{
        /**
         * Composer Library dependencies
         */
        require_once plugin_dir_path( __FILE__) . 'vendor/autoload.php';

        /**
         * Localization
         */
        load_plugin_textdomain( 'pbc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        /**
         * Image Sizes
         */
        add_image_size( 'pbc_icon', 150, 230, false );
	    add_image_size( 'pbc_product', 570, 460, true );

	}
	/**
	 * PBC Admin Scripts
	 */
	public function pbc_admin_scripts()
	{
		$screen = get_current_screen();
		if( !empty($screen) && ($screen->parent_base == 'pbc_menu'))
		{
			wp_enqueue_script('post');
			$this->plugin_admin_scripts();
		}
		return null;
	}

	public function plugin_admin_scripts()
	{
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
     * Registering menu admin
     *
     */

    public function pbc_add_admin_menus(){

         // Settings for custom admin menu
         $page_title = __('Product Budget Configurator','pbc');
         $menu_title = 'PBC';
         $capability = 'manage_options';
         $menu_slug  = 'pbc_menu';
         $function   = array($this,'pbc_display_admin_page');// Callback function which displays the page content.
         $icon_url   = 'dashicons-tagcloud';
         $position   = 2;

         // Add custom admin menu
         add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);

         $submenu_pages = array(

             // Avoid duplicate pages. Add submenu page with same slug as parent slug.
             array(
                 'parent_slug'   => 'pbc_menu',
                 'page_title'    => __('Product Budget Configurator','pbc'),
                 'menu_title'    => __('Settings','pbc'),
                 'capability'    => 'manage_options',
                 'menu_slug'     => 'pbc_menu',
                 'function'      => array($this,'pbc_display_admin_page'),// Uses the same callback function as parent menu.
             ),

             // Post Type :: View All Posts
             array(
                 'parent_slug'   => 'pbc_menu',
                 'page_title'    => __('Phases of Configurator','pbc'),
                 'menu_title'    => __('Phases','pbc'),
                 'capability'    => 'manage_options',
                 'menu_slug'     => 'edit.php?post_type=phases',
                 'function'      => null,// Doesn't need a callback function.
             ),

			 // Post Type :: View All Posts
			 array(
			 	'parent_slug'   => 'pbc_menu',
			 	'page_title'    => __('Variations in Phases','pbc'),
			 	'menu_title'    => __('Variations','pbc'),
			 	'capability'    => 'manage_options',
			 	'menu_slug'     => 'edit.php?post_type=variation',
			 	'function'      => null,// Doesn't need a callback function.
			 ),

         );

         // Add each submenu item to custom admin menu.
         foreach($submenu_pages as $submenu){

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

     /* If you add any extra custom sub menu pages which are not a Custom Post Type
      * or a Custom Taxonomy, you will need to create a callback function for each
      * of your custom submenu items you create above.
      */

    public function pbc_display_admin_page(){

		if(isset($_POST['form_submit']))
		{
			if(isset($_POST['select_budget_page'])){
				update_option('pbc_budget_configurator_page', $_POST['select_budget_page']);
				$update = __("Successfully Saved!",'pbc');
			}
			if ( isset( $_POST['pdf_image_selected'] ) ){
				update_option( 'pbc_pdf_image_selected', $_POST['pdf_image_selected'] );
				$update = __("Successfully Saved!",'pbc');
			}
			$variations_images_flipped = isset( $_POST['variations_images_flipped'] ) ? $_POST['variations_images_flipped'] : array('');
			update_option( 'variations_images_flipped', $variations_images_flipped );
		}
	?>
		<div class='wrap'>
			<h2><?php echo $GLOBALS['title'] ?> - <?php _e('Global Settings','pbc');?></h2>

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
						add_meta_box("phases_lists_meta_box", __("All Phases Lists", "pbc"), array($this, "phases_lists_meta_box_callback"), "pbc_import_left");
		                do_meta_boxes('pbc_import_left','advanced', null);
						?>
					</div>
					<div class="postcontent-right">
						<?php
						add_meta_box("general_settings_meta_box", __("General Settings","pbc"), array($this, "general_settings_meta_box_callback"), "pbc_import_right");
		                do_meta_boxes('pbc_import_right','advanced',null);
						?>
					</div>
				</div>
			</div>
		</div>
	<?php
    }

	/**
	 * Options Metabox
	 *
	 *
	 */

	function pbc_settings_pages( $settings_pages )
	{
		$settings_pages[] = array(
			'id'            => 'options_flip',
			'parent'				=> 'pbc_menu',
			'option_name'   => 'options_flip',
			'menu_title'    => __( 'Image Effect flip conditions', 'pbc' ),
			'icon_url'      => 'dashicons-images-alt',
			'submenu_title' => __( 'Settings', 'pbc' ),
		);
		return $settings_pages;
	}
	function pbc_options_meta_boxes( $meta_boxes )
	{
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
		$meta_boxes[] = array(
			'id'             => 'general',
			'title'          => __( 'General', 'textdomain' ),
			'settings_pages' => 'options_flip',
			'fields'         => array(
    			// SELECT BOX VARIATIONS
    			array(
    				'name'        => 'Variación',
    				'id'          => "pbc_depvar_flip",
    				'type'        => 'select',
    				'options'     => $var_options,
    				'multiple'    => true,
    				'std'         => '',
    				'placeholder' => 'No depende de una variación',
    			),
			), //array fields
		);
		return $meta_boxes;
	}
	/**
	 * Import Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function phases_lists_meta_box_callback()
	{
	?>
	<table class="phases-lists-table">
		<tr>
			<th class="order-col"><?php _e('Order','pbc');?></th>
			<th class="phases-col"><?php _e('Phases','pbc');?></th>
			<th class="variations-col"><?php _e('Number of Variations','pbc');?></th>
		</tr>
		<?php $phases = get_posts('posts_per_page=-1&post_type=phases&orderby=menu_order&order=ASC');
		if(!empty($phases)){
			foreach($phases as $phase){?>
				<tr>
					<td class="order-col"><?php echo $phase->menu_order;?></td>
					<td class="phases-col"><?php echo $phase->post_title;?></td>
					<td class="variations-col"><?php $variations = get_posts('posts_per_page=-1&post_type=variation&meta_key=pbc_phase&meta_value='.$phase->ID.'&fields=ids');
					if(!empty($variations)) echo count($variations);
					?></td>
				</tr>
			<?php
			}
		}?>
	</table>
    <?php
	}

	/**
	 * General Settings Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function general_settings_meta_box_callback()
	{
	?>
	<form action="" method="post" enctype="multipart/form-data" id="pbc_general_settings_form">
		<div class="content">
			<fieldset>
				<label class="block" for="select_budget_page"><?php _e("Budget Configurator Page", 'pbc');?></label>
				<?php
				$budget_configurator = get_option('pbc_budget_configurator_page');
				$pages = get_pages();
				if(!empty($pages))
				{
					echo '<select name="select_budget_page">
						<option value="">Select a Page</option>';
					foreach ( $pages as $page ) {
						$option = '<option value="' .( $page->ID ) . '"';
						$option .= ($page->ID == $budget_configurator) ? " selected='selected'" : "";
						$option .= '>'.$page->post_title.'</option>';
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
					$pdf_image_selected = get_option('pbc_pdf_image_selected');
				?>
				<input type="text" name="pdf_image_selected" value="<?php if($pdf_image_selected) echo $pdf_image_selected;?>" /><button class="select-image button"><?php _e('Select image','pbc');?></button>
			</fieldset>
			<fieldset>
				<br/>
				<label class="block" for="variations_images_flipped"><?php _e("Flip Images Horizontal", 'pbc');?></label>
				<?php
					$variations_images_flipped = get_option('variations_images_flipped');
					$phases =  get_posts(array('post_type'=>'phases','posts_per_page'=>-1,'orderby'=>'menu_order','order'=>'ASC'));
					if(!empty($phases)){
						echo '<select multiple="multiple" name="variations_images_flipped[]" size="5">';
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
		</div>

		<div class="save_bar">
			<input type="hidden" name="form_submit" value="true"/>
			<input type="submit" value="<?php _e('Save', 'pbc');?>" class="button button-primary submit-button" />
		</div>
	</form>
    <?php
	}

    /**
     * Post Type Phases
     */
    public function pbc_register_cpt()
    {
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

    function pbc_metabox_variation( $meta_boxes )
    {
		// Phase options
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

    	$prefix = 'pbc_';
    	// 1st meta box
    	$meta_boxes[] = array(
    		'id'         => 'standard',
    		'title'      => 'Opciones para la variación',
    		'post_types' => array( 'variation' ),
    		'context'    => 'normal',
    		'priority'   => 'high',
    		'autosave'   => true,

    		'fields'     => array(
    			// SELECT BOX PHASE
    			array(
    				'name'        => 'Fase',
    				'id'          => "{$prefix}phase",
    				'type'        => 'select',
    				'options'     => $phase_options,
    				'multiple'    => false,
    				'std'         => '',
    				'placeholder' => 'Selecciona una Fase',
    			),
    			// TEXT
    			array(
    				'name'  => 'Referencia',
    				'id'    => "{$prefix}sku",
    				'desc'  => '',
    				'type'  => 'text',
    				'std'   => '',
    				'clone' => false,
                    'columns' => 3,
    			),
    			// IMAGE ADVANCED (WP 3.5+)
    			array(
    				'name'             => 'Imagen icono',
    				'id'               => "{$prefix}imgicon",
    				'type'             => 'image_advanced',
    				'max_file_uploads' => 1,
    			),

				array(
    				'name'   => 'Depende de',
					'id'     => "{$prefix}depends",
					'type'   => 'group',
					'clone'  => true,
					'sort_clone' => true,
					'fields' => array(
		    			// SELECT BOX VARIATIONS
		    			array(
		    				'name'        => 'Variación',
		    				'id'          => "{$prefix}depvar",
		    				'type'        => 'select',
		    				'options'     => $var_options,
		    				'multiple'    => false,
		    				'std'         => '',
		    				'placeholder' => 'No depende de una variación',
		    			),
					),
				), //array

				array(
    			'name'   => 'Grupos Imagen Producto',
					'id'     => "{$prefix}imgprodgroup",
					'type'   => 'group',
					'clone'  => true,
					'sort_clone' => true,
					'fields' => array(
		    			// SELECT BOX VARIATIONS
		    			array(
		    				'name'        => 'Variación',
		    				'id'          => "{$prefix}depvarimgprod",
		    				'type'        => 'select',
		    				'options'     => $var_options,
		    				'multiple'    => true,
		    				'std'         => '',
		    				'placeholder' => 'No depende de una variación',
		    			),
		    			// IMAGE ADVANCED (WP 3.5+)
		    			array(
		    				'name'             => 'Imagen del producto',
		    				'id'               => "{$prefix}imgprod",
		    				'type'             => 'image_advanced',
		    				'max_file_uploads' => 1,
		    			),
					),
				), //array
				array(
    				'name'   => 'Precio',
					'id'     => "{$prefix}pricegroup",
					'type'   => 'group',
					'clone'  => true,
					'sort_clone' => true,
					'fields' => array(
		    			// TEXT
		    			array(
		    				'name'  => 'Opción del precio',
		    				'id'    => "{$prefix}meaprice",
		    				'desc'  => '',
		    				'type'  => 'text',
		    				'std'   => '',
		    				'clone' => false,
		                    'columns' => 3,
		    			),
		    			// TEXT
		    			array(
		    				'name'  => 'Precio (IVA No incluido)',
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
				$_SESSION['pbc_output'] = $this->configurator_result_email_send($_POST['email_field']);
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
					        $html2pdf->close();
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
			$imgprodgroup = get_post_meta($sVar, 'pbc_imgprodgroup', true);
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
								isset($_SESSION['pbc_variation'][$sPhaseKey]) &&
								in_array($_SESSION['pbc_variation'][$sPhaseKey]['var']['id'], $prevVar[$sPhaseKey]))
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
			if(!empty($variations_images_flipped) && in_array($sVar, $variations_images_flipped))
				$flipped = true;
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
			$_SESSION['pbc_output'] = $this->configurator_result_email_send($email_field);
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
	public function configurator_result_email_send($email){
		if(!$email){
			$result = array('type'=>'error', 'response'=>'Email field empty!');
		}else{
			$emails = explode(',', $email);
			if(!isset($_SESSION['pbc_variation'])){
				$result = array('type'=>'error', 'response'=>'Configurator not ready!');
			}else{
				$subject = get_option('blogname').' Budget Configurator';
				$message = '<h3>'.__('Here are the details of your selection:','pbc').'</h3>'.'<br>';
				$message .= '<table><tr><th>'.__('Phase','pbc').'</th><th>'.__('Variation','pbc').'</th><th>'.__('Price','pbc').'</th></tr>';
				$total_price = '';
				foreach($_SESSION['pbc_variation'] as $phaseKey => $details){
					$total_price += $details['var']['price'];
					$message .= '<tr>';
					$message .= '<td>'.$details['phase']['name'].'</td>';
					$message .= '<td>'.$details['var']['name'].'</td>';
					$message .= '<td>'.$details['var']['price'].'</td>';
					$message .= '</tr>';
				}
				if($total_price) $total_price = $total_price.' €';
				else $total_price = '-';
				$message .= '<tr>';
				$output .= '<td>&nbsp;</td><td>Total: </td>';
				$message .= '<td>'.$total_price.'</td>';
				$message .= '</tr>';
				$message .= '</table>';
				$message .= '<br>'.get_option('blogname');

				function set_html_content_type() {
					return 'text/html';
				}
				add_filter( 'wp_mail_content_type', 'set_html_content_type' );
			    if(!wp_mail( $emails, $subject, $message)){
					$result = array('type'=>'error', 'response'=>__('Error in sending mail. Please try again!','pbc'));
				}else
					$result = array('type'=>'success', 'response'=>'Mail sent!');
				remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
			}
		}
		return $result;
	}
	public function configurator_result_generate_pdf(){
		if(!isset($_SESSION['pbc_variation'])){
			$result = array('type'=>'error', 'response'=>__('Configurator not ready!','pbc'));
		}else{
			$output = '';
		    $output .= "<page backcolor='#fafafa'>";
			$output .= "<style>
			.product_preview {
		        position: relative;
		        text-align: left;
				width: 500px;
		        max-width: 500px;
		    }
		    .product_preview .image-wrap img:first-child{position: relative;}
		    .product_preview .image-wrap img{width: 100%;max-width: 500px;position: absolute;top: 0;left: 0;}
			</style>";
			// $pdf_image_selected = get_option('pbc_pdf_image_selected');
			// if($pdf_image_selected)
			// 	$output .="<img src='".$pdf_image_selected."' width='200'/>";
			$output .= "<div class=\"product_preview\"><div class=\"image-wrap\">";
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
						$output .= '<img phaseid="'.$i.'" src="'.$imgprodurl[0].'" alt="product image"/>';
					}
				}
			}
			$output .= "</div></div>";

			$output .="<h1>".get_option('blogname')."</h1>";
			$output .="<h3>".__('Details of Your Selection','pbc')."</h3>";
			$output .= '<table><tr><th>'.__('Phase','pbc').'</th><th>'.__('Variation','pbc').'</th><th>'.__('Price','pbc').'</th></tr>';
			$total_price = '';
			foreach($_SESSION['pbc_variation'] as $phaseKey => $details){
				$total_price += $details['var']['price'];
				$output .= '<tr>';
				$output .= '<td>'.$details['phase']['name'].'</td>';
				$output .= '<td>'.$details['var']['name'].'</td>';
				$output .= '<td>'.$details['var']['price'].'</td>';
				$output .= '</tr>';
			}
			if($total_price) $total_price = $total_price.' €';
			else $total_price = '-';
			$output .= '<tr>';
			$output .= '<td>&nbsp;</td><td>'.__('Total:','pbc').'</td>';
			$output .= '<td>'.$total_price.'</td>';
			$output .= '</tr>';
			$output .= '</table>';

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
				$('#print-message').html('<img src="<?php echo WPPBC_PLUGIN_URL;?>loading.gif"/>');
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
}

global $pbc_plugin;

$pbc_plugin = new PBCPlugin( __FILE__ );



/**
 * Register the required plugins for this plugin.
 *
 * This function is hooked into tgmpa_init, which is fired within the
 * TGM_Plugin_Activation class constructor.
 */
 require_once dirname( __FILE__ ) . '/vendor/tgmpa/tgm-plugin-activation/class-tgm-plugin-activation.php';

add_action('tgmpa_register', 'pbc_required_plugins' );
function pbc_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(
		array(
			'name'               => 'Meta Box Group', // The plugin name.
			'slug'               => 'meta-box-group', // The plugin slug (typically the folder name).
			'source'             => dirname( __FILE__ ) . '/lib/plugins/meta-box-group.zip',
			'required'           => true,
			'version'            => '',
			'force_activation'   => true,
			'force_deactivation' => false,
			'external_url'       => '',
			'is_callable'        => '',
		),
		array(
			'name'      => 'Meta Box',
			'slug'      => 'meta-box',
			'required'  => true,
			'force_activation'   => true,
		),
		array(
			'name'      => 'Duplicate Post',
			'slug'      => 'duplicate-post',
			'required'  => true,
			'force_activation'   => true,
		),

		array(
			'name'               => 'Meta Box Settings', // The plugin name.
			'slug'               => 'mb-settings-page', // The plugin slug (typically the folder name).
			'source'             => dirname( __FILE__ ) . '/lib/plugins/mb-settings-page.zip',
			'required'           => true,
			'version'            => '',
			'force_activation'   => true,
			'force_deactivation' => false,
			'external_url'       => '',
			'is_callable'        => '',
		),

	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id'           => 'pbc',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'plugins.php',            // Parent menu slug.
		'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
		'strings'      => array(
			'page_title'                      => __('Install Required Plugins','pbc'),
			'menu_title'                      => __('Install Plugins','pbc'),
			/* translators: %s: plugin name. */
			'installing'                      => __('Installing Plugin: %s','pbc'),
			/* translators: %s: plugin name. */
			'updating'                        => __('Updating Plugin: %s','pbc'),
			'oops'                            => __('Something went wrong with the plugin API.','pbc'),
			'notice_can_install_required'     => _n_noop(
				/* translators: 1: plugin name(s). */
				'This theme requires the following plugin: %1$s.',
				'This theme requires the following plugins: %1$s.',
				'pbc'
			),
			'notice_can_install_recommended'  => _n_noop(
				/* translators: 1: plugin name(s). */
				'This theme recommends the following plugin: %1$s.',
				'This theme recommends the following plugins: %1$s.',
				'pbc'
			),
			'notice_ask_to_update'            => _n_noop(
				/* translators: 1: plugin name(s). */
				'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
				'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
				'pbc'
			),
			'notice_ask_to_update_maybe'      => _n_noop(
				/* translators: 1: plugin name(s). */
				'There is an update available for: %1$s.',
				'There are updates available for the following plugins: %1$s.',
				'pbc'
			),
			'notice_can_activate_required'    => _n_noop(
				/* translators: 1: plugin name(s). */
				'The following required plugin is currently inactive: %1$s.',
				'The following required plugins are currently inactive: %1$s.',
				'pbc'
			),
			'notice_can_activate_recommended' => _n_noop(
				/* translators: 1: plugin name(s). */
				'The following recommended plugin is currently inactive: %1$s.',
				'The following recommended plugins are currently inactive: %1$s.',
				'pbc'
			),
			'install_link'                    => _n_noop(
				'Begin installing plugin',
				'Begin installing plugins',
				'pbc'
			),
			'update_link' 					  => _n_noop(
				'Begin updating plugin',
				'Begin updating plugins',
				'pbc'
			),
			'activate_link'                   => _n_noop(
				'Begin activating plugin',
				'Begin activating plugins',
				'pbc'
			),
			'return'                          => __('Return to Required Plugins Installer','pbc'),
			'plugin_activated'                => __('Plugin activated successfully.','pbc'),
			'activated_successfully'          => __('The following plugin was activated successfully:','pbc'),
			/* translators: 1: plugin name. */
			'plugin_already_active'           => __('No action taken. Plugin %1$s was already active.','pbc'),
			/* translators: 1: plugin name. */
			'plugin_needs_higher_version'     => __('Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.','pbc'),
			/* translators: 1: dashboard link. */
			'complete'                        => __('All plugins installed and activated successfully. %1$s','pbc'),
			'dismiss'                         => __('Dismiss this notice','pbc'),
			'notice_cannot_install_activate'  => __('There are one or more required or recommended plugins to install, update or activate.','pbc'),
			'contact_admin'                   => __('Please contact the administrator of this site for help.','pbc'),

			'nag_type'                        => '', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
		),

	);

	tgmpa( $plugins, $config );

}
