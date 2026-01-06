<?php
namespace WebPExpress;

if (!defined('ABSPATH')) {
    exit;
}

class MediaLibraryHelper
{
    /**
     * Returns the relative path to the uploads directory for a given absolute path,
     * stripped of the multisite 'sites/<blog_id>/' prefix if present.
     *
     * Examples:
     *  - Single site: /var/www/wp-content/uploads/2026/01/foo.webp
     *      -> 2026/01/foo.webp
     *  - Subfolder multisite: /var/www/wp-content/uploads/sites/2/2026/01/foo.webp
     *      -> 2026/01/foo.webp
     *
     * @param string $absolutePath Absolute path to a file
     * @param int|null &$blogId Optional output: blog ID if detected
     * @return string Relative path from blog uploads (without sites/<blog_id>/)
     */
    public static function getRelativePathFromBlogUploads($absolutePath, &$blogId = null)
    {
        $uploadsBase = rtrim(Paths::getUploadDirAbs(), '/');
        $blogId = 0;

        if (strpos($absolutePath, $uploadsBase) !== 0) {
            // File is outside uploads folder
            return '';
        }

        // Relative path from network uploads base
        $relativePath = ltrim(substr($absolutePath, strlen($uploadsBase)), '/');

        // Multisite subfolder detection
        if (is_multisite()) {
            if (preg_match('#^sites/(\d+)/(.*)#', $relativePath, $matches)) {
                $blogId = (int)$matches[1];
                $relativePath = $matches[2]; // strip "sites/<blog_id>/"
            }
        }

        return $relativePath;
    }

    /**
     * Returns true if $absolutePath corresponds to a registered attachment in the media library.
     * @param string $absolutePath
     * @return bool
     */
    public static function isRegisteredAttachment($absolutePath)
    {
        global $wpdb;

        $relativePath = self::getRelativePathFromBlogUploads($absolutePath, $blogId);
        if (!$relativePath) {
            return false;
        }

        // Determine correct postmeta table
        $table = is_multisite() && $blogId > 0 ? $wpdb->get_blog_prefix($blogId) . 'postmeta' : $wpdb->postmeta;

        $sql = $wpdb->prepare(
            "SELECT meta_id FROM $table WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
            $relativePath
        );

        return (bool) $wpdb->get_var($sql);
    }

    /**
     * Returns true if $absolutePath corresponds to a registered thumbnail size in the media library.
     * @param string $absolutePath
     * @return bool
     */
    public static function isRegisteredThumbnail($absolutePath)
    {
        global $wpdb;

        $relativePath = self::getRelativePathFromBlogUploads($absolutePath, $blogId);
        if (!$relativePath) {
            return false;
        }

        // Determine correct postmeta table
        $table = is_multisite() && $blogId > 0 ? $wpdb->get_blog_prefix($blogId) . 'postmeta' : $wpdb->postmeta;

        // Strip thumbnail pattern (-WxH) from filename if present
        $filename = basename($relativePath);
        $dir = dirname($relativePath);

        if (preg_match('/^(.*)-\d+x\d+(\.\w+)$/', $filename, $matches)) {
            $originalFilename = $matches[1] . $matches[2];
        } else {
            $originalFilename = $filename;
        }

        $originalPath = ($dir === '.' ? '' : $dir . '/') . $originalFilename;

        $sql = $wpdb->prepare(
            "SELECT meta_id FROM $table WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s LIMIT 1",
            '%' . $wpdb->esc_like($originalFilename) . '%'
        );

        return (bool) $wpdb->get_var($sql);
    }

    /**
     * Returns true if $absolutePath is either a registered attachment or thumbnail.
     * @param string $absolutePath
     * @return bool
     */
    public static function isRegisteredAttachmentOrThumbnail($absolutePath)
    {
        return self::isRegisteredAttachment($absolutePath) || self::isRegisteredThumbnail($absolutePath);
    }
}
