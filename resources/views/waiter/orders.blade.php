@extends('layouts.minimal')

@section('title', 'Заказы официанта')

@section('content')
    <div class="container mx-auto py-8 sm:py-12 px-4 sm:px-6 flex justify-center">
        <div class="w-full max-w-7xl">
            @if (session('success'))
                <div id="success-message" class="notification bg-green-500">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div id="error-message" class="notification bg-red-500">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Панель вкладок -->
            <div class="bg-white rounded-lg shadow-md border border-[#D4A373] mb-6">
                <div class="flex flex-wrap">
                    <button class="tab-btn flex-1 py-2 sm:py-3 px-2 sm:px-4 text-center font-semibold text-[#6B4F4F] border-b-2 border-transparent hover:border-[#A52A2A] text-sm sm:text-base" data-tab="create">Создать заказ</button>
                    <button class="tab-btn flex-1 py-2 sm:py-3 px-2 sm:px-4 text-center font-semibold text-[#6B4F4F] border-b-2 border-transparent hover:border-[#A52A2A] text-sm sm:text-base" data-tab="ready">Готовые</button>
                    <button class="tab-btn flex-1 py-2 sm:py-3 px-2 sm:px-4 text-center font-semibold text-[#6B4F4F] border-b-2 border-transparent hover:border-[#A52A2A] text-sm sm:text-base" data-tab="bookings">Забронированные</button>
                </div>
            </div>

            <!-- Вкладка "Создать заказ" -->
            <div id="create-tab" class="tab-content">
                <h1 class="text-2xl sm:text-4xl font-bold mb-4 sm:mb-6 text-[#6B4F4F] text-center">Создать заказ</h1>
                <form method="POST" action="{{ route('waiter.createOrder') }}" id="order-form">
                    @csrf
                    <div class="mb-4 sm:mb-6">
                        <label for="table_number" class="block text-sm font-medium text-[#6B4F4F]">Выберите столик:</label>
                        @if (empty($availableTables))
                            <p class="text-red-500 text-sm">Нет доступных столиков.</p>
                        @else
                            <select name="table_number" id="table_number" class="mt-1 p-2 sm:p-3 border border-[#D4A373] rounded w-full text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-[#A52A2A]" required>
                                <option value="">-- Выберите столик --</option>
                                @foreach ($availableTables as $table)
                                    <option value="{{ $table }}">Столик {{ $table }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('table_number')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <h2 class="text-lg sm:text-2xl font-semibold mb-4 text-[#6B4F4F] text-center">Меню</h2>
                    @if (empty($groupedMenu))
                        <p class="text-red-500 text-center text-sm sm:text-base">Меню недоступно.</p>
                    @else
                        @foreach ($groupedMenu as $category => $items)
                            <div class="mb-6 sm:mb-8">
                                <h3 class="text-base sm:text-xl font-semibold mb-3 sm:mb-4 text-[#6B4F4F]">{{ $category }}</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                                    @foreach ($items as $item)
                                        <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                                            <img src="{{ !empty($item['images']) && isset($item['images'][0]['image_url']) ? $item['images'][0]['image_url'] : 'https://via.placeholder.com/300x200' }}" alt="{{ $item['name'] }}" class="w-full h-32 sm:h-48 object-cover rounded-lg mb-2 sm:mb-3">
                                            <h4 class="text-sm sm:text-lg font-semibold text-[#6B4F4F]">{{ $item['name'] }}</h4>
                                            <p class="text-gray-600 text-xs sm:text-sm">{{ $item['description'] }}</p>
                                            <p class="text-[#A52A2A] font-semibold mt-1 sm:mt-2 text-sm sm:text-base">{{ $item['price'] }} руб.</p>
                                            <div class="flex items-center mt-2 sm:mt-3 space-x-1">
                                                <button type="button" class="decrement-btn w-8 sm:w-9 h-8 sm:h-9 flex items-center justify-center bg-gray-200 rounded-l hover:bg-gray-300 text-sm sm:text-base z-10 touch-action-manipulation">-</button>
                                                <input type="number" name="items[{{ $item['item_id'] }}][quantity]" min="0" value="0" class="quantity-input w-12 sm:w-14 h-8 sm:h-9 p-1 border-t border-b border-gray-200 text-center text-sm sm:text-base focus:outline-none" data-price="{{ $item['price'] }}" readonly>
                                                <button type="button" class="increment-btn w-8 sm:w-9 h-8 sm:h-9 flex items-center justify-center bg-gray-200 rounded-r hover:bg-gray-300 text-sm sm:text-base z-10 touch-action-manipulation">+</button>
                                                <input type="hidden" name="items[{{ $item['item_id'] }}][item_id]" value="{{ $item['item_id'] }}">
                                            </div>
                                            @error("items.{$item['item_id']}.quantity")
                                                <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                            @error("items.{$item['item_id']}.item_id")
                                                <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        @error('items')
                            <p class="text-red-500 text-sm sm:text-base mt-1 text-center">{{ $message }}</p>
                        @enderror
                    @endif

                    <div class="mt-4 sm:mt-6 text-center">
                        <strong class="text-[#6B4F4F] text-sm sm:text-base">Итоговая сумма: <span id="total-price">0</span> руб.</strong>
                    </div>

                    <div class="mt-4 sm:mt-6 mb-4 sm:mb-6">
                        <label for="comment" class="block text-sm font-medium text-[#6B4F4F]">Комментарий к заказу:</label>
                        <textarea name="comment" id="comment" rows="4" class="mt-1 p-2 sm:p-3 border border-[#D4A373] rounded w-full text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-[#A52A2A]" placeholder="Введите комментарий (например, особые пожелания)"></textarea>
                        @error('comment')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="text-center">
                        <input type="submit" class="btn mt-2 sm:mt-4 px-4 sm:px-6 py-2 text-sm sm:text-base bg-[#A52A2A] text-white rounded hover:bg-[#6B4F4F] focus:outline-none focus:ring-2 focus:ring-[#D4A373] disabled:bg-gray-400 disabled:cursor-not-allowed cursor-pointer" id="submit-btn" value="Создать заказ">
                    </div>
                </form>
            </div>

            <!-- Вкладка "Готовые" -->
            <div id="ready-tab" class="tab-content hidden">
                <h1 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-6 text-[#6B4F4F] text-center">Готовые заказы</h1>
                <div id="ready-orders" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    @if (empty($readyOrders))
                        <p class="text-center text-gray-500 text-sm sm:text-base">Нет готовых заказов</p>
                    @else
                        @foreach ($readyOrders as $order)
                            <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md border border-[#D4A373]" data-order-id="{{ $order['order_id'] }}" data-table-number="{{ $order['table_number'] }}">
                                <h3 class="text-sm sm:text-lg font-semibold text-[#6B4F4F] break-words">Заказ #{{ $order['order_id'] }} (Столик {{ $order['table_number'] }})</h3>
                                @if (!empty($order['comment']))
                                    <p class="text-gray-600 mt-1 sm:mt-2 text-xs sm:text-sm break-words"><strong>Комментарий:</strong> {{ $order['comment'] }}</p>
                                @endif
                                <ul class="text-gray-600 mt-1 sm:mt-2 text-xs sm:text-sm">
                                    @foreach ($order['items'] as $item)
                                        @php
                                            $menuItem = collect($menu)->firstWhere('item_id', $item['item_id']);
                                            $itemName = $menuItem ? $menuItem['name'] : 'Неизвестное блюдо';
                                            $itemStatus = $item['status'] ?? 'Pending';
                                        @endphp
                                        <li class="flex items-center justify-between {{ $itemStatus === 'Ready' ? 'text-green-600' : ($itemStatus === 'Completed' ? 'text-gray-400' : '') }}" data-item-id="{{ $item['order_item_id'] ?? 0 }}">
                                            <span class="break-words">{{ $itemName }} {{ $item['quantity'] > 1 ? "x{$item['quantity']}" : '' }}</span>
                                            @if ($itemStatus === 'Ready' && isset($item['order_item_id']) && $item['order_item_id'] != 0)
                                                <form class="update-item-status-form" data-order-item-id="{{ $item['order_item_id'] }}">
                                                    @csrf
                                                    <input type="hidden" name="order_item_id" value="{{ $item['order_item_id'] }}">
                                                    <input type="hidden" name="status" value="Completed">
                                                    <button type="submit" class="check-btn text-[#6B4F4F] hover:text-[#A52A2A] text-sm sm:text-base p-2 z-10 touch-action-manipulation">
                                                        <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                                                    </button>
                                                </form>
                                            @elseif ($itemStatus === 'Completed')
                                                <span class="text-gray-400 cursor-not-allowed text-sm sm:text-base p-2">
                                                    <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Вкладка "Забронированные" -->
            <div id="bookings-tab" class="tab-content hidden">
                <h1 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-6 text-[#6B4F4F] text-center">Забронированные столики</h1>
                <div id="bookings" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    @if (empty($bookings))
                        <p class="text-center text-gray-500 text-sm sm:text-base">Нет бронирований на сегодня</p>
                    @else
                        @foreach ($bookings as $booking)
                            <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md border border-[#D4A373]">
                                <h3 class="text-sm sm:text-lg font-semibold text-[#6B4F4F]">Столик {{ $booking['table_number'] }}</h3>
                                <p class="text-gray-600 text-xs sm:text-sm">Время: {{ \Carbon\Carbon::parse($booking['booking_time'])->format('H:i') }}</p>
                                <p class="text-gray-600 text-xs sm:text-sm">Имя: {{ $booking['customer_name'] }}</p>
                                <p class="text-gray-600 text-xs sm:text-sm">Телефон: {{ $booking['phone_number'] }}</p>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Адаптивные стили */
        @media (max-width: 640px) {
            .container {
                padding-left: 8px;
                padding-right: 8px;
            }
            .tab-btn {
                padding: 8px 10px;
                font-size: 0.75rem;
            }
            .grid {
                gap: 8px;
            }
            .btn {
                padding: 8px 16px;
                font-size: 0.875rem;
            }
            h1 {
                font-size: 1.25rem;
            }
            h2 {
                font-size: 1rem;
            }
            h3 {
                font-size: 0.875rem;
            }
            select, textarea, input {
                font-size: 0.875rem;
            }
            .quantity-input, .increment-btn, .decrement-btn {
                height: 28px;
                font-size: 0.75rem;
                min-width: 28px;
                touch-action: manipulation;
            }
            .quantity-input {
                width: 40px;
            }
            .check-btn {
                padding: 8px;
                font-size: 1rem;
            }
            .notification {
                width: 90%;
                font-size: 0.75rem;
                padding: 0.75rem 1rem;
            }
        }
        @media (min-width: 641px) and (max-width: 768px) {
            .tab-btn {
                font-size: 0.9rem;
                padding: 10px 12px;
            }
            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            h1 {
                font-size: 1.5rem;
            }
            h2 {
                font-size: 1.25rem;
            }
            h3 {
                font-size: 1rem;
            }
            .check-btn {
                padding: 6px;
                font-size: 1.1rem;
            }
            .notification {
                max-width: 300px;
            }
        }
        @media (min-width: 769px) {
            .notification {
                max-width: 400px;
            }
        }
        .check-btn {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
        }
        .notification {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.75rem 1rem;
            color: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 9998;
            animation: fadeOut 5s forwards;
            border: 2px solid #D4A373;
            width: 90%;
            max-width: 400px;
            text-align: center;
            opacity: 1;
            background-color: #10B981;
        }
        .notification.bg-green-500 {
            background-color: #10B981;
        }
        .notification.bg-red-500 {
            background-color: #EF4444;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }
        .increment-btn, .decrement-btn {
            position: relative;
            z-index: 10;
            touch-action: manipulation;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .quantity-input {
            cursor: default;
        }
        .btn {
            touch-action: manipulation;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .btn:disabled {
            background-color: #D4A373 !important;
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>

    <script>
        console.log('[INFO] Script loaded in waiter/orders.blade.php at {{ now() }}');

        // Инициализация
        const accessToken = "{{ Session::get('access_token') }}";
        const clientId = "{{ Session::get('user_id') }}";
        let socket = null;

        // Доступ к данным меню
        const menuItems = @json(collect($menu)->mapWithKeys(function ($item) {
            return [$item['item_id'] => $item['name']];
        })->toArray());

        function initializeWebSocket() {
            if (!accessToken || !clientId) {
                console.error('[ERROR] Missing access token or client ID', { accessToken, clientId });
                showNotification('Ошибка: не найден токен или ID пользователя', 'error');
                return;
            }

            console.log('[DEBUG] Initializing WebSocket:', { clientId, accessToken: accessToken.substring(0, 10) + '...' });

            try {
                socket = new WebSocket(`wss://cafebar-oaba.onrender.com/ws/${clientId}`);

                socket.onopen = () => {
                    console.log('[INFO] WebSocket connected');
                    try {
                        socket.send(JSON.stringify({ token: accessToken }));
                        console.log('[INFO] WebSocket sent token');
                    } catch (e) {
                        console.error('[ERROR] Failed to send token:', e);
                    }
                };

                socket.onmessage = (event) => {
                    console.log('[RAW] WebSocket raw message:', event.data);
                    try {
                        const data = JSON.parse(event.data);
                        console.log('[INFO] WebSocket message parsed:', data);

                        if (data.type === 'order_update' && data.payload) {
                            const { order_id, status, items, table_number, comment } = data.payload;
                            if (status === 'Ready' || (status === 'In_progress' && items.some(item => item.status === 'Ready'))) {
                                addReadyOrder({ order_id, table_number: table_number || 'N/A', items, comment });
                            } else if (status === 'Completed') {
                                removeOrder(order_id);
                            }
                        } else if (data.type === 'order_item_update' && data.payload) {
                            const { order_id, order_item_id, item_status } = data.payload;
                            updateOrderItemStatus(order_id, order_item_id, item_status);
                        }
                    } catch (e) {
                        console.error('[ERROR] Failed to parse WebSocket message:', e);
                    }
                };

                socket.onclose = (event) => {
                    console.log('[INFO] WebSocket disconnected', { code: event.code, reason: event.reason });
                    setTimeout(initializeWebSocket, 5000);
                };

                socket.onerror = (error) => {
                    console.error('[ERROR] WebSocket error:', error);
                };
            } catch (e) {
                console.error('[ERROR] Failed to initialize WebSocket:', e);
                setTimeout(initializeWebSocket, 5000);
            }
        }

        // Функция для добавления заказа в DOM
        function addReadyOrder(order) {
            const ordersContainer = document.getElementById('ready-orders');
            const existingOrder = ordersContainer.querySelector(`[data-order-id="${order.order_id}"]`);
            if (existingOrder) {
                console.log('[INFO] Order already exists:', order.order_id);
                updateOrder(order);
                return;
            }

            const itemsWithNames = order.items.map(item => ({
                ...item,
                name: menuItems[item.item_id] || 'Неизвестное блюдо',
                status: item.status || 'Pending',
                order_item_id: item.order_item_id || 0
            }));

            const orderDiv = document.createElement('div');
            orderDiv.className = 'bg-white p-3 sm:p-4 rounded-lg shadow-md border border-[#D4A373]';
            orderDiv.dataset.orderId = order.order_id;
            orderDiv.dataset.tableNumber = order.table_number || 'N/A';
            orderDiv.innerHTML = `
                <h3 class="text-sm sm:text-lg font-semibold text-[#6B4F4F] break-words">Заказ #${order.order_id} (Столик ${order.table_number || 'N/A'})</h3>
                ${order.comment ? `<p class="text-gray-600 mt-1 sm:mt-2 text-xs sm:text-sm break-words"><strong>Комментарий:</strong> ${order.comment}</p>` : ''}
                <ul class="text-gray-600 mt-1 sm:mt-2 text-xs sm:text-sm">
                    ${itemsWithNames.map(item => `
                        <li class="flex items-center justify-between ${item.status === 'Ready' ? 'text-green-600' : (item.status === 'Completed' ? 'text-gray-400' : '')}" data-item-id="${item.order_item_id}">
                            <span class="break-words">${item.name} ${item.quantity > 1 ? 'x' + item.quantity : ''}</span>
                            ${item.status === 'Ready' && item.order_item_id > 0 ? `
                                <form class="update-item-status-form" data-order-item-id="${item.order_item_id}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="order_item_id" value="${item.order_item_id}">
                                    <input type="hidden" name="status" value="Completed">
                                    <button type="submit" class="check-btn text-[#6B4F4F] hover:text-[#A52A2A] text-sm sm:text-base p-2 z-10 touch-action-manipulation">
                                        <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                                    </button>
                                </form>
                            ` : item.status === 'Completed' ? `
                                <span class="text-gray-400 cursor-not-allowed text-sm sm:text-base p-2">
                                    <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                                </span>
                            ` : ''}
                        </li>
                    `).join('')}
                </ul>
            `;
            ordersContainer.prepend(orderDiv);
            console.log('[INFO] Added order:', order.order_id);

            const noOrdersMessage = ordersContainer.querySelector('p.text-center.text-gray-500');
            if (noOrdersMessage) {
                noOrdersMessage.remove();
            }
        }

        // Функция для обновления существующего заказа
        function updateOrder(order) {
            const orderElement = document.querySelector(`#ready-orders [data-order-id="${order.order_id}"]`);
            if (!orderElement) {
                console.log('[INFO] Order not found for update:', order.order_id);
                return;
            }

            const itemsWithNames = order.items.map(item => ({
                ...item,
                name: menuItems[item.item_id] || 'Неизвестное блюдо',
                status: item.status || 'Pending',
                order_item_id: item.order_item_id || 0
            }));

            const ulElement = orderElement.querySelector('ul.text-gray-600');
            ulElement.innerHTML = itemsWithNames.map(item => `
                <li class="flex items-center justify-between ${item.status === 'Ready' ? 'text-green-600' : (item.status === 'Completed' ? 'text-gray-400' : '')}" data-item-id="${item.order_item_id}">
                    <span class="break-words">${item.name} ${item.quantity > 1 ? 'x' + item.quantity : ''}</span>
                    ${item.status === 'Ready' && item.order_item_id > 0 ? `
                        <form class="update-item-status-form" data-order-item-id="${item.order_item_id}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="order_item_id" value="${item.order_item_id}">
                            <input type="hidden" name="status" value="Completed">
                            <button type="submit" class="check-btn text-[#6B4F4F] hover:text-[#A52A2A] text-sm sm:text-base p-2 z-10 touch-action-manipulation">
                                <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                            </button>
                        </form>
                    ` : item.status === 'Completed' ? `
                        <span class="text-gray-400 cursor-not-allowed text-sm sm:text-base p-2">
                            <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                        </span>
                    ` : ''}
                </li>
            `).join('');

            const existingComment = orderElement.querySelector('p.text-gray-600');
            if (existingComment) {
                if (order.comment) {
                    existingComment.innerHTML = `<strong>Комментарий:</strong> ${order.comment}`;
                } else {
                    existingComment.remove();
                }
            } else if (order.comment) {
                const commentP = document.createElement('p');
                commentP.className = 'text-gray-600 mt-1 sm:mt-2 text-xs sm:text-sm break-words';
                commentP.innerHTML = `<strong>Комментарий:</strong> ${order.comment}`;
                orderElement.insertBefore(commentP, ulElement);
            }

            console.log('[INFO] Updated order:', order.order_id);
        }

        // Функция для обновления статуса элемента заказа
        function updateOrderItemStatus(orderId, orderItemId, status) {
            const orderElement = document.querySelector(`#ready-orders [data-order-id="${orderId}"]`);
            if (!orderElement) {
                console.log('[INFO] Order not found in Ready:', orderId);
                return;
            }

            const itemElement = orderElement.querySelector(`[data-item-id="${orderItemId}"]`);
            if (!itemElement) {
                console.log('[INFO] Item not found in order:', orderItemId);
                return;
            }

            console.log('[DEBUG] Updating item status in DOM:', { orderId, orderItemId, status });

            if (status === 'Completed') {
                itemElement.classList.remove('text-green-600');
                itemElement.classList.add('text-gray-400');
                const form = itemElement.querySelector('form.update-item-status-form');
                if (form) {
                    const span = document.createElement('span');
                    span.className = 'text-gray-400 cursor-not-allowed text-sm sm:text-base p-2';
                    span.innerHTML = '<i class="fas fa-check-circle text-lg sm:text-xl"></i>';
                    itemElement.replaceChild(span, form);
                    console.log('[INFO] Updated item status to Completed:', orderItemId);
                }

                const items = orderElement.querySelectorAll('li');
                const allCompleted = Array.from(items).every(item => {
                    return item.querySelector('.cursor-not-allowed') !== null || !item.querySelector('form.update-item-status-form');
                });
                if (allCompleted) {
                    removeOrder(orderId);
                    console.log('[INFO] All items completed, removing order:', orderId);
                    showNotification('Заказ #' + orderId + ' завершён', 'success');
                }
            } else if (status === 'Ready') {
                itemElement.classList.add('text-green-600');
                itemElement.classList.remove('text-gray-400');
                const span = itemElement.querySelector('span.cursor-not-allowed');
                if (span) {
                    const form = document.createElement('form');
                    form.className = 'update-item-status-form';
                    form.dataset.orderItemId = orderItemId;
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="order_item_id" value="${orderItemId}">
                        <input type="hidden" name="status" value="Completed">
                        <button type="submit" class="check-btn text-[#6B4F4F] hover:text-[#A52A2A] text-sm sm:text-base p-2 z-10 touch-action-manipulation">
                            <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                        </button>
                    `;
                    itemElement.replaceChild(form, span);
                    console.log('[INFO] Restored item status to Ready:', orderItemId);
                }
            }
        }

        // Функция для удаления заказа из DOM
        function removeOrder(orderId) {
            const orderElement = document.querySelector(`[data-order-id="${orderId}"]`);
            if (orderElement) {
                orderElement.remove();
                console.log('[INFO] Removed order:', orderId);

                const ordersContainer = document.getElementById('ready-orders');
                if (!ordersContainer.hasChildNodes()) {
                    const noOrdersMessage = document.createElement('p');
                    noOrdersMessage.className = 'text-center text-gray-500 text-sm sm:text-base';
                    noOrdersMessage.textContent = 'Нет готовых заказов';
                    ordersContainer.appendChild(noOrdersMessage);
                }
            }
        }

        // Обработка AJAX для обновления статуса элемента
        function handleUpdateItemStatus(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const form = e.target;
            const formData = new FormData(form);
            const orderItemId = parseInt(formData.get('order_item_id'));
            const status = formData.get('status');
            const orderId = parseInt(form.closest('[data-order-id]').dataset.orderId);
            const submitBtn = form.querySelector('.check-btn');

            console.log('[DEBUG] Updating item status:', { orderItemId, status, orderId });

            if (!orderItemId || orderItemId <= 0) {
                console.error('[ERROR] Invalid order_item_id:', orderItemId);
                showNotification('Ошибка: некорректный ID элемента заказа', 'error');
                return;
            }

            submitBtn.disabled = true;

            fetch("{{ route('waiter.completeOrder') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                submitBtn.disabled = false;
                console.log('[DEBUG] Status update response status:', response.status);
                if (!response.ok) {
                    return response.json().then(error => {
                        console.error('[ERROR] Status update failed:', error);
                        throw new Error(error.message || `Ошибка сервера: ${response.status}`);
                    }).catch(() => {
                        throw new Error(`Ошибка сервера: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('[INFO] AJAX response data:', data);
                if (data.success) {
                    console.log('[INFO] Item status updated:', orderItemId, status);
                    showNotification('Статус элемента обновлён', 'success');
                    updateOrderItemStatus(orderId, orderItemId, status);
                } else {
                    console.error('[ERROR] Failed to update item status:', data.message);
                    showNotification(data.message || 'Ошибка обновления статуса элемента', 'error');
                }
            })
            .catch(error => {
                console.error('[ERROR] AJAX request failed:', error);
                submitBtn.disabled = false;
                showNotification('Ошибка: ' + error.message, 'error');
            });
        }

        // Функция для обновления итоговой суммы
        function updateTotalPrice() {
            let total = 0;
            document.querySelectorAll('.quantity-input').forEach(input => {
                const quantity = parseInt(input.value) || 0;
                const price = parseFloat(input.dataset.price) || 0;
                total += quantity * price;
            });
            document.getElementById('total-price').textContent = total.toFixed(2);
            console.log('[INFO] Total price updated:', total.toFixed(2));
        }

        // Обработчики для кнопок плюс и минус
        document.querySelectorAll('.increment-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                let quantity = parseInt(input.value) || 0;
                input.value = quantity + 1;
                updateTotalPrice();
                console.log('[INFO] Incremented:', input.name, 'to', input.value);
            });
            btn.addEventListener('touchstart', (e) => {
                e.preventDefault();
                console.log('[EVENT] Touchstart on increment button');
                btn.click();
            });
        });

        document.querySelectorAll('.decrement-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.nextElementSibling;
                let quantity = parseInt(input.value) || 0;
                if (quantity > 0) {
                    input.value = quantity - 1;
                    updateTotalPrice();
                    console.log('[INFO] Decremented:', input.name, 'to', input.value);
                }
            });
            btn.addEventListener('touchstart', (e) => {
                e.preventDefault();
                console.log('[EVENT] Touchstart on decrement button');
                btn.click();
            });
        });

        // Валидация и отправка формы
        function validateAndSubmitForm(e) {
            e.preventDefault();
            console.log('[EVENT] Form submission triggered');

            const form = e.target;
            const tableNumber = document.getElementById('table_number').value;
            const submitBtn = document.getElementById('submit-btn');

            console.log('[DEBUG] Starting validation:', { tableNumber });

            // Фронтенд-валидация
            if (!tableNumber) {
                console.error('[ERROR] Table number is empty');
                showNotification('Ошибка: Пожалуйста, выберите столик', 'error');
                alert('[DEBUG] Ошибка: Пожалуйста, выберите столик');
                submitBtn.disabled = false;
                return;
            }

            const items = [];
            let hasValidItem = false;
            const inputs = document.querySelectorAll('.quantity-input');
            inputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                const itemInput = input.closest('div').querySelector('[name*="[item_id"]');
                if (!itemInput) {
                    console.error('[ERROR] Item ID not found for input:', input.name);
                    showNotification('Ошибка: некорректный ID позиции', 'error');
                    alert('[DEBUG] Ошибка: некорректный ID позиции');
                    submitBtn.disabled = false;
                    return;
                }
                const itemId = parseInt(itemInput.value);
                if (quantity > 0) {
                    items.push({ item_id: itemId, quantity });
                    hasValidItem = true;
                }
            });

            if (!hasValidItem) {
                console.error('[ERROR] No valid items in order');
                showNotification('Ошибка: Заказ пустой, добавьте хотя бы одну позицию', 'error');
                alert('[DEBUG] Ошибка: Заказ пустой');
                submitBtn.disabled = false;
                return;
            }

            console.log('[INFO] Validation passed:', { tableNumber, items });
            submitBtn.disabled = true;

            const formData = new FormData(form);
            fetch('{{ route('waiter.createOrder') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + accessToken
                },
                body: formData
            })
            .then(response => {
                submitBtn.disabled = false;
                console.log('[DEBUG] Fetch response status:', response.status);
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const error = JSON.parse(text);
                            let message = 'Ошибка сервера';
                            if (error.errors) {
                                message = Object.values(error.errors).flat().join(', ');
                            } else if (error.message) {
                                message = error.message;
                            }
                            throw new Error(message);
                        } catch {
                            throw new Error(`HTTP error: ${text || response.status}`);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('[DATA] Server response:', data);
                if (data.success) {
                    showNotification('Заказ успешно создан!', 'success');
                    form.reset();
                    document.getElementById('table_number').value = '';
                    updateTotalPrice();
                    console.log('[SUCCESS] Order created successfully');
                } else {
                    console.error('[ERROR] Order creation failed:', data.message);
                    showNotification(data.message || 'Ошибка при создании заказа', 'error');
                    alert('[DEBUG] Ошибка: ' + (data.message || 'Создание заказа'));
                }
            })
            .catch(error => {
                console.error('[ERROR] AJAX error:', error);
                submitBtn.disabled = false;
                showNotification('Ошибка: ' + error.message, 'error');
                alert('[DEBUG] AJAX error: ' + error.message);
            });
        }

        // Функция для показа уведомлений
        function showNotification(message, type) {
            console.log('[NOTIFY] Showing notification:', { message, type });
            const existing = document.querySelector('.notification');
            if (existing) {
                existing.remove();
            }
            const notifyDiv = document.createElement('div');
            notifyDiv.className = `notification ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            notifyDiv.textContent = message;
            document.body.appendChild(notifyDiv);
            setTimeout(() => {
                notifyDiv.style.opacity = '0';
                setTimeout(() => notifyDiv.remove(), 300);
            }, 5000);
        }

        // Переключение вкладок
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                console.log('[INFO] Tab selected:', button.dataset.tab);
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active', 'border-[#A52A2A]');
                    btn.classList.add('border-transparent');
                });
                button.classList.add('active', 'border-[#A52A2A]');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                document.getElementById(`${button.dataset.tab}-tab`).classList.remove('hidden');
                sessionStorage.setItem('selectedTab', button.dataset.tab);
            });
        });

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', () => {
            console.log('[EVENT] DOM content loaded');
            initializeWebSocket();

            const activeTab = sessionStorage.getItem('selectedTab') || 'create';
            document.querySelectorAll('.tab-btn').forEach(button => {
                button.classList.remove('active', 'border-[#A52A2A]');
                button.classList.add('border-transparent');
                if (button.dataset.tab === activeTab) {
                    button.classList.add('active', 'border-[#A52A2A]');
                }
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
                if (content.id === `${activeTab}-tab`) {
                    content.classList.remove('hidden');
                }
            });

            const notify = document.querySelector('.notification');
            if (notify) {
                console.log('[INFO] Found initial notification');
                setTimeout(() => {
                    notify.style.opacity = '0';
                    setTimeout(() => notify.remove(), 300);
                }, 5000);
            }

            const form = document.getElementById('order-form');
            const submitBtn = document.getElementById('submit-btn');

            if (form) {
                form.addEventListener('submit', validateAndSubmitForm);

                submitBtn.addEventListener('click', (e) => {
                    console.log('[EVENT] Submit button clicked');
                    validateAndSubmitForm(e);
                });

                submitBtn.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    console.log('[EVENT] Submit button touched');
                    submitBtn.click();
                });
            }

            document.addEventListener('submit', (event) => {
                if (event.target.classList.contains('update-item-status-form')) {
                    handleUpdateItemStatus(event);
                }
            });
        });
    </script>
@endsection