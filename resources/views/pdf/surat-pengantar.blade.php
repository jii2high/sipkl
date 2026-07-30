<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pengantar PKL</title>
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

        table.siswa {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 11pt;
        }

        table.siswa th,
        table.siswa td {
            border: 1px solid #000;
            padding: 5px 8px;
        }

        table.siswa th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }

        table.siswa td {
            text-align: left;
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

        .warning-kuota {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 6px 10px;
            margin: 10px 0;
            font-size: 10pt;
            color: #856404;
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
    <div class="judul">Surat Pengantar Praktek Kerja Lapangan</div>
    <div class="nomor">Nomor: {{ $suratPengantar->nomor_surat }}</div>

    {{-- PEMBUKA --}}
    <div class="pembuka">
        <p>Yang bertanda tangan di bawah ini, Kepala SMK Negeri 1 Kota Bekasi, dengan ini menerangkan bahwa
        siswa-siswa tersebut di bawah ini adalah benar siswa SMK Negeri 1 Kota Bekasi yang sedang melaksanakan
        Praktek Kerja Lapangan (PKL):</p>
    </div>

    {{-- TABEL SISWA --}}
    <table class="siswa">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Nama Siswa</th>
                <th width="15%">NIS</th>
                <th width="25%">Kompetensi Keahlian</th>
                <th width="15%">Kelas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($suratPengantar->pengajuans as $i => $pengajuan)
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>{{ $pengajuan->siswa->user->name ?? '-' }}</td>
                    <td>{{ $pengajuan->siswa->nis ?? '-' }}</td>
                    <td>{{ $pengajuan->siswa->kompetensiKeahlian->nama ?? '-' }}</td>
                    <td>{{ $pengajuan->siswa->kelas ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- PERUSAHAAN --}}
    <div class="pembuka">
        <p>Siswa tersebut di atas ditempatkan untuk melaksanakan Praktek Kerja Lapangan di:</p>
        <table style="margin-top: 8px; font-size: 11pt;">
            <tr>
                <td width="140">Nama Perusahaan</td>
                <td width="10">:</td>
                <td><strong>{{ $suratPengantar->perusahaan->nama }}</strong></td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>:</td>
                <td>{{ $suratPengantar->perusahaan->alamat ?? '-' }}</td>
            </tr>
            <tr>
                <td>Periode PKL</td>
                <td>:</td>
                <td>{{ $suratPengantar->periode->nama }}</td>
            </tr>
        </table>
    </div>

    {{-- PENUTUP --}}
    <div class="penutup">
        <p>Demikian surat pengantar ini kami buat untuk dapat dipergunakan sebagaimana mestinya.
        Atas perhatian dan kerja sama Bapak/Ibu, kami mengucapkan terima kasih.</p>
    </div>

    {{-- TTD --}}
    <div style="margin-top: 30px; overflow: hidden;">
        <div class="ttd">
            <p>Bekasi, {{ \Carbon\Carbon::parse($suratPengantar->tanggal_terbit)->translatedFormat('d F Y') }}</p>
            <p>Kepala Sekolah,</p>
            <div class="ttd-nama">
                ........................................
            </div>
            <p style="margin-top:4px; font-size: 10pt;">NIP. ........................................</p>
        </div>
    </div>
</body>
</html>
