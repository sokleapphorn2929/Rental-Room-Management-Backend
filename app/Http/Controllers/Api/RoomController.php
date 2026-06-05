<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Room;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
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
        $rooms = Room::all();

        return response()->json([
            "message" => "Get all rooms successful!",
            "data" => $rooms
        ],200);
    }

    public function storeFromAi(Request $request): JsonResponse
    {
        // 1. Validate the building target and the raw text input
        $validated = $request->validate([
            'building_id' => 'required|integer|exists:building,building_id', // Adjust table/column key name to match your migration
            'user_input'  => 'required|string|max:2000',
        ]);

        // 2. Fetch the building to pass its context (like total floors) to the AI for safety checks
        $building = Building::findOrFail($validated['building_id']);

        // 3. Craft a strict system prompt tailored to your Room ENUM and DECIMAL definitions
        $systemContext = "You are a backend database utility. Analyze the room description text for a building named '{$building->building_name}'.\n" .
                         "Extract the room features and return a strict JSON object with exactly these keys:\n" .
                         "1. 'room_number' (string, max 10 characters, e.g., '101', 'A-02')\n" .
                         "2. 'room_type' (string, MUST be exactly one of these lowercase options: 'single', 'double', 'studio')\n" .
                         "3. 'floor_number' (integer, maximum limit is {$building->total_floors})\n" .
                         "4. 'monthly_price' (numeric float value, e.g., 120.50)\n" .
                         "5. 'status' (string, MUST be exactly one of these lowercase options: 'available', 'occupied', 'maintenance')\n" .
                         "6. 'area_sqm' (numeric float value representing square meters, or null if unknown)\n" .
                         "7. 'description' (string short summary text or null)\n\n" .
                         "Rules:\n" .
                         "- Output raw JSON only. Do not wrap in markdown syntax.\n\n" .
                         "Room text to analyze:\n";

        $finalPrompt = $systemContext . $validated['user_input'];

        // 4. Request payload generation from Gemini
        $aiResponse = $this->geminiService->generateText($finalPrompt);

        if (is_null($aiResponse)) {
            return response()->json([
                'success' => false,
                'message' => 'The AI processing engine is currently unavailable.'
            ], 503);
        }

        // 5. Clean the string response
        $cleanedResponse = $aiResponse;
        $cleanedResponse = str_replace(['```json', '```JSON', '```'], '', $cleanedResponse);
        $cleanedResponse = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $roomData = json_decode($cleanedResponse, true);

        // 6. Verification check
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($roomData)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an unparseable room data schema.',
                'debug' => $aiResponse
            ], 422);
        }

        // 7. Save parsed features natively into your 'បន្ទប់ (Room)' table
        $room = Room::create([
            'building_id'   => $building->building_id, // Linked foreign key constraint
            'room_number'   => $roomData['room_number'],
            'room_type'     => $roomData['room_type'] ?? 'single',
            'floor_number'  => $roomData['floor_number'] ?? 1,
            'monthly_price' => $roomData['monthly_price'] ?? 0.00,
            'status'        => $roomData['status'] ?? 'available',
            'area_sqm'      => $roomData['area_sqm'],
            'description'   => $roomData['description'],
        ]);

        return response()->json([
            "message" => "Room extracted and saved into database successfully!",
            "data" => $room
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'building_id' => 'required|exists:building,building_id',
            'room_number' => 'required|string|max:255',
            'room_type' => 'required|in:single,double,studio',
            'floor_number' => 'required|integer|min:1',
            'monthly_price' => 'required|numeric|min:0',
            'status' => 'required|in:available,occupied,maintenance',
            'area_sqm' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $room = Room::create($validatedData);

        return response()->json([
            "message" => "Room created successfully!",
            "data" => $room
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room = Room::findOrFail($id);

        if(!$room) {
            return response()->json([
                "message" => "Room not found!"
            ],404);
        }

        return response()->json([
            "message" => "Get room details successful!",
            "data" => $room
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $room = Room::findOrFail($id);

        if(!$room) {
            return response()->json([
                "message" => "Room not found!"
            ],404);
        }

        $validatedData = $request->validate([
            'building_id' => 'sometimes|exists:building,building_id',
            'room_number' => 'sometimes|string|max:255',
            'room_type' => 'sometimes|in:single,double,studio',
            'floor_number' => 'sometimes|integer|min:1',
            'monthly_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:available,occupied,maintenance',
            'area_sqm' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $room->update($validatedData);

        return response()->json([
            "message" => "Room updated successfully!",
            "data" => $room
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // $room = Room::findOrFail($id);

        $room = Room::firstWhere('room_id', $id);

         if(!$room) {
            return response()->json([
                "message" => "Room not found!"
            ],404);
        }

        if(!$room) {
            return response()->json([
                "message" => "Room not found!"
            ],404);
        }

        $room->maintenances()->delete();
        $room->amenities()->delete();
        $room->delete();

        return response()->json([
            "message" => "Room deleted successfully!"
        ],200);
    }
}
