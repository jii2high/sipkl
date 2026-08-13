import { Head, router } from "@inertiajs/react";
import { Clock, Plus, Trash2, Eye } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { dashboard as kaprogDashboard } from "@/routes/kaprog";

interface SuratTugasItem {
    id: number;
    status: string;
    lingkup: string;
    nomor_surat: string | null;
    tanggal_terbit: string | null;
    berlaku_hingga: string | null;
    periode: { id: number; nama: string };
    kompetensi_keahlian: { id: number; nama: string } | null;
}

interface Props {
    drafts: SuratTugasItem[];
    terbit: SuratTugasItem[];
    periodes: { id: number; nama: string }[];
}

const PAGE_SIZE = 6;

export default function Monitoring({ drafts, terbit, periodes }: Props) {
    const [open, setOpen] = useState(false);
    const [form, setForm] = useState({ periode_id: "", berlaku_hingga: "" });
    const [processing, setProcessing] = useState(false);

    // Preview surat terbit (read-only)
    const [previewTarget, setPreviewTarget] = useState<SuratTugasItem | null>(null);

    // Pagination terbit
    const [halaman, setHalaman] = useState(1);
    const [search, setSearch] = useState("");

    function submit() {
        setProcessing(true);
        router.post("/kaprog/monitoring", form, {
            onSuccess: () => {
 setOpen(false); setForm({ periode_id: "", berlaku_hingga: "" }); toast.success("Draft pengajuan monitoring berhasil dibuat."); 
},
            onFinish: () => setProcessing(false),
        });
    }

    function hapusDraft(id: number) {
        if (confirm("Batalkan draft pengajuan ini?")) {
            router.delete(`/kaprog/monitoring/${id}`, {
                onSuccess: () => toast.success("Draft dibatalkan."),
            });
        }
    }

    const filteredTerbit = terbit.filter((t) =>
        !search ||
        t.periode.nama.toLowerCase().includes(search.toLowerCase()) ||
        (t.kompetensi_keahlian?.nama ?? "global").toLowerCase().includes(search.toLowerCase())
    );
    const totalHalaman = Math.ceil(filteredTerbit.length / PAGE_SIZE);
    const itemsHalaman = filteredTerbit.slice((halaman - 1) * PAGE_SIZE, halaman * PAGE_SIZE);

    return (
        <>
            <Head title="Monitoring PKL" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">Monitoring PKL</h2>
                        <p className="text-sm text-muted-foreground">
                            Buat draft Surat Tugas untuk jurusan Anda. Draft akan dikirim ke Hubin untuk diterbitkan.
                        </p>
                    </div>
                    <Button onClick={() => setOpen(true)}>
                        <Plus className="mr-1 size-4" />Ajukan Monitoring
                    </Button>
                </div>

                {/* Draft Menunggu */}
                <section>
                    <h3 className="mb-2 font-semibold">Draft Menunggu Hubin</h3>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">No</TableHead>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Berlaku S/d</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {drafts.length === 0 && (
                                    <TableRow><TableCell colSpan={5} className="py-6 text-center text-muted-foreground">Tidak ada draft pengajuan.</TableCell></TableRow>
                                )}
                                {drafts.map((d, idx) => (
                                    <TableRow key={d.id}>
                                        <TableCell>{idx + 1}</TableCell>
                                        <TableCell className="font-medium">{d.periode.nama}</TableCell>
                                        <TableCell>{d.berlaku_hingga ?? "—"}</TableCell>
                                        <TableCell>
                                            <span className="flex items-center gap-1 text-sm text-muted-foreground">
                                                <Clock className="size-4" />Menunggu Hubin
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="sm" className="text-destructive hover:text-destructive" onClick={() => hapusDraft(d.id)}>
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </section>

                {/* Surat Terbit */}
                <section>
                    <h3 className="mb-2 font-semibold">Surat Tugas Terbit</h3>
                    <Input placeholder="Cari periode atau jurusan..." value={search} onChange={(e) => {
 setSearch(e.target.value); setHalaman(1); 
}} className="mb-3 max-w-sm" />
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">No</TableHead>
                                    <TableHead>Lingkup</TableHead>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Berlaku S/d</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {itemsHalaman.length === 0 && (
                                    <TableRow><TableCell colSpan={6} className="py-6 text-center text-muted-foreground">Belum ada surat tugas terbit.</TableCell></TableRow>
                                )}
                                {itemsHalaman.map((t, idx) => (
                                    <TableRow key={t.id}>
                                        <TableCell>{(halaman - 1) * PAGE_SIZE + idx + 1}</TableCell>
                                        <TableCell>{t.lingkup === "global" ? <span className="text-muted-foreground">Global</span> : t.kompetensi_keahlian?.nama}</TableCell>
                                        <TableCell>{t.periode.nama}</TableCell>
                                        <TableCell>{t.berlaku_hingga ?? "—"}</TableCell>
                                        <TableCell><Badge variant={t.status === "aktif" ? "default" : "secondary"}>{t.status}</Badge></TableCell>
                                        <TableCell className="text-right">
                                            <Button size="sm" variant="outline" onClick={() => setPreviewTarget(t)}>
                                                <Eye className="mr-1 size-4" />Lihat Surat
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    {totalHalaman > 1 && (
                        <div className="mt-3 flex items-center justify-between text-sm text-muted-foreground">
                            <span>Halaman {halaman} dari {totalHalaman}</span>
                            <div className="flex gap-2">
                                <Button size="sm" variant="outline" disabled={halaman <= 1} onClick={() => setHalaman(halaman - 1)}>Sebelumnya</Button>
                                <Button size="sm" variant="outline" disabled={halaman >= totalHalaman} onClick={() => setHalaman(halaman + 1)}>Berikutnya</Button>
                            </div>
                        </div>
                    )}
                </section>
            </div>

            {/* Dialog Ajukan Monitoring */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Ajukan Monitoring</DialogTitle>
                        <DialogDescription>Scoped ke jurusan Anda. Hubin akan menerbitkan surat tugas.</DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-3">
                        <div className="space-y-1">
                            <Label>Periode</Label>
                            <Select value={form.periode_id} onValueChange={(v) => setForm({ ...form, periode_id: v })}>
                                <SelectTrigger><SelectValue placeholder="Pilih periode" /></SelectTrigger>
                                <SelectContent>{periodes.map((p) => <SelectItem key={p.id} value={String(p.id)}>{p.nama}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1">
                            <Label>Berlaku Hingga</Label>
                            <Input type="date" value={form.berlaku_hingga} onChange={(e) => setForm({ ...form, berlaku_hingga: e.target.value })} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)}>Batal</Button>
                        <Button disabled={processing} onClick={submit}>Ajukan</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog Preview Surat Terbit (read-only) */}
            <Dialog open={!!previewTarget} onOpenChange={(v) => {
 if (!v) {
setPreviewTarget(null);
} 
}}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Detail Surat Tugas</DialogTitle>
                        <DialogDescription>{previewTarget?.nomor_surat ?? "—"}</DialogDescription>
                    </DialogHeader>
                    <div className="grid grid-cols-2 gap-3 text-sm">
                        <div><p className="text-muted-foreground">Status</p><Badge className="mt-1" variant={previewTarget?.status === "aktif" ? "default" : "secondary"}>{previewTarget?.status}</Badge></div>
                        <div><p className="text-muted-foreground">Lingkup</p><p className="font-medium">{previewTarget?.lingkup === "global" ? "Global" : previewTarget?.kompetensi_keahlian?.nama}</p></div>
                        <div><p className="text-muted-foreground">Periode</p><p className="font-medium">{previewTarget?.periode.nama}</p></div>
                        <div><p className="text-muted-foreground">Berlaku Hingga</p><p className="font-medium">{previewTarget?.berlaku_hingga ?? "—"}</p></div>
                        {previewTarget?.tanggal_terbit && <div><p className="text-muted-foreground">Tanggal Terbit</p><p className="font-medium">{previewTarget.tanggal_terbit}</p></div>}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setPreviewTarget(null)}>Tutup</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Monitoring.layout = { breadcrumbs: [{ title: "Dashboard", href: kaprogDashboard() }, { title: "Monitoring PKL" }] };
