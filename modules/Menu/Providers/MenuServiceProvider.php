<?php

namespace Modules\Menu\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use Modules\Menu\Models\Menu;

class MenuServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = base_path('modules/Menu');

        if (File::exists($modulePath . '/Routes/web.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        }
        if (File::exists($modulePath . '/Views')) {
            $this->loadViewsFrom($modulePath . '/Views', 'Menu');
        }
        if (File::exists($modulePath . '/Database/Migrations')) {
            $this->loadMigrationsFrom($modulePath . '/Database/Migrations');
        }

        // Подаём активные хедер-меню с родителями и детьми (сортировка по order)
        View::composer('Menu::frontend.header', function ($view) {
            $menus = Menu::query()
                ->where('active', true)
                ->where('position', 'header')
                ->with([
                    'items' => fn($q) => $q->where('active', true)->whereNull('parent_id')->orderBy('order'),
                    'items.activeChildren' => fn($q) => $q->where('active', true)->orderBy('order'),
                    'items.linkedPage',
                ])
                ->get();

            $view->with('menus', $menus);
        });
    }

    public function register(): void {}
}
