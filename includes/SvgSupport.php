<?php
/**
 * SVG Upload Support
 *
 * @package    WordPress
 * @author     Closetechnology
 * @copyright  2024 Closemarketing
 * @version    1.0
 */

namespace CLOSE\QProductFlow;

defined( 'ABSPATH' ) || exit;

use DOMDocument;

/**
 * Enable SVG uploads with sanitization for admin
 */
class SvgSupport {
	/**
	 * Construct and initialize hooks
	 */
	public function __construct() {
		add_filter( 'upload_mimes', array( $this, 'add_svg_mime_type' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'allow_svg_extension' ), 10, 4 );
		add_filter( 'wp_handle_upload', array( $this, 'sanitize_svg_upload' ), 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_svg_for_media_library' ) );
	}

	/**
	 * Add SVG to allowed MIME types
	 *
	 * @param array $mimes MIME types.
	 * @return array
	 */
	public function add_svg_mime_type( $mimes ) {
		if ( ! isset( $mimes['svg'] ) ) {
			$mimes['svg'] = 'image/svg+xml';
		}
		return $mimes;
	}

	/**
	 * Allow SVG file extension
	 *
	 * @param array  $data File data.
	 * @param string $file File path.
	 * @param string $filename File name.
	 * @param array  $mimes Allowed MIME types.
	 * @return array
	 */
	public function allow_svg_extension( $data, $file, $filename, $mimes ) {
		if ( 'svg' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$data['type'] = 'image/svg+xml';
			$data['ext']  = 'svg';
		}
		return $data;
	}

	/**
	 * Sanitize SVG file on upload
	 *
	 * @param array  $upload File upload data.
	 * @param string $context Upload context.
	 * @return array
	 */
	public function sanitize_svg_upload( $upload, $context ) {
		$file = $upload['file'] ?? '';
		if ( ! $file || ! file_exists( $file ) ) {
			return $upload;
		}

		if ( 'svg' !== strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) ) {
			return $upload;
		}

		$svg_content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! $svg_content ) {
			return $upload;
		}

		$sanitized = $this->sanitize_svg( $svg_content );
		if ( false === $sanitized ) {
			$upload['error'] = 'SVG file is corrupted or invalid.';
			wp_delete_file( $file );
			return $upload;
		}

		file_put_contents( $file, $sanitized ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return $upload;
	}

	/**
	 * Prepare SVG for display in media library
	 *
	 * @param array $response Attachment data.
	 * @return array
	 */
	public function prepare_svg_for_media_library( $response ) {
		if ( 'image/svg+xml' === $response['mime'] ) {
			$response['image'] = array(
				'src' => $response['url'],
			);
		}
		return $response;
	}

	/**
	 * Sanitize SVG content
	 *
	 * Removes:
	 * - script, style, foreignObject elements
	 * - on* event handlers
	 * - javascript: URLs
	 * - data: URLs (except data:image/*)
	 *
	 * @param string $svg SVG XML content.
	 * @return string|false Sanitized SVG or false on error.
	 */
	private function sanitize_svg( $svg ) {
		// Disable external entity loading for security.
		$old_use_errors = libxml_use_internal_errors( true );
		libxml_clear_errors();

		$dom = new DOMDocument();
		if ( ! @$dom->loadXML( $svg, LIBXML_NONET ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			libxml_use_internal_errors( $old_use_errors );
			return false;
		}

		libxml_use_internal_errors( $old_use_errors );

		// Remove dangerous elements.
		$dangerous_tags = array( 'script', 'style', 'foreignObject' );
		foreach ( $dangerous_tags as $tag ) {
			$nodes = $dom->getElementsByTagName( $tag );
			for ( $i = $nodes->length - 1; $i >= 0; $i-- ) {
				$nodes->item( $i )->parentNode->removeChild( $nodes->item( $i ) );
			}
		}

		// Remove event handlers and dangerous attributes.
		$this->remove_dangerous_attributes( $dom );

		return $dom->saveXML( $dom->documentElement ) ? $dom->saveXML( $dom->documentElement ) : false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Recursively remove dangerous attributes from DOM nodes
	 *
	 * @param DOMNode $node DOM node to process.
	 * @return void
	 */
	private function remove_dangerous_attributes( $node ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHP native DOM API
		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return;
		}

		$to_remove = array();
		if ( $node->hasAttributes() ) {
			foreach ( $node->attributes as $attr ) {
				$name  = strtolower( $attr->nodeName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$value = $attr->nodeValue; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

				// Remove all on* handlers.
				if ( 0 === strpos( $name, 'on' ) ) {
					$to_remove[] = $attr->nodeName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					continue;
				}

				// Remove javascript: URLs.
				if ( false !== strpos( $value, 'javascript:' ) ) {
					$to_remove[] = $attr->nodeName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					continue;
				}

				// Remove data: URLs (except image/* for inline images).
				if ( false !== strpos( $value, 'data:' ) && false === strpos( $value, 'data:image/' ) ) {
					$to_remove[] = $attr->nodeName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					continue;
				}

				// Remove vbscript: URLs.
				if ( false !== strpos( $value, 'vbscript:' ) ) {
					$to_remove[] = $attr->nodeName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					continue;
				}

				// Remove url() with external references in style attribute.
				if ( 'style' === $name && ( false !== strpos( $value, 'url(' ) || false !== strpos( $value, 'expression(' ) ) ) {
					$to_remove[] = $attr->nodeName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					continue;
				}
			}
		}

		// Remove collected attributes.
		foreach ( $to_remove as $attr_name ) {
			$node->removeAttribute( $attr_name );
		}

		// Process child nodes.
		if ( $node->hasChildNodes() ) {
			foreach ( $node->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$this->remove_dangerous_attributes( $child );
			}
		}
	}
}
