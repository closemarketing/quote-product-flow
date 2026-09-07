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

	/**
	 * Test save_phase_meta persists qpfw_legacy_style as 0 when the checkbox is absent.
	 *
	 * @return void
	 */
	public function test_save_phase_meta_persists_legacy_style_as_0_when_unchecked() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'qpfw_phases' ) );

		$_POST['qpfw_phase_nonce'] = wp_create_nonce( 'qpfw_phase_save' );
		unset( $_POST['qpfw_legacy_style'] );

		$this->helper->save_phase_meta( $post_id, get_post( $post_id ) );

		$this->assertSame( '0', get_post_meta( $post_id, 'qpfw_legacy_style', true ) );
	}

	/**
	 * Test variations_content wizard output omits the choice-row/choice-label
	 * classes when legacy_style is true, but keeps them when legacy_style is false.
	 *
	 * @return void
	 */
	public function test_variations_content_omits_choice_row_classes_when_legacy_style() {
		$variations_section = array(
			array(
				'id'      => 0,
				'section' => 'Options',
				'title'   => 'Choice A',
			),
		);

		ob_start();
		SHOW::variations_content( $variations_section, 0, 1, 'wizard', false, true );
		$legacy_output = ob_get_clean();

		ob_start();
		SHOW::variations_content( $variations_section, 0, 1, 'wizard', false, false );
		$normal_output = ob_get_clean();

		$this->assertStringNotContainsString( 'qpfw-choice-row', $legacy_output, 'Legacy style output should not have the choice-row class' );
		$this->assertStringNotContainsString( 'qpfw-choice-label', $legacy_output, 'Legacy style output should not have the choice-label class' );

		$this->assertStringContainsString( 'qpfw-choice-row', $normal_output, 'Non-legacy output should have the choice-row class' );
		$this->assertStringContainsString( 'qpfw-choice-label', $normal_output, 'Non-legacy output should have the choice-label class' );
	}

	/**
	 * Test variations_content renders the variation icon image wrapper one
	 * extra time in legacy style.
	 *
	 * The markup contains two possible spots for the "variation_img" wrapper:
	 * one gated by `$imgicon && ! $is_choice_row` right before the option
	 * label, and one inside the option card that is always rendered when the
	 * variation has an icon. Legacy style forces $is_choice_row to false, so
	 * the gated wrapper additionally appears, for a total of two occurrences
	 * instead of one.
	 *
	 * @return void
	 */
	public function test_variations_content_renders_extra_icon_wrapper_in_legacy_style() {
		$variation_id = $this->factory->post->create( array( 'post_type' => 'qpfw_variation' ) );
		update_post_meta( $variation_id, 'qpfw_imgicon', '999999' );

		$variations_section = array(
			array(
				'id'      => $variation_id,
				'section' => 'Options',
				'title'   => 'Choice A',
			),
		);

		ob_start();
		SHOW::variations_content( $variations_section, 0, 1, 'wizard', false, true );
		$legacy_output = ob_get_clean();

		ob_start();
		SHOW::variations_content( $variations_section, 0, 1, 'wizard', false, false );
		$normal_output = ob_get_clean();

		$this->assertSame( 2, substr_count( $legacy_output, 'variation_img' ), 'Legacy style should render the icon image wrapper twice (gated + card)' );
		$this->assertSame( 1, substr_count( $normal_output, 'variation_img' ), 'Non-legacy (choice-row) style should render the icon image wrapper only once (card)' );
	}
}
