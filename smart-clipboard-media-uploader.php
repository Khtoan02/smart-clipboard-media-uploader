<?php
/**
 * Plugin Name: Smart Clipboard Media Uploader
 * Plugin URI:  https://mamxanhdigital.vn
 * Description: Dán trực tiếp hình ảnh (Ctrl+V / Cmd+V) từ Clipboard vào Thư viện Media, Trình soạn thảo bài viết (Gutenberg & Classic Editor TinyMCE) và Ảnh đại diện (Featured Image) siêu tốc không cần tải ảnh về máy tính. Tự động tối ưu tên file chuẩn SEO theo tiêu đề bài viết.
 * Version:     1.0.0
 * Author:      Khánh Toàn
 * Author URI:  https://mamxanhdigital.vn
 * Text Domain: smart-clipboard-media-uploader
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Chống truy cập trực tiếp
}

// -----------------------------------------------------------------------------
// HÀM CHUNG: RENDER CENTRAL DASHBOARD & ĐĂNG KÝ MENU SMART TOOLS
// -----------------------------------------------------------------------------
if ( ! function_exists( 'smart_tools_add_submenu' ) ) {
	function smart_tools_add_submenu( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon = 'dashicons-shield-alt', $position = 80 ) {
		global $menu;
		$parent_slug = 'smart-tools';

		$parent_exists = false;
		if ( ! empty( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && $item[2] === $parent_slug ) {
					$parent_exists = true;
					break;
				}
			}
		}

		if ( ! $parent_exists && function_exists( 'smart_tools_render_central_dashboard' ) ) {
			add_menu_page(
				'Smart Tools',
				'Smart Tools',
				$capability,
				$parent_slug,
				'smart_tools_render_central_dashboard',
				$icon,
				$position
			);
			add_submenu_page(
				$parent_slug,
				'Smart Tools Dashboard',
				'📌 Bảng Điều Khiển',
				$capability,
				$parent_slug,
				'smart_tools_render_central_dashboard'
			);
		}

		add_submenu_page(
			$parent_slug,
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$callback
		);
	}
}

if ( ! function_exists( 'smart_tools_render_central_dashboard' ) ) {
	function smart_tools_render_central_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'smart-clipboard-media-uploader' ) );
		}

		global $wpdb;
		$total_images = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'" );
		$total_pasted = (int) get_option( 'smart_paste_total_count', 0 );
		?>
		<div class="wrap smart-dashboard-wrap" style="max-width: 1200px; margin: 20px 20px 40px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
			<div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 30px; border-radius: 16px; margin-bottom: 30px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25); display: flex; justify-content: space-between; align-items: center;">
				<div>
					<h1 style="font-size: 28px; font-weight: 800; margin: 0 0 8px 0; color: #fff; display: flex; align-items: center; gap: 12px;">⚡ Smart Tools Dashboard</h1>
					<p style="margin: 0; opacity: 0.85; font-size: 15px;">Hệ sinh thái công cụ tối ưu Media, Bảo mật và Tự động hóa WordPress.</p>
				</div>
				<div style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 99px; font-size: 13px; font-weight: 600;">
					Khánh Toàn | mamxanhdigital.vn
				</div>
			</div>

			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px;">
				<div style="background: #fff; padding: 22px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);">
					<h3 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: #64748b; margin: 0 0 10px 0;">Tổng số ảnh Thư viện</h3>
					<div style="font-size: 32px; font-weight: 800; color: #0f172a;"><?php echo number_format_i18n( $total_images ); ?></div>
					<div style="font-size: 13px; color: #64748b; margin-top: 6px;">Tệp ảnh đang có trong Thư viện</div>
				</div>
				<div style="background: #fff; padding: 22px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);">
					<h3 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: #64748b; margin: 0 0 10px 0;">Ảnh dán từ Clipboard</h3>
					<div style="font-size: 32px; font-weight: 800; color: #16a34a;"><?php echo number_format_i18n( $total_pasted ); ?></div>
					<div style="font-size: 13px; color: #64748b; margin-top: 6px;">Đã tải lên siêu tốc qua Ctrl+V</div>
				</div>
			</div>

			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-clipboard-media-uploader' ) ); ?>" class="button button-primary">Quản lý Cài đặt Dán Ảnh Clipboard &rarr;</a></p>
		</div>
		<?php
	}
}

/**
 * Class Smart_Clipboard_Media_Uploader
 */
class Smart_Clipboard_Media_Uploader {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// AJAX Upload Handler
		add_action( 'wp_ajax_smart_clipboard_upload_image', array( $this, 'ajax_upload_image' ) );

		// Tự động xử lý và lưu ảnh dán vào Thư viện Media khi Lưu bài viết / Xuất bản (Chống rác thư viện)
		add_filter( 'wp_insert_post_data', array( $this, 'process_post_content_base64_images' ), 20, 2 );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Đăng ký các thiết lập trong wp_options
	 */
	public function register_settings() {
		$options = array(
			'smart_paste_enable_media_library' => array( 'type' => 'boolean', 'default' => 1 ),
			'smart_paste_enable_editor'        => array( 'type' => 'boolean', 'default' => 1 ),
			'smart_paste_enable_featured'      => array( 'type' => 'boolean', 'default' => 1 ),
			'smart_paste_editor_mode'          => array( 'type' => 'string',  'default' => 'on_save' ),
			'smart_paste_convert_format'       => array( 'type' => 'string',  'default' => 'webp' ),
			'smart_paste_image_quality'        => array( 'type' => 'integer', 'default' => 82 ),
			'smart_paste_naming_scheme'        => array( 'type' => 'string',  'default' => 'post_title' ),
			'smart_paste_custom_prefix'        => array( 'type' => 'string',  'default' => 'pasted-image' ),
			'smart_paste_auto_alt'             => array( 'type' => 'boolean', 'default' => 1 ),
			'smart_paste_show_toast'           => array( 'type' => 'boolean', 'default' => 1 ),
			'smart_paste_play_sound'           => array( 'type' => 'boolean', 'default' => 1 ),
		);

		foreach ( $options as $opt_name => $opt_args ) {
			$sanitize_cb = 'sanitize_text_field';
			if ( 'boolean' === $opt_args['type'] ) {
				$sanitize_cb = 'rest_sanitize_boolean';
			} elseif ( 'integer' === $opt_args['type'] ) {
				$sanitize_cb = 'absint';
			}

			register_setting( 'smart_clipboard_settings_group', $opt_name, array(
				'type'              => $opt_args['type'],
				'sanitize_callback' => $sanitize_cb,
				'default'           => $opt_args['default'],
			) );
		}
	}

	/**
	 * Thêm submenu vào Smart Tools
	 */
	public function add_admin_menu() {
		smart_tools_add_submenu(
			__( 'Dán Ảnh Clipboard (Ctrl+V)', 'smart-clipboard-media-uploader' ),
			__( '📋 Dán Ảnh Clipboard', 'smart-clipboard-media-uploader' ),
			'manage_options',
			'smart-clipboard-media-uploader',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Link cài đặt trong danh sách plugins
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=smart-clipboard-media-uploader' ) ),
			__( 'Cài đặt & Dùng thử', 'smart-clipboard-media-uploader' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Enqueue Scripts và Styles trong Admin
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		$plugin_url = plugin_dir_url( __FILE__ );
		$version    = '1.0.0';

		wp_enqueue_style(
			'smart-clipboard-admin-css',
			$plugin_url . 'assets/css/smart-clipboard-admin.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'smart-clipboard-admin-js',
			$plugin_url . 'assets/js/smart-clipboard-admin.js',
			array( 'jquery' ),
			$version,
			true
		);

		$settings = array(
			'enableMediaLibrary' => (bool) get_option( 'smart_paste_enable_media_library', 1 ),
			'enableEditor'       => (bool) get_option( 'smart_paste_enable_editor', 1 ),
			'enableFeatured'     => (bool) get_option( 'smart_paste_enable_featured', 1 ),
			'editorMode'         => get_option( 'smart_paste_editor_mode', 'on_save' ),
			'convertFormat'      => get_option( 'smart_paste_convert_format', 'webp' ),
			'imageQuality'       => (int) get_option( 'smart_paste_image_quality', 82 ),
			'namingScheme'       => get_option( 'smart_paste_naming_scheme', 'post_title' ),
			'customPrefix'       => get_option( 'smart_paste_custom_prefix', 'pasted-image' ),
			'autoAlt'            => (bool) get_option( 'smart_paste_auto_alt', 1 ),
			'showToast'          => (bool) get_option( 'smart_paste_show_toast', 1 ),
			'playSound'          => (bool) get_option( 'smart_paste_play_sound', 1 ),
		);

		wp_localize_script(
			'smart-clipboard-admin-js',
			'smartClipboardVars',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'smart_clipboard_nonce' ),
				'settings' => $settings,
				'strings'  => array(
					'loading'    => __( 'Đang tải ảnh từ Clipboard lên Thư viện Media...', 'smart-clipboard-media-uploader' ),
					'success'    => __( 'Đã dán ảnh thành công!', 'smart-clipboard-media-uploader' ),
					'error'      => __( 'Có lỗi khi tải ảnh từ Clipboard.', 'smart-clipboard-media-uploader' ),
					'copyUrl'    => __( 'Sao chép URL', 'smart-clipboard-media-uploader' ),
					'urlCopied'  => __( 'Đã sao chép liên kết ảnh vào Clipboard!', 'smart-clipboard-media-uploader' ),
				),
			)
		);
	}

	/**
	 * Kiểm tra khả năng hỗ trợ WebP / AVIF của máy chủ
	 */
	public function check_server_capabilities() {
		$support = array(
			'webp' => false,
			'avif' => false,
		);

		if ( function_exists( 'imagetypes' ) ) {
			if ( imagetypes() & IMG_WEBP ) {
				$support['webp'] = true;
			}
			if ( defined( 'IMG_AVIF' ) && ( imagetypes() & IMG_AVIF ) && function_exists( 'imageavif' ) ) {
				$support['avif'] = true;
			}
		}

		if ( class_exists( 'Imagick' ) ) {
			try {
				$formats = Imagick::queryFormats();
				if ( in_array( 'WEBP', $formats, true ) ) {
					$support['webp'] = true;
				}
				if ( in_array( 'AVIF', $formats, true ) ) {
					$support['avif'] = true;
				}
			} catch ( Exception $e ) {}
		}

		return $support;
	}

	/**
	 * Chuyển đổi tệp tin hình ảnh tạm sang WebP hoặc AVIF ngay trước khi lưu vào Media
	 */
	public function convert_image_file( $source_path, $target_format = 'webp', $quality = 82 ) {
		if ( ! file_exists( $source_path ) ) {
			return false;
		}

		$image_info = @getimagesize( $source_path );
		if ( ! $image_info ) {
			return false;
		}

		$mime = strtolower( $image_info['mime'] );

		// Nếu đã là định dạng mục tiêu thì không cần chuyển đổi
		if ( 'image/' . $target_format === $mime ) {
			return array(
				'path' => $source_path,
				'mime' => $mime,
				'ext'  => '.' . $target_format,
			);
		}

		$capabilities = $this->check_server_capabilities();
		if ( 'avif' === $target_format && ! $capabilities['avif'] ) {
			$target_format = 'webp'; // Tự động chuyển về WebP nếu server chưa hỗ trợ AVIF
		}

		if ( 'webp' === $target_format && ! $capabilities['webp'] ) {
			return false;
		}

		$temp_dir = get_temp_dir();
		$new_path = wp_unique_filename( $temp_dir, 'smart_conv_' . uniqid() . '.' . $target_format );
		$new_file = trailingslashit( $temp_dir ) . $new_path;

		// 1. Chuyển đổi bằng GD Library
		if ( function_exists( 'imagecreatefromjpeg' ) || function_exists( 'imagecreatefrompng' ) || function_exists( 'imagecreatefromgif' ) ) {
			$image = false;
			if ( 'image/jpeg' === $mime && function_exists( 'imagecreatefromjpeg' ) ) {
				$image = @imagecreatefromjpeg( $source_path );
			} elseif ( 'image/png' === $mime && function_exists( 'imagecreatefrompng' ) ) {
				$image = @imagecreatefrompng( $source_path );
				if ( $image ) {
					imagepalettetotruecolor( $image );
					imagealphablending( $image, true );
					imagesavealpha( $image, true );
				}
			} elseif ( 'image/gif' === $mime && function_exists( 'imagecreatefromgif' ) ) {
				$image = @imagecreatefromgif( $source_path );
			}

			if ( $image ) {
				$saved = false;
				if ( 'webp' === $target_format && function_exists( 'imagewebp' ) ) {
					$saved = @imagewebp( $image, $new_file, $quality );
				} elseif ( 'avif' === $target_format && function_exists( 'imageavif' ) ) {
					$saved = @imageavif( $image, $new_file, $quality );
				}

				imagedestroy( $image );

				if ( $saved && file_exists( $new_file ) && filesize( $new_file ) > 0 ) {
					return array(
						'path' => $new_file,
						'mime' => 'image/' . $target_format,
						'ext'  => '.' . $target_format,
					);
				}
			}
		}

		// 2. Chuyển đổi bằng Imagick nếu GD chưa thực hiện được
		if ( class_exists( 'Imagick' ) ) {
			try {
				$imagick = new Imagick( $source_path );
				$imagick->setImageFormat( $target_format );
				$imagick->setImageCompressionQuality( $quality );
				$imagick->writeImage( $new_file );
				$imagick->clear();
				$imagick->destroy();

				if ( file_exists( $new_file ) && filesize( $new_file ) > 0 ) {
					return array(
						'path' => $new_file,
						'mime' => 'image/' . $target_format,
						'ext'  => '.' . $target_format,
					);
				}
			} catch ( Exception $e ) {}
		}

		return false;
	}

	/**
	 * Xử lý tải ảnh lên qua AJAX từ Clipboard
	 */
	public function ajax_upload_image() {
		check_ajax_referer( 'smart_clipboard_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không có quyền tải tệp tin lên hệ thống.', 'smart-clipboard-media-uploader' ) ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Không nhận được dữ liệu tệp tin hình ảnh từ Clipboard.', 'smart-clipboard-media-uploader' ) ) );
		}

		$file        = $_FILES['file'];
		$post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$is_featured = ! empty( $_POST['is_featured'] );
		$post_title  = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$alt_text    = isset( $_POST['alt_text'] ) ? sanitize_text_field( wp_unslash( $_POST['alt_text'] ) ) : '';

		// 1. Bảo mật: Xác thực quyền sửa bài viết trước khi gắn ảnh hoặc đặt làm ảnh đại diện
		if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
			$post_id     = 0;
			$is_featured = false;
		}

		// 2. Kiểm tra mã lỗi upload PHP
		if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] && 0 !== (int) $file['error'] ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Lỗi tải tệp tin từ trình duyệt (Mã lỗi: %d).', 'smart-clipboard-media-uploader' ), (int) $file['error'] ) ) );
		}

		// 3. Kiểm tra kích thước tệp tin tối đa cho phép trên máy chủ
		$max_size = wp_max_upload_size();
		if ( ! empty( $file['size'] ) && $file['size'] > $max_size ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Kích thước tệp vượt quá giới hạn tải lên của máy chủ (%s).', 'smart-clipboard-media-uploader' ), size_format( $max_size ) ) ) );
		}

		// 4. Kiểm tra tệp tạm có tồn tại và đọc được không
		if ( empty( $file['tmp_name'] ) || ! file_exists( $file['tmp_name'] ) || ! is_readable( $file['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Tệp tạm không tồn tại hoặc không thể đọc được trên máy chủ.', 'smart-clipboard-media-uploader' ) ) );
		}

		// 5. Kiểm tra định dạng hình ảnh thực tế (Magic Bytes qua getimagesize và MIME an toàn)
		$image_info    = @getimagesize( $file['tmp_name'] );
		$detected_mime = '';

		if ( false !== $image_info && ! empty( $image_info['mime'] ) ) {
			$detected_mime = strtolower( $image_info['mime'] );
		} else {
			// Fallback kiểm tra WebP hoặc AVIF (trên môi trường PHP chưa biên dịch getimagesize cho WebP/AVIF)
			if ( function_exists( 'finfo_open' ) ) {
				$finfo      = finfo_open( FILEINFO_MIME_TYPE );
				$finfo_mime = finfo_file( $finfo, $file['tmp_name'] );
				finfo_close( $finfo );
				if ( in_array( $finfo_mime, array( 'image/webp', 'image/avif' ), true ) ) {
					$detected_mime = $finfo_mime;
				}
			}
		}

		$allowed_mimes_map = array(
			'image/jpeg' => '.jpg',
			'image/png'  => '.png',
			'image/webp' => '.webp',
			'image/avif' => '.avif',
			'image/gif'  => '.gif',
		);

		if ( empty( $detected_mime ) || ! isset( $allowed_mimes_map[ $detected_mime ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Tệp tin không phải là hình ảnh hợp lệ. Chỉ chấp nhận các định dạng ảnh JPG, PNG, WEBP, AVIF, GIF.', 'smart-clipboard-media-uploader' ) ) );
		}

		// Đuôi file được lấy cố định từ MIME Type đã xác thực bởi máy chủ (Chặn tuyệt đối giả mạo file độc hại)
		$ext = $allowed_mimes_map[ $detected_mime ];

		// Tự động chuyển đổi định dạng sang WebP hoặc AVIF tức thì nếu được kích hoạt
		$convert_format = get_option( 'smart_paste_convert_format', 'webp' );
		$image_quality  = (int) get_option( 'smart_paste_image_quality', 82 );

		if ( 'original' !== $convert_format && in_array( $detected_mime, array( 'image/png', 'image/jpeg', 'image/gif' ), true ) ) {
			$converted = $this->convert_image_file( $file['tmp_name'], $convert_format, $image_quality );
			if ( $converted && ! empty( $converted['path'] ) && file_exists( $converted['path'] ) ) {
				if ( $converted['path'] !== $file['tmp_name'] ) {
					@unlink( $file['tmp_name'] );
				}
				$file['tmp_name']           = $converted['path'];
				$file['type']               = $converted['mime'];
				$file['size']               = filesize( $converted['path'] );
				$_FILES['file']['tmp_name'] = $converted['path'];
				$_FILES['file']['type']     = $converted['mime'];
				$_FILES['file']['size']     = $file['size'];
				$detected_mime              = $converted['mime'];
				$ext                        = $converted['ext'];
			}
		}

		// Xử lý Tối ưu Tên file chuẩn SEO
		$naming_scheme = get_option( 'smart_paste_naming_scheme', 'post_title' );
		$custom_prefix = get_option( 'smart_paste_custom_prefix', 'pasted-image' );
		if ( empty( $custom_prefix ) ) {
			$custom_prefix = 'pasted-image';
		}

		$base_slug   = '';
		$final_title = '';

		// 1. Nếu cấu hình đặt tên theo bài viết và có bài viết liên kết
		if ( 'post_title' === $naming_scheme && $post_id > 0 ) {
			$linked_post = get_post( $post_id );
			if ( $linked_post && ! empty( $linked_post->post_title ) ) {
				$base_slug   = sanitize_title( $linked_post->post_title );
				$final_title = $linked_post->post_title;
			}
		}

		// 2. Nếu ở giao diện tạo bài viết mới trước khi lưu nhưng có tiêu đề gửi lên
		if ( empty( $base_slug ) && ! empty( $post_title ) ) {
			$base_slug   = sanitize_title( $post_title );
			$final_title = $post_title;
		}

		// 3. Fallback theo tiền tố cấu hình
		if ( empty( $base_slug ) ) {
			$base_slug   = sanitize_title( $custom_prefix );
			$final_title = ucwords( str_replace( array( '-', '_' ), ' ', $base_slug ) );
		}

		$timestamp     = gmdate( 'Ymd-His' );
		$random_suffix = wp_generate_password( 4, false, false );
		$seo_filename  = $base_slug . '-' . $timestamp . '-' . $random_suffix . $ext;

		// Gán tên file chuẩn SEO vào biến file để wp_handle_upload lưu đúng tên
		$_FILES['file']['name'] = $seo_filename;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$upload_overrides = array(
			'test_form'   => false,
			'test_upload' => false,
		);

		if ( is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
			$uploaded_file = wp_handle_upload( $_FILES['file'], $upload_overrides );
		} else {
			$uploaded_file = wp_handle_sideload( $_FILES['file'], $upload_overrides );
		}

		if ( isset( $uploaded_file['error'] ) ) {
			wp_send_json_error( array( 'message' => $uploaded_file['error'] ) );
		}

		$file_path = $uploaded_file['file'];
		$file_url  = $uploaded_file['url'];
		$mime_type = $uploaded_file['type'];

		$attachment = array(
			'post_mime_type' => $mime_type,
			'post_title'     => $final_title,
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => $file_url,
		);

		$attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );

		if ( is_wp_error( $attach_id ) ) {
			wp_send_json_error( array( 'message' => $attach_id->get_error_message() ) );
		}

		// Sinh metadata (sub-sizes, thumbnails)
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Cập nhật Thẻ Alt Text chuẩn SEO
		$auto_alt = get_option( 'smart_paste_auto_alt', 1 );
		if ( ! empty( $alt_text ) ) {
			update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt_text );
		} elseif ( $auto_alt && ! empty( $final_title ) ) {
			update_post_meta( $attach_id, '_wp_attachment_image_alt', $final_title );
		}

		// Đánh dấu ảnh dán từ clipboard
		update_post_meta( $attach_id, '_smart_clipboard_pasted', 1 );

		// Đếm tổng số ảnh dán
		$count = (int) get_option( 'smart_paste_total_count', 0 ) + 1;
		update_option( 'smart_paste_total_count', $count, false );

		// Nếu chọn đặt làm ảnh đại diện
		$featured_html = '';
		if ( $is_featured && $post_id > 0 ) {
			set_post_thumbnail( $post_id, $attach_id );
			if ( function_exists( '_wp_post_thumbnail_html' ) ) {
				$featured_html = _wp_post_thumbnail_html( $attach_id, $post_id );
			}
		}

		// Kích thước ảnh
		$meta   = wp_get_attachment_metadata( $attach_id );
		$width  = ! empty( $meta['width'] ) ? $meta['width'] : 0;
		$height = ! empty( $meta['height'] ) ? $meta['height'] : 0;

		$thumb     = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
		$thumb_url = $thumb ? $thumb[0] : $file_url;

		// Dữ liệu chuẩn cho wp.media (Backbone Models)
		$attachment_js = wp_prepare_attachment_for_js( $attach_id );

		wp_send_json_success( array(
			'id'            => $attach_id,
			'url'           => $file_url,
			'thumbnail_url' => $thumb_url,
			'title'         => $final_title,
			'alt'           => get_post_meta( $attach_id, '_wp_attachment_image_alt', true ),
			'width'         => $width,
			'height'        => $height,
			'filename'      => basename( $file_path ),
			'filesize'      => size_format( file_exists( $file_path ) ? filesize( $file_path ) : 0 ),
			'attachment'    => $attachment_js,
			'featured_html' => $featured_html,
			'message'       => __( 'Tải ảnh từ Clipboard lên Thư viện Media thành công!', 'smart-clipboard-media-uploader' ),
		) );
	}

	/**
	 * Tự động quét và tải ảnh Base64 trong nội dung bài viết lên Thư viện Media khi Xuất bản / Lưu bài viết
	 * Tránh làm phình database và giúp người dùng dán nhầm ảnh không bị tốn dung lượng media.
	 *
	 * @param array $data    Mảng dữ liệu bài viết chuẩn bị lưu vào database.
	 * @param array $postarr Mảng dữ liệu thô gửi từ form.
	 * @return array
	 */
	public function process_post_content_base64_images( $data, $postarr ) {
		if ( empty( $data['post_content'] ) || false === strpos( $data['post_content'], 'data:image/' ) ) {
			return $data;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $data;
		}

		$post_id = ! empty( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;
		if ( $post_id > 0 && ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
			return $data;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return $data;
		}

		if ( ! (bool) get_option( 'smart_paste_enable_editor', 1 ) ) {
			return $data;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$post_title     = ! empty( $data['post_title'] ) ? $data['post_title'] : ( ! empty( $postarr['post_title'] ) ? $postarr['post_title'] : '' );
		$naming_scheme  = get_option( 'smart_paste_naming_scheme', 'post_title' );
		$custom_prefix  = get_option( 'smart_paste_custom_prefix', 'pasted-image' );
		if ( empty( $custom_prefix ) ) {
			$custom_prefix = 'pasted-image';
		}

		$convert_format = get_option( 'smart_paste_convert_format', 'webp' );
		$image_quality  = (int) get_option( 'smart_paste_image_quality', 82 );
		$auto_alt       = (bool) get_option( 'smart_paste_auto_alt', 1 );

		$allowed_mimes_map = array(
			'image/jpeg' => '.jpg',
			'image/png'  => '.png',
			'image/webp' => '.webp',
			'image/avif' => '.avif',
			'image/gif'  => '.gif',
		);

		// Tìm tất cả các thẻ <img> chứa src="data:image/..."
		$pattern = '/<img([^>]*?)src=["\'](data:image\/(png|jpe?g|gif|webp|avif);base64,([A-Za-z0-9+\/=\r\n]+))["\']([^>]*?)>/i';

		$img_index = 0;
		$content = preg_replace_callback( $pattern, function( $matches ) use (
			&$img_index, $post_id, $post_title, $naming_scheme, $custom_prefix,
			$convert_format, $image_quality, $auto_alt, $allowed_mimes_map
		) {
			$img_index++;
			$before_attrs = $matches[1];
			$base64_data  = $matches[4];
			$after_attrs  = $matches[5];

			$binary_data = base64_decode( preg_replace( '/\s+/', '', $base64_data ) );
			if ( false === $binary_data || empty( $binary_data ) ) {
				return $matches[0];
			}

			// Tạo file tạm an toàn
			$temp_file = wp_tempnam( 'smart_paste_' );
			if ( ! $temp_file ) {
				return $matches[0];
			}
			file_put_contents( $temp_file, $binary_data );

			// Xác thực file thực tế
			$image_info    = @getimagesize( $temp_file );
			$detected_mime = $image_info ? $image_info['mime'] : '';
			if ( empty( $detected_mime ) && function_exists( 'finfo_open' ) ) {
				$finfo         = finfo_open( FILEINFO_MIME_TYPE );
				$detected_mime = finfo_file( $finfo, $temp_file );
				finfo_close( $finfo );
			}

			if ( empty( $detected_mime ) || ! isset( $allowed_mimes_map[ $detected_mime ] ) ) {
				@unlink( $temp_file );
				return $matches[0];
			}

			$ext = $allowed_mimes_map[ $detected_mime ];

			// Chuyển đổi định dạng sang WebP / AVIF
			if ( 'original' !== $convert_format && in_array( $detected_mime, array( 'image/png', 'image/jpeg', 'image/gif' ), true ) ) {
				$converted = $this->convert_image_file( $temp_file, $convert_format, $image_quality );
				if ( $converted && ! empty( $converted['path'] ) && file_exists( $converted['path'] ) ) {
					if ( $converted['path'] !== $temp_file ) {
						@unlink( $temp_file );
					}
					$temp_file     = $converted['path'];
					$detected_mime = $converted['mime'];
					$ext           = $converted['ext'];
				}
			}

			// Tạo tên file chuẩn SEO
			$base_slug = '';
			if ( 'post_title' === $naming_scheme && ! empty( $post_title ) ) {
				$base_slug = sanitize_title( $post_title );
			}
			if ( empty( $base_slug ) ) {
				$base_slug = sanitize_title( $custom_prefix );
			}

			$timestamp     = gmdate( 'Ymd-His' );
			$random_suffix = wp_generate_password( 4, false, false );
			$seo_filename  = $base_slug . '-' . $timestamp . '-' . $random_suffix . '-' . $img_index . $ext;

			// Sideload vào WordPress Media Library
			$file_array = array(
				'name'     => $seo_filename,
				'tmp_name' => $temp_file,
			);

			$uploaded = wp_handle_sideload( $file_array, array( 'test_form' => false ) );
			if ( isset( $uploaded['error'] ) || empty( $uploaded['file'] ) ) {
				@unlink( $temp_file );
				return $matches[0];
			}

			$file_path = $uploaded['file'];
			$file_url  = $uploaded['url'];
			$mime_type = $uploaded['type'];

			$final_title = ! empty( $post_title ) ? $post_title . ' ' . $img_index : ucwords( str_replace( array( '-', '_' ), ' ', $base_slug ) );

			$attachment = array(
				'post_mime_type' => $mime_type,
				'post_title'     => $final_title,
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => $file_url,
			);

			$attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );
			if ( is_wp_error( $attach_id ) ) {
				return $matches[0];
			}

			$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			if ( $auto_alt ) {
				update_post_meta( $attach_id, '_wp_attachment_image_alt', $final_title );
			}
			update_post_meta( $attach_id, '_smart_clipboard_pasted', 1 );

			// Tăng bộ đếm
			$count = (int) get_option( 'smart_paste_total_count', 0 ) + 1;
			update_option( 'smart_paste_total_count', $count, false );

			// Xóa class tạm smart-paste-pending và thêm class chuẩn WordPress
			$all_attrs = trim( $before_attrs . ' ' . $after_attrs );
			$all_attrs = rtrim( $all_attrs, "/> \t\n\r\0\x0B" );
			$all_attrs = preg_replace( '/\bsmart-paste-pending\b/', '', $all_attrs );

			// Kiểm tra alt
			if ( false === strpos( $all_attrs, 'alt=' ) ) {
				$all_attrs .= ' alt="' . esc_attr( $final_title ) . '"';
			}

			// Đảm bảo class chứa wp-image-{id}
			if ( preg_match( '/class=["\']([^"\']*)["\']/', $all_attrs, $class_match ) ) {
				$classes   = trim( preg_replace( '/\s+/', ' ', $class_match[1] . " wp-image-{$attach_id}" ) );
				$all_attrs = preg_replace( '/class=["\'][^"\']*["\']/', 'class="' . esc_attr( $classes ) . '"', $all_attrs );
			} else {
				$all_attrs .= ' class="aligncenter size-full wp-image-' . $attach_id . '"';
			}

			// Lấy kích thước
			$meta   = wp_get_attachment_metadata( $attach_id );
			$width  = ! empty( $meta['width'] ) ? $meta['width'] : 0;
			$height = ! empty( $meta['height'] ) ? $meta['height'] : 0;
			if ( $width > 0 && false === strpos( $all_attrs, 'width=' ) ) {
				$all_attrs .= ' width="' . $width . '"';
			}
			if ( $height > 0 && false === strpos( $all_attrs, 'height=' ) ) {
				$all_attrs .= ' height="' . $height . '"';
			}

			return '<img src="' . esc_url( $file_url ) . '" ' . trim( $all_attrs ) . ' />';
		}, $data['post_content'] );

		if ( null !== $content ) {
			$data['post_content'] = $content;
		}

		return $data;
	}

	/**
	 * Trang Bảng điều khiển & Cài đặt Plugin
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'smart-clipboard-media-uploader' ) );
		}

		$total_pasted = (int) get_option( 'smart_paste_total_count', 0 );
		?>
		<div class="wrap smart-dashboard-wrap" style="max-width: 1100px; margin: 20px 20px 40px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
			
			<!-- HEADER BANNER -->
			<div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 30px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25); display: flex; justify-content: space-between; align-items: center;">
				<div>
					<h1 style="font-size: 26px; font-weight: 800; margin: 0 0 8px 0; color: #fff; display: flex; align-items: center; gap: 10px;">
						📋 Smart Clipboard Media Uploader
						<span style="font-size: 12px; background: rgba(59, 130, 246, 0.25); border: 1px solid rgba(59, 130, 246, 0.4); color: #93c5fd; padding: 2px 10px; border-radius: 99px; font-weight: 700;">v1.0.0</span>
					</h1>
					<p style="margin: 0; opacity: 0.85; font-size: 14px; max-width: 700px; line-height: 1.5;">
						Dán ảnh trực tiếp từ Clipboard (Ctrl + V hoặc Cmd + V) vào Thư viện Media, Trình soạn thảo bài viết và Ảnh đại diện siêu tốc không cần lưu file về máy tính. Tự động đổi tên chuẩn SEO!
					</p>
				</div>
				<div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); padding: 10px 18px; border-radius: 12px; font-size: 13px; text-align: right;">
					<div style="font-weight: 700; color: #38bdf8;">Khánh Toàn</div>
					<div style="color: #94a3b8; font-size: 12px;">mamxanhdigital.vn</div>
				</div>
			</div>

			<!-- STATS METRICS -->
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 25px;">
				<div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
					<div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Ảnh đã dán qua Clipboard</div>
					<div style="font-size: 32px; font-weight: 800; color: #16a34a; margin-top: 4px;"><?php echo number_format_i18n( $total_pasted ); ?></div>
					<div style="font-size: 12px; color: #64748b; margin-top: 4px;">Tiết kiệm hàng trăm thao tác tải file</div>
				</div>
				<div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
					<div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Vị trí hỗ trợ</div>
					<div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 8px;">Thư viện & Soạn thảo</div>
					<div style="font-size: 12px; color: #64748b; margin-top: 4px;">Gutenberg, Classic, Media Grid, Modal</div>
				</div>
				<div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
					<div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Chuẩn SEO</div>
					<div style="font-size: 20px; font-weight: 800; color: #2563eb; margin-top: 8px;">Tự động đặt tên</div>
					<div style="font-size: 12px; color: #64748b; margin-top: 4px;">Đổi tên theo tiêu đề bài viết & gán Alt Text</div>
				</div>
				<div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
					<div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Phối hợp hệ sinh thái</div>
					<div style="font-size: 20px; font-weight: 800; color: #7c3aed; margin-top: 8px;">Tương thích 100%</div>
					<div style="font-size: 12px; color: #64748b; margin-top: 4px;">Nén WebP/AVIF & Chặn ảnh con tự động</div>
				</div>
			</div>

			<!-- INTERACTIVE TEST BOX -->
			<div class="smart-settings-card" style="margin-bottom: 25px;">
				<h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">🎯 Khu vực Thử nghiệm Dán Ảnh (Interactive Test Zone)</h3>
				<p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">Bạn hãy chụp ảnh màn hình (hoặc bấm chuột phải copy ảnh từ trang web bất kỳ), sau đó <strong>bấm vào khung dưới đây và nhấn Ctrl + V (hoặc Cmd + V trên Mac)</strong> để xem ảnh được tải lên tức thì!</p>

				<div id="smart-paste-test-zone" class="smart-test-zone" tabindex="0">
					<div class="smart-test-icon">📋</div>
					<div class="smart-test-title">Bấm vào đây rồi nhấn Ctrl + V (Cmd + V) để dán ảnh</div>
					<div class="smart-test-sub">Hoặc kéo thả file ảnh trực tiếp từ máy tính vào khung này để thử nghiệm</div>
				</div>

				<div id="smart-paste-test-results" class="smart-test-results"></div>
			</div>

			<!-- SETTINGS FORM -->
			<div class="smart-settings-card">
				<h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">⚙️ Cấu hình tính năng</h3>

				<form method="post" action="options.php">
					<?php settings_fields( 'smart_clipboard_settings_group' ); ?>

					<table class="form-table" role="presentation" style="margin-top: 0;">
						<tr>
							<th scope="row">Thư viện Media (upload.php)</th>
							<td>
								<label style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
									<input type="checkbox" name="smart_paste_enable_media_library" value="1" <?php checked( 1, get_option( 'smart_paste_enable_media_library', 1 ) ); ?> />
									Bật dán ảnh trực tiếp trong Thư viện Media
								</label>
								<p class="description">Khi mở trang Thư viện Media (chế độ Lưới hoặc Danh sách), nhấn Ctrl+V ở bất kỳ đâu trên trang sẽ tải ảnh lên ngay lập tức.</p>
							</td>
						</tr>

						<tr>
							<th scope="row">Trình soạn thảo bài viết</th>
							<td>
								<label style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
									<input type="checkbox" name="smart_paste_enable_editor" value="1" <?php checked( 1, get_option( 'smart_paste_enable_editor', 1 ) ); ?> />
									Bật dán ảnh trong Trình soạn thảo (Gutenberg & Classic Editor)
								</label>
								<p class="description">Chặn chuỗi Base64 làm phình cơ sở dữ liệu. Hỗ trợ cả Trình soạn thảo Cổ điển (Classic Editor) và Khối (Gutenberg).</p>
							</td>
						</tr>

						<tr>
							<th scope="row">Thời điểm tải ảnh vào Thư viện</th>
							<td>
								<fieldset>
									<label style="display: block; margin-bottom: 8px; font-weight: 600;">
										<input type="radio" name="smart_paste_editor_mode" value="on_save" <?php checked( 'on_save', get_option( 'smart_paste_editor_mode', 'on_save' ) ); ?> />
										<span style="color: #16a34a; font-weight: 700;">Chỉ tải lên Thư viện khi Xuất bản / Lưu bài viết (Khuyên dùng)</span>
									</label>
									<p class="description" style="margin: 0 0 12px 24px; line-height: 1.6;">
										Khi dán ảnh vào bài viết, ảnh hiển thị tức thì 0 giây dưới dạng xem trước. Chỉ khi bạn bấm <strong>"Xuất bản"</strong>, <strong>"Cập nhật"</strong> hoặc <strong>"Lưu bản nháp"</strong> thì ảnh mới thực sự được nén WebP và tải vào Thư viện Media. <em>Nếu dán nhầm ảnh và xóa đi thì không hề bị tốn dung lượng hosting!</em>
									</p>

									<label style="display: block; margin-bottom: 8px; font-weight: 600;">
										<input type="radio" name="smart_paste_editor_mode" value="instant" <?php checked( 'instant', get_option( 'smart_paste_editor_mode', 'on_save' ) ); ?> />
										<span>Tải lên Thư viện Media ngay lập tức khi vừa nhấn Ctrl + V</span>
									</label>
									<p class="description" style="margin: 0 0 0 24px;">Ảnh sẽ được tải lên thư viện ngay khi vừa dán. Phù hợp nếu bạn muốn ảnh có ID thư viện ngay lập tức trong bài viết.</p>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row">Ảnh đại diện (Featured Image)</th>
							<td>
								<label style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
									<input type="checkbox" name="smart_paste_enable_featured" value="1" <?php checked( 1, get_option( 'smart_paste_enable_featured', 1 ) ); ?> />
									Bật vùng dán ảnh đại diện siêu tốc
								</label>
								<p class="description">Hiển thị khung "Dán ảnh (Ctrl+V) làm Ảnh đại diện" trong hộp thoại Ảnh đại diện của bài viết.</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="smart_paste_naming_scheme">Quy tắc đặt tên file ảnh (SEO)</label></th>
							<td>
								<select id="smart_paste_naming_scheme" name="smart_paste_naming_scheme" style="min-width: 320px;">
									<option value="post_title" <?php selected( 'post_title', get_option( 'smart_paste_naming_scheme', 'post_title' ) ); ?>>Theo tiêu đề bài viết (Khuyên dùng cho SEO)</option>
									<option value="prefix_datetime" <?php selected( 'prefix_datetime', get_option( 'smart_paste_naming_scheme', 'post_title' ) ); ?>>Theo tiền tố cố định + Ngày giờ (Ví dụ: clipboard-20260924-120000.png)</option>
								</select>
								<p class="description">Giúp ảnh chụp màn hình tự động có tên chuẩn SEO thay vì tên mặc định <code>image.png</code> của clipboard.</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="smart_paste_convert_format">Định dạng nén ảnh tự động</label></th>
							<td>
								<select id="smart_paste_convert_format" name="smart_paste_convert_format" style="min-width: 320px;">
									<option value="webp" <?php selected( 'webp', get_option( 'smart_paste_convert_format', 'webp' ) ); ?>>Chuyển đổi sang WebP (Khuyên dùng - Nén siêu nhẹ, nét căng)</option>
									<option value="avif" <?php selected( 'avif', get_option( 'smart_paste_convert_format', 'webp' ) ); ?>>Chuyển đổi sang AVIF (Thế hệ mới nhất, tự động sang WebP nếu chưa hỗ trợ)</option>
									<option value="original" <?php selected( 'original', get_option( 'smart_paste_convert_format', 'webp' ) ); ?>>Giữ nguyên định dạng gốc từ Clipboard (PNG/JPG)</option>
								</select>
								<p class="description">Khi dán ảnh chụp màn hình từ Clipboard (thường là file PNG nặng hàng MB), hệ thống sẽ nén và đổi đuôi sang WebP tức thì trước khi lưu vào Thư viện.</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="smart_paste_image_quality">Chất lượng hình ảnh nén</label></th>
							<td>
								<input type="number" id="smart_paste_image_quality" name="smart_paste_image_quality" min="60" max="100" value="<?php echo esc_attr( get_option( 'smart_paste_image_quality', 82 ) ); ?>" class="small-text" /> %
								<p class="description">Chất lượng nén đề xuất là 82% (cân bằng hoàn hảo giữa độ sắc nét nguyên bản và dung lượng siêu nhẹ).</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="smart_paste_custom_prefix">Tiền tố tên ảnh mặc định</label></th>
							<td>
								<input type="text" id="smart_paste_custom_prefix" name="smart_paste_custom_prefix" value="<?php echo esc_attr( get_option( 'smart_paste_custom_prefix', 'pasted-image' ) ); ?>" class="regular-text" />
								<p class="description">Sử dụng khi tải ảnh ngoài Thư viện Media hoặc khi bài viết chưa có tiêu đề.</p>
							</td>
						</tr>

						<tr>
							<th scope="row">Thẻ Alt Text tự động</th>
							<td>
								<label style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
									<input type="checkbox" name="smart_paste_auto_alt" value="1" <?php checked( 1, get_option( 'smart_paste_auto_alt', 1 ) ); ?> />
									Tự động điền Thẻ Alt Text cho ảnh theo tiêu đề bài viết
								</label>
								<p class="description">Tối ưu 100% điểm SEO hình ảnh trong Rank Math và Yoast SEO mà không cần gõ thủ công.</p>
							</td>
						</tr>

						<tr>
							<th scope="row">Giao diện phản hồi</th>
							<td>
								<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 8px;">
									<input type="checkbox" name="smart_paste_show_toast" value="1" <?php checked( 1, get_option( 'smart_paste_show_toast', 1 ) ); ?> />
									Hiển thị thông báo Toast nổi góc màn hình khi tải ảnh
								</label>
								<label style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
									<input type="checkbox" name="smart_paste_play_sound" value="1" <?php checked( 1, get_option( 'smart_paste_play_sound', 1 ) ); ?> />
									Phát âm thanh nhẹ vui tai khi dán ảnh thành công
								</label>
							</td>
						</tr>
					</table>

					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Lưu Thay Đổi Cài Đặt', 'smart-clipboard-media-uploader' ); ?>" />
					</p>
				</form>
			</div>

			<!-- TIPS & INSTRUCTIONS -->
			<div class="smart-settings-card" style="margin-top: 25px; background: #f8fafc; border-left: 4px solid #2563eb;">
				<h4 style="margin: 0 0 10px 0; font-size: 15px; color: #0f172a;">💡 Mẹo chụp ảnh màn hình nhanh trên máy tính:</h4>
				<ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #475569; line-height: 1.7;">
					<li><strong>Windows:</strong> Nhấn tổ hợp phím <code>Windows + Shift + S</code> &rarr; Quét chọn vùng cần chụp &rarr; Mở WordPress và nhấn <code>Ctrl + V</code> để dán ảnh ngay.</li>
					<li><strong>macOS:</strong> Nhấn tổ hợp phím <code>Cmd + Shift + 4</code> (sau đó nhấn kèm <code>Ctrl</code> khi chụp để lưu vào clipboard) hoặc <code>Cmd + Ctrl + Shift + 4</code> &rarr; Quét chọn &rarr; Mở WordPress và nhấn <code>Cmd + V</code>.</li>
					<li><strong>Dán ảnh từ Website khác:</strong> Bấm chuột phải vào bất kỳ hình ảnh nào trên web &rarr; Chọn <strong>"Sao chép hình ảnh" (Copy Image)</strong> &rarr; Nhấn <code>Ctrl + V</code> trong WordPress.</li>
				</ul>
			</div>

		</div>
		<?php
	}
}

// Khởi chạy Plugin
Smart_Clipboard_Media_Uploader::get_instance();
