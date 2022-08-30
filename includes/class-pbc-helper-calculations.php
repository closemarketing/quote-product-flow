<?php
/**
 * Class for Calculations
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Helper Calculate PBC.
 *
 * All helpers calculations.
 *
 * @since 1.1
 */
class PBC_Helper_Calculations {

	/**
	 * Get variation image url with filter dependency
	 *
	 * @param array $session_variation
	 * @param integer $variation_id
	 * @return string
	 */
	public function get_image_variation_url( $session_variation, $variation_id = 0 ) {
		if ( ! isset( $variation_id ) ) {
			return '';
		}
		$imgprodgroup = get_post_meta( $variation_id, 'pbc_imgprodgroup', true);
		if ( ! empty( $imgprodgroup ) ) {
			foreach ( $imgprodgroup as $deps ) {
				if ( ! empty( $deps['pbc_depvarimgprod'] ) ) {
					$prevVar = array();
					foreach ( $deps['pbc_depvarimgprod'] as $depvarimgprod ) {
						$imgprod_arr = explode( '|', $depvarimgprod );
						if ( ! empty( $imgprod_arr[0] ) && ! empty( $imgprod_arr[1] ) ) {
							$prevVar[(int)$imgprod_arr[0]][] = $imgprod_arr[1];
						}
					}
					if ( ! empty( $session_variation ) && ! empty( $prevVar ) ) {
						foreach ( $prevVar as $sPhaseKey => $sVariations ) {
							if ( isset( $prevVar[ $sPhaseKey ]) &&
							isset( $session_variation[ $sPhaseKey ] ) &&
							in_array( $session_variation[ $sPhaseKey ]['var']['id'], $prevVar[ $sPhaseKey ] ) ) {
								$imgprod_id = $deps['pbc_imgprod'][0];
							} else {
								$imgprod_id = '';
								break;
							}
						}
					}
				} elseif ( ( ! isset( $deps['pbc_depvarimgprod'] ) || empty($deps['pbc_depvarimgprod'])) && isset($deps['pbc_imgprod']) ) {
						$imgprod_id = $deps['pbc_imgprod'][0];
						break;
				}
				if ( $imgprod_id ) {
					break;
				}
			}
		}
		if ( isset( $imgprod_id ) && $imgprod_id ) {
			$imgprodurl = wp_get_attachment_image_src( $imgprod_id, 'full', true );
		}

		return isset( $imgprodurl[0] ) ? $imgprodurl[0] : '';
	}

	/**
	 * Gets total price from enquiry
	 *
	 * @param integer $post_id
	 * @return float
	 */
	public function get_total_from_enquiry( $post_id ) {
		$metas = get_post_meta( $post_id );
		$total_price = 0;
		foreach ( $metas as $key => $value ) {
			if ( false !== strpos( $key, 'pbc_price_' ) ) {
				$price = isset( $value[0] ) ? (double) str_replace( ',', '.', $value[0] ) : 0;
				$total_price = $total_price + $price;
			}
		}

		return $total_price;
	}

}

$pbc_helper_calc = new PBC_Helper_Calculations();
