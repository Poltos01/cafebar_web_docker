<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $establishment = [
            'name' => 'Кафе "Золотой Дуб"',
            'description' => 'Публичное кафе с уникальной атмосферой.',
            'address' => 'г. Москва, ул. Примерная, д. 1',
            'phone' => '+7 (999) 123-45-67',
            'hours' => 'Пн-Вс: 10:00-22:00',
            'specials' => 'Скидка 10% на первые блюда!',
            'image' => 'https://images.unsplash.com/photo-1519669556878-63bdad8a1a49?q=80&w=2071&auto=format&fit=crop&ixlib=rb-4.0.3',
            'gallery' => [
                'https://images.unsplash.com/photo-1627308594174-3f3f2d5a7b18?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3',
                'https://images.unsplash.com/photo-1514329772176-aca0e3bb5109?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3',
                'https://images.unsplash.com/photo-1555244162-803834f70033?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3',
            ],
            'jobs' => [
                'Официант' => 'Ищем энергичных людей для работы в зале.',
                'Повар' => 'Требуется повар с опытом.',
                'Бармен' => 'Ищем креативного бармена.',
            ],
        ];

        return response()->view('home.index', compact('establishment'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}