<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Verifikasi email signer industri</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px;">
    <h2 style="color: #2456a6;">Verifikasi email signer industri</h2>

    <p>Halo {{ $nama }},</p>

    <p>
        {{ $company->nama }} menunjuk Anda sebagai
        {{ $tipe === 'pembimbing' ? 'Pembimbing Industri' : 'Kepala Industri' }}
        pada Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi.
    </p>

    <p>Klik tombol berikut untuk memverifikasi email Anda:</p>

    <p>
        <a href="{{ $url }}"
           style="display: inline-block; padding: 10px 18px; background: #2456a6; color: #ffffff; text-decoration: none; border-radius: 6px;">
            Verifikasi email saya
        </a>
    </p>

    <p style="font-size: 13px; color: #6b7280;">
        Tautan ini berlaku terbatas. Jika email Anda berubah, minta Hubin mengirim ulang verifikasi.
        Abaikan email ini bila Anda merasa tidak terkait dengan permintaan tersebut.
    </p>
</body>
</html>
