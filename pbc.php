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

		add_filter( 'template_include', array($this,'pbc_custom_page_template'), 99 );
		add_action( 'wp_ajax_variation_selected', array($this,'variation_selected_action_callback') );
		add_action( 'wp_ajax_nopriv_variation_selected', array($this,'variation_selected_action_callback') );
		add_action( 'wp_ajax_configurator_submit', array($this,'configurator_submit_action_callback') );
		add_action( 'wp_ajax_nopriv_configurator_submit', array($this,'configurator_submit_action_callback') );

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
			$phase_id = get_post_meta($var_item->ID, 'pbc_phase', true);
			$phase_post = get_post($phase_id);
			if($phase_post->menu_order<10) $phase_order = '0'.$phase_post->menu_order; else $phase_order = $phase_post->menu_order;
        	$var_options[$var_item->ID] = $phase_order.' - '.$phase_post->post_title.' - '.$var_item->post_title;
        }
		asort($var_options);

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
	    $new_columns['depends'] = __('Depends of','pbc');

	    return $new_columns;
	}


	public function manage_var_columns($column_name, $id) {
	    global $wpdb, $post;

		$phase_id = get_post_meta(get_the_id(),'pbc_phase',true);
		$price = get_post_meta(get_the_id(),'pbc_price',true);
		$depends_group = rwmb_meta( 'pbc_depends' );
		$depends_column = '';
		foreach($depends_group as $depends_item) {
			$var_post = get_post($depends_item['pbc_depvar']);
			$phase_id = get_post_meta($var_post->ID, 'pbc_phase', true);
			$phase_post = get_post($phase_id);
			if($phase_post->menu_order<10) $phase_order = '0'.$phase_post->menu_order; else $phase_order = $phase_post->menu_order;
        	$depends_column .= $phase_order.' - '.$phase_post->post_title.' - '.$var_post->post_title;
			$depends_column .= '<br/>';
		}

	    switch ($column_name) {

	    case 'phase':
			$phase_post = get_post($phase_id);
	        echo $phase_post->menu_order.' - '.$phase_post->post_title;
	        break;
	    case 'price':
	        echo $price;
	        break;
	    case 'depends':
			echo $depends_column;
	        break;
	    default:
	        break;
	    } // end switch
	}


	public function pbc_custom_page_template( $template ) {
		if ( \is_page( 'budget-configurator' )  ) {
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
			$imgprod = get_post_meta($sVar, 'pbc_imgprod', true);
			if($imgprod){ $imgprodurl = wp_get_attachment_image_src($imgprod, 'full', true)[0];}?>
		<?php }
		if(isset($imgprodurl) && $imgprodurl){
			$return = $imgprodurl;
		}else{
			$return = WPPBC_PLUGIN_URL.'preview-img.jpg';
		}
		echo ';;--;;'.json_encode(array('type'=>'success', 'url'=>$return));
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
				$message = '<h3>Here are the details of your selection:</h3>'.'<br>';
				$message .= '<table><tr><th>Phase</th><th>Variation</th><th>Price</th></tr>';
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
				$message .= '<br>Thank You!';
				$message .= '<br>'.get_option('blogname');

				function set_html_content_type() {
					return 'text/html';
				}
				add_filter( 'wp_mail_content_type', 'set_html_content_type' );
			    if(!wp_mail( $emails, $subject, $message)){
					$result = array('type'=>'error', 'response'=>'Error in sending mail. Please try again!');
				}else
					$result = array('type'=>'success', 'response'=>'Mail sent!');
				remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
			}
		}
		return $result;
	}
	public function configurator_result_generate_pdf(){
		if(!isset($_SESSION['pbc_variation'])){
			$result = array('type'=>'error', 'response'=>'Configurator not ready!');
		}else{
		    $output .= "<page backcolor='#fafafa'>
			<h1>".get_option('blogname')." Budget Configurator</h1>
			<h3>Details of Your Selection</h3>";
			$output .= '<table><tr><th>Phase</th><th>Variation</th><th>Price</th></tr>';
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
			$output .= '<td>&nbsp;</td><td>Total: </td>';
			$output .= '<td>'.$total_price.'</td>';
			$output .= '</tr>';
			$output .= '</table>';

			$output .= '</page>';
			$result = array('type'=>'success', 'response'=>$output);
		}
		return $result;
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

add_action( 'tgmpa_register', 'pbc_required_plugins' );
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
			'page_title'                      => __( 'Install Required Plugins', 'pbc' ),
			'menu_title'                      => __( 'Install Plugins', 'pbc' ),
			/* translators: %s: plugin name. */
			'installing'                      => __( 'Installing Plugin: %s', 'pbc' ),
			/* translators: %s: plugin name. */
			'updating'                        => __( 'Updating Plugin: %s', 'pbc' ),
			'oops'                            => __( 'Something went wrong with the plugin API.', 'pbc' ),
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
			'return'                          => __( 'Return to Required Plugins Installer', 'pbc' ),
			'plugin_activated'                => __( 'Plugin activated successfully.', 'pbc' ),
			'activated_successfully'          => __( 'The following plugin was activated successfully:', 'pbc' ),
			/* translators: 1: plugin name. */
			'plugin_already_active'           => __( 'No action taken. Plugin %1$s was already active.', 'pbc' ),
			/* translators: 1: plugin name. */
			'plugin_needs_higher_version'     => __( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'pbc' ),
			/* translators: 1: dashboard link. */
			'complete'                        => __( 'All plugins installed and activated successfully. %1$s', 'pbc' ),
			'dismiss'                         => __( 'Dismiss this notice', 'pbc' ),
			'notice_cannot_install_activate'  => __( 'There are one or more required or recommended plugins to install, update or activate.', 'pbc' ),
			'contact_admin'                   => __( 'Please contact the administrator of this site for help.', 'pbc' ),

			'nag_type'                        => '', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
		),

	);

	tgmpa( $plugins, $config );

}
