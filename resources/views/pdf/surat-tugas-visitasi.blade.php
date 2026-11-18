<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Tugas Visitasi {{ $nomor_surat }}</title>
    <style>@include('pdf.partials.style')</style>
</head>
<body>
@include('pdf.partials.kop')

<div class="judul">
    <div class="judul-nama">SURAT TUGAS</div>
    <div class="judul-nomor">Nomor: {{ $nomor_surat }}</div>
</div>

<p>
    Kepala Sekolah Menengah Kejuruan (SMK) Negeri 1 Kota Bekasi menugaskan kepada:
</p>

<table class="identitas">
    <tr>
        <td class="identitas-label">NAMA</td>
        <td>: {{ $guru['nama'] }}</td>
    </tr>
    <tr>
        <td class="identitas-label">NIP/NIGB</td>
        <td>: {{ $guru['nip'] }}</td>
    </tr>
    <tr>
        <td class="identitas-label">Pangkat/Golongan</td>
        <td>: -</td>
    </tr>
    <tr>
        <td class="identitas-label">Jabatan</td>
        <td>: {{ __('pkl.document.jabatan_pembimbing') }}</td>
    </tr>
</table>

<p>Untuk melaksanakan kunjungan (visitasi) Praktik Kerja Lapangan (PKL) pada:</p>

@if ($is_general)
    <table class="identitas">
        <tr>
            <td>Seluruh perusahaan yang menjadi bimbingan guru tersebut.</td>
        </tr>
    </table>
@else
    <table class="data">
        <thead>
            <tr>
                <th style="width: 7%">NO</th>
                <th style="width: 38%">NAMA PERUSAHAAN</th>
                <th style="width: 55%">ALAMAT</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($companies as $index => $row)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ $row['alamat'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="3">Belum ada perusahaan pada surat tugas ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endif

<p>
    Surat tugas ini berlaku mulai {{ $masa_berlaku['mulai'] }} sampai dengan
    {{ $masa_berlaku['selesai'] }}.
</p>

<p>Demikian agar dilaksanakan dengan penuh tanggung jawab.</p>

@include('pdf.partials.ttd')
</body>
</html>