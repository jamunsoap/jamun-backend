<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
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
        // 1. Create Default Master Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@jamunsoap.com'],
            [
                'name' => 'Jamun Admin',
                'password' => Hash::make('admin1234'),
                'role' => 'admin',
                'phone' => '+91 9876543210',
                'address' => 'Surat, Gujarat, India',
            ]
        );

        // 2. Create Realistic Jamun Soap Products (Pack 1, 2, 3 as per website)
        $products = [
            [
                'id' => 1,
                'name' => 'Jadui Jamun Soap (Pack of 1)',
                'slug' => 'jadui-jamun-soap-pack-1',
                'description' => 'Cold-processed botanical soap enriched with pure wild Jamun berry extract, organic cold-pressed neem seed oil, and raw unrefined shea butter for deep pore clarification.',
                'short_description' => 'Ayurvedic clarifying bar for blemish-free, radiant skin.',
                'price' => 249.00,
                'discount_price' => 199.00,
                'category' => 'soap',
                'images' => [
                    '/images/front-box-all.png'
                ],
                'stock' => 150,
                'weight' => '125g',
                'ingredients' => ['Wild Jamun Extract', 'Cold-Pressed Neem Oil', 'Raw Shea Butter', 'Virgin Coconut Oil', 'Rosemary Essential Oil'],
                'benefits' => ['Reduces blemishes & dark spots', 'Gentle sebum control', 'Restores natural skin barrier', '100% Vegan & Palm-Oil Free'],
                'is_featured' => true,
                'is_active' => true,
                'average_rating' => 4.90,
            ],
            [
                'id' => 2,
                'name' => 'Jadui Jamun Soap (Pack of 2)',
                'slug' => 'jadui-jamun-soap-pack-2',
                'description' => 'Cold-processed botanical soap enriched with pure wild Jamun berry extract, organic cold-pressed neem seed oil, and raw unrefined shea butter for deep pore clarification.',
                'short_description' => 'Ayurvedic clarifying bar for blemish-free, radiant skin.',
                'price' => 498.00,
                'discount_price' => 349.00,
                'category' => 'soap',
                'images' => [
                    '/images/front-box-all.png'
                ],
                'stock' => 100,
                'weight' => '250g',
                'ingredients' => ['Wild Jamun Extract', 'Cold-Pressed Neem Oil', 'Raw Shea Butter', 'Virgin Coconut Oil', 'Rosemary Essential Oil'],
                'benefits' => ['Reduces blemishes & dark spots', 'Gentle sebum control', 'Restores natural skin barrier', '100% Vegan & Palm-Oil Free'],
                'is_featured' => true,
                'is_active' => true,
                'average_rating' => 4.90,
            ],
            [
                'id' => 3,
                'name' => 'Jadui Jamun Soap (Pack of 3)',
                'slug' => 'jadui-jamun-soap-pack-3',
                'description' => 'Cold-processed botanical soap enriched with pure wild Jamun berry extract, organic cold-pressed neem seed oil, and raw unrefined shea butter for deep pore clarification.',
                'short_description' => 'Ayurvedic clarifying bar for blemish-free, radiant skin.',
                'price' => 747.00,
                'discount_price' => 449.00,
                'category' => 'soap',
                'images' => [
                    '/images/front-box-all.png'
                ],
                'stock' => 75,
                'weight' => '375g',
                'ingredients' => ['Wild Jamun Extract', 'Cold-Pressed Neem Oil', 'Raw Shea Butter', 'Virgin Coconut Oil', 'Rosemary Essential Oil'],
                'benefits' => ['Reduces blemishes & dark spots', 'Gentle sebum control', 'Restores natural skin barrier', '100% Vegan & Palm-Oil Free'],
                'is_featured' => true,
                'is_active' => true,
                'average_rating' => 4.90,
            ]
        ];

        foreach ($products as $prodData) {
            $product = Product::updateOrCreate(['id' => $prodData['id']], $prodData);

            // Add sample approved review
            Review::updateOrCreate(
                ['product_id' => $product->id, 'user_name' => 'Pooja Patel'],
                [
                    'rating' => 5,
                    'comment' => 'This Jamun soap completely changed my skincare routine! Dark spots visibly faded within 2 weeks.',
                    'is_approved' => true,
                ]
            );
        }

        // 3. Create Sample Demo Order for Verification (REMOVED FOR FRESH TESTING)
        // Orders will now be empty after fresh migration.
    }
}
