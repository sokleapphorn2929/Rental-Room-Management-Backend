<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
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
        $contracts = Contract::all();

        return response()->json([
            "message" => "Get all contracts successful!",
            "data" => $contracts
        ],200);
    }

    public function storeFromAi(Request $request): JsonResponse
    {
        // 1. Validate relational targets and the raw text input
        $validated = $request->validate([
            'room_id'    => 'required|integer|exists:room,room_id',
            'tenant_id'  => 'required|integer|exists:tenant,tenant_id',
            'user_input' => 'required|string|max:2000',
        ]);

        // 2. Fetch context information to feed into the AI engine
        $room = Room::findOrFail($validated['room_id']);
        $tenant = Tenant::findOrFail($validated['tenant_id']);

        // 3. Build a strict system prompt targeting Contract validation criteria (including created_at)
        $systemContext = "You are a backend database utility. Analyze the lease contract description text.\n" .
                         "Context: Tenant is '{$tenant->full_name}' and Room Number is '{$room->room_number}'.\n" .
                         "Extract contract specifications and return a strict JSON object with exactly these keys:\n" .
                         "1. 'start_date' (string, ISO date format 'YYYY-MM-DD'. If a generic statement like 'starting next month' is used, calculate it relative to today's date: " . now()->toDateString() . ")\n" .
                         "2. 'end_date' (string, ISO date format 'YYYY-MM-DD'. Must be equal to or later than start_date. If a duration like '1 year' is given, calculate it from the start_date)\n" .
                         "3. 'deposit_amount' (numeric float value representing the security deposit down payment, e.g., 500.00. Default to 0.00 if missing)\n" .
                         "4. 'status' (string, MUST be exactly one of these lowercase options: 'active', 'terminated', 'expired'. Default to 'active')\n" .
                         "5. 'notes' (string, short summary regarding additional rental conditions or null)\n" .
                         "6. 'created_at' (string, ISO date format 'YYYY-MM-DD'. If the text specifies when this document/record was officially drawn up or signed, extract that date. If no specific creation date is stated, use today's date: " . now()->toDateString() . ")\n\n" .
                         "Rules:\n" .
                         "- Output raw JSON only. Do not wrap in markdown syntax.\n\n" .
                         "Contract text to analyze:\n";

        $finalPrompt = $systemContext . $validated['user_input'];

        // 4. Request payload generation from Gemini
        $aiResponse = $this->geminiService->generateText($finalPrompt);

        if (is_null($aiResponse)) {
            return response()->json([
                'success' => false,
                'message' => 'The AI processing engine is currently unavailable.'
            ], 503);
        }

        // 5. Clean structural syntax wrappers
        $cleanedResponse = $aiResponse;
        $cleanedResponse = str_replace(['```json', '```JSON', '```'], '', $cleanedResponse);
        $cleanedResponse = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $contractData = json_decode($cleanedResponse, true);

        // 6. Verification check on JSON schema validity
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($contractData)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an unparseable contract data schema.',
                'debug' => $aiResponse
            ], 422);
        }

        // 7. Prevent logical errors locally before hitting Laravel model level constraint checks
        $startDate = $contractData['start_date'] ?? now()->toDateString();
        $endDate   = $contractData['end_date'] ?? now()->toDateString();

        if (strtotime($endDate) < strtotime($startDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Logical evaluation failed: The calculated contract end date cannot be earlier than the start date.',
                'extracted_dates' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ], 422);
        }

        // 8. Save parsed contract record natively into your database
        $contract = Contract::create([
            'room_id'        => $room->room_id,
            'tenant_id'      => $tenant->tenant_id,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'deposit_amount' => $contractData['deposit_amount'] ?? 0.00,
            'status'         => $contractData['status'] ?? 'active',
            'notes'          => $contractData['notes'] ?? null,
            'created_at'     => $contractData['created_at'] ?? now()->toDateString(), // Fallback to current date if missing from extraction
        ]);

        // 9. Return successful response 
        return response()->json([
            "message" => "Contract extracted and saved into database successfully!",
            "data" => $contract
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'room_id' => 'required|exists:room,room_id',
            'tenant_id' => 'required|exists:tenant,tenant_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'deposit_amount' => 'required|numeric|min:0',
            'status' => 'required|in:active,terminated,expired',
            'notes' => 'nullable|string',
            'created_at' => 'required|date',
        ]);

        $contract = Contract::create($validatedData);

        return response()->json([
            "message" => "Contract created successfully!",
            "data" => $contract
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contract = Contract::findOrFail($id);

        if(!$contract){
            return response()->json([
                "message" => "Contract not found!"
            ],404);
        }

        return response()->json([
            "message" => "Get contract successful!",
            "data" => $contract
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contract = Contract::findOrFail($id);
        
        if(!$contract){
            return response()->json([
                "message" => "Contract not found!"
            ],404);
        }

        $validatedData = $request->validate([
            'room_id' => 'sometimes|exists:room,room_id',
            'tenant_id' => 'sometimes|exists:tenant,tenant_id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'deposit_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,terminated,expired',
            'notes' => 'nullable|string',
            'created_at' => 'sometimes|date',
        ]);

        $contract->update($validatedData);

        return response()->json([
            "message" => "Contract updated successfully!",
            "data" => $contract
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contract = Contract::firstWhere('contract_id', $id);

        if(!$contract){
            return response()->json([
                "message" => "Contract not found!"
            ],404);
        }

        $contract->delete();

        return response()->json([
            "message" => "Contract deleted successfully!"
        ],200);
    }
}
