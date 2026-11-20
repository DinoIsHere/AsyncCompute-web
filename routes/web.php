<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstanceController as AdminInstanceController;
use App\Http\Controllers\InstanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', fn () => redirect('/instances'));

Route::get('/about', [AboutController::class, 'index'])->name('about.index');

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/instances', [AdminInstanceController::class, 'index'])->name('instances.index');
    Route::get('/instances/create', [AdminInstanceController::class, 'create'])->name('instances.create');
    Route::post('/instances', [AdminInstanceController::class, 'store'])->name('instances.store');
    Route::get('/instances/{gpuModel}/edit', [AdminInstanceController::class, 'edit'])->name('instances.edit');
    Route::put('/instances/{gpuModel}', [AdminInstanceController::class, 'update'])->name('instances.update');
    Route::delete('/instances/{gpuModel}', [AdminInstanceController::class, 'destroy'])->name('instances.destroy');
});

Route::get('/instances', [InstanceController::class, 'index'])->name('instances.index');
