import React from 'react';
import { RouterProvider } from 'react-router-dom';
import { router } from './router';
import Sidebar from './components/Sidebar';
import Header from './components/Header';

export default function AdminApp() {
    return (
        <div className="flex min-h-screen bg-gray-100 dark:bg-gray-900">
            <Sidebar />
            <div className="flex-1 flex flex-col">
                <Header />
                <main className="p-6">
                    <RouterProvider router={router} />
                </main>
            </div>
        </div>
    );
}
