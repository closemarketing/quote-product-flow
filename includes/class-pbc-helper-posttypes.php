<?php
/**
 * Class for Calculations
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

use Close\PBC\Helpers\CALC;

/**
 * Helper Post Types.
 *
 * All helpers calculations.
 *
 * @since 1.1
 */
class PBC_Helper_PostTypes {

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'pbc_register_cpt' ) );
		add_filter( 'rwmb_meta_boxes', array( $this, 'pbc_metabox_variation' ) );
		add_filter( 'add_meta_boxes_enquiry', array( $this, 'pbc_metabox_enquiry' ) );

		add_filter( 'manage_edit-phases_columns', array( $this, 'add_new_phases_columns' ) );
		add_action( 'manage_phases_posts_custom_column', array( $this, 'manage_phases_columns' ), 10, 2 );

		add_filter( 'manage_edit-enquiry_columns', array( $this, 'add_new_budgets_columns' ) );
		add_action( 'manage_enquiry_posts_custom_column', array( $this, 'manage_budgets_columns' ), 10, 2 );

		add_filter( 'manage_edit-variation_columns', array( $this, 'add_new_var_columns' ) );
		add_action( 'manage_variation_posts_custom_column', array( $this, 'manage_var_columns' ), 10, 2 );

		add_action( 'restrict_manage_posts', array( $this, 'admin_posts_filter' ) );
		add_filter( 'parse_query', array( $this, 'pbc_posts_filter' ) );
	}

	/**
	 * Register post types
	 *
	 * @return void
	 */
	public function pbc_register_cpt() {
		$labels = array(
			'name'               => __( 'Phases', 'pbc' ),
			'singular_name'      => __( 'Phase', 'pbc' ),
			'add_new'            => __( 'Add Phase', 'pbc' ),
			'add_new_item'       => __( 'Add New Phase', 'pbc' ),
			'edit_item'          => __( 'Edit Phase', 'pbc' ),
			'new_item'           => __( 'New Phase ', 'pbc' ),
			'view_item'          => __( 'View Phase ', 'pbc' ),
			'search_items'       => __( 'Search for Phases', 'pbc' ),
			'not_found'          => __( "We didn't find any phase", 'pbc' ),
			'not_found_in_trash' => __( "We didn't find an phase in the trash", 'pbc' ),
		);
		$args   = array(
			'labels'             => $labels,
			'public'             => false,
			'show_in_menu'       => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'query_var'          => true,
			'rewrite'            => false,
			'has_archive'        => false,
			'capability_type'    => 'page',
			'hierarchical'       => true,
			'menu_position'      => 5,
			'supports'           => array( 'title', 'editor', 'page-attributes' ),
		);
		register_post_type( 'phases', $args );

		$labels = array(
			'name'               => __( 'Variations', 'pbc' ),
			'singular_name'      => __( 'Variation', 'pbc' ),
			'add_new'            => __( 'Add Variation', 'pbc' ),
			'add_new_item'       => __( 'Add New variation', 'pbc' ),
			'edit_item'          => __( 'Edit Variation', 'pbc' ),
			'new_item'           => __( 'New Variation', 'pbc' ),
			'view_item'          => __( 'View Variation', 'pbc' ),
			'search_items'       => __( 'Search for variations', 'pbc' ),
			'not_found'          => __( "We didn't find any Variation", 'pbc' ),
			'not_found_in_trash' => __( "We didn't find any Variation in the trash", 'pbc' ),
		);
		$args   = array(
			'labels'             => $labels,
			'public'             => false,
			'show_in_menu'       => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'query_var'          => true,
			'rewrite'            => false,
			'has_archive'        => false,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_position'      => 5,
			'supports'           => array( 'title' ),
		);
		register_post_type( 'variation', $args );

		/**
		 * Register Taxonomy Sections
		 */
		$labels = array(
			'name'          => __( 'Sections', 'pbc' ),
			'singular_name' => __( 'Section', 'pbc' ),
			'search_items'  => __( 'Search Section', 'pbc' ),
			'all_items'     => __( 'All Sections', 'pbc' ),
			'edit_item'     => __( 'Edit Section', 'pbc' ),
			'update_item'   => __( 'Update Section', 'pbc' ),
			'add_new_item'  => __( 'Add New Section', 'pbc' ),
			'new_item_name' => __( 'Add New Section', 'pbc' ),
		);

		register_taxonomy(
			'variation_tag',
			array(
				'variation',
			),
			array(
				'hierarchical'       => false,
				'public'             => false,
				'publicly_queryable' => true,
				'labels'             => $labels,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_admin_column'  => true,
				'query_var'          => true,
				'rewrite'            => false,
			)
		);

		$labels = array(
			'name'               => __( 'Enquiries', 'pbc' ),
			'singular_name'      => __( 'Enquiry', 'pbc' ),
			'add_new'            => __( 'Add Enquiry', 'pbc' ),
			'add_new_item'       => __( 'Add New Enquiry', 'pbc' ),
			'edit_item'          => __( 'Edit Enquiry', 'pbc' ),
			'new_item'           => __( 'New Enquiry', 'pbc' ),
			'view_item'          => __( 'View Enquiry', 'pbc' ),
			'search_items'       => __( 'Search for Enquiry', 'pbc' ),
			'not_found'          => __( 'We didn\'t find any Enquiry', 'pbc' ),
			'not_found_in_trash' => __( 'We didn\'t find any Enquiry in the trash', 'pbc' ),
		);
		$args   = array(
			'labels'             => $labels,
			'public'             => false,
			'show_in_menu'       => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'query_var'          => true,
			'rewrite'            => false,
			'has_archive'        => false,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_position'      => 5,
			'supports'           => array( 'title' ),
		);
		register_post_type( 'enquiry', $args );
	}

	/**
	 * Metabox variations
	 *
	 * @param array $meta_boxes Metaboxes.
	 * @return array
	 */
	public function pbc_metabox_variation( $meta_boxes ) {
		$phase_options = array();
		$var_options   = array();

		if ( is_admin() ) {
			// Phase options.
			$phase_options = CALC::get_phases_options();

			// Variations Options.
			$variationscpt = get_posts(
				array(
					'post_type'      => 'variation',
					'posts_per_page' => -1,
					'orderby'        => 'name',
					'order'          => 'ASC',
				)
			);
			foreach ( $variationscpt as $var_item ) {
				$phase_id   = get_post_meta( $var_item->ID, 'pbc_phase', true );
				$phase_post = get_post( $phase_id );
				if ( empty( $phase_post ) ) {
					continue;
				}
				$phase_title    = '';
				$post_parent_id = isset( $phase_post->post_parent ) ? $phase_post->post_parent : 0;
				if ( $post_parent_id > 0 ) {
					$phase_parent = get_post( $post_parent_id );
					$phase_title .= $phase_parent->post_title . ' - ';
				}

				$phase_order = CALC::adds_zero( $phase_post->menu_order );
				$var_value   = $phase_order . '|' . $var_item->ID;
				$var_sku     = get_post_meta( $var_item->ID, 'pbc_sku', true );

				$phase_title              .= $phase_order . ' - ' . $phase_post->post_title . ' - ' . $var_item->post_title;
				$phase_title              .= ! empty( $var_sku ) ? ' (' . $var_sku . ')' : '';
				$var_options[ $var_value ] = $phase_title;
			}
			asort( $var_options );
		}

		$prefix = 'pbc_';
		// 1st meta box.
		$meta_boxes[] = array(
			'id'         => 'standard',
			'title'      => __( 'Options for variation', 'pbc' ),
			'post_types' => array( 'variation' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'autosave'   => true,
			'fields'     => array(
				// SELECT BOX PHASE.
				array(
					'name'        => __( 'Phase', 'pbc' ),
					'id'          => "{$prefix}phase",
					'type'        => 'select',
					'options'     => $phase_options,
					'multiple'    => false,
					'std'         => '',
					'placeholder' => __( 'Select a phase', 'pbc' ),
				),
				// TEXT.
				array(
					'name'  => __( 'Reference', 'pbc' ),
					'id'    => "{$prefix}sku",
					'desc'  => '',
					'type'  => 'text',
					'std'   => '',
					'clone' => false,
				),
				// SELECT BOX PHASE.
				array(
					'name'        => __( 'Field type', 'pbc' ),
					'id'          => "{$prefix}field_type",
					'type'        => 'select',
					'options'     => array(
						''    => __( 'Default by price', 'pbc' ),
						'qty' => __( 'Quantity', 'pbc' ),
					),
					'multiple'    => false,
					'std'         => '',
					'placeholder' => __( 'Select a phase', 'pbc' ),
				),
				// IMAGE ADVANCED (WP 3.5+).
				array(
					'name'             => __( 'Icon image', 'pbc' ),
					'id'               => "{$prefix}imgicon",
					'type'             => 'image_advanced',
					'max_file_uploads' => 1,
				),
				array(
					'name'       => __( 'Depends of', 'pbc' ),
					'id'         => "{$prefix}depends",
					'type'       => 'group',
					'clone'      => true,
					'sort_clone' => true,
					'fields'     => array(
						// SELECT BOX VARIATIONS.
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
				),
				array(
					'name'       => __( 'Product group image', 'pbc' ),
					'id'         => "{$prefix}imgprodgroup",
					'type'       => 'group',
					'clone'      => true,
					'sort_clone' => true,
					'fields'     => array(
						// SELECT BOX VARIATIONS.
						array(
							'name'        => __( 'Depends of Variation', 'pbc' ),
							'id'          => "{$prefix}depvarimgprod",
							'type'        => 'select_advanced',
							'options'     => $var_options,
							'multiple'    => true,
							'std'         => '',
							'placeholder' => 'No depende de una variación',
						),
						// IMAGE ADVANCED (WP 3.5+).
						array(
							'name'             => __( 'Product image', 'pbc' ),
							'id'               => "{$prefix}imgprod",
							'type'             => 'image_advanced',
							'max_file_uploads' => 1,
						),
					),
				), // array.
				array(
					'name'       => __( 'Price', 'pbc' ),
					'id'         => "{$prefix}pricegroup",
					'type'       => 'group',
					'clone'      => true,
					'sort_clone' => true,
					'fields'     => array(
						// TEXT.
						array(
							'name'    => __( 'Option price', 'pbc' ),
							'id'      => "{$prefix}meaprice",
							'desc'    => '',
							'type'    => 'text',
							'std'     => '',
							'clone'   => false,
							'columns' => 3,
						),
						// TEXT.
						array(
							'name'    => __( 'Price (VAT not included)', 'pbc' ),
							'id'      => "{$prefix}pricem",
							'desc'    => '',
							'type'    => 'text',
							'std'     => '',
							'clone'   => false,
							'columns' => 1,
						),
					),
				), // array.
				// Desc HTML.
				array(
					'name'    => __( 'Description after option', 'pbc' ),
					'id'      => "{$prefix}descopt",
					'type'    => 'wysiwyg',
					'raw'     => false,
					'options' => array(
						'textarea_rows' => 5,
						'teeny'         => true,
					),
				),
				// Desc HTML.
				array(
					'name'    => __( 'Description', 'pbc' ),
					'id'      => "{$prefix}descvar",
					'type'    => 'wysiwyg',
					'raw'     => false,
					'options' => array(
						'textarea_rows' => 8,
						'teeny'         => true,
					),
				),
			),
		);

		return $meta_boxes;
	}

	/**
	 * Metabox enquiry
	 *
	 * @return void
	 */
	public function pbc_metabox_enquiry() {
		add_meta_box(
			'enquiry-details',
			__( 'Enquiry Details', 'pbc' ),
			array( $this, 'render_enquiry_details' ),
			'enquiry',
			'normal',
			'default'
		);

		add_meta_box(
			'configuration-details',
			__( 'Budget Configuration', 'pbc' ),
			array( $this, 'render_budget_configuration' ),
			'enquiry',
			'normal',
			'default'
		);
	}

	/**
	 * Render enquiry details
	 *
	 * @param object $post Post object.
	 * @return void
	 */
	public function render_enquiry_details( $post ) {
		$post_id       = is_object( $post ) ? $post->ID : $post;
		$enquiry_name  = get_post_meta( $post_id, 'pbc_enquiry_name', true );
		$enquiry_phone = get_post_meta( $post_id, 'pbc_enquiry_phone', true );
		$enquiry_email = get_post_meta( $post_id, 'pbc_enquiry_email', true );
		$enquiry_city  = get_post_meta( $post_id, 'pbc_enquiry_city', true );
		$enquiry_state = get_post_meta( $post_id, 'pbc_enquiry_state', true );
		$comments      = get_post_meta( $post_id, 'pbc_enquiry_comments', true );
		$parent_phase  = (int) get_post_meta( $post_id, 'pbc_parent_phase', true );
		?>
		<div><label><strong><?php esc_html_e( 'Enquiry ID:', 'pbc' ); ?></strong> <?php echo esc_html( $post_id ); ?></label></div>
		<?php if ( ! empty( $enquiry_name ) ) { ?>
			<div><label><strong><?php esc_html_e( 'Name:', 'pbc' ); ?></strong> <?php echo esc_html( $enquiry_name ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_phone ) ) { ?>
			<div><label><strong><?php esc_html_e( 'Phone:', 'pbc' ); ?></strong> <?php echo esc_html( $enquiry_phone ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_email ) ) { ?>
			<div><label><strong><?php esc_html_e( 'Email:', 'pbc' ); ?></strong> <?php echo esc_html( $enquiry_email ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_city ) ) { ?>
			<div><label><strong><?php esc_html_e( 'City:', 'pbc' ); ?></strong> <?php echo esc_html( $enquiry_city ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_state ) ) { ?>
			<div><label><strong><?php esc_html_e( 'State:', 'pbc' ); ?></strong> <?php echo esc_html( $enquiry_state ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_state ) ) { ?>
			<div><label><strong><?php esc_html_e( 'Comments:', 'pbc' ); ?></strong> <?php echo esc_html( $comments ); ?></label></div>
		<?php } ?>
		<?php
		if ( $parent_phase ) {
			$parent_phase_post = get_post( $parent_phase );
			echo '<div><label><strong>' . esc_html__( 'Product:', 'pbc' ) . '</strong> ' . esc_html( $parent_phase_post->post_title ) . '</label></div>';
		}
	}
	/**
	 * Renders the budget Configuration
	 *
	 * @param object $post Post object.
	 * @return void
	 */
	public function render_budget_configuration( $post ) {
		$post_id = is_object( $post ) ? $post->ID : $post;
		?>
		<table>
			<thead>
				<tr>
					<th style="width:20%" class="sn">#</th>
					<th style="width:50%" class="phase-variation"><?php esc_html_e( 'Phase/Variation', 'pbc' ); ?></th>
					<th style="width:30%" class="price"><?php esc_html_e( 'Price', 'pbc' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ( $i = 0; $i < 50; $i++ ) {
					$phase_var = get_post_meta( $post_id, 'pbc_phase_var_' . $i, true );
					if ( $phase_var ) {
						?>
						<tr>
							<td class="sn"><?php echo (int) $i; ?></td>
							<td class="phase-variation"><?php echo esc_html( $phase_var ); ?></td>
							<td class="price"><?php echo esc_html( get_post_meta( $post_id, 'pbc_price_' . $i, true ) ); ?></td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Add columns for Phases
	 *
	 * @param array $phases_columns Columns.
	 * @return array
	 */
	public function add_new_phases_columns( $phases_columns ) {
		$new_columns['cb']         = '<input type="checkbox" />';
		$new_columns['title']      = __( 'Phase', 'pbc' );
		$new_columns['menu_order'] = __( 'Order', 'pbc' );
		$new_columns['shortcode']  = __( 'Shortcode', 'pbc' );

		return $new_columns;
	}

	/**
	 * Manages columns for Budget
	 *
	 * @param string  $column_name Name of the column.
	 * @param integer $id Post ID.
	 * @return void
	 */
	public function manage_phases_columns( $column_name, $id ) {
		$post        = get_post( $id );
		$is_multiple = CALC::is_multiple_products();

		switch ( $column_name ) {
			case 'menu_order':
				echo isset( $post->menu_order ) ? esc_html( $post->menu_order ) : '';
				break;
			case 'shortcode':
				$post_parent = $post->post_parent;
				if ( empty( $post_parent ) && $is_multiple ) {
					echo '<input type="text" value="[pbc pid=' . (int) $id . ']" readonly style="min-width:130px"/>';
				}
				break;
			default:
				break;
		} // end switch
	}

	/**
	 * Add columns for Budget
	 *
	 * @param array $phases_columns Columns.
	 * @return array
	 */
	public function add_new_budgets_columns( $phases_columns ) {
		$new_columns['cb']              = '<input type = "checkbox" />';
		$new_columns['enquiry_name']    = __( 'Budget', 'pbc' );
		$new_columns['enquiry_details'] = __( 'Details', 'pbc' );
		$new_columns['enquiry_conf']    = __( 'Configuration', 'pbc' );
		$new_columns['enquiry_date']    = __( 'Date', 'pbc' );

		return $new_columns;
	}

	/**
	 * Manages columns for Budget
	 *
	 * @param string  $column_name Name of the column.
	 * @param integer $id Post ID.
	 * @return void
	 */
	public function manage_budgets_columns( $column_name, $id ) {
		switch ( $column_name ) {
			case 'enquiry_name':
				echo '<a href="' . esc_url( get_edit_post_link( $id ) ) . '" class="row-title">';
				$name = get_post_meta( $id, 'pbc_enquiry_name', true );
				if ( empty( $name ) ) {
					$name = get_the_title( $id );
				}
				echo esc_html( $name );
				echo '</a>';
				break;
			case 'enquiry_details':
				$this->render_enquiry_details( $id );
				break;
			case 'enquiry_conf':
				echo number_format( CALC::get_total_from_enquiry( $id ), 2, ',', '.' ) . ' € ' . esc_html__( 'VAT not included', 'pbc' );
				break;
			case 'enquiry_date':
				echo get_the_date( 'd-m-Y H:i', $id );
				echo '<br/><button class="button generate-pbc-pdf" data-post-id="' . (int) $id . '">';
				esc_html_e( 'Generate PDF', 'pbc' );
				echo '</button><span id="pbc-pdf-' . (int) $id . '" class="spinner"></span>';
				break;
			default:
				break;
		} // end switch
	}

	/**
	 * Add columns for Variations
	 *
	 * @param array $phases_columns Columns.
	 * @return array
	 */
	public function add_new_var_columns( $phases_columns ) {
		$new_columns['cb']      = '<input type="checkbox" />';
		$new_columns['title']   = __( 'Variation', 'pbc' );
		$new_columns['phase']   = __( 'Phase and section', 'pbc' );
		$new_columns['price']   = __( 'Price', 'pbc' );
		$new_columns['depends'] = __( 'Depends of', 'pbc' );
		$new_columns['imgicon'] = __( 'Icon', 'pbc' );
		$new_columns['imgprod'] = __( 'Product Images', 'pbc' );

		return $new_columns;
	}

	/**
	 * Manages columns for Variations
	 *
	 * @param string  $column_name Name of the column.
	 * @param integer $id Post ID.
	 * @return void
	 */
	public function manage_var_columns( $column_name, $id ) {
		$phase_id = get_post_meta( $id, 'pbc_phase', true );

		switch ( $column_name ) {
			case 'phase':
				$phase_post = get_post( $phase_id );
				// Phase parent.
				if ( $phase_post->post_parent > 0 ) {
					$phase_parent = get_post( $phase_post->post_parent );
					echo esc_html( $phase_parent->post_title ) . ' <br/>';
				}
				echo esc_html( CALC::adds_zero( $phase_post->menu_order ) . ' - ' . $phase_post->post_title );

				// Shows taxonomy.
				$term_list = wp_get_post_terms( $id, 'variation_tag', array( 'fields' => 'all' ) );
				foreach ( $term_list as $term_single ) {
					echo '<p class="taxonomy-variation_tag">' . esc_html( $term_single->name ) . '</p>';
				}
				break;
			case 'price':
				// Price group.
				$price_group = rwmb_meta( 'pbc_pricegroup' );
				foreach ( $price_group as $price_item ) {
					if ( isset( $price_item['pbc_meaprice'] ) ) {
						echo esc_attr( $price_item['pbc_meaprice'] ) . ' - ' . esc_attr( $price_item['pbc_pricem'] ) . ' €';
					} else {
						// Price without any option.
						echo esc_attr( $price_item['pbc_pricem'] ) . ' €';
					}
					echo '<br/>';
				}
				break;
			case 'depends':
				// Depends group.
				$depends_group = rwmb_meta( 'pbc_depends' );
				foreach ( $depends_group as $depends_item ) {
					$depvar         = explode( '|', $depends_item['pbc_depvar'] );
					$variation_id   = isset( $depvar[1] ) ? (int) $depvar[1] : 0;
					$variation_post = get_post( $variation_id );
					$phase_id_dp    = get_post_meta( $variation_id, 'pbc_phase', true );
					$phase_post_dp  = get_post( $phase_id_dp );
					$phase_order    = '';
					$phase_order   .= CALC::adds_zero( $phase_post_dp->menu_order );
					echo esc_html( $phase_order ) . ' - ' . esc_html( $phase_post_dp->post_title ) . ' - ';
					echo esc_html( $variation_post->post_title ) . '<br/>';
				}
				break;
			case 'imgicon':
				// Image icon.
				$imgicon = get_post_meta( $id, 'pbc_imgicon', true );
				if ( $imgicon ) {
					$icon_image = wp_get_attachment_image_src( $imgicon, array( 120, 120 ), true );
				}
				if ( isset( $icon_image ) ) {
					echo '<img src="' . esc_url( $icon_image[0] ) . '" />';
				}
				break;
			case 'imgprod':
				// Image Group Product.
				$image_group = rwmb_meta( 'pbc_imgprodgroup' );
				if ( ! empty( $image_group ) ) {
					if ( count( $image_group ) > 0 ) {
						echo count( $image_group ) . '<br>';
					}
					foreach ( $image_group as $imageg_item ) {
						if ( isset( $imageg_item['pbc_imgprod'][0] ) ) {
							$icon_imageprod = wp_get_attachment_image_src( $imageg_item['pbc_imgprod'][0], array( 57, 46 ), true );
							echo '<img src="' . esc_url( $icon_imageprod[0] ) . '" width="57" height="46"/>';
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
	 *
	 * @return void
	 */
	public function admin_posts_filter() {
		$type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'variation';

		// Only add filter to post type you want.
		if ( 'variation' === $type ) {
			$phase_options = CALC::get_phases_options();
			?>
			<select name="pbc_filter_phase">
			<option value=""><?php esc_html_e( 'All Phases', 'pbc' ); ?></option>
			<?php
			$current_v = isset( $_GET['pbc_filter_phase'] ) ? sanitize_text_field( wp_unslash( $_GET['pbc_filter_phase'] ) ) : '';
			foreach ( $phase_options as $value => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_html( $value ),
					$value == $current_v ? ' selected="selected"' : '',
					esc_html( $label )
				);
			}
			?>
			</select>
			<?php
		} //variation type
	}

	/**
	 * If submitted filter by post meta
	 *
	 * Make sure to change META_KEY to the actual meta key
	 * and variation to the name of your custom post type
	 *
	 * @param (wp_query object) $query Query.
	 * @return void
	 */
	public function pbc_posts_filter( $query ) {
		global $pagenow;
		$type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post';

		if ( 'variation' == $type && is_admin() && 'edit.php' === $pagenow ) {
			if ( isset( $_GET['pbc_filter_phase'] ) && '' !== $_GET['pbc_filter_phase'] ) {
				$query->query_vars['meta_key']   = 'pbc_phase';
				$query->query_vars['meta_value'] = sanitize_text_field( wp_unslash( $_GET['pbc_filter_phase'] ) );
			}
		}
	}
}

new PBC_Helper_PostTypes();
