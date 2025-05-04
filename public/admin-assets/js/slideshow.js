document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('add-slide-btn');
    const slidesContainer = document.getElementById('slides-container');

    if (!addBtn || !slidesContainer) return;

    addBtn.addEventListener('click', function (e) {
        e.preventDefault();

        fetch('/admin/slideshow/slide-template')
            .then(response => response.text())
            .then(html => {
                const slideWrapper = document.createElement('div');
                slideWrapper.classList.add('slide-wrapper');
                slideWrapper.innerHTML = html;

                // Добавить кнопку удаления
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'text-red-600 hover:underline mb-2';
                deleteBtn.innerText = 'Удалить слайд';
                deleteBtn.onclick = () => slideWrapper.remove();

                slideWrapper.prepend(deleteBtn);
                slidesContainer.appendChild(slideWrapper);

                // Пересчитать индексы
                updateSlideIndexes();
            })
            .catch(err => console.error('Ошибка загрузки шаблона:', err));
    });

    function updateSlideIndexes() {
        const slides = slidesContainer.querySelectorAll('.slide-wrapper');
        slides.forEach((slide, index) => {
            slide.querySelectorAll('input, textarea, select').forEach(input => {
                input.name = input.name.replace(/\[\d+]/g, `[${index}]`);
            });
        });
    }

    // Если ты подключаешь drag&drop для сортировки — сюда добавишь сортировку
});
