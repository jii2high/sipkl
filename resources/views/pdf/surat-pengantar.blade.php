<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Pengantar {{ $nomor_surat }}</title>
    <style>@include('pdf.partials.style')</style>
</head>
<body>
@include('pdf.partials.kop')

<table class="meta">
    <tr>
        <td class="meta-label">Nomor</td>
        <td class="meta-titik">:</td>
        <td>{{ $nomor_surat }}</td>
        <td class="meta-kanan">{{ $kop['kota'] }}, {{ $tanggal }}</td>
    </tr>
    <tr>
        <td class="meta-label">Lampiran</td>
        <td class="meta-titik">:</td>
        <td>-</td>
        <td class="meta-kanan"></td>
    </tr>
    <tr>
        <td class="meta-label">Perihal</td>
        <td class="meta-titik">:</td>
        <td colspan="2">Permohonan Praktik Kerja Lapangan (PKL)</td>
    </tr>
</table>

<div class="tujuan">
    Kepada Yth:<br>
    <strong>{{ $company['nama'] }}</strong><br>
    {!! nl2br(e($company['alamat'] ?? '')) !!}
</div>

<p>
    Dalam rangka pelaksanaan Pendidikan Sistem Ganda untuk lebih meningkatkan keterampilan
    lulusan Sekolah Menengah Kejuruan, siswa/siswi SMK Negeri 1 Kota Bekasi diwajibkan
    melaksanakan Praktik Kerja Industri di Perusahaan/&#8203;Industri/&#8203;Instansi/&#8203;Lembaga.
</p>
<p>
    Sehubungan dengan hal tersebut kami mengajukan permohonan kepada Bapak/Ibu untuk dapat
    memberikan kesempatan kepada siswa/siswi kami melaksanakan Praktik Kerja Industri di
    Perusahaan/Industri/Instansi yang Bapak/Ibu pimpin, yaitu:
</p>

<table class="data">
    <thead>
        <tr>
            <th style="width: 7%">NO</th>
            <th style="width: 28%">NAMA</th>
            <th style="width: 16%">NIS</th>
            <th style="width: 25%">KOMP. KEAHLIAN</th>
            <th style="width: 24%">KET</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($siswa as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row['nama'] }}</td>
                <td class="center">{{ $row['nis'] }}</td>
                <td>{{ $row['komp_keahlian'] ?? '-' }}</td>
                <td>{{ $row['keterangan'] ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td class="center" colspan="5">Belum ada siswa pada kelompok ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($periode !== null)
    <p>
        Rencana pelaksanaan pada {{ $periode['mulai'] }} s.d. {{ $periode['selesai'] }} dengan
        menyesuaikan program yang ada di perusahaan Bapak/Ibu.
    </p>
@endif

<p>
    Demikian permohonan ini kami sampaikan, atas perhatian dan kerja samanya kami ucapkan
    terima kasih.
</p>

@include('pdf.partials.ttd')
</body>
</html>