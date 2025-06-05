@extends('layouts.minimal')

@section('title', 'Вход')

@section('content')
    <div class="container mx-auto py-12 px-4 sm:px-6 flex justify-center">
        <div class="w-full max-w-md">
            @if ($errors->any())
                <div class="notification bg-red-500 mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="notification bg-red-500 mb-4">{{ session('error') }}</div>
            @endif

            @if (session('success'))
                <div class="notification mb-4">{{ session('success') }}</div>
            @endif

            <h1 class="text-4xl font-bold mb-6 text-[#6B4F4F] text-center">Вход для сотрудников</h1>

            <div class="bg-white p-6 rounded-lg shadow-md border border-[#D4A373]">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-[#6B4F4F]">Email:</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="mt-1 block w-full p-2 border border-[#D4A373] rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-[#6B4F4F]">Пароль:</label>
                        <input type="password" name="password" id="password" class="mt-1 block w-full p-2 border border-[#D4A373] rounded" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-lg">Войти</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection