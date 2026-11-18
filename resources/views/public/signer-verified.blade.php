<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi signer — SIPKL</title>
</head>
<body style="margin: 0; background: #f3f4f6;">
    <main style="max-width: 640px; margin: 48px auto; padding: 24px; background: #ffffff; border-radius: 12px; font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
        <h1 style="color: #2456a6;">Email berhasil diverifikasi</h1>

        <p>
            Terima kasih, <strong>{{ $nama }}</strong>. Email Anda tercatat sebagai
            {{ $tipe === 'pembimbing' ? 'Pembimbing Industri' : 'Kepala Industri' }}
            untuk <strong>{{ $perusahaan }}</strong>.
        </p>

        <p>Anda tidak perlu melakukan langkah lain. Hubin akan menghubungi Anda bila dokumen perlu ditandatangani.</p>
    </main>
</body>
</html>
