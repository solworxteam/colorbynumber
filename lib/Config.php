<?php
declare(strict_types=1);

/**
 * Application configuration and constants
 */
class Config
{
    // File upload limits
    public const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    // Image processing limits
    public const MAX_IMAGE_WIDTH = 2000;
    public const MAX_IMAGE_HEIGHT = 2000;
    public const RESIZE_QUALITY = 85; // JPEG quality for resized images

    // Grid settings
    public const MIN_GRID_SIZE = 10;
    public const MAX_GRID_SIZE = 100;
    public const DEFAULT_GRID_SIZE = 20;

    // Color palette settings
    public const MIN_COLORS = 2;
    public const MAX_COLORS = 11;
    public const DEFAULT_COLORS = 6;

    // File cleanup (days)
    public const CLEANUP_DAYS = 7;

    /**
     * Get error message by code
     */
    public static function getErrorMessage(string $code): string
    {
        $messages = [
            'missing_fields' => 'Missing required form fields.',
            'no_image' => 'No image file uploaded.',
            'invalid_extension' => 'Only JPG, JPEG, PNG and WEBP are allowed.',
            'file_too_large' => 'File is too large. Maximum size is 10 MB.',
            'invalid_image' => 'Invalid image file. Please upload a valid image.',
            'image_too_large' => 'Image dimensions are too large. Maximum 2000x2000 pixels.',
            'upload_failed' => 'Failed to save uploaded file.',
            'read_failed' => 'Failed to read image file.',
            'resize_failed' => 'Failed to process image.',
            'mkdir_failed' => 'Failed to create necessary directories.',
            'save_failed' => 'Failed to save worksheet data.',
        ];

        return $messages[$code] ?? 'An error occurred.';
    }
}
