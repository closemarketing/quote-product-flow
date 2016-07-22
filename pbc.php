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
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'admin_init' ) );

        //Custom Post types stuff
        add_action( 'init', array( $this, 'pbc_register_cpt') );
        add_filter( 'rwmb_meta_boxes', array( $this, 'pbc_metabox_variation') );

		add_filter('manage_edit-phases_columns', array($this,'add_new_phases_columns') );
		add_action('manage_phases_posts_custom_column', array($this,'manage_phases_columns'), 10, 2);

		add_filter('manage_edit-variation_columns', array($this,'add_new_var_columns') );
		add_action('manage_variation_posts_custom_column', array($this,'manage_var_columns'), 10, 2);
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
	   require_once plugin_dir_path( __FILE__) . 'vendor/rilwis/meta-box-group/meta-box-group.php';

        /**
         * Localization
         */
        load_plugin_textdomain( 'pbc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	/**
	 * Admin Initialize
	 */
	public function admin_init()
	{
		add_menu_page( __('Product Budget Configurator','pbc'), __('PBC','pbc'), 'manage_options', 'pbc_menu', array($this,'pbc_menu_page'), 'dashicons-schedule');
	}
    /**
     * PBC Options Page
     */
    public function pbc_menu_page()
    {
    ?>
        <div class='wrap'>
            <h2><?php echo $GLOBALS['title'] ?> - <?php _e('Global Settings','cmo');?></h2>

            <?php if($update){?>
                <div id="message" class="updated fade"><?php echo $update;?></div>
            <?php }?>
            <?php if($error){?>
                <div id="message" class="error"><?php echo $error;?></div>
            <?php }?>

            <div id="poststuff">
                <div id="post-body">
                    <div class="postcontent-left">
                        ?>
                    </div>
                    <div class="postcontent-right">
                    </div>
                </div>
            </div>
        </div>
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
         'show_in_menu' => 'pbc_menu',
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
         'show_in_menu' => 'pbc_menu',
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
    }

    /**
     * Registering meta boxes for variation
     *
     */
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
           $var_options[$var_item->ID] = $var_item->post_title;
        }

    	$prefix = 'pbc_';
    	// 1st meta box
    	$meta_boxes[] = array(
    		'id'         => 'standard',
    		'title'      => __( 'Options for Variation', 'pbc' ),
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
    				'placeholder' => __( 'Select a Phase', 'pbc' ),
    			),
    			// TEXT
    			array(
    				'name'  => __( 'SKU', 'pbc' ),
    				'id'    => "{$prefix}sku",
    				'desc'  => '',
    				'type'  => 'text',
    				'std'   => '',
    				'clone' => false,
                    'columns' => 3,
    			),
    			// TEXT
    			array(
    				'name'  => __( 'Price', 'pbc' ),
    				'id'    => "{$prefix}price",
    				'desc'  => '',
    				'type'  => 'text',
    				'std'   => '',
    				'clone' => false,
                    'columns' => 1,
    			),
    			// IMAGE ADVANCED (WP 3.5+)
    			array(
    				'name'             => esc_html__( 'Image Product', 'pbc' ),
    				'id'               => "{$prefix}imgprod",
    				'type'             => 'image_advanced',
    				'max_file_uploads' => 1,
    			),
    			// IMAGE ADVANCED (WP 3.5+)
    			array(
    				'name'             => esc_html__( 'Image Icon', 'pbc' ),
    				'id'               => "{$prefix}imgicon",
    				'type'             => 'image_advanced',
    				'max_file_uploads' => 1,
    			),

				array(
    				'name'   => esc_html__( 'Depends of', 'pbc' ),
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
		    				'placeholder' => __( 'Select a Variation', 'pbc' ),
		    			),
					),
				), //array
    		)
    	);

    	return $meta_boxes;
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

	    return $new_columns;
	}


	public function manage_var_columns($column_name, $id) {
	    global $wpdb, $post;

		$phase_id = get_post_meta(get_the_id(),'pbc_phase',true);
		$price = get_post_meta(get_the_id(),'pbc_price',true);

	    switch ($column_name) {

	    case 'phase':
			$phase_post = get_post($phase_id);
	        echo $phase_post->menu_order.' - '.$phase_post->post_title;
	        break;
	    case 'price':
	        echo $price;
	        break;
	    default:
	        break;
	    } // end switch
	}

}

global $pbc_plugin;

$pbc_plugin = new PBCPlugin( __FILE__ );
