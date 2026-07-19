import { createBrowserRouter } from 'react-router-dom';
import Dashboard from '../pages/Dashboard';

export const router = createBrowserRouter([
    {
        path: '/admin/modules',
        element: <Dashboard />,
    },
]);

export default router;




