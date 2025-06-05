@extends('layouts.minimal')

@section('title', 'Смена пароля')

@section('content')
    <div class="container mx-auto py-12">
        <h1 class="text-4xl font-bold mb-6 text-[#6B4F4F] text-center">Смена пароля</h1>

        @if (session('success'))
            <div class="notification bg-green-500 mb-4">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="notification bg-red-500 mb-4">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow-md max-w-md mx-auto border border-[#D4A373]">
            <form method="POST" action="{{ route('profile.update-password') }}">
                @csrf
                <div class="mb-4">
                    <label for="old_password" class="block text-[#6B4F4F] font-semibold mb-2">Старый пароль</label>
                    <input type="password" name="old_password" id="old_password" class="w-full p-2 border border-[#D4A373] rounded focus:outline-none focus:ring-2 focus:ring-[#A52A2A]" required>
                    @error('old_password')
                        <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="new_password" class="block text-[#6B4F4F] font-semibold mb-2">Новый пароль</label>
                    <input type="password" name="new_password" id="new_password" class="w-full p-2 border border-[#D4A373] rounded focus:outline-none focus:ring-2 focus:ring-[#A52A2A]" required>
                    @error('new_password')
                        <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-6">
                    <label for="new_password_confirmation" class="block text-[#6B4F4F] font-semibold mb-2">Подтвердите новый пароль</label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="w-full p-2 border border-[#D4A373] rounded focus:outline-none focus:ring-2 focus:ring-[#A52A2A]" required>
                    @error('new_password_confirmation')
                        <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-between">
                    <button type="submit" class="btn w-full sm:w-auto text-center">Сохранить</button>
                    <a href="{{ route('profile.show') }}" class="btn bg-gray-500 hover:bg-gray-600 w-full sm:w-auto text-center">Назад</a>
                </div>
            </form>
        </div>
    </div>
@endsection