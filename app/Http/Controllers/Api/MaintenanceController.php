<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Room;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    protected GeminiService $geminiService;

    // Inject GeminiService into the controller constructor
    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $maintenance = Maintenance::all();

        return response()->json([
            "message" => "Get all maintenance records successful!",
            "data" => $maintenance
        ],200);
    }

    public function storeFromAi(Request $request): JsonResponse
    {
        // 1. Validate incoming data against your singular 'room' table mapping
        $validated = $request->validate([
            'room_id'    => 'required|integer|exists:room,room_id', 
            'user_input' => 'required|string|max:2000',
        ]);

        // 2. Fetch the room for context
        $room = Room::findOrFail($validated['room_id']);

        // 3. Create a strict structure prompt matching your exact columns
        $systemContext = "You are a backend database helper tracking maintenance requests for Room Number '{$room->room_number}'.\n" .
                         "Analyze the issue description text and return a strict JSON object with these exact keys:\n" .
                         "1. 'issue_type' (string, categorize into exactly one of these lowercase options: 'plumbing', 'electrical', 'appliance', 'structural', 'other')\n" .
                         "2. 'description' (string, clean detailed text extracted from user input describing the problem)\n" .
                         "3. 'repair_cost' (numeric float value representing the cost if mentioned, or 0.00 if not specified)\n" .
                         "4. 'status' (string, MUST be exactly one of these lowercase options: 'open', 'in_progress', 'closed')\n\n" .
                         "Rules:\n" .
                         "- Output raw JSON only. Do not wrap in markdown code blocks.\n\n" .
                         "Text to parse:\n";

        $finalPrompt = $systemContext . $validated['user_input'];

        // 4. Send request to Gemini
        $aiResponse = $this->geminiService->generateText($finalPrompt);

        if (is_null($aiResponse)) {
            return response()->json([
                'success' => false,
                'message' => 'The AI processing engine is currently offline.'
            ], 503);
        }

        // 5. Clean string data strings
        $cleanedResponse = $aiResponse;
        $cleanedResponse = str_replace(['```json', '```JSON', '```'], '', $cleanedResponse);
        $cleanedResponse = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $maintenanceData = json_decode($cleanedResponse, true);

        // 6. JSON Integrity verification
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($maintenanceData)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an unparseable maintenance schema data.',
                'debug' => $aiResponse
            ], 422);
        }

        // 7. Store natively inside your maintenance table using exact columns
        $maintenance = Maintenance::create([
            'room_id'       => $room->room_id,
            'issue_type'    => $maintenanceData['issue_type'] ?? 'other',
            'description'   => $maintenanceData['description'] ?? $validated['user_input'],
            'reported_date' => now()->toDateString(), // Formats as YYYY-MM-DD for your date field
            'resolved_date' => null,                  // Brand new issue, not resolved yet
            'status'        => $maintenanceData['status'] ?? 'open', 
            'repair_cost'   => $maintenanceData['repair_cost'] ?? 0.00,
        ]);

        return response()->json([
            "message" => "Maintenance issue registered and assigned successfully!",
            "data" => $maintenance
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'room_id' => 'required|exists:room,room_id',
            'issue_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reported_date' => 'required|date',
            'resolved_date' => 'nullable|date|after_or_equal:reported_date',
            'status' => 'required|in:reported,in_progress,resolved',
            'repair_cost' => 'nullable|numeric|min:0',
        ]);

        $maintenance = Maintenance::create($validatedData);

        return response()->json([
            "message" => "Maintenance record created successfully!",
            "data" => $maintenance
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $maintenance = Maintenance::findOrFail($id);

        if(!$maintenance){
            return response()->json([
                "message" => "Maintenance record not found!"
            ],404);
        }

        return response()->json([
            "message" => "Get maintenance record successful!",
            "data" => $maintenance
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $maintenance = Maintenance::findOrFail($id);
        
        if(!$maintenance){
            return response()->json([
                "message" => "Maintenance record not found!"
            ],404);
        }

        $validatedData = $request->validate([
            'room_id' => 'sometimes|exists:room,room_id',
            'issue_type' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'reported_date' => 'sometimes|date',
            'resolved_date' => 'nullable|date|after_or_equal:reported_date',
            'status' => 'sometimes|in:reported,in_progress,resolved',
            'repair_cost' => 'nullable|numeric|min:0',
        ]);

        $maintenance->update($validatedData);

        return response()->json([
            "message" => "Maintenance record updated successfully!",
            "data" => $maintenance
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $maintenance = Maintenance::firstWhere('maintenance_id', $id);
        
        if(!$maintenance){
            return response()->json([
                "message" => "Maintenance record not found!"
            ],404);
        }

        $maintenance->delete();

        return response()->json([
            "message" => "Maintenance record deleted successfully!"
        ],200);
    }
}
