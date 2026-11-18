<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('user_type', 'hubin')->first();

        if ($admin === null) {
            return;
        }

        $isi = [
            'Pengajuan' => [
                ['Bagaimana cara mengajukan tempat PKL?', 'Isi form pengajuan, pilih perusahaan, lalu kirim untuk diverifikasi Kaprog dan Walas.'],
                ['Berapa siswa yang diterima satu perusahaan?', 'Maksimal 6 siswa aktif per perusahaan per periode.'],
            ],
            'Akun' => [
                ['Apa kata sandi awal saya?', 'Kata sandi awal sama dengan NIS/NIP Anda. Ganti saat login pertama.'],
                ['Saya lupa kata sandi, apa yang harus dilakukan?', 'Hubungi Hubin untuk reset melalui pengaturan akun.'],
            ],
        ];

        $urutanKategori = 0;

        foreach ($isi as $kategori => $faqs) {
            $category = FaqCategory::query()->firstOrCreate(
                ['nama' => $kategori],
                ['sort_order' => $urutanKategori],
            );

            foreach ($faqs as $index => [$pertanyaan, $jawaban]) {
                Faq::query()->firstOrCreate(
                    ['faq_category_id' => $category->id, 'pertanyaan' => $pertanyaan],
                    ['jawaban' => $jawaban, 'sort_order' => $index, 'is_published' => true, 'created_by' => $admin->id],
                );
            }

            $urutanKategori++;
        }
    }
}
