@extends('layouts.admin')

@section('title', 'Спецвозможности')

@section('content')
    <style>
        .toggle {
            appearance: none;
            width: 2.5rem;
            height: 1.3rem;
            background-color: #cbd5e0;
            border-radius: 9999px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .toggle:checked {
            background-color: #3b82f6;
        }

        .toggle::before {
            content: '';
            position: absolute;
            left: 0.2rem;
            top: 0.2rem;
            width: 0.9rem;
            height: 0.9rem;
            background-color: white;
            border-radius: 9999px;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .toggle:checked::before {
            transform: translateX(1.2rem);
        }
    </style>

    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow text-sm text-gray-800 dark:text-gray-100 transition duration-300 ease-in-out">
        <h1 class="text-2xl font-bold mb-6 text-blue-700 flex items-center gap-2">
            <i class="fas fa-universal-access text-xl"></i> Спецвозможности
        </h1>

        <form method="POST" action="{{ route('admin.accessibility.update') }}" class="space-y-4">
            @csrf

            @php
                $toggles = [
                    ['name' => 'enabled', 'label' => 'Показывать кнопку виджета на сайте', 'icon' => 'fa-eye'],
                    ['name' => 'enable_font_size', 'label' => 'Увеличение/уменьшение шрифта', 'icon' => 'fa-text-height'],
                    ['name' => 'enable_speech', 'label' => 'Озвучивание всей страницы', 'icon' => 'fa-volume-up'],
                    ['name' => 'enable_selected_text_speech', 'label' => 'Озвучивание выделенного текста', 'icon' => 'fa-comment-dots'],
                    ['name' => 'enable_contrast', 'label' => 'Контрастная тема', 'icon' => 'fa-adjust'],
                    ['name' => 'enable_bw_mode', 'label' => 'Монохромный режим', 'icon' => 'fa-low-vision'],
                    ['name' => 'enable_sepia_mode', 'label' => 'Сепия-тема', 'icon' => 'fa-tint'],
                    ['name' => 'enable_reading_mask', 'label' => 'Маска для чтения', 'icon' => 'fa-minus'],
                    ['name' => 'enable_highlight_links', 'label' => 'Подсветка ссылок', 'icon' => 'fa-link'],
                ];
            @endphp

            @foreach ($toggles as $toggle)
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-sm hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200 ease-in-out">
                    <label for="{{ $toggle['name'] }}" class="flex items-center gap-2 font-medium cursor-pointer">
                        <i class="fas {{ $toggle['icon'] }}"></i>
                        {{ $toggle['label'] }}
                    </label>
                    <input type="checkbox" id="{{ $toggle['name'] }}" name="{{ $toggle['name'] }}" class="toggle"
                        {{ $settings->{$toggle['name']} ? 'checked' : '' }}>
                </div>
            @endforeach

            <div class="pt-6 text-right">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow text-sm font-semibold transition duration-300 ease-in-out">
                    <i class="fas fa-save"></i> Сохранить
                </button>
            </div>
        </form>
    </div>
@endsection
