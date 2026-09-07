<?php
/**
 * Class LegacyPhaseStyleTest
 *
 * Tests for the "legacy phase style" feature: HelperPostTypes::save_phase_meta()
 * and SHOW::variations_content().
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\HelperPostTypes;
use CLOSE\QProductFlow\Helpers\SHOW;
use ReflectionClass;
use WP_UnitTestCase;

/**
 * Test case for the legacy phase style feature.
 */
class LegacyPhaseStyleTest extends WP_UnitTestCase {
	/**
	 * Instance of HelperPostTypes built without running its constructor.
	 *
	 * @var HelperPostTypes
	 */
	private $helper;

	/**
	 * Sets up the HelperPostTypes instance and an administrator user.
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
	 * Test save_phase_meta persists qpfw_legacy_style as 1 when the checkbox is posted.
	 *
	 * @return void
	 */
	public function test_save_phase_meta_persists_legacy_style_as_1_when_checked() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_phases' ) );

		$_POST['qpfw_phase_nonce']   = wp_create_nonce( 'qpfw_phase_save' );
		$_POST['qpfw_legacy_style']  = '1';

		$this->helper->save_phase_meta( $post_id, get_post( $post_id ) );

		$this->assertSame( '1', get_post_meta( $post_id, 'qpfw_legacy_style', true ) );
	}

}
