<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductDescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            'GRL-OW-01' => 'Hoodie heavyweight dengan siluet boxy dan tampilan washed black. Potongan yang berkarakter memberi kesan santai untuk koleksi streetwear, sekaligus mudah dipadukan dengan celana cargo atau outerwear favorit.',
            'GRL-OW-02' => 'Shell jacket bergaya teknikal dengan tampilan minimal dan karakter utilitarian. Desain outerwear ini menjadi pilihan untuk menyusun tampilan berlapis dengan nuansa streetwear yang rapi.',
            'GRL-TP-01' => 'T-shirt oversized dengan grafis bergaya vintage dan tampilan distressed. Siluet longgar serta karakter visualnya cocok sebagai titik utama tampilan kasual atau dipadukan dengan outerwear.',
            'GRL-BT-01' => 'Celana cargo bergaya parachute dengan desain multi-pocket dan karakter taktikal. Siluet utilitarian menghadirkan tampilan yang tegas untuk dipadukan dengan tee oversized, hoodie, atau jaket teknikal.',
            'GRL-WT-01' => 'Timepiece otomatis dengan karakter desain moonphase chronograph dan ukuran nominal 41 mm. Tampilan dial yang berlapis menjadikannya pilihan koleksi dengan nuansa horologi klasik dan elegan.',
            'GRL-WT-02' => 'Timepiece bergaya diver dari koleksi Titanium Deep Diver 300M, dengan dial bernuansa obsidian. Karakter sporty dan tampilan gelapnya memberi aksen yang tegas pada koleksi horologi.',
            'GRL-AC-01' => 'Single watch winder untuk melengkapi penyimpanan satu jam tangan otomatis. Tampilan carbon fiber memberikan nuansa modern yang selaras dengan koleksi timepiece dan ruang penyimpanan pribadi.',
            'GRL-AC-02' => 'Watch strap Horween Shell Cordovan dengan lebar nominal 20 mm. Karakter aksesori kulit ini memberi sentuhan klasik pada timepiece; sesuaikan ukuran lug jam sebelum memilih strap.',
        ];
        foreach ($descriptions as $sku => $description) {
            // Enrich only the original placeholder; preserve descriptions edited by the owner.
            Product::where('sku', $sku)
                ->where('description', 'Curated collectible selected for the GRAIL.CO private archive.')
                ->update(['description' => $description]);
        }
    }
}
