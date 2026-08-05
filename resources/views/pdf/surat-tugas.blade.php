<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Tugas Monitoring PKL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
            line-height: 1.4;
        }

        .kop {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .kop .nama-sekolah {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kop .alamat {
            font-size: 10pt;
            margin-top: 4px;
        }

        .judul {
            text-align: center;
            margin: 20px 0 4px;
            font-weight: bold;
            font-size: 13pt;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .nomor {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 20px;
        }

        .pembuka {
            margin-bottom: 16px;
            text-align: justify;
        }

        table.daftar {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 11pt;
        }

        table.daftar th,
        table.daftar td {
            border: 1px solid #000;
            padding: 5px 8px;
        }

        table.daftar th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }

        .penutup {
            margin-top: 16px;
            text-align: justify;
        }

        .ttd {
            margin-top: 40px;
            text-align: right;
            float: right;
            width: 220px;
        }

        .ttd .ttd-nama {
            margin-top: 70px;
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    {{-- KOP SURAT --}}
    <div class="kop">
        <div class="nama-sekolah">PEMERINTAH DAERAH PROVINSI JAWA BARAT</div>
        <div class="nama-sekolah">DINAS PENDIDIKAN</div>
        <div class="nama-sekolah">SMK NEGERI 1 KOTA BEKASI</div>
        <div class="alamat">
            Jl. Raya Kalimalang No.1, Margahayu, Kec. Bekasi Tim., Kota Bekasi, Jawa Barat 17113<br>
            Telp. (021) 8807841 | Email: info@smkn1kotabekasi.sch.id
        </div>
    </div>

    {{-- JUDUL --}}
    <div class="judul">Surat Tugas Monitoring Praktek Kerja Lapangan</div>
    <div class="nomor">Nomor: {{ $suratTugas->nomor_surat }}</div>

    {{-- PEMBUKA --}}
    <div class="pembuka">
        <p>Yang bertanda tangan di bawah ini, Kepala SMK Negeri 1 Kota Bekasi, dengan ini menugaskan
        guru pembimbing untuk melaksanakan monitoring kegiatan Praktek Kerja Lapangan (PKL)
        pada periode {{ $suratTugas->periode->nama }}.</p>
        @if ($suratTugas->lingkup === 'scoped')
            <p style="margin-top: 8px;">Cakupan: Kompetensi Keahlian <strong>{{ $suratTugas->kompetensiKeahlian?->nama ?? '-' }}</strong>.</p>
        @else
            <p style="margin-top: 8px;">Cakupan: seluruh kompetensi keahlian (global).</p>
        @endif
    </div>

    {{-- TABEL PEMBIMBING --}}
    <table class="daftar">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="28%">Nama Guru Pembimbing</th>
                <th width="28%">Nama Siswa</th>
                <th width="22%">Kompetensi Keahlian</th>
                <th width="17%">Perusahaan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suratTugas->penugasans as $i => $penugasan)
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>{{ $penugasan->guruPembimbing->name ?? '-' }}</td>
                    <td>{{ $penugasan->pengajuan->siswa->user->name ?? '-' }}</td>
                    <td>{{ $penugasan->pengajuan->siswa->kompetensiKeahlian->nama ?? '-' }}</td>
                    <td>{{ $penugasan->pengajuan->perusahaan->nama ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center">Tidak ada penugasan aktif.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PENUTUP --}}
    <div class="penutup">
        <p>Demikian surat tugas ini kami buat untuk dapat dipergunakan sebagaimana mestinya.
        Atas perhatian dan kerja sama Bapak/Ibu, kami mengucapkan terima kasih.</p>
    </div>

    {{-- TTD --}}
    <div style="margin-top: 30px; overflow: hidden;">
        <div class="ttd">
            <p>Bekasi, {{ \Carbon\Carbon::parse($suratTugas->tanggal_terbit)->translatedFormat('d F Y') }}</p>
            <p>Kepala Sekolah,</p>
            <div class="ttd-nama">
                ........................................
            </div>
            <p style="margin-top:4px; font-size: 10pt;">NIP. ........................................</p>
        </div>
    </div>
</body>
</html>
