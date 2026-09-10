<?php
/**
 * Class SvgSupportTest
 *
 * Tests for SvgSupport: SVG upload sanitization (XSS prevention).
 *
 * @package Product_Budget_Configurator
 */

namespace CLOSE\QProductFlow\Tests\Unit;

use CLOSE\QProductFlow\SvgSupport;
use ReflectionClass;
use ReflectionMethod;
use WP_UnitTestCase;

/**
 * Test case for SvgSupport.
 */
class SvgSupportTest extends WP_UnitTestCase {
	/**
	 * Instance built without running the constructor, so we can call
	 * methods directly without also registering WordPress hooks.
	 *
	 * @var SvgSupport
	 */
	private $svg_support;

	/**
	 * Sets up the SvgSupport instance for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$reflection        = new ReflectionClass( SvgSupport::class );
		$this->svg_support = $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Invokes the private sanitize_svg() method via reflection.
	 *
	 * @param string $svg Raw SVG content.
	 * @return string|false
	 */
	private function sanitize( $svg ) {
		$method = new ReflectionMethod( SvgSupport::class, 'sanitize_svg' );
		$method->setAccessible( true );

		return $method->invoke( $this->svg_support, $svg );
	}

	/**
	 * Test add_svg_mime_type adds the svg mime type when not already present.
	 *
	 * @return void
	 */
	public function test_add_svg_mime_type_adds_svg() {
		$mimes = $this->svg_support->add_svg_mime_type( array( 'jpg' => 'image/jpeg' ) );

		$this->assertSame( 'image/svg+xml', $mimes['svg'] );
	}

	/**
	 * Test add_svg_mime_type does not override an existing svg entry.
	 *
	 * @return void
	 */
	public function test_add_svg_mime_type_does_not_override_existing_entry() {
		$mimes = $this->svg_support->add_svg_mime_type( array( 'svg' => 'custom/svg-type' ) );

		$this->assertSame( 'custom/svg-type', $mimes['svg'] );
	}

	/**
	 * Test allow_svg_extension sets type/ext for a .svg filename.
	 *
	 * @return void
	 */
	public function test_allow_svg_extension_recognizes_svg_filename() {
		$data = $this->svg_support->allow_svg_extension( array(), '/tmp/file.svg', 'icon.svg', array() );

		$this->assertSame( 'image/svg+xml', $data['type'] );
		$this->assertSame( 'svg', $data['ext'] );
	}

	/**
	 * Test allow_svg_extension leaves non-svg filenames untouched.
	 *
	 * @return void
	 */
	public function test_allow_svg_extension_ignores_other_extensions() {
		$original = array(
			'type' => 'image/png',
			'ext'  => 'png',
		);

		$data = $this->svg_support->allow_svg_extension( $original, '/tmp/file.png', 'icon.png', array() );

		$this->assertSame( $original, $data );
	}

	/**
	 * Test sanitize_svg strips <script> elements.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_removes_script_tag() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><circle r="5"/></svg>';

		$result = $this->sanitize( $svg );

		$this->assertStringNotContainsString( '<script', $result );
		$this->assertStringNotContainsString( 'alert(1)', $result );
		$this->assertStringContainsString( '<circle', $result );
	}

	/**
	 * Test sanitize_svg strips <style> elements.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_removes_style_tag() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><style>body{background:url(evil.com)}</style><rect/></svg>';

		$result = $this->sanitize( $svg );

		$this->assertStringNotContainsString( '<style', $result );
	}

	/**
	 * Test sanitize_svg strips <foreignObject> elements.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_removes_foreign_object_tag() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div>x</div></foreignObject></svg>';

		$result = $this->sanitize( $svg );

		$this->assertStringNotContainsString( 'foreignObject', $result );
	}

	/**
	 * Test sanitize_svg removes on* event handler attributes.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_removes_event_handler_attributes() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect onclick="alert(1)" onload="alert(2)" width="10"/></svg>';

		$result = $this->sanitize( $svg );

		$this->assertStringNotContainsString( 'onclick', $result );
		$this->assertStringNotContainsString( 'onload', $result );
		$this->assertStringContainsString( 'width="10"', $result );
	}

	/**
	 * Test sanitize_svg removes javascript: URLs.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_removes_javascript_url() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><rect/></a></svg>';

		$result = $this->sanitize( $svg );

		$this->assertStringNotContainsString( 'javascript:', $result );
	}

	/**
	 * Test sanitize_svg removes vbscript: URLs.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_removes_vbscript_url() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><a href="vbscript:msgbox(1)"><rect/></a></svg>';

		$result = $this->sanitize( $svg );

		$this->assertStringNotContainsString( 'vbscript:', $result );
	}

	/**
	 * Test sanitize_svg removes non-image data: URLs but keeps data:image/.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_removes_non_image_data_url_but_keeps_data_image() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg">'
			. '<a href="data:text/html,evil"><rect id="bad"/></a>'
			. '<image xlink:href="data:image/png;base64,AAAA" id="good"/>'
			. '</svg>';

		$result = $this->sanitize( $svg );

		$this->assertStringNotContainsString( 'data:text/html', $result );
		$this->assertStringContainsString( 'data:image/png;base64,AAAA', $result );
	}

	/**
	 * Test sanitize_svg removes url()/expression() inside a style attribute.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_removes_dangerous_style_attribute() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:url(evil.com)" width="10"/></svg>';

		$result = $this->sanitize( $svg );

		$this->assertStringNotContainsString( 'style=', $result );
		$this->assertStringContainsString( 'width="10"', $result );
	}

	/**
	 * Test sanitize_svg returns false for invalid/corrupted XML input.
	 *
	 * @return void
	 */
	public function test_sanitize_svg_returns_false_for_invalid_xml() {
		$result = $this->sanitize( '<svg><unclosed>' );

		$this->assertFalse( $result );
	}

	/**
	 * Test prepare_svg_for_media_library sets a plain image src for SVG attachments.
	 *
	 * @return void
	 */
	public function test_prepare_svg_for_media_library_sets_image_src() {
		$response = $this->svg_support->prepare_svg_for_media_library(
			array(
				'mime' => 'image/svg+xml',
				'url'  => 'https://example.org/icon.svg',
			)
		);

		$this->assertSame( array( 'src' => 'https://example.org/icon.svg' ), $response['image'] );
	}

	/**
	 * Test prepare_svg_for_media_library leaves non-SVG attachments untouched.
	 *
	 * @return void
	 */
	public function test_prepare_svg_for_media_library_ignores_non_svg() {
		$original = array(
			'mime' => 'image/png',
			'url'  => 'https://example.org/photo.png',
		);

		$response = $this->svg_support->prepare_svg_for_media_library( $original );

		$this->assertSame( $original, $response );
	}
}
