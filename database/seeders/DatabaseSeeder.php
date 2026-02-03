<?php

namespace Database\Seeders;

use App\Models\Bioimpedance;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Root (acceso total)
        User::create([
            'name' => 'Root',
            'email' => 'root@example.com',
            'password' => Hash::make('password'),
            'role' => 'root',
        ]);

        // Admin
        User::create([
            'name' => 'Nico Gabadian',
            'email' => 'nicogabadian2@gmail.com',
            'password' => Hash::make('0600pro'),
            'role' => 'admin',
        ]);

        // Create some members
        $members = [
            [
                'name' => 'Juan Pérez',
                'document_number' => '1234567',
                'email' => 'juan@example.com',
                'date_of_birth' => '1990-05-15',
                'gender' => 'male',
            ],
            [
                'name' => 'María García',
                'document_number' => '7654321',
                'email' => 'maria@example.com',
                'date_of_birth' => '1995-10-20',
                'gender' => 'female',
            ],
        ];

        foreach ($members as $memberData) {
            $member = Member::create($memberData);

            // Create Membership Plan
            MembershipPlan::create([
                'member_id' => $member->id,
                'monthly_fee' => 250000,
                'weekly_frequency' => 3,
                'start_date' => now()->subMonths(3),
                'is_active' => true,
            ]);

            // Create Bioimpedance records
            for ($i = 0; $i < 3; $i++) {
                Bioimpedance::create([
                    'member_id' => $member->id,
                    'date' => now()->subMonths($i),
                    'height' => 175,
                    'weight' => 80 - ($i * 0.5),
                    'imc' => 26.1,
                    'body_fat_percentage' => 22 - ($i * 0.2),
                    'muscle_mass_percentage' => 35 + ($i * 0.1),
                    'kcal' => 1800,
                    'metabolic_age' => 32,
                    'visceral_fat_percentage' => 8,
                    'notes' => "Control mensual $i",
                ]);
            }

            // Create Payment records
            for ($i = 0; $i < 3; $i++) {
                Payment::create([
                    'member_id' => $member->id,
                    'month' => now()->subMonths($i)->format('Y-m'),
                    'amount' => 250000,
                    'payment_date' => now()->subMonths($i)->startOfMonth()->addDays(5),
                    'status' => 'paid',
                ]);
            }
        }
    }
}
