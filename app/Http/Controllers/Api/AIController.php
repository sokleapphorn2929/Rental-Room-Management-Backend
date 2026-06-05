<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use App\Models\AiResponse; // Import your new Eloquent Model
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function processPrompt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_input' => 'required|string|max:2000',
        ]);

        $systemContext = "You are an intelligent data extraction assistant for a Room Rental Management System backend. " .
                         "Your task is to analyze raw text provided by an administrator or landlord and extract relevant information " .
                         "matching our relational database schema. " .
                         "You must output a strict JSON object containing exactly these key blocks and sub-keys, substituting null or 0 if a detail isn't mentioned:\n\n" .
                         
                         "{\n" .
                         "  'building_data': {\n" .
                         "    'building_name': (string or null),\n" .
                         "    'address': (string or null),\n" .
                         "    'total_floors': (integer or null)\n" .
                         "  },\n" .
                         "  'room_data': {\n" .
                         "    'room_number': (string or null),\n" .
                         "    'room_type': (string: must be exactly 'single', 'double', or 'studio'),\n" .
                         "    'floor_number': (integer or null),\n" .
                         "    'monthly_price': (numeric float value or 0.00),\n" .
                         "    'area_sqm': (numeric float value or null),\n" .
                         "    'description': (string summary of features or null)\n" .
                         "  },\n" .
                         "  'tenant_data': {\n" .
                         "    'full_name': (string or null),\n" .
                         "    'phone': (string or null),\n" .
                         "    'email': (string or null),\n" .
                         "    'national_id': (string or null),\n" .
                         "    'gender': (string: must be exactly 'male' or 'female' or 'other')\n" .
                         "  },\n" .
                         "  'contract_data': {\n" .
                         "    'start_date': (date string format YYYY-MM-DD or null),\n" .
                         "    'end_date': (date string format YYYY-MM-DD or null),\n" .
                         "    'deposit_amount': (numeric float value or 0.00),\n" .
                         "    'notes': (string text or null)\n" .
                         "  }\n" .
                         "}\n\n" .
                         
                         "Rules:\n" .
                         "- Do not make up information. Use null if it is missing.\n" .
                         "- Output raw JSON only. Do not format with markdown blocks.\n\n" .
                         "Input Text to Analyze:\n";

        $finalPrompt = $systemContext . $validated['user_input'];

        $aiResponse = $this->geminiService->generateText($finalPrompt);

        if (is_null($aiResponse)) {
            return response()->json([
                'success' => false,
                'message' => 'The AI processing engine is currently unavailable.'
            ], 503);
        }

        // =====================================================================
        // BULLETPROOF PARSING LAYER
        // =====================================================================
        $cleanedResponse = $aiResponse;

        // 1. Strip markdown fences if Gemini added them anyway
        $cleanedResponse = str_replace(['```json', '```JSON', '```'], '', $cleanedResponse);

        // 2. Clear invisible spaces/UTF-8 BOM tags that break strict parsers
        $cleanedResponse = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        // 3. PARSE: Decodes the string into a true PHP associative array
        $cleanedData = json_decode($cleanedResponse, true);

        // 4. CHECK: Validate if it's an actual, clean array structure
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($cleanedData)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an invalid JSON schema.',
                'debug' => $aiResponse,
                'json_error' => json_last_error_msg()
            ], 422);
        }
        // =====================================================================

        // Save the clean PHP array to MySQL. Eloquent converts it cleanly.
        $savedRecord = AiResponse::create([
            'prompt' => $validated['user_input'],
            'ai_payload' => $cleanedData, 
        ]);

        // Return the clean array directly from the model
        return response()->json([
            'success' => true,
            'db_id' => $savedRecord->id,
            'data' => $savedRecord->ai_payload 
        ], 200);
    }
}