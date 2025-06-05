@extends('layouts.minimal')

@section('title', 'Отправить резюме - Кафе "Золотой Дуб"')

@section('content')
    <div class="container mx-auto py-12">
        @if (session('success'))
            <div class="notification mb-4 bg-green-500">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="notification bg-red-500 mb-4">{{ session('error') }}</div>
        @endif

        <h1 class="text-4xl font-bold mb-6 text-[#6B4F4F] text-center">Отправить резюме</h1>

        <div class="bg-white p-6 rounded-lg shadow-md max-w-md mx-auto border border-[#D4A373]">
            <form id="resume-form" method="POST" action="{{ route('resume.store') }}">
                @csrf
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-[#6B4F4F]">Имя:</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full p-2 border border-[#D4A373] rounded" required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="info" class="block text-sm font-medium text-[#6B4F4F]">Информация о Вас:</label>
                    <textarea name="info" id="info" class="mt-1 block w-full p-2 border border-[#D4A373] rounded" required>{{ old('info') }}</textarea>
                    @error('info')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium text-[#6B4F4F]">Телефон:</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="mt-1 block w-full p-2 border border-[#D4A373] rounded" required placeholder="+79123456789">
                    <p id="phone-error" class="text-red-500 text-sm mt-1 hidden">Введите телефон в формате +7XXXXXXXXXX, 8XXXXXXXXXX или 7XXXXXXXXXX</p>
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-lg">Отправить резюме</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('resume-form').addEventListener('submit', function (event) {
            const phone = document.getElementById('phone').value;
            const phoneError = document.getElementById('phone-error');
            const phoneRegex = /^(\+7|8|7)\d{10}$/;

            if (!phoneRegex.test(phone)) {
                event.preventDefault();
                phoneError.classList.remove('hidden');
                return;
            } else {
                phoneError.classList.add('hidden');
            }
        });

        setTimeout(() => {
            const notification = document.querySelector('.notification');
            if (notification) {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }
        }, 5000);
    </script>

    <style>
        .notification {
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            padding: 1rem;
            color: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 9999; /* Увеличен z-index */
            animation: fadeOut 5s forwards;
            border: 1px solid #D4A373;
            min-width: 200px;
            text-align: center;
            opacity: 1; /* Явная установка начальной непрозрачности */
            background-color: #10B981; /* Зелёный для успеха */
        }
        .notification.bg-red-500 {
            background-color: #EF4444;
        }
        @keyframes fadeOut {
            to { opacity: 0; }
        }
    </style>
@endsection