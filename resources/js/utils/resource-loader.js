/**
 * 🛠️ Утилиты для загрузки локальных ресурсов с обработкой ошибок
 */

/**
 * Проверяет существование ресурса
 * @param {string} url URL ресурса
 * @returns {Promise<boolean>}
 */
export async function checkResourceExists(url) {
    try {
        const response = await fetch(url, { method: 'HEAD' });
        return response.ok;
    } catch (error) {
        console.warn(`Ресурс не найден: ${url}`, error);
        return false;
    }
}

/**
 * Загружает CSS файл с обработкой ошибок
 * @param {string} href Путь к CSS файлу
 * @param {string} id ID для элемента link (опционально)
 * @returns {Promise<HTMLLinkElement>}
 */
export function loadCSS(href, id = null) {
    return new Promise((resolve, reject) => {
        // Проверяем, не загружен ли уже
        const existing = document.querySelector(`link[href="${href}"]`);
        if (existing) {
            resolve(existing);
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        if (id) {
            link.id = id;
        }

        link.onload = () => resolve(link);
        link.onerror = () => {
            console.error(`Ошибка загрузки CSS: ${href}`);
            reject(new Error(`Failed to load CSS: ${href}`));
        };

        document.head.appendChild(link);
    });
}

/**
 * Загружает JS файл с обработкой ошибок
 * @param {string} src Путь к JS файлу
 * @param {object} options Опции загрузки
 * @returns {Promise<HTMLScriptElement>}
 */
export function loadJS(src, options = {}) {
    return new Promise((resolve, reject) => {
        // Проверяем, не загружен ли уже
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            resolve(existing);
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        if (options.async) script.async = true;
        if (options.defer) script.defer = true;
        if (options.type) script.type = options.type;

        script.onload = () => resolve(script);
        script.onerror = () => {
            console.error(`Ошибка загрузки JS: ${src}`);
            reject(new Error(`Failed to load JS: ${src}`));
        };

        document.head.appendChild(script);
    });
}

/**
 * Загружает несколько ресурсов параллельно
 * @param {Array<string>} resources Массив URL ресурсов
 * @returns {Promise<void>}
 */
export async function loadResources(resources) {
    const promises = resources.map(resource => {
        if (resource.endsWith('.css')) {
            return loadCSS(resource).catch(() => null);
        } else if (resource.endsWith('.js')) {
            return loadJS(resource).catch(() => null);
        }
        return Promise.resolve(null);
    });

    await Promise.allSettled(promises);
}

/**
 * Обработчик ошибок загрузки изображений
 */
export function setupImageErrorHandling() {
    document.addEventListener('error', (event) => {
        if (event.target.tagName === 'IMG') {
            const img = event.target;
            // Устанавливаем fallback изображение
            if (!img.hasAttribute('data-error-handled')) {
                img.setAttribute('data-error-handled', 'true');
                img.src = '/images/placeholder.png'; // Замените на ваш placeholder
                img.alt = img.alt || 'Изображение не загружено';
                img.style.opacity = '0.5';
            }
        }
    }, true);
}

// Автоматическая настройка при загрузке
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        setupImageErrorHandling();
    });
}

export default {
    checkResourceExists,
    loadCSS,
    loadJS,
    loadResources,
    setupImageErrorHandling,
};




