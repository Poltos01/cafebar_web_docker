@extends('layouts.minimal')

@section('title', 'Заказы бармена')

@section('content')
    <div class="container mx-auto py-8 sm:py-12 px-4 sm:px-6">
        @if (session('status') || session('success'))
            <div id="session-message" class="notification bg-green-500">{{ session('success') ?? session('status') }}</div>
        @endif
        @if (session('error_message') === 'error')
            <div id="error-message" class="notification bg-red-700">{{ 'Ошибка' }}</div>
        @endif

        <!-- Панель вкладок -->
        <div class="bg-white rounded-lg shadow-md border-b border-[#D4A373] mb-4">
            <div class="flex flex-wrap">
                <button class="tab-btn flex-1 py-3 px-4 sm:px-6 text-center font-semibold text-[#6B5B4F] border-b-2 border-transparent hover:border-[#A52A2A] active text-sm sm:text-base" data-tab="pending">В ожидании</button>
                <button class="tab-btn flex-1 py-3 px-4 sm:px-6 text-center font-semibold text-[#6B5B4F] border-b-2 border-transparent hover:border-[#A52A2A] text-sm sm:text-base" data-tab="in-progress">В процессе</button>
            </div>
        </div>

        <!-- Вкладка "В ожидании" -->
        <div id="pending-tab" class="tab-content">
            <h1 class="text-xl sm:text-2xl font-semibold mb-4 text-[#6B5B4F] text-center">Заказы в ожидании</h1>
            <div id="pending-orders" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @if (empty($pendingOrders))
                    <p class="text-gray-500 text-center">Пока нет заказов</p>
                @else
                    @foreach ($pendingOrders as $order)
                        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-[#D4A373] min-w-[250px]" data-order-id="{{ $order['order_id'] }}" data-table-number="{{ $order['table_number'] }}">
                            <h3 class="text-base sm:text-lg font-semibold text-[#6B5B4F] break-words">Заказ №{{ $order['order_id'] }} (Столик {{ $order['table_number'] }})</h3>
                            @if (isset($order['comment']) && $order['comment'])
                                <p class="text-gray-600 mt-2 text-sm sm:text-base break-words"><strong>Комментарий:</strong> {{ $order['comment'] }}</p>
                            @endif
                            <ul class="text-gray-600 mt-2 text-sm sm:text-base">
                                @foreach ($order['items'] as $item)
                                    <li data-item-id="{{ $item['order_item_id'] ?? 0 }}" data-item-name="{{ isset($menuMap[$item['item_id']]) ? $menuMap[$item['item_id']]['name'] : 'Неизвестный элемент' }}" class="break-words">
                                        {{ isset($menuMap[$item['item_id']]) ? $menuMap[$item['item_id']]['name'] : 'Неизвестный элемент' }} {{ $item['quantity'] > 1 ? "x{$item['quantity']}" : '' }}
                                    </li>
                                @endforeach
                            </ul>
                            <form class="mt-4 assign-order-form">
                                @csrf
                                <input type="hidden" name="order_id" value="{{ $order['order_id'] }}">
                                <button type="submit" class="btn bg-[#D4A373] text-white px-4 py-2 rounded hover:bg-[#A67B5B] w-full sm:w-auto text-sm sm:text-base">Взять в работу</button>
                            </form>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Вкладка "В процессе" -->
        <div id="in-progress-tab" class="tab-content hidden">
            <h1 class="text-xl sm:text-2xl font-semibold mb-4 text-[#6B5B4F] text-center">Заказы в процессе</h1>
            <div id="in-progress-orders" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @if (empty($inProgressOrders))
                    <p class="text-gray-500 text-center">Пока нет заказов</p>
                @else
                    @foreach ($inProgressOrders as $order)
                        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-[#D4A373] min-w-[250px]" data-order-id="{{ $order['order_id'] }}" data-table-number="{{ $order['table_number'] }}">
                            <h3 class="text-base sm:text-lg font-semibold text-[#6B5B4F] break-words">Заказ №{{ $order['order_id'] }} (Столик {{ $order['table_number'] }})</h3>
                            @if (isset($order['comment']) && $order['comment'])
                                <p class="text-gray-600 mt-2 text-sm sm:text-base break-words"><strong>Комментарий:</strong> {{ $order['comment'] }}</p>
                            @endif
                            <ul class="text-gray-600 mt-2 text-sm sm:text-base">
                                @foreach ($order['items'] as $item)
                                    <li class="flex items-center justify-between" data-item-id="{{ $item['order_item_id'] ?? 0 }}" data-item-name="{{ isset($menuMap[$item['item_id']]) ? $menuMap[$item['item_id']]['name'] : 'Неизвестный элемент' }}">
                                        <span class="break-words">{{ isset($menuMap[$item['item_id']]) ? $menuMap[$item['item_id']]['name'] : 'Неизвестный элемент' }} {{ $item['quantity'] > 1 ? "x{$item['quantity']}" : '' }}</span>
                                        @if (($item['status'] ?? 'In_progress') === 'In_progress' && ($item['order_item_id'] ?? 0) > 0)
                                            <form class="update-item-status-form" data-order-item-id="{{ $item['order_item_id'] }}">
                                                @csrf
                                                <input type="hidden" name="order_item_id" value="{{ $item['order_item_id'] }}">
                                                <input type="hidden" name="status" value="Ready">
                                                <button type="submit" class="text-[#6B5B4F] hover:text-[#A52A2A] text-lg sm:text-xl">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-400 cursor-not-allowed text-lg sm:text-xl">
                                                <i class="fas fa-check-circle"></i>
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
    </div>

    <style>
        /* Адаптивные стили для мобильных устройств */
        @media (max-width: 640px) {
            .tab-btn {
                padding: 8px 12px;
                font-size: 0.875rem; /* Меньший шрифт для вкладок */
            }
            .grid {
                gap: 8px; /* Меньший отступ между карточками */
            }
            .min-w-\[250px\] {
                min-width: 100%; /* Карточки занимают всю ширину на мобильных */
            }
            .btn {
                padding: 10px 16px; /* Увеличенные отступы для кнопок */
                font-size: 0.875rem;
            }
            h1 {
                font-size: 1.25rem; /* Меньший заголовок */
            }
            .container {
                padding-left: 8px;
                padding-right: 8px; /* Меньшие боковые отступы */
            }
        }
        @media (min-width: 641px) and (max-width: 768px) {
            .tab-btn {
                font-size: 0.9rem;
            }
            .btn {
                padding: 10px 20px;
            }
        }
    </style>

    <script>
        console.log('[INFO] Script loaded in barkeeper/orders.blade.php at {{ now() }}');

        const accessToken = "{{ Session::get('access_token') }}";
        const userId = "{{ Session::get('user_id') }}";
        let socket = null;

        const menuItems = @json(collect($menuMap)->mapWithKeys(function ($item, $id) {
            return [$id => ['name' => $item['name'] ?? 'Неизвестный элемент', 'category' => strtolower($item['category'] ?? 'unknown')]];
        })->toArray());

        function initializeWebSocket() {
            if (!accessToken || !userId) {
                console.error('[ERROR] Missing access token or user ID', { accessToken, userId });
                showNotification('Ошибка: не найден токен или ID пользователя', 'error');
                return;
            }

            console.log('[DEBUG] Initializing WebSocket with:', { userId, accessToken: accessToken.substring(0, 10) + '...' });

            try {
                socket = new WebSocket(`wss://cafebar-oaba.onrender.com/ws/${userId}`);

                socket.onopen = () => {
                    console.log('[INFO] WebSocket connected');
                    socket.send(JSON.stringify({ token: accessToken }));
                    console.log('[INFO] WebSocket sent token');
                };

                socket.onmessage = (event) => {
                    console.log('[RAW] WebSocket raw message:', event.data);
                    try {
                        const data = JSON.parse(event.data);
                        console.log('[INFO] WebSocket message parsed:', data);

                        if (data.type === 'order_update' && data.payload) {
                            const { order_id, status, table_number, items, comment } = data.payload;

                            fetch("{{ route('proxy.order.staff', ['order_id' => ':order_id']) }}".replace(':order_id', order_id), {
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            })
                            .then(response => {
                                if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
                                return response.json();
                            })
                            .then(staff => {
                                console.log('[DEBUG] Staff data:', staff);
                                const isStaffArray = Array.isArray(staff);
                                const isAssignedToCurrentUser = isStaffArray && staff.some(user => user.user_id == userId && user.role.toLowerCase() === 'barkeeper');
                                const hasBarkeeper = isStaffArray && staff.some(user => user.role.toLowerCase() === 'barkeeper');
                                console.log('[DEBUG] WebSocket order assignment check:', { order_id, isAssignedToCurrentUser, hasBarkeeper, status });

                                if (status === 'Pending' || (status === 'In_progress' && !hasBarkeeper)) {
                                    addPendingOrder({ order_id, table_number: table_number || 'N/A', items, comment });
                                } else if (status === 'In_progress' && isAssignedToCurrentUser) {
                                    moveToInProgress({ order_id, table_number: table_number || 'N/A', items, comment });
                                } else if (status === 'Ready') {
                                    removeOrder(order_id);
                                }
                            })
                            .catch(error => {
                                console.error('[ERROR] Failed to check order assignment:', error);
                                showNotification('Ошибка проверки привязки заказа', 'error');
                            });
                        } else if (data.type === 'order_item_update' && data.payload) {
                            const { order_id, order_item_id, item_status } = data.payload;
                            updateOrderItemStatus(order_id, order_item_id, item_status);
                        }
                    } catch (e) {
                        console.error('[ERROR] Failed to parse WebSocket message:', e);
                        showNotification('Ошибка обработки сообщения WebSocket', 'error');
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

        function filterOrderItems(items) {
            if (!items || !Array.isArray(items)) {
                console.warn('[WARN] No items or invalid items array:', items);
                return [];
            }
            const filteredItems = items.filter(item => {
                const menuItem = menuItems[item.item_id];
                const category = menuItem ? menuItem.category : 'unknown';
                if (category !== 'drink') {
                    console.log('[INFO] Skipping non-drink item:', { item_id: item.item_id, category });
                    return false;
                }
                return true;
            });
            console.log('[DEBUG] Filtered items:', filteredItems);
            return filteredItems;
        }

        function addPendingOrder(order) {
            const filteredItems = filterOrderItems(order.items);
            if (filteredItems.length === 0) {
                console.log('[INFO] No drinks in order, skipping:', order.order_id);
                return;
            }

            const ordersContainer = document.getElementById('pending-orders');
            const existingOrder = ordersContainer.querySelector(`[data-order-id="${order.order_id}"]`);
            if (existingOrder) {
                console.log('[INFO] Order already exists in Pending:', order.order_id);
                return;
            }

            const itemsWithNames = filteredItems.map(item => ({
                ...item,
                name: menuItems[item.item_id]?.name || 'Неизвестный элемент',
                order_item_id: item.order_item_id || 0
            }));

            const orderDiv = document.createElement('div');
            orderDiv.className = 'bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-[#D4A373] min-w-[250px]';
            orderDiv.dataset.orderId = order.order_id;
            orderDiv.dataset.tableNumber = order.table_number || 'N/A';
            orderDiv.innerHTML = `
                <h3 class="text-base sm:text-lg font-semibold text-[#6B5B4F] break-words">Заказ №${order.order_id} (Столик ${order.table_number || 'N/A'})</h3>
                ${order.comment ? `<p class="text-gray-600 mt-2 text-sm sm:text-base break-words"><strong>Комментарий:</strong> ${order.comment}</p>` : ''}
                <ul class="text-gray-600 mt-2 text-sm sm:text-base">
                    ${itemsWithNames.map(item => `
                        <li data-item-id="${item.order_item_id}" data-item-name="${item.name}" class="break-words">${item.name}${item.quantity > 1 ? ' x' + item.quantity : ''}</li>
                    `).join('')}
                </ul>
                <form class="mt-4 assign-order-form">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="order_id" value="${order.order_id}">
                    <button type="submit" class="btn bg-[#D4A373] text-white px-4 py-2 rounded hover:bg-[#A67B5B] w-full sm:w-auto text-sm sm:text-base">Взять в работу</button>
                </form>
            `;
            ordersContainer.appendChild(orderDiv);
            console.log('[INFO] Added order to Pending:', { order_id: order.order_id, items: itemsWithNames });

            const noOrdersMessage = ordersContainer.querySelector('p.text-gray-500');
            if (noOrdersMessage) noOrdersMessage.remove();
        }

        function moveToInProgress(order) {
            console.log('[INFO] Moving order to InProgress:', { order_id: order.order_id, items: order.items });

            fetch("{{ route('proxy.order', ['order_id' => ':order_id']) }}".replace(':order_id', order.order_id), {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(response => response.ok ? response.json() : Promise.reject(`HTTP error: ${response.status}`))
            .then(data => {
                if (data.status !== 'In_progress') {
                    console.log('[INFO] Order not In_progress, status:', data.status);
                    addPendingOrder(order);
                    return;
                }

                const filteredItems = filterOrderItems(order.items);
                if (filteredItems.length === 0) {
                    console.log('[INFO] No drinks in order, skipping:', order.order_id);
                    return;
                }

                const itemsWithNames = filteredItems.map(item => ({
                    ...item,
                    name: menuItems[item.item_id]?.name || 'Неизвестный элемент',
                    status: item.status || 'In_progress',
                    order_item_id: item.order_item_id || 0
                }));
                console.log('[DEBUG] Items for InProgress:', itemsWithNames);

                const pendingOrder = document.querySelector(`#pending-orders [data-order-id="${order.order_id}"]`);
                if (pendingOrder) {
                    pendingOrder.remove();
                    console.log('[INFO] Removed order from Pending:', order.order_id);
                    checkEmptyPendingOrders();
                }

                const inProgressContainer = document.getElementById('in-progress-orders');
                if (inProgressContainer.querySelector(`[data-order-id="${order.order_id}"]`)) {
                    console.log('[INFO] Order already in In_progress:', order.order_id);
                    return;
                }

                const orderDiv = document.createElement('div');
                orderDiv.className = 'bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-[#D4A373] min-w-[250px]';
                orderDiv.dataset.orderId = order.order_id;
                orderDiv.dataset.tableNumber = order.table_number || 'N/A';
                orderDiv.innerHTML = `
                    <h3 class="text-base sm:text-lg font-semibold text-[#6B5B4F] break-words">Заказ №${order.order_id} (Столик ${order.table_number || 'N/A'})</h3>
                    ${order.comment ? `<p class="text-gray-600 mt-2 text-sm sm:text-base break-words"><strong>Комментарий:</strong> ${order.comment}</p>` : ''}
                    <ul class="text-gray-600 mt-2 text-sm sm:text-base">
                        ${itemsWithNames.map(item => `
                            <li class="flex items-center justify-between" data-item-id="${item.order_item_id}" data-item-name="${item.name}">
                                <span class="break-words">${item.name}${item.quantity > 1 ? ' x' + item.quantity : ''}</span>
                                ${item.status === 'In_progress' && item.order_item_id ? `
                                    <form class="update-item-status-form" data-order-item-id="${item.order_item_id}">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="order_item_id" value="${item.order_item_id}">
                                        <input type="hidden" name="status" value="Ready">
                                        <button type="submit" class="text-[#6B5B4F] hover:text-[#A52A2A] text-lg sm:text-xl">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                ` : `
                                    <span class="text-gray-400 cursor-not-allowed text-lg sm:text-xl">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                `}
                            </li>
                        `).join('')}
                    </ul>
                `;

                // Проверяем готовность всех элементов после добавления
                const allBarkeeperItemsReady = itemsWithNames.every(item => item.status === 'Ready' || !item.order_item_id);
                console.log('[DEBUG] All barkeeper items ready on move:', { order_id: order.order_id, allBarkeeperItemsReady });
                if (allBarkeeperItemsReady) {
                    console.log('[INFO] All items ready on move, removing order:', order.order_id);
                    return;
                }

                inProgressContainer.prepend(orderDiv);
                console.log('[INFO] Added order to In_progress:', { order_id: order.order_id, items: itemsWithNames });

                const noOrdersMessage = inProgressContainer.querySelector('p.text-gray-500');
                if (noOrdersMessage) noOrdersMessage.remove();
            })
            .catch(error => {
                console.error('[ERROR] Failed to fetch order:', error);
                addPendingOrder(order);
            });
        }

        function updateOrderItemStatus(orderId, orderItemId, status) {
            console.log('[INFO] Updating item status:', { orderId, orderItemId, status });

            const orderElement = document.querySelector(`#in-progress-orders [data-order-id="${orderId}"]`);
            if (!orderElement) {
                console.log('[INFO] Order not found in In_progress:', orderId);
                return;
            }

            const itemElement = orderElement.querySelector(`[data-item-id="${orderItemId}"]`);
            if (!itemElement) {
                console.log('[INFO] Item not found:', orderItemId);
                return;
            }

            if (status === 'Ready') {
                const form = itemElement.querySelector('form.update-item-status-form');
                if (form) {
                    const span = document.createElement('span');
                    span.className = 'text-gray-400 cursor-not-allowed text-lg sm:text-xl';
                    span.innerHTML = '<i class="fas fa-check-circle"></i>';
                    form.replaceWith(span);
                    console.log('[INFO] Item marked Ready:', orderItemId);
                }

                // Проверяем статусы всех элементов заказа через API
                fetch("{{ route('proxy.order', ['order_id' => ':order_id']) }}".replace(':order_id', orderId), {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(response => response.ok ? response.json() : Promise.reject(`HTTP error: ${response.status}`))
                .then(data => {
                    const items = filterOrderItems(data.items || []);
                    const allBarkeeperItemsReady = items.every(item => item.status === 'Ready');
                    console.log('[DEBUG] All barkeeper items ready via API:', { orderId, allBarkeeperItemsReady, items });

                    if (allBarkeeperItemsReady) {
                        console.log('[INFO] All barkeeper items ready via API, removing order:', orderId);
                        removeOrder(orderId);
                    }
                })
                .catch(error => {
                    console.error('[ERROR] Failed to fetch order for status check:', error);
                    showNotification('Ошибка проверки статуса заказа', 'error');
                });
            } else if (status === 'In_progress') {
                const span = itemElement.querySelector('span.text-gray-400');
                if (span) {
                    const form = document.createElement('form');
                    form.className = 'update-item-status-form';
                    form.dataset.orderItemId = orderItemId;
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="order_item_id" value="${orderItemId}">
                        <input type="hidden" name="status" value="Ready">
                        <button type="submit" class="text-[#6B5B4F] hover:text-[#A52A2A] text-lg sm:text-xl">
                            <i class="fas fa-check-circle"></i>
                        </button>
                    `;
                    span.replaceWith(form);
                    console.log('[INFO] Item restored to In_progress:', orderItemId);
                }
            }
        }

        function checkOrderCompletionOnLoad() {
            const orders = document.querySelectorAll('#in-progress-orders [data-order-id]');
            orders.forEach(orderElement => {
                const orderId = orderElement.dataset.orderId;
                fetch("{{ route('proxy.order', ['order_id' => ':order_id']) }}".replace(':order_id', orderId), {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(response => response.ok ? response.json() : Promise.reject(`HTTP error: ${response.status}`))
                .then(data => {
                    const items = filterOrderItems(data.items || []);
                    const allBarkeeperItemsReady = items.every(item => item.status === 'Ready');
                    console.log('[DEBUG] Initial check for order:', { orderId, allBarkeeperItemsReady, items });

                    if (allBarkeeperItemsReady) {
                        console.log('[INFO] All items ready on load, removing order:', orderId);
                        removeOrder(orderId);
                    }
                })
                .catch(error => {
                    console.error('[ERROR] Failed to check order on load:', error);
                });
            });
        }

        function removeOrder(orderId) {
            const orderElement = document.querySelector(`[data-order-id="${orderId}"]`);
            if (!orderElement) {
                console.log('[INFO] Order not found in DOM:', orderId);
                return;
            }

            const parentContainer = orderElement.parentNode;
            orderElement.remove();
            console.log('[INFO] Removed order:', orderId);

            if (parentContainer.id === 'pending-orders') {
                checkEmptyPendingOrders();
            } else if (parentContainer.id === 'in-progress-orders') {
                checkEmptyInProgressOrders();
            }
        }

        function checkEmptyPendingOrders() {
            const container = document.getElementById('pending-orders');
            if (!container.querySelector('[data-order-id]')) {
                container.innerHTML = '<p class="text-gray-500 text-center">Пока нет заказов</p>';
            }
        }

        function checkEmptyInProgressOrders() {
            const container = document.getElementById('in-progress-orders');
            if (!container.querySelector('[data-order-id]')) {
                container.innerHTML = '<p class="text-gray-500 text-center">Пока нет заказов</p>';
            }
        }

        function handleOrderAssignment(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const form = e.target;
            const formData = new FormData(form);
            const orderId = parseInt(formData.get('order_id'));

            console.log('[INFO] Assigning order:', { orderId, userId });

            fetch("{{ route('proxy.order', ['order_id' => ':order_id']) }}".replace(':order_id', orderId), {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
                return response.json();
            })
            .then(order => {
                const currentStatus = order.status;
                console.log('[DEBUG] Order status checked:', { orderId, status: currentStatus });

                const requests = [];
                if (currentStatus === 'Pending') {
                    const statusFormData = new FormData();
                    statusFormData.append('order_id', orderId);
                    statusFormData.append('status', 'In_progress');
                    statusFormData.append('_token', '{{ csrf_token() }}');
                    requests.push(
                        fetch("{{ route('barkeeper.updateOrderStatus') }}", {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: statusFormData
                        })
                    );
                }

                requests.push(
                    fetch("{{ route('barkeeper.assignOrder') }}", {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData
                    })
                );

                return Promise.all(requests.map(req => req.then(res => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    return res.json();
                })));
            })
            .then(responses => {
                const assignResponse = responses[responses.length - 1];
                if (assignResponse.success) {
                    console.log('[INFO] Order assigned:', orderId);
                    showNotification(assignResponse.message, 'success');

                    const orderContainer = form.closest('[data-order-id]');
                    const itemList = orderContainer.querySelector('ul.text-gray-600');
                    const items = itemList ? Array.from(itemList.querySelectorAll('li')).map(li => {
                        const text = li.textContent.trim();
                        const nameQuantity = text.includes('x') ? text.split('x') : [text.trim(), '1'];
                        const name = li.dataset.itemName || nameQuantity[0].trim();
                        const quantity = parseInt(nameQuantity[1]) || 1;
                        const itemId = parseInt(Object.keys(menuItems).find(key => menuItems[key].name === name)) || 0;
                        const orderItemId = parseInt(li.dataset.itemId) || 0;

                        console.log('[DEBUG] Parsed item:', { name, quantity, itemId, orderItemId });

                        if (!itemId && !orderItemId) {
                            console.warn('[WARN] Skipping item with invalid IDs:', { name, quantity });
                            return null;
                        }

                        return {
                            item_id: itemId,
                            quantity: quantity,
                            order_item_id: orderItemId,
                            status: 'In_progress',
                            name: name
                        };
                    }).filter(item => item !== null) : [];

                    console.log('[DEBUG] Items for moveToInProgress:', items);

                    if (items.length === 0) {
                        console.error('[ERROR] No valid items for order:', orderId);
                        showNotification('Ошибка: нет валидных элементов в заказе', 'error');
                        return;
                    }

                    moveToInProgress({
                        order_id: orderId,
                        table_number: orderContainer.dataset.tableNumber || '',
                        items: items,
                        comment: orderContainer.querySelector('p.text-gray-600')?.textContent?.replace('Комментарий:', '')?.trim() || ''
                    });
                } else {
                    console.error('[ERROR] Failed to assign order:', assignResponse.message);
                    showNotification(assignResponse.message || 'Ошибка привязки заказа', 'error');
                }
            })
            .catch(error => {
                console.error('[ERROR] AJAX error:', error);
                showNotification('Ошибка связи с сервером: ' + error.message, 'error');
            });
        }

        function handleUpdateItemStatus(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const orderItemId = parseInt(formData.get('order_item_id'));
            const status = formData.get('status');
            const orderId = parseInt(form.closest('[data-order-id]').dataset.orderId);

            console.log('[INFO] Updating item status:', { orderItemId, status, orderId });

            if (!orderItemId || orderItemId <= 0) {
                console.error('[ERROR] Invalid order_item_id:', orderItemId);
                showNotification('Недопустимый ID элемента заказа', 'error');
                return;
            }

            if (!orderId) {
                console.error('[ERROR] Invalid order_id:', orderId);
                showNotification('Недопустимый ID заказа', 'error');
                return;
            }

            fetch("{{ route('barkeeper.updateOrderItemStatus') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('[INFO] Item status updated:', { orderItemId, status });
                    showNotification(data.message, 'success');
                    updateOrderItemStatus(orderId, orderItemId, status);
                } else {
                    console.error('[ERROR] Failed to update item status:', data.message);
                    showNotification(data.message || 'Ошибка обновления статуса элемента', 'error');
                }
            })
            .catch(error => {
                console.error('[ERROR] AJAX error:', error);
                showNotification('Ошибка связи с сервером: ' + error.message, 'error');
            });
        }

        function showNotification(message, type) {
            console.log(`[${type.toUpperCase()}] Notification: ${message}`);
            const notification = document.createElement('div');
            notification.className = `notification ${type === 'success' ? 'bg-green-500' : 'bg-red-700'}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }

        function initializeTabs() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            if (!tabButtons.length) {
                console.error('[ERROR] No tab buttons found');
                return;
            }
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    console.log('[INFO] Switching to tab:', button.dataset.tab);
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active', 'border-[#A52A2A]');
                        btn.classList.add('border-transparent');
                    });
                    button.classList.add('active', 'border-[#A52A2A]');
                    button.classList.remove('border-transparent');
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                    });
                    const targetTab = document.getElementById(button.dataset.tab + '-tab');
                    if (targetTab) {
                        targetTab.classList.remove('hidden');
                    }
                });
            });

            // Установить вкладку "В ожидании" по умолчанию
            const pendingButton = document.querySelector('[data-tab="pending"]');
            if (pendingButton) {
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-[#A52A2A]');
                    btn.classList.add('border-transparent');
                });
                pendingButton.classList.add('active', 'border-[#A52A2A]');
                pendingButton.classList.remove('border-transparent');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                document.getElementById('pending-tab').classList.remove('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            console.log('[INFO] DOM loaded, initializing at {{ now() }}');

            const successMessage = document.getElementById('session-message');
            const errorMessage = document.getElementById('error-message');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    setTimeout(() => successMessage.remove(), 500);
                }, 5000);
            }
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    setTimeout(() => errorMessage.remove(), 500);
                }, 5000);
            }

            initializeWebSocket();
            initializeTabs();
            checkOrderCompletionOnLoad();

            document.addEventListener('submit', (e) => {
                if (e.target.classList.contains('assign-order-form')) {
                    console.log('[DEBUG] Form submit captured for order assignment');
                    e.stopImmediatePropagation();
                    handleOrderAssignment(e);
                } else if (e.target.classList.contains('update-item-status-form')) {
                    console.log('[DEBUG] Form submit captured for item status');
                    e.stopImmediatePropagation();
                    handleUpdateItemStatus(e);
                }
            });
        });
    </script>
@endsection