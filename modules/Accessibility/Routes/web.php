<?php

use Illuminate\Support\Facades\Route;
use Modules\Accessibility\Controllers\Frontend\AccessibilityController;

Route::get('/accessibility/script.js', [AccessibilityController::class, 'script'])->name('accessibility.script');
