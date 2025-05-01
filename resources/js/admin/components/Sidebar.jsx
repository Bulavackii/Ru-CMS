import React from 'react';
import { Link } from 'react-router-dom';

export default function Sidebar() {
    return (
        <aside className="w-64 bg-white shadow-md">
            <div className="p-6 font-bold text-lg">
                RuShop Admin
            </div>
            <nav className="mt-6">
                <Link to="/admin/modules" className="block py-2.5 px-4 hover:bg-gray-100">
                    Дэшборд
                </Link>
            </nav>
        </aside>
    );
}
