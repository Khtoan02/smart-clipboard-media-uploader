# Smart Clipboard Media Uploader

Plugin WordPress hỗ trợ dán ảnh trực tiếp từ Clipboard (`Ctrl + V` trên Windows/Linux hoặc `Cmd + V` trên macOS) vào Thư viện Media, Trình soạn thảo bài viết (Gutenberg, Classic Editor TinyMCE, Text mode) và Ảnh đại diện (Featured Image) siêu tốc mà không cần tải ảnh về máy tính.

---

## 🌟 Tính Năng Nổi Bật

1. **Dán ảnh mọi lúc, mọi nơi trong WordPress Admin**:
   - **Thư viện Media (`upload.php`)**: Ở cả chế độ Lưới (Grid) và Danh sách (List), chỉ cần nhấn `Ctrl+V`, ảnh sẽ được tải lên và hiển thị ngay ở đầu danh sách mà không cần reload trang.
   - **Hộp thoại Thêm Media (`wp.media` modal)**: Đang mở popup chọn ảnh hoặc thư viện, nhấn `Ctrl+V` ảnh sẽ tải lên và tự động tick chọn sẵn để chèn.
   - **Trình soạn thảo Classic Editor (TinyMCE)**: Chặn chuỗi Base64 cực dài làm phình cơ sở dữ liệu; tự động upload và chèn thẻ ảnh chuẩn WordPress tại vị trí con trỏ chuột.
   - **Trình soạn thảo Block Editor (Gutenberg)**: Tự động chèn Block Image với ảnh vừa dán.
   - **Trình soạn thảo Văn bản (HTML/Quicktags)**: Dán ảnh trực tiếp vào textarea `#content`.
   - **Ảnh đại diện (Featured Image)**: Khung dán ảnh nhanh trong hộp thoại Ảnh đại diện.
   - **Kéo thả thông minh (Drag & Drop)**: Hỗ trợ kéo thả ảnh từ màn hình hoặc trình duyệt khác vào WordPress với hiệu ứng trực quan.

2. **Tự động tối ưu tên file chuẩn SEO (Smart SEO Naming)**:
   - Thay vì lưu các file vô nghĩa như `image.png`, `image-1.png`... plugin tự động đổi tên file theo Tiêu đề bài viết (Slugified) kết hợp mốc thời gian.
   - Tự động điền thẻ Alt Text cho ảnh theo tiêu đề bài viết.

3. **Phối hợp hoàn hảo với Hệ sinh thái Smart Tools**:
   - Tự động tích hợp nén WebP/AVIF qua `smart-image-converter-compressor`.
   - Tự động chặn tạo ảnh con rác qua `smart-subsize-suppressor`.
   - Tự động tối ưu đường dẫn qua `smart-media-seo-router`.

4. **Trải nghiệm người dùng cao cấp**:
   - Thông báo Toast nổi hiện đại góc màn hình kèm thumbnail xem trước, kích thước và link sao chép nhanh.
   - Âm thanh phản hồi nhẹ nhàng khi dán thành công qua Web Audio API.
   - Khu vực Interactive Test Zone trong trang cài đặt để thử nghiệm ngay lập tức.

---

## 🚀 Hướng Dẫn Sử Dụng Nhanh

1. **Chụp ảnh màn hình:**
   - **Windows:** Bấm `Win + Shift + S` -> quét chọn vùng cần chụp.
   - **macOS:** Bấm `Cmd + Ctrl + Shift + 4` -> quét chọn vùng cần chụp.
2. **Hoặc sao chép ảnh từ web:** Bấm chuột phải vào bất kỳ ảnh nào trên trình duyệt -> Chọn **Sao chép hình ảnh (Copy Image)**.
3. **Mở WordPress:** Vào Thư viện Media hoặc trang Viết bài mới -> Bấm `Ctrl + V` (hoặc `Cmd + V`).
4. **Hoàn tất:** Ảnh được tải lên ngay tức thì!
