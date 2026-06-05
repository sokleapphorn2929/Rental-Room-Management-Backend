<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
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
        $tenants = Tenant::all();

        return response()->json([
            "message" => "Get all tenants successful!",
            "data" => $tenants
        ],200);
    }

    public function storeFromAi(Request $request): JsonResponse
    {
        // 1. Validate the raw text input from the user/AI service payload
        $validated = $request->validate([
            'user_input' => 'required|string|max:2000',
        ]);

        // 2. Craft a strict system prompt tailored to your Tenant validation rules
        $systemContext = "You are a backend database utility. Analyze the tenant registration text provided.\n" .
                         "Extract the tenant information and return a strict JSON object with exactly these keys:\n" .
                         "1. 'full_name' (string, max 255 characters)\n" .
                         "2. 'phone' (string, max 20 characters, strip out spaces if necessary)\n" .
                         "3. 'email' (string, valid email format)\n" .
                         "4. 'national_id' (string, max 50 characters, identification card or passport number)\n" .
                         "5. 'gender' (string, MUST be exactly one of these lowercase options: 'male', 'female', 'other')\n" .
                         "6. 'current_address' (string, full address string, or null if not provided)\n" .
                         "7. 'move_in_date' (string, ISO date format 'YYYY-MM-DD'. If no specific date is mentioned, default to today: " . now()->toDateString() . ")\n\n" .
                         "Rules:\n" .
                         "- Output raw JSON only. Do not wrap in markdown syntax.\n\n" .
                         "Tenant text to analyze:\n";

        $finalPrompt = $systemContext . $validated['user_input'];

        // 3. Request payload generation from Gemini
        $aiResponse = $this->geminiService->generateText($finalPrompt);

        if (is_null($aiResponse)) {
            return response()->json([
                'success' => false,
                'message' => 'The AI processing engine is currently unavailable.'
            ], 503);
        }

        // 4. Clean the string response (strip markdown syntax wrappers)
        $cleanedResponse = $aiResponse;
        $cleanedResponse = str_replace(['```json', '```JSON', '```'], '', $cleanedResponse);
        $cleanedResponse = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $tenantData = json_decode($cleanedResponse, true);

        // 5. Verification check on JSON schema validity
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($tenantData)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an unparseable tenant data schema.',
                'debug' => $aiResponse
            ], 422);
        }

        // 6. Manual validation layer for unique database constraints (email & national_id)
        // This prevents nasty SQL query exceptions if the AI processes a duplicated tenant
        $errors = [];
        if (isset($tenantData['email']) && Tenant::where('email', $tenantData['email'])->exists()) {
            $errors['email'] = ['The email extracted by AI has already been taken.'];
        }
        if (isset($tenantData['national_id']) && Tenant::where('national_id', $tenantData['national_id'])->exists()) {
            $errors['national_id'] = ['The national ID extracted by AI has already been taken.'];
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
                'extracted_data' => $tenantData
            ], 422);
        }

        // 7. Save parsed profile data natively into your 'tenant' table
        $tenant = Tenant::create([
            'full_name'       => $tenantData['full_name'] ?? 'Unknown Tenant',
            'phone'           => $tenantData['phone'] ?? '0000000000',
            'email'           => $tenantData['email'] ?? null,
            'national_id'     => $tenantData['national_id'] ?? null,
            'gender'          => $tenantData['gender'] ?? 'other',
            'current_address' => $tenantData['current_address'] ?? null,
            'move_in_date'    => $tenantData['move_in_date'] ?? now()->toDateString(),
        ]);

        // 8. Return a successful 201 Created response
        return response()->json([
            "message" => "Tenant profile extracted and saved into database successfully!",
            "data" => $tenant
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:tenant,email',
            'national_id' => 'required|string|max:50|unique:tenant,national_id',
            'gender' => 'required|in:male,female,other',
            'current_address' => 'nullable|string',
            'move_in_date' => 'required|date',
        ]);

        $tenant = Tenant::create($validatedData);

        return response()->json([
            "message" => "Tenant created successfully!",
            "data" => $tenant
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        if(!$tenant){
            return response()->json([
                "message" => "Tenant not found!"
            ],404);
        }

        return response()->json([
            "message" => "Get tenant details successful!",
            "data" => $tenant
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        if(!$tenant){
            return response()->json([
                "message" => "Tenant not found!"
            ],404);
        }

        $validatedData = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|unique:tenant,email,'.$tenant->tenant_id.',tenant_id',
            'national_id' => 'sometimes|required|string|max:50|unique:tenant,national_id,'.$tenant->tenant_id.',tenant_id',
            'gender' => 'sometimes|required|in:male,female,other',
            'current_address' => 'nullable|string',
            'move_in_date' => 'sometimes|required|date',
        ]);

        $tenant->update($validatedData);

        return response()->json([
            "message" => "Tenant updated successfully!",
            "data" => $tenant
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tenant = Tenant::firstWhere('tenant_id', $id);

        if(!$tenant){
            return response()->json([
                "message" => "Tenant not found!"
            ],404);
        }

        $tenant->delete();

        return response()->json([
            "message" => "Tenant deleted successfully!"
        ], 200);
    }
}
