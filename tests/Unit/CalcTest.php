<?php
/**
 * Class CalcTest
 *
 * Tests for the CALC helper class
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\Helpers\CALC;
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
		update_option( 'qpfw_show_prices_global', 'yes' );

		$result = CALC::get_show_prices_for_user( '' );
		$this->assertEquals( 'yes', $result, 'Should return global setting when no role provided' );

		// Clean up.
		delete_option( 'qpfw_show_prices_global' );
	}

	/**
	 * Test get_show_prices_for_user with role specific setting.
	 *
	 * @return void
	 */
	public function test_get_show_prices_for_user_with_role_setting() {
		// Set global to no but specific role to yes.
		update_option( 'qpfw_show_prices_global', 'no' );
		update_option( 'qpfw_show_prices_user_administrator', 'yes' );

		$result = CALC::get_show_prices_for_user( 'administrator' );
		$this->assertEquals( 'yes', $result, 'Should return role specific setting over global' );

		// Clean up.
		delete_option( 'qpfw_show_prices_global' );
		delete_option( 'qpfw_show_prices_user_administrator' );
	}

	/**
	 * Test get_show_prices_for_user falls back to global when role has no setting.
	 *
	 * @return void
	 */
	public function test_get_show_prices_for_user_fallback_to_global() {
		// Set only global setting.
		update_option( 'qpfw_show_prices_global', 'yes' );

		$result = CALC::get_show_prices_for_user( 'subscriber' );
		$this->assertEquals( 'yes', $result, 'Should fall back to global when role has no specific setting' );

		// Clean up.
		delete_option( 'qpfw_show_prices_global' );
	}

	/**
	 * Test get_show_prices_for_user defaults to yes when no settings exist.
	 *
	 * @return void
	 */
	public function test_get_show_prices_for_user_default_yes() {
		// Make sure no settings exist.
		delete_option( 'qpfw_show_prices_global' );

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
		add_post_meta( $post_id, 'qpfw_price_1', '100.50' );
		add_post_meta( $post_id, 'qpfw_price_2', '50,25' );
		add_post_meta( $post_id, 'qpfw_price_3', '25.00' );

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
}

