/*
 * Gaya bersama tiga template surat (dompdf). Ukuran memakai pt agar konsisten
 * di A4; tidak ada flex/grid karena dompdf hanya mendukung layout tabel.
 */
@page {
    margin: 1.2cm 2cm 1.2cm 2cm;
}

body {
    font-family: "DejaVu Serif", serif;
    font-size: 10.5pt;
    line-height: 1.4;
    color: #000000;
}

.kop {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
}

.kop-baris {
    font-size: 10.5pt;
    line-height: 1.25;
}

.kop-nama {
    font-size: 15pt;
    font-weight: bold;
    letter-spacing: 0.4pt;
    line-height: 1.3;
}

.kop-alamat,
.kop-kontak {
    font-size: 8.5pt;
    line-height: 1.3;
}

.kop-garis {
    border-top: 3px solid #000000;
    border-bottom: 1px solid #000000;
    height: 2px;
    margin: 4pt 0 12pt;
}

table.meta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10pt;
}

table.meta td {
    vertical-align: top;
    padding: 0 0 1pt;
}

.meta-label {
    width: 62pt;
}

.meta-titik {
    width: 8pt;
}

.meta-kanan {
    text-align: right;
    width: 40%;
}

.tujuan {
    margin: 0 0 14pt 12pt;
    line-height: 1.5;
}

p {
    margin: 0 0 8pt;
    text-align: justify;
}

table.data {
    width: 100%;
    border-collapse: collapse;
    margin: 6pt 0 12pt;
}

table.data th,
table.data td {
    border: 1px solid #000000;
    padding: 3pt 4pt;
    vertical-align: top;
    font-size: 9.5pt;
}

table.data th {
    text-align: center;
    font-weight: bold;
}

.center {
    text-align: center;
}

.judul {
    text-align: center;
    margin: 2pt 0 10pt;
}

.judul-nama {
    font-size: 13pt;
    font-weight: bold;
    text-decoration: underline;
    letter-spacing: 0.4pt;
}

.judul-nomor {
    font-size: 10.5pt;
    font-weight: bold;
}

.identitas {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 12pt 12pt;
}

.identitas td {
    vertical-align: top;
    padding: 1pt 0;
}

.identitas-label {
    width: 120pt;
}

table.ttd {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12pt;
    page-break-inside: avoid;
}

table.ttd td {
    vertical-align: top;
}

.ttd-qr {
    width: 110pt;
    text-align: left;
}

.ttd-qr img {
    width: 78pt;
    height: 78pt;
}

.qr-caption {
    font-size: 7.5pt;
    color: #333333;
}

.ttd-sign {
    text-align: center;
    width: 55%;
}

.ttd-space {
    height: 48pt;
}

.ttd-space img {
    height: 44pt;
}

.ttd-nama {
    font-weight: bold;
    text-decoration: underline;
}

.lembar-isi {
    margin-top: 2pt;
}