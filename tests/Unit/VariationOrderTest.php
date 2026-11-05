<?php
/**
 * Class VariationOrderTest
 *
 * Tests for the "order by title" / per-variation display order helpers.
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\Helpers\CALC;
use WP_UnitTestCase;

/**
 * Test case for CALC variation ordering helpers.
 */
class VariationOrderTest extends WP_UnitTestCase {

	/**
	 * Tear down: clear the global order-by-title option after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		delete_option( 'qpfw_order_by_title' );
		parent::tear_down();
	}

	/**
	 * Test get_order_by_title_map returns an empty array when the option is unset.
	 *
	 * @return void
	 */
	public function test_get_order_by_title_map_empty_when_option_unset() {
		delete_option( 'qpfw_order_by_title' );
		$this->assertSame( array(), CALC::get_order_by_title_map() );
	}

	/**
	 * Test get_order_by_title_map builds a title => order map from stored rows.
	 *
	 * @return void
	 */
	public function test_get_order_by_title_map_builds_map_from_rows() {
		update_option(
			'qpfw_order_by_title',
			array(
				array(
					'qpfw_order_title' => 'Medida pequeña',
					'qpfw_order_value' => '1',
				),
				array(
					'qpfw_order_title' => 'Medida grande',
					'qpfw_order_value' => 5,
				),
			)
		);

		$map = CALC::get_order_by_title_map();

		$this->assertSame( 1, $map['Medida pequeña'] );
		$this->assertSame( 5, $map['Medida grande'] );
	}

	/**
	 * Test get_order_by_title_map skips rows with an empty title.
	 *
	 * @return void
	 */
	public function test_get_order_by_title_map_skips_rows_with_empty_title() {
		update_option(
			'qpfw_order_by_title',
			array(
				array(
					'qpfw_order_title' => '  ',
					'qpfw_order_value' => 1,
				),
			)
		);

		$this->assertSame( array(), CALC::get_order_by_title_map() );
	}

	/**
	 * Test get_variation_display_order falls back to the variation's own
	 * meta when no title match exists in the global map.
	 *
	 * @return void
	 */
	public function test_get_variation_display_order_falls_back_to_own_meta() {
		delete_option( 'qpfw_order_by_title' );
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );
		update_post_meta( $post_id, 'qpfw_display_order', 7 );

		$order = CALC::get_variation_display_order( $post_id, 'Some Title' );

		$this->assertSame( 7, $order );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_variation_display_order defaults to 0 with no meta and no title match.
	 *
	 * @return void
	 */
	public function test_get_variation_display_order_defaults_to_zero() {
		delete_option( 'qpfw_order_by_title' );
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );

		$this->assertSame( 0, CALC::get_variation_display_order( $post_id, 'Some Title' ) );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_variation_display_order prefers the global title match over
	 * the variation's own per-phase meta.
	 *
	 * @return void
	 */
	public function test_get_variation_display_order_title_match_wins_over_own_meta() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );
		update_post_meta( $post_id, 'qpfw_display_order', 9 );
		update_option(
			'qpfw_order_by_title',
			array(
				array(
					'qpfw_order_title' => 'Medida pequeña',
					'qpfw_order_value' => 1,
				),
			)
		);

		$order = CALC::get_variation_display_order( $post_id, 'Medida pequeña' );

		$this->assertSame( 1, $order, 'Global title-based order should win over the variation own meta' );

		wp_delete_post( $post_id, true );
	}
}
