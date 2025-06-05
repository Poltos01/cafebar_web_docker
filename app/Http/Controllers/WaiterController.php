<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WaiterController extends Controller
{
    public function dashboard()
    {
        if (!Session::has('access_token') || strtolower(Session::get('user_role')) !== 'waiter') {
            Log::warning('Попытка доступа к дашборду официанта без авторизации или неверной роли', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return redirect()->route('login')->with('error', 'Пожалуйста, авторизуйтесь.');
        }
        return view('waiter.dashboard');
    }

    public function orders()
    {
        if (!Session::has('access_token') || strtolower(Session::get('user_role')) !== 'waiter') {
            Log::warning('Попытка доступа к заказам официанта без авторизации или неверной роли', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return redirect()->route('login')->with('error', 'Пожалуйста, авторизуйтесь.');
        }

        // Загрузка меню
        try {
            $menuResponse = Http::timeout(10)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/menu/');

            if ($menuResponse->successful()) {
                $menu = $menuResponse->json();
                Log::info('Меню для официанта успешно получено', ['menu_count' => count($menu)]);
            } else {
                Log::error('Ошибка при получении меню', [
                    'status' => $menuResponse->status(),
                    'body' => $menuResponse->body(),
                ]);
                $menu = [];
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при запросе меню: ' . $e->getMessage());
            $menu = [];
        }

        $menu = array_filter($menu, fn($item) => isset($item['is_available']) && $item['is_available']);
        $groupedMenu = collect($menu)->groupBy(function ($item) {
            return match (strtolower($item['category'])) {
                'drink' => 'Напитки',
                'starter' => 'Закуски',
                'main_course' => 'Основные блюда',
                'soup' => 'Суп',
                default => $item['category'],
            };
        })->toArray();

        // Загрузка заказов
        try {
            $ordersResponse = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/orders/');

            if ($ordersResponse->successful()) {
                $orders = $ordersResponse->json();
                Log::info('Заказы успешно получены', ['order_count' => count($orders)]);
                $readyOrders = array_filter($orders, function ($order) {
                    return $order['status'] === 'Ready' || ($order['status'] === 'In_progress' && !empty(array_filter($order['items'], fn($item) => $item['status'] === 'Ready')));
                });
                foreach ($readyOrders as &$order) {
                    foreach ($order['items'] as &$item) {
                        $menuItem = collect($menu)->firstWhere('item_id', $item['item_id']);
                        $item['name'] = $menuItem ? $menuItem['name'] : 'Неизвестное блюдо';
                    }
                }
            } else {
                Log::error('Ошибка при получении заказов', [
                    'status' => $ordersResponse->status(),
                    'body' => $ordersResponse->body(),
                ]);
                $readyOrders = [];
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при запросе заказов: ' . $e->getMessage());
            $readyOrders = [];
        }

        // Загрузка бронирований
        try {
            $bookingsResponse = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/bookings/');

            if ($bookingsResponse->successful()) {
                $bookings = $bookingsResponse->json();
                Log::info('Бронирования успешно получены', ['booking_count' => count($bookings)]);
                $today = Carbon::today()->format('Y-m-d');
                Log::info('Фильтрация бронирований для текущей даты', ['today' => $today]);
                $bookings = array_filter(
                    $bookings,
                    fn($booking) => str_starts_with($booking['booking_time'], $today)
                );
                Log::info('Отфильтровано бронирований на сегодня', ['filtered_booking_count' => count($bookings)]);
            } else {
                Log::error('Ошибка при получении бронирований', [
                    'status' => $bookingsResponse->status(),
                    'body' => $bookingsResponse->body(),
                ]);
                $bookings = [];
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при запросе бронирований: ' . $e->getMessage());
            $bookings = [];
        }

        // Доступные столики
        $totalTables = range(1, 20);
        $bookedTables = [3, 5, 10];
        $availableTables = array_diff($totalTables, $bookedTables);

        return view('waiter.orders', compact('groupedMenu', 'availableTables', 'readyOrders', 'bookings', 'menu'));
    }

    public function createOrder(Request $request)
    {
        if (!Session::has('access_token') || strtolower(Session::get('user_role')) !== 'waiter') {
            Log::warning('Попытка создания заказа без авторизации или неверной роли', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return redirect()->route('login')->with('error', 'Пожалуйста, авторизуйтесь.');
        }

        Log::info('Получен запрос на создание заказа', [
            'request_data' => $request->all(),
            'access_token_partial' => substr(Session::get('access_token', ''), 0, 10) . '...',
        ]);

        $request->validate([
            'table_number' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:0',
            'comment' => 'nullable|string|max:500',
        ], [
            'table_number.required' => 'Пожалуйста, выберите номер столика.',
            'table_number.integer' => 'Номер столика должен быть числом.',
            'table_number.min' => 'Номер столика должен быть больше 0.',
            'items.required' => 'Заказ должен содержать хотя бы одну позицию.',
            'items.min' => 'Заказ должен содержать хотя бы одну позицию.',
            'items.*.item_id.required' => 'Идентификатор позиции обязателен.',
            'items.*.item_id.integer' => 'Идентификатор позиции должен быть числом.',
            'items.*.item_id.min' => 'Идентификатор позиции должен быть больше 0.',
            'items.*.quantity.required' => 'Количество для позиции обязательно.',
            'items.*.quantity.integer' => 'Количество должно быть целым числом.',
            'items.*.quantity.min' => 'Количество не может быть отрицательным.',
            'comment.string' => 'Комментарий должен быть текстом.',
            'comment.max' => 'Комментарий не может превышать 500 символов.',
        ]);

        // Проверка, что хотя бы один элемент имеет quantity >= 1
        $hasValidItem = false;
        foreach ($request->items as $item) {
            if (isset($item['quantity']) && $item['quantity'] >= 1) {
                $hasValidItem = true;
                break;
            }
        }
        if (!$hasValidItem) {
            Log::warning('Заказ не содержит позиций с количеством >= 1', ['request_items' => $request->items]);
            return redirect()->route('waiter.orders')->with('error', 'Заказ не может быть пустым.');
        }

        try {
            $menuResponse = Http::timeout(10)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/menu/');

            if ($menuResponse->successful()) {
                $menu = $menuResponse->json();
                Log::info('Меню получено для создания заказа', ['menu_count' => count($menu)]);
            } else {
                Log::error('Ошибка при получении меню', [
                    'status' => $menuResponse->status(),
                    'body' => $menuResponse->body(),
                ]);
                return redirect()->route('waiter.orders')->with('error', 'Ошибка создания: не удалось получить меню.');
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при запросе меню: ' . $e->getMessage());
            return redirect()->route('waiter.orders')->with('error', 'Ошибка создания: не удалось получить меню.');
        }

        $items = [];
        foreach ($request->items as $item) {
            if (isset($item['quantity']) && $item['quantity'] >= 1) {
                $menuItem = collect($menu)->firstWhere('item_id', $item['item_id']);
                if ($menuItem && $menuItem['is_available']) {
                    $items[] = [
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                        'price' => $menuItem['price'],
                    ];
                }
            }
        }

        if (empty($items)) {
            Log::warning('Нет валидных позиций заказа после фильтрации', ['request_items' => $request->items]);
            return redirect()->route('waiter.orders')->with('error', 'Ошибка создания: выбраны недоступные позиции.');
        }

        $orderData = [
            'user_id' => Session::get('user_id'),
            'table_number' => $request->table_number,
            'items' => $items,
            'comment' => $request->comment ?? '',
        ];

        try {
            $response = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->post('https://cafebar-oaba.onrender.com/orders/', $orderData);

            if ($response->successful()) {
                $orderResponse = $response->json();
                $orderId = $orderResponse['order_id'] ?? null;
                Log::info('Заказ успешно создан', [
                    'order_id' => $orderId,
                    'order_data' => $orderData,
                    'response' => $orderResponse,
                ]);

                // Привязка официанта к заказу
                if ($orderId !== null && $orderId > 0) {
                    try {
                        $accessToken = Session::get('access_token');
                        Log::info('Попытка привязки официанта к заказу', [
                            'order_id' => $orderId,
                            'access_token_partial' => substr($accessToken, 0, 10) . '...',
                            'url' => "https://cafebar-oaba.onrender.com/orders/{$orderId}/assign",
                        ]);

                        $assignResponse = Http::withToken($accessToken)
                            ->timeout(10)
                            ->connectTimeout(5)
                            ->patch("https://cafebar-oaba.onrender.com/orders/{$orderId}/assign");

                        if ($assignResponse->successful()) {
                            $assignData = $assignResponse->json();
                            if (is_array($assignData) && !empty($assignData) && isset($assignData[0]['user_id'], $assignData[0]['role'])) {
                                Log::info('Официант успешно привязан к заказу', [
                                    'order_id' => $orderId,
                                    'user_id' => $assignData[0]['user_id'],
                                    'role' => $assignData[0]['role'],
                                    'response' => $assignData,
                                ]);
                            } else {
                                Log::warning('Неожиданный формат ответа при успешной привязке', [
                                    'order_id' => $orderId,
                                    'response' => $assignData,
                                ]);
                            }
                        } else {
                            Log::error('Ошибка при привязке официанта к заказу', [
                                'order_id' => $orderId,
                                'status' => $assignResponse->status(),
                                'detail' => $assignResponse->json()['detail'] ?? $assignResponse->body(),
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Исключение при запросе привязки официанта', [
                            'order_id' => $orderId,
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } else {
                    Log::warning('Невалидный или отсутствующий order_id для привязки', [
                        'order_id' => $orderId,
                        'response' => $orderResponse,
                    ]);
                }

                return redirect()->route('waiter.orders')->with('success', 'Заказ успешно создан.');
            } else {
                Log::error('Ошибка при создании заказа', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'order_data' => $orderData,
                ]);
                return redirect()->route('waiter.orders')->with('error', 'Ошибка создания: сервер вернул ошибку ' . $response->status() . '.');
            }
        } catch (\Exception $e) {
            Log::error('Исключение при запросе создания заказа', [
                'message' => $e->getMessage(),
                'order_data' => $orderData,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('waiter.orders')->with('error', 'Ошибка создания: проблема с сервером.');
        }
    }

    public function completeOrder(Request $request)
    {
        if (!Session::has('access_token') || strtolower(Session::get('user_role')) !== 'waiter') {
            Log::warning('Попытка завершения элемента заказа без авторизации или неверной роли', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return response()->json(['success' => false, 'message' => 'Пожалуйста, авторизуйтесь.'], 401);
        }

        $request->validate([
            'order_item_id' => 'required|integer|min:1',
            'status' => 'required|in:Completed',
        ], [
            'order_item_id.required' => 'Идентификатор элемента заказа обязателен.',
            'order_item_id.integer' => 'Идентификатор элемента заказа должен быть числом.',
            'order_item_id.min' => 'Идентификатор элемента заказа должен быть больше 0.',
            'status.required' => 'Статус обязателен.',
            'status.in' => 'Статус должен быть "Completed".',
        ]);

        $orderItemId = $request->order_item_id;
        $status = $request->status;
        $accessToken = Session::get('access_token');

        Log::info('Отправка запроса на завершение элемента заказа', [
            'url' => "https://cafebar-oaba.onrender.com/orders/order-items/{$orderItemId}/status",
            'order_item_id' => $orderItemId,
            'status' => $status,
            'access_token_partial' => substr($accessToken, 0, 10) . '...',
        ]);

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->connectTimeout(5)
                ->patch("https://cafebar-oaba.onrender.com/orders/order-items/{$orderItemId}/status", [
                    'status' => $status
                ]);

            if ($response->successful()) {
                Log::info('Элемент заказа успешно завершён', [
                    'order_item_id' => $orderItemId,
                    'status' => $status,
                    'response' => $response->json(),
                ]);
                return response()->json(['success' => true, 'message' => 'Статус элемента обновлён.']);
            } else {
                Log::error('Ошибка при завершении элемента заказа', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'order_item_id' => $orderItemId,
                ]);
                $message = $response->status() === 404
                    ? 'Элемент заказа не найден. Проверьте ID.'
                    : ($response->status() === 403
                        ? 'У вас нет прав для обновления этого заказа.'
                        : 'Ошибка обновления статуса: сервер вернул ошибку ' . $response->status() . '.');
                return response()->json(['success' => false, 'message' => $message], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при запросе завершения элемента заказа', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_item_id' => $orderItemId,
                'status' => $status,
            ]);
            return response()->json(['success' => false, 'message' => 'Ошибка завершения элемента: проблема с сервером.'], 500);
        }
    }
}