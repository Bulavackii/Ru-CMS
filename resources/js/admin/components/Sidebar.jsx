import React from 'react';
import { Link, useLocation } from 'react-router-dom';

export default function Sidebar() {
    const location = useLocation();

    const menuItems = [
        { path: '/admin/modules', label: 'Дэшборд', icon: '📊' },
    ];

    return (
        <aside className="w-64 bg-white dark:bg-gray-800 shadow-md fixed left-0 top-0 h-full transition-colors duration-200">
            <div className="p-6 font-bold text-lg text-gray-800 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700">
                RuShop Admin
            </div>
            <nav className="mt-6">
                {menuItems.map((item) => {
                    const isActive = location.pathname === item.path;
                    return (
                        <Link
                            key={item.path}
                            to={item.path}
                            className={`block py-2.5 px-4 transition-colors duration-200 ${
                                isActive
                                    ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-medium'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                            }`}
                        >
                            <span className="mr-2">{item.icon}</span>
                            {item.label}
                        </Link>
                    );
                })}
            </nav>
        </aside>
    );
}
