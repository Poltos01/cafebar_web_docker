<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\CookController;
use App\Http\Controllers\BarkeeperController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------|
| Основные маршруты
|--------------------------------------------------------------------------|
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home.redirect');

/*
|--------------------------------------------------------------------------|
| Аутентификация
|--------------------------------------------------------------------------|
*/
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------|
| Резюме (публичные маршруты)
|--------------------------------------------------------------------------|
*/
Route::get('/resume/create', function () {
    return view('resume.create');
})->name('resume.create');
Route::post('/resume', [ResumeController::class, 'store'])->name('resume.store');

/*
|--------------------------------------------------------------------------|
| Меню (по умолчанию после авторизации)
|--------------------------------------------------------------------------|
*/
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/ingredients/{itemId}', [MenuController::class, 'getIngredients'])->name('menu.getIngredients');

/*
|--------------------------------------------------------------------------|
| Основные маршруты (программная проверка доступа)
|--------------------------------------------------------------------------|
*/
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

/*
|--------------------------------------------------------------------------|
| Профиль пользователя
|--------------------------------------------------------------------------|
*/
Route::prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/edit-password', [ProfileController::class, 'editPassword'])->name('profile.edit-password');
    Route::post('/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::post('/logout', [AuthController::class, 'logout'])->name('profile.logout');
});

/*
|--------------------------------------------------------------------------|
| Маршруты для официантов
|--------------------------------------------------------------------------|
*/
Route::prefix('waiter')->group(function () {
    Route::get('/dashboard', [WaiterController::class, 'dashboard'])->name('waiter.dashboard');
    Route::get('/orders', [WaiterController::class, 'orders'])->name('waiter.orders');
    Route::post('/orders', [WaiterController::class, 'createOrder'])->name('waiter.createOrder');
    Route::post('/orders/order-items/update-status', [WaiterController::class, 'completeOrder'])->name('waiter.completeOrder');
});

/*
|--------------------------------------------------------------------------|
| Маршруты для поваров
|--------------------------------------------------------------------------|
*/
Route::prefix('cook')->group(function () {
    Route::get('/dashboard', [CookController::class, 'dashboard'])->name('cook.dashboard');
    Route::get('/orders', [CookController::class, 'orders'])->name('cook.orders');
    Route::get('/menu', [CookController::class, 'menu'])->name('cook.menu');
    Route::get('/menu/{item_id}/ingredients', [CookController::class, 'menuItemIngredients'])->name('cook.menuItemIngredients');
    Route::get('/orders/{order_id}', [CookController::class, 'orderDetails'])->name('cook.orderDetails');
    Route::patch('/orders/{order_id}', [CookController::class, 'updateOrderStatus'])->name('cook.orders.update');
    Route::post('/orders/update-status', [CookController::class, 'updateOrderStatus'])->name('cook.updateOrderStatus');
    Route::post('/orders/order-items/update-status', [CookController::class, 'updateOrderItemStatus'])->name('cook.updateOrderItemStatus');
    Route::post('/orders/assign', [CookController::class, 'assignOrder'])->name('cook.assignOrder');
    Route::get('/orders/{order_id}/check-assignment', [CookController::class, 'checkAssignment'])->name('cook.checkAssignment');
});

/*
|--------------------------------------------------------------------------|
| Маршруты для барменов
|--------------------------------------------------------------------------|
*/
Route::prefix('barkeeper')->group(function () {
    Route::get('/dashboard', [BarkeeperController::class, 'dashboard'])->name('barkeeper.dashboard');
    Route::get('/orders', [BarkeeperController::class, 'orders'])->name('barkeeper.orders');
    Route::get('/menu', [BarkeeperController::class, 'menu'])->name('barkeeper.menu');
    Route::get('/menu/{item_id}/ingredients', [BarkeeperController::class, 'menuItemIngredients'])->name('barkeeper.menuItemIngredients');
    Route::get('/orders/{order_id}', [BarkeeperController::class, 'orderDetails'])->name('barkeeper.orderDetails');
    Route::patch('/orders/{order_id}/status', [BarkeeperController::class, 'updateOrderStatus'])->name('barkeeper.updateOrderStatus.patch');
    Route::post('/orders/update-status', [BarkeeperController::class, 'updateOrderStatus'])->name('barkeeper.updateOrderStatus');
    Route::post('/orders/order-items/update-status', [BarkeeperController::class, 'updateOrderItemStatus'])->name('barkeeper.updateOrderItemStatus');
    Route::post('/barkeeper/orders/assign', [App\Http\Controllers\BarkeeperController::class, 'assignOrder'])->name('barkeeper.assignOrder');
});

/*
|--------------------------------------------------------------------------|
| Прокси-маршруты
|--------------------------------------------------------------------------|
*/
Route::prefix('proxy')->group(function () {
    Route::get('/orders/{order_id}', function ($orderId) {
        $response = Http::withToken(Session::get('access_token'))
            ->timeout(15)
            ->connectTimeout(10)
            ->get("https://cafebar-oaba.onrender.com/orders/{$orderId}");
        return $response->json();
    })->name('proxy.order');

    Route::get('/orders/{order_id}/staff', function ($orderId) {
        $response = Http::withToken(Session::get('access_token'))
            ->timeout(15)
            ->connectTimeout(10)
            ->get("https://cafebar-oaba.onrender.com/orders/{$orderId}/staff");
        return $response->json();
    })->name('proxy.order.staff');

    Route::get('/orders/assigned_staff', function () {
        $response = Http::withToken(Session::get('access_token'))
            ->timeout(15)
            ->connectTimeout(10)
            ->get("https://cafebar-oaba.onrender.com/orders/assigned_staff");
        return $response->json();
    })->name('proxy.order.assigned_staff');
});