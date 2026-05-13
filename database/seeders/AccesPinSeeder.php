<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccesPinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\AccessPin::insert([
        ['pin' => '1234', 'owner_name' => 'Admin Principal', 'is_active' => true],
        ['pin' => '5678', 'owner_name' => 'Usuario Invitado', 'is_active' => true],
        ['pin' => '0000', 'owner_name' => 'Mantenimiento', 'is_active' => true],
    ]);
}
    }
