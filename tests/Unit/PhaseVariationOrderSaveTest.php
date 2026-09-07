<?php
/**
 * Class PhaseVariationOrderSaveTest
 *
 * Tests for HelperPostTypes::save_phase_meta() persisting per-variation
 * qpfw_display_order values submitted from the phase edit screen.
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\HelperPostTypes;
use ReflectionClass;
use WP_UnitTestCase;

/**
 * Test case for HelperPostTypes::save_phase_meta() variation order handling.
 */
class PhaseVariationOrderSaveTest extends WP_UnitTestCase {
	/**
	 * Instance of HelperPostTypes built without running its constructor.
	 *
	 * @var HelperPostTypes
	 */
	private $helper;

	/**
	 * Sets up the HelperPostTypes instance and an admin user for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$reflection   = new ReflectionClass( HelperPostTypes::class );
		$this->helper = $reflection->newInstanceWithoutConstructor();

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
	}

	/**
	 * Resets superglobals after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Test save_phase_meta persists qpfw_display_order for a variation that
	 * actually belongs to the phase being saved.
	 *
	 * @return void
	 */
	public function test_save_phase_meta_persists_display_order_for_own_variation() {
		$phase_id      = $this->factory->post->create( array( 'post_type' => 'qpfw_phases' ) );
		$variation_id  = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );
		update_post_meta( $variation_id, 'qpfw_phase', $phase_id );

		$_POST['qpfw_phase_nonce']    = wp_create_nonce( 'qpfw_phase_save' );
		$_POST['qpfw_display_order']  = array( $variation_id => '3' );

		$this->helper->save_phase_meta( $phase_id, get_post( $phase_id ) );

		$this->assertSame( 3, (int) get_post_meta( $variation_id, 'qpfw_display_order', true ) );
	}

	/**
	 * Test save_phase_meta does NOT write qpfw_display_order for a variation
	 * that belongs to a different phase, even if its ID is submitted.
	 *
	 * @return void
	 */
	public function test_save_phase_meta_skips_variation_belonging_to_another_phase() {
		$phase_id       = $this->factory->post->create( array( 'post_type' => 'qpfw_phases' ) );
		$other_phase_id = $this->factory->post->create( array( 'post_type' => 'qpfw_phases' ) );
		$other_variation_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );
		update_post_meta( $other_variation_id, 'qpfw_phase', $other_phase_id );

		$_POST['qpfw_phase_nonce']   = wp_create_nonce( 'qpfw_phase_save' );
		$_POST['qpfw_display_order'] = array( $other_variation_id => '9' );

		$this->helper->save_phase_meta( $phase_id, get_post( $phase_id ) );

		$this->assertSame( '', get_post_meta( $other_variation_id, 'qpfw_display_order', true ), 'A variation belonging to a different phase must not be touched' );
	}

	/**
	 * Test save_phase_meta does nothing when the nonce is missing or invalid.
	 *
	 * @return void
	 */
	public function test_save_phase_meta_does_nothing_without_valid_nonce() {
		$phase_id     = $this->factory->post->create( array( 'post_type' => 'qpfw_phases' ) );
		$variation_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );
		update_post_meta( $variation_id, 'qpfw_phase', $phase_id );

		$_POST['qpfw_phase_nonce']   = 'not-a-valid-nonce';
		$_POST['qpfw_display_order'] = array( $variation_id => '3' );

		$this->helper->save_phase_meta( $phase_id, get_post( $phase_id ) );

		$this->assertSame( '', get_post_meta( $variation_id, 'qpfw_display_order', true ) );
	}
}
