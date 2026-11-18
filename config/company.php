<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pencocokan perusahaan (SIPKL TASKS M2.3)
    |--------------------------------------------------------------------------
    |
    | `similarity_threshold` adalah skor minimum (0..1) agar sebuah perusahaan
    | muncul sebagai saran. Naikkan bila saran terlalu berisik, turunkan bila
    | perusahaan mirip sering tidak terdeteksi.
    |
    */

    'similarity_threshold' => env('COMPANY_SIMILARITY_THRESHOLD', 0.72),

    'max_candidates' => env('COMPANY_MAX_CANDIDATES', 100),

    'max_suggestions' => env('COMPANY_MAX_SUGGESTIONS', 5),

    /*
    | TTL tautan verifikasi email signer industri (hari). Tautan juga terikat
    | pada email signer saat dikirim, jadi ganti email = tautan lama gugur.
    */
    'signer_link_ttl_days' => env('COMPANY_SIGNER_LINK_TTL_DAYS', 3),

];
