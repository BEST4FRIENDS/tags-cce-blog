<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model', 'gemini-2.0-flash');
    }

    /**
     * Sendet einen Prompt an Gemini und gibt den Text zurück.
     */
    public function generateText(string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API Key fehlt.');
            return null;
        }

        $url = "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 8192,
                ]
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Fehler: ' . $response->body());
                return null;
            }

            $data = $response->json();
            
            // Extrahiere den Text aus der tief verschachtelten Google-Antwort
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        } catch (\Exception $e) {
            Log::error('Gemini Verbindung fehlgeschlagen: ' . $e->getMessage());
            return null;
        }
    }
}