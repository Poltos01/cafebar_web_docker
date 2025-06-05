<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResumeController extends Controller
{
    public function create()
    {
        return view('resume.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'info' => 'required|string',
            'phone' => ['required', 'string', 'regex:/^(\+7|8|7)\d{10}$/'],
        ]);

        $apiData = [
            'content' => "Имя: {$data['name']}. {$data['info']}",
            'phone_number' => $data['phone'],
        ];

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->post('https://cafebar-oaba.onrender.com/resumes', $apiData);

            if ($response->successful()) {
                Log::info('Резюме отправлено на API:', ['data' => $apiData, 'response' => $response->json()]);
                return redirect()->route('home')->with('success', 'Резюме успешно отправлено!');
            } else {
                Log::error('Ошибка при отправке резюме на API:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return redirect()->back()->with('error', 'Ошибка при отправке резюме. Попробуйте снова.');
            }
        } catch (\Exception $e) {
            Log::error('Исключение при отправке резюме: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Произошла ошибка. Попробуйте снова.');
        }
    }
}