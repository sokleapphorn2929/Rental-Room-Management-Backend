<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Room;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmenityController extends Controller
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
        $amenities = Amenity::all();

        return response()->json([
            "message" => "Get all amenities successful!",
            'data' => $amenities
        ]);
    }

    public function storeFromAi(Request $request): JsonResponse
    {
        // 1. Validate the parent target room and the raw text input
        $validated = $request->validate([
            'room_id'    => 'required|integer|exists:room,room_id', // Adjust table name to match your migration ('room' vs 'rooms')
            'user_input' => 'required|string|max:2000',
        ]);

        // 2. Fetch the target room to pass context to the AI
        $room = Room::findOrFail($validated['room_id']);

        // 3. Craft a strict system prompt tailored to your Amenity schema
        $systemContext = "You are a backend database utility. Analyze the amenity description text intended for Room Number '{$room->room_number}'.\n" .
                         "Extract the amenity features and return a strict JSON object with exactly these keys:\n" .
                         "1. 'amenity_name' (string, max 255 characters, e.g., 'Air Conditioner', 'Double Bed', 'Smart TV')\n" .
                         "2. 'note' (string, short summary/condition notes, or null if nothing relevant is mentioned)\n" .
                         "3. 'added_date' (string, ISO date format 'YYYY-MM-DD'. If no date is inferred from the text, use today's date: " . now()->toDateString() . ")\n\n" .
                         "Rules:\n" .
                         "- Output raw JSON only. Do not wrap in markdown syntax.\n\n" .
                         "Amenity text to analyze:\n";

        $finalPrompt = $systemContext . $validated['user_input'];

        // 4. Request payload generation from Gemini
        $aiResponse = $this->geminiService->generateText($finalPrompt);

        if (is_null($aiResponse)) {
            return response()->json([
                'success' => false,
                'message' => 'The AI processing engine is currently unavailable.'
            ], 503);
        }

        // 5. Clean the string response (strip markdown wrappers & hidden characters)
        $cleanedResponse = $aiResponse;
        $cleanedResponse = str_replace(['```json', '```JSON', '```'], '', $cleanedResponse);
        $cleanedResponse = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $amenityData = json_decode($cleanedResponse, true);

        // 6. Verification check on JSON schema validity
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($amenityData)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an unparseable amenity data schema.',
                'debug' => $aiResponse
            ], 422);
        }

        // 7. Save parsed features natively into your 'Amenity' table
        $amenity = Amenity::create([
            'room_id'      => $room->room_id, // Linked foreign key constraint
            'amenity_name' => $amenityData['amenity_name'] ?? 'Unknown Amenity',
            'note'         => $amenityData['note'] ?? null,
            'added_date'   => $amenityData['added_date'] ?? now()->toDateString(),
        ]);

        // 8. Return a successful 201 Created response
        return response()->json([
            "message" => "Amenity extracted and saved into database successfully!",
            "data" => $amenity
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'room_id' => 'required|exists:room,room_id',
            'amenity_name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'added_date' => 'required|date',
        ]);

        $amenity = Amenity::create($validatedData);

        return response()->json([
            "message" => "Amenity created successfully!",
            "data" => $amenity
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $amenity = Amenity::findOrFail($id);

        if(!$amenity) {
            return response()->json([
                "message" => "Amenity not found!"
            ],404);
        }

        return response()->json([
            "message" => "Get amenity successful!",
            "data" => $amenity
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $amenity = Amenity::findOrFail($id);

        if(!$amenity) {
            return response()->json([
                "message" => "Amenity not found!"
            ],404);
        }

        $validatedData = $request->validate([
            'room_id' => 'sometimes|exists:room,room_id',
            'amenity_name' => 'sometimes|string|max:255',
            'note' => 'nullable|string',
            'added_date' => 'sometimes|date',
        ]);

        $amenity->update($validatedData);

        return response()->json([
            "message" => "Amenity updated successfully!",
            "data" => $amenity
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $amenity = Amenity::firstWhere('amenity_id', $id);

        if(!$amenity) {
            return response()->json([
                "message" => "Amenity not found!"
            ],404);
        }

        $amenity->delete();

        return response()->json([
            "message" => "Amenity deleted successfully!"
        ],200);
    }
}
