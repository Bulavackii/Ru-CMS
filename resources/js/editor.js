/**
 * 📝 WYSIWYG редактор контента
 * Использует TinyMCE для редактирования контента
 * 
 * TinyMCE загружается глобально через script тег из public/admin/tinymce/
 */

// Инициализация редактора
export function initEditor(selector = 'textarea.editor', options = {}) {
    // Проверяем, загружен ли TinyMCE
    if (typeof window.tinymce === 'undefined') {
        console.error('TinyMCE не загружен. Убедитесь, что подключен script src="/admin/tinymce/tinymce.min.js"');
        return Promise.reject(new Error('TinyMCE not loaded'));
    }

    const defaultOptions = {
        selector: selector,
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'anchor', 'autolink', 'autosave', 'charmap', 'code', 'codesample',
            'directionality', 'emoticons', 'fullscreen', 'help', 'image', 'insertdatetime',
            'link', 'lists', 'media', 'nonbreaking', 'pagebreak', 'preview', 'quickbars',
            'save', 'searchreplace', 'table', 'visualblocks', 'visualchars', 'wordcount'
        ],
        // Самостоятельный хостинг (GPL) — без этого TinyMCE 8 уходит в read-only
        license_key: 'gpl',
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help | code | fullscreen',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
        language: 'ru',
        language_url: '/admin/tinymce/langs/ru.js',
        branding: false,
        promotion: false,
        // Автосохранение
        autosave_interval: '30s',
        autosave_retention: '2m',
        autosave_prefix: '{path}{query}-{id}-',
        autosave_restore_when_empty: false,
        // Загрузка изображений
        images_upload_url: '/admin/upload-media',
        images_upload_handler: async (blobInfo, progress) => {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (csrfToken) {
                    formData.append('_token', csrfToken);
                }

                const xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        progress((e.loaded / e.total) * 100);
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status === 403 || xhr.status === 404) {
                        reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                        return;
                    }

                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('HTTP Error: ' + xhr.status);
                        return;
                    }

                    try {
                        const json = JSON.parse(xhr.responseText);
                        if (!json || typeof json.location !== 'string') {
                            reject('Invalid JSON: ' + xhr.responseText);
                            return;
                        }
                        resolve(json.location);
                    } catch (e) {
                        reject('Failed to parse response: ' + e.message);
                    }
                });

                xhr.addEventListener('error', () => {
                    reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                });

                xhr.open('POST', '/admin/upload-media');
                xhr.send(formData);
            });
        },
        // Шаблоны контента
        templates: [
            {
                title: 'Новость',
                description: 'Шаблон для новости',
                content: '<h2>Заголовок новости</h2><p>Содержание новости...</p>'
            },
            {
                title: 'Статья',
                description: 'Шаблон для статьи',
                content: '<h1>Заголовок статьи</h1><p>Введение...</p><h2>Основной контент</h2><p>Текст статьи...</p>'
            }
        ],
        // Быстрые действия
        quickbars_selection_toolbar: 'bold italic | quicklink quickimage quicktable',
        quickbars_insert_toolbar: 'quickimage quicktable',
        // Настройки для темной темы
        skin: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oxide-dark' : 'oxide',
        content_css: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'default',
        // ...другие опции
        ...options
    };

    return window.tinymce.init({ ...defaultOptions, ...options });
}

// Инициализация при загрузке страницы (если есть textarea.editor)
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        // Ждем загрузки TinyMCE
        const checkTinyMCE = setInterval(() => {
            if (typeof window.tinymce !== 'undefined') {
                clearInterval(checkTinyMCE);
                const editors = document.querySelectorAll('textarea.editor:not(.tinymce-initialized)');
                if (editors.length > 0) {
                    editors.forEach((editor) => {
                        editor.classList.add('tinymce-initialized');
                        initEditor('#' + editor.id);
                    });
                }
            }
        }, 100);
        
        // Таймаут на случай, если TinyMCE не загрузится
        setTimeout(() => {
            clearInterval(checkTinyMCE);
        }, 10000);
    });
}

// Экспорт для использования в других модулях
export default { initEditor };
