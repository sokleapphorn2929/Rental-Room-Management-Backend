<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuildingController extends Controller
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
        $buildings = Building::all();

        return response()->json([
            "message" => "Get all buildings successful!",
            "data" => $buildings
        ],200);
    }

    public function storeFromAi(Request $request): JsonResponse
    {
        // 1. Validate that raw text input exists
        $validated = $request->validate([
            'user_input' => 'required|string|max:2000',
        ]);

        // 2. Craft a system prompt constrained strictly to your Building Schema rules
        $systemContext = "You are a backend database assistant. Analyze the description of a property.\n" .
                         "Extract the building characteristics and return a strict JSON object with exactly these keys:\n" .
                         "1. 'building_name' (string, max 100 characters)\n" .
                         "2. 'address' (string description)\n" .
                         "3. 'total_floors' (integer, default to 1 if not explicitly found)\n" .
                         "4. 'status' (string, must be exactly 'active' or 'inactive')\n\n" .
                         "Rules:\n" .
                         "- Output raw JSON only. Do not wrap in markdown tags.\n\n" .
                         "Property text to analyze:\n";

        $finalPrompt = $systemContext . $validated['user_input'];

        // 3. Handshake with the Gemini engine
        $aiResponse = $this->geminiService->generateText($finalPrompt);

        if (is_null($aiResponse)) {
            return response()->json([
                'success' => false,
                'message' => 'The AI processing engine is currently unavailable.'
            ], 503);
        }

        // 4. Run the Bulletproof Cleansing Layer
        $cleanedResponse = $aiResponse;
        $cleanedResponse = str_replace(['```json', '```JSON', '```'], '', $cleanedResponse);
        $cleanedResponse = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $buildingData = json_decode($cleanedResponse, true);

        // 5. Schema verification guard structural integrity check
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($buildingData)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an unparseable building data format.',
                'debug' => $aiResponse
            ], 422);
        }

        // 6. Secure the logged-in administrator ID handling the operation
        $adminId = Auth::user()->admin_id;

        // 7. Save parsed data natively into your 'អគារ (Building)' table
        $building = Building::create([
            'admin_id'      => $adminId, // Track who created it
            'building_name' => $buildingData['building_name'] ?? 'Unnamed AI Building',
            'address'       => $buildingData['address'] ?? 'No Address Provided',
            'total_floors'  => $buildingData['total_floors'] ?? 1,
            'status'        => $buildingData['status'] ?? 'active',
        ]);

        return response()->json([
            "message" => "Building extracted and saved via AI successfully!",
            "data" => $building
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'building_name' => 'required|string|max:255',
            'address' => 'required|string',
            'total_floors' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive'
        ]);

        $adminId = Auth::user()->admin_id;

        $building = Building::create(array_merge($validatedData, [
            'admin_id' => $adminId
        ]));

        return response()->json([
            "message" => "Building created successfully!",
            "data" => $building
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $building = Building::findOrFail($id);

        if(!$building) {
            return response()->json([
                "message" => "Building not found!"
            ],404);
        }

        return response()->json([
            "message" => "Get building details successful!",
            "data" => $building
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $building = Building::findOrFail($id);

        if(!$building) {
            return response()->json([
                "message" => "Building not found!"
            ],404);
        }

        $validatedData = $request->validate([
            'building_name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'total_floors' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:active,inactive'
        ]);

        $building->update($validatedData);

        return response()->json([
            "message" => "Building updated successfully!",
            "data" => $building
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $building = Building::firstWhere('building_id', $id);

        if(!$building) {
            return response()->json([
                "message" => "Building not found!"
            ],404);
        }

        $building->delete();

        return response()->json([
            "message" => "Building deleted successfully!"
        ],200);
    }
}
