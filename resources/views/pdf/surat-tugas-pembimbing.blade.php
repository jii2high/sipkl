<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Tugas Pembimbing {{ $nomor_surat }}</title>
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

<p>Untuk melaksanakan bimbingan Praktik Kerja Lapangan (PKL) di:</p>

<table class="identitas">
    <tr>
        <td class="identitas-label"><strong>{{ $company['nama'] }}</strong></td>
    </tr>
    <tr>
        <td class="identitas-label">{!! nl2br(e($company['alamat'] ?? '')) !!}</td>
    </tr>
</table>

<table class="data">
    <thead>
        <tr>
            <th style="width: 7%">NO</th>
            <th style="width: 30%">NAMA SISWA PESERTA PRAKERIN</th>
            <th style="width: 30%">ALAMAT</th>
            <th style="width: 15%">KELAS</th>
            <th style="width: 18%">KETERANGAN</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($siswa as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $row['nama'] }}</td>
                <td>{{ $row['alamat'] ?? '-' }}</td>
                <td class="center">{{ $row['kelas'] ?? '-' }}</td>
                <td>{{ $row['keterangan'] ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td class="center" colspan="5">Belum ada siswa pada perusahaan ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<p>Demikian agar dilaksanakan dengan penuh tanggung jawab.</p>

@include('pdf.partials.ttd')
</body>
</html>