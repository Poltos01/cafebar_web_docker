@extends('layouts.minimal')

@section('title', 'Главная страница - Кафе "Золотой Дуб"')

@section('content')
    <div class="container mx-auto px-2 xs:px-4 sm:px-6 lg:px-8 py-6 sm:py-12 min-h-screen bg-[#F5F5DC]">
        @if (session('success'))
            <div class="notification fixed top-4 left-1/2 transform -translate-x-1/2 bg-[#6B4F4F] text-white px-3 xs:px-4 sm:px-6 py-2 xs:py-3 rounded-lg shadow-xl transition-opacity duration-500 z-50 text-xs xs:text-sm sm:text-base font-medium">
                {{ session('success') }}
            </div>
        @endif
        <h1 class="text-xl xs:text-2xl sm:text-3xl lg:text-4xl font-bold mb-4 xs:mb-6 sm:mb-8 text-[#6B4F4F] text-center leading-tight">Добро пожаловать в Кафе "Золотой Дуб"</h1>
        <div class="relative mb-6 xs:mb-8 sm:mb-10">
            <img src="https://i.pinimg.com/originals/0a/cb/ea/0acbea7a66f3df4fa38071437de75b32.jpg" 
                 alt="{{ $establishment['name'] }}" 
                 class="w-full h-40 xs:h-48 sm:h-56 lg:h-80 object-cover rounded-lg shadow-lg transition-transform duration-300 hover:scale-[1.02] max-w-full" 
                 onerror="this.src='https://via.placeholder.com/1200x400'; this.onerror=null;">
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <p class="text-white text-sm xs:text-base sm:text-lg lg:text-2xl font-semibold text-center px-3 xs:px-4 sm:px-6">Вкус, который вдохновляет</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 xs:gap-6 sm:gap-8 lg:gap-10 mb-8 xs:mb-10 sm:mb-12">
            <div>
                <h2 class="text-base xs:text-lg sm:text-xl font-semibold mb-3 xs:mb-4 sm:mb-5 text-[#6B4F4F]">О нас</h2>
                <p class="text-gray-700 text-xs xs:text-sm sm:text-base leading-relaxed mb-3 xs:mb-4">{{ $establishment['description'] }}</p>
                <p class="text-gray-700 text-xs xs:text-sm sm:text-base leading-relaxed">Мы открылись в 2015 году с миссией создать место, где гастрономия и музыка сливаются в гармонии. Наш шеф-повар, с 20-летним опытом, вдохновляется сезонными продуктами, а наша команда барменов создаёт уникальные коктейли, которые станут изюминкой вашего вечера.</p>
            </div>
            <div>
                <h2 class="text-base xs:text-lg sm:text-xl font-semibold mb-3 xs:mb-4 sm:mb-5 text-[#6B4F4F]">Контакты</h2>
                <p class="text-xs xs:text-sm sm:text-base mb-1 xs:mb-2 sm:mb-3"><strong class="text-[#6B4F4F]">Адрес:</strong> {{ $establishment['address'] }}</p>
                <p class="text-xs xs:text-sm sm:text-base mb-1 xs:mb-2 sm:mb-3"><strong class="text-[#6B4F4F]">Телефон:</strong> <a href="tel:{{ str_replace(' ', '', $establishment['phone']) }}" class="text-[#A52A2A] hover:underline focus:outline-none focus:ring-2 focus:ring-[#A52A2A] rounded">{{ $establishment['phone'] }}</a></p>
                <p class="text-xs xs:text-sm sm:text-base mb-1 xs:mb-2 sm:mb-3"><strong class="text-[#6B4F4F]">Режим работы:</strong> {{ $establishment['hours'] }}</p>
                <p class="text-xs xs:text-sm sm:text-base mb-1 xs:mb-2 sm:mb-3"><strong class="text-[#6B4F4F]">Спецпредложение:</strong> {{ $establishment['specials'] }}</p>
            </div>
        </div>
        <div class="mb-8 xs:mb-10 sm:mb-12">
            <h2 class="text-base xs:text-lg sm:text-xl font-semibold mb-3 xs:mb-4 sm:mb-5 text-[#6B4F4F] text-center">Галерея</h2>
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 gap-3 xs:gap-4 sm:gap-6">
                @foreach ([
                    'https://txtooo.ru/upload/iblock/c76/c76d63e16dbba05facdab61a4a91b3de.jpg',
                    'https://avatars.mds.yandex.net/i?id=32a6cf68ba5628da8080b3878af971c7_l-5243656-images-thumbs&n=13',
                    'https://i.pinimg.com/originals/61/21/ff/6121ffc1bb3c42c456139f5c032686ef.jpg'
                ] as $image)
                    <a href="{{ $image }}" target="_blank" class="block">
                        <img src="{{ $image }}" alt="Галерея кафе" 
                             class="w-full h-32 xs:h-36 sm:h-40 lg:h-48 object-cover rounded-lg shadow-md hover:opacity-90 transition-opacity duration-300 max-w-full" 
                             onerror="this.src='https://via.placeholder.com/300x200'; this.onerror=null;">
                    </a>
                @endforeach
            </div>
        </div>
        <div class="mb-8 xs:mb-10 sm:mb-12">
            <h2 class="text-base xs:text-lg sm:text-xl font-semibold mb-3 xs:mb-4 sm:mb-5 text-[#6B4F4F] text-center">Работа с нами</h2>
            <p class="text-gray-700 text-xs xs:text-sm sm:text-base text-center mb-4 xs:mb-6 sm:mb-8 leading-relaxed">Присоединяйтесь к нашей дружной команде! Мы ищем талантливых людей, готовых расти и развиваться.</p>
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 gap-3 xs:gap-4 sm:gap-6">
                @foreach ($establishment['jobs'] as $role => $description)
                    <div class="bg-white p-3 xs:p-4 sm:p-5 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-[#D4A373]">
                        <h3 class="text-sm xs:text-base sm:text-lg font-semibold text-[#6B4F4F] mb-1 xs:mb-2">{{ $role }}</h3>
                        <p class="text-gray-600 text-xs xs:text-sm sm:text-base leading-relaxed">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-4 xs:mt-6 sm:mt-8">
                <a href="{{ route('resume.create') }}" 
                   style="background-color: #6B4F4F !important; color: white !important; border: 2px solid #D4A373 !important;"
                   class="inline-block px-6 xs:px-8 sm:px-10 py-2 xs:py-3 sm:py-4 rounded-lg shadow-xl hover:bg-[#543737] focus:outline-none focus:ring-2 focus:ring-[#D4A373] active:bg-[#4A2F2F] transition-all duration-300 text-sm xs:text-base sm:text-lg font-semibold w-full xs:w-auto">
                    Отправить резюме
                </a>
            </div>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const successMessage = document.querySelector('.notification');
            if (successMessage) {
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 500);
            }
        }, 5000);
    </script>
@endsection