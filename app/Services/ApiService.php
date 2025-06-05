<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiService
{
    protected $baseUrl = 'https://cafebar-oaba.onrender.com';

    public function get($endpoint, $token = null)
    {
        $url = "{$this->baseUrl}/{$endpoint}";
        Log::info("GET запрос к: {$url}", ['token' => $token ? 'Present' : 'Absent']);

        $response = Http::withHeaders([
            'Authorization' => $token ? "Bearer {$token}" : null,
        ])
            ->timeout(10)
            ->connectTimeout(5)
            ->get($url);

        Log::info("Ответ от сервера: {$response->status()} - {$response->body()}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Ошибка запроса: ' . $response->status() . ' - ' . $response->body());
    }

    public function post($endpoint, $data, $token = null)
    {
        $url = "{$this->baseUrl}/{$endpoint}";
        Log::info("POST запрос к: {$url}", ['data' => $data, 'token' => $token ? 'Present' : 'Absent']);

        $response = Http::withHeaders([
            'Authorization' => $token ? "Bearer {$token}" : null,
        ])
            ->timeout(10)
            ->connectTimeout(5)
            ->post($url, $data);

        Log::info("Ответ от сервера: {$response->status()} - {$response->body()}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Ошибка запроса: ' . $response->status() . ' - ' . $response->body());
    }

    public function patch($endpoint, $data, $token = null)
    {
        $url = "{$this->baseUrl}/{$endpoint}";
        Log::info("PATCH запрос к: {$url}", ['data' => $data, 'token' => $token ? 'Present' : 'Absent']);

        $response = Http::withHeaders([
            'Authorization' => $token ? "Bearer {$token}" : null,
        ])
            ->timeout(10)
            ->connectTimeout(5)
            ->patch($url, $data);

        Log::info("Ответ от сервера: {$response->status()} - {$response->body()}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Ошибка запроса: ' . $response->status() . ' - ' . $response->body());
    }
}