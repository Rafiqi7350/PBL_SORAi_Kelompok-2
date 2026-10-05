<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $umkmUser = User::firstOrCreate(
            ['email' => 'umkm@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'umkm', 'status' => 'active']
        );
        $supplierUser = User::firstOrCreate(
            ['email' => 'supplier@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'supplier', 'status' => 'active']
        );

        $umkm = UmkmProfile::firstOrCreate(
            ['user_id' => $umkmUser->id],
            ['business_name' => 'Resto Ayam Bakar Budi', 'owner_name' => 'Budi', 'phone' => '0811223344', 'address' => 'Jl. Merdeka No. 1']
        );

        $supplier = SupplierProfile::firstOrCreate(
            ['user_id' => $supplierUser->id],
            ['company_name' => 'PT Tani Poultry Makmur', 'owner_name' => 'Tani', 'phone' => '0899887766', 'warehouse_address' => 'Pergudangan Tani Jaya, Blok A1']
        );

        $commodity = Commodity::firstOrCreate(
            ['name' => 'Daging Ayam Broiler Segar'],
            ['category' => 'Daging & Unggas', 'unit' => 'kg', 'government_ref_price' => 35500.00]
        );
        
        $commodity2 = Commodity::firstOrCreate(
            ['name' => 'Bawang Merah Biru Probolinggo'],
            ['category' => 'Bumbu & Rempah', 'unit' => 'kg', 'government_ref_price' => 28000.00]
        );

        SupplierProduct::firstOrCreate(
            ['supplier_id' => $supplier->id, 'commodity_id' => $commodity->id],
            [
                'price' => 34000.00, 
                'stock_capacity' => '500 kg/minggu', 
                'stock_status' => 'Tersedia',
                'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&q=80&w=400' // Ayam Broiler
            ]
        );
        
        SupplierProduct::firstOrCreate(
            ['supplier_id' => $supplier->id, 'commodity_id' => $commodity2->id],
            [
                'price' => 28500.00, 
                'stock_capacity' => '1.200 kg/minggu', 
                'stock_status' => 'Tersedia',
                'image_url' => 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?auto=format&fit=crop&q=80&w=400' // Bawang Merah
            ]
        );
    }
}
