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
		add_action( 'add_meta_boxes_variation', array( $this, 'pbc_metabox_variation' ) );
		add_action( 'add_meta_boxes_phases', array( $this, 'pbc_metabox_phase' ) );
		add_action( 'save_post_variation', array( $this, 'save_variation_meta' ), 5, 2 );
		add_action( 'save_post_phases', array( $this, 'save_phase_meta' ), 5, 2 );
		add_action( 'add_meta_boxes_enquiry', array( $this, 'pbc_metabox_enquiry' ) );
		add_action( 'save_post_enquiry', array( $this, 'save_budget_configuration_lines' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_enquiry_budget_admin_assets' ) );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'use_classic_editor_for_enquiry' ), 100, 2 );

		add_filter( 'manage_edit-phases_columns', array( $this, 'add_new_phases_columns' ) );
		add_action( 'manage_phases_posts_custom_column', array( $this, 'manage_phases_columns' ), 10, 2 );

		add_filter( 'manage_edit-enquiry_columns', array( $this, 'add_new_budgets_columns' ) );
		add_action( 'manage_enquiry_posts_custom_column', array( $this, 'manage_budgets_columns' ), 10, 2 );

		add_filter( 'manage_edit-variation_columns', array( $this, 'add_new_var_columns' ) );
		add_action( 'manage_variation_posts_custom_column', array( $this, 'manage_var_columns' ), 10, 2 );

		add_action( 'restrict_manage_posts', array( $this, 'admin_posts_filter' ) );
		add_filter( 'parse_query', array( $this, 'pbc_posts_filter' ) );

		// Validate question key on save.
		add_action( 'save_post_variation', array( $this, 'validate_question_key' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'show_duplicate_key_notice' ) );

		// Duplicate post.
		add_filter( 'post_row_actions', array( $this, 'add_duplicate_row_action' ), 10, 2 );
		add_action( 'admin_action_pbc_duplicate_post', array( $this, 'handle_duplicate_post' ) );
	}

	/**
	 * Register post types
	 *
	 * @return void
	 */
	public function pbc_register_cpt() {
		$labels = array(
			'name'               => __( 'Phases', 'product-budget-configurator' ),
			'singular_name'      => __( 'Phase', 'product-budget-configurator' ),
			'add_new'            => __( 'Add Phase', 'product-budget-configurator' ),
			'add_new_item'       => __( 'Add New Phase', 'product-budget-configurator' ),
			'edit_item'          => __( 'Edit Phase', 'product-budget-configurator' ),
			'new_item'           => __( 'New Phase ', 'product-budget-configurator' ),
			'view_item'          => __( 'View Phase ', 'product-budget-configurator' ),
			'search_items'       => __( 'Search for Phases', 'product-budget-configurator' ),
			'not_found'          => __( "We didn't find any phase", 'product-budget-configurator' ),
			'not_found_in_trash' => __( "We didn't find an phase in the trash", 'product-budget-configurator' ),
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
			'name'               => __( 'Variations', 'product-budget-configurator' ),
			'singular_name'      => __( 'Variation', 'product-budget-configurator' ),
			'add_new'            => __( 'Add Variation', 'product-budget-configurator' ),
			'add_new_item'       => __( 'Add New variation', 'product-budget-configurator' ),
			'edit_item'          => __( 'Edit Variation', 'product-budget-configurator' ),
			'new_item'           => __( 'New Variation', 'product-budget-configurator' ),
			'view_item'          => __( 'View Variation', 'product-budget-configurator' ),
			'search_items'       => __( 'Search for variations', 'product-budget-configurator' ),
			'not_found'          => __( "We didn't find any Variation", 'product-budget-configurator' ),
			'not_found_in_trash' => __( "We didn't find any Variation in the trash", 'product-budget-configurator' ),
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
			'name'          => __( 'Sections', 'product-budget-configurator' ),
			'singular_name' => __( 'Section', 'product-budget-configurator' ),
			'search_items'  => __( 'Search Section', 'product-budget-configurator' ),
			'all_items'     => __( 'All Sections', 'product-budget-configurator' ),
			'edit_item'     => __( 'Edit Section', 'product-budget-configurator' ),
			'update_item'   => __( 'Update Section', 'product-budget-configurator' ),
			'add_new_item'  => __( 'Add New Section', 'product-budget-configurator' ),
			'new_item_name' => __( 'Add New Section', 'product-budget-configurator' ),
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
			'name'               => __( 'Enquiries', 'product-budget-configurator' ),
			'singular_name'      => __( 'Enquiry', 'product-budget-configurator' ),
			'add_new'            => __( 'Add Enquiry', 'product-budget-configurator' ),
			'add_new_item'       => __( 'Add New Enquiry', 'product-budget-configurator' ),
			'edit_item'          => __( 'Edit Enquiry', 'product-budget-configurator' ),
			'new_item'           => __( 'New Enquiry', 'product-budget-configurator' ),
			'view_item'          => __( 'View Enquiry', 'product-budget-configurator' ),
			'search_items'       => __( 'Search for Enquiry', 'product-budget-configurator' ),
			'not_found'          => __( 'We didn\'t find any Enquiry', 'product-budget-configurator' ),
			'not_found_in_trash' => __( 'We didn\'t find any Enquiry in the trash', 'product-budget-configurator' ),
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
	 * Register metaboxes for variation post type.
	 *
	 * @return void
	 */
	public function pbc_metabox_variation() {
		add_meta_box(
			'pbc-variation-options',
			__( 'Options for variation', 'product-budget-configurator' ),
			array( $this, 'render_variation_metabox' ),
			'variation',
			'normal',
			'high'
		);
	}

	/**
	 * Register metaboxes for phases post type.
	 *
	 * @return void
	 */
	public function pbc_metabox_phase() {
		add_meta_box(
			'pbc-phase-options',
			__( 'Phase Options', 'product-budget-configurator' ),
			array( $this, 'render_phase_metabox' ),
			'phases',
			'normal',
			'high'
		);
	}

	/**
	 * Build variation options for select fields (variation selector).
	 *
	 * @param int $current_post_id Current post ID to exclude from self-reference.
	 * @return array
	 */
	private function get_var_options( $current_post_id = 0 ) {
		$var_options   = array();
		$variationscpt = get_posts(
			array(
				'post_type'      => 'variation',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		foreach ( $variationscpt as $var_item ) {
			if ( $current_post_id && $var_item->ID === $current_post_id ) {
				continue;
			}
			$phase_id   = get_post_meta( $var_item->ID, 'pbc_phase', true );
			$phase_post = get_post( $phase_id );
			if ( empty( $phase_post ) ) {
				continue;
			}
			$phase_title    = '';
			$post_parent_id = isset( $phase_post->post_parent ) ? (int) $phase_post->post_parent : 0;
			if ( $post_parent_id > 0 ) {
				$phase_parent = get_post( $post_parent_id );
				if ( $phase_parent ) {
					$phase_title .= $phase_parent->post_title . ' - ';
				}
			}
			$phase_order = CALC::adds_zero( $phase_post->menu_order );
			$var_value   = $phase_order . '|' . $var_item->ID;
			$var_sku     = get_post_meta( $var_item->ID, 'pbc_sku', true );

			$label                     = $phase_title . $phase_order . ' - ' . $phase_post->post_title . ' - ' . $var_item->post_title;
			$label                    .= ! empty( $var_sku ) ? ' (' . $var_sku . ')' : '';
			$var_options[ $var_value ] = $label;
		}
		asort( $var_options );
		return $var_options;
	}

	/**
	 * Render the variation metabox.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_variation_metabox( $post ) {
		$post_id       = $post->ID;
		$phase_options = CALC::get_phases_options();
		$var_options   = $this->get_var_options( $post_id );

		$pbc_phase              = get_post_meta( $post_id, 'pbc_phase', true );
		$pbc_sku                = get_post_meta( $post_id, 'pbc_sku', true );
		$pbc_field_type         = get_post_meta( $post_id, 'pbc_field_type', true );
		$pbc_is_question        = (bool) get_post_meta( $post_id, 'pbc_is_question', true );
		$pbc_question_key       = get_post_meta( $post_id, 'pbc_question_key', true );
		$pbc_question_input_type = get_post_meta( $post_id, 'pbc_question_input_type', true );
		$pbc_question_placeholder = get_post_meta( $post_id, 'pbc_question_placeholder', true );
		$pbc_question_required  = get_post_meta( $post_id, 'pbc_question_required', true );
		$pbc_show_custom_input  = (bool) get_post_meta( $post_id, 'pbc_show_custom_input', true );
		$pbc_imgicon            = get_post_meta( $post_id, 'pbc_imgicon', true );
		$pbc_descopt            = get_post_meta( $post_id, 'pbc_descopt', true );
		$pbc_descvar            = get_post_meta( $post_id, 'pbc_descvar', true );

		$pbc_depends        = get_post_meta( $post_id, 'pbc_depends', true );
		$pbc_imgprodgroup   = get_post_meta( $post_id, 'pbc_imgprodgroup', true );
		$pbc_pricegroup     = get_post_meta( $post_id, 'pbc_pricegroup', true );
		$pbc_question_depends = get_post_meta( $post_id, 'pbc_question_depends', true );

		if ( ! is_array( $pbc_depends ) ) {
			$pbc_depends = array();
		}
		if ( ! is_array( $pbc_imgprodgroup ) ) {
			$pbc_imgprodgroup = array();
		}
		if ( ! is_array( $pbc_pricegroup ) ) {
			$pbc_pricegroup = array();
		}
		if ( ! is_array( $pbc_question_depends ) ) {
			$pbc_question_depends = array();
		}

		if ( '' === $pbc_question_input_type ) {
			$pbc_question_input_type = 'number';
		}

		wp_nonce_field( 'pbc_variation_save', 'pbc_variation_nonce' );
		?>
		<table class="form-table pbc-metabox-table">
			<tr>
				<th><label for="pbc_phase"><?php esc_html_e( 'Phase', 'product-budget-configurator' ); ?></label></th>
				<td>
					<select id="pbc_phase" name="pbc_phase" style="min-width:300px;">
						<option value=""><?php esc_html_e( 'Select a phase', 'product-budget-configurator' ); ?></option>
						<?php foreach ( $phase_options as $pid => $plabel ) : ?>
							<option value="<?php echo esc_attr( (string) $pid ); ?>"<?php selected( $pbc_phase, (string) $pid ); ?>><?php echo esc_html( $plabel ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="pbc_sku"><?php esc_html_e( 'Reference', 'product-budget-configurator' ); ?></label></th>
				<td><input type="text" id="pbc_sku" name="pbc_sku" value="<?php echo esc_attr( $pbc_sku ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="pbc_field_type"><?php esc_html_e( 'Field type', 'product-budget-configurator' ); ?></label></th>
				<td>
					<select id="pbc_field_type" name="pbc_field_type">
						<option value=""<?php selected( $pbc_field_type, '' ); ?>><?php esc_html_e( 'Default by price', 'product-budget-configurator' ); ?></option>
						<option value="qty"<?php selected( $pbc_field_type, 'qty' ); ?>><?php esc_html_e( 'Quantity', 'product-budget-configurator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="pbc-toggle-row">
				<th><?php esc_html_e( 'Convert to Question', 'product-budget-configurator' ); ?></th>
				<td>
					<div class="pbc-toggle-wrap">
						<label class="pbc-toggle">
							<input type="checkbox" id="pbc_is_question" name="pbc_is_question" value="1" <?php checked( $pbc_is_question ); ?> />
							<span class="pbc-toggle-slider"></span>
						</label>
						<span class="pbc-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_attr_e( 'When enabled, this variation will show as an input field for the user to answer. The answer can be used in dependencies.', 'product-budget-configurator' ); ?>"></span>
					</div>
				</td>
			</tr>
			<tr class="pbc-question-field" style="<?php echo $pbc_is_question ? '' : 'display:none;'; ?>">
				<th><label for="pbc_question_key"><?php esc_html_e( 'Question Key', 'product-budget-configurator' ); ?></label></th>
				<td>
					<input type="text" id="pbc_question_key" name="pbc_question_key" value="<?php echo esc_attr( $pbc_question_key ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Unique identifier for this question (e.g., house_m2, height). Use lowercase and underscores.', 'product-budget-configurator' ); ?></p>
				</td>
			</tr>
			<tr class="pbc-question-field" style="<?php echo $pbc_is_question ? '' : 'display:none;'; ?>">
				<th><label for="pbc_question_input_type"><?php esc_html_e( 'Input Type', 'product-budget-configurator' ); ?></label></th>
				<td>
					<select id="pbc_question_input_type" name="pbc_question_input_type">
						<option value="number"<?php selected( $pbc_question_input_type, 'number' ); ?>><?php esc_html_e( 'Number', 'product-budget-configurator' ); ?></option>
						<option value="text"<?php selected( $pbc_question_input_type, 'text' ); ?>><?php esc_html_e( 'Text', 'product-budget-configurator' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Type of input field to show', 'product-budget-configurator' ); ?></p>
				</td>
			</tr>
			<tr class="pbc-question-field" style="<?php echo $pbc_is_question ? '' : 'display:none;'; ?>">
				<th><label for="pbc_question_placeholder"><?php esc_html_e( 'Placeholder', 'product-budget-configurator' ); ?></label></th>
				<td>
					<input type="text" id="pbc_question_placeholder" name="pbc_question_placeholder" value="<?php echo esc_attr( $pbc_question_placeholder ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter the value (e.g. m²)', 'product-budget-configurator' ); ?>" />
					<p class="description"><?php esc_html_e( 'Placeholder text for the input', 'product-budget-configurator' ); ?></p>
				</td>
			</tr>
			<tr class="pbc-question-field" style="<?php echo $pbc_is_question ? '' : 'display:none;'; ?>">
				<th><?php esc_html_e( 'Required', 'product-budget-configurator' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="pbc_question_required" value="1" <?php checked( $pbc_question_required, '1' ); ?> />
						<?php esc_html_e( 'Make this question required', 'product-budget-configurator' ); ?>
					</label>
				</td>
			</tr>
			<tr class="pbc-toggle-row">
				<th><?php esc_html_e( 'Show custom input field', 'product-budget-configurator' ); ?></th>
				<td>
					<div class="pbc-toggle-wrap">
						<label class="pbc-toggle">
							<input type="checkbox" name="pbc_show_custom_input" value="1" <?php checked( $pbc_show_custom_input ); ?> />
							<span class="pbc-toggle-slider"></span>
						</label>
						<span class="pbc-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_attr_e( 'If checked, an input field will appear below this variation when selected', 'product-budget-configurator' ); ?>"></span>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Icon image', 'product-budget-configurator' ); ?></th>
				<td>
					<?php
					$icon_img_url = '';
					if ( $pbc_imgicon ) {
						$icon_src     = wp_get_attachment_image_src( (int) $pbc_imgicon, array( 80, 80 ) );
						$icon_img_url = $icon_src ? $icon_src[0] : '';
					}
					?>
					<div class="pbc-image-field">
						<input type="hidden" name="pbc_imgicon" id="pbc_imgicon" value="<?php echo esc_attr( $pbc_imgicon ); ?>" />
						<?php if ( $icon_img_url ) : ?>
							<img src="<?php echo esc_url( $icon_img_url ); ?>" style="max-width:80px;max-height:80px;display:block;margin-bottom:6px;" class="pbc-img-preview" />
						<?php else : ?>
							<img src="" style="max-width:80px;max-height:80px;display:none;margin-bottom:6px;" class="pbc-img-preview" />
						<?php endif; ?>
						<button type="button" class="button pbc-upload-image" data-target="pbc_imgicon"><?php esc_html_e( 'Select image', 'product-budget-configurator' ); ?></button>
						<button type="button" class="button pbc-remove-image" data-target="pbc_imgicon"<?php echo $pbc_imgicon ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button>
					</div>
				</td>
			</tr>
		</table>

		<?php /* ---- PRICE GROUP ---- */ ?>
		<h3 style="padding:0 0 6px;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Price', 'product-budget-configurator' ); ?></h3>
		<table class="widefat striped pbc-repeatable" id="pbc-pricegroup-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Option price', 'product-budget-configurator' ); ?></th>
					<th><?php esc_html_e( 'Price (VAT not included)', 'product-budget-configurator' ); ?></th>
					<th style="width:60px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$price_rows = ! empty( $pbc_pricegroup ) ? $pbc_pricegroup : array( array( 'pbc_meaprice' => '', 'pbc_pricem' => '' ) );
				foreach ( $price_rows as $row ) :
					$meaprice = isset( $row['pbc_meaprice'] ) ? $row['pbc_meaprice'] : '';
					$pricem   = isset( $row['pbc_pricem'] ) ? $row['pbc_pricem'] : '';
				?>
				<tr>
					<td><input type="text" name="pbc_pricegroup[][pbc_meaprice]" value="<?php echo esc_attr( $meaprice ); ?>" class="widefat" /></td>
					<td><input type="text" name="pbc_pricegroup[][pbc_pricem]" value="<?php echo esc_attr( $pricem ); ?>" class="widefat" placeholder="0" /></td>
					<td><button type="button" class="button-link-delete pbc-remove-row"><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<script type="text/template" id="pbc-pricegroup-tpl">
			<tr>
				<td><input type="text" name="pbc_pricegroup[][pbc_meaprice]" value="" class="widefat" /></td>
				<td><input type="text" name="pbc_pricegroup[][pbc_pricem]" value="" class="widefat" placeholder="0" /></td>
				<td><button type="button" class="button-link-delete pbc-remove-row"><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button></td>
			</tr>
		</script>
		<p><button type="button" class="button pbc-add-row" data-table="pbc-pricegroup-table" data-tpl="pbc-pricegroup-tpl"><?php esc_html_e( 'Add price row', 'product-budget-configurator' ); ?></button></p>

		<?php /* ---- DEPENDS GROUP ---- */ ?>
		<h3 style="padding:8px 0 6px;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Depends of', 'product-budget-configurator' ); ?></h3>
		<div id="pbc-depends-table" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:6px 12px;margin-bottom:8px;overflow:hidden;">
			<?php
			$dep_rows = ! empty( $pbc_depends ) ? $pbc_depends : array();
			if ( empty( $dep_rows ) ) {
				$dep_rows = array( array( 'pbc_depvar' => '' ) );
			}
			foreach ( $dep_rows as $row ) :
				$depvar = isset( $row['pbc_depvar'] ) ? $row['pbc_depvar'] : '';
			?>
			<div class="pbc-depends-item" style="display:flex;gap:4px;align-items:center;">
				<select name="pbc_depends[][pbc_depvar]" style="flex:1;min-width:0;">
					<option value=""><?php esc_html_e( 'Not depends of variation', 'product-budget-configurator' ); ?></option>
					<?php foreach ( $var_options as $vval => $vlabel ) : ?>
						<option value="<?php echo esc_attr( $vval ); ?>"<?php selected( $depvar, $vval ); ?>><?php echo esc_html( $vlabel ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button-link-delete pbc-remove-dep" style="white-space:nowrap;flex-shrink:0;"><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button>
			</div>
			<?php endforeach; ?>
		</div>
		<script type="text/template" id="pbc-depends-tpl">
			<div class="pbc-depends-item" style="display:flex;gap:4px;align-items:center;">
				<select name="pbc_depends[][pbc_depvar]" style="flex:1;min-width:0;">
					<option value=""><?php esc_html_e( 'Not depends of variation', 'product-budget-configurator' ); ?></option>
					<?php foreach ( $var_options as $vval => $vlabel ) : ?>
						<option value="<?php echo esc_attr( $vval ); ?>"><?php echo esc_html( $vlabel ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button-link-delete pbc-remove-dep" style="white-space:nowrap;flex-shrink:0;"><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button>
			</div>
		</script>
		<p><button type="button" class="button" id="pbc-add-dep-row"><?php esc_html_e( 'Add dependency', 'product-budget-configurator' ); ?></button></p>

		<?php /* ---- IMG PROD GROUP ---- */ ?>
		<h3 style="padding:8px 0 6px;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Product group image', 'product-budget-configurator' ); ?></h3>
		<table class="widefat striped pbc-repeatable" id="pbc-imgprodgroup-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Depends of Variation', 'product-budget-configurator' ); ?></th>
					<th><?php esc_html_e( 'Product image', 'product-budget-configurator' ); ?></th>
					<th style="width:60px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$imgprod_rows = ! empty( $pbc_imgprodgroup ) ? $pbc_imgprodgroup : array( array( 'pbc_depvarimgprod' => array(), 'pbc_imgprod' => array() ) );
				foreach ( $imgprod_rows as $ri => $row ) :
					$depvarimgprod = isset( $row['pbc_depvarimgprod'] ) ? (array) $row['pbc_depvarimgprod'] : array();
					$imgprod_ids   = isset( $row['pbc_imgprod'] ) ? (array) $row['pbc_imgprod'] : array();
					$imgprod_id    = ! empty( $imgprod_ids ) ? (int) $imgprod_ids[0] : 0;
					$imgprod_url   = '';
					if ( $imgprod_id ) {
						$isrc        = wp_get_attachment_image_src( $imgprod_id, array( 80, 64 ) );
						$imgprod_url = $isrc ? $isrc[0] : '';
					}
					$field_name_dep = 'pbc_imgprodgroup[' . $ri . '][pbc_depvarimgprod][]';
					$field_name_img = 'pbc_imgprodgroup[' . $ri . '][pbc_imgprod]';
				?>
				<tr data-index="<?php echo (int) $ri; ?>">
					<td style="width:70%;max-width:0;">
						<select name="<?php echo esc_attr( $field_name_dep ); ?>" multiple style="width:100%;height:80px;box-sizing:border-box;">
							<?php foreach ( $var_options as $vval => $vlabel ) : ?>
								<option value="<?php echo esc_attr( $vval ); ?>"<?php echo in_array( $vval, $depvarimgprod, true ) ? ' selected' : ''; ?>><?php echo esc_html( $vlabel ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple', 'product-budget-configurator' ); ?></p>
					</td>
					<td>
						<div class="pbc-image-field">
							<input type="hidden" name="<?php echo esc_attr( $field_name_img ); ?>" class="pbc-imgprod-id" value="<?php echo esc_attr( $imgprod_id ); ?>" />
							<?php if ( $imgprod_url ) : ?>
								<img src="<?php echo esc_url( $imgprod_url ); ?>" style="max-width:80px;max-height:64px;display:block;margin-bottom:4px;" class="pbc-img-preview" />
							<?php else : ?>
								<img src="" style="max-width:80px;max-height:64px;display:none;margin-bottom:4px;" class="pbc-img-preview" />
							<?php endif; ?>
							<button type="button" class="button pbc-upload-imgprod"><?php esc_html_e( 'Select image', 'product-budget-configurator' ); ?></button>
							<button type="button" class="button pbc-remove-imgprod"<?php echo $imgprod_id ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button>
						</div>
					</td>
					<td><button type="button" class="button-link-delete pbc-remove-row"><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" id="pbc-add-imgprodgroup-row"><?php esc_html_e( 'Add product image row', 'product-budget-configurator' ); ?></button></p>

		<?php /* ---- WYSIWYG FIELDS ---- */ ?>
		<table class="form-table pbc-metabox-table" style="margin-top:16px;">
			<tr>
				<th><?php esc_html_e( 'Description after option', 'product-budget-configurator' ); ?></th>
				<td>
					<?php
					wp_editor(
						wp_kses_post( $pbc_descopt ),
						'pbc_descopt',
						array(
							'textarea_name' => 'pbc_descopt',
							'textarea_rows' => 5,
							'teeny'         => true,
							'media_buttons' => false,
						)
					);
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Description', 'product-budget-configurator' ); ?></th>
				<td>
					<?php
					wp_editor(
						wp_kses_post( $pbc_descvar ),
						'pbc_descvar',
						array(
							'textarea_name' => 'pbc_descvar',
							'textarea_rows' => 8,
							'teeny'         => true,
							'media_buttons' => false,
						)
					);
					?>
				</td>
			</tr>
		</table>

		<?php /* ---- QUESTION DEPENDS ---- */ ?>
		<h3 style="padding:8px 0 6px;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Depends on Question Answers', 'product-budget-configurator' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Show this variation only when question answers meet these conditions', 'product-budget-configurator' ); ?></p>
		<table class="widefat striped pbc-repeatable" id="pbc-qdepends-table" style="margin-top:8px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Question Key', 'product-budget-configurator' ); ?></th>
					<th><?php esc_html_e( 'Operator', 'product-budget-configurator' ); ?></th>
					<th><?php esc_html_e( 'Value', 'product-budget-configurator' ); ?></th>
					<th style="width:60px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$qdep_rows = ! empty( $pbc_question_depends ) ? $pbc_question_depends : array();
				if ( empty( $qdep_rows ) ) {
					$qdep_rows = array( array( 'pbc_question_key_ref' => '', 'pbc_question_operator' => '>', 'pbc_question_value' => '' ) );
				}
				$operator_options = array(
					'>'  => __( 'Greater than (>)', 'product-budget-configurator' ),
					'>=' => __( 'Greater than or equal (>=)', 'product-budget-configurator' ),
					'<'  => __( 'Less than (<)', 'product-budget-configurator' ),
					'<=' => __( 'Less than or equal (<=)', 'product-budget-configurator' ),
					'='  => __( 'Equal (=)', 'product-budget-configurator' ),
					'!=' => __( 'Not equal (!=)', 'product-budget-configurator' ),
				);
				foreach ( $qdep_rows as $row ) :
					$qkey = isset( $row['pbc_question_key_ref'] ) ? $row['pbc_question_key_ref'] : '';
					$qop  = isset( $row['pbc_question_operator'] ) ? $row['pbc_question_operator'] : '>';
					$qval = isset( $row['pbc_question_value'] ) ? $row['pbc_question_value'] : '';
				?>
				<tr>
					<td>
						<input type="text" name="pbc_question_depends[][pbc_question_key_ref]" value="<?php echo esc_attr( $qkey ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. house_m2', 'product-budget-configurator' ); ?>" />
					</td>
					<td>
						<select name="pbc_question_depends[][pbc_question_operator]">
							<?php foreach ( $operator_options as $oval => $olabel ) : ?>
								<option value="<?php echo esc_attr( $oval ); ?>"<?php selected( $qop, $oval ); ?>><?php echo esc_html( $olabel ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td>
						<input type="text" name="pbc_question_depends[][pbc_question_value]" value="<?php echo esc_attr( $qval ); ?>" class="widefat" />
					</td>
					<td><button type="button" class="button-link-delete pbc-remove-row"><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<script type="text/template" id="pbc-qdepends-tpl">
			<tr>
				<td><input type="text" name="pbc_question_depends[][pbc_question_key_ref]" value="" class="widefat" placeholder="<?php esc_attr_e( 'e.g. house_m2', 'product-budget-configurator' ); ?>" /></td>
				<td>
					<select name="pbc_question_depends[][pbc_question_operator]">
						<?php foreach ( $operator_options as $oval => $olabel ) : ?>
							<option value="<?php echo esc_attr( $oval ); ?>"><?php echo esc_html( $olabel ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td><input type="text" name="pbc_question_depends[][pbc_question_value]" value="" class="widefat" /></td>
				<td><button type="button" class="button-link-delete pbc-remove-row"><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button></td>
			</tr>
		</script>
		<p><button type="button" class="button pbc-add-row" data-table="pbc-qdepends-table" data-tpl="pbc-qdepends-tpl"><?php esc_html_e( 'Add condition', 'product-budget-configurator' ); ?></button></p>
		<?php
	}

	/**
	 * Render the phase metabox.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_phase_metabox( $post ) {
		$post_id                 = $post->ID;
		$pbc_phase_note          = get_post_meta( $post_id, 'pbc_phase_note', true );
		$pbc_allow_multiple      = (bool) get_post_meta( $post_id, 'pbc_allow_multiple_selections', true );
		$pbc_show_direct_input   = (bool) get_post_meta( $post_id, 'pbc_show_direct_input', true );
		$pbc_direct_input_type   = get_post_meta( $post_id, 'pbc_direct_input_type', true );
		if ( '' === $pbc_direct_input_type ) {
			$pbc_direct_input_type = 'textarea';
		}

		wp_nonce_field( 'pbc_phase_save', 'pbc_phase_nonce' );
		?>
		<table class="form-table pbc-metabox-table">
			<tr>
				<th><?php esc_html_e( 'Note', 'product-budget-configurator' ); ?></th>
				<td>
					<?php
					wp_editor(
						wp_kses_post( $pbc_phase_note ),
						'pbc_phase_note',
						array(
							'textarea_name' => 'pbc_phase_note',
							'textarea_rows' => 5,
							'teeny'         => true,
							'media_buttons' => false,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Add a note that will be displayed between phase elements', 'product-budget-configurator' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Allow multiple selections', 'product-budget-configurator' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="pbc_allow_multiple_selections" value="1" <?php checked( $pbc_allow_multiple ); ?> />
						<?php esc_html_e( 'If checked, users can select multiple variations instead of just one', 'product-budget-configurator' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Show direct input field', 'product-budget-configurator' ); ?></th>
				<td>
					<label>
						<input type="checkbox" id="pbc_show_direct_input" name="pbc_show_direct_input" value="1" <?php checked( $pbc_show_direct_input ); ?> />
						<?php esc_html_e( 'If checked, an input field will appear directly without needing to select a variation. Useful for open-ended questions like "What is your hobby?"', 'product-budget-configurator' ); ?>
					</label>
				</td>
			</tr>
			<tr id="pbc-direct-input-type-row" style="<?php echo $pbc_show_direct_input ? '' : 'display:none;'; ?>">
				<th><label for="pbc_direct_input_type"><?php esc_html_e( 'Input field type', 'product-budget-configurator' ); ?></label></th>
				<td>
					<select id="pbc_direct_input_type" name="pbc_direct_input_type">
						<option value="textarea"<?php selected( $pbc_direct_input_type, 'textarea' ); ?>><?php esc_html_e( 'Textarea (Large text box)', 'product-budget-configurator' ); ?></option>
						<option value="text"<?php selected( $pbc_direct_input_type, 'text' ); ?>><?php esc_html_e( 'Text (Single line)', 'product-budget-configurator' ); ?></option>
						<option value="number"<?php selected( $pbc_direct_input_type, 'number' ); ?>><?php esc_html_e( 'Number (With arrows)', 'product-budget-configurator' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<script>
		jQuery(document).ready(function($){
			$('#pbc_show_direct_input').on('change', function(){
				$('#pbc-direct-input-type-row').toggle(this.checked);
			});
		});
		</script>
		<?php
	}

	/**
	 * Save variation metabox fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_variation_meta( $post_id, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! isset( $_POST['pbc_variation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pbc_variation_nonce'] ) ), 'pbc_variation_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$simple_fields = array(
			'pbc_phase'               => 'intval',
			'pbc_sku'                 => 'sanitize_text_field',
			'pbc_field_type'          => 'sanitize_key',
			'pbc_question_key'        => 'sanitize_key',
			'pbc_question_input_type' => 'sanitize_key',
			'pbc_question_placeholder'=> 'sanitize_text_field',
			'pbc_descopt'             => 'wp_kses_post',
			'pbc_descvar'             => 'wp_kses_post',
		);
		foreach ( $simple_fields as $key => $sanitizer ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$val = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			update_post_meta( $post_id, $key, call_user_func( $sanitizer, $val ) );
		}

		$checkbox_fields = array( 'pbc_is_question', 'pbc_question_required', 'pbc_show_custom_input' );
		foreach ( $checkbox_fields as $key ) {
			$val = isset( $_POST[ $key ] ) ? 1 : 0;
			update_post_meta( $post_id, $key, $val );
		}

		// imgicon.
		$imgicon = isset( $_POST['pbc_imgicon'] ) ? (int) $_POST['pbc_imgicon'] : 0;
		update_post_meta( $post_id, 'pbc_imgicon', $imgicon );

		// pricegroup.
		$pricegroup = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_price = isset( $_POST['pbc_pricegroup'] ) ? wp_unslash( $_POST['pbc_pricegroup'] ) : array();
		if ( is_array( $raw_price ) ) {
			foreach ( $raw_price as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$pricegroup[] = array(
					'pbc_meaprice' => sanitize_text_field( isset( $row['pbc_meaprice'] ) ? $row['pbc_meaprice'] : '' ),
					'pbc_pricem'   => sanitize_text_field( isset( $row['pbc_pricem'] ) ? $row['pbc_pricem'] : '' ),
				);
			}
		}
		update_post_meta( $post_id, 'pbc_pricegroup', $pricegroup );

		// depends.
		$depends = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_dep = isset( $_POST['pbc_depends'] ) ? wp_unslash( $_POST['pbc_depends'] ) : array();
		if ( is_array( $raw_dep ) ) {
			foreach ( $raw_dep as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$depvar = sanitize_text_field( isset( $row['pbc_depvar'] ) ? $row['pbc_depvar'] : '' );
				$depends[] = array( 'pbc_depvar' => $depvar );
			}
		}
		update_post_meta( $post_id, 'pbc_depends', $depends );

		// imgprodgroup.
		$imgprodgroup = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_imgprod = isset( $_POST['pbc_imgprodgroup'] ) ? wp_unslash( $_POST['pbc_imgprodgroup'] ) : array();
		if ( is_array( $raw_imgprod ) ) {
			foreach ( $raw_imgprod as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$depvar_arr = array();
				if ( isset( $row['pbc_depvarimgprod'] ) && is_array( $row['pbc_depvarimgprod'] ) ) {
					foreach ( $row['pbc_depvarimgprod'] as $dv ) {
						$depvar_arr[] = sanitize_text_field( $dv );
					}
				}
				$imgprod_id     = isset( $row['pbc_imgprod'] ) ? (int) $row['pbc_imgprod'] : 0;
				$imgprodgroup[] = array(
					'pbc_depvarimgprod' => $depvar_arr,
					'pbc_imgprod'       => $imgprod_id ? array( $imgprod_id ) : array(),
				);
			}
		}
		update_post_meta( $post_id, 'pbc_imgprodgroup', $imgprodgroup );

		// question_depends.
		$question_depends = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_qdep = isset( $_POST['pbc_question_depends'] ) ? wp_unslash( $_POST['pbc_question_depends'] ) : array();
		if ( is_array( $raw_qdep ) ) {
			$allowed_ops = array( '>', '>=', '<', '<=', '=', '!=' );
			foreach ( $raw_qdep as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$qkey = sanitize_key( isset( $row['pbc_question_key_ref'] ) ? $row['pbc_question_key_ref'] : '' );
				$qop  = sanitize_text_field( isset( $row['pbc_question_operator'] ) ? $row['pbc_question_operator'] : '>' );
				if ( ! in_array( $qop, $allowed_ops, true ) ) {
					$qop = '>';
				}
				$qval             = sanitize_text_field( isset( $row['pbc_question_value'] ) ? $row['pbc_question_value'] : '' );
				$question_depends[] = array(
					'pbc_question_key_ref'  => $qkey,
					'pbc_question_operator' => $qop,
					'pbc_question_value'    => $qval,
				);
			}
		}
		update_post_meta( $post_id, 'pbc_question_depends', $question_depends );
	}

	/**
	 * Save phase metabox fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_phase_meta( $post_id, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! isset( $_POST['pbc_phase_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pbc_phase_nonce'] ) ), 'pbc_phase_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$phase_note = isset( $_POST['pbc_phase_note'] ) ? wp_kses_post( wp_unslash( $_POST['pbc_phase_note'] ) ) : '';
		update_post_meta( $post_id, 'pbc_phase_note', $phase_note );

		$allow_multiple = isset( $_POST['pbc_allow_multiple_selections'] ) ? 1 : 0;
		update_post_meta( $post_id, 'pbc_allow_multiple_selections', $allow_multiple );

		$show_direct = isset( $_POST['pbc_show_direct_input'] ) ? 1 : 0;
		update_post_meta( $post_id, 'pbc_show_direct_input', $show_direct );

		$allowed_input_types = array( 'textarea', 'text', 'number' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$direct_input_type = sanitize_key( isset( $_POST['pbc_direct_input_type'] ) ? wp_unslash( $_POST['pbc_direct_input_type'] ) : 'textarea' );
		if ( ! in_array( $direct_input_type, $allowed_input_types, true ) ) {
			$direct_input_type = 'textarea';
		}
		update_post_meta( $post_id, 'pbc_direct_input_type', $direct_input_type );
	}

	/**
	 * Metabox enquiry
	 *
	 * @return void
	 */
	public function pbc_metabox_enquiry() {
		add_meta_box(
			'enquiry-details',
			__( 'Enquiry Details', 'product-budget-configurator' ),
			array( $this, 'render_enquiry_details' ),
			'enquiry',
			'normal',
			'default'
		);

		add_meta_box(
			'configuration-details',
			__( 'Budget Configuration', 'product-budget-configurator' ),
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
		<div><label><strong><?php esc_html_e( 'Enquiry ID:', 'product-budget-configurator' ); ?></strong> <?php echo esc_html( $post_id ); ?></label></div>
		<?php if ( ! empty( $enquiry_name ) ) { ?>
			<div><label><strong><?php esc_html_e( 'Name:', 'product-budget-configurator' ); ?></strong> <?php echo esc_html( $enquiry_name ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_phone ) ) { ?>
			<div><label><strong><?php esc_html_e( 'Phone:', 'product-budget-configurator' ); ?></strong> <?php echo esc_html( $enquiry_phone ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_email ) ) { ?>
			<div><label><strong><?php esc_html_e( 'Email:', 'product-budget-configurator' ); ?></strong> <?php echo esc_html( $enquiry_email ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_city ) ) { ?>
			<div><label><strong><?php esc_html_e( 'City:', 'product-budget-configurator' ); ?></strong> <?php echo esc_html( $enquiry_city ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_state ) ) { ?>
			<div><label><strong><?php esc_html_e( 'State:', 'product-budget-configurator' ); ?></strong> <?php echo esc_html( $enquiry_state ); ?></label></div>
		<?php } ?>
		<?php if ( ! empty( $enquiry_state ) ) { ?>
			<div><label><strong><?php esc_html_e( 'Comments:', 'product-budget-configurator' ); ?></strong> <?php echo esc_html( $comments ); ?></label></div>
		<?php } ?>
		<?php
		if ( $parent_phase ) {
			$parent_phase_post = get_post( $parent_phase );
			echo '<div><label><strong>' . esc_html__( 'Product:', 'product-budget-configurator' ) . '</strong> ' . esc_html( $parent_phase_post->post_title ) . '</label></div>';
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
		$rows = array();
		for ( $i = 0; $i < 50; $i++ ) {
			$phase_var = get_post_meta( $post_id, 'pbc_phase_var_' . $i, true );
			if ( $phase_var ) {
				$rows[] = array(
					'desc'       => $phase_var,
					'price'      => get_post_meta( $post_id, 'pbc_price_' . $i, true ),
					'type'       => get_post_meta( $post_id, 'pbc_type_' . $i, true ),
					'phase_name' => get_post_meta( $post_id, 'pbc_phase_name_' . $i, true ),
				);
			}
		}
		if ( empty( $rows ) ) {
			$rows[] = array(
				'desc'       => '',
				'price'      => '',
				'type'       => '',
				'phase_name' => '',
			);
		}
		$phase_options = CALC::get_phases_options();
		wp_nonce_field( 'pbc_budget_config_save', 'pbc_budget_config_nonce' );
		?>
		<p class="description" style="margin-bottom:12px;">
			<?php esc_html_e( 'Lines mirror the configurator: standard options (price), quantity fields, free-text questions, and phases with multiple selections (one combined line). You can insert from the catalog or type manually.', 'product-budget-configurator' ); ?>
		</p>
		<?php if ( ! empty( $phase_options ) ) : ?>
		<div class="pbc-catalog-picker" style="margin:12px 0;padding:12px;background:#f6f7f7;border:1px solid #c3c4c7;border-radius:4px;">
			<strong><?php esc_html_e( 'Insert from catalog', 'product-budget-configurator' ); ?></strong>
			<p style="margin:8px 0 6px;">
				<label for="pbc-catalog-phase" style="display:inline-block;min-width:90px;"><?php esc_html_e( 'Phase', 'product-budget-configurator' ); ?></label>
				<select id="pbc-catalog-phase" style="min-width:280px;">
					<option value=""><?php esc_html_e( 'Select phase…', 'product-budget-configurator' ); ?></option>
					<?php foreach ( $phase_options as $pid => $plabel ) : ?>
						<option value="<?php echo esc_attr( (string) $pid ); ?>"><?php echo esc_html( $plabel ); ?></option>
					<?php endforeach; ?>
				</select>
				<span id="pbc-catalog-phase-hint" style="margin-left:8px;color:#50575e;"></span>
			</p>
			<div id="pbc-catalog-multi-mode" style="display:none;margin-top:10px;padding-top:10px;border-top:1px solid #c3c4c7;">
				<strong><?php esc_html_e( 'Multiple selection (combined in one line)', 'product-budget-configurator' ); ?></strong>
				<p class="description"><?php esc_html_e( 'Select several price options and insert one line with all names and the sum of prices (same as when the customer ticks several boxes).', 'product-budget-configurator' ); ?></p>
				<div id="pbc-catalog-multi-list" style="max-height:220px;overflow:auto;margin:8px 0;padding:8px;background:#fff;border:1px solid #c3c4c7;"></div>
				<button type="button" class="button button-primary" id="pbc-insert-multi-row"><?php esc_html_e( 'Insert combined selection', 'product-budget-configurator' ); ?></button>
			</div>
			<div id="pbc-catalog-single-mode" style="margin-top:12px;">
				<strong><?php esc_html_e( 'Single option or question', 'product-budget-configurator' ); ?></strong>
				<p style="margin:6px 0;">
					<label for="pbc-catalog-variation" style="display:inline-block;min-width:90px;"><?php esc_html_e( 'Variation', 'product-budget-configurator' ); ?></label>
					<select id="pbc-catalog-variation" style="min-width:280px;">
						<option value=""><?php esc_html_e( 'Select variation…', 'product-budget-configurator' ); ?></option>
					</select>
					<span id="pbc-catalog-price-hint" style="margin-left:8px;color:#50575e;"></span>
				</p>
				<p id="pbc-catalog-answer-wrap" style="display:none;margin:6px 0;">
					<label for="pbc-catalog-answer" style="display:inline-block;min-width:90px;"><?php esc_html_e( 'Answer', 'product-budget-configurator' ); ?></label>
					<input type="text" id="pbc-catalog-answer" class="regular-text" placeholder="<?php esc_attr_e( 'Customer answer (same format as on the website)', 'product-budget-configurator' ); ?>" />
				</p>
				<p id="pbc-catalog-qty-wrap" style="display:none;margin:6px 0;">
					<label for="pbc-catalog-qty" style="display:inline-block;min-width:90px;"><?php esc_html_e( 'Quantity', 'product-budget-configurator' ); ?></label>
					<input type="number" id="pbc-catalog-qty" min="1" value="1" style="width:80px;" />
					<span class="description"><?php esc_html_e( 'Total price = quantity × unit price.', 'product-budget-configurator' ); ?></span>
				</p>
				<button type="button" class="button" id="pbc-insert-catalog-row"><?php esc_html_e( 'Insert row', 'product-budget-configurator' ); ?></button>
			</div>
		</div>
		<?php else : ?>
		<p class="notice notice-warning inline" style="padding:8px 12px;">
			<?php esc_html_e( 'No phases found. Create phases and variations under Product Budget Configurator so you can build budgets from the catalog.', 'product-budget-configurator' ); ?>
		</p>
		<?php endif; ?>
		<table class="widefat striped" style="margin-top:12px;">
			<thead>
				<tr>
					<th style="width:42%;"><?php esc_html_e( 'Phase / option (description)', 'product-budget-configurator' ); ?></th>
					<th style="width:18%;"><?php esc_html_e( 'Price', 'product-budget-configurator' ); ?></th>
					<th style="width:22%;"><?php esc_html_e( 'Line type', 'product-budget-configurator' ); ?></th>
					<th style="width:18%;"><?php esc_html_e( 'Actions', 'product-budget-configurator' ); ?></th>
				</tr>
			</thead>
			<tbody id="pbc-budget-rows">
				<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td>
						<input type="text" class="widefat pbc-row-desc" name="pbc_line_desc[]" value="<?php echo esc_attr( $row['desc'] ); ?>" />
						<input type="hidden" class="pbc-row-phase-name" name="pbc_line_phase_name[]" value="<?php echo esc_attr( $row['phase_name'] ); ?>" />
					</td>
					<td>
						<input type="text" class="widefat pbc-row-price" name="pbc_line_price[]" value="<?php echo esc_attr( $row['price'] ); ?>" placeholder="0,00 / -" />
					</td>
					<td>
						<select class="widefat pbc-row-type" name="pbc_line_type[]">
							<?php
							$lt = isset( $row['type'] ) ? (string) $row['type'] : '';
							$line_types = array(
								''          => __( 'Default (price)', 'product-budget-configurator' ),
								'price'     => __( 'Fixed price option', 'product-budget-configurator' ),
								'qty'       => __( 'Quantity × unit', 'product-budget-configurator' ),
								'question'  => __( 'Question / answer', 'product-budget-configurator' ),
								'multiple'  => __( 'Multiple options (combined)', 'product-budget-configurator' ),
							);
							foreach ( $line_types as $k => $lab ) {
								printf(
									'<option value="%s"%s>%s</option>',
									esc_attr( $k ),
									selected( $lt, $k, false ),
									esc_html( $lab )
								);
							}
							?>
						</select>
					</td>
					<td>
						<button type="button" class="button-link-delete pbc-remove-budget-row"><?php esc_html_e( 'Remove', 'product-budget-configurator' ); ?></button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p style="margin-top:10px;">
			<button type="button" class="button pbc-add-budget-row"><?php esc_html_e( 'Add empty row', 'product-budget-configurator' ); ?></button>
		</p>
		<script type="text/template" id="pbc-budget-row-template">
			<tr>
				<td>
					<input type="text" class="widefat pbc-row-desc" name="pbc_line_desc[]" value="" />
					<input type="hidden" class="pbc-row-phase-name" name="pbc_line_phase_name[]" value="" />
				</td>
				<td><input type="text" class="widefat pbc-row-price" name="pbc_line_price[]" value="" placeholder="0,00 / -" /></td>
				<td>
					<select class="widefat pbc-row-type" name="pbc_line_type[]">
						<option value=""><?php echo esc_html__( 'Default (price)', 'product-budget-configurator' ); ?></option>
						<option value="price"><?php echo esc_html__( 'Fixed price option', 'product-budget-configurator' ); ?></option>
						<option value="qty"><?php echo esc_html__( 'Quantity × unit', 'product-budget-configurator' ); ?></option>
						<option value="question"><?php echo esc_html__( 'Question / answer', 'product-budget-configurator' ); ?></option>
						<option value="multiple"><?php echo esc_html__( 'Multiple options (combined)', 'product-budget-configurator' ); ?></option>
					</select>
				</td>
				<td><button type="button" class="button-link-delete pbc-remove-budget-row"><?php echo esc_html__( 'Remove', 'product-budget-configurator' ); ?></button></td>
			</tr>
		</script>
		<?php
	}

	/**
	 * Admin scripts for enquiry budget editor.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_enquiry_budget_admin_assets( $hook_suffix ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'enquiry' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_script(
			'pbc-admin-enquiry-budget',
			WPPBC_PLUGIN_URL . 'includes/assets/pbc-admin-enquiry-budget.js',
			array( 'jquery' ),
			WPPBC_VERSION,
			true
		);
		wp_localize_script(
			'pbc-admin-enquiry-budget',
			'pbcEnquiryBudget',
			array(
				'phaseVariations' => $this->get_phase_variations_admin_data(),
				'phaseFlags'      => $this->get_phases_admin_flags(),
				'i18n'            => array(
					'selectVariation'   => __( 'Select variation…', 'product-budget-configurator' ),
					'suggestedPrice'    => __( 'Suggested price:', 'product-budget-configurator' ),
					'phaseMulti'        => __( 'Multiple selection phase', 'product-budget-configurator' ),
					'needAnswer'        => __( 'Enter the answer for this question.', 'product-budget-configurator' ),
					'pickMulti'         => __( 'Select at least one option.', 'product-budget-configurator' ),
					'noOptionsMulti'    => __( 'No price options in this phase (only questions?). Add questions using single mode on another step or a manual row.', 'product-budget-configurator' ),
					'perUnit'           => __( 'per unit', 'product-budget-configurator' ),
				),
			)
		);
	}

	/**
	 * Variations grouped by phase for admin budget UI.
	 *
	 * @return array<int, array<int, array<string, int|string>>>
	 */
	private function get_phase_variations_admin_data() {
		$by_phase      = array();
		$variationscpt = get_posts(
			array(
				'post_type'      => 'variation',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => array( 'publish', 'draft', 'private' ),
			)
		);
		foreach ( $variationscpt as $var_item ) {
			$phase_id = (int) get_post_meta( $var_item->ID, 'pbc_phase', true );
			if ( ! $phase_id ) {
				continue;
			}
			$phase_post = get_post( $phase_id );
			if ( empty( $phase_post ) ) {
				continue;
			}
			$phase_title    = '';
			$post_parent_id = isset( $phase_post->post_parent ) ? (int) $phase_post->post_parent : 0;
			if ( $post_parent_id > 0 ) {
				$phase_parent = get_post( $post_parent_id );
				if ( $phase_parent ) {
					$phase_title .= $phase_parent->post_title . ' - ';
				}
			}
			$phase_order = CALC::adds_zero( $phase_post->menu_order );
			$line_label  = $phase_title . $phase_order . ' - ' . $phase_post->post_title . ' - ' . $var_item->post_title;
			$var_sku     = get_post_meta( $var_item->ID, 'pbc_sku', true );
			$line_label .= ! empty( $var_sku ) ? ' (' . $var_sku . ')' : '';

			$pricegroup   = get_post_meta( $var_item->ID, 'pbc_pricegroup', true );
			$unit         = 0.0;
			$first_mea    = '';
			if ( ! empty( $pricegroup ) && is_array( $pricegroup ) ) {
				foreach ( $pricegroup as $row ) {
					if ( isset( $row['pbc_pricem'] ) && '' !== $row['pbc_pricem'] && is_numeric( $row['pbc_pricem'] ) ) {
						$unit      = (float) $row['pbc_pricem'];
						$first_mea = isset( $row['pbc_meaprice'] ) ? (string) $row['pbc_meaprice'] : '';
						break;
					}
				}
			}
			$short_label = $var_item->post_title;
			if ( '' !== $first_mea ) {
				$short_label .= ' [' . $first_mea . ']';
			}
			$is_question = (bool) get_post_meta( $var_item->ID, 'pbc_is_question', true );
			$field_type  = get_post_meta( $var_item->ID, 'pbc_field_type', true );
			if ( $is_question ) {
				$line_label .= ' — ' . __( 'Question', 'product-budget-configurator' );
			} elseif ( 'qty' === $field_type ) {
				$line_label .= ' — ' . __( 'Quantity field', 'product-budget-configurator' );
			}
			$price_display = $unit > 0 ? number_format( $unit, 2, ',', '.' ) : '-';
			if ( ! isset( $by_phase[ $phase_id ] ) ) {
				$by_phase[ $phase_id ] = array();
			}
			$by_phase[ $phase_id ][] = array(
				'id'            => $var_item->ID,
				'lineLabel'     => $line_label,
				'shortLabel'    => $short_label,
				'questionTitle' => $var_item->post_title,
				'isQuestion'    => $is_question,
				'fieldType'     => ( 'qty' === $field_type ) ? 'qty' : '',
				'unitPrice'     => $unit,
				'price'         => $price_display,
			);
		}
		return $by_phase;
	}

	/**
	 * Phase flags for budget admin (multiple selection, title).
	 *
	 * @return array<string, array{allowMultiple: bool, phaseName: string}>
	 */
	private function get_phases_admin_flags() {
		$flags = array();
		foreach ( array_keys( CALC::get_phases_options() ) as $pid ) {
			$pid = (int) $pid;
			if ( $pid < 1 ) {
				continue;
			}
			$flags[ (string) $pid ] = array(
				'allowMultiple' => (bool) get_post_meta( $pid, 'pbc_allow_multiple_selections', true ),
				'phaseName'     => get_the_title( $pid ),
			);
		}
		return $flags;
	}

	/**
	 * Save budget lines from enquiry metabox.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_budget_configuration_lines( $post_id, $post ) {
		if ( ! isset( $_POST['pbc_budget_config_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pbc_budget_config_nonce'] ) ), 'pbc_budget_config_save' ) ) {
			return;
		}
		// Block editor / REST saves do not send metabox fields; never wipe stored lines.
		if ( ! isset( $_POST['pbc_line_desc'] ) || ! is_array( $_POST['pbc_line_desc'] ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! $post || 'enquiry' !== $post->post_type ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		for ( $c = 0; $c < 120; $c++ ) {
			delete_post_meta( $post_id, 'pbc_phase_var_' . $c );
			delete_post_meta( $post_id, 'pbc_price_' . $c );
			delete_post_meta( $post_id, 'pbc_phase_name_' . $c );
			delete_post_meta( $post_id, 'pbc_type_' . $c );
		}

		$descs        = isset( $_POST['pbc_line_desc'] ) ? wp_unslash( $_POST['pbc_line_desc'] ) : array();
		$prices       = isset( $_POST['pbc_line_price'] ) ? wp_unslash( $_POST['pbc_line_price'] ) : array();
		$types        = isset( $_POST['pbc_line_type'] ) ? wp_unslash( $_POST['pbc_line_type'] ) : array();
		$phase_names  = isset( $_POST['pbc_line_phase_name'] ) ? wp_unslash( $_POST['pbc_line_phase_name'] ) : array();
		if ( ! is_array( $descs ) ) {
			$descs = array();
		}
		if ( ! is_array( $prices ) ) {
			$prices = array();
		}
		if ( ! is_array( $types ) ) {
			$types = array();
		}
		if ( ! is_array( $phase_names ) ) {
			$phase_names = array();
		}

		$allowed_types = array( '', 'price', 'qty', 'question', 'multiple' );

		$index = 0;
		$max   = max( count( $descs ), count( $prices ), count( $types ), count( $phase_names ) );
		for ( $r = 0; $r < $max; $r++ ) {
			$desc = isset( $descs[ $r ] ) ? sanitize_text_field( $descs[ $r ] ) : '';
			if ( '' === trim( $desc ) ) {
				continue;
			}
			$price = isset( $prices[ $r ] ) ? sanitize_text_field( $prices[ $r ] ) : '-';
			if ( '' === trim( $price ) ) {
				$price = '-';
			}
			$line_type = isset( $types[ $r ] ) ? sanitize_text_field( $types[ $r ] ) : '';
			if ( ! in_array( $line_type, $allowed_types, true ) ) {
				$line_type = '';
			}
			$phase_n = isset( $phase_names[ $r ] ) ? sanitize_text_field( $phase_names[ $r ] ) : '';
			update_post_meta( $post_id, 'pbc_phase_var_' . $index, $desc );
			update_post_meta( $post_id, 'pbc_price_' . $index, $price );
			update_post_meta( $post_id, 'pbc_type_' . $index, $line_type );
			update_post_meta( $post_id, 'pbc_phase_name_' . $index, $phase_n );
			++$index;
		}
		update_post_meta( $post_id, 'pbc_total_var', $index );
	}

	/**
	 * Use classic editor for enquiries so budget metabox fields are submitted on save.
	 *
	 * @param bool   $use_block_editor Whether to use block editor.
	 * @param string $post_type        Post type slug.
	 * @return bool
	 */
	public function use_classic_editor_for_enquiry( $use_block_editor, $post_type ) {
		if ( 'enquiry' === $post_type ) {
			return false;
		}
		return $use_block_editor;
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
		$new_columns['title']      = __( 'Phase', 'product-budget-configurator' );
		$new_columns['menu_order'] = __( 'Order', 'product-budget-configurator' );
		$new_columns['variations'] = __( 'Variations', 'product-budget-configurator' );
		$new_columns['shortcode']  = __( 'Shortcode', 'product-budget-configurator' );

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
					echo '<a href="' . esc_url( $url ) . '" title="' . esc_attr__( 'View variations of this phase', 'product-budget-configurator' ) . '">';
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
		$new_columns['enquiry_name']    = __( 'Budget', 'product-budget-configurator' );
		$new_columns['enquiry_details'] = __( 'Details', 'product-budget-configurator' );
		$new_columns['enquiry_conf']    = __( 'Configuration', 'product-budget-configurator' );
		$new_columns['enquiry_date']    = __( 'Date', 'product-budget-configurator' );

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
				echo number_format( CALC::get_total_from_enquiry( $id ), 2, ',', '.' ) . ' € ' . esc_html__( 'VAT not included', 'product-budget-configurator' );
				break;
			case 'enquiry_date':
				echo get_the_date( 'd-m-Y H:i', $id );
				echo '<br/><button class="button generate-pbc-pdf" data-post-id="' . (int) $id . '">';
				esc_html_e( 'Generate PDF', 'product-budget-configurator' );
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
		$new_columns['title']   = __( 'Variation', 'product-budget-configurator' );
		$new_columns['phase']   = __( 'Phase and section', 'product-budget-configurator' );
		$new_columns['price']   = __( 'Price', 'product-budget-configurator' );
		$new_columns['depends'] = __( 'Depends of', 'product-budget-configurator' );
		$new_columns['imgicon'] = __( 'Icon', 'product-budget-configurator' );
		$new_columns['imgprod'] = __( 'Product Images', 'product-budget-configurator' );

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
					echo '<a href="' . esc_url( $phase_parent_link ) . '" title="' . esc_attr__( 'Go to phases list', 'product-budget-configurator' ) . '">';
					echo esc_html( $phase_parent->post_title );
					echo '</a><br/>';
				}
				// Phase link - goes to phases list and scrolls to this phase.
				$phase_list_link = admin_url( 'edit.php?post_type=phases#post-' . $phase_id );
				echo '<a href="' . esc_url( $phase_list_link ) . '" title="' . esc_attr__( 'Go to phases list', 'product-budget-configurator' ) . '">';
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
				$price_group = (array) get_post_meta( $id, 'pbc_pricegroup', true );
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
				$depends_group = (array) get_post_meta( $id, 'pbc_depends', true );
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
				$image_group = (array) get_post_meta( $id, 'pbc_imgprodgroup', true );
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
			<option value=""><?php esc_html_e( '📋 All Phases', 'product-budget-configurator' ); ?></option>
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

	/**
	 * Show duplicate key admin notice.
	 *
	 * @return void
	 */
	public function show_duplicate_key_notice() {
		// Check if we're on the variation edit screen.
		$screen = get_current_screen();
		if ( ! $screen || 'variation' !== $screen->post_type ) {
			return;
		}

		// Check for duplicate key parameter.
		if ( isset( $_GET['pbc_duplicate_key'] ) && '1' === $_GET['pbc_duplicate_key'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id      = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$question_key = get_transient( 'pbc_duplicate_key_' . $post_id );

			if ( $question_key ) {
				delete_transient( 'pbc_duplicate_key_' . $post_id );
				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<strong><?php esc_html_e( 'Error:', 'product-budget-configurator' ); ?></strong>
						<?php
						printf(
							/* translators: %s: question key */
							esc_html__( 'Don\'t use the same key! Another variation already uses the Question Key "%s". Please use a unique key for each question.', 'product-budget-configurator' ),
							'<code>' . esc_html( $question_key ) . '</code>'
						);
						?>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Validate question key uniqueness on save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 * @return void
	 */
	public function validate_question_key( $post_id, $post, $update ) {
		// Skip autosaves and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check if this is a question type variation.
		$is_question = isset( $_POST['pbc_is_question'] ) ? (int) $_POST['pbc_is_question'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $is_question ) {
			return;
		}

		// Get the question key.
		$question_key = isset( $_POST['pbc_question_key'] ) ? sanitize_key( wp_unslash( $_POST['pbc_question_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $question_key ) ) {
			return;
		}

		// Check if another variation already uses this key.
		$existing_variations = get_posts(
			array(
				'post_type'      => 'variation',
				'posts_per_page' => -1,
				'post__not_in'   => array( $post_id ),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => 'pbc_question_key',
						'value' => $question_key,
					),
				),
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing_variations ) ) {
			// Add admin notice.
			add_filter(
				'redirect_post_location',
				function ( $location ) {
					return add_query_arg( 'pbc_duplicate_key', '1', $location );
				}
			);

			// Also set a transient for the notice.
			set_transient( 'pbc_duplicate_key_' . $post_id, $question_key, 30 );
		}
	}
	/**
	 * Add duplicate row action to PBC post types.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post object.
	 * @return array
	 */
	public function add_duplicate_row_action( $actions, $post ) {
		$pbc_post_types = array( 'phases', 'variation', 'enquiry' );

		if ( ! in_array( $post->post_type, $pbc_post_types, true ) ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'pbc_duplicate_post',
					'post_id' => $post->ID,
				),
				admin_url( 'admin.php' )
			),
			'pbc_duplicate_post_' . $post->ID
		);

		$actions['pbc_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'product-budget-configurator' ) . '</a>';

		return $actions;
	}

	/**
	 * Handle post duplication.
	 *
	 * @return void
	 */
	public function handle_duplicate_post() {
		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'Invalid post ID.', 'product-budget-configurator' ) );
		}

		check_admin_referer( 'pbc_duplicate_post_' . $post_id );

		$post = get_post( $post_id );

		if ( ! $post ) {
			wp_die( esc_html__( 'Post not found.', 'product-budget-configurator' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate this post.', 'product-budget-configurator' ) );
		}

		$new_post_args = array(
			'post_title'     => $post->post_title . ' ' . __( '(Copy)', 'product-budget-configurator' ),
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_status'    => 'draft',
			'post_type'      => $post->post_type,
			'post_author'    => get_current_user_id(),
			'post_parent'    => $post->post_parent,
			'menu_order'     => $post->menu_order,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
		);

		$new_post_id = wp_insert_post( $new_post_args );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html( $new_post_id->get_error_message() ) );
		}

		// Copy all postmeta.
		$meta_entries = get_post_meta( $post_id );
		foreach ( $meta_entries as $meta_key => $meta_values ) {
			// Skip internal WP meta.
			if ( in_array( $meta_key, array( '_edit_lock', '_edit_last' ), true ) ) {
				continue;
			}
			foreach ( $meta_values as $meta_value ) {
				add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => $post->post_type,
					'duplicated' => '1',
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}

new PBC_Helper_PostTypes();
