<?php
/**
 * Generates PDF
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

namespace Close\PBC\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Helper Calculate PBC.
 *
 * All helpers calculations.
 *
 * @since 1.1
 */
class PDF {
	/**
	 * Generates PDF from session
	 *
	 * @param array  $item        Item of budget to generate.
	 * @param string $type_return url/path for type to return.
	 * @return file
	 */
	public static function generate_engine_pdf( $item = array(), $type_return = 'path' ) {
		$filename      = __( 'budget', 'pbc' ) . '-' . sanitize_title( get_bloginfo( 'name' ) ) . '-' . gmdate( 'Y-m-d-H-i' ) . '.pdf';
		$dirname       = self::get_budget_base_dir( 'path' );
		$filename_path = $dirname . $filename;

		$content = self::configurator_result_generate_pdf( $item );

		if ( 'error' !== $content['type'] ) {
			try {
				$html2pdf  = new \Spipu\Html2Pdf\Html2Pdf( 'P', 'A4', 'en', true, 'UTF-8', array( 2.5, 2.5, 2.5, 2.5 ) );
				$html2pdf->setTestTdInOnePage( false );
				$html2pdf->writeHTML( $content['response'] );
				$html2pdf->Output( $filename_path, 'F' );
				// $html2pdf->close();
			} catch ( \Spipu\Html2Pdf\Exception $e ) {
				// error
				// $formatter = new ExceptionFormatter($e);
				// echo "Unexpected Error!<br>Can't load PDF this time!<br>".$formatter->getHtmlMessage();
			}
		}
		if ( is_file( $filename_path ) && 'path' === $type_return ) {
			return $filename_path;
		} elseif ( is_file( $filename_path ) && 'url' === $type_return ) {
			return self::get_budget_base_dir( 'url' ) . $filename;
		}
	}

	/**
	 * Returns the filename created in folder
	 *
	 * @return string Filename and path
	 */
	public static function get_budget_base_dir( $type = 'path' ) {
		$upload_dir = wp_upload_dir();
		$dir_name   = $upload_dir['basedir'] . '/pbc/';
		if ( ! file_exists( $dir_name ) ) {
			wp_mkdir_p( $dir_name );
		}

		if ( 'url' === $type ) {
			return $upload_dir['baseurl'] . '/pbc/';
		} else {
			return $dir_name;
		}
	}

	/**
	 * Generates PDF from session
	 *
	 * @param array $item Item of budget to generate.
	 * @return array
	 */
	public static function configurator_result_generate_pdf( $item = array() ) {
		$parent_phase = isset( $item['pbc_parent_phase'] ) ? (int) $item['pbc_parent_phase'] : 0;
		$session_key  = 'pbc_variation_' . $parent_phase;
		$budget_date  = isset( $item['pbc_budget_date'] ) ? sanitize_text_field( $item['pbc_budget_date'] ) : gmdate( 'd-m-Y' );

		if ( empty( $item[ $session_key ] ) && ! is_array( $item[ $session_key ] ) ) {
			$result = array(
				'type'     => 'error',
				'response' => __( 'Configurator not ready!', 'pbc' ),
			);
		}
		$total_vars       = count( $item[ $session_key ] );
		$itemv            = $item[ $session_key ];
		$contact          = isset( $item['pbc_contact'] ) ? $item['pbc_contact'] : array();
		$pdf_color_odd    = get_option( 'pbc_pdf_color_odd' );
		$background_color = $pdf_color_odd && '#' === substr( $pdf_color_odd, 0, 1 ) ? trim( $pdf_color_odd ) : '#ffebcb';

		$pdf_color_total  = get_option( 'pbc_pdf_color_total' );
		$background_total = $pdf_color_total && '#' === substr( $pdf_color_total, 0, 1 ) ? trim( $pdf_color_total ) : '#835536';
		$show_prices     = get_option( 'pbc_budget_show_prices' );
		$show_prices     = ! empty( $show_prices ) && 'no' === $show_prices ? false : true;
        $show_prices     = isset( $item['pbc_admin'] ) ? true : $show_prices;

		// Starts PDF.
		$output             = '<page backcolor="#fff">';
		$output            .= "<style>
		.header, .product .product-title {margin-left: 20px;}
		.product .product-title{ width:400px;text-align:left;vertical-align:bottom; }
		.product .product-preview{ width:300px; background-color: #fff; }
		.product .image-wrap{ position:relative; }
			.product .image-wrap img:first-child{ position:relative; }
			.product .image-wrap img{ width:100%;max-width:300px;position:absolute;top:0;left:0; }
		table.summary, table.product, table.summary-total, table.comments { width:600px;border-collapse:collapse;border:0; margin-left:50px;}
		table td.title{ width:500px;padding:5px 0 5px 15px; }
		table.comments td.title{ width:600px;padding:5px 0 5px 15px; }
		table td.value{ width:70px;padding:5px 15px 5px 0; }
		table td.right{text-align:right;}
		table.summary td.background, table.summary td.background{ background-color:$background_color; }
		table.summary-total td.empty{width:450px;}
		table.summary-total td.title{width:50px;}
		img.header_image{ width:700px;height:120px; }
		img.footer_image{ width:700px;height:70px; margin: 50px 0 0 30px;}
		</style>";
		$pdf_image_selected = get_option( 'pbc_pdf_image_selected' );
		$pdf_image_selected = ! empty( $pdf_image_selected ) ? trim( $pdf_image_selected ) : '';
		if ( ! empty( $pdf_image_selected ) ) {
			$output .= "<img src='" . esc_url( $pdf_image_selected ) . "' width='200'/>";
		}
		$header_image = get_option( 'pbc_pdf_image_header' );
		$header_image = ! empty( $header_image ) ? trim( $header_image ) : '';
		if ( ! empty( $header_image ) ) {
			$output .= '<table class="header"><tr><td><img src="' . esc_url( $header_image ) . '" class="header_image"/></td></tr></table><br/>';
		}
		$output .= '<table class="product"><tr><td class="product-title">';
		$output .= '<h1>' . esc_html__( 'Budget', 'pbc' ) . '</h1>';
		$output .= '<strong>' . esc_html__( 'Date', 'pbc' ) . ':</strong> ' . $budget_date . '<br/>';

		// Budget ID.
		$budget_id = isset( $item['pbc_enquiry'] ) ? (int) $item['pbc_enquiry'] : 0;
		if ( ! empty( $budget_id ) ) {
			$output .= '<strong>' . esc_html__( 'Budget ID', 'pbc' ) . ':</strong> ' . $budget_id . '<br/>';
		}

		// Contact.
		if ( ! empty( $contact['email'] ) ) {
			$output .= '<p><strong>' . esc_html__( 'Contact', 'pbc' ) . ': ' . $contact['name'] . '</strong>';
			if ( ! empty( $contact['email'] ) ) {
				$output .= '<br/><strong>' . esc_html__( 'Email', 'pbc' ) . ':</strong> ' . $contact['email'];
			}
			if ( ! empty( $contact['phone'] ) ) {
				$output .= '<br/><strong>' . esc_html__( 'Phone', 'pbc' ) . ':</strong> ' . $contact['phone'];
			}
			if ( ! empty( $contact['city'] ) ) {
				$output .= '<br/><strong>' . esc_html__( 'City', 'pbc' ) . ':</strong> ' . $contact['city'];
			}
			if ( ! empty( $contact['state'] ) ) {
				$output .= '<br/><strong>' . esc_html__( 'State', 'pbc' ) . ':</strong> ' . $contact['state'] . '';
			}
			$output .= '</p>';
		}

		$output .= '<h2>' . esc_html__( 'Characteristics selected', 'pbc' ) . '</h2>';
		$output .= '<p>' . esc_html__( 'Lists of options selected:', 'pbc' ) . '</p></td><td class="product-preview"><div class="image-wrap">';

		// Flipped images.
		$flipped                   = false;
		$variations_images_flipped = get_option( 'variations_images_flipped' );
		$variations_images_flipped = is_array( $variations_images_flipped ) ? array_filter( $variations_images_flipped ) : array();
		if ( ! empty( $variations_images_flipped ) && file_exists( $variations_images_flipped ) ) {
			for ( $j = 1; $j <= $total_vars; $j++ ) {
				if ( isset( $itemv[ $j ] ) && in_array( $itemv[ $j ]['var']['id'], $variations_images_flipped ) ) {
					$flipped = true;
				}
			}
		}
        $output .= self::generateProductImage($itemv, $total_vars, $flipped);
		$output .= '</div></td></tr></table><br/><br/>';

		$output     .= '<table class="summary">';
		$total_price = 0;
		$i           = 0;
		$total_qty   = 1;

		foreach ( $itemv as $details ) {
			if ( ! is_array( $details ) ) {
				continue;
			}
			$variation_name  = isset( $details['phase']['name'] ) ? sanitize_text_field( $details['phase']['name'] ) . ': ' : '';
			$variation_name .= isset( $details['var']['name'] ) ? sanitize_text_field( $details['var']['name'] ) : '';
			$variation_id    = isset( $details['var']['id'] ) ? (int) $details['var']['id'] : 0;
			$bg              = ( $i % 2 ) == 0 ? 'background' : '';

			$variation_type = get_post_meta( $variation_id, 'pbc_field_type', true );
			$variation_type = ! empty( $details['var']['id'] ) ? $details['var']['type'] : $variation_type;
			$price          = (float) str_replace( ',', '.', $details['var']['price'] );
			if ( 'qty' === $variation_type ) {
				$total_qty = $price;
			} else {
				$total_price += $price;

				$output .= '<tr>';
				$output .= '<td class="title ' . $bg . '">' . $variation_name . '</td>';
				$output .= '<td class="value right ' . $bg . '">';
				if ( $price > 0 && $show_prices ) {
					$output .= number_format( $price, 2, ',', '.' ) . ' €';
				}
				$output .= '</td>';
				$output .= '</tr>';
			}
			++$i;
		}
		if ( ! $total_price ) {
			$total_price = 0;
			$tax         = 0;
		}
		$tax            = ( $total_price * 0.21 ) * $total_qty;
		$total_pricevat = ( $total_price + $total_price * 0.21 ) * $total_qty;

		$output .= '</table>';

        if ( $show_prices ) {
            // Summary.
            $output .= '<br/><br/><table class="summary-total"><tr>';
            $output .= '<td class="empty">&nbsp;</td><td class="title right">' . esc_html__( 'Taxes', 'pbc' ) . '</td>';
            $output .= '<td class="value right">';
            if ( $tax > 0 ) {
                $output .= number_format( $tax, 2, ',', '.' ) . ' €';
            }
            $output .= '</td>';
            $output .= '</tr>';

            // Subtotal.
            $output .= '<tr><td class="empty">&nbsp;</td><td class="title right">' . esc_html__( 'Subtotal', 'pbc' ) . '</td>';
            $output .= '<td class="value right">';
            if ( $total_price > 0 ) {
                $output .= number_format( $total_price, 2, ',', '.' ) . ' €';
            }
            $output .= '</td>';
            $output .= '</tr>';

            // Quantity.
            $output .= '<tr><td class="empty">&nbsp;</td><td class="title right">' . esc_html__( 'Quantity', 'pbc' ) . '</td>';
            $output .= '<td class="value right">';
            if ( $total_price > 0 ) {
                $output .= number_format( $total_qty, 2, ',', '.' );
            }
            $output .= '</td>';
            $output .= '</tr>';

            // Total.
            $output .= '<tr>';
            $color   = CALC::calculate_color_text( $background_total );
            $output .= '<td class="empty">&nbsp;</td><td class="title right" style="background-color:' . $background_total . ';color:' . $color . ';">' . esc_html__( 'Total', 'pbc' ) . '</td>';
            $output .= '<td class="value right" style="background-color:' . $background_total . ';color:' . $color . ';">';
            if ( $total_pricevat > 0 ) {
                $output .= number_format( $total_pricevat, 2, ',', '.' ) . ' €';
            }
            $output .= '</td>';
            $output .= '</tr>';
            $output .= '</table><br/>';
        }

		// Comments.
		$comments = isset( $contact['comments'] ) ? sanitize_text_field( $contact['comments'] ) : '';
		if ( ! empty( $comments ) ) {
			$output .= '<table class="comments"><tr><td class="title"><p><strong>' . esc_html__( 'Comments', 'pbc' ) . '</strong><br/>';
			$output .= wp_kses_post( $comments ) . '</p></td></tr></table><br/>';
		}

		$footer_image = get_option( 'pbc_pdf_image_footer' );
		$footer_image = ! empty( $footer_image ) ? trim( $footer_image ) : '';
		if ( ! empty( $footer_image ) ) {
			$output .= '<table class="footer"><tr><td><img src="' . esc_url( $footer_image ) . '" class="footer_image"/></td></tr></table><br/>';
		}

		$output .= '</page>';
		$result  = array(
			'type'     => 'success',
			'response' => $output,
		);
		return $result;
	}

    public static function generateProductImage($itemv, $total_vars, $flipped) {
        // Define the output image dimensions
        $output_width = 300;
        $output_height = 243;

        // Create the true color image for the output
        $output_image = imagecreatetruecolor($output_width, $output_height);

        // --- Transparency Setup for Output Image ---
        // 1. Turn OFF alpha blending for the output image.
        imagealphablending($output_image, false);

        // 2. Enable saving alpha channel for the output image.
        //    Ensures the transparency information is preserved when the image is saved.
        imagesavealpha($output_image, true);

        // 3. Allocate a fully transparent color (alpha 127 = 100% transparent)
        $transparent_color = imagecolorallocatealpha($output_image, 0, 0, 0, 127);

        // 4. Fill the entire output image with the fully transparent color
        imagefill($output_image, 0, 0, $transparent_color);
        // --- End Transparency Setup ---

        $dirname = self::get_budget_base_dir();

        // Ensure the directory exists and is writable
        if (!is_dir($dirname)) {
            if (!mkdir($dirname, 0755, true)) {
                error_log("Failed to create directory: " . $dirname);
                return '<p style="color:red;">Error: Output directory not found or writable.</p>';
            }
        }

        for ($i = 0; $i <= $total_vars; $i++) {
            $imgprodid = '';
            $imgprodurl = '';

            if (!empty($itemv[$i]['var']['id'])) {
                $ssVar = $itemv[$i]['var']['id'];
                $imgprodgroup = get_post_meta($ssVar, 'pbc_imgprodgroup', true);

                if (!empty($imgprodgroup)) {
                    foreach ($imgprodgroup as $deps) {
                        if (isset($deps['pbc_depvarimgprod']) && !empty($deps['pbc_depvarimgprod']) && isset($deps['pbc_imgprod'])) {
                            $prevVar = array();
                            foreach ($deps['pbc_depvarimgprod'] as $depvarimgprod) {
                                $arr = explode('|', $depvarimgprod);
                                if (!empty($arr[0]) && !empty($arr[1])) {
                                    $prevVar[(int)$arr[0]][] = $arr[1];
                                }
                            }

                            if (!empty($itemv)) {
                                foreach ($itemv as $sPhaseKey => $svariations) {
                                    if (isset($prevVar[$sPhaseKey]) &&
                                        isset($itemv[$sPhaseKey]) && in_array($itemv[$sPhaseKey]['var']['id'], $prevVar[$sPhaseKey])) {
                                        $imgprodid = $deps['pbc_imgprod'][0];
                                        break 2;
                                    }
                                }
                            }
                        } elseif ((!isset($deps['pbc_depvarimgprod']) || empty($deps['pbc_depvarimgprod'])) && isset($deps['pbc_imgprod'])) {
                            $imgprodid = $deps['pbc_imgprod'][0];
                            break;
                        }
                    }
                }

                if (!empty($imgprodid)) {
                    $imgprodurl_array = wp_get_attachment_image_src($imgprodid, 'full', true);
                    $imgprodurl = $imgprodurl_array[0] ?? '';
                }

                if (!empty($imgprodurl) && wp_remote_retrieve_response_code(wp_remote_head($imgprodurl)) === 200) {
                    $extension = pathinfo($imgprodurl, PATHINFO_EXTENSION);
                    $img = false;
                    $width = 0;
                    $height = 0;

                    // Attempt to get image size first to avoid unnecessary image creation
                    $image_size_info = @getimagesize($imgprodurl); 
                    if ($image_size_info) {
                        list($width, $height, $type) = $image_size_info;

                        switch (strtolower($extension)) {
                            case 'png':
                                $img = imagecreatefrompng($imgprodurl);
                                break;
                            case 'jpg':
                            case 'jpeg':
                                $img = imagecreatefromjpeg($imgprodurl);
                                break;
                            case 'gif':
                                $img = imagecreatefromgif($imgprodurl);
                                break;
                            case 'webp':
                                $img = imagecreatefromwebp($imgprodurl);
                                break;
                            default:
                                error_log("Unsupported image format: " . $extension . " for URL: " . $imgprodurl);
                                continue 2;
                        }
                    } else {
                        error_log("DEBUG: Failed to get image size for URL: " . $imgprodurl);
                        continue;
                    }

                    if ($img) {
                        error_log("DEBUG: Image loaded for product ID: " . $imgprodid . " from URL: " . $imgprodurl);
                        error_log("DEBUG: Source dimensions (width, height): " . $width . ", " . $height);

                        // If the source image supports alpha (PNG, WebP), ensure alpha blending is on for it
                        // and imagesavealpha is true if you were modifying it before copying.
                        // For imagecopyresampled, the destination's alpha settings are primary.
                        if (in_array(strtolower($extension), ['png', 'webp'])) {
                            imagealphablending($img, true);
                            imagesavealpha($img, true);
                        }
                        // Flip it horizontally if $flipped is true
                        if ($flipped) {
                            imageflip($img, IMG_FLIP_HORIZONTAL);
                        }

                        // Calculate proportional new dimensions
                        $new_height = $output_height;
                        $new_width = ($height > 0) ? ($width / $height) * $new_height : $output_width;

                        if ($new_width > $output_width) {
                            $new_width = $output_width;
                            $new_height = ($width > 0) ? ($height / $width) * $new_width : $output_height;
                        }

                        $x_position = max(0, (int)(($output_width - $new_width) / 2));
                        $y_position = max(0, (int)(($output_height - $new_height) / 2));

                        error_log("DEBUG: Calculated copy dimensions (new_width, new_height): " . (int)$new_width . ", " . (int)$new_height);
                        error_log("DEBUG: Copy positions (x_position, y_position): " . $x_position . ", " . $y_position);

                        // --- Critical: Re-enable alpha blending on the output image just before copying ---
                        // This ensures that the alpha channels of the source images are correctly blended
                        // with the output image's transparent background.
                        imagealphablending($output_image, true);

                        // Copy and resample the image onto the output canvas
                        imagecopyresampled($output_image, $img, $x_position, $y_position, 0, 0, (int)$new_width, (int)$new_height, $width, $height);
                        imagedestroy($img); // Free memory for the source image
                    } else {
                        error_log("DEBUG: Failed to create image resource for URL: " . $imgprodurl);
                    }
                } else {
                    error_log("DEBUG: Image URL not found or inaccessible: " . $imgprodurl);
                }
            }
        }

        $output_file_name = 'product-image-for-pdf.png';
        $output_file_path = $dirname . $output_file_name;

        // Save the final image. Check if saving was successful.
        if (!imagepng($output_image, $output_file_path)) {
            error_log("Failed to save image to: " . $output_file_path);
            imagedestroy($output_image);
            return '<p style="color:red;">Error: Failed to save product image.</p>';
        }
        imagedestroy($output_image); // Free memory for the output image

        // Provide the direct file system path for Html2Pdf
        $output = '<img phaseid="' . $i . '" src="' . $output_file_path . '" alt="product image" height="500px" width="auto" />';
        return $output;
    }
}
