<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = config('katalog.admin_password');
        $userPassword = config('katalog.user_password');

        if (
            ! is_string($adminPassword) || mb_strlen($adminPassword) < 12 ||
            ! is_string($userPassword) || mb_strlen($userPassword) < 12
        ) {
            throw new RuntimeException(
                'Atur KATALOG_ADMIN_PASSWORD dan KATALOG_USER_PASSWORD '
                .'(minimal 12 karakter) pada file .env sebelum menjalankan seeder.'
            );
        }

        // Membuat akun Admin
        User::firstOrCreate(
            ['email' => 'admin@pustakajaringan.test'],
            [
                'name' => 'Admin Praktikum',
                'password' => Hash::make($adminPassword),
                'is_admin' => true,
            ]
        );

        // Membuat akun mahasiswa
        User::firstOrCreate(
            ['email' => 'mahasiswa@pustakajaringan.test'],
            [
                'name' => 'Mahasiswa Praktikum',
                'password' => Hash::make($userPassword),
                'is_admin' => false,
            ]
        );

        // Data buku latihan fiktif
        $books = [
            [
                'kode' => 'LAT-NET-001',
                'judul' => 'Modul Latihan: Pengantar Jaringan Komputer',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2023,
                'kategori' => ['Dasar Jaringan'],
                'stok' => 8,
            ],
            [
                'kode' => 'LAT-NET-002',
                'judul' => 'Modul Latihan: Protokol TCP/IP dan IPv4',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2024,
                'kategori' => ['Dasar Jaringan', 'Protokol'],
                'stok' => 6,
            ],
            [
                'kode' => 'LAT-NET-003',
                'judul' => 'Modul Latihan: Subnetting dan VLSM',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2024,
                'kategori' => ['Dasar Jaringan', 'Routing'],
                'stok' => 4,
            ],
            [
                'kode' => 'LAT-NET-004',
                'judul' => 'Modul Latihan: Routing Statis dan Dinamis',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2024,
                'kategori' => ['Routing'],
                'stok' => 5,
            ],
            [
                'kode' => 'LAT-NET-005',
                'judul' => 'Modul Latihan: Switching dan VLAN',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2025,
                'kategori' => ['Switching'],
                'stok' => 7,
            ],
            [
                'kode' => 'LAT-NET-006',
                'judul' => 'Modul Latihan: DNS dan DHCP',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2023,
                'kategori' => ['Protokol'],
                'stok' => 9,
            ],
            [
                'kode' => 'LAT-NET-007',
                'judul' => 'Modul Latihan: Administrasi Jaringan Linux',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2025,
                'kategori' => ['Administrasi Jaringan'],
                'stok' => 3,
            ],
            [
                'kode' => 'LAT-NET-008',
                'judul' => 'Modul Latihan: Keamanan Jaringan dan Firewall',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2025,
                'kategori' => ['Keamanan Jaringan'],
                'stok' => 2,
            ],
            [
                'kode' => 'LAT-NET-009',
                'judul' => 'Modul Latihan: Jaringan Nirkabel dan Wi-Fi',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2024,
                'kategori' => ['Nirkabel'],
                'stok' => 6,
            ],
            [
                'kode' => 'LAT-NET-010',
                'judul' => 'Modul Latihan: Troubleshooting dan Monitoring Jaringan',
                'penulis' => ['Tim Praktikum Jaringan'],
                'tahun' => 2025,
                'kategori' => [
                    'Administrasi Jaringan',
                    'Keamanan Jaringan',
                ],
                'stok' => 5,
            ],
        ];

        // Memasukkan data tanpa menggandakan buku
        foreach ($books as $book) {
            Book::firstOrCreate(
                ['kode' => $book['kode']],
                $book + [
                    'open_library_key' => null,
                    'cover_id' => null,
                ]
            );
        }

        $this->command?->info(
            'Seeder selesai: akun latihan dan 10 buku fiktif tersedia.'
        );
    }
}
