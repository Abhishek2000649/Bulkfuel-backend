<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Address;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /* ================= ADMIN USER ================= */

        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@test.com',
            'password' => Hash::make('123456'),
            'role'     => 'ADMIN'
        ]);

        Address::create([
            'user_id' => $admin->id,
            'address' => 'Admin Office Address',
            'city'    => 'Delhi',
            'state'   => 'Delhi',
            'pincode' => '110001'
        ]);

        /* ================= NORMAL USER ================= */

        $user = User::create([
            'name'     => 'Test User',
            'email'    => 'user@test.com',
            'password' => Hash::make('123456'),
            'role'     => 'USER'
        ]);

        Address::create([
            'user_id' => $user->id,
            'address' => 'User Home Address',
            'city'    => 'Mumbai',
            'state'   => 'Maharashtra',
            'pincode' => '400001'
        ]);
        $agent = User::create([
            'name'     => 'Test Agent',
            'email'    => 'agent@test.com',
            'password' => Hash::make('123456'),
            'role'     => 'delivery_agent'
        ]);
        Address::create([
            'user_id' => $agent->id,
            'address' => 'Agent Home Address',
            'city'    => 'Delhi',
            'state'   => 'Delhi',
            'pincode' => '204041'
        ]);

        /* ================= SINGLE PRODUCT ================= */
        Category::create([
            'name'=>'Book',
        ]);

        Product::create([
            'name'        => 'Java Programming Book',
            'price'       => 499,
            'stock'       => 20,
            'category_id'=>1,
            'description' => 'Complete Java programming book for beginners'
        ]);
    }
}
