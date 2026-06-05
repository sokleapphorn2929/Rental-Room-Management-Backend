<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        // Dynamic fallback fallback for gender options
        $genderOptions = ['male', 'female', 'other']; 

        // 1. Fetch EXISTING Admins from your DB instead of faking them
        // Pulls verified admins first (where code is null)
        $verifiedAdminIds = DB::table('admin')
            ->whereNull('verification_code')
            ->pluck('admin_id')
            ->toArray();

        // Fallback: If you haven't verified an admin yet, grab any admin id so it doesn't crash
        if (empty($verifiedAdminIds)) {
            $verifiedAdminIds = DB::table('admin')->pluck('admin_id')->toArray();
        }

        // Safety break: If the admin table is completely empty, stop and warn
        if (empty($verifiedAdminIds)) {
            $this->command->error("No admins found in the 'admin' table! Please register an admin first before seeding.");
            return;
        }

        // 2. Seed Buildings (Catch ONLY your real admin_ids)
        $buildingIds = [];
        for ($i = 0; $i < 5; $i++) {
            $buildingIds[] = DB::table('building')->insertGetId([
                'admin_id' => $faker->randomElement($verifiedAdminIds), 
                'building_name' => 'Building ' . Str::upper(Str::random(1)) . ' ' . rand(1, 20),
                'address' => $faker->address,
                'total_floors' => rand(3, 10),
                'status' => 'active', 
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Seed Rooms
        $roomIds = [];
        $roomStatuses = ['available', 'occupied', 'maintenance'];
        $roomTypes = ['single', 'double', 'studio'];
        
        foreach ($buildingIds as $buildingId) {
            $building = DB::table('building')->where('building_id', $buildingId)->first();
            for ($floor = 1; $floor <= $building->total_floors; $floor++) {
                for ($roomNum = 1; $roomNum <= 4; $roomNum++) {
                    $roomIds[] = DB::table('room')->insertGetId([
                        'building_id' => $buildingId,
                        'room_number' => $floor . str_pad($roomNum, 2, '0', STR_PAD_LEFT), 
                        'room_type' => $faker->randomElement($roomTypes),
                        'floor_number' => $floor,
                        'monthly_price' => $faker->randomElement([120.00, 180.00, 250.00, 400.00]),
                        'status' => $faker->randomElement($roomStatuses),
                        'area_sqm' => $faker->randomElement([22.50, 30.00, 45.00, 55.50]),
                        'description' => $faker->sentence,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 4. Seed Tenants
        $tenantIds = [];
        for ($i = 0; $i < 30; $i++) {
            $tenantIds[] = DB::table('tenant')->insertGetId([
                'full_name' => $faker->name,
                'phone' => substr($faker->phoneNumber, 0, 15),
                'email' => $faker->safeEmail,
                'national_id' => (string)rand(100000000, 999999999),
                'gender' => $faker->randomElement($genderOptions), 
                'current_address' => $faker->address,
                'move_in_date' => $faker->date(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Seed Contracts for Occupied Rooms
        $contractIds = [];
        $occupiedRooms = DB::table('room')->where('status', 'occupied')->get();
        
        foreach ($occupiedRooms as $room) {
            $startDate = Carbon::parse($faker->dateTimeThisYear);
            $contractIds[] = DB::table('contract')->insertGetId([
                'room_id' => $room->room_id,
                'tenant_id' => $faker->randomElement($tenantIds),
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addYear(),
                'deposit_amount' => $room->monthly_price * 2, 
                'status' => 'active',
                'notes' => $faker->sentence,
                'created_at' => now(),
            ]);
        }

        // 6. Seed Invoices for Contracts
        foreach ($contractIds as $contractId) {
            $contract = DB::table('contract')->where('contract_id', $contractId)->first();
            $room = DB::table('room')->where('room_id', $contract->room_id)->first();
            
            $roomCharge = $room->monthly_price;
            $electricity = rand(15, 45);
            $water = rand(5, 15);

            DB::table('invoice')->insert([
                'contract_id' => $contractId,
                'billing_month' => Carbon::now()->startOfMonth(),
                'room_charge' => $roomCharge,
                'electricity_charge' => $electricity,
                'water_charge' => $water,
                'total_amount' => $roomCharge + $electricity + $water,
                'status' => $faker->randomElement(['pending', 'paid', 'overdue']),
                'issue_date' => Carbon::now()->subDays(5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 7. Seed Maintenance
        $maintenanceTypes = ['Plumbing', 'Electrical', 'Air Conditioner', 'Furniture', 'Key Leak'];
        
        $maintenanceTableInfo = DB::select("SHOW COLUMNS FROM `maintenance` LIKE 'status'");
        $acceptedStatuses = ['open', 'closed']; 
        
        if (!empty($maintenanceTableInfo)) {
            $typeStr = $maintenanceTableInfo[0]->Type; 
            preg_match_all("/'([^']+)'/", $typeStr, $matches);
            if (!empty($matches[1])) {
                $acceptedStatuses = $matches[1];
            }
        }

        foreach (array_rand($roomIds, 15) as $index) {
            $roomId = $roomIds[$index];
            $status = $faker->randomElement($acceptedStatuses);
            $isClosed = ($status === 'closed' || $status === 'ជួសជុលរួច' || $status === 'resolved');
            
            DB::table('maintenance')->insert([
                'room_id' => $roomId,
                'issue_type' => $faker->randomElement($maintenanceTypes),
                'description' => $faker->paragraph(1),
                'reported_date' => Carbon::now()->subDays(rand(1, 30)),
                'resolved_date' => $isClosed ? Carbon::now() : null,
                'status' => $status,
                'repair_cost' => $isClosed ? rand(20, 150) : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 8. Seed Amenities
        $amenityPool = ['Wi-Fi Router', 'Air Conditioner', 'Washing Machine', 'Refrigerator', 'Bed Wardrobe'];
        foreach ($roomIds as $roomId) {
            $assignedAmenities = (array) array_rand(array_flip($amenityPool), rand(2, 4));
            foreach ($assignedAmenities as $name) {
                DB::table('amenity')->insert([
                    'room_id' => $roomId,
                    'amenity_name' => $name,
                    'note' => $faker->boolean ? $faker->sentence : null,
                    'added_date' => Carbon::now()->subMonths(rand(6, 12)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}