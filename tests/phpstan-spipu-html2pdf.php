<?php
// Mock Spipu\Html2Pdf\Exception class for PHPStan static analysis.
namespace Spipu\Html2Pdf;

if ( ! class_exists( 'Spipu\Html2Pdf\Exception' ) ) {
	/**
	 * Dummy Exception class for PHPStan compatibility.
	 */
	class Exception extends \Exception {
	}
}
