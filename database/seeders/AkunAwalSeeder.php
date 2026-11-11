<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AkunAwalSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            [
                'name' => 'Kepala Sarpras',
                'email' => 'admin@laporsarana.test',
                'password' => 'Admin12345',
                'role' => 'Admin',
                'keahlian' => null,
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@laporsarana.test',
                'password' => 'Teknisi12345',
                'role' => 'Teknisi',
                'keahlian' => 'Listrik & Penerangan',
            ],
            [
                'name' => 'Agus Prasetyo',
                'email' => 'agus@laporsarana.test',
                'password' => 'Teknisi12345',
                'role' => 'Teknisi',
                'keahlian' => 'Bangunan, Air & Sanitasi',
            ],
            [
                'name' => 'Rina Wulandari',
                'email' => 'rina@laporsarana.test',
                'password' => 'Teknisi12345',
                'role' => 'Teknisi',
                'keahlian' => 'Perangkat Elektronik & Jaringan',
            ],
        ];

        foreach ($akun as $item) {
            User::updateOrCreate(
                ['email' => $item['email']],
                $item + ['aktif' => true]
            );
        }
    }
}
