<?php
/**
 * Class for Calculations
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

namespace CLOSE\QProductFlow;

defined( 'ABSPATH' ) || exit;

use CLOSE\QProductFlow\Helpers\CALC;

/**
 * Helper Post Types.
 *
 * All helpers calculations.
 *
 * @since 1.1
 */
class HelperPostTypes {

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'qpfw_register_cpt' ) );
		add_action( 'add_meta_boxes_qpfw_variation', array( $this, 'qpfw_metabox_variation' ) );
		add_action( 'add_meta_boxes_qpfw_phases', array( $this, 'qpfw_metabox_phase' ) );
		add_action( 'save_post_qpfw_variation', array( $this, 'save_variation_meta' ), 5, 2 );
		add_action( 'save_post_qpfw_phases', array( $this, 'save_phase_meta' ), 5, 2 );

		add_filter( 'manage_edit-qpfw_phases_columns', array( $this, 'add_new_phases_columns' ) );
		add_action( 'manage_qpfw_phases_posts_custom_column', array( $this, 'manage_phases_columns' ), 10, 2 );

		add_filter( 'manage_edit-qpfw_variation_columns', array( $this, 'add_new_var_columns' ) );
		add_action( 'manage_qpfw_variation_posts_custom_column', array( $this, 'manage_var_columns' ), 10, 2 );

		add_action( 'restrict_manage_posts', array( $this, 'admin_posts_filter' ) );
		add_filter( 'parse_query', array( $this, 'qpfw_posts_filter' ) );

		// Validate question key on save.
		add_action( 'save_post_qpfw_variation', array( $this, 'validate_question_key' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'show_duplicate_key_notice' ) );

		// Duplicate post.
		add_filter( 'post_row_actions', array( $this, 'add_duplicate_row_action' ), 10, 2 );
		add_action( 'admin_action_qpfw_duplicate_post', array( $this, 'handle_duplicate_post' ) );
	}

	/**
	 * Register post types
	 *
	 * @return void
	 */
	public function qpfw_register_cpt() {
		$labels = array(
			'name'               => __( 'Phases', 'quote-product-flow' ),
			'singular_name'      => __( 'Phase', 'quote-product-flow' ),
			'add_new'            => __( 'Add Phase', 'quote-product-flow' ),
			'add_new_item'       => __( 'Add New Phase', 'quote-product-flow' ),
			'edit_item'          => __( 'Edit Phase', 'quote-product-flow' ),
			'new_item'           => __( 'New Phase ', 'quote-product-flow' ),
			'view_item'          => __( 'View Phase ', 'quote-product-flow' ),
			'search_items'       => __( 'Search for Phases', 'quote-product-flow' ),
			'not_found'          => __( "We didn't find any phase", 'quote-product-flow' ),
			'not_found_in_trash' => __( "We didn't find an phase in the trash", 'quote-product-flow' ),
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
		register_post_type( 'qpfw_phases', $args );

		$labels = array(
			'name'               => __( 'Variations', 'quote-product-flow' ),
			'singular_name'      => __( 'Variation', 'quote-product-flow' ),
			'add_new'            => __( 'Add Variation', 'quote-product-flow' ),
			'add_new_item'       => __( 'Add New variation', 'quote-product-flow' ),
			'edit_item'          => __( 'Edit Variation', 'quote-product-flow' ),
			'new_item'           => __( 'New Variation', 'quote-product-flow' ),
			'view_item'          => __( 'View Variation', 'quote-product-flow' ),
			'search_items'       => __( 'Search for variations', 'quote-product-flow' ),
			'not_found'          => __( "We didn't find any Variation", 'quote-product-flow' ),
			'not_found_in_trash' => __( "We didn't find any Variation in the trash", 'quote-product-flow' ),
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
		register_post_type( 'qpfw_variation', $args );

		/**
		 * Register Taxonomy Sections
		 */
		$labels = array(
			'name'          => __( 'Sections', 'quote-product-flow' ),
			'singular_name' => __( 'Section', 'quote-product-flow' ),
			'search_items'  => __( 'Search Section', 'quote-product-flow' ),
			'all_items'     => __( 'All Sections', 'quote-product-flow' ),
			'edit_item'     => __( 'Edit Section', 'quote-product-flow' ),
			'update_item'   => __( 'Update Section', 'quote-product-flow' ),
			'add_new_item'  => __( 'Add New Section', 'quote-product-flow' ),
			'new_item_name' => __( 'Add New Section', 'quote-product-flow' ),
		);

		register_taxonomy(
			'qpfw_variation_tag',
			array(
				'qpfw_variation',
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
	}

	/**
	 * Register metaboxes for variation post type.
	 *
	 * @return void
	 */
	public function qpfw_metabox_variation() {
		add_meta_box(
			'qpfw-variation-options',
			__( 'Options for variation', 'quote-product-flow' ),
			array( $this, 'render_variation_metabox' ),
			'qpfw_variation',
			'normal',
			'high'
		);
	}

	/**
	 * Register metaboxes for phases post type.
	 *
	 * @return void
	 */
	public function qpfw_metabox_phase() {
		add_meta_box(
			'qpfw-phase-options',
			__( 'Phase Options', 'quote-product-flow' ),
			array( $this, 'render_phase_metabox' ),
			'qpfw_phases',
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
				'post_type'      => 'qpfw_variation',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		foreach ( $variationscpt as $var_item ) {
			if ( $current_post_id && $var_item->ID === $current_post_id ) {
				continue;
			}
			$phase_id   = get_post_meta( $var_item->ID, 'qpfw_phase', true );
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
			$var_sku     = get_post_meta( $var_item->ID, 'qpfw_sku', true );

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

		$qpfw_phase              = get_post_meta( $post_id, 'qpfw_phase', true );
		$qpfw_sku                = get_post_meta( $post_id, 'qpfw_sku', true );
		$qpfw_field_type         = get_post_meta( $post_id, 'qpfw_field_type', true );
		$qpfw_is_question        = (bool) get_post_meta( $post_id, 'qpfw_is_question', true );
		$qpfw_question_key       = get_post_meta( $post_id, 'qpfw_question_key', true );
		$qpfw_question_input_type = get_post_meta( $post_id, 'qpfw_question_input_type', true );
		$qpfw_question_placeholder = get_post_meta( $post_id, 'qpfw_question_placeholder', true );
		$qpfw_question_required  = get_post_meta( $post_id, 'qpfw_question_required', true );
		$qpfw_show_custom_input  = (bool) get_post_meta( $post_id, 'qpfw_show_custom_input', true );
		$qpfw_imgicon            = get_post_meta( $post_id, 'qpfw_imgicon', true );
		$qpfw_descopt            = get_post_meta( $post_id, 'qpfw_descopt', true );
		$qpfw_descvar            = get_post_meta( $post_id, 'qpfw_descvar', true );

		$qpfw_depends        = get_post_meta( $post_id, 'qpfw_depends', true );
		$qpfw_imgprodgroup   = get_post_meta( $post_id, 'qpfw_imgprodgroup', true );
		$qpfw_pricegroup     = get_post_meta( $post_id, 'qpfw_pricegroup', true );
		$qpfw_question_depends = get_post_meta( $post_id, 'qpfw_question_depends', true );

		if ( ! is_array( $qpfw_depends ) ) {
			$qpfw_depends = array();
		}
		if ( ! is_array( $qpfw_imgprodgroup ) ) {
			$qpfw_imgprodgroup = array();
		}
		if ( ! is_array( $qpfw_pricegroup ) ) {
			$qpfw_pricegroup = array();
		}
		if ( ! is_array( $qpfw_question_depends ) ) {
			$qpfw_question_depends = array();
		}

		if ( '' === $qpfw_question_input_type ) {
			$qpfw_question_input_type = 'number';
		}

		wp_nonce_field( 'qpfw_variation_save', 'qpfw_variation_nonce' );
		?>
		<table class="form-table qpfw-metabox-table">
			<tr>
				<th><label for="qpfw_phase"><?php esc_html_e( 'Phase', 'quote-product-flow' ); ?></label></th>
				<td>
					<select id="qpfw_phase" name="qpfw_phase" style="min-width:300px;">
						<option value=""><?php esc_html_e( 'Select a phase', 'quote-product-flow' ); ?></option>
						<?php foreach ( $phase_options as $pid => $plabel ) : ?>
							<option value="<?php echo esc_attr( (string) $pid ); ?>"<?php selected( $qpfw_phase, (string) $pid ); ?>><?php echo esc_html( $plabel ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="qpfw_sku"><?php esc_html_e( 'Reference', 'quote-product-flow' ); ?></label></th>
				<td><input type="text" id="qpfw_sku" name="qpfw_sku" value="<?php echo esc_attr( $qpfw_sku ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="qpfw_field_type"><?php esc_html_e( 'Field type', 'quote-product-flow' ); ?></label></th>
				<td>
					<select id="qpfw_field_type" name="qpfw_field_type">
						<option value=""<?php selected( $qpfw_field_type, '' ); ?>><?php esc_html_e( 'Default by price', 'quote-product-flow' ); ?></option>
						<option value="qty"<?php selected( $qpfw_field_type, 'qty' ); ?>><?php esc_html_e( 'Quantity', 'quote-product-flow' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="qpfw-toggle-row">
				<th><?php esc_html_e( 'Convert to Question', 'quote-product-flow' ); ?></th>
				<td>
					<div class="qpfw-toggle-wrap">
						<label class="qpfw-toggle">
							<input type="checkbox" id="qpfw_is_question" name="qpfw_is_question" value="1" <?php checked( $qpfw_is_question ); ?> />
							<span class="qpfw-toggle-slider"></span>
						</label>
						<span class="qpfw-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_attr_e( 'When enabled, this variation will show as an input field for the user to answer. The answer can be used in dependencies.', 'quote-product-flow' ); ?>"></span>
					</div>
				</td>
			</tr>
			<tr class="qpfw-question-field" style="<?php echo $qpfw_is_question ? '' : 'display:none;'; ?>">
				<th><label for="qpfw_question_key"><?php esc_html_e( 'Question Key', 'quote-product-flow' ); ?></label></th>
				<td>
					<input type="text" id="qpfw_question_key" name="qpfw_question_key" value="<?php echo esc_attr( $qpfw_question_key ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Unique identifier for this question (e.g., house_m2, height). Use lowercase and underscores.', 'quote-product-flow' ); ?></p>
				</td>
			</tr>
			<tr class="qpfw-question-field" style="<?php echo $qpfw_is_question ? '' : 'display:none;'; ?>">
				<th><label for="qpfw_question_input_type"><?php esc_html_e( 'Input Type', 'quote-product-flow' ); ?></label></th>
				<td>
					<select id="qpfw_question_input_type" name="qpfw_question_input_type">
						<option value="number"<?php selected( $qpfw_question_input_type, 'number' ); ?>><?php esc_html_e( 'Number', 'quote-product-flow' ); ?></option>
						<option value="text"<?php selected( $qpfw_question_input_type, 'text' ); ?>><?php esc_html_e( 'Text', 'quote-product-flow' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Type of input field to show', 'quote-product-flow' ); ?></p>
				</td>
			</tr>
			<tr class="qpfw-question-field" style="<?php echo $qpfw_is_question ? '' : 'display:none;'; ?>">
				<th><label for="qpfw_question_placeholder"><?php esc_html_e( 'Placeholder', 'quote-product-flow' ); ?></label></th>
				<td>
					<input type="text" id="qpfw_question_placeholder" name="qpfw_question_placeholder" value="<?php echo esc_attr( $qpfw_question_placeholder ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter the value (e.g. m²)', 'quote-product-flow' ); ?>" />
					<p class="description"><?php esc_html_e( 'Placeholder text for the input', 'quote-product-flow' ); ?></p>
				</td>
			</tr>
			<tr class="qpfw-question-field" style="<?php echo $qpfw_is_question ? '' : 'display:none;'; ?>">
				<th><?php esc_html_e( 'Required', 'quote-product-flow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="qpfw_question_required" value="1" <?php checked( $qpfw_question_required, '1' ); ?> />
						<?php esc_html_e( 'Make this question required', 'quote-product-flow' ); ?>
					</label>
				</td>
			</tr>
			<tr class="qpfw-toggle-row">
				<th><?php esc_html_e( 'Show custom input field', 'quote-product-flow' ); ?></th>
				<td>
					<div class="qpfw-toggle-wrap">
						<label class="qpfw-toggle">
							<input type="checkbox" name="qpfw_show_custom_input" value="1" <?php checked( $qpfw_show_custom_input ); ?> />
							<span class="qpfw-toggle-slider"></span>
						</label>
						<span class="qpfw-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_attr_e( 'If checked, an input field will appear below this variation when selected', 'quote-product-flow' ); ?>"></span>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Icon image', 'quote-product-flow' ); ?></th>
				<td>
					<?php
					$icon_img_url = '';
					if ( $qpfw_imgicon ) {
						$icon_src     = wp_get_attachment_image_src( (int) $qpfw_imgicon, array( 80, 80 ) );
						$icon_img_url = $icon_src ? $icon_src[0] : '';
					}
					?>
					<div class="qpfw-image-field">
						<input type="hidden" name="qpfw_imgicon" id="qpfw_imgicon" value="<?php echo esc_attr( $qpfw_imgicon ); ?>" />
						<?php if ( $icon_img_url ) : ?>
							<img src="<?php echo esc_url( $icon_img_url ); ?>" style="max-width:80px;max-height:80px;display:block;margin-bottom:6px;" class="qpfw-img-preview" />
						<?php else : ?>
							<img src="" style="max-width:80px;max-height:80px;display:none;margin-bottom:6px;" class="qpfw-img-preview" />
						<?php endif; ?>
						<button type="button" class="button qpfw-upload-image" data-target="qpfw_imgicon"><?php esc_html_e( 'Select image', 'quote-product-flow' ); ?></button>
						<button type="button" class="button qpfw-remove-image" data-target="qpfw_imgicon"<?php echo $qpfw_imgicon ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button>
					</div>
				</td>
			</tr>
		</table>

		<?php /* ---- PRICE GROUP ---- */ ?>
		<h3 style="padding:0 0 6px;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Price', 'quote-product-flow' ); ?></h3>
		<table class="widefat striped qpfw-repeatable" id="qpfw-pricegroup-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Option price', 'quote-product-flow' ); ?></th>
					<th><?php esc_html_e( 'Price (VAT not included)', 'quote-product-flow' ); ?></th>
					<th style="width:60px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$price_rows = ! empty( $qpfw_pricegroup ) ? $qpfw_pricegroup : array(
					array(
						'qpfw_meaprice' => '',
						'qpfw_pricem' => '',
					),
				);
				foreach ( $price_rows as $pri => $row ) :
					$meaprice = isset( $row['qpfw_meaprice'] ) ? $row['qpfw_meaprice'] : '';
					$pricem   = isset( $row['qpfw_pricem'] ) ? $row['qpfw_pricem'] : '';
				?>
				<tr>
					<td><input type="text" name="qpfw_pricegroup[<?php echo (int) $pri; ?>][qpfw_meaprice]" value="<?php echo esc_attr( $meaprice ); ?>" class="widefat" /></td>
					<td><input type="text" name="qpfw_pricegroup[<?php echo (int) $pri; ?>][qpfw_pricem]" value="<?php echo esc_attr( $pricem ); ?>" class="widefat" placeholder="0" /></td>
					<td><button type="button" class="button-link-delete qpfw-remove-row"><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<script type="text/template" id="qpfw-pricegroup-tpl">
			<tr>
				<td><input type="text" name="qpfw_pricegroup[__IDX__][qpfw_meaprice]" value="" class="widefat" /></td>
				<td><input type="text" name="qpfw_pricegroup[__IDX__][qpfw_pricem]" value="" class="widefat" placeholder="0" /></td>
				<td><button type="button" class="button-link-delete qpfw-remove-row"><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button></td>
			</tr>
		</script>
		<p><button type="button" class="button qpfw-add-row" data-table="qpfw-pricegroup-table" data-tpl="qpfw-pricegroup-tpl"><?php esc_html_e( 'Add price row', 'quote-product-flow' ); ?></button></p>

		<?php /* ---- DEPENDS GROUP ---- */ ?>
		<h3 style="padding:8px 0 6px;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Depends of', 'quote-product-flow' ); ?></h3>
		<div id="qpfw-depends-table" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:6px 12px;margin-bottom:8px;overflow:hidden;">
			<?php
			$dep_rows = ! empty( $qpfw_depends ) ? $qpfw_depends : array();
			if ( empty( $dep_rows ) ) {
				$dep_rows = array( array( 'qpfw_depvar' => '' ) );
			}
			foreach ( $dep_rows as $row ) :
				$depvar = isset( $row['qpfw_depvar'] ) ? $row['qpfw_depvar'] : '';
			?>
			<div class="qpfw-depends-item" style="display:flex;gap:4px;align-items:center;">
				<select name="qpfw_depends[][qpfw_depvar]" style="flex:1;min-width:0;">
					<option value=""><?php esc_html_e( 'Not depends of variation', 'quote-product-flow' ); ?></option>
					<?php foreach ( $var_options as $vval => $vlabel ) : ?>
						<option value="<?php echo esc_attr( $vval ); ?>"<?php selected( $depvar, $vval ); ?>><?php echo esc_html( $vlabel ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button-link-delete qpfw-remove-dep" style="white-space:nowrap;flex-shrink:0;"><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button>
			</div>
			<?php endforeach; ?>
		</div>
		<script type="text/template" id="qpfw-depends-tpl">
			<div class="qpfw-depends-item" style="display:flex;gap:4px;align-items:center;">
				<select name="qpfw_depends[][qpfw_depvar]" style="flex:1;min-width:0;">
					<option value=""><?php esc_html_e( 'Not depends of variation', 'quote-product-flow' ); ?></option>
					<?php foreach ( $var_options as $vval => $vlabel ) : ?>
						<option value="<?php echo esc_attr( $vval ); ?>"><?php echo esc_html( $vlabel ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button-link-delete qpfw-remove-dep" style="white-space:nowrap;flex-shrink:0;"><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button>
			</div>
		</script>
		<p><button type="button" class="button" id="qpfw-add-dep-row"><?php esc_html_e( 'Add dependency', 'quote-product-flow' ); ?></button></p>

		<?php /* ---- IMG PROD GROUP ---- */ ?>
		<h3 style="padding:8px 0 6px;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Product group image', 'quote-product-flow' ); ?></h3>
		<table class="widefat striped qpfw-repeatable" id="qpfw-imgprodgroup-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Depends of Variation', 'quote-product-flow' ); ?></th>
					<th><?php esc_html_e( 'Product image', 'quote-product-flow' ); ?></th>
					<th style="width:60px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$imgprod_rows = ! empty( $qpfw_imgprodgroup ) ? $qpfw_imgprodgroup : array(
					array(
						'qpfw_depvarimgprod' => array(),
						'qpfw_imgprod' => array(),
					),
				);
				foreach ( $imgprod_rows as $ri => $row ) :
					$depvarimgprod = isset( $row['qpfw_depvarimgprod'] ) ? (array) $row['qpfw_depvarimgprod'] : array();
					$imgprod_ids   = isset( $row['qpfw_imgprod'] ) ? (array) $row['qpfw_imgprod'] : array();
					$imgprod_id    = ! empty( $imgprod_ids ) ? (int) $imgprod_ids[0] : 0;
					$imgprod_url   = '';
					if ( $imgprod_id ) {
						$isrc        = wp_get_attachment_image_src( $imgprod_id, array( 80, 64 ) );
						$imgprod_url = $isrc ? $isrc[0] : '';
					}
					$field_name_dep = 'qpfw_imgprodgroup[' . $ri . '][qpfw_depvarimgprod][]';
					$field_name_img = 'qpfw_imgprodgroup[' . $ri . '][qpfw_imgprod]';
				?>
				<tr data-index="<?php echo (int) $ri; ?>">
					<td style="width:70%;max-width:0;">
						<select name="<?php echo esc_attr( $field_name_dep ); ?>" multiple style="width:100%;height:80px;box-sizing:border-box;">
							<?php foreach ( $var_options as $vval => $vlabel ) : ?>
								<option value="<?php echo esc_attr( $vval ); ?>"<?php echo in_array( $vval, $depvarimgprod, true ) ? ' selected' : ''; ?>><?php echo esc_html( $vlabel ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple', 'quote-product-flow' ); ?></p>
					</td>
					<td>
						<div class="qpfw-image-field">
							<input type="hidden" name="<?php echo esc_attr( $field_name_img ); ?>" class="qpfw-imgprod-id" value="<?php echo esc_attr( $imgprod_id ); ?>" />
							<?php if ( $imgprod_url ) : ?>
								<img src="<?php echo esc_url( $imgprod_url ); ?>" style="max-width:80px;max-height:64px;display:block;margin-bottom:4px;" class="qpfw-img-preview" />
							<?php else : ?>
								<img src="" style="max-width:80px;max-height:64px;display:none;margin-bottom:4px;" class="qpfw-img-preview" />
							<?php endif; ?>
							<button type="button" class="button qpfw-upload-imgprod"><?php esc_html_e( 'Select image', 'quote-product-flow' ); ?></button>
							<button type="button" class="button qpfw-remove-imgprod"<?php echo $imgprod_id ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button>
						</div>
					</td>
					<td><button type="button" class="button-link-delete qpfw-remove-row"><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" id="qpfw-add-imgprodgroup-row"><?php esc_html_e( 'Add product image row', 'quote-product-flow' ); ?></button></p>

		<?php /* ---- WYSIWYG FIELDS ---- */ ?>
		<table class="form-table qpfw-metabox-table" style="margin-top:16px;">
			<tr>
				<th><?php esc_html_e( 'Description after option', 'quote-product-flow' ); ?></th>
				<td>
					<?php
					wp_editor(
						wp_kses_post( $qpfw_descopt ),
						'qpfw_descopt',
						array(
							'textarea_name' => 'qpfw_descopt',
							'textarea_rows' => 5,
							'teeny'         => true,
							'media_buttons' => false,
						)
					);
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Description', 'quote-product-flow' ); ?></th>
				<td>
					<?php
					wp_editor(
						wp_kses_post( $qpfw_descvar ),
						'qpfw_descvar',
						array(
							'textarea_name' => 'qpfw_descvar',
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
		<h3 style="padding:8px 0 6px;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Depends on Question Answers', 'quote-product-flow' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Show this variation only when question answers meet these conditions', 'quote-product-flow' ); ?></p>
		<table class="widefat striped qpfw-repeatable" id="qpfw-qdepends-table" style="margin-top:8px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Question Key', 'quote-product-flow' ); ?></th>
					<th><?php esc_html_e( 'Operator', 'quote-product-flow' ); ?></th>
					<th><?php esc_html_e( 'Value', 'quote-product-flow' ); ?></th>
					<th style="width:60px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$qdep_rows = ! empty( $qpfw_question_depends ) ? $qpfw_question_depends : array();
				if ( empty( $qdep_rows ) ) {
					$qdep_rows = array(
						array(
							'qpfw_question_key_ref' => '',
							'qpfw_question_operator' => '>',
							'qpfw_question_value' => '',
						),
					);
				}
				$operator_options = array(
					'>'  => __( 'Greater than (>)', 'quote-product-flow' ),
					'>=' => __( 'Greater than or equal (>=)', 'quote-product-flow' ),
					'<'  => __( 'Less than (<)', 'quote-product-flow' ),
					'<=' => __( 'Less than or equal (<=)', 'quote-product-flow' ),
					'='  => __( 'Equal (=)', 'quote-product-flow' ),
					'!=' => __( 'Not equal (!=)', 'quote-product-flow' ),
				);
				foreach ( $qdep_rows as $qdi => $row ) :
					$qkey = isset( $row['qpfw_question_key_ref'] ) ? $row['qpfw_question_key_ref'] : '';
					$qop  = isset( $row['qpfw_question_operator'] ) ? $row['qpfw_question_operator'] : '>';
					$qval = isset( $row['qpfw_question_value'] ) ? $row['qpfw_question_value'] : '';
				?>
				<tr>
					<td>
						<input type="text" name="qpfw_question_depends[<?php echo (int) $qdi; ?>][qpfw_question_key_ref]" value="<?php echo esc_attr( $qkey ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. house_m2', 'quote-product-flow' ); ?>" />
					</td>
					<td>
						<select name="qpfw_question_depends[<?php echo (int) $qdi; ?>][qpfw_question_operator]">
							<?php foreach ( $operator_options as $oval => $olabel ) : ?>
								<option value="<?php echo esc_attr( $oval ); ?>"<?php selected( $qop, $oval ); ?>><?php echo esc_html( $olabel ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td>
						<input type="text" name="qpfw_question_depends[<?php echo (int) $qdi; ?>][qpfw_question_value]" value="<?php echo esc_attr( $qval ); ?>" class="widefat" />
					</td>
					<td><button type="button" class="button-link-delete qpfw-remove-row"><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<script type="text/template" id="qpfw-qdepends-tpl">
			<tr>
				<td><input type="text" name="qpfw_question_depends[__IDX__][qpfw_question_key_ref]" value="" class="widefat" placeholder="<?php esc_attr_e( 'e.g. house_m2', 'quote-product-flow' ); ?>" /></td>
				<td>
					<select name="qpfw_question_depends[__IDX__][qpfw_question_operator]">
						<?php foreach ( $operator_options as $oval => $olabel ) : ?>
							<option value="<?php echo esc_attr( $oval ); ?>"><?php echo esc_html( $olabel ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td><input type="text" name="qpfw_question_depends[__IDX__][qpfw_question_value]" value="" class="widefat" /></td>
				<td><button type="button" class="button-link-delete qpfw-remove-row"><?php esc_html_e( 'Remove', 'quote-product-flow' ); ?></button></td>
			</tr>
		</script>
		<p><button type="button" class="button qpfw-add-row" data-table="qpfw-qdepends-table" data-tpl="qpfw-qdepends-tpl"><?php esc_html_e( 'Add condition', 'quote-product-flow' ); ?></button></p>
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
		$qpfw_phase_note          = get_post_meta( $post_id, 'qpfw_phase_note', true );
		$qpfw_allow_multiple      = (bool) get_post_meta( $post_id, 'qpfw_allow_multiple_selections', true );
		$qpfw_show_direct_input   = (bool) get_post_meta( $post_id, 'qpfw_show_direct_input', true );
		$qpfw_direct_input_type   = get_post_meta( $post_id, 'qpfw_direct_input_type', true );
		if ( '' === $qpfw_direct_input_type ) {
			$qpfw_direct_input_type = 'textarea';
		}

		wp_nonce_field( 'qpfw_phase_save', 'qpfw_phase_nonce' );
		?>
		<table class="form-table qpfw-metabox-table">
			<tr>
				<th><?php esc_html_e( 'Note', 'quote-product-flow' ); ?></th>
				<td>
					<?php
					wp_editor(
						wp_kses_post( $qpfw_phase_note ),
						'qpfw_phase_note',
						array(
							'textarea_name' => 'qpfw_phase_note',
							'textarea_rows' => 5,
							'teeny'         => true,
							'media_buttons' => false,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Add a note that will be displayed between phase elements', 'quote-product-flow' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Allow multiple selections', 'quote-product-flow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="qpfw_allow_multiple_selections" value="1" <?php checked( $qpfw_allow_multiple ); ?> />
						<?php esc_html_e( 'If checked, users can select multiple variations instead of just one', 'quote-product-flow' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Show direct input field', 'quote-product-flow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" id="qpfw_show_direct_input" name="qpfw_show_direct_input" value="1" <?php checked( $qpfw_show_direct_input ); ?> />
						<?php esc_html_e( 'If checked, an input field will appear directly without needing to select a variation. Useful for open-ended questions like "What is your hobby?"', 'quote-product-flow' ); ?>
					</label>
				</td>
			</tr>
			<tr id="qpfw-direct-input-type-row" style="<?php echo $qpfw_show_direct_input ? '' : 'display:none;'; ?>">
				<th><label for="qpfw_direct_input_type"><?php esc_html_e( 'Input field type', 'quote-product-flow' ); ?></label></th>
				<td>
					<select id="qpfw_direct_input_type" name="qpfw_direct_input_type">
						<option value="textarea"<?php selected( $qpfw_direct_input_type, 'textarea' ); ?>><?php esc_html_e( 'Textarea (Large text box)', 'quote-product-flow' ); ?></option>
						<option value="text"<?php selected( $qpfw_direct_input_type, 'text' ); ?>><?php esc_html_e( 'Text (Single line)', 'quote-product-flow' ); ?></option>
						<option value="number"<?php selected( $qpfw_direct_input_type, 'number' ); ?>><?php esc_html_e( 'Number (With arrows)', 'quote-product-flow' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php
		wp_add_inline_script(
			'qpfw-admin-scripts',
			'jQuery(document).ready(function($){$("#qpfw_show_direct_input").on("change",function(){$("#qpfw-direct-input-type-row").toggle(this.checked);});});'
		);
		?>
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
		if ( ! isset( $_POST['qpfw_variation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qpfw_variation_nonce'] ) ), 'qpfw_variation_save' ) ) {
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
			'qpfw_phase'               => 'intval',
			'qpfw_sku'                 => 'sanitize_text_field',
			'qpfw_field_type'          => 'sanitize_key',
			'qpfw_question_key'        => 'sanitize_key',
			'qpfw_question_input_type' => 'sanitize_key',
			'qpfw_question_placeholder' => 'sanitize_text_field',
			'qpfw_descopt'             => 'wp_kses_post',
			'qpfw_descvar'             => 'wp_kses_post',
		);
		foreach ( $simple_fields as $key => $sanitizer ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$val = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			update_post_meta( $post_id, $key, call_user_func( $sanitizer, $val ) );
		}

		$checkbox_fields = array( 'qpfw_is_question', 'qpfw_question_required', 'qpfw_show_custom_input' );
		foreach ( $checkbox_fields as $key ) {
			$val = isset( $_POST[ $key ] ) ? 1 : 0;
			update_post_meta( $post_id, $key, $val );
		}

		// imgicon.
		$imgicon = isset( $_POST['qpfw_imgicon'] ) ? (int) $_POST['qpfw_imgicon'] : 0;
		update_post_meta( $post_id, 'qpfw_imgicon', $imgicon );

		// pricegroup.
		$pricegroup = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_price = isset( $_POST['qpfw_pricegroup'] ) ? wp_unslash( $_POST['qpfw_pricegroup'] ) : array();
		if ( is_array( $raw_price ) ) {
			foreach ( $raw_price as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$pricegroup[] = array(
					'qpfw_meaprice' => sanitize_text_field( isset( $row['qpfw_meaprice'] ) ? $row['qpfw_meaprice'] : '' ),
					'qpfw_pricem'   => sanitize_text_field( isset( $row['qpfw_pricem'] ) ? $row['qpfw_pricem'] : '' ),
				);
			}
		}
		update_post_meta( $post_id, 'qpfw_pricegroup', $pricegroup );

		// depends.
		$depends = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_dep = isset( $_POST['qpfw_depends'] ) ? wp_unslash( $_POST['qpfw_depends'] ) : array();
		if ( is_array( $raw_dep ) ) {
			foreach ( $raw_dep as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$depvar = sanitize_text_field( isset( $row['qpfw_depvar'] ) ? $row['qpfw_depvar'] : '' );
				$depends[] = array( 'qpfw_depvar' => $depvar );
			}
		}
		update_post_meta( $post_id, 'qpfw_depends', $depends );

		// imgprodgroup.
		$imgprodgroup = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_imgprod = isset( $_POST['qpfw_imgprodgroup'] ) ? wp_unslash( $_POST['qpfw_imgprodgroup'] ) : array();
		if ( is_array( $raw_imgprod ) ) {
			foreach ( $raw_imgprod as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$depvar_arr = array();
				if ( isset( $row['qpfw_depvarimgprod'] ) && is_array( $row['qpfw_depvarimgprod'] ) ) {
					foreach ( $row['qpfw_depvarimgprod'] as $dv ) {
						$depvar_arr[] = sanitize_text_field( $dv );
					}
				}
				$imgprod_id     = isset( $row['qpfw_imgprod'] ) ? (int) $row['qpfw_imgprod'] : 0;
				$imgprodgroup[] = array(
					'qpfw_depvarimgprod' => $depvar_arr,
					'qpfw_imgprod'       => $imgprod_id ? array( $imgprod_id ) : array(),
				);
			}
		}
		update_post_meta( $post_id, 'qpfw_imgprodgroup', $imgprodgroup );

		// question_depends.
		$question_depends = array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_qdep = isset( $_POST['qpfw_question_depends'] ) ? wp_unslash( $_POST['qpfw_question_depends'] ) : array();
		if ( is_array( $raw_qdep ) ) {
			$allowed_ops = array( '>', '>=', '<', '<=', '=', '!=' );
			foreach ( $raw_qdep as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$qkey = sanitize_key( isset( $row['qpfw_question_key_ref'] ) ? $row['qpfw_question_key_ref'] : '' );
				$qop  = sanitize_text_field( isset( $row['qpfw_question_operator'] ) ? $row['qpfw_question_operator'] : '>' );
				if ( ! in_array( $qop, $allowed_ops, true ) ) {
					$qop = '>';
				}
				$qval             = sanitize_text_field( isset( $row['qpfw_question_value'] ) ? $row['qpfw_question_value'] : '' );
				$question_depends[] = array(
					'qpfw_question_key_ref'  => $qkey,
					'qpfw_question_operator' => $qop,
					'qpfw_question_value'    => $qval,
				);
			}
		}
		update_post_meta( $post_id, 'qpfw_question_depends', $question_depends );
	}

	/**
	 * Save phase metabox fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_phase_meta( $post_id, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! isset( $_POST['qpfw_phase_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qpfw_phase_nonce'] ) ), 'qpfw_phase_save' ) ) {
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
		$phase_note = isset( $_POST['qpfw_phase_note'] ) ? wp_kses_post( wp_unslash( $_POST['qpfw_phase_note'] ) ) : '';
		update_post_meta( $post_id, 'qpfw_phase_note', $phase_note );

		$allow_multiple = isset( $_POST['qpfw_allow_multiple_selections'] ) ? 1 : 0;
		update_post_meta( $post_id, 'qpfw_allow_multiple_selections', $allow_multiple );

		$show_direct = isset( $_POST['qpfw_show_direct_input'] ) ? 1 : 0;
		update_post_meta( $post_id, 'qpfw_show_direct_input', $show_direct );

		$allowed_input_types = array( 'textarea', 'text', 'number' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$direct_input_type = sanitize_key( isset( $_POST['qpfw_direct_input_type'] ) ? wp_unslash( $_POST['qpfw_direct_input_type'] ) : 'textarea' );
		if ( ! in_array( $direct_input_type, $allowed_input_types, true ) ) {
			$direct_input_type = 'textarea';
		}
		update_post_meta( $post_id, 'qpfw_direct_input_type', $direct_input_type );
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
		$new_columns['title']      = __( 'Phase', 'quote-product-flow' );
		$new_columns['menu_order'] = __( 'Order', 'quote-product-flow' );
		$new_columns['variations'] = __( 'Variations', 'quote-product-flow' );
		$new_columns['shortcode']  = __( 'Shortcode', 'quote-product-flow' );

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
						'post_type'      => 'qpfw_variation',
						'posts_per_page' => -1,
						'meta_key'       => 'qpfw_phase',
						'meta_value'     => $id,
						'fields'         => 'ids',
					)
				);
				$count      = ! empty( $variations ) ? count( $variations ) : 0;

				// Create link to variations filtered by this phase.
				$url = add_query_arg(
					array(
						'post_type'         => 'qpfw_variation',
						'qpfw_filter_phase' => $id,
					),
					admin_url( 'edit.php' )
				);

				if ( $count > 0 ) {
					echo '<a href="' . esc_url( $url ) . '" title="' . esc_attr__( 'View variations of this phase', 'quote-product-flow' ) . '">';
					echo esc_html( $count );
					echo '</a>';
				} else {
					echo esc_html( $count );
				}
				break;
			case 'shortcode':
				$post_parent = $post->post_parent;
				if ( empty( $post_parent ) && $is_multiple ) {
					echo '<input type="text" value="[quote-product-flow pid=' . (int) $id . ']" readonly style="min-width:130px"/>';
				}
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
		$new_columns['title']   = __( 'Variation', 'quote-product-flow' );
		$new_columns['phase']   = __( 'Phase and section', 'quote-product-flow' );
		$new_columns['price']   = __( 'Price', 'quote-product-flow' );
		$new_columns['depends'] = __( 'Depends of', 'quote-product-flow' );
		$new_columns['imgicon'] = __( 'Icon', 'quote-product-flow' );
		$new_columns['imgprod'] = __( 'Product Images', 'quote-product-flow' );

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
		$phase_id = get_post_meta( $id, 'qpfw_phase', true );

		switch ( $column_name ) {
			case 'phase':
				$phase_post = get_post( $phase_id );
				// Phase parent.
				if ( $phase_post->post_parent > 0 ) {
					$phase_parent      = get_post( $phase_post->post_parent );
					$phase_parent_link = admin_url( 'edit.php?post_type=qpfw_phases#post-' . $phase_parent->ID );
					echo '<a href="' . esc_url( $phase_parent_link ) . '" title="' . esc_attr__( 'Go to phases list', 'quote-product-flow' ) . '">';
					echo esc_html( $phase_parent->post_title );
					echo '</a><br/>';
				}
				// Phase link - goes to phases list and scrolls to this phase.
				$phase_list_link = admin_url( 'edit.php?post_type=qpfw_phases#post-' . $phase_id );
				echo '<a href="' . esc_url( $phase_list_link ) . '" title="' . esc_attr__( 'Go to phases list', 'quote-product-flow' ) . '">';
				echo esc_html( CALC::adds_zero( $phase_post->menu_order ) . ' - ' . $phase_post->post_title );
				echo '</a>';

				// Shows taxonomy.
				$term_list = wp_get_post_terms( $id, 'qpfw_variation_tag', array( 'fields' => 'all' ) );
				foreach ( $term_list as $term_single ) {
					echo '<p class="taxonomy-variation_tag">' . esc_html( $term_single->name ) . '</p>';
				}
				break;
			case 'price':
				// Price group.
				$price_group = get_post_meta( $id, 'qpfw_pricegroup', true );
				if ( ! is_array( $price_group ) ) {
					break;
				}
				foreach ( $price_group as $price_item ) {
					if ( ! is_array( $price_item ) ) {
						continue;
					}
					if ( isset( $price_item['qpfw_meaprice'] ) ) {
						echo esc_attr( $price_item['qpfw_meaprice'] ) . ' - ' . esc_attr( $price_item['qpfw_pricem'] ) . ' €';
					} elseif ( isset( $price_item['qpfw_pricem'] ) ) {
						echo esc_attr( $price_item['qpfw_pricem'] ) . ' €';
					}
					echo '<br/>';
				}
				break;
			case 'depends':
				// Depends group.
				$depends_group = (array) get_post_meta( $id, 'qpfw_depends', true );
				$depends_group = array_filter( $depends_group, 'is_array' );
				if ( empty( $depends_group ) ) {
					break;
				}
				foreach ( $depends_group as $depends_item ) {
					$depvar         = explode( '|', $depends_item['qpfw_depvar'] );
					$variation_id   = isset( $depvar[1] ) ? (int) $depvar[1] : 0;
					$variation_post = $variation_id ? get_post( $variation_id ) : null;
					if ( ! $variation_post ) {
						echo esc_html( $depends_item['qpfw_depvar'] ) . '<br/>';
						continue;
					}
					$phase_id_dp   = get_post_meta( $variation_id, 'qpfw_phase', true );
					$phase_post_dp = $phase_id_dp ? get_post( $phase_id_dp ) : null;
					if ( $phase_post_dp ) {
						echo esc_html( CALC::adds_zero( $phase_post_dp->menu_order ) ) . ' - ';
						echo esc_html( $phase_post_dp->post_title ) . ' - ';
					}
					echo esc_html( $variation_post->post_title ) . '<br/>';
				}
				break;
			case 'imgicon':
				// Image icon.
				$imgicon = get_post_meta( $id, 'qpfw_imgicon', true );
				if ( $imgicon ) {
					$icon_image = wp_get_attachment_image_src( $imgicon, array( 120, 120 ), true );
				}
				if ( isset( $icon_image ) ) {
					echo '<img src="' . esc_url( $icon_image[0] ) . '" />';
				}
				break;
			case 'imgprod':
				// Image Group Product.
				$image_group = (array) get_post_meta( $id, 'qpfw_imgprodgroup', true );
				if ( ! empty( $image_group ) ) {
					if ( count( $image_group ) > 0 ) {
						echo count( $image_group ) . '<br>';
					}
					foreach ( $image_group as $imageg_item ) {
						if ( isset( $imageg_item['qpfw_imgprod'][0] ) ) {
							$icon_imageprod = wp_get_attachment_image_src( $imageg_item['qpfw_imgprod'][0], array( 57, 46 ), true );
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

		if ( 'qpfw_variation' !== $typenow ) {
			return;
		}

		// Get current filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET request for admin filter, no data modification.
		$selected = isset( $_GET['qpfw_filter_phase'] ) ? (int) $_GET['qpfw_filter_phase'] : 0;

		// Get ALL phases.
		$phases = get_posts(
			array(
				'post_type' => 'qpfw_phases',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		if ( empty( $phases ) ) {
			return;
		}
		?>
		<select name="qpfw_filter_phase" id="qpfw_phase_select">
			<option value=""><?php esc_html_e( '📋 All Phases', 'quote-product-flow' ); ?></option>
			<?php
			foreach ( $phases as $phase ) {
				$count = count(
					get_posts(
						array(
							'post_type'      => 'qpfw_variation',
							'posts_per_page' => -1,
							'meta_key'       => 'qpfw_phase',
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

		<?php
		wp_add_inline_script(
			'jquery',
			'jQuery(document).ready(function($){$("#qpfw_phase_select").on("change",function(){$(this).closest("form").submit();});$("#post-query-submit").hide();});'
		);
		?>
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
	public function qpfw_posts_filter( $query ) {
		global $pagenow, $typenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || 'qpfw_variation' !== $typenow || ! $query->is_main_query() ) {
			return $query;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET request for admin filter, no data modification.
		if ( isset( $_GET['qpfw_filter_phase'] ) && $_GET['qpfw_filter_phase'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$phase_id = (int) $_GET['qpfw_filter_phase'];
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( $phase_id > 0 ) {
				$query->set( 'meta_key', 'qpfw_phase' );
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
		if ( ! $screen || 'qpfw_variation' !== $screen->post_type ) {
			return;
		}

		// Check for duplicate key parameter.
		if ( isset( $_GET['qpfw_duplicate_key'] ) && '1' === $_GET['qpfw_duplicate_key'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id      = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$question_key = get_transient( 'qpfw_duplicate_key_' . $post_id );

			if ( $question_key ) {
				delete_transient( 'qpfw_duplicate_key_' . $post_id );
				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<strong><?php esc_html_e( 'Error:', 'quote-product-flow' ); ?></strong>
						<?php
						printf(
							/* translators: %s: question key */
							esc_html__( 'Don\'t use the same key! Another variation already uses the Question Key "%s". Please use a unique key for each question.', 'quote-product-flow' ),
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
		$is_question = isset( $_POST['qpfw_is_question'] ) ? (int) $_POST['qpfw_is_question'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $is_question ) {
			return;
		}

		// Get the question key.
		$question_key = isset( $_POST['qpfw_question_key'] ) ? sanitize_key( wp_unslash( $_POST['qpfw_question_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $question_key ) ) {
			return;
		}

		// Check if another variation already uses this key.
		$existing_variations = get_posts(
			array(
				'post_type'      => 'qpfw_variation',
				'posts_per_page' => -1,
				'post__not_in'   => array( $post_id ),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => 'qpfw_question_key',
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
					return add_query_arg( 'qpfw_duplicate_key', '1', $location );
				}
			);

			// Also set a transient for the notice.
			set_transient( 'qpfw_duplicate_key_' . $post_id, $question_key, 30 );
		}
	}
	/**
	 * Add duplicate row action to QPFW post types.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post object.
	 * @return array
	 */
	public function add_duplicate_row_action( $actions, $post ) {
		$qpfw_post_types = array( 'qpfw_phases', 'qpfw_variation' );

		if ( ! in_array( $post->post_type, $qpfw_post_types, true ) ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'qpfw_duplicate_post',
					'post_id' => $post->ID,
				),
				admin_url( 'admin.php' )
			),
			'qpfw_duplicate_post_' . $post->ID
		);

		$actions['qpfw_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'quote-product-flow' ) . '</a>';

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
			wp_die( esc_html__( 'Invalid post ID.', 'quote-product-flow' ) );
		}

		check_admin_referer( 'qpfw_duplicate_post_' . $post_id );

		$post = get_post( $post_id );

		if ( ! $post ) {
			wp_die( esc_html__( 'Post not found.', 'quote-product-flow' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate this post.', 'quote-product-flow' ) );
		}

		$new_post_args = array(
			'post_title'     => $post->post_title . ' ' . __( '(Copy)', 'quote-product-flow' ),
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

