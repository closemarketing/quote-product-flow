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
		add_action( 'add_meta_boxes_enquiry', array( $this, 'pbc_metabox_enquiry' ) );

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
				// QUESTION MODE.
				array(
					'name' => __( 'Convert to Question', 'pbc' ),
					'id'   => "{$prefix}is_question",
					'type' => 'checkbox',
					'desc' => __( 'When enabled, this variation will show as an input field for the user to answer. The answer can be used in dependencies.', 'pbc' ),
					'std'  => 0,
				),
				array(
					'name'    => __( 'Question Key', 'pbc' ),
					'id'      => "{$prefix}question_key",
					'type'    => 'text',
					'desc'    => __( 'Unique identifier for this question (e.g., house_m2, height). Use lowercase and underscores.', 'pbc' ),
					'visible' => array( "{$prefix}is_question", '=', 1 ),
				),
				array(
					'name'    => __( 'Input Type', 'pbc' ),
					'id'      => "{$prefix}question_input_type",
					'type'    => 'select',
					'options' => array(
						'number' => __( 'Number', 'pbc' ),
						'text'   => __( 'Text', 'pbc' ),
					),
					'std'     => 'number',
					'desc'    => __( 'Type of input field to show', 'pbc' ),
					'visible' => array( "{$prefix}is_question", '=', 1 ),
				),
				array(
					'name'    => __( 'Placeholder', 'pbc' ),
					'id'      => "{$prefix}question_placeholder",
					'type'    => 'text',
					'desc'    => __( 'Placeholder text for the input (e.g., "Introduce los m²")', 'pbc' ),
					'visible' => array( "{$prefix}is_question", '=', 1 ),
				),
				array(
					'name'    => __( 'Required', 'pbc' ),
					'id'      => "{$prefix}question_required",
					'type'    => 'checkbox',
					'desc'    => __( 'Make this question required', 'pbc' ),
					'std'     => 1,
					'visible' => array( "{$prefix}is_question", '=', 1 ),
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
				// Question dependencies.
				array(
					'name'       => __( 'Depends on Question Answers', 'pbc' ),
					'id'         => "{$prefix}question_depends",
					'type'       => 'group',
					'clone'      => true,
					'sort_clone' => true,
					'desc'       => __( 'Show this variation only when question answers meet these conditions', 'pbc' ),
					'fields'     => array(
						array(
							'name'    => __( 'Question Key', 'pbc' ),
							'id'      => "{$prefix}question_key_ref",
							'type'    => 'text',
							'desc'    => __( 'The question key to check (e.g., house_m2)', 'pbc' ),
							'columns' => 3,
						),
						array(
							'name'    => __( 'Operator', 'pbc' ),
							'id'      => "{$prefix}question_operator",
							'type'    => 'select',
							'options' => array(
								'>'  => __( 'Greater than (>)', 'pbc' ),
								'>=' => __( 'Greater than or equal (>=)', 'pbc' ),
								'<'  => __( 'Less than (<)', 'pbc' ),
								'<=' => __( 'Less than or equal (<=)', 'pbc' ),
								'='  => __( 'Equal (=)', 'pbc' ),
								'!=' => __( 'Not equal (!=)', 'pbc' ),
							),
							'std'     => '>',
							'columns' => 3,
						),
						array(
							'name'    => __( 'Value', 'pbc' ),
							'id'      => "{$prefix}question_value",
							'type'    => 'text',
							'desc'    => __( 'The value to compare against', 'pbc' ),
							'columns' => 3,
						),
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
	public function add_new_phases_columns( $phases_columns ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		// Parameter required by WordPress filter hook.
		unset( $phases_columns );
		$new_columns['cb']         = '<input type="checkbox" />';
		$new_columns['title']      = __( 'Phase', 'pbc' );
		$new_columns['menu_order'] = __( 'Order', 'pbc' );
		$new_columns['variations'] = __( 'Variations', 'pbc' );
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
			case 'variations':
				// Count variations for this phase.
				$variations = get_posts(
					array(
						'post_type'      => 'variation',
						'posts_per_page' => -1,
						'meta_key'       => 'pbc_phase',
						'meta_value'     => $id,
						'fields'         => 'ids',
					)
				);
				$count      = ! empty( $variations ) ? count( $variations ) : 0;

				// Create link to variations filtered by this phase.
				$url = add_query_arg(
					array(
						'post_type'        => 'variation',
						'pbc_filter_phase' => $id,
					),
					admin_url( 'edit.php' )
				);

				if ( $count > 0 ) {
					echo '<a href="' . esc_url( $url ) . '" title="' . esc_attr__( 'View variations of this phase', 'pbc' ) . '">';
					echo esc_html( $count );
					echo '</a>';
				} else {
					echo esc_html( $count );
				}
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
	public function add_new_budgets_columns( $phases_columns ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		// Parameter required by WordPress filter hook.
		unset( $phases_columns );
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
	public function add_new_var_columns( $phases_columns ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		// Parameter required by WordPress filter hook.
		unset( $phases_columns );
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
					$phase_parent      = get_post( $phase_post->post_parent );
					$phase_parent_link = admin_url( 'edit.php?post_type=phases#post-' . $phase_parent->ID );
					echo '<a href="' . esc_url( $phase_parent_link ) . '" title="' . esc_attr__( 'Go to phases list', 'pbc' ) . '">';
					echo esc_html( $phase_parent->post_title );
					echo '</a><br/>';
				}
				// Phase link - goes to phases list and scrolls to this phase.
				$phase_list_link = admin_url( 'edit.php?post_type=phases#post-' . $phase_id );
				echo '<a href="' . esc_url( $phase_list_link ) . '" title="' . esc_attr__( 'Go to phases list', 'pbc' ) . '">';
				echo esc_html( CALC::adds_zero( $phase_post->menu_order ) . ' - ' . $phase_post->post_title );
				echo '</a>';

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
		global $typenow;

		if ( 'variation' !== $typenow ) {
			return;
		}

		// Get current filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET request for admin filter, no data modification.
		$selected = isset( $_GET['pbc_filter_phase'] ) ? (int) $_GET['pbc_filter_phase'] : 0;

		// Get ALL phases.
		$phases = get_posts(
			array(
				'post_type'      => 'phases',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		if ( empty( $phases ) ) {
			return;
		}
		?>
		<select name="pbc_filter_phase" id="pbc_phase_select">
			<option value=""><?php esc_html_e( '📋 All Phases', 'pbc' ); ?></option>
			<?php
			foreach ( $phases as $phase ) {
				$count = count(
					get_posts(
						array(
							'post_type'      => 'variation',
							'posts_per_page' => -1,
							'meta_key'       => 'pbc_phase',
							'meta_value'     => $phase->ID,
							'fields'         => 'ids',
						)
					)
				);

				$name = CALC::adds_zero( $phase->menu_order ) . ' - ' . $phase->post_title;

				if ( $phase->post_parent > 0 ) {
					$parent = get_post( $phase->post_parent );
					if ( $parent ) {
						$name = $parent->post_title . ' → ' . $name;
					}
				}

				$name .= ' (' . $count . ')';

				printf(
					'<option value="%d"%s>%s</option>',
					(int) $phase->ID,
					selected( $selected, $phase->ID, false ),
					esc_html( $name )
				);
			}
			?>
		</select>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#pbc_phase_select').on('change', function() {
				$(this).closest('form').submit();
			});

			// Hide the filter button.
			$('#post-query-submit').hide();
		});
		</script>
		<?php
	}

	/**
	 * If submitted filter by post meta
	 *
	 * Make sure to change META_KEY to the actual meta key
	 * and variation to the name of your custom post type
	 *
	 * @param (wp_query object) $query Query.
	 * @return object Modified query object.
	 */
	public function pbc_posts_filter( $query ) {
		global $pagenow, $typenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || 'variation' !== $typenow || ! $query->is_main_query() ) {
			return $query;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET request for admin filter, no data modification.
		if ( isset( $_GET['pbc_filter_phase'] ) && $_GET['pbc_filter_phase'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$phase_id = (int) $_GET['pbc_filter_phase'];
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( $phase_id > 0 ) {
				$query->set( 'meta_key', 'pbc_phase' );
				$query->set( 'meta_value', $phase_id );
			}
		}

		return $query;
	}
}

new PBC_Helper_PostTypes();
