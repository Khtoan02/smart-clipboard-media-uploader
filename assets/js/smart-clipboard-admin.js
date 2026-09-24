/**
 * Smart Clipboard Media Uploader - Admin Script
 * Tải ảnh trực tiếp từ Clipboard (Ctrl+V / Cmd+V) vào Thư viện Media và Trình soạn thảo
 * Author: Khánh Toàn | https://mamxanhdigital.vn
 */

(function($) {
    'use strict';

    if (typeof smartClipboardVars === 'undefined') {
        return;
    }

    var vars = smartClipboardVars;
    var settings = vars.settings || {};

    // -------------------------------------------------------------------------
    // 1. WEB AUDIO API CHIME (Âm thanh phản hồi dễ chịu khi dán thành công)
    // -------------------------------------------------------------------------
    function playSuccessChime() {
        if (!settings.playSound) return;
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            var ctx = new AudioCtx();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // Note D5
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12); // Note A5

            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.28);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.28);
        } catch (e) {
            // AudioContext might be blocked until user gesture, ignore silently
        }
    }

    // -------------------------------------------------------------------------
    // 2. TOAST NOTIFICATION SYSTEM
    // -------------------------------------------------------------------------
    var $toastContainer = null;

    function getToastContainer() {
        if (!$toastContainer || !$toastContainer.length) {
            $toastContainer = $('#smart-clipboard-toasts');
            if (!$toastContainer.length) {
                $toastContainer = $('<div id="smart-clipboard-toasts"></div>').appendTo('body');
            }
        }
        return $toastContainer;
    }

    function showToast(type, title, desc, meta, actions) {
        if (!settings.showToast && type !== 'loading') {
            return { update: function() {}, close: function() {} };
        }

        var $container = getToastContainer();
        var iconHtml = '📋';
        if (type === 'loading') {
            iconHtml = '<div class="smart-toast-spinner"></div>';
        } else if (type === 'success') {
            iconHtml = '✅';
        } else if (type === 'error') {
            iconHtml = '⚠️';
        }

        var $toast = $('<div class="smart-paste-toast smart-toast-' + type + '">' +
            '<div class="smart-toast-icon">' + iconHtml + '</div>' +
            '<div class="smart-toast-body">' +
                '<div class="smart-toast-title">' + (title || '') + '</div>' +
                (desc ? '<div class="smart-toast-desc">' + desc + '</div>' : '') +
            '</div>' +
            '<button type="button" class="smart-toast-close" title="Đóng">&times;</button>' +
            (type === 'success' ? '<div class="smart-toast-progress"></div>' : '') +
        '</div>');

        // Meta media info
        if (meta && meta.thumbnail_url) {
            var $media = $('<div class="smart-toast-media">' +
                '<img src="' + meta.thumbnail_url + '" class="smart-toast-thumb" alt="Thumbnail" />' +
                '<div class="smart-toast-meta">' +
                    '<span class="smart-toast-filename" title="' + (meta.filename || '') + '">' + (meta.filename || 'Ảnh Clipboard') + '</span>' +
                    '<div class="smart-toast-filesize">' + (meta.filesize || '') + ' (' + (meta.width || 0) + 'x' + (meta.height || 0) + ')</div>' +
                '</div>' +
            '</div>');
            $toast.find('.smart-toast-body').append($media);
        }

        // Actions buttons
        if (actions && actions.length) {
            var $actionsWrap = $('<div class="smart-toast-actions"></div>');
            $.each(actions, function(i, act) {
                var $btn = $('<button type="button" class="smart-toast-btn ' + (act.primary ? 'smart-toast-btn-primary' : '') + '">' + act.label + '</button>');
                $btn.on('click', function(e) {
                    e.preventDefault();
                    if (typeof act.onClick === 'function') {
                        act.onClick();
                    }
                });
                $actionsWrap.append($btn);
            });
            $toast.find('.smart-toast-body').append($actionsWrap);
        }

        $container.append($toast);

        var autoCloseTimer = null;
        if (type === 'success') {
            autoCloseTimer = setTimeout(function() {
                closeToast();
            }, 4500);
        }

        function closeToast() {
            if (autoCloseTimer) clearTimeout(autoCloseTimer);
            $toast.addClass('smart-toast-fadeout');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }

        $toast.find('.smart-toast-close').on('click', closeToast);

        return {
            update: function(newType, newTitle, newDesc, newMeta, newActions) {
                if (autoCloseTimer) clearTimeout(autoCloseTimer);
                $toast.removeClass('smart-toast-loading smart-toast-success smart-toast-error').addClass('smart-toast-' + newType);

                var newIcon = '📋';
                if (newType === 'loading') newIcon = '<div class="smart-toast-spinner"></div>';
                else if (newType === 'success') newIcon = '✅';
                else if (newType === 'error') newIcon = '⚠️';

                $toast.find('.smart-toast-icon').html(newIcon);
                $toast.find('.smart-toast-title').text(newTitle || '');
                $toast.find('.smart-toast-desc').text(newDesc || '');

                $toast.find('.smart-toast-media').remove();
                if (newMeta && newMeta.thumbnail_url) {
                    var $m = $('<div class="smart-toast-media">' +
                        '<img src="' + newMeta.thumbnail_url + '" class="smart-toast-thumb" alt="Thumbnail" />' +
                        '<div class="smart-toast-meta">' +
                            '<span class="smart-toast-filename" title="' + (newMeta.filename || '') + '">' + (newMeta.filename || 'Ảnh Clipboard') + '</span>' +
                            '<div class="smart-toast-filesize">' + (newMeta.filesize || '') + ' (' + (newMeta.width || 0) + 'x' + (newMeta.height || 0) + ')</div>' +
                        '</div>' +
                    '</div>');
                    $toast.find('.smart-toast-body').append($m);
                }

                $toast.find('.smart-toast-actions').remove();
                if (newActions && newActions.length) {
                    var $aw = $('<div class="smart-toast-actions"></div>');
                    $.each(newActions, function(i, act) {
                        var $b = $('<button type="button" class="smart-toast-btn ' + (act.primary ? 'smart-toast-btn-primary' : '') + '">' + act.label + '</button>');
                        $b.on('click', function(e) {
                            e.preventDefault();
                            if (typeof act.onClick === 'function') act.onClick();
                        });
                        $aw.append($b);
                    });
                    $toast.find('.smart-toast-body').append($aw);
                }

                if (newType === 'success') {
                    playSuccessChime();
                    $toast.find('.smart-toast-progress').remove();
                    $toast.append('<div class="smart-toast-progress"></div>');
                    autoCloseTimer = setTimeout(closeToast, 4500);
                }
            },
            close: closeToast
        };
    }

    // -------------------------------------------------------------------------
    // 3. CLIPBOARD EXTRACTION HELPER
    // -------------------------------------------------------------------------
    function extractImageFromClipboard(event) {
        var clipboardData = event.clipboardData || (event.originalEvent && event.originalEvent.clipboardData) || window.clipboardData;
        if (!clipboardData) return null;

        // 1. Check files
        if (clipboardData.files && clipboardData.files.length > 0) {
            for (var i = 0; i < clipboardData.files.length; i++) {
                var file = clipboardData.files[i];
                if (file && file.type && file.type.indexOf('image/') === 0) {
                    return file;
                }
            }
        }

        // 2. Check items (blobs from screenshots)
        if (clipboardData.items && clipboardData.items.length > 0) {
            for (var j = 0; j < clipboardData.items.length; j++) {
                var item = clipboardData.items[j];
                if (item && item.kind === 'file' && item.type && item.type.indexOf('image/') === 0) {
                    var blob = item.getAsFile();
                    if (blob) return blob;
                }
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // 4. POST METADATA HELPER (Post ID, Title, SEO Slug)
    // -------------------------------------------------------------------------
    function getPostId() {
        var id = parseInt($('#post_ID').val(), 10);
        if (id && !isNaN(id)) return id;

        // Gutenberg check
        if (window.wp && wp.data && wp.data.select && wp.data.select('core/editor')) {
            var gId = wp.data.select('core/editor').getCurrentPostId();
            if (gId) return gId;
        }

        return 0;
    }

    function getPostTitle() {
        // Classic Editor / Standard Title
        var $title = $('#title');
        if ($title.length && $title.val().trim()) {
            return $title.val().trim();
        }

        // Gutenberg Title
        if (window.wp && wp.data && wp.data.select && wp.data.select('core/editor')) {
            var gTitle = wp.data.select('core/editor').getEditedPostAttribute('title');
            if (gTitle && gTitle.trim()) return gTitle.trim();
        }

        return '';
    }

    // -------------------------------------------------------------------------
    // 5. CORE UPLOAD FUNCTION
    // -------------------------------------------------------------------------
    function uploadImageFile(file, options) {
        options = options || {};
        var postId = options.postId || getPostId();
        var postTitle = options.postTitle || getPostTitle();
        var isFeatured = options.isFeatured ? 1 : 0;

        var toast = showToast('loading', 'Đang tải ảnh từ Clipboard...', 'Đang xử lý và lưu vào Thư viện Media...');

        var formData = new FormData();
        var filename = file.name || 'clipboard.png';
        if (filename === 'blob' || filename === 'image.png') {
            filename = 'clipboard-' + Date.now() + '.png';
        }

        formData.append('file', file, filename);
        formData.append('action', 'smart_clipboard_upload_image');
        formData.append('nonce', vars.nonce);
        formData.append('post_id', postId);
        formData.append('title', postTitle);
        formData.append('alt_text', postTitle);
        formData.append('is_featured', isFeatured);

        $.ajax({
            url: vars.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                // Optional progress handler
                return xhr;
            },
            success: function(res) {
                if (res && res.success && res.data) {
                    var data = res.data;

                    // Update toast
                    var actions = [];
                    if (data.url) {
                        actions.push({
                            label: '🔗 Sao chép URL',
                            onClick: function() {
                                navigator.clipboard.writeText(data.url).then(function() {
                                    alert('Đã sao chép đường dẫn ảnh vào bộ nhớ tạm:\n' + data.url);
                                });
                            }
                        });
                    }

                    toast.update('success', 'Đã tải ảnh lên Thư viện Media!', 'Ảnh đã sẵn sàng sử dụng.', data, actions);

                    if (typeof options.onSuccess === 'function') {
                        options.onSuccess(data);
                    }
                } else {
                    var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Có lỗi không xác định xảy ra khi tải ảnh!';
                    toast.update('error', 'Lỗi tải ảnh từ Clipboard', errMsg);
                    if (typeof options.onError === 'function') {
                        options.onError(errMsg);
                    }
                }
            },
            error: function(xhr, status, error) {
                var msg = 'Lỗi kết nối máy chủ (' + (error || status) + ')';
                toast.update('error', 'Lỗi kết nối', msg);
                if (typeof options.onError === 'function') {
                    options.onError(msg);
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // 6. CLASSIC EDITOR (TinyMCE) INTEGRATION
    // -------------------------------------------------------------------------
    function initTinyMCE() {
        if (!settings.enableEditor) return;

        function bindEditor(editor) {
            if (!editor) return;

            editor.on('init', function() {
                editor.on('paste', function(e) {
                    var file = extractImageFromClipboard(e);
                    if (!file) return;

                    // Chặn hành vi dán mặc định của trình duyệt
                    e.preventDefault();
                    e.stopPropagation();

                    // Chế độ 1: Chỉ tải lên khi Xuất bản / Lưu bài viết (on_save) - Mặc định & khuyên dùng
                    if (settings.editorMode === 'on_save') {
                        var reader = new FileReader();
                        reader.onload = function(loadEvent) {
                            var base64Data = loadEvent.target.result;
                            var previewHtml = '<p><img src="' + base64Data + '" alt="" class="aligncenter size-full smart-paste-pending" /></p>';
                            editor.insertContent(previewHtml);
                            editor.fire('change');

                            if (settings.showToast) {
                                showToast('success', 'Đã dán ảnh xem trước', 'Ảnh sẽ tự động nén WebP & tải lên Thư viện khi bạn Xuất bản hoặc Lưu bài viết.');
                            }
                        };
                        reader.readAsDataURL(file);
                        return;
                    }

                    // Chế độ 2: Tải lên ngay lập tức khi vừa dán (instant)
                    uploadImageFile(file, {
                        postId: getPostId(),
                        postTitle: getPostTitle(),
                        onSuccess: function(data) {
                            var imgHtml = '<p><img src="' + data.url + '" alt="' + (data.alt || data.title) + '" width="' + data.width + '" height="' + data.height + '" class="aligncenter size-full wp-image-' + data.id + '" /></p>';
                            editor.insertContent(imgHtml);
                            editor.fire('change');
                        }
                    });
                });
            });
        }

        if (window.tinymce) {
            tinymce.on('AddEditor', function(e) {
                bindEditor(e.editor);
            });
            if (tinymce.editors && tinymce.editors.length) {
                for (var i = 0; i < tinymce.editors.length; i++) {
                    bindEditor(tinymce.editors[i]);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // 7. TEXTAREA / QUICKTAGS HTML EDITOR INTEGRATION
    // -------------------------------------------------------------------------
    function initTextEditor() {
        if (!settings.enableEditor) return;

        $(document).on('paste', '#content', function(e) {
            var file = extractImageFromClipboard(e);
            if (!file) return;

            e.preventDefault();
            var $textarea = $(this);
            var cursorPos = $textarea.prop('selectionStart') || 0;
            var textVal = $textarea.val();
            var textBefore = textVal.substring(0, cursorPos);
            var textAfter = textVal.substring(cursorPos);

            if (settings.editorMode === 'on_save') {
                var reader = new FileReader();
                reader.onload = function(loadEvent) {
                    var base64Data = loadEvent.target.result;
                    var imgTag = '\n<img src="' + base64Data + '" alt="" class="aligncenter size-full smart-paste-pending" />\n';
                    $textarea.val(textBefore + imgTag + textAfter);
                    $textarea.prop('selectionStart', cursorPos + imgTag.length);
                    $textarea.prop('selectionEnd', cursorPos + imgTag.length);
                    $textarea.trigger('input').trigger('change');

                    if (settings.showToast) {
                        showToast('success', 'Đã dán ảnh xem trước', 'Ảnh sẽ tự động nén WebP & tải lên Thư viện khi bạn Xuất bản hoặc Lưu bài viết.');
                    }
                };
                reader.readAsDataURL(file);
                return;
            }

            uploadImageFile(file, {
                postId: getPostId(),
                postTitle: getPostTitle(),
                onSuccess: function(data) {
                    var imgTag = '\n<img src="' + data.url + '" alt="' + (data.alt || data.title) + '" width="' + data.width + '" height="' + data.height + '" class="aligncenter size-full wp-image-' + data.id + '" />\n';
                    $textarea.val(textBefore + imgTag + textAfter);
                    $textarea.prop('selectionStart', cursorPos + imgTag.length);
                    $textarea.prop('selectionEnd', cursorPos + imgTag.length);
                    $textarea.trigger('input').trigger('change');
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // 8. GUTENBERG (BLOCK EDITOR) INTEGRATION
    // -------------------------------------------------------------------------
    function initGutenberg() {
        if (!settings.enableEditor) return;

        // Check if Gutenberg is running
        var isGutenberg = $('body').hasClass('block-editor-page') || (window.wp && wp.data && wp.data.select && wp.data.select('core/editor'));
        if (!isGutenberg) return;

        // Catch paste on block editor writing canvas
        $(document).on('paste', function(e) {
            // If media modal is open, let modal handler handle it
            if (isMediaModalOpen()) return;

            // If user is focused inside a standard text input (title, URL bar, meta field), ignore
            var activeEl = document.activeElement;
            if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'SELECT')) {
                return;
            }

            var file = extractImageFromClipboard(e);
            if (!file) return;

            // Only intercept if we are on Gutenberg page and not in another custom dropzone
            if ($(e.target).closest('.smart-test-zone, .smart-paste-featured-zone').length) {
                return;
            }

            // Check if Gutenberg's block editor container is the focus
            if ($(e.target).closest('.edit-post-visual-editor, .block-editor-writing-flow, .interface-interface-skeleton__content').length || activeEl === document.body) {
                e.preventDefault();
                e.stopPropagation();

                if (settings.editorMode === 'on_save') {
                    var reader = new FileReader();
                    reader.onload = function(loadEvent) {
                        var base64Data = loadEvent.target.result;
                        if (window.wp && wp.blocks && wp.data && wp.data.dispatch) {
                            var imageBlock = wp.blocks.createBlock('core/image', {
                                url: base64Data,
                                alt: '',
                                caption: '',
                                className: 'smart-paste-pending'
                            });
                            wp.data.dispatch('core/block-editor').insertBlocks(imageBlock);

                            if (settings.showToast) {
                                showToast('success', 'Đã dán ảnh xem trước', 'Ảnh sẽ tự động nén WebP & tải lên Thư viện khi bạn Xuất bản hoặc Lưu bài viết.');
                            }
                        }
                    };
                    reader.readAsDataURL(file);
                    return;
                }

                uploadImageFile(file, {
                    postId: getPostId(),
                    postTitle: getPostTitle(),
                    onSuccess: function(data) {
                        if (window.wp && wp.blocks && wp.data && wp.data.dispatch) {
                            var imageBlock = wp.blocks.createBlock('core/image', {
                                id: data.id,
                                url: data.url,
                                alt: data.alt || '',
                                caption: '',
                                sizeSlug: 'full'
                            });
                            wp.data.dispatch('core/block-editor').insertBlocks(imageBlock);
                        }
                    }
                });
            }
        });
    }

    // -------------------------------------------------------------------------
    // 9. MEDIA MODAL POPUP (wp.media) INTEGRATION
    // -------------------------------------------------------------------------
    function isMediaModalOpen() {
        if ($('.media-modal:visible').length > 0) return true;
        if (window.wp && wp.media && wp.media.frame && wp.media.frame.isOpen && wp.media.frame.isOpen()) return true;
        return false;
    }

    function initMediaModal() {
        $(document).on('paste', function(e) {
            if (!isMediaModalOpen()) return;

            var file = extractImageFromClipboard(e);
            if (!file) return;

            e.preventDefault();
            e.stopPropagation();

            uploadImageFile(file, {
                postId: getPostId(),
                postTitle: getPostTitle(),
                onSuccess: function(data) {
                    if (window.wp && wp.media && wp.media.frame) {
                        var frame = wp.media.frame;
                        var attachment = wp.media.model.Attachment.create(data.attachment);

                        // Add to library collection
                        var state = frame.state();
                        if (state) {
                            var library = state.get('library');
                            if (library) {
                                library.add(attachment, { at: 0 });
                            }
                            var selection = state.get('selection');
                            if (selection) {
                                selection.reset([attachment]);
                            }
                        }

                        // Also add to view collection
                        if (frame.content && frame.content.get() && frame.content.get().collection) {
                            frame.content.get().collection.add(attachment, { at: 0 });
                        }
                    }
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // 10. MEDIA LIBRARY (upload.php) INTEGRATION
    // -------------------------------------------------------------------------
    function initMediaLibrary() {
        if (!settings.enableMediaLibrary) return;
        if (window.pagenow !== 'upload') return;

        $(document).on('paste', function(e) {
            // Ignore if in search box or text inputs
            var activeEl = document.activeElement;
            if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'SELECT')) {
                // If it's a file paste, still accept it!
                var fileCheck = extractImageFromClipboard(e);
                if (!fileCheck) return;
            }

            var file = extractImageFromClipboard(e);
            if (!file) return;

            e.preventDefault();
            e.stopPropagation();

            uploadImageFile(file, {
                onSuccess: function(data) {
                    // If in Grid view: Add directly to wp.media collection!
                    if (window.wp && wp.media && wp.media.frame && wp.media.frame.content && wp.media.frame.content.get()) {
                        var content = wp.media.frame.content.get();
                        if (content.collection) {
                            var model = wp.media.model.Attachment.create(data.attachment);
                            content.collection.add(model, { at: 0 });

                            // Highlight the newly created item
                            setTimeout(function() {
                                var $el = $('.attachments .attachment[data-id="' + data.id + '"]');
                                if ($el.length) {
                                    $el.css({
                                        'box-shadow': '0 0 0 3px #16a34a, 0 10px 20px rgba(22, 163, 74, 0.35)',
                                        'transition': 'all 0.5s ease',
                                        'transform': 'scale(1.04)'
                                    });
                                    setTimeout(function() {
                                        $el.css({ 'box-shadow': '', 'transform': '' });
                                    }, 2500);
                                }
                            }, 300);
                        }
                    } else {
                        // In List view: show reload notice or insert
                        var $notice = $('<div class="notice notice-success is-dismissible" style="margin-top:15px;"><p><strong>Đã dán ảnh thành công:</strong> ' + data.title + ' (<a href="' + data.url + '" target="_blank">Xem ảnh</a>). <a href="javascript:location.reload()">Nhấn vào đây để tải lại trang</a>.</p></div>');
                        $('.wrap > hr.wp-header-end').after($notice);
                    }
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // 11. FEATURED IMAGE (Ảnh đại diện) INTEGRATION
    // -------------------------------------------------------------------------
    function initFeaturedImageZone() {
        if (!settings.enableFeatured) return;

        // Classic Editor Featured Image Box (#postimagediv)
        var $postimagediv = $('#postimagediv');
        if ($postimagediv.length) {
            var $zone = $('<div class="smart-paste-featured-zone" tabindex="0" title="Nhấn vào đây rồi bấm Ctrl+V để đặt ảnh đại diện">' +
                '<div class="smart-zone-title">📋 Dán ảnh (Ctrl+V) làm Ảnh đại diện</div>' +
                '<div class="smart-zone-desc">Hoặc kéo thả ảnh trực tiếp vào đây</div>' +
            '</div>');

            $postimagediv.find('.inside').append($zone);

            // Click focus
            $zone.on('click', function() {
                $(this).focus();
            });

            // Paste on zone
            $zone.on('paste', function(e) {
                var file = extractImageFromClipboard(e);
                if (!file) return;

                e.preventDefault();
                e.stopPropagation();

                uploadImageFile(file, {
                    postId: getPostId(),
                    postTitle: getPostTitle(),
                    isFeatured: true,
                    onSuccess: function(data) {
                        if (data.featured_html) {
                            $postimagediv.find('.inside').html(data.featured_html).append($zone);
                        }
                    }
                });
            });

            // Drag & drop on zone
            $zone.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('smart-dragover');
            });

            $zone.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('smart-dragover');
            });

            $zone.on('drop', function(e) {
                var dt = e.originalEvent.dataTransfer;
                if (dt && dt.files && dt.files.length) {
                    var file = dt.files[0];
                    if (file.type.indexOf('image/') === 0) {
                        uploadImageFile(file, {
                            postId: getPostId(),
                            postTitle: getPostTitle(),
                            isFeatured: true,
                            onSuccess: function(data) {
                                if (data.featured_html) {
                                    $postimagediv.find('.inside').html(data.featured_html).append($zone);
                                }
                            }
                        });
                    }
                }
            });
        }
    }

    // -------------------------------------------------------------------------
    // 12. TEST ZONE ON SETTINGS PAGE
    // -------------------------------------------------------------------------
    function initTestZone() {
        var $zone = $('#smart-paste-test-zone');
        if (!$zone.length) return;

        var $results = $('#smart-paste-test-results');

        $zone.on('paste', function(e) {
            var file = extractImageFromClipboard(e);
            if (!file) return;

            e.preventDefault();
            e.stopPropagation();

            uploadImageFile(file, {
                postId: 0,
                postTitle: 'Demo Clipboard Test',
                onSuccess: function(data) {
                    var $item = $('<div class="smart-test-item">' +
                        '<img src="' + data.thumbnail_url + '" class="smart-test-item-thumb" alt="Preview" />' +
                        '<div class="smart-test-item-info">' +
                            '<div class="smart-test-item-title">' + data.title + '</div>' +
                            '<div class="smart-test-item-meta">' +
                                '<span>Tệp: <code>' + data.filename + '</code></span>' +
                                '<span>Kích thước: ' + data.filesize + '</span>' +
                                '<span>ID: #' + data.id + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<a href="' + data.url + '" target="_blank" class="smart-btn smart-btn-primary" style="padding:6px 12px; font-size:12px;">Xem ảnh gốc</a>' +
                    '</div>');

                    $results.prepend($item);
                }
            });
        });

        // Click to focus
        $zone.on('click', function() {
            $(this).focus();
        });
    }

    // -------------------------------------------------------------------------
    // 13. GLOBAL DRAG & DROP OVERLAY
    // -------------------------------------------------------------------------
    function initGlobalDragDrop() {
        var $overlay = $('<div id="smart-clipboard-drop-overlay">' +
            '<div class="smart-drop-box">' +
                '<div class="smart-drop-icon">📋</div>' +
                '<div class="smart-drop-title">Thả ảnh vào đây để tải lên Thư viện Media</div>' +
                '<div class="smart-drop-desc">Hệ thống sẽ tự động tối ưu hóa và đưa vào Thư viện ngay lập tức.</div>' +
            '</div>' +
        '</div>').appendTo('body');

        var dragCounter = 0;

        $(window).on('dragenter', function(e) {
            e.preventDefault();
            dragCounter++;
            var dt = e.originalEvent.dataTransfer;
            if (dt && dt.types && (dt.types.indexOf ? dt.types.indexOf('Files') !== -1 : true)) {
                $overlay.addClass('smart-active');
            }
        });

        $(window).on('dragleave', function(e) {
            e.preventDefault();
            dragCounter--;
            if (dragCounter <= 0) {
                dragCounter = 0;
                $overlay.removeClass('smart-active');
            }
        });

        $(window).on('dragover', function(e) {
            e.preventDefault();
        });

        $(window).on('drop', function(e) {
            e.preventDefault();
            dragCounter = 0;
            $overlay.removeClass('smart-active');

            var dt = e.originalEvent.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                var file = dt.files[0];
                if (file.type.indexOf('image/') === 0) {
                    uploadImageFile(file, {
                        postId: getPostId(),
                        postTitle: getPostTitle()
                    });
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // INITIALIZATION ON DOCUMENT READY
    // -------------------------------------------------------------------------
    $(document).ready(function() {
        initTinyMCE();
        initTextEditor();
        initGutenberg();
        initMediaModal();
        initMediaLibrary();
        initFeaturedImageZone();
        initTestZone();
        initGlobalDragDrop();
    });

})(jQuery);
