<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        
        // Ensure there are no trailing spaces or typos here
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';
    }

    /**
     * Sends a text prompt to the Gemini API and returns the string response.
     */
    public function generateText(string $prompt): ?string
    {
        // 1. Guard clause: Ensure the API key exists
        if (!$this->apiKey) {
            Log::error('Gemini API Integration: Missing API Key in configuration.');
            return null;
        }

        try {
            // 2. Make an HTTP POST request with a 60-second timeout
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    // =========================================================
                    // ADDED: Force Gemini API into Native JSON Mode
                    // =========================================================
                    'generationConfig' => [
                        'responseMimeType' => 'application/json'
                    ]
                    // =========================================================
                ]);

            // 3. Check if the external server responded with a 200 OK status
            if ($response->successful()) {
                $responseData = $response->json();
                return $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            // TEMPORARY DEBUG LINE: This will dump Google's exact error response to Postman
            dd($response->status(), $response->json());

            // Log server-side errors
            Log::error('Gemini API Server Error: ' . $response->status() . ' - ' . $response->body());
            return null;

        } catch (\Exception $e) {
            // Catch networking infrastructure issues (e.g., no internet connection, DNS resolution failure)
            Log::error('Gemini API Connection Exception: ' . $e->getMessage());
            return null;
        }
    }
}