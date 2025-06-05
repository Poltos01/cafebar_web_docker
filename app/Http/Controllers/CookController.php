<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CookController extends Controller
{
    protected function checkAccess()
    {
        if (!Session::has('access_token') || !Session::has('user_role')) {
            Log::warning('Попытка доступа без авторизации', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return redirect()->route('login')->withErrors(['auth' => 'Необходима авторизация']);
        }

        if (strtolower(Session::get('user_role')) !== 'cook') {
            Log::warning('Попытка доступа с неверной ролью', [
                'ip' => request()->ip(),
                'role' => Session::get('user_role'),
            ]);
            return redirect()->route('dashboard')->withErrors(['role' => 'Доступ запрещён для вашей роли']);
        }

        return null;
    }

    public function dashboard()
    {
        if ($response = $this->checkAccess()) {
            return $response;
        }
        return view('cook.dashboard');
    }

    public function menu()
    {
        if ($response = $this->checkAccess()) {
            return $response;
        }

        try {
            $menu = app(ApiService::class)->get('menu', session('access_token'));
            Log::info('Меню для повара успешно получено', ['menu_count' => count($menu)]);
            return view('cook.menu', compact('menu'));
        } catch (\Exception $e) {
            Log::error('Ошибка при получении меню для повара', ['message' => $e->getMessage()]);
            return redirect()->route('dashboard')->withErrors(['menu' => 'Ошибка получения меню: ' . $e->getMessage()]);
        }
    }

    public function menuItemIngredients($itemId)
    {
        if ($response = $this->checkAccess()) {
            return $response;
        }

        try {
            $ingredients = app(ApiService::class)->get("menu/{$itemId}/ingredients", session('access_token'));
            Log::info('Ингредиенты получены для позиции меню', ['item_id' => $itemId]);
            return view('cook.menu_item_ingredients', compact('ingredients'));
        } catch (\Exception $e) {
            Log::error('Ошибка при получении ингредиентов', ['item_id' => $itemId, 'message' => $e->getMessage()]);
            return redirect()->route('cook.menu')->withErrors(['ingredients' => 'Ошибка получения ингредиентов: ' . $e->getMessage()]);
        }
    }

    public function orders()
    {
        set_time_limit(120);

        if ($response = $this->checkAccess()) {
            return $response;
        }

        $menu = [];
        $menuMap = [];
        $pendingOrders = [];
        $inProgressOrders = [];

        try {
            // Получение меню
            $menuResponse = Http::timeout(15)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->get('https://cafebar-oaba.onrender.com/menu/');

            if ($menuResponse->successful()) {
                $menu = $menuResponse->json() ?? [];
                Log::info('Меню успешно получено для повара', ['menu_count' => count($menu)]);
                $menuMap = collect($menu)->keyBy('item_id')->toArray();
            } else {
                Log::error('Ошибка при получении меню', [
                    'status' => $menuResponse->status(),
                    'body' => $menuResponse->body(),
                ]);
            }

            // Получение user_id текущего повара
            $userResponse = Http::withToken(Session::get('access_token'))
                ->timeout(15)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->get('https://cafebar-oaba.onrender.com/users/me');

            if ($userResponse->successful()) {
                $user = $userResponse->json();
                $userId = (string) $user['user_id']; // Приведение к строке
                Session::put('user_id', $userId);
                Log::info('ID пользователя получен', ['user_id' => $userId]);
            } else {
                Log::error('Ошибка при получении данных пользователя', [
                    'status' => $userResponse->status(),
                    'body' => $userResponse->body(),
                ]);
                $userId = (string) Session::get('user_id');
                if (!$userId) {
                    return redirect()->route('dashboard')->withErrors(['user' => 'Ошибка получения данных пользователя']);
                }
            }

            // Получение заказов
            $ordersResponse = Http::withToken(Session::get('access_token'))
                ->timeout(15)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->get('https://cafebar-oaba.onrender.com/orders/');

            $orders = [];
            if ($ordersResponse->successful()) {
                $orders = $ordersResponse->json() ?? [];
                Log::info('Заказы для повара успешно получены', ['count' => count($orders)]);
            } else {
                Log::error('Ошибка при получении заказов', [
                    'status' => $ordersResponse->status(),
                    'body' => $ordersResponse->body(),
                ]);
            }

            // Получение назначенного персонала
            $assignedStaff = Cache::remember('order_assigned_staff', now()->addMinutes(5), function () use ($userId) {
                try {
                    $staffResponse = Http::withToken(Session::get('access_token'))
                        ->timeout(15)
                        ->connectTimeout(10)
                        ->retry(2, 1000)
                        ->get('https://cafebar-oaba.onrender.com/orders/assigned_staff');

                    if ($staffResponse->successful()) {
                        $staff = $staffResponse->json() ?? [];
                        Log::info('Персонал для заказов получен', ['staff_count' => count($staff)]);
                        return $staff;
                    } else {
                        Log::warning('Ошибка при получении назначенного персонала', [
                            'status' => $staffResponse->status(),
                            'body' => $staffResponse->body(),
                        ]);
                        return [];
                    }
                } catch (\Exception $e) {
                    Log::error('Исключение при получении назначенного персонала', [
                        'message' => $e->getMessage(),
                        'user_id' => $userId,
                    ]);
                    return [];
                }
            });

            // Карта назначений по order_id
            $assignedStaffMap = collect($assignedStaff)->groupBy('order_id')->mapWithKeys(function ($staff, $orderId) {
                return [(string) $orderId => collect($staff)->map(function ($user) {
                    return ['user_id' => (string) $user['user_id'], 'role' => strtolower($user['role'])];
                })->toArray()];
            })->toArray();

            // Обработка заказов
            foreach ($orders as $order) {
                $orderId = (string) $order['order_id'];
                $filteredItems = [];
                $hasValidItems = false;
                foreach ($order['items'] as $item) {
                    $menuItem = $menuMap[$item['item_id']] ?? null;
                    $category = $menuItem ? strtolower($menuItem['category']) : 'unknown';
                    if ($category !== 'drink' && $category !== 'unknown') {
                        $item['status'] = ($item['status'] ?? 'In_progress') === 'Ready' ? 'Ready' : 'In_progress';
                        $filteredItems[] = $item;
                        $hasValidItems = true;
                    }
                }

                if (!$hasValidItems) {
                    Log::warning('Заказ пропущен: нет подходящих элементов.', ['order_id' => $orderId]);
                    continue;
                }

                $assignedUsers = $assignedStaffMap[$orderId] ?? [];
                $hasCook = collect($assignedUsers)->contains(function ($staff) {
                    return $staff['role'] === 'cook';
                });
                $isAssignedToCurrentUser = collect($assignedUsers)->contains(function ($user) use ($userId) {
                    return $user['user_id'] === $userId && $user['role'] === 'cook';
                });

                Log::debug('Проверка назначения заказа:', [
                    'order_id' => $orderId,
                    'status' => $order['status'],
                    'has_cook' => $hasCook,
                    'is_assigned_to_current_user' => $isAssignedToCurrentUser,
                    'assigned_users' => $assignedUsers,
                    'user_id' => $userId,
                ]);

                $filteredOrder = $order;
                $filteredOrder['items'] = $filteredItems;

                if ($order['status'] === 'Pending' || ($order['status'] === 'In_progress' && !$hasCook)) {
                    $pendingOrders[] = $filteredOrder;
                    Log::debug('Заказ добавлен в Pending:', [
                        'order_id' => $orderId,
                        'status' => $order['status'],
                        'has_cook' => $hasCook,
                    ]);
                } elseif ($order['status'] === 'In_progress' && $isAssignedToCurrentUser) {
                    $inProgressOrders[] = $filteredOrder;
                    Log::debug('Заказ добавлен в In_progress:', [
                        'order_id' => $orderId,
                        'user_id' => $userId,
                        'assigned_users' => $assignedUsers,
                    ]);
                } else {
                    Log::debug('Заказ не добавлен: не соответствует условиям.', [
                        'order_id' => $orderId,
                        'status' => $order['status'],
                        'is_assigned_to_current_user' => $isAssignedToCurrentUser,
                    ]);
                }
            }

            Log::info('Итоговые заказы для повара:', [
                'pending_orders' => array_map(fn($order) => [
                    'order_id' => $order['order_id'],
                    'status' => $order['status'],
                    'items' => $order['items'],
                    'comment' => $order['comment'] ?? '',
                ], $pendingOrders),
                'in_progress_orders' => array_map(fn($order) => [
                    'order_id' => $order['order_id'],
                    'status' => $order['status'],
                    'items' => $order['items'],
                    'comment' => $order['comment'] ?? '',
                ], $inProgressOrders),
            ]);
        } catch (\Exception $e) {
            Log::error('Общая ошибка при запросе orders:', ['message' => $e->getMessage()]);
        }

        return view('cook.orders', compact('pendingOrders', 'inProgressOrders', 'menu', 'menuMap'));
    }

    public function orderDetails($orderId)
    {
        if ($response = $this->checkAccess()) {
            return $response;
        }

        try {
            $order = app(ApiService::class)->get("orders/{$orderId}", session('access_token'));
            Log::info('Детали заказа получены:', ['order_id' => $orderId]);
            return view('cook.order_details', compact('order'));
        } catch (\Exception $e) {
            Log::error('Ошибка при получении деталей заказа:', ['order_id' => $orderId, 'message' => $e->getMessage()]);
            return redirect()->route('cook.orders')->withErrors(['order' => 'Ошибка получения заказа: ' . $e->getMessage()]);
        }
    }

    public function clearAssignedOrdersCache()
    {
        Cache::forget('order_assigned_staff');
        Log::info('Кэш назначенных заказов очищен');
    }

    public function updateOrderStatus(Request $request)
    {
        if (!Session::has('access_token') || !Session::has('user_role') || strtolower(Session::get('user_role')) !== 'cook') {
            Log::warning('Попытка обновления статуса заказа без авторизации или неверной роли.', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return response()->json(['success' => false, 'message' => 'Необходима авторизация'], 401);
        }

        $request->validate([
            'order_id' => 'required|integer',
            'status' => 'required|in:Pending,In_progress,Ready',
        ]);

        $orderId = $request->order_id;
        $status = $request->status;

        try {
            $response = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->patch("https://cafebar-oaba.onrender.com/orders/{$orderId}/status?status={$status}");

            if ($response->successful()) {
                Log::info('Статус заказа успешно обновлён:', [
                    'order_id' => $orderId,
                    'status' => $status,
                    'response' => $response->json(),
                ]);
                $this->clearAssignedOrdersCache();
                return response()->json(['success' => true, 'message' => 'Статус заказа обновлён']);
            } else {
                Log::error('Ошибка при обновлении статуса заказа:', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['success' => false, 'message' => 'Ошибка обновления статуса: сервер вернул ошибку ' . $response->status()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Исключение при обновлении статуса заказа:', [
                'order_id' => $orderId,
                'status' => $status,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Ошибка обновления статуса: ' . $e->getMessage()], 500);
        }
    }

    public function updateOrderItemStatus(Request $request)
    {
        if (!Session::has('access_token') || !Session::has('user_role') || strtolower(Session::get('user_role')) !== 'cook') {
            Log::warning('Попытка обновления статуса элемента заказа без авторизации или неверной роли.', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return response()->json(['success' => false, 'message' => 'Необходима авторизация'], 401);
        }

        $request->validate([
            'order_item_id' => 'required|integer|min:1',
            'status' => 'required|in:Pending,In_progress,Ready',
        ]);

        $orderItemId = $request->order_item_id;
        $status = $request->status;

        try {
            $response = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->patch("https://cafebar-oaba.onrender.com/orders/order-items/{$orderItemId}/status", [
                    'status' => $status
                ]);

            if ($response->successful()) {
                Log::info('Статус элемента заказа успешно обновлён:', [
                    'order_item_id' => $orderItemId,
                    'status' => $status,
                    'response' => $response->json(),
                ]);
                $this->clearAssignedOrdersCache();
                return response()->json(['success' => true, 'message' => 'Статус элемента обновлён']);
            } else {
                Log::error('Ошибка при обновлении статуса элемента заказа:', [
                    'order_item_id' => $orderItemId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['success' => false, 'message' => 'Ошибка обновления статуса: сервер вернул ошибку ' . $response->status()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Исключение при обновлении статуса элемента заказа:', [
                'order_item_id' => $orderItemId,
                'status' => $status,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Ошибка обновления статуса: ' . $e->getMessage()], 500);
        }
    }

    public function assignOrder(Request $request)
    {
        if (!Session::has('access_token') || !Session::has('user_role') || strtolower(Session::get('user_role')) !== 'cook') {
            Log::warning('Попытка привязки заказа без авторизации или неверной роли.', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return response()->json(['success' => false, 'message' => 'Необходима авторизация'], 401);
        }

        $request->validate([
            'order_id' => 'required|integer',
        ]);

        $orderId = $request->order_id;
        $accessToken = Session::get('access_token');
        $userId = Session::get('user_id');

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->connectTimeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->patch("https://cafebar-oaba.onrender.com/orders/{$orderId}/assign", [
                    'user_id' => $userId
                ]);

            if ($response->successful()) {
                Log::info('Заказ успешно привязан:', [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'response' => $response->json(),
                ]);
                $this->clearAssignedOrdersCache();
                return response()->json(['success' => true, 'message' => 'Заказ успешно привязан']);
            } else {
                Log::error('Ошибка при привязке заказа:', [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['success' => false, 'message' => 'Ошибка привязки: сервер вернул ошибку ' . $response->status()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Исключение при привязке заказа:', [
                'order_id' => $orderId,
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Ошибка привязки: ' . $e->getMessage()], 500);
        }
    }

    public function checkNotifications()
    {
        if ($response = $this->checkAccess()) {
            return $response;
        }

        try {
            $notifications = app(ApiService::class)->get('notifications', session('access_token'));
            Log::info('Уведомления для повара получены:', ['count' => count($notifications)]);
            return view('cook.notifications', compact('notifications'));
        } catch (\Exception $e) {
            Log::error('Ошибка при получении уведомлений:', ['message' => $e->getMessage()]);
            return redirect()->route('dashboard')->withErrors(['notifications' => 'Ошибка получения уведомлений: ' . $e->getMessage()]);
        }
    }

    public function statistics()
    {
        if ($response = $this->checkAccess()) {
            return $response;
        }

        try {
            $statistics = app(ApiService::class)->get('statistics', session('access_token'));
            Log::info('Статистика для повара получена:', ['statistics' => $statistics]);
            return view('cook.statistics', compact('statistics'));
        } catch (\Exception $e) {
            Log::error('Ошибка при получении статистики:', ['message' => $e->getMessage()]);
            return redirect()->route('dashboard')->withErrors(['statistics' => 'Ошибка получения статистики: ' . $e->getMessage()]);
        }
    }
}