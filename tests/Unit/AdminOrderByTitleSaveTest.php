<?php
/**
 * Class AdminOrderByTitleSaveTest
 *
 * Tests for the private AdminPlugin::save_post_options() persisting the
 * global qpfw_order_by_title option, exercised through reflection.
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\AdminPlugin;
use ReflectionClass;
use ReflectionMethod;
use WP_UnitTestCase;

/**
 * Test case for AdminPlugin::save_post_options() order-by-title handling.
 */
class AdminOrderByTitleSaveTest extends WP_UnitTestCase {
	/**
	 * Invokes the private save_post_options() method via reflection.
	 *
	 * @return mixed Whatever save_post_options() returns.
	 */
	private function call_save_post_options() {
		$reflection_class = new ReflectionClass( AdminPlugin::class );
		$admin            = $reflection_class->newInstanceWithoutConstructor();

		$method = new ReflectionMethod( AdminPlugin::class, 'save_post_options' );
		$method->setAccessible( true );

		return $method->invoke( $admin );
	}

	/**
	 * Resets superglobals and the option after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_POST = array();
		delete_option( 'qpfw_order_by_title' );
		parent::tear_down();
	}

	/**
	 * Test save_post_options persists valid order-by-title rows and skips a
	 * row with a blank title.
	 *
	 * @return void
	 */
	public function test_save_post_options_persists_order_by_title_rows() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$_POST['form_submit'] = 'true';
		$_POST['qpfw_nonce']  = wp_create_nonce( 'qpfw_nonce' );
		$_POST['qpfw_order_by_title'] = array(
			array(
				'qpfw_order_title' => 'Medida pequeña',
				'qpfw_order_value' => '1',
			),
			array(
				'qpfw_order_title' => '  ',
				'qpfw_order_value' => '2',
			),
			array(
				'qpfw_order_title' => 'Medida grande',
				'qpfw_order_value' => '5',
			),
		);

		$this->call_save_post_options();

		$this->assertSame(
			array(
				array(
					'qpfw_order_title' => 'Medida pequeña',
					'qpfw_order_value' => 1,
				),
				array(
					'qpfw_order_title' => 'Medida grande',
					'qpfw_order_value' => 5,
				),
			),
			get_option( 'qpfw_order_by_title' ),
			'Blank-title rows must be skipped and values cast to int'
		);
	}

	/**
	 * Test save_post_options does nothing for a user without manage_options
	 * capability, even with an otherwise valid submission.
	 *
	 * @return void
	 */
	public function test_save_post_options_requires_manage_options_capability() {
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$_POST['form_submit'] = 'true';
		$_POST['qpfw_nonce']  = wp_create_nonce( 'qpfw_nonce' );
		$_POST['qpfw_order_by_title'] = array(
			array(
				'qpfw_order_title' => 'Medida pequeña',
				'qpfw_order_value' => '1',
			),
		);

		$this->call_save_post_options();

		$this->assertFalse( get_option( 'qpfw_order_by_title' ), 'Option must not be created without manage_options capability' );
	}
}
