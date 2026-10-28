<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChatController::class, 'index'])->name('home');
Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
