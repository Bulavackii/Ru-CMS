@props(['name', 'value' => '', 'id' => null, 'height' => 500, 'placeholder' => 'Введите текст...'])

@php
    $editorId = $id ?? $name;
@endphp

<div class="editor-wrapper">
    <textarea 
        name="{{ $name }}" 
        id="{{ $editorId }}" 
        class="editor"
        placeholder="{{ $placeholder }}"
        style="min-height: {{ $height }}px;"
    >{{ old($name, $value) }}</textarea>
</div>

@push('scripts')
{{-- TinyMCE загружается локально --}}
<script src="{{ asset('admin/tinymce/tinymce.min.js') }}"></script>
<script>
    (function() {
        const editorId = '{{ $editorId }}';
        const height = {{ $height }};
        
        // Ждем загрузки TinyMCE и DOM
        function initTinyMCEEditor() {
            if (typeof window.tinymce === 'undefined') {
                setTimeout(initTinyMCEEditor, 100);
                return;
            }
            
            if (!document.getElementById(editorId)) {
                setTimeout(initTinyMCEEditor, 100);
                return;
            }
            
            window.tinymce.init({
                selector: '#' + editorId,
                height: height,
                language: 'ru',
                language_url: '{{ asset('admin/tinymce/langs/ru.js') }}',
                branding: false,
                promotion: false,
                license_key: 'gpl',
                plugins: [
                    'advlist', 'anchor', 'autolink', 'autosave', 'charmap', 'code', 'codesample',
                    'directionality', 'emoticons', 'fullscreen', 'help', 'image', 'insertdatetime',
                    'link', 'lists', 'media', 'nonbreaking', 'pagebreak', 'preview', 'quickbars',
                    'save', 'searchreplace', 'table', 'visualblocks', 'visualchars', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic forecolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | help | code | fullscreen',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
                autosave_interval: '30s',
                autosave_retention: '2m',
                autosave_prefix: '{path}{query}-{id}-',
                autosave_restore_when_empty: false,
                images_upload_url: '/admin/upload-media',
                setup: (editor) => {
                    // Автосохранение
                    editor.on('change', () => {
                        const content = editor.getContent();
                        localStorage.setItem('editor_autosave_' + editorId, content);
                    });

                    // Восстановление автосохранения
                    const autosaved = localStorage.getItem('editor_autosave_' + editorId);
                    if (autosaved && !editor.getContent()) {
                        if (confirm('Найдено автосохранение. Восстановить?')) {
                            editor.setContent(autosaved);
                        }
                    }
                }
            });
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTinyMCEEditor);
        } else {
            initTinyMCEEditor();
        }
    })();
</script>
@endpush

