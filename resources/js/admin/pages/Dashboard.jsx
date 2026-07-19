import React from 'react';

export default function Dashboard() {
    return (
        <div className="max-w-7xl mx-auto">
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                    Добро пожаловать в админку!
                </h1>
                <p className="text-gray-600 dark:text-gray-400">
                    Здесь будет информация о заказах, продажах и посещениях.
                </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {/* Здесь можно добавить карточки со статистикой */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Статистика
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400">
                        Данные будут отображаться здесь
                    </p>
                </div>
            </div>
        </div>
    );
}
