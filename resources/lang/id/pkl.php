<?php

/*
|--------------------------------------------------------------------------
| Pesan Domain PKL
|--------------------------------------------------------------------------
| Teks yang dipakai Action, Service, dan komponen UI. Angka diambil dari
| config/pkl.php, tidak ditulis ulang di sini.
*/

return [
    'email' => [
        'domain' => 'Email harus memakai domain @:domain.',
        'not_found' => 'Email atau kata sandi tidak cocok.',
    ],

    'password' => [
        'min' => 'Kata sandi minimal :min karakter.',
        'same_as_nis_nip' => 'Kata sandi tidak boleh sama dengan NIS/NIP.',
        'current_incorrect' => 'Kata sandi lama salah.',
    ],

    'otp' => [
        'sent' => 'Kode OTP sudah dikirim ke email Anda.',
        'invalid' => 'Kode OTP tidak valid.',
        'expired' => 'Kode OTP sudah kedaluwarsa. Minta kode baru.',
        'too_many_attempts' => 'Terlalu banyak percobaan. Minta kode baru.',
        'resend_cooldown' => 'Tunggu :minutes menit sebelum minta kode baru.',
    ],

    'role' => [
        'not_owned' => 'Anda tidak memiliki peran tersebut.',
        'not_active' => 'Tidak ada peran aktif yang sah untuk akses ini.',
        'none' => 'Akun ini belum memiliki peran. Hubungi Hubin.',
    ],

    'auth' => [
        'otp_verified' => 'Kode OTP terverifikasi.',
        'password_changed' => 'Kata sandi berhasil diperbarui.',
    ],

    'rejection_note' => [
        'required' => 'Catatan penolakan wajib diisi.',
        'min' => 'Catatan penolakan minimal :min karakter.',
    ],

    'quota' => [
        'exceeded' => 'Kuota :quota siswa per perusahaan per periode sudah penuh.',
    ],

    'upload' => [
        'max' => 'Ukuran berkas maksimal :max MB.',
        'mimes' => 'Berkas harus bertipe: :types.',
        'mime_mismatch' => 'Isi berkas tidak sesuai ekstensinya.',
    ],

    'status' => [
        'changed' => 'Status berubah dari :dari ke :ke.',
    ],

    'company' => [
        'duplicate' => ':Field sudah dipakai perusahaan ":nama".',
        'similar_name' => 'Nama mirip dengan ":nama". Periksa apakah perusahaan yang sama.',
        'similar_address' => 'Alamat mirip dengan ":nama". Periksa apakah perusahaan yang sama.',
        'signer_generic' => 'Jabatan generik tidak boleh dipakai sebagai nama signer; isi nama individu.',
        'verified_requires_contact' => 'Email terverifikasi hanya boleh ditandai bila nama dan email signer sudah diisi.',
        'field' => [
            'email' => 'Email',
            'website' => 'Website',
            'telp_fax' => 'Telepon/faks',
        ],
        'table' => [
            'pengajuan_pkl' => 'company group pengajuan',
            'pengajuan_siswa' => 'pengajuan siswa',
            'guru_pembimbing_plotting' => 'plotting guru pembimbing',
            'surat_tugas_visitasi_companies' => 'perusahaan pada surat tugas visitasi',
            'visitasi_dokumentasi' => 'dokumentasi visitasi',
            'form_submissions' => 'pengisian form',
        ],
    ],

    'import' => [
        'summary' => 'Impor selesai: :imported dari :total baris masuk, :failed baris bermasalah.',
        'failure_line' => 'Baris :row: :messages',
        'row_rejected' => 'Baris gagal disimpan (duplikat atau data tidak sah).',
        'hint' => 'Berkas CSV/XLSX dengan baris pertama sebagai judul kolom. Setiap baris diproses sendiri; baris gagal dilaporkan tanpa membatalkan baris lain.',
    ],

    'period' => [
        'delete_blocked' => 'Periode tidak dapat dihapus karena masih punya pengajuan terkait.',
        'tahun_ajaran_format' => 'Format tahun ajaran harus 2025/2026.',
    ],

    'account' => [
        'otp_reset' => 'Akses OTP akun :nama sudah direset.',
        'activated' => 'Akun :nama diaktifkan.',
        'deactivated' => 'Akun :nama dinonaktifkan.',
    ],

    'signature' => [
        'uploaded' => 'Scan TTD Kepsek tersimpan dan aktif.',
        'hint' => 'Mengunggah scan baru akan menonaktifkan scan lama. Berkas disimpan privat.',
    ],

    'document' => [
        'held_no_signature' => 'Penerbitan surat ditahan: scan TTD Kepala Sekolah belum tersedia.',
        'issued' => 'Terbit',
        'kepsek_jabatan' => 'Kepala SMK Negeri 1 Kota Bekasi',
        'jabatan_pembimbing' => 'Guru Pembimbing',
        'verify' => [
            'judul' => 'Verifikasi surat',
            'petunjuk' => 'Pindai QR pada surat atau buka tautannya. Halaman ini hanya menampilkan nomor surat, status, dan tanggal terbit.',
            'nomor' => 'Nomor surat',
            'status' => 'Status',
            'tanggal' => 'Tanggal terbit',
            'tidak_ditemukan' => 'Dokumen tidak ditemukan atau tautan verifikasi tidak berlaku. Periksa kembali tautan pada QR surat, atau hubungi Hubin sekolah.',
            'kembali' => 'Kembali ke beranda',
        ],
    ],
];
