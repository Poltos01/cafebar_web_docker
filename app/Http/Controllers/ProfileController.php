<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function show()
    {
        if (!Session::has('access_token')) {
            \Log::warning('Попытка доступа к профилю без токена', ['ip' => request()->ip()]);
            return redirect()->route('login')->with('error', 'Пожалуйста, авторизуйтесь.');
        }

        $user = $this->fetchUserData();
        if (!$user) {
            \Log::error('Не удалось получить данные пользователя');
            return redirect()->route('login')->with('error', 'Не удалось получить данные пользователя.');
        }

        // Проверка кэша для общей статистики
        $totalStats = Session::get('total_stats');
        if (!$totalStats) {
            $totalOrdersResponse = Http::withToken(Session::get('access_token'))
                ->timeout(15)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/statistics/');

            $totalOrders = 0;
            $rating = 0;
            $totalEmployees = 0;
            if ($totalOrdersResponse->successful()) {
                $totalOrdersData = $totalOrdersResponse->json();
                $totalOrders = $totalOrdersData[0]['orders_count'] ?? 0;
                $rating = $totalOrdersData[0]['rating'] ?? 0;
                $totalEmployees = $totalOrdersData[0]['total_employees'] ?? 0;
                $totalStats = [
                    'total_orders' => $totalOrders,
                    'rating' => $rating,
                    'total_employees' => $totalEmployees,
                ];
                Session::put('total_stats', $totalStats);
            } else {
                \Log::error('Ошибка при получении общей статистики заказов', [
                    'status' => $totalOrdersResponse->status(),
                    'body' => $totalOrdersResponse->body(),
                ]);
            }
        }

        // Получение смен пользователя
        $shifts = $this->fetchUserShifts($user['user_id']);
        
        $userData = [
            'name' => $user['username'] ?? 'Имя не указано',
            'email' => $user['email'] ?? 'email@notset.com',
            'role' => $this->translateRole($user['role'] ?? 'Неизвестно'),
            'role_plural' => $this->translateRolePlural($user['role'] ?? 'Неизвестно'),
            'stats' => [
                'total_orders' => $totalStats['total_orders'] ?? 0,
                'rating' => $totalStats['rating'] ?? 0,
                'total_employees' => $totalStats['total_employees'] ?? 0,
            ],
            'shifts' => $shifts,
        ];

        return view('profile.show', compact('userData'));
    }

    public function editPassword()
    {
        if (!Session::has('access_token')) {
            \Log::warning('Попытка доступа к смене пароля без токена', ['ip' => request()->ip()]);
            return redirect()->route('login')->with('error', 'Пожалуйста, авторизуйтесь.');
        }
        return view('profile.edit-password');
    }

    public function updatePassword(Request $request)
    {
        if (!Session::has('access_token')) {
            \Log::warning('Попытка обновления пароля без токена', ['ip' => request()->ip()]);
            return redirect()->route('login')->with('error', 'Пожалуйста, авторизуйтесь.');
        }

        $request->validate([
            'old_password' => 'required|string|min:1',
            'new_password' => 'required|string|min:1|confirmed',
        ], [
            'old_password.required' => 'Поле старый пароль обязательно для заполнения.',
            'old_password.string' => 'Старый пароль должен быть строкой.',
            'old_password.min' => 'Старый пароль должен содержать минимум :min символов.',
            'new_password.required' => 'Поле новый пароль обязательно для заполнения.',
            'new_password.string' => 'Новый пароль должен быть строкой.',
            'new_password.min' => 'Новый пароль должен содержать минимум :min символов.',
            'new_password.confirmed' => 'Подтверждение нового пароля не совпадает.',
        ]);

        try {
            $response = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->put('https://cafebar-oaba.onrender.com/users/me/password', [
                    'old_password' => $request->input('old_password'),
                    'new_password' => $request->input('new_password'),
                ]);

            if ($response->successful()) {
                \Log::info('Пароль успешно обновлён для пользователя', ['id' => Session::get('user_id')]);
                return redirect()->route('profile.show')->with('success', 'Пароль успешно сменён.');
            } else {
                $errorMessage = $response->json()['message'] ?? 'Не удалось сменить пароль. Проверьте правильность старого пароля.';
                \Log::error('Ошибка при смене пароля', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return redirect()->route('profile.edit-password')->with('error', $errorMessage);
            }
        } catch (\Exception $e) {
            \Log::error('Ошибка при запросе смены пароля: ' . $e->getMessage());
            return redirect()->route('profile.edit-password')->with('error', 'Произошла ошибка при смене пароля. Попробуйте позже.');
        }
    }

    public function logout()
    {
        \Log::info('Пользователь вышел из системы', ['user_id' => Session::get('user_id')]);
        Session::flush();
        return redirect()->route('login');
    }

    private function fetchUserData()
    {
        try {
            $response = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/users/me');

            if ($response->successful()) {
                $userData = $response->json();
                Session::put('user_name', $userData['username'] ?? 'Имя не указано');
                Session::put('user_email', $userData['email'] ?? 'email@notset.com');
                Session::put('user_role', $userData['role'] ?? 'Неизвестно');
                \Log::info('Данные пользователя успешно получены', ['user_id' => $userData['user_id']]);
                return $userData;
            } else {
                \Log::error('Ошибка при получении данных пользователя', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            \Log::error('Ошибка при запросе данных пользователя: ' . $e->getMessage());
            return null;
        }
    }

    private function fetchUserShifts($userId)
    {
        try {
            $response = Http::withToken(Session::get('access_token'))
                ->timeout(10)
                ->connectTimeout(5)
                ->get('https://cafebar-oaba.onrender.com/shifts');

            if ($response->successful()) {
                $shifts = $response->json();
                $currentDateTime = Carbon::now();

                // Фильтрация смен по user_id и актуальности
                $filteredShifts = array_filter($shifts, function ($shift) use ($currentDateTime, $userId) {
                    $shiftEndDateTime = Carbon::parse($shift['shift_date'] . ' ' . $shift['shift_end']);
                    return $shift['user_id'] == $userId && $shiftEndDateTime->isFuture();
                });

                // Сортировка по дате
                usort($filteredShifts, function ($a, $b) {
                    return strcmp($a['shift_date'], $b['shift_date']);
                });

                \Log::info('Смены пользователя успешно получены', ['user_id' => $userId, 'shifts_count' => count($filteredShifts)]);
                return $filteredShifts;
            } else {
                \Log::error('Ошибка при получении смен пользователя', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }
        } catch (\Exception $e) {
            \Log::error('Ошибка при запросе смен пользователя: ' . $e->getMessage());
            return [];
        }
    }

    private function translateRole($role)
    {
        return match (strtolower($role)) {
            'cook' => 'Повар',
            'waiter' => 'Официант',
            'barkeeper' => 'Бармен',
            default => 'Неизвестно',
        };
    }

    private function translateRolePlural($role)
    {
        return match (strtolower($role)) {
            'cook' => 'поваров',
            'waiter' => 'официантов',
            'barkeeper' => 'барменов',
            default => 'неизвестных',
        };
    }
}