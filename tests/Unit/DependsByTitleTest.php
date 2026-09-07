<?php
/**
 * Class DependsByTitleTest
 *
 * Tests for the "depends by title" feature in the CALC helper class.
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\Helpers\CALC;
use WP_UnitTestCase;

/**
 * Test case for CALC::get_variation_ids_by_title() and CALC::expand_depend_row().
 */
class DependsByTitleTest extends WP_UnitTestCase {
	/**
	 * Creates a qpfw_phases post with a given menu_order.
	 *
	 * @param int $menu_order Menu order for the phase.
	 * @return int Phase post ID.
	 */
	private function create_phase( $menu_order ) {
		return $this->factory->post->create(
			array(
				'post_type'  => 'qpfw_phases',
				'menu_order' => $menu_order,
			)
		);
	}

	/**
	 * Creates a qpfw_variation post linked to a phase via qpfw_phase meta.
	 *
	 * @param string $title    Variation title.
	 * @param int    $phase_id Phase post ID this variation belongs to.
	 * @return int Variation post ID.
	 */
	private function create_variation( $title, $phase_id ) {
		$variation_id = $this->factory->post->create(
			array(
				'post_type'  => 'qpfw_variation',
				'post_title' => $title,
			)
		);
		update_post_meta( $variation_id, 'qpfw_phase', $phase_id );

		return $variation_id;
	}

	/**
	 * Test get_variation_ids_by_title returns empty array for an empty title.
	 *
	 * @return void
	 */
	public function test_get_variation_ids_by_title_empty_string() {
		$this->assertSame( array(), CALC::get_variation_ids_by_title( '' ) );
	}

	/**
	 * Test get_variation_ids_by_title returns empty array for a whitespace-only title.
	 *
	 * @return void
	 */
	public function test_get_variation_ids_by_title_whitespace_only() {
		$this->assertSame( array(), CALC::get_variation_ids_by_title( '   ' ) );
	}

}
