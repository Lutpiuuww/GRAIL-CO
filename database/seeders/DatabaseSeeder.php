<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'GRAIL Owner', 'email' => 'admin@grail.co', 'role' => 'admin'],
            ['name' => 'Warehouse Staff', 'email' => 'staff@grail.co', 'role' => 'staff'],
            ['name' => 'GRAIL Collector', 'email' => 'customer@grail.co', 'role' => 'customer'],
        ] as $user) {
            User::firstOrCreate(['email' => $user['email']], $user + ['password' => 'password']);
        }

        $categories = collect([
            'Heavyweight Outerwear', 'Archive Tops & Tees', 'Tactical & Utility Pants',
            'Automatic & Mechanical Watches', 'Watch Winder & Accessories',
        ])->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['name' => $name])]);

        foreach ([
            ['Tokyo Fabric & Garment Co.', '+81 3 5550 1122', 'trade@tokyofabric.jp'],
            ['Milano Archive Textile', '+39 02 5550 2233', 'archive@milano-textile.it'],
            ['Geneva Horology Precision Ltd.', '+41 22 555 33 44', 'vault@genevahorology.ch'],
            ['Kyoto Leather & Craft Studio', '+81 75 555 4455', 'craft@kyotoleather.jp'],
        ] as [$name, $phone, $email]) {
            Supplier::firstOrCreate(['name' => $name], compact('phone', 'email'));
        }

        foreach ([
            ['Heavyweight Outerwear', 'GRL-OW-01', 'Heavyweight Boxy Hoodie Washed Black', 850000, 1850000, 12, 4],
            ['Heavyweight Outerwear', 'GRL-OW-02', 'Technical Waterproof Shell Jacket', 1200000, 2750000, 2, 5],
            ['Archive Tops & Tees', 'GRL-TP-01', 'Vintage Distressed Graphic Oversized Tee', 350000, 750000, 20, 5],
            ['Tactical & Utility Pants', 'GRL-BT-01', 'Parachute Multi-Pocket Tactical Cargo', 900000, 1950000, 15, 5],
            ['Automatic & Mechanical Watches', 'GRL-WT-01', 'Moonphase Chronograph Automatic 41mm', 6500000, 12500000, 3, 2],
            ['Automatic & Mechanical Watches', 'GRL-WT-02', 'Titanium Deep Diver 300M Obsidian Dial', 4800000, 9200000, 1, 3],
            ['Watch Winder & Accessories', 'GRL-AC-01', 'Carbon Fiber Automatic Single Watch Winder', 1100000, 2400000, 8, 3],
            ['Watch Winder & Accessories', 'GRL-AC-02', 'Horween Shell Cordovan Watch Strap 20mm', 400000, 950000, 18, 4],
        ] as [$category, $sku, $name, $buy, $sell, $stock, $minimum]) {
            Product::withTrashed()->firstOrCreate(['sku' => $sku], [
                'category_id' => $categories[$category]->id, 'name' => $name,
                'description' => 'Curated collectible selected for the GRAIL.CO private archive.',
                'purchase_price' => $buy, 'selling_price' => $sell, 'stock' => $stock, 'minimum_stock' => $minimum,
            ]);
        }
        $this->call(ProductDescriptionSeeder::class);
    }
}
