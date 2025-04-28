import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';

function App() {
  return (
    <div className="container mx-auto text-center">
      <h1 className="text-2xl font-bold text-blue-600">
        Добро пожаловать в RuShop CMS!
      </h1>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
