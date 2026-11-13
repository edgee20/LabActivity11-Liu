<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('bulletin2026');

        User::create([
            'name' => 'Lola Liu',
            'email' => 'lola.liu@bulletin.test',
            'password' => $password,
        ]);

        $kagawad = User::create([
            'name' => 'Kagawad Ej Liu',
            'email' => 'kagawad.liu@bulletin.test',
            'password' => $password,
        ]);

        $author = Author::where('email', 'kagawad.liu@bulletin.test')->firstOrFail();
        $author->update(['user_id' => $kagawad->id]);

        User::factory()->count(8)->create();
    }
}
