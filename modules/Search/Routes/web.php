<?php

use Illuminate\Support\Facades\Route;
use Modules\Search\Controllers\Admin\SearchController as AdminSearch;
use Modules\Search\Controllers\Frontend\SearchController as FrontendSearch;

Route::middleware(['web', 'auth', 'admin'])->get('/admin/search', [AdminSearch::class, 'index'])->name('admin.search.index');
Route::middleware('web')->get('/search', [FrontendSearch::class, 'index'])->name('search.index');

