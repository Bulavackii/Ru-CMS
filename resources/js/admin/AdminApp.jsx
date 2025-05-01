import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import Dashboard from './pages/Dashboard';

export default function AdminApp() {
    return (
        <Router>
            <div className="flex min-h-screen bg-gray-100">
                <Sidebar />
                <div className="flex-1 flex flex-col">
                    <Header />
                    <main className="p-6">
                        <Routes>
                            <Route path="/admin/modules" element={<Dashboard />} />
                        </Routes>
                    </main>
                </div>
            </div>
        </Router>
    );
}
