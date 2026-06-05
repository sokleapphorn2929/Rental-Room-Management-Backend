<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
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
        $invoices = Invoice::all();

        return response()->json([
            "message" => "Get all invoices successful!",
            "data" => $invoices
        ],200);
    }

    public function storeFromAi(Request $request): JsonResponse
    {
        // 1. Validate the relational contract link and raw utility text
        $validated = $request->validate([
            'contract_id' => 'required|integer|exists:contract,contract_id',
            'user_input'  => 'required|string|max:2000',
        ]);

        // 2. Fetch contract contextual data to help guide the parsing engine
        $contract = Contract::findOrFail($validated['contract_id']);

        // 3. Build a strict system prompt targeting Invoice evaluation rules
        $systemContext = "You are a backend database utility. Analyze the utility/rent billing text details for Contract ID '{$contract->contract_id}'.\n" .
                         "Extract individual billable line items and return a strict JSON object with exactly these keys:\n" .
                         "1. 'billing_month' (string, ISO date format representing the first day of the billed month 'YYYY-MM-01'. If a text implies a month like 'May 2026', use '2026-05-01')\n" .
                         "2. 'room_charge' (numeric float value representing baseline rent. Default to 0.00 if missing)\n" .
                         "3. 'electricity_charge' (numeric float value representing power usage costs. Default to 0.00 if missing)\n" .
                         "4. 'water_charge' (numeric float value representing water usage costs. Default to 0.00 if missing)\n" .
                         "5. 'total_amount' (numeric float value. This MUST be the exact mathematical sum of room_charge + electricity_charge + water_charge)\n" .
                         "6. 'status' (string, MUST be exactly one of these lowercase options: 'pending', 'paid', 'overdue'. Default to 'pending')\n" .
                         "7. 'issue_date' (string, ISO date format 'YYYY-MM-DD'. If no concrete generation date is stated, default to today's date: " . now()->toDateString() . ")\n\n" .
                         "Rules:\n" .
                         "- Output raw JSON only. Do not wrap in markdown syntax.\n\n" .
                         "Billing statement text to analyze:\n";

        $finalPrompt = $systemContext . $validated['user_input'];

        // 4. Request payload generation from Gemini
        $aiResponse = $this->geminiService->generateText($finalPrompt);

        if (is_null($aiResponse)) {
            return response()->json([
                'success' => false,
                'message' => 'The AI processing engine is currently unavailable.'
            ], 503);
        }

        // 5. Clean structural syntax markdown wrappers
        $cleanedResponse = $aiResponse;
        $cleanedResponse = str_replace(['```json', '```JSON', '```'], '', $cleanedResponse);
        $cleanedResponse = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $invoiceData = json_decode($cleanedResponse, true);

        // 6. Verification check on JSON schema validity
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($invoiceData)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an unparseable invoice data schema.',
                'debug' => $aiResponse
            ], 422);
        }

        // 7. Extract items safely with clean internal fallback values
        $roomCharge  = floatval($invoiceData['room_charge'] ?? 0.00);
        $electCharge = floatval($invoiceData['electricity_charge'] ?? 0.00);
        $waterCharge = floatval($invoiceData['water_charge'] ?? 0.00);
        
        // Explicitly calculate total on backend to prevent artificial discrepancy exploits
        $calculatedTotal = $roomCharge + $electCharge + $waterCharge;

        // 8. Save parsed invoice record natively into your database
        $invoice = Invoice::create([
            'contract_id'        => $contract->contract_id,
            'billing_month'      => $invoiceData['billing_month'] ?? now()->startOfMonth()->toDateString(),
            'room_charge'        => $roomCharge,
            'electricity_charge' => $electCharge,
            'water_charge'       => $waterCharge,
            'total_amount'       => $calculatedTotal,
            'status'             => $invoiceData['status'] ?? 'pending',
            'issue_date'         => $invoiceData['issue_date'] ?? now()->toDateString(),
        ]);

        // 9. Return successful response
        return response()->json([
            "message" => "Invoice lines extracted, tallied, and saved successfully!",
            "data" => $invoice
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'contract_id' => 'required|exists:contract,contract_id',
            'billing_month' => 'required|date',
            'room_charge' => 'required|numeric|min:0',
            'electricity_charge' => 'required|numeric|min:0',
            'water_charge' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,overdue',
            'issue_date' => 'required|date',
        ]);

        $invoice = Invoice::create($validatedData);

        return response()->json([
            "message" => "Invoice created successfully!",
            "data" => $invoice
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if(!$invoice){
            return response()->json([
                "message" => "Invoice not found!"
            ],404);
        }

        return response()->json([
            "message" => "Get invoice successful!",
            "data" => $invoice
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if(!$invoice){
            return response()->json([
                "message" => "Invoice not found!"
            ],404);
        }

        $validatedData = $request->validate([
            'contract_id' => 'sometimes|exists:contract,contract_id',
            'billing_month' => 'sometimes|date',
            'room_charge' => 'sometimes|numeric|min:0',
            'electricity_charge' => 'sometimes|numeric|min:0',
            'water_charge' => 'sometimes|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,paid,overdue',
            'issue_date' => 'sometimes|date',
        ]);

        $invoice->update($validatedData);

        return response()->json([
            "message" => "Invoice updated successfully!",
            "data" => $invoice
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $invoice = Invoice::firstWhere('invoice_id', $id);

        if(!$invoice){
            return response()->json([
                "message" => "Invoice not found!"
            ],404);
        }

        $invoice->delete();

        return response()->json([
            "message" => "Invoice deleted successfully!"
        ],200);
    }
}
