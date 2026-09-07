<?php
/**
 * Class VariationDependsSaveTest
 *
 * Tests for HelperPostTypes::save_variation_meta() persisting qpfw_depends.
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\HelperPostTypes;
use ReflectionClass;
use WP_UnitTestCase;

/**
 * Test case for HelperPostTypes::save_variation_meta().
 */
class VariationDependsSaveTest extends WP_UnitTestCase {
	/**
	 * Instance of HelperPostTypes built without running its constructor,
	 * so we can call save_variation_meta() directly without also
	 * registering its WordPress hooks.
	 *
	 * @var HelperPostTypes
	 */
	private $helper;

	/**
	 * Sets up the HelperPostTypes instance for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$reflection   = new ReflectionClass( HelperPostTypes::class );
		$this->helper = $reflection->newInstanceWithoutConstructor();

		// Default to an administrator so edit_post capability checks pass;
		// individual tests can switch to a lower-privileged user.
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
	 * Test save_variation_meta persists a "title:..." depvar row verbatim.
	 *
	 * @return void
	 */
	public function test_save_variation_meta_persists_title_depvar_verbatim() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );

		$_POST['qpfw_variation_nonce'] = wp_create_nonce( 'qpfw_variation_save' );
		$_POST['qpfw_depends']         = array(
			array( 'qpfw_depvar' => 'title:130x150' ),
		);

		$this->helper->save_variation_meta( $post_id, get_post( $post_id ) );

		$this->assertSame(
			array( array( 'qpfw_depvar' => 'title:130x150' ) ),
			get_post_meta( $post_id, 'qpfw_depends', true )
		);
	}

	/**
	 * Test save_variation_meta persists a legacy "order|id" depvar row verbatim.
	 *
	 * @return void
	 */
	public function test_save_variation_meta_persists_legacy_depvar_verbatim() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );

		$_POST['qpfw_variation_nonce'] = wp_create_nonce( 'qpfw_variation_save' );
		$_POST['qpfw_depends']         = array(
			array( 'qpfw_depvar' => '2|55' ),
		);

		$this->helper->save_variation_meta( $post_id, get_post( $post_id ) );

		$this->assertSame(
			array( array( 'qpfw_depvar' => '2|55' ) ),
			get_post_meta( $post_id, 'qpfw_depends', true )
		);
	}

	/**
	 * Test save_variation_meta does nothing when the nonce is missing or invalid.
	 *
	 * @return void
	 */
	public function test_save_variation_meta_does_nothing_without_valid_nonce() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );

		$_POST['qpfw_variation_nonce'] = 'not-a-valid-nonce';
		$_POST['qpfw_depends']         = array(
			array( 'qpfw_depvar' => 'title:130x150' ),
		);

		$this->helper->save_variation_meta( $post_id, get_post( $post_id ) );

		$this->assertSame( '', get_post_meta( $post_id, 'qpfw_depends', true ) );
	}

	/**
	 * Test save_variation_meta does nothing for a user without edit_post capability.
	 *
	 * @return void
	 */
	public function test_save_variation_meta_does_nothing_without_edit_capability() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );

		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$_POST['qpfw_variation_nonce'] = wp_create_nonce( 'qpfw_variation_save' );
		$_POST['qpfw_depends']         = array(
			array( 'qpfw_depvar' => 'title:130x150' ),
		);

		$this->helper->save_variation_meta( $post_id, get_post( $post_id ) );

		$this->assertSame( '', get_post_meta( $post_id, 'qpfw_depends', true ) );
	}
}
