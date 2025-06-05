<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

class MenuController extends Controller
{
    public function index()
    {
        if (!Session::has('access_token')) {
            \Log::warning('Попытка доступа к меню без авторизации', [
                'ip' => request()->ip(),
                'session' => Session::all(),
            ]);
            return redirect()->route('login')->with('error', 'Пожалуйста, авторизуйтесь.');
        }

        try {
            $response = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/menu/');

            if ($response->successful()) {
                $menu = $response->json();
                \Log::info('Меню успешно получено', ['count' => count($menu)]);
            } else {
                \Log::error('Ошибка при получении меню', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $menu = [];
            }
        } catch (\Exception $e) {
            \Log::error('Ошибка при запросе меню: ' . $e->getMessage());
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

        return view('menu.index', compact('groupedMenu'));
    }

    public function getIngredients($itemId)
    {
        if (!Session::has('access_token')) {
            return response()->json(['error' => 'Не авторизован'], 401);
        }

        try {
            $ingredientsResponse = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->get("https://cafebar-oaba.onrender.com/menu-item-ingredients/{$itemId}");

            if ($ingredientsResponse->successful()) {
                $ingredients = $ingredientsResponse->json();
                \Log::info('Ингредиенты успешно получены', ['item_id' => $itemId, 'ingredients' => $ingredients]);
                return response()->json($ingredients);
            } else {
                \Log::error('Ошибка при получении ингредиентов', [
                    'status' => $ingredientsResponse->status(),
                    'body' => $ingredientsResponse->body(),
                ]);
                return response()->json(['message' => 'Ингредиенты отсутствуют'], 404);
            }
        } catch (\Exception $e) {
            \Log::error('Ошибка при запросе ингредиентов: ' . $e->getMessage());
            return response()->json(['message' => 'Ингредиенты отсутствуют'], 500);
        }
    }
}