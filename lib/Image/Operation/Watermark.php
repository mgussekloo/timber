<?php

namespace Timber\Image\Operation;

use Timber\Helper;
use Timber\Image\Operation as ImageOperation;

/*
 * Watermark an image with a (preferably transparent) watermark-image.
 *
 */
class Watermark extends ImageOperation {

	private $watermark_image;

	/**
	 * @param int    $watermark_image
	 */
	public function __construct( $watermark_image ) {
		$this->watermark_image = $watermark_image;
	}

	/**
	 * @param   string    $src_filename     the basename of the file (ex: my-awesome-pic)
	 * @param   string    $src_extension    the extension (ex: .jpg)
	 * @return  string    the final filename to be used
	 *                    (ex: my-awesome-pic-lbox-300x200-FF3366.jpg)
	 */
	public function filename( $src_filename, $src_extension ) {
		$newbase = $src_filename.'-watermark-'.substr(md5($this->watermark_image), 0, 7);
		$new_name = $newbase.'.'.$src_extension;
		return $new_name;
	}

	/**
	 * Performs the actual image manipulation,
	 * including saving the target file.
	 *
	 * @param  string $load_filename filepath (not URL) to source file
	 *                               (ex: /src/var/www/wp-content/uploads/my-pic.jpg)
	 * @param  string $save_filename filepath (not URL) where result file should be saved
	 *                               (ex: /src/var/www/wp-content/uploads/my-pic-lbox-300x200-FF3366.jpg)
	 * @return bool                  true if everything went fine, false otherwise
	 */
	public function run( $load_filename, $save_filename ) {
		// Load the stamp and the photo to apply the watermark to
		$source = self::getImage($load_filename);
		$watermark = self::getImage($this->watermark_image);

		$wp_image = wp_get_image_editor( $load_filename );
		$quality = $wp_image->get_quality();

		// Set the margins for the stamp and get the height/width of the stamp image
		$margin_right = 10;
		$margin_bottom = 10;

		$sx = imagesx($source);
		$sy = imagesy($source);

		$wx = imagesx($watermark);
		$wy = imagesy($watermark);

		// Copy the stamp image onto our photo using the margin offsets and the photo
		// width to calculate positioning of the stamp.
		imagecopy(
			$source,
			$watermark,
			$sx - $wx - $margin_right,
			$sy - $wy - $margin_bottom,
			0,
			0,
			$wx,
			$wy
		);

		return self::saveImage($source, $save_filename, $quality);
	}

	static function getImage($src) {
		$func = 'imagecreatefromjpeg';
		$ext = pathinfo($src, PATHINFO_EXTENSION);
		if ( $ext == 'gif' ) {
			$func = 'imagecreatefromgif';
		} else if ( $ext == 'png' ) {
			$func = 'imagecreatefrompng';
		}
		return $func($src);
	}

	static function saveImage($imageObj, $filename, $quality=90) {


		$save_func = 'imagejpeg';
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		if ( $ext == 'gif' ) {
			$save_func = 'imagegif';
		} else if ( $ext == 'png' ) {
			$save_func = 'imagepng';
			if ( $quality > 9 ) {
				$quality = $quality / 10;
				$quality = round(10 - $quality);
			}
		}

		if ( $save_func === 'imagegif' ) {
			return $save_func($imageObj, $filename);
		}
		return $save_func($imageObj, $filename, $quality);
	}
}
