<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Modules\Payments\Models\Order;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    /**
     * 📤 Экспорт пользователей в CSV
     */
    public function exportUsers(Request $request)
    {
        // Оптимизация: выбираем только нужные поля, исключаем пароли
        $users = User::select('id', 'name', 'email', 'is_admin', 'created_at')->get();

        $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Имя', 'Email', 'Администратор', 'Дата регистрации']);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->is_admin ? 'Да' : 'Нет',
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * 📤 Экспорт заказов в CSV
     */
    public function exportOrders(Request $request)
    {
        $query = Order::with(['user', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $orders = $query->get();

        $filename = 'orders_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Клиент', 'Email', 'Сумма', 'Статус', 'Дата создания']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->customer_name ?? $order->user?->name ?? 'Гость',
                    $order->customer_email ?? $order->user?->email ?? '',
                    number_format($order->total, 2, ',', ' '),
                    $order->status,
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * 📥 Импорт пользователей из CSV
     */
    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        fgetcsv($handle); // Пропустить заголовки

        $imported = 0;
        $errors = [];

        while (($data = fgetcsv($handle)) !== false) {
            try {
                User::create([
                    'name' => $data[1] ?? '',
                    'email' => $data[2] ?? '',
                    'password' => bcrypt('password123'), // Временный пароль
                    'is_admin' => ($data[3] ?? 'Нет') === 'Да',
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Строка " . ($imported + 1) . ": " . $e->getMessage();
            }
        }

        fclose($handle);

        return back()->with('success', "Импортировано: {$imported}. Ошибок: " . count($errors));
    }
}

