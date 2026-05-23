<?php
/**
 * Image Processor - Görsel İşleme
 * 
 * 700x700px JPG otomatik ölçekleme
 * MIME-type kontrolü (sadece JPG)
 * Orijinal silme
 * 
 * @package GOOIDA
 * @subpackage ImageProcessor
 * @since 1.0.0
 */

namespace GOOIDA;

class ImageProcessor {
    /**
     * Allowed MIME types
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
    ];

    /**
     * Output size
     */
    private const OUTPUT_SIZE = 700;

    /**
     * Output format
     */
    private const OUTPUT_FORMAT = 'jpg';

    /**
     * JPEG quality
     */
    private const JPEG_QUALITY = 85;

    /**
     * Process and resize image
     *
     * @param string $file_path Path to image file
     * @return string|false Processed image path or false
     */
    public static function process_image( string $file_path ) {
        // Check if file exists
        if ( ! file_exists( $file_path ) ) {
            return false;
        }

        // Get MIME type
        $mime_type = mime_content_type( $file_path );

        // Check MIME type
        if ( ! in_array( $mime_type, self::ALLOWED_MIME_TYPES, true ) ) {
            return false;
        }

        // Load image
        $image = imagecreatefromjpeg( $file_path );
        if ( $image === false ) {
            return false;
        }

        // Get original dimensions
        $width = imagesx( $image );
        $height = imagesy( $image );

        // Calculate new dimensions (1x1 square)
        $size = min( $width, $height );
        $x = intdiv( $width - $size, 2 );
        $y = intdiv( $height - $size, 2 );

        // Create new image
        $new_image = imagecrop( $image, [
            'x' => $x,
            'y' => $y,
            'width' => $size,
            'height' => $size,
        ] );

        // Resize to 700x700
        $resized = imagescale( $new_image, self::OUTPUT_SIZE, self::OUTPUT_SIZE );

        // Save as JPG
        $output_path = preg_replace( '/\.[^.]*$/', '.' . self::OUTPUT_FORMAT, $file_path );
        $success = imagejpeg( $resized, $output_path, self::JPEG_QUALITY );

        // Free memory
        imagedestroy( $image );
        imagedestroy( $new_image );
        imagedestroy( $resized );

        // Delete original if different
        if ( $output_path !== $file_path && file_exists( $file_path ) ) {
            unlink( $file_path );
        }

        return $success ? $output_path : false;
    }

    /**
     * Validate image file
     *
     * @param array $file $_FILES array element
     * @return bool Valid
     */
    public static function validate_image( array $file ): bool {
        // Check size
        if ( $file['size'] > 5 * MB_IN_BYTES ) {
            return false;
        }

        // Check MIME type
        $mime_type = mime_content_type( $file['tmp_name'] );
        return in_array( $mime_type, self::ALLOWED_MIME_TYPES, true );
    }
}
