<?php
/**
 * Class CheckPhasesTest
 *
 * Tests for CALC::check_phases_options() — the phase dependency validator.
 *
 * @package Product_Budget_Configurator
 */

namespace Close\PBC\Tests\Unit;

use Close\PBC\Helpers\CALC;
use WP_UnitTestCase;

/**
 * Test case for CALC::check_phases_options().
 */
class CheckPhasesTest extends WP_UnitTestCase {

	/**
	 * Phase IDs created during a test, cleaned up in tearDown.
	 *
	 * @var int[]
	 */
	private $phase_ids = array();

	/**
	 * Remove all phases created during the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( $this->phase_ids as $id ) {
			wp_delete_post( $id, true );
		}
		$this->phase_ids = array();
		parent::tearDown();
	}

	/**
	 * Helper: create a 'phases' post and track it for cleanup.
	 *
	 * @param array $args wp_insert_post-compatible args merged onto safe defaults.
	 * @return int Created post ID.
	 */
	private function create_phase( $args = array() ) {
		$defaults = array(
			'post_type'   => 'phases',
			'post_status' => 'publish',
			'post_title'  => 'Test Phase',
			'post_parent' => 0,
		);
		$id                 = $this->factory->post->create( array_merge( $defaults, $args ) );
		$this->phase_ids[]  = $id;
		return $id;
	}

	// -------------------------------------------------------------------------
	// Result structure
	// -------------------------------------------------------------------------

	/**
	 * Test the returned array always has the expected keys.
	 *
	 * @return void
	 */
	public function test_result_has_expected_keys() {
		$result = CALC::check_phases_options( array() );

		$this->assertArrayHasKey( 'valid', $result );
		$this->assertArrayHasKey( 'passed_ids', $result );
		$this->assertArrayHasKey( 'failed_ids', $result );
		$this->assertArrayHasKey( 'details', $result );
	}

	// -------------------------------------------------------------------------
	// Empty / edge inputs
	// -------------------------------------------------------------------------

	/**
	 * Test empty array input returns valid (nothing failed).
	 *
	 * @return void
	 */
	public function test_empty_phase_ids_returns_valid() {
		$result = CALC::check_phases_options( array() );

		$this->assertTrue( $result['valid'] );
		$this->assertEmpty( $result['passed_ids'] );
		$this->assertEmpty( $result['failed_ids'] );
	}

	/**
	 * Test a scalar phase ID is converted to an array internally.
	 *
	 * @return void
	 */
	public function test_scalar_phase_id_is_accepted() {
		$phase_id = $this->create_phase();

		$result = CALC::check_phases_options( $phase_id );

		$this->assertContains( $phase_id, $result['passed_ids'] );
	}

	// -------------------------------------------------------------------------
	// Published check
	// -------------------------------------------------------------------------

	/**
	 * Test a published phase passes the published check.
	 *
	 * @return void
	 */
	public function test_published_phase_passes() {
		$phase_id = $this->create_phase( array( 'post_status' => 'publish' ) );

		$result = CALC::check_phases_options( array( $phase_id ) );

		$this->assertTrue( $result['valid'] );
		$this->assertContains( $phase_id, $result['passed_ids'] );
		$this->assertEmpty( $result['failed_ids'] );
	}

	/**
	 * Test a draft phase fails the published check.
	 *
	 * @return void
	 */
	public function test_draft_phase_fails_published_check() {
		$phase_id = $this->create_phase( array( 'post_status' => 'draft' ) );

		$result = CALC::check_phases_options( array( $phase_id ) );

		$this->assertFalse( $result['valid'] );
		$this->assertContains( $phase_id, $result['failed_ids'] );
		$this->assertContains( 'not_published', $result['details'][ $phase_id ]['reasons'] );
	}

	/**
	 * Test a non-existent post ID fails the published check.
	 *
	 * @return void
	 */
	public function test_nonexistent_phase_fails() {
		$fake_id = 999999;

		$result = CALC::check_phases_options( array( $fake_id ) );

		$this->assertFalse( $result['valid'] );
		$this->assertContains( $fake_id, $result['failed_ids'] );
		$this->assertContains( 'not_published', $result['details'][ $fake_id ]['reasons'] );
	}

	/**
	 * Test a post with the wrong post_type fails even when published.
	 *
	 * @return void
	 */
	public function test_wrong_post_type_fails() {
		$post_id           = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->phase_ids[] = $post_id;

		$result = CALC::check_phases_options( array( $post_id ) );

		$this->assertFalse( $result['valid'] );
		$this->assertContains( 'not_published', $result['details'][ $post_id ]['reasons'] );
	}

	/**
	 * Test published check is skipped when published option is false.
	 *
	 * @return void
	 */
	public function test_skip_published_check_allows_draft() {
		$phase_id = $this->create_phase( array( 'post_status' => 'draft' ) );

		$result = CALC::check_phases_options( array( $phase_id ), array( 'published' => false ) );

		$this->assertTrue( $result['valid'] );
		$this->assertContains( $phase_id, $result['passed_ids'] );
	}

	// -------------------------------------------------------------------------
	// Parent check
	// -------------------------------------------------------------------------

	/**
	 * Test phase passes when its parent matches the expected value.
	 *
	 * @return void
	 */
	public function test_parent_check_passes_with_correct_parent() {
		$parent_id = $this->create_phase();
		$child_id  = $this->create_phase( array( 'post_parent' => $parent_id ) );

		$result = CALC::check_phases_options(
			array( $child_id ),
			array( 'parent' => $parent_id )
		);

		$this->assertTrue( $result['valid'] );
		$this->assertContains( $child_id, $result['passed_ids'] );
	}

	/**
	 * Test phase fails when its parent does not match the expected value.
	 *
	 * @return void
	 */
	public function test_parent_check_fails_with_wrong_parent() {
		$wrong_parent = $this->create_phase();
		$child_id     = $this->create_phase( array( 'post_parent' => 0 ) );

		$result = CALC::check_phases_options(
			array( $child_id ),
			array( 'parent' => $wrong_parent )
		);

		$this->assertFalse( $result['valid'] );
		$this->assertContains( $child_id, $result['failed_ids'] );
		$this->assertContains( 'parent_mismatch', $result['details'][ $child_id ]['reasons'] );
	}

	/**
	 * Test that a nonexistent phase ID with published=false is treated as passing
	 * the parent check (get_post returns null so the parent guard is skipped).
	 *
	 * @return void
	 */
	public function test_nonexistent_phase_with_skip_published_passes_parent_silently() {
		$fake_id = 999998;

		$result = CALC::check_phases_options(
			array( $fake_id ),
			array(
				'published' => false,
				'parent'    => 0,
			)
		);

		// When published check is disabled and get_post() returns null,
		// the parent guard ($phase && ...) is also skipped → phase "passes".
		$this->assertTrue( $result['valid'] );
		$this->assertContains( $fake_id, $result['passed_ids'] );
		$this->assertEmpty( $result['details'][ $fake_id ]['reasons'] );
	}

	/**
	 * Test top-level phase passes when parent option is explicitly 0.
	 *
	 * @return void
	 */
	public function test_parent_check_passes_for_top_level_phase() {
		$phase_id = $this->create_phase( array( 'post_parent' => 0 ) );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array( 'parent' => 0 )
		);

		$this->assertTrue( $result['valid'] );
	}

	// -------------------------------------------------------------------------
	// Meta conditions
	// -------------------------------------------------------------------------

	/**
	 * Test phase passes when a meta value matches the expected string.
	 *
	 * @return void
	 */
	public function test_meta_string_condition_passes() {
		$phase_id = $this->create_phase();
		update_post_meta( $phase_id, 'pbc_phase_active', 'yes' );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array( 'meta_conditions' => array( 'pbc_phase_active' => 'yes' ) )
		);

		$this->assertTrue( $result['valid'] );
		$this->assertContains( $phase_id, $result['passed_ids'] );
	}

	/**
	 * Test phase fails when a meta value does not match the expected string.
	 *
	 * @return void
	 */
	public function test_meta_string_condition_fails() {
		$phase_id = $this->create_phase();
		update_post_meta( $phase_id, 'pbc_phase_active', 'no' );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array( 'meta_conditions' => array( 'pbc_phase_active' => 'yes' ) )
		);

		$this->assertFalse( $result['valid'] );
		$this->assertContains( $phase_id, $result['failed_ids'] );
		$this->assertContains( 'meta_pbc_phase_active_mismatch', $result['details'][ $phase_id ]['reasons'] );
	}

	/**
	 * Test phase passes when its meta value is within the expected array of values.
	 *
	 * @return void
	 */
	public function test_meta_array_condition_passes_when_value_in_array() {
		$phase_id = $this->create_phase();
		update_post_meta( $phase_id, 'pbc_type', 'premium' );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array( 'meta_conditions' => array( 'pbc_type' => array( 'free', 'premium' ) ) )
		);

		$this->assertTrue( $result['valid'] );
	}

	/**
	 * Test phase fails when its meta value is not in the expected array of values.
	 *
	 * @return void
	 */
	public function test_meta_array_condition_fails_when_value_not_in_array() {
		$phase_id = $this->create_phase();
		update_post_meta( $phase_id, 'pbc_type', 'enterprise' );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array( 'meta_conditions' => array( 'pbc_type' => array( 'free', 'premium' ) ) )
		);

		$this->assertFalse( $result['valid'] );
		$this->assertContains( 'meta_pbc_type_not_in_expected', $result['details'][ $phase_id ]['reasons'] );
	}

	/**
	 * Test phase fails when the meta key does not exist at all.
	 *
	 * @return void
	 */
	public function test_meta_condition_fails_when_key_missing() {
		$phase_id = $this->create_phase();

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array( 'meta_conditions' => array( 'pbc_nonexistent_key' => 'expected_value' ) )
		);

		$this->assertFalse( $result['valid'] );
		$this->assertContains( $phase_id, $result['failed_ids'] );
	}

	/**
	 * Test multiple meta conditions must all pass.
	 *
	 * @return void
	 */
	public function test_multiple_meta_conditions_all_must_pass() {
		$phase_id = $this->create_phase();
		update_post_meta( $phase_id, 'pbc_phase_active', 'yes' );
		update_post_meta( $phase_id, 'pbc_type', 'premium' );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array(
				'meta_conditions' => array(
					'pbc_phase_active' => 'yes',
					'pbc_type'         => 'premium',
				),
			)
		);

		$this->assertTrue( $result['valid'] );
	}

	/**
	 * Test one failing meta condition is enough to fail the whole phase.
	 *
	 * @return void
	 */
	public function test_one_failing_meta_condition_fails_the_phase() {
		$phase_id = $this->create_phase();
		update_post_meta( $phase_id, 'pbc_phase_active', 'yes' );
		update_post_meta( $phase_id, 'pbc_type', 'free' );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array(
				'meta_conditions' => array(
					'pbc_phase_active' => 'yes',
					'pbc_type'         => 'premium',
				),
			)
		);

		$this->assertFalse( $result['valid'] );
		$this->assertContains( $phase_id, $result['failed_ids'] );
	}

	// -------------------------------------------------------------------------
	// all_must_pass flag
	// -------------------------------------------------------------------------

	/**
	 * Test all_must_pass=true: valid only when every phase passes.
	 *
	 * @return void
	 */
	public function test_all_must_pass_true_fails_when_one_fails() {
		$good_id = $this->create_phase( array( 'post_status' => 'publish' ) );
		$bad_id  = $this->create_phase( array( 'post_status' => 'draft' ) );

		$result = CALC::check_phases_options(
			array( $good_id, $bad_id ),
			array( 'all_must_pass' => true )
		);

		$this->assertFalse( $result['valid'] );
		$this->assertContains( $good_id, $result['passed_ids'] );
		$this->assertContains( $bad_id, $result['failed_ids'] );
	}

	/**
	 * Test all_must_pass=true: valid when every phase passes.
	 *
	 * @return void
	 */
	public function test_all_must_pass_true_passes_when_all_pass() {
		$phase1 = $this->create_phase();
		$phase2 = $this->create_phase();

		$result = CALC::check_phases_options(
			array( $phase1, $phase2 ),
			array( 'all_must_pass' => true )
		);

		$this->assertTrue( $result['valid'] );
		$this->assertCount( 2, $result['passed_ids'] );
		$this->assertEmpty( $result['failed_ids'] );
	}

	/**
	 * Test all_must_pass=false: valid when at least one phase passes.
	 *
	 * @return void
	 */
	public function test_all_must_pass_false_valid_when_one_passes() {
		$good_id = $this->create_phase( array( 'post_status' => 'publish' ) );
		$bad_id  = $this->create_phase( array( 'post_status' => 'draft' ) );

		$result = CALC::check_phases_options(
			array( $good_id, $bad_id ),
			array( 'all_must_pass' => false )
		);

		$this->assertTrue( $result['valid'] );
		$this->assertContains( $good_id, $result['passed_ids'] );
		$this->assertContains( $bad_id, $result['failed_ids'] );
	}

	/**
	 * Test all_must_pass=false: invalid only when no phase passes.
	 *
	 * @return void
	 */
	public function test_all_must_pass_false_invalid_when_none_pass() {
		$bad1 = $this->create_phase( array( 'post_status' => 'draft' ) );
		$bad2 = $this->create_phase( array( 'post_status' => 'draft' ) );

		$result = CALC::check_phases_options(
			array( $bad1, $bad2 ),
			array( 'all_must_pass' => false )
		);

		$this->assertFalse( $result['valid'] );
		$this->assertEmpty( $result['passed_ids'] );
	}

	// -------------------------------------------------------------------------
	// Details structure
	// -------------------------------------------------------------------------

	/**
	 * Test details array contains an entry for every queried phase.
	 *
	 * @return void
	 */
	public function test_details_contains_entry_for_every_phase() {
		$phase1 = $this->create_phase();
		$phase2 = $this->create_phase( array( 'post_status' => 'draft' ) );

		$result = CALC::check_phases_options( array( $phase1, $phase2 ) );

		$this->assertArrayHasKey( $phase1, $result['details'] );
		$this->assertArrayHasKey( $phase2, $result['details'] );
	}

	/**
	 * Test each detail entry has 'passed' and 'reasons' keys.
	 *
	 * @return void
	 */
	public function test_detail_entry_structure() {
		$phase_id = $this->create_phase();

		$result = CALC::check_phases_options( array( $phase_id ) );

		$detail = $result['details'][ $phase_id ];
		$this->assertArrayHasKey( 'passed', $detail );
		$this->assertArrayHasKey( 'reasons', $detail );
		$this->assertIsBool( $detail['passed'] );
		$this->assertIsArray( $detail['reasons'] );
	}

	/**
	 * Test passing phase has an empty reasons array.
	 *
	 * @return void
	 */
	public function test_passing_phase_has_no_reasons() {
		$phase_id = $this->create_phase();

		$result = CALC::check_phases_options( array( $phase_id ) );

		$this->assertTrue( $result['details'][ $phase_id ]['passed'] );
		$this->assertEmpty( $result['details'][ $phase_id ]['reasons'] );
	}

	// -------------------------------------------------------------------------
	// Combined conditions
	// -------------------------------------------------------------------------

	/**
	 * Test phase passes when all combined conditions are met.
	 *
	 * @return void
	 */
	public function test_combined_conditions_all_pass() {
		$parent_id = $this->create_phase();
		$phase_id  = $this->create_phase( array( 'post_parent' => $parent_id ) );
		update_post_meta( $phase_id, 'pbc_phase_active', 'yes' );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array(
				'published'       => true,
				'parent'          => $parent_id,
				'meta_conditions' => array( 'pbc_phase_active' => 'yes' ),
			)
		);

		$this->assertTrue( $result['valid'] );
	}

	/**
	 * Test phase fails when parent matches but meta condition fails.
	 *
	 * @return void
	 */
	public function test_combined_conditions_meta_fails_overrides_parent_pass() {
		$parent_id = $this->create_phase();
		$phase_id  = $this->create_phase( array( 'post_parent' => $parent_id ) );
		update_post_meta( $phase_id, 'pbc_phase_active', 'no' );

		$result = CALC::check_phases_options(
			array( $phase_id ),
			array(
				'parent'          => $parent_id,
				'meta_conditions' => array( 'pbc_phase_active' => 'yes' ),
			)
		);

		$this->assertFalse( $result['valid'] );
		$this->assertContains( $phase_id, $result['failed_ids'] );
	}
}
