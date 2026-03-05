<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence\Seeders;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SubscriptionTestSeeder extends Seeder
{
    public function run(): void
    {
        $action = $this->command->option('action');

        if (!$action) {
            $this->command->error("No action provided. Usage: php artisan module:seed Subscription --class=SubscriptionSeeder --action=[populate|clear|refresh|reset-dates]");
            return;
        }

        $this->command->info("Action: {$action}");

        match ($action) {
            'populate' => $this->populate(),
            'clear' => $this->clear(),
            'reset-dates' => $this->resetBillingDates(),
            'refresh' => $this->refresh(),
            default => $this->command->error("Invalid action: {$action}")
        };
    }

    public function populate(): void
    {
        $this->command->info("Populating subscriptions...");

        $user = DB::table('users')->first();

        if (!$user) {
            $userId = Uuid::uuid4()->toString();
            DB::table('users')->insert([
              'id' => $userId,
              'name' => 'admin',
              'email' => 'admin@gmail.com',
              'password' => bcrypt('12345678'),
              'created_at' => now(),
              'updated_at' => now(),
            ]);
            $this->command->info("Admin user created");
        } else {
            $userId = $user->id;
            $this->command->info("User found: {$user->email}");
        }

        $count = 0;
        foreach (range(1, 10) as $i) {
            DB::table('subscriptions')->insert([
              'id' => Uuid::uuid4()->toString(),
              'name' => "Netflix {$i}",
              'price' => 3500,
              'currency' => 'BRL',
              'billing_cycle' => 'monthly',
              'next_billing_date' => now()->addMonth(),
              'category' => 'Streaming',
              'status' => 'active',
              'user_id' => $userId,
              'created_at' => now(),
              'updated_at' => now(),
            ]);

            $count++;
        }

        $this->command->info("{$count} subscriptions created successfully!");
    }

    public function clear(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $this->command->warn("Clearing subscriptions...");

            $count = DB::table('billing_histories')->count();
            DB::table('billing_histories')->truncate();

            $this->command->info("{$count} billing histories removed!");

            $count = DB::table('subscriptions')->count();
            DB::table('subscriptions')->truncate();

            $this->command->info("{$count} subscriptions removed!");
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function resetBillingDates(): void
    {
        $this->command->warn("Resetting billing dates to today...");

        $count = DB::table('subscriptions')
          ->update([
            'next_billing_date' => now(),
            'updated_at' => now(),
          ]);

        $this->command->info("{$count} subscriptions updated with today's date!");
    }

    public function refresh(): void
    {
        $this->clear();
        $this->populate();
    }
}
