<?php

namespace Database\Seeders;

use App\Enums\ServiceTypeEnum;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Service;
use App\Models\Unavailability;
use App\Models\User;
use App\Models\WorkingHours;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BookingTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create users for barbers
        $barberUser1 = User::create([
            'name' => 'Matthew',
            'email' => 'matthew@example.com',
            'password' => Hash::make('BSideLabel1!'),
            'email_verified_at' => now(),
        ]);

        $barberUser2 = User::create([
            'name' => 'Santi Barber',
            'email' => 'santi@example.com',
            'password' => Hash::make('BSideLabel1!'),
            'email_verified_at' => now(),
        ]);

        $barberUser3 = User::create([
            'name' => 'Scott Edwards',
            'email' => 'scott@example.com',
            'password' => Hash::make('BSideLabel1!'),
            'email_verified_at' => now(),
        ]);

        $barber1 = Barber::create([
            'user_id' => $barberUser1->id,
        ]);

        $barber2 = Barber::create([
            'user_id' => $barberUser2->id,
        ]);

        $barber3 = Barber::create([
            'user_id' => $barberUser3->id,
        ]);

        $haircut = Service::create([
            'name' => 'Skin Fade',
            'type' => ServiceTypeEnum::HAIR,
            'duration_minutes' => 30,
            'price' => 2500, // $25.00
        ]);

        $beardTrim = Service::create([
            'name' => 'Beard Trim',
            'type' => ServiceTypeEnum::BEARD,
            'duration_minutes' => 20,
            'price' => 1500, // $15.00
        ]);

        $buzzCut = Service::create([
            'name' => 'Buzz Cut',
            'type' => ServiceTypeEnum::HAIR,
            'duration_minutes' => 15,
            'price' => 1800, // $18.00
        ]);

        $fadeHaircut = Service::create([
            'name' => 'Fade Haircut',
            'type' => ServiceTypeEnum::HAIR,
            'duration_minutes' => 45,
            'price' => 3500, // $35.00
        ]);

        $fullBeardService = Service::create([
            'name' => 'Full Beard Service',
            'type' => ServiceTypeEnum::BEARD,
            'duration_minutes' => 40,
            'price' => 3000, // $30.00
        ]);

        $barber1->services()->attach([
            $haircut->id => ['price' => 2500, 'duration_minutes' => 30],
            $beardTrim->id => ['price' => 1500, 'duration_minutes' => 20],
            $fadeHaircut->id => ['price' => 3500, 'duration_minutes' => 45],
        ]);

        $barber2->services()->attach([
            $haircut->id => ['price' => 2000, 'duration_minutes' => 25],
            $buzzCut->id => ['price' => 1800, 'duration_minutes' => 15],
            $fullBeardService->id => ['price' => 3200, 'duration_minutes' => 40],
        ]);

        $barber3->services()->attach([
            $haircut->id => ['price' => 2800, 'duration_minutes' => 35],
            $beardTrim->id => ['price' => 1800, 'duration_minutes' => 25],
            $buzzCut->id => ['price' => 1600, 'duration_minutes' => 12],
            $fadeHaircut->id => ['price' => 4000, 'duration_minutes' => 50],
            $fullBeardService->id => ['price' => 3500, 'duration_minutes' => 45],
        ]);

        // Create working hours for all barbers (Monday to Friday, 9 AM to 6 PM)
        foreach ([$barber1, $barber2, $barber3] as $barber) {
            for ($day = 1; $day <= 5; $day++) { // Monday to Friday
                WorkingHours::create([
                    'barber_id' => $barber->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'is_available' => true,
                ]);
            }

            // Saturday (shorter hours)
            WorkingHours::create([
                'barber_id' => $barber->id,
                'day_of_week' => 6,
                'start_time' => '10:00',
                'end_time' => '16:00',
                'is_available' => true,
            ]);

            // Sunday (closed)
            WorkingHours::create([
                'barber_id' => $barber->id,
                'day_of_week' => 0,
                'start_time' => '00:00',
                'end_time' => '00:00',
                'is_available' => false,
            ]);
        }

        // Create some unavailabilities
        Unavailability::create([
            'barber_id' => $barber1->id,
            'reason' => 'Vacation',
            'start_time' => Carbon::now()->addDays(7)->setHour(9)->setMinute(0),
            'end_time' => Carbon::now()->addDays(10)->setHour(18)->setMinute(0),
        ]);

        Unavailability::create([
            'barber_id' => $barber2->id,
            'reason' => 'Dentist appointment',
            'start_time' => Carbon::now()->addDays(3)->setHour(14)->setMinute(0),
            'end_time' => Carbon::now()->addDays(3)->setHour(16)->setMinute(0),
        ]);

        // Create client users
        $clientUser1 = User::create([
            'name' => 'Alex Johnson',
            'email' => 'alex@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $clientUser2 = User::create([
            'name' => 'Sarah Williams',
            'email' => 'sarah@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $clientUser3 = User::create([
            'name' => 'David Brown',
            'email' => 'david@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create clients
        $client1 = Client::create([
            'user_id' => $clientUser1->id,
            'name' => 'Alex Johnson',
            'email' => 'alex@example.com',
            'phone' => '+1234567890',
        ]);

        $client2 = Client::create([
            'user_id' => $clientUser2->id,
            'name' => 'Sarah Williams',
            'email' => 'sarah@example.com',
            'phone' => '+1234567891',
        ]);

        $client3 = Client::create([
            'user_id' => $clientUser3->id,
            'name' => 'David Brown',
            'email' => 'david@example.com',
            'phone' => '+1234567892',
        ]);

        // Create some bookings
        $booking1 = Booking::create([
            'barber_id' => $barber1->id,
            'client_id' => $client1->id,
            'start_time' => Carbon::now()->addDays(1)->setHour(10)->setMinute(0),
            'end_time' => Carbon::now()->addDays(1)->setHour(10)->setMinute(30),
            'status' => 'confirmed',
            'notes' => 'Regular customer, likes it short on the sides',
        ]);

        $booking2 = Booking::create([
            'barber_id' => $barber2->id,
            'client_id' => $client2->id,
            'start_time' => Carbon::now()->addDays(2)->setHour(14)->setMinute(0),
            'end_time' => Carbon::now()->addDays(2)->setHour(14)->setMinute(45),
            'status' => 'confirmed',
            'notes' => 'First time customer',
        ]);

        $booking3 = Booking::create([
            'barber_id' => $barber3->id,
            'client_id' => $client3->id,
            'start_time' => Carbon::now()->addDays(4)->setHour(16)->setMinute(0),
            'end_time' => Carbon::now()->addDays(4)->setHour(17)->setMinute(0),
            'status' => 'pending',
            'notes' => 'Wants both haircut and beard service',
        ]);

        // Attach services to bookings
        $booking1->services()->attach($haircut->id);
        $booking2->services()->attach($fadeHaircut->id);
        $booking3->services()->attach([$haircut->id, $fullBeardService->id]);

        // Create a few more bookings for testing
        for ($i = 1; $i <= 5; $i++) {
            $booking = Booking::create([
                'barber_id' => collect([$barber1, $barber2, $barber3])->random()->id,
                'client_id' => collect([$client1, $client2, $client3])->random()->id,
                'start_time' => Carbon::now()->addDays(rand(1, 14))->setHour(rand(10, 16))->setMinute(0),
                'end_time' => Carbon::now()->addDays(rand(1, 14))->setHour(rand(10, 16))->addMinutes(30),
                'status' => collect(['confirmed', 'pending', 'completed'])->random(),
                'notes' => 'Test booking #' . $i,
            ]);

            // Attach random services
            $randomServices = collect([$haircut, $beardTrim, $buzzCut])->random(rand(1, 2));
            $booking->services()->attach($randomServices->pluck('id'));
        }

        $this->command->info('✅ Booking test data seeded successfully!');
        $this->command->info('📊 Created:');
        $this->command->info('   • 3 barbers with working hours');
        $this->command->info('   • 5 services (hair & beard)');
        $this->command->info('   • 3 clients');
        $this->command->info('   • 8 bookings');
        $this->command->info('   • 2 unavailability periods');
    }
}
