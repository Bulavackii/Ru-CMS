import React from 'react';

export default function Header() {
    return (
        <header className="bg-white dark:bg-gray-800 shadow-md p-4 flex justify-between items-center transition-colors duration-200">
            <div className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                Админ-панель
            </div>
            <div className="flex items-center gap-4">
                {/* Здесь можно добавить уведомления, профиль и т.д. */}
            </div>
        </header>
    );
}
