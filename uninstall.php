<?php
/**
 * Smart Clipboard Media Uploader - Uninstall Script
 * Xử lý dọn dẹp các options cấu hình khi người dùng xóa plugin trong trang quản trị.
 * 
 * @package Smart_Clipboard_Media_Uploader
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Xóa các tùy chọn cấu hình trong wp_options
delete_option( 'smart_paste_enable_media_library' );
delete_option( 'smart_paste_enable_editor' );
delete_option( 'smart_paste_enable_featured' );
delete_option( 'smart_paste_naming_scheme' );
delete_option( 'smart_paste_custom_prefix' );
delete_option( 'smart_paste_auto_alt' );
delete_option( 'smart_paste_show_toast' );
delete_option( 'smart_paste_play_sound' );
delete_option( 'smart_paste_total_count' );

// Lưu ý: Tuyệt đối KHÔNG xóa các file ảnh đã upload vào Thư viện Media để bảo vệ dữ liệu khách hàng.
