<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\User\Infrastructure\Persistence\Eloquent\UsersModel;
use Ramsey\Uuid\Uuid;

final class UsersSeeder extends Seeder
{
    /**
     * Execute o seeder.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Raul',
                'surname' => 'Oliveira',
                'email' => 'raulntjj@gmail.com',
                'password' => bcrypt('12345678'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
