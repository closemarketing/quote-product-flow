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

	/**
	 * Test get_variation_ids_by_title returns empty array when nothing matches.
	 *
	 * @return void
	 */
	public function test_get_variation_ids_by_title_no_match() {
		$this->assertSame( array(), CALC::get_variation_ids_by_title( 'no-such-variation-title-xyz' ) );
	}

	/**
	 * Test get_variation_ids_by_title matches exactly and case-insensitively.
	 *
	 * @return void
	 */
	public function test_get_variation_ids_by_title_matches_case_insensitively() {
		$phase_id     = $this->create_phase( 1 );
		$variation_id = $this->create_variation( 'Test Title Case', $phase_id );

		$result = CALC::get_variation_ids_by_title( 'test title case' );

		$this->assertSame( array( $variation_id ), $result );
	}

	/**
	 * Test get_variation_ids_by_title does not match a partial title.
	 *
	 * @return void
	 */
	public function test_get_variation_ids_by_title_does_not_match_partial_title() {
		$phase_id = $this->create_phase( 1 );
		$this->create_variation( '130x150 Extra', $phase_id );

		$result = CALC::get_variation_ids_by_title( '130x150' );

		$this->assertSame( array(), $result );
	}

	/**
	 * Test expand_depend_row returns empty array for an empty depvar.
	 *
	 * @return void
	 */
	public function test_expand_depend_row_empty_depvar() {
		$this->assertSame( array(), CALC::expand_depend_row( '', array( 1, 2, 3 ) ) );
	}

	/**
	 * Test expand_depend_row resolves the legacy "order|id" format.
	 *
	 * @return void
	 */
	public function test_expand_depend_row_legacy_format_resolves_step_order() {
		$phases_order = array( 1, 2, 3 );

		$result = CALC::expand_depend_row( '2|55', $phases_order );

		$this->assertSame( array( 1 => array( 55 ) ), $result );
	}

	/**
	 * Test expand_depend_row legacy format returns empty array when the phase
	 * menu_order is not present in phases_order.
	 *
	 * @return void
	 */
	public function test_expand_depend_row_legacy_format_menu_order_not_found() {
		$phases_order = array( 1, 2, 3 );

		$result = CALC::expand_depend_row( '9|55', $phases_order );

		$this->assertSame( array(), $result );
	}

}
