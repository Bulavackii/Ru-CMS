<div id="accessibility-widget" class="fixed bottom-6 left-6 z-50">
    <button onclick="toggleAccessibilityMenu()"
            class="bg-blue-700 text-white p-3 rounded-full shadow hover:bg-blue-800 transition">
        ♿
    </button>
    <div id="accessibility-menu" class="hidden mt-2 w-56 bg-white text-sm text-gray-800 rounded-lg shadow-xl p-4 space-y-3 border border-gray-200">
        <button onclick="increaseFontSize()" class="w-full text-left hover:text-blue-700">🔠 Увеличить шрифт</button>
        <button onclick="toggleHighContrast()" class="w-full text-left hover:text-blue-700">🌗 Контрастный режим</button>
        <button onclick="enableTTS()" class="w-full text-left hover:text-blue-700">🔊 Озвучить текст</button>
        <button onclick="resetAccessibility()" class="w-full text-left hover:text-red-600">♻️ Сбросить настройки</button>
    </div>
</div>

@push('scripts')
    <script src="{{ route('accessibility.script') }}" defer></script>
@endpush

// 📜 Resources/views/frontend/script.blade.php
console.log('✅ Accessibility script loaded');

function toggleAccessibilityMenu() {
    document.getElementById('accessibility-menu')?.classList.toggle('hidden');
}

function increaseFontSize() {
    document.body.style.fontSize = '1.15em';
    localStorage.setItem('fontSize', '1.15');
}

function toggleHighContrast() {
    document.body.classList.toggle('high-contrast');
    localStorage.setItem('contrast', document.body.classList.contains('high-contrast'));
}

function enableTTS() {
    const selection = window.getSelection().toString();
    if (selection) {
        const utterance = new SpeechSynthesisUtterance(selection);
        speechSynthesis.speak(utterance);
    } else {
        alert('Выделите текст для озвучивания');
    }
}

function resetAccessibility() {
    document.body.style.fontSize = '';
    document.body.classList.remove('high-contrast');
    localStorage.clear();
    alert('Настройки сброшены');
}

document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('fontSize')) {
        document.body.style.fontSize = localStorage.getItem('fontSize') + 'em';
    }
    if (localStorage.getItem('contrast') === 'true') {
        document.body.classList.add('high-contrast');
    }
});

// 💅 CSS класс для контраста
<style>
    .high-contrast {
        background-color: black !important;
        color: yellow !important;
    }
</style>

// 💡 Подключение в layout (например, frontend.blade.php):
@includeIf('Accessibility::frontend.button')
