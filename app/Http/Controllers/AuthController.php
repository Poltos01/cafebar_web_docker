<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // Шаг 1: Запрос токена через /auth/login
            $loginResponse = Http::timeout(15)
                ->connectTimeout(5)
                ->post('https://cafebar-oaba.onrender.com/auth/login', [
                    'email' => $request->email,
                    'password' => $request->password,
                ]);

            if (!$loginResponse->successful()) {
                \Log::warning('Неудачная попытка авторизации', [
                    'status' => $loginResponse->status(),
                    'body' => $loginResponse->body(),
                ]);
                return redirect()->back()->with('error', 'Неверные данные для входа.');
            }

            $loginData = $loginResponse->json();
            $accessToken = $loginData['access_token'] ?? null;

            if (!$accessToken) {
                \Log::error('Токен доступа отсутствует в ответе /auth/login', ['response' => $loginData]);
                return redirect()->back()->with('error', 'Ошибка авторизации: токен не получен.');
            }

            // Сохраняем токен в сессии
            Session::put('access_token', $accessToken);
            \Log::info('Токен сохранён в сессии', ['token' => $accessToken]);

            // Шаг 2: Запрос данных пользователя через /users/me
            $userResponse = Http::withToken($accessToken)
                ->timeout(15)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/users/me');

            if (!$userResponse->successful()) {
                \Log::error('Ошибка при получении данных пользователя через /users/me', [
                    'status' => $userResponse->status(),
                    'body' => $userResponse->body(),
                ]);
                Session::flush();
                return redirect()->back()->with('error', 'Не удалось получить данные пользователя.');
            }

            $userData = $userResponse->json();
            $role = $userData['role'] ?? null;

            // Проверка разрешённых ролей
            $allowedRoles = ['waiter', 'barkeeper', 'cook'];
            $normalizedRole = strtolower($role);
            if (!$role || !in_array($normalizedRole, $allowedRoles)) {
                \Log::warning('Попытка входа с недопустимой ролью', [
                    'email' => $request->email,
                    'role' => $role,
                ]);
                Session::flush();
                return redirect()->back()->with('error', 'Вход разрешён только для официантов, барменов и поваров.');
            }

            // Сохранение данных в сессии
            Session::put('user_id', $userData['user_id'] ?? 'unknown');
            Session::put('user_role', $normalizedRole); // Сохраняем роль в нижнем регистре
            Session::put('user_name', $userData['username'] ?? 'Имя не указано');
            Session::put('user_email', $userData['email'] ?? 'email@notset.com');
            \Log::info('Успешная авторизация', [
                'user_id' => $userData['user_id'] ?? 'не указан',
                'role' => $normalizedRole,
                'session_data' => Session::all(),
            ]);

            return redirect()->route('menu.index');
        } catch (\Exception $e) {
            \Log::error('Ошибка при авторизации: ' . $e->getMessage());
            Session::flush();
            return redirect()->back()->with('error', 'Произошла ошибка при входе.');
        }
    }

    public function logout()
    {
        \Log::info('Пользователь вышел из системы', ['user_id' => Session::get('user_id')]);
        Session::flush();
        return redirect()->route('login');
    }
}