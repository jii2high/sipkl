import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Send } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard as guruPembimbingDashboard } from '@/routes/guru-pembimbing';
import {
    index as penilaianIndex,
    store as penilaianStore,
} from '@/routes/guru-pembimbing/penilaian';

function predikatDari(nilai: string): string | null {
    const n = parseFloat(nilai);

    if (Number.isNaN(n)) {
        return null;
    }

    if (n < 70) {
        return 'K';
    }

    if (n <= 80) {
        return 'C';
    }

    if (n <= 90) {
        return 'B';
    }

    return 'SB';
}

type Leaf = {
    key: string;
    label: string;
    kategori: string;
    indikator_penilaian_id: number | null;
    section_penilaian_id: number | null;
};

export default function FormPenilaian({
    penugasan,
    indikatorUmum,
    indikatorTeknis,
}: any) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const { data, setData, errors, processing } = useForm({
        sakit: '0',
        izin: '0',
        alpa: '0',
        catatan_guru_pembimbing: '',
        nama_pembimbing_industri: '',
        nilai: {} as Record<string, string>,
    });

    const allLeaves = useMemo<Leaf[]>(() => {
        const hasil: Leaf[] = [];

        function ambilIndikator(daftar: any[], kategori: string) {
            daftar.forEach((ind: any) => {
                if (ind.sections?.length) {
                    ind.sections.forEach((sec: any) => {
                        hasil.push({
                            key: `sec:${sec.id}`,
                            label: `${ind.deskripsi} — ${sec.deskripsi}`,
                            kategori,
                            indikator_penilaian_id: null,
                            section_penilaian_id: sec.id,
                        });
                    });
                } else {
                    hasil.push({
                        key: `ind:${ind.id}`,
                        label: ind.deskripsi,
                        kategori,
                        indikator_penilaian_id: ind.id,
                        section_penilaian_id: null,
                    });
                }
            });
        }

        ambilIndikator(indikatorUmum, 'umum');
        ambilIndikator(indikatorTeknis, 'teknis_kejuruan');

        return hasil;
    }, [indikatorUmum, indikatorTeknis]);

    const kosong = allLeaves.filter(
        (leaf) => !data.nilai[leaf.key] || data.nilai[leaf.key] === '',
    );

    const terisiPerKategori = useMemo(() => {
        const hitung: Record<string, number> = {};

        allLeaves.forEach((leaf) => {
            if (data.nilai[leaf.key] && data.nilai[leaf.key] !== '') {
                hitung[leaf.kategori] = (hitung[leaf.kategori] || 0) + 1;
            }
        });

        return hitung;
    }, [data.nilai, allLeaves]);

    function bukaKonfirmasi() {
        setConfirmOpen(true);
    }

    function submit() {
        const payload = allLeaves.map((leaf) => ({
            indikator_penilaian_id: leaf.indikator_penilaian_id,
            section_penilaian_id: leaf.section_penilaian_id,
            nilai: data.nilai[leaf.key] || null,
        }));

        router.post(penilaianStore({ penugasan: penugasan.id }).url, {
            sakit: data.sakit,
            izin: data.izin,
            alpa: data.alpa,
            catatan_guru_pembimbing: data.catatan_guru_pembimbing,
            nama_pembimbing_industri: data.nama_pembimbing_industri,
            nilai: payload,
        });
    }

    function renderIndikator(daftar: any[], kategori: string) {
        if (daftar.length === 0) {
            return null;
        }

        return (
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        {kategori === 'umum'
                            ? 'Aspek Umum'
                            : 'Aspek Teknis Kejuruan'}
                        <Badge
                            className={`ml-2 ${terisiPerKategori[kategori] > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}
                        >
                            {terisiPerKategori[kategori] || 0} terisi
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {daftar.map((ind: any) => (
                        <div key={ind.id}>
                            <p className="mb-1 text-sm font-medium">
                                {ind.nomor_urut}. {ind.deskripsi}
                            </p>
                            {ind.sections?.length ? (
                                <div className="space-y-2 pl-4">
                                    {ind.sections.map((sec: any) => (
                                        <LeafInput
                                            key={`sec:${sec.id}`}
                                            label={sec.deskripsi}
                                            value={
                                                data.nilai[`sec:${sec.id}`]
                                            }
                                            onChange={(v) =>
                                                setData('nilai', {
                                                    ...data.nilai,
                                                    [`sec:${sec.id}`]: v,
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                            ) : (
                                <LeafInput
                                    label="Nilai"
                                    value={data.nilai[`ind:${ind.id}`]}
                                    onChange={(v) =>
                                        setData('nilai', {
                                            ...data.nilai,
                                            [`ind:${ind.id}`]: v,
                                        })
                                    }
                                />
                            )}
                        </div>
                    ))}
                </CardContent>
            </Card>
        );
    }

    return (
        <>
            <Head title="Formulir Penilaian" />

            <div className="mx-auto max-w-3xl space-y-6">
                <div>
                    <h2 className="text-2xl font-semibold">
                        Formulir Penilaian PKL
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {penugasan.pengajuan?.siswa?.user?.name} —{' '}
                        {penugasan.pengajuan?.perusahaan?.nama}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Kehadiran & Catatan (BR29)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-3 gap-3">
                            {[
                                { key: 'sakit', label: 'Sakit (hari)' },
                                { key: 'izin', label: 'Izin (hari)' },
                                { key: 'alpa', label: 'Alpa (hari)' },
                            ].map((f) => (
                                <div key={f.key} className="space-y-2">
                                    <Label htmlFor={f.key}>{f.label}</Label>
                                    <Input
                                        id={f.key}
                                        type="number"
                                        min={0}
                                        value={data[f.key as 'sakit']}
                                        onChange={(e) =>
                                            setData(
                                                f.key as 'sakit',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="nama_pembimbing_industri">
                                Nama Pembimbing Industri
                            </Label>
                            <Input
                                id="nama_pembimbing_industri"
                                value={data.nama_pembimbing_industri}
                                onChange={(e) =>
                                    setData(
                                        'nama_pembimbing_industri',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="catatan">
                                Catatan Guru Pembimbing
                            </Label>
                            <Textarea
                                id="catatan"
                                value={data.catatan_guru_pembimbing}
                                onChange={(e) =>
                                    setData(
                                        'catatan_guru_pembimbing',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </CardContent>
                </Card>

                {renderIndikator(indikatorUmum, 'umum')}
                {renderIndikator(indikatorTeknis, 'teknis_kejuruan')}

                {errors.nilai && (
                    <p className="text-sm text-red-500">{errors.nilai}</p>
                )}

                <Button onClick={bukaKonfirmasi} className="w-full" size="lg">
                    <Send className="mr-1 size-4" />
                    Submit Penilaian
                </Button>
            </div>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="size-5 text-yellow-600" />
                            Konfirmasi Submit Penilaian
                        </DialogTitle>
                        <DialogDescription>
                            Penilaian bersifat final setelah disubmit (BR31).
                            {kosong.length > 0 &&
                                ' Butir berikut masih kosong dan tidak akan dihitung:'}
                        </DialogDescription>
                    </DialogHeader>

                    {kosong.length > 0 && (
                        <ul className="max-h-48 space-y-1 overflow-y-auto rounded-lg border p-3 text-sm">
                            {kosong.map((leaf) => (
                                <li key={leaf.key} className="text-muted-foreground">
                                    • {leaf.label}
                                </li>
                            ))}
                        </ul>
                    )}

                    <div className="flex justify-end gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setConfirmOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={() => {
                                setConfirmOpen(false);
                                submit();
                            }}
                            disabled={processing}
                        >
                            {processing ? 'Menyimpan...' : 'Ya, Submit'}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

function LeafInput({
    label,
    value,
    onChange,
}: {
    label: string;
    value?: string;
    onChange: (v: string) => void;
}) {
    const predikat = predikatDari(value ?? '');

    return (
        <div className="flex items-center gap-3">
            <p className="flex-1 text-sm text-muted-foreground">{label}</p>
            <Input
                type="number"
                min={0}
                max={100}
                placeholder="0-100"
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
                className="w-24"
            />
            {predikat && (
                <Badge variant="outline" className="w-10 justify-center">
                    {predikat}
                </Badge>
            )}
        </div>
    );
}

FormPenilaian.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: guruPembimbingDashboard() },
        { title: 'Penilaian PKL', href: penilaianIndex() },
    ],
};
