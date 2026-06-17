<?php
/**
 * Class CalcTest
 *
 * Tests for the CALC helper class
 *
 * @package Product_Budget_Configurator
 */

namespace Close\PBC\Tests\Unit;

use Close\PBC\Helpers\CALC;
use WP_UnitTestCase;

/**
 * Test case for CALC helper class.
 */
class CalcTest extends WP_UnitTestCase {

	/**
	 * Test calculate_color_text with dark background.
	 *
	 * @return void
	 */
	public function test_calculate_color_text_with_dark_background() {
		$result = CALC::calculate_color_text( '#000000' );
		$this->assertEquals( '#ffffff', $result, 'Dark background should return white text' );
	}

	/**
	 * Test calculate_color_text with light background.
	 *
	 * @return void
	 */
	public function test_calculate_color_text_with_light_background() {
		$result = CALC::calculate_color_text( '#ffffff' );
		$this->assertEquals( '#000000', $result, 'Light background should return black text' );
	}

	/**
	 * Test calculate_color_text with medium background.
	 *
	 * @return void
	 */
	public function test_calculate_color_text_with_medium_background() {
		$result = CALC::calculate_color_text( '#808080' );
		$this->assertContains( $result, array( '#000000', '#ffffff' ), 'Medium background should return either black or white' );
	}

	/**
	 * Test adjust_brightness makes color lighter.
	 *
	 * @return void
	 */
	public function test_adjust_brightness_lighter() {
		$original = '#808080';
		$lighter  = CALC::adjust_brightness( $original, 50 );

		$this->assertNotEquals( $original, $lighter, 'Adjusted color should be different' );
		$this->assertMatchesRegularExpression( '/^#[0-9A-F]{6}$/i', $lighter, 'Result should be valid hex color' );
	}

	/**
	 * Test adjust_brightness makes color darker.
	 *
	 * @return void
	 */
	public function test_adjust_brightness_darker() {
		$original = '#808080';
		$darker   = CALC::adjust_brightness( $original, -50 );

		$this->assertNotEquals( $original, $darker, 'Adjusted color should be different' );
		$this->assertMatchesRegularExpression( '/^#[0-9A-F]{6}$/i', $darker, 'Result should be valid hex color' );
	}

	/**
	 * Test get_show_prices_for_user with no role returns global setting.
	 *
	 * @return void
	 */
	public function test_get_show_prices_for_user_no_role() {
		// Set global setting to yes.
		update_option( 'pbc_show_prices_global', 'yes' );

		$result = CALC::get_show_prices_for_user( '' );
		$this->assertEquals( 'yes', $result, 'Should return global setting when no role provided' );

		// Clean up.
		delete_option( 'pbc_show_prices_global' );
	}

	/**
	 * Test get_show_prices_for_user with role specific setting.
	 *
	 * @return void
	 */
	public function test_get_show_prices_for_user_with_role_setting() {
		// Set global to no but specific role to yes.
		update_option( 'pbc_show_prices_global', 'no' );
		update_option( 'pbc_show_prices_user_administrator', 'yes' );

		$result = CALC::get_show_prices_for_user( 'administrator' );
		$this->assertEquals( 'yes', $result, 'Should return role specific setting over global' );

		// Clean up.
		delete_option( 'pbc_show_prices_global' );
		delete_option( 'pbc_show_prices_user_administrator' );
	}

	/**
	 * Test get_show_prices_for_user falls back to global when role has no setting.
	 *
	 * @return void
	 */
	public function test_get_show_prices_for_user_fallback_to_global() {
		// Set only global setting.
		update_option( 'pbc_show_prices_global', 'yes' );

		$result = CALC::get_show_prices_for_user( 'subscriber' );
		$this->assertEquals( 'yes', $result, 'Should fall back to global when role has no specific setting' );

		// Clean up.
		delete_option( 'pbc_show_prices_global' );
	}

	/**
	 * Test get_show_prices_for_user defaults to yes when no settings exist.
	 *
	 * @return void
	 */
	public function test_get_show_prices_for_user_default_yes() {
		// Make sure no settings exist.
		delete_option( 'pbc_show_prices_global' );

		$result = CALC::get_show_prices_for_user( '' );
		$this->assertEquals( 'yes', $result, 'Should default to yes when no settings exist' );
	}

	/**
	 * Test get_total_from_enquiry with valid post.
	 *
	 * @return void
	 */
	public function test_get_total_from_enquiry() {
		// Create a test post.
		$post_id = $this->factory->post->create();

		// Add some price meta fields.
		add_post_meta( $post_id, 'pbc_price_1', '100.50' );
		add_post_meta( $post_id, 'pbc_price_2', '50,25' );
		add_post_meta( $post_id, 'pbc_price_3', '25.00' );

		$total = CALC::get_total_from_enquiry( $post_id );

		$this->assertEquals( 175.75, $total, 'Should calculate correct total from price meta fields' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_total_from_enquiry with no prices.
	 *
	 * @return void
	 */
	public function test_get_total_from_enquiry_no_prices() {
		$post_id = $this->factory->post->create();

		$total = CALC::get_total_from_enquiry( $post_id );

		$this->assertEquals( 0, $total, 'Should return 0 when no price meta fields exist' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test adds_zero pads single-digit numbers with a leading zero.
	 *
	 * @return void
	 */
	public function test_adds_zero_with_single_digit() {
		$this->assertEquals( '05', CALC::adds_zero( 5 ) );
	}

	/**
	 * Test adds_zero leaves double-digit numbers unchanged.
	 *
	 * @return void
	 */
	public function test_adds_zero_with_double_digit() {
		$this->assertEquals( 10, CALC::adds_zero( 10 ) );
	}

	/**
	 * Test adds_zero with zero value.
	 *
	 * @return void
	 */
	public function test_adds_zero_with_zero() {
		$this->assertEquals( '00', CALC::adds_zero( 0 ) );
	}

	/**
	 * Test get_user_discount_and_role returns empty data when no user is logged in.
	 *
	 * @return void
	 */
	public function test_get_user_discount_and_role_no_logged_in_user() {
		wp_set_current_user( 0 );

		$result = CALC::get_user_discount_and_role();

		$this->assertEquals( '', $result['role'] );
		$this->assertEquals( 0, $result['discount'] );
	}

	/**
	 * Test get_user_discount_and_role returns role and discount when set.
	 *
	 * @return void
	 */
	public function test_get_user_discount_and_role_with_discount() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		update_option( 'pbc_discount_user_subscriber', 20 );

		$result = CALC::get_user_discount_and_role();

		$this->assertEquals( 'subscriber', $result['role'] );
		$this->assertEquals( 20, $result['discount'] );

		delete_option( 'pbc_discount_user_subscriber' );
		wp_set_current_user( 0 );
	}

	/**
	 * Test get_user_discount_and_role returns empty data when role has no discount configured.
	 *
	 * @return void
	 */
	public function test_get_user_discount_and_role_no_discount_set() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		delete_option( 'pbc_discount_user_subscriber' );

		$result = CALC::get_user_discount_and_role();

		$this->assertEquals( '', $result['role'] );
		$this->assertEquals( 0, $result['discount'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Test get_price_variation returns 0 when no pricegroup meta exists.
	 *
	 * @return void
	 */
	public function test_get_price_variation_no_pricegroup_returns_zero() {
		wp_set_current_user( 0 );
		$post_id = $this->factory->post->create( array( 'post_type' => 'variation' ) );

		$price = CALC::get_price_variation( $post_id, '' );

		$this->assertEquals( 0.0, $price );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_price_variation returns base price from first pricegroup entry.
	 *
	 * @return void
	 */
	public function test_get_price_variation_returns_base_price() {
		wp_set_current_user( 0 );
		$post_id = $this->factory->post->create( array( 'post_type' => 'variation' ) );
		update_post_meta(
			$post_id,
			'pbc_pricegroup',
			array(
				array(
					'pbc_pricem'   => '150.00',
					'pbc_meaprice' => 'unit',
				),
			)
		);

		$price = CALC::get_price_variation( $post_id, 'nonexistent' );

		$this->assertEquals( 150.0, $price );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_price_variation applies role discount to base price.
	 *
	 * @return void
	 */
	public function test_get_price_variation_applies_role_discount() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		update_option( 'pbc_discount_user_subscriber', 10 );

		$post_id = $this->factory->post->create( array( 'post_type' => 'variation' ) );
		update_post_meta(
			$post_id,
			'pbc_pricegroup',
			array(
				array(
					'pbc_pricem'   => '200.00',
					'pbc_meaprice' => 'unit',
				),
			)
		);

		$price = CALC::get_price_variation( $post_id, 'nonexistent' );

		// 200 - 10% = 180.
		$this->assertEquals( 180.0, $price );

		delete_option( 'pbc_discount_user_subscriber' );
		wp_delete_post( $post_id, true );
		wp_set_current_user( 0 );
	}

	/**
	 * Test get_price_variation: no discount applied when user has no role discount.
	 *
	 * @return void
	 */
	public function test_get_price_variation_no_discount_when_not_set() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		delete_option( 'pbc_discount_user_subscriber' );

		$post_id = $this->factory->post->create( array( 'post_type' => 'variation' ) );
		update_post_meta(
			$post_id,
			'pbc_pricegroup',
			array(
				array(
					'pbc_pricem'   => '100.00',
					'pbc_meaprice' => 'unit',
				),
			)
		);

		$price = CALC::get_price_variation( $post_id, 'nonexistent' );

		$this->assertEquals( 100.0, $price );

		wp_delete_post( $post_id, true );
		wp_set_current_user( 0 );
	}

	/**
	 * Test is_multiple_products returns false when no phases exist.
	 *
	 * @return void
	 */
	public function test_is_multiple_products_no_phases() {
		$result = CALC::is_multiple_products();
		$this->assertFalse( $result );
	}

	/**
	 * Test is_multiple_products returns false when all phases are top-level.
	 *
	 * @return void
	 */
	public function test_is_multiple_products_flat_hierarchy() {
		$phase1 = $this->factory->post->create( array( 'post_type' => 'phases', 'post_parent' => 0 ) );
		$phase2 = $this->factory->post->create( array( 'post_type' => 'phases', 'post_parent' => 0 ) );

		$result = CALC::is_multiple_products();

		$this->assertFalse( $result );

		wp_delete_post( $phase1, true );
		wp_delete_post( $phase2, true );
	}

	/**
	 * Test is_multiple_products returns true when child phases exist.
	 *
	 * @return void
	 */
	public function test_is_multiple_products_with_nested_phases() {
		$parent = $this->factory->post->create( array( 'post_type' => 'phases', 'post_parent' => 0 ) );
		$child  = $this->factory->post->create( array( 'post_type' => 'phases', 'post_parent' => $parent ) );

		$result = CALC::is_multiple_products();

		$this->assertTrue( $result );

		wp_delete_post( $child, true );
		wp_delete_post( $parent, true );
	}

	/**
	 * Test get_default_parent_phase returns 0 when no phases exist.
	 *
	 * @return void
	 */
	public function test_get_default_parent_phase_no_phases() {
		$result = CALC::get_default_parent_phase();
		$this->assertEquals( 0, $result );
	}

	/**
	 * Test get_default_parent_phase returns the first top-level phase ID.
	 *
	 * @return void
	 */
	public function test_get_default_parent_phase_returns_top_level_phase() {
		$phase_id = $this->factory->post->create( array( 'post_type' => 'phases', 'post_parent' => 0 ) );

		$result = CALC::get_default_parent_phase();

		$this->assertEquals( $phase_id, $result );

		wp_delete_post( $phase_id, true );
	}

	/**
	 * Test configurator_save_enquiry creates an enquiry post with correct meta.
	 *
	 * @return void
	 */
	public function test_configurator_save_enquiry_creates_post() {
		$session_key = 'pbc_variation_test1';
		$item        = array(
			'pbc_contact'      => array(
				'email_field'    => 'test@example.com',
				'name_field'     => 'Test User',
				'phone_field'    => '123456789',
				'city_field'     => 'Barcelona',
				'state_field'    => 'Catalonia',
				'comments_field' => 'Test comment',
			),
			'pbc_session_key'  => $session_key,
			'pbc_parent_phase' => 0,
			$session_key       => array(
				1 => array(
					'phase' => array( 'name' => 'Phase One' ),
					'var'   => array( 'name' => 'Option A', 'price' => 100.00, 'type' => 'price' ),
				),
			),
		);

		$post_id = CALC::configurator_save_enquiry( $item );

		$this->assertGreaterThan( 0, $post_id, 'Should return a valid post ID' );
		$this->assertEquals( 'enquiry', get_post_type( $post_id ) );
		$this->assertEquals( 'test@example.com', get_post_meta( $post_id, 'pbc_enquiry_email', true ) );
		$this->assertEquals( 'Test User', get_post_meta( $post_id, 'pbc_enquiry_name', true ) );
		$this->assertEquals( '123456789', get_post_meta( $post_id, 'pbc_enquiry_phone', true ) );
		$this->assertEquals( 'Phase One', get_post_meta( $post_id, 'pbc_phase_name_0', true ) );
		$this->assertEquals( 'Option A', get_post_meta( $post_id, 'pbc_phase_var_0', true ) );
		$this->assertEquals( 'price', get_post_meta( $post_id, 'pbc_type_0', true ) );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test configurator_save_enquiry stores multiple questions format correctly.
	 *
	 * @return void
	 */
	public function test_configurator_save_enquiry_with_multiple_questions() {
		$session_key = 'pbc_variation_test2';
		$item        = array(
			'pbc_contact'      => array(
				'email_field' => 'q@example.com',
				'name_field'  => 'Question User',
				'phone_field' => '987654321',
			),
			'pbc_session_key'  => $session_key,
			'pbc_parent_phase' => 0,
			$session_key       => array(
				1 => array(
					'phase'     => array( 'name' => 'Questions Phase' ),
					'questions' => array(
						array( 'variation_title' => 'Color', 'answer' => 'Red' ),
						array( 'variation_title' => 'Size', 'answer' => 'Large' ),
					),
				),
			),
		);

		$post_id = CALC::configurator_save_enquiry( $item );

		$this->assertGreaterThan( 0, $post_id );
		$this->assertEquals( 'question', get_post_meta( $post_id, 'pbc_type_0', true ) );
		$this->assertEquals( 'Color: Red', get_post_meta( $post_id, 'pbc_phase_var_0', true ) );
		$this->assertEquals( 'question', get_post_meta( $post_id, 'pbc_type_1', true ) );
		$this->assertEquals( 'Size: Large', get_post_meta( $post_id, 'pbc_phase_var_1', true ) );
		$this->assertEquals( 2, (int) get_post_meta( $post_id, 'pbc_total_var', true ) );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_total_from_enquiry handles comma as decimal separator correctly.
	 *
	 * @return void
	 */
	public function test_get_total_from_enquiry_with_comma_decimal() {
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'pbc_price_0', '1500,75' );
		add_post_meta( $post_id, 'pbc_price_1', '250,00' );

		$total = CALC::get_total_from_enquiry( $post_id );

		// 1500.75 + 250.00 = 1750.75.
		$this->assertEquals( 1750.75, $total );

		wp_delete_post( $post_id, true );
	}
}

