<?php
/**
 * Class RenderDependRowTest
 *
 * Tests for the private HelperPostTypes::render_depend_row() markup, exercised
 * through reflection since the method itself is not public.
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\HelperPostTypes;
use ReflectionClass;
use ReflectionMethod;
use WP_UnitTestCase;

/**
 * Test case for HelperPostTypes::render_depend_row().
 */
class RenderDependRowTest extends WP_UnitTestCase {
	/**
	 * Invokes the private render_depend_row() method via reflection.
	 *
	 * @param string $depvar      Raw qpfw_depvar value.
	 * @param bool   $is_title    Whether the row is in "by title" mode.
	 * @param string $title_value Pre-filled title value.
	 * @param array  $var_options Options for the specific-variation select.
	 * @return string Rendered HTML markup for the row.
	 */
	private function render_row( $depvar, $is_title, $title_value, $var_options = array() ) {
		$reflection_class = new ReflectionClass( HelperPostTypes::class );
		$helper           = $reflection_class->newInstanceWithoutConstructor();

		$method = new ReflectionMethod( HelperPostTypes::class, 'render_depend_row' );
		$method->setAccessible( true );

		return $method->invoke( $helper, $depvar, $is_title, $title_value, $var_options );
	}

	/**
	 * Test render_depend_row shows an enabled, pre-filled title input and hides
	 * the disabled specific-variation select when in "by title" mode.
	 *
	 * @return void
	 */
	public function test_render_depend_row_title_mode_shows_visible_title_input() {
		$html = $this->render_row( 'title:130x150', true, '130x150' );

		$this->assertStringContainsString(
			'class="qpfw-dep-title widefat" style="flex:1;min-width:0;" value="130x150"',
			$html,
			'Title input should be visible/enabled and pre-filled with the title value'
		);
		$this->assertStringContainsString(
			'class="qpfw-dep-id" style="flex:1;min-width:0;display:none;" disabled',
			$html,
			'Specific-variation select should be hidden and disabled in title mode'
		);
	}

	/**
	 * Test render_depend_row shows an enabled specific-variation select and
	 * hides the disabled title input when in "specific variation" mode.
	 *
	 * @return void
	 */
	public function test_render_depend_row_id_mode_shows_visible_variation_select() {
		$html = $this->render_row( '55', false, '', array( '55' => 'Some Variation' ) );

		$this->assertStringContainsString(
			'class="qpfw-dep-id" style="flex:1;min-width:0;">',
			$html,
			'Specific-variation select should be visible and enabled in id mode'
		);
		$this->assertStringContainsString(
			'class="qpfw-dep-title widefat" style="flex:1;min-width:0;display:none;" disabled value=""',
			$html,
			'Title input should be hidden and disabled in id mode'
		);
		$this->assertStringContainsString( 'Some Variation', $html, 'The specific-variation options should be rendered' );
	}
}
