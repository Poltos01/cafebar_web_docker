@extends('layouts.minimal')

@section('title', 'Профиль')

@section('content')
    <div class="container mx-auto py-6 sm:py-12">
        <h1 class="text-3xl sm:text-4xl font-bold mb-4 sm:mb-6 text-[#6B4F4F] text-center">Профиль</h1>

        @if (session('success'))
            <div class="notification mb-4">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="notification bg-red-500 mb-4">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md w-full max-w-md mx-2 sm:mx-auto border border-[#D4A373]">
            <p class="mb-2 text-sm sm:text-base"><strong class="text-[#6B4F4F]">Имя:</strong> {{ $userData['name'] }}</p>
            <p class="mb-2 text-sm sm:text-base"><strong class="text-[#6B4F4F]">Email:</strong> {{ $userData['email'] }}</p>
            <p class="mb-4 text-sm sm:text-base"><strong class="text-[#6B4F4F]">Роль:</strong> {{ $userData['role'] }}</p>

            <h2 class="text-xl sm:text-2xl font-semibold mt-4 sm:mt-6 mb-3 sm:mb-4 text-[#6B4F4F]">Моя статистика</h2>
            <p class="mb-2 text-sm sm:text-base"><strong class="text-[#6B4F4F]">Мои выполненные заказы за всё время:</strong> {{ $userData['stats']['total_orders'] }}</p>
            <p class="mb-4 text-sm sm:text-base"><strong class="text-[#6B4F4F]">Рейтинг:</strong> Вы находитесь на {{ $userData['stats']['rating'] }} месте среди {{ $userData['stats']['total_employees'] }} {{ $userData['role_plural'] }}</p>

            <div class="flex flex-col sm:flex-row justify-between mt-4 sm:mt-6 space-y-3 sm:space-y-0 sm:space-x-3">
                <a href="{{ route('profile.edit-password') }}" class="btn w-full sm:w-auto text-center">Изменить пароль</a>
                <form method="POST" action="{{ route('profile.logout') }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="btn bg-red-500 hover:bg-red-600 w-full text-center">Выйти</button>
                </form>
            </div>
        </div>
    </div>
@endsection