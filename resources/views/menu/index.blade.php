@extends('layouts.minimal')

@section('title', 'Меню')

@section('content')
    <div class="container mx-auto py-6 sm:py-12">
        @if (session('success'))
            <div class="notification mb-4">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="notification bg-red-500 mb-4">{{ session('error') }}</div>
        @endif

        <h1 class="text-3xl sm:text-4xl font-bold mb-4 sm:mb-6 text-[#6B4F4F] text-center">Меню</h1>

        @if (empty($groupedMenu))
            <p class="text-red-500 text-center text-base sm:text-lg">Меню недоступно.</p>
        @else
            @foreach ($groupedMenu as $category => $items)
                <div class="mb-6 sm:mb-8">
                    <h2 class="text-xl sm:text-2xl font-semibold mb-3 sm:mb-4 text-[#6B4F4F]">{{ $category }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        @foreach ($items as $item)
                            <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer" onclick="showIngredients({{ $item['item_id'] }}, '{{ $item['name'] }}', {{ json_encode(array_column($item['images'] ?? [], 'image_url')) }}, {{ $item['price'] }})">
                                <img src="{{ !empty($item['images']) && isset($item['images'][0]['image_url']) ? $item['images'][0]['image_url'] : 'https://via.placeholder.com/300x200' }}" alt="{{ $item['name'] }}" class="w-full h-40 sm:h-48 object-cover rounded-lg mb-2 sm:mb-3">
                                <h3 class="text-base sm:text-lg font-semibold text-[#6B4F4F]">{{ $item['name'] }}</h3>
                                <p class="text-gray-600 text-sm sm:text-base">{{ $item['description'] }}</p>
                                <p class="text-[#A52A2A] font-semibold mt-1 sm:mt-2 text-sm sm:text-base">{{ $item['price'] }} руб.</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        <!-- Модальное окно для ингредиентов -->
        <div id="ingredients-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg w-full max-w-lg sm:max-w-2xl mx-2 sm:mx-0">
                <h3 class="text-lg sm:text-xl font-semibold text-[#6B4F4F] mb-3 sm:mb-4">Информация о блюде</h3>
                <div id="modal-content" class="text-gray-700">
                    <h4 id="modal-item-name" class="text-xl sm:text-2xl font-bold text-[#6B4F4F] mb-2"></h4>
                    <div id="modal-image-gallery" class="mb-3 sm:mb-4">
                        <img id="modal-image" src="https://via.placeholder.com/300x200" alt="Блюдо" class="w-full h-48 sm:h-64 object-cover rounded-lg">
                        <div id="image-nav" class="flex justify-between mt-2 text-[#6B4F4F] hidden">
                            <button id="prev-image" class="btn text-sm sm:text-base">Предыдущая</button>
                            <span id="image-count" class="text-sm sm:text-base"></span>
                            <button id="next-image" class="btn text-sm sm:text-base">Следующая</button>
                        </div>
                    </div>
                    <p id="modal-item-price" class="text-[#A52A2A] font-semibold mb-3 sm:mb-4 text-sm sm:text-base"></p>
                    <div id="ingredients-content" class="text-sm sm:text-base"></div>
                </div>
                <button onclick="closeIngredientsModal()" class="btn mt-3 sm:mt-4 w-full sm:w-auto">Закрыть</button>
            </div>
        </div>
    </div>

    <script>
        let currentImageIndex = 0;
        let images = [];

        function showIngredients(itemId, name, imageUrls, price) {
            currentImageIndex = 0;
            images = Array.isArray(imageUrls) ? imageUrls : [imageUrls];
            document.getElementById('modal-item-name').textContent = name;
            updateImage();
            document.getElementById('modal-item-price').textContent = `${price} руб.`;
            document.getElementById('ingredients-content').innerHTML = '';

            fetch(`/menu/ingredients/${itemId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                const content = document.getElementById('ingredients-content');
                if (data.message || data.length === 0) {
                    content.innerHTML = '<p class="text-sm sm:text-base">Ингредиенты отсутствуют</p>';
                } else {
                    let ingredientsList = '<p class="text-sm sm:text-base">Состав:</p><ul>';
                    data.forEach(item => {
                        const quantity = parseFloat(item.required_quantity).toFixed(2).replace('.', ',');
                        ingredientsList += `<li class="text-sm sm:text-base">• ${item.ingredient.name} ${quantity} ${item.ingredient.unit}</li>`;
                    });
                    ingredientsList += '</ul>';
                    content.innerHTML = ingredientsList;
                }
                document.getElementById('ingredients-modal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Ошибка при получении ингредиентов:', error);
                document.getElementById('ingredients-content').innerHTML = '<p class="text-sm sm:text-base">Ингредиенты отсутствуют</p>';
                document.getElementById('ingredients-modal').classList.remove('hidden');
            });
        }

        function updateImage() {
            const imageElement = document.getElementById('modal-image');
            const imageNav = document.getElementById('image-nav');
            const imageCount = document.getElementById('image-count');

            if (images.length > 0) {
                imageElement.src = images[currentImageIndex] || 'https://via.placeholder.com/300x200';
                imageCount.textContent = `${currentImageIndex + 1} из ${images.length}`;
                imageNav.classList.remove('hidden');
            } else {
                imageElement.src = 'https://via.placeholder.com/300x200';
                imageNav.classList.add('hidden');
            }

            document.getElementById('prev-image').disabled = currentImageIndex === 0;
            document.getElementById('next-image').disabled = currentImageIndex === images.length - 1;
        }

        document.getElementById('prev-image').addEventListener('click', () => {
            if (currentImageIndex > 0) {
                currentImageIndex--;
                updateImage();
            }
        });

        document.getElementById('next-image').addEventListener('click', () => {
            if (currentImageIndex < images.length - 1) {
                currentImageIndex++;
                updateImage();
            }
        });

        function closeIngredientsModal() {
            document.getElementById('ingredients-modal').classList.add('hidden');
        }

        setTimeout(() => {
            const notification = document.querySelector('.notification');
            if (notification) {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }
        }, 5000);
    </script>
@endsection