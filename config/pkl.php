<?php

/*
|--------------------------------------------------------------------------
| Konstanta Bisnis PKL
|--------------------------------------------------------------------------
| Semua angka bisnis dari PRD dipusatkan di sini (TRD.md §4) agar tidak
| tersebar sebagai magic number di Action/Service/UI.
*/

return [
    'school' => [
        'name' => 'SMKN 1 Kota Bekasi',
        'email_domain' => env('PKL_EMAIL_DOMAIN', 'smkn1kotabekasi.sch.id'),

        // Kop surat PDF (F12). Isi mengikuti contoh di
        // `docs/old physical dokument examples/surat_pengantar.pdf`.
        'kop' => [
            'baris1' => 'PEMERINTAH DAERAH PROVINSI JAWA BARAT',
            'baris2' => 'DINAS PENDIDIKAN',
            'baris3' => 'CABANG DINAS PENDIDIKAN WILAYAH III',
            'nama' => 'SMK NEGERI 1 KOTA BEKASI',
            'alamat' => 'Jl. Bintara VIII No.2 Kec. Bekasi Barat 17134 Telp./Fax. (021) 88951151/(021) 8851383',
            'kontak' => 'Website: http://www.smkn1kotabekasi.sch.id  e-mail: info@smkn1kotabekasi.sch.id',
            'kota' => 'Bekasi',
        ],
    ],

    'timezone' => [
        'application' => env('APP_TIMEZONE', 'UTC'),
        'operational' => 'Asia/Jakarta',
    ],

    // Kuota maksimum siswa `sedang_pkl` (provisional ikut dihitung) per company+periode.
    'quota_per_company_period' => 6,

    'password' => [
        'min_length' => 8,
    ],

    // Catatan penolakan (Kaprog/Walas/step TTD/visitasi) minimal 10 karakter.
    'rejection_note' => [
        'min_length' => 10,
    ],

    'otp' => [
        'internal' => [
            'length' => 6,
            'ttl_seconds' => 60,
            'max_verify_attempts' => 3,
            'resend_max' => 5,
            'resend_window_minutes' => 30,
            'resend_cooldown_minutes' => 30,
        ],
        'external' => [
            'length' => 6,
            'ttl_minutes' => 5,
            'max_attempts' => 3,
            'session_hours' => 24,
        ],
    ],

    'bimbingan' => [
        'min_interactions' => 4,
        'upload' => [
            'max_mb' => 10,
            'extensions' => ['pdf', 'doc', 'docx'],
            'mimes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ],
    ],

    'visitasi' => [
        'default_validity_days' => 7,
        'upload' => [
            'max_mb' => 5,
            'extensions' => ['jpg', 'jpeg', 'png'],
            'mimes' => ['image/jpeg', 'image/png'],
        ],
    ],

    // Penilai duplikat perusahaan (BR-F2-1/2). Jarak Levenshtein maksimum dan
    // persentase kemiripan minimum untuk peringatan nama/alamat.
    'company' => [
        'similarity_distance_max' => 3,
        'similarity_ratio_min' => 85,
    ],

    // Impor massal (T2.1/T2.2): batas baris per berkas agar laporan tetap terbaca.
    'import' => [
        'max_rows' => 2000,
    ],

    // Scan TTD Kepsek (BR-F2-5).
    'signature' => [
        'max_mb' => 5,
        'extensions' => ['png', 'jpg', 'jpeg', 'pdf'],
        'mimes' => ['image/png', 'image/jpeg', 'application/pdf'],
    ],

    // Surat balasan perusahaan (BR-F3-4). Jenis berkasnya tidak disebut TRD §4;
    // dipilih yang paling lazim untuk dokumen jawaban (PDF atau pindaian).
    'surat_balasan' => [
        'max_mb' => 5,
        'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
        'mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
    ],

    // Signer industri (BR-F2-3): jabatan generik tidak boleh menerima OTP.
    'signer' => [
        'generic_tokens' => [
            'hrd', 'hr', 'pic', 'admin', 'administrasi', 'kabag', 'staff', 'staf',
            'personalia', 'bagian', 'divisi', 'departemen', 'manager', 'manajer',
            'supervisor', 'koordinator', 'sekretaris', 'resepsionis', 'satpam',
            'security', 'operator', 'pemilik', 'owner', 'umum', 'marketing', 'pemasaran',
        ],
    ],

    'magic_link' => [
        'min_bytes' => 32,
        'ttl_days' => 7,
    ],

    'examiner' => [
        'pin_digits' => 4,
        'pin_max_attempts' => 3,
    ],

    'score_unlock' => [
        'ttl_hours' => 24,
    ],

    'industry_assessment' => [
        'window_months_before_end' => 1,
        'reminder_days_before_end' => 7,
    ],

    'export' => [
        'chunk_size' => 500,
        'retention_days' => 30,
        'target_seconds' => 300,
    ],

    'faq' => [
        'per_page' => 10,
    ],

    // Surat & dokumen (F12). Nomor urut diambil dari `nomor_surat_counters` per
    // (tahun ajaran, jenis); format: [kode]/[urut 4 digit]/[suffix].
    'document' => [
        'nomor' => [
            'suffix' => 'SMKN.1-CADISDIK WIL 3',
            'kode' => [
                'surat_pengantar' => '421.5',
                'surat_tugas_pembimbing' => 'KPG.11.01',
                'surat_tugas_visitasi' => 'KPV.11.01',
            ],
        ],
        // Rate limit halaman verifikasi publik `/verify/{token}` (TRD.md §16).
        'verify_throttle' => '60,1',
    ],

    'mail' => [
        'retry_max' => 3,
        // Di lingkungan `local`, semua email dialihkan ke alamat ini agar OTP dan
        // notifikasi lain dapat diuji tanpa domain sekolah. Kosongkan untuk mematikan.
        'override_to' => env('PKL_MAIL_OVERRIDE_TO'),
    ],
];
