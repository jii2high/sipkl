import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export interface SubSectionItem {
    id: number;
    nomor_urut: number;
    nama: string;
    deskripsi: string | null;
}

export interface SectionItem extends SubSectionItem {
    sub_sections: SubSectionItem[];
}

export interface IndikatorItem {
    id: number;
    nomor_urut: number;
    nama: string;
    deskripsi: string | null;
    sections: SectionItem[];
}

interface Props {
    indikator: IndikatorItem[];
    basePath: string;
}

interface LevelKey {
    level: 'indikator' | 'section' | 'subSection';
    itemId: number;
}

export default function IndikatorTree({ indikator, basePath }: Props) {
    const [open, setOpen] = useState(false);
    const [edit, setEdit] = useState<IndikatorItem | null>(null);
    const [form, setForm] = useState({
        nomor_urut: 1,
        nama: '',
        deskripsi: '',
    });

    const [section, setSection] = useState<{
        open: boolean;
        indikator: IndikatorItem | null;
        edit: SectionItem | null;
    }>({ open: false, indikator: null, edit: null });
    const [sectionForm, setSectionForm] = useState({
        nomor_urut: 1,
        nama: '',
        deskripsi: '',
    });

    const [subSection, setSubSection] = useState<{
        open: boolean;
        section: SectionItem | null;
        edit: SubSectionItem | null;
    }>({ open: false, section: null, edit: null });
    const [subForm, setSubForm] = useState({
        nomor_urut: 1,
        nama: '',
        deskripsi: '',
    });

    function resetForm(no: number, nama = '', deskripsi = '') {
        return { nomor_urut: no, nama, deskripsi };
    }

    function openCreate() {
        setEdit(null);
        setForm(resetForm((indikator.length ?? 0) + 1));
        setOpen(true);
    }

    function openEdit(i: IndikatorItem) {
        setEdit(i);
        setForm(resetForm(i.nomor_urut, i.nama, i.deskripsi ?? ''));
        setOpen(true);
    }

    function submit() {
        router[edit ? 'put' : 'post'](
            edit ? `${basePath}/${edit.id}` : basePath,
            form,
        );
        setOpen(false);
    }

    function hapus(id: number) {
        if (confirm('Yakin ingin menghapus indikator ini beserta isinya?')) {
            router.delete(`${basePath}/${id}`);
        }
    }

    function openSectionCreate(i: IndikatorItem) {
        setSection({ open: true, indikator: i, edit: null });
        setSectionForm(resetForm((i.sections?.length ?? 0) + 1));
    }

    function openSectionEdit(s: SectionItem) {
        setSection({ open: true, indikator: null, edit: s });
        setSectionForm(resetForm(s.nomor_urut, s.nama, s.deskripsi ?? ''));
    }

    function submitSection() {
        const s = section;

        if (s.edit) {
            router.put(`${basePath}/section/${s.edit.id}`, sectionForm);
        } else if (s.indikator) {
            router.post(`${basePath}/${s.indikator.id}/section`, sectionForm);
        }

        setSection({ open: false, indikator: null, edit: null });
    }

    function hapusSection(id: number) {
        if (confirm('Yakin ingin menghapus section ini?')) {
            router.delete(`${basePath}/section/${id}`);
        }
    }

    function openSubSectionCreate(s: SectionItem) {
        setSubSection({ open: true, section: s, edit: null });
        setSubForm(resetForm((s.sub_sections?.length ?? 0) + 1));
    }

    function openSubSectionEdit(s: SubSectionItem) {
        setSubSection({ open: true, section: null, edit: s });
        setSubForm(resetForm(s.nomor_urut, s.nama, s.deskripsi ?? ''));
    }

    function submitSubSection() {
        const ss = subSection;

        if (ss.edit) {
            router.put(`${basePath}/sub-section/${ss.edit.id}`, subForm);
        } else if (ss.section) {
            router.post(
                `${basePath}/section/${ss.section.id}/sub-section`,
                subForm,
            );
        }

        setSubSection({ open: false, section: null, edit: null });
    }

    function hapusSubSection(id: number) {
        if (confirm('Yakin ingin menghapus sub-indikator ini?')) {
            router.delete(`${basePath}/sub-section/${id}`);
        }
    }

    function pindah(
        level: LevelKey['level'],
        itemId: number,
        siblings: { id: number }[],
        from: number,
        to: number,
    ) {
        if (to < 0 || to >= siblings.length) {
            return;
        }

        router.post(`${basePath}/reorder`, {
            level,
            itemId,
            fromIndex: from,
            toIndex: to,
        });
    }

    const Panah = ({
        level,
        itemId,
        siblings,
        index,
    }: {
        level: LevelKey['level'];
        itemId: number;
        siblings: { id: number }[];
        index: number;
    }) => (
        <span className="flex flex-col">
            <button
                type="button"
                className="text-muted-foreground hover:text-foreground"
                disabled={index === 0}
                onClick={() =>
                    pindah(level, itemId, siblings, index, index - 1)
                }
            >
                <ArrowUp className="size-3.5" />
            </button>
            <button
                type="button"
                className="text-muted-foreground hover:text-foreground"
                disabled={index === siblings.length - 1}
                onClick={() =>
                    pindah(level, itemId, siblings, index, index + 1)
                }
            >
                <ArrowDown className="size-3.5" />
            </button>
        </span>
    );

    return (
        <>
            <div className="flex flex-col gap-4">
                <div className="flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">
                        {indikator.length} indikator — urutkan dengan panah.
                    </p>
                    <Button size="sm" onClick={openCreate}>
                        <Plus className="mr-1 size-4" />
                        Tambah Indikator
                    </Button>
                </div>
                <div className="space-y-3">
                    {indikator.map((i, iIdx) => (
                        <div
                            key={i.id}
                            className="bg-surface rounded-lg border p-4"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-start gap-3">
                                    <Panah
                                        level="indikator"
                                        itemId={i.id}
                                        siblings={indikator}
                                        index={iIdx}
                                    />
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="secondary">
                                                Indikator {i.nomor_urut}
                                            </Badge>
                                            <span className="font-semibold">
                                                {i.nama}
                                            </span>
                                        </div>
                                        {i.deskripsi && (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {i.deskripsi}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                <div className="flex shrink-0 gap-1">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => openEdit(i)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => hapus(i.id)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>

                            <div className="mt-3 space-y-2 pl-0 md:pl-10">
                                {i.sections?.map((s, sIdx) => (
                                    <div
                                        key={s.id}
                                        className="rounded-md border bg-background p-3"
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <div className="flex items-center gap-2">
                                                <Panah
                                                    level="section"
                                                    itemId={i.id}
                                                    siblings={i.sections}
                                                    index={sIdx}
                                                />
                                                <div>
                                                    <span className="text-sm font-medium">
                                                        {s.nomor_urut}. {s.nama}
                                                    </span>
                                                    {s.deskripsi && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {s.deskripsi}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex shrink-0 gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        openSectionEdit(s)
                                                    }
                                                >
                                                    Edit
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive"
                                                    onClick={() =>
                                                        hapusSection(s.id)
                                                    }
                                                >
                                                    Hapus
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openSubSectionCreate(s)
                                                    }
                                                >
                                                    <Plus className="mr-1 size-3.5" />
                                                    Sub
                                                </Button>
                                            </div>
                                        </div>

                                        {s.sub_sections?.length > 0 && (
                                            <ul className="mt-2 space-y-1 pl-8">
                                                {s.sub_sections.map(
                                                    (ss, ssIdx) => (
                                                        <li
                                                            key={ss.id}
                                                            className="flex items-center justify-between gap-2 text-sm text-muted-foreground"
                                                        >
                                                            <div className="flex items-center gap-2">
                                                                <Panah
                                                                    level="subSection"
                                                                    itemId={
                                                                        s.id
                                                                    }
                                                                    siblings={
                                                                        s.sub_sections
                                                                    }
                                                                    index={
                                                                        ssIdx
                                                                    }
                                                                />
                                                                <span>
                                                                    {
                                                                        ss.nomor_urut
                                                                    }
                                                                    . {ss.nama}
                                                                </span>
                                                            </div>
                                                            <div className="flex shrink-0 gap-1">
                                                                <button
                                                                    className="hover:underline"
                                                                    onClick={() =>
                                                                        openSubSectionEdit(
                                                                            ss,
                                                                        )
                                                                    }
                                                                >
                                                                    edit
                                                                </button>
                                                                <button
                                                                    className="text-destructive hover:underline"
                                                                    onClick={() =>
                                                                        hapusSubSection(
                                                                            ss.id,
                                                                        )
                                                                    }
                                                                >
                                                                    hapus
                                                                </button>
                                                            </div>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        )}
                                    </div>
                                ))}
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => openSectionCreate(i)}
                                >
                                    <Plus className="mr-1 size-3.5" />
                                    Tambah Indikator Penilaian
                                </Button>
                            </div>
                        </div>
                    ))}
                    {indikator.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Belum ada indikator. Tambahkan yang pertama.
                        </p>
                    )}
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {edit ? 'Edit Indikator' : 'Tambah Indikator'}
                        </DialogTitle>
                        <DialogDescription>
                            Level 1 — tujuan pembelajaran.
                        </DialogDescription>
                    </DialogHeader>
                    <FormIndikator
                        form={form}
                        setForm={setForm}
                        submit={submit}
                        submitLabel={edit ? 'Simpan' : 'Tambah'}
                    />
                </DialogContent>
            </Dialog>

            <Dialog
                open={section.open}
                onOpenChange={(v) => setSection({ ...section, open: v })}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {section.edit ? 'Edit' : 'Tambah'} Indikator
                            Penilaian
                        </DialogTitle>
                        <DialogDescription>
                            Level 2 — sub-kompetensi dari indikator.
                        </DialogDescription>
                    </DialogHeader>
                    <FormIndikator
                        form={sectionForm}
                        setForm={setSectionForm}
                        submit={submitSection}
                        submitLabel={section.edit ? 'Simpan' : 'Tambah'}
                    />
                </DialogContent>
            </Dialog>

            <Dialog
                open={subSection.open}
                onOpenChange={(v) => setSubSection({ ...subSection, open: v })}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {subSection.edit ? 'Edit' : 'Tambah'} Sub-indikator
                        </DialogTitle>
                        <DialogDescription>
                            Level 3 — rincian nilai leaf.
                        </DialogDescription>
                    </DialogHeader>
                    <FormIndikator
                        form={subForm}
                        setForm={setSubForm}
                        submit={submitSubSection}
                        submitLabel={subSection.edit ? 'Simpan' : 'Tambah'}
                    />
                </DialogContent>
            </Dialog>
        </>
    );
}

function FormIndikator({
    form,
    setForm,
    submit,
    submitLabel,
}: {
    form: { nomor_urut: number; nama: string; deskripsi: string };
    setForm: (v: {
        nomor_urut: number;
        nama: string;
        deskripsi: string;
    }) => void;
    submit: () => void;
    submitLabel: string;
}) {
    return (
        <div className="flex flex-col gap-3">
            <div>
                <Label htmlFor="nama">Nama</Label>
                <Input
                    id="nama"
                    value={form.nama}
                    onChange={(e) => setForm({ ...form, nama: e.target.value })}
                />
            </div>
            <div>
                <Label htmlFor="deskripsi">
                    Deskripsi{' '}
                    <span className="text-muted-foreground">(opsional)</span>
                </Label>
                <Input
                    id="deskripsi"
                    value={form.deskripsi}
                    onChange={(e) =>
                        setForm({ ...form, deskripsi: e.target.value })
                    }
                />
            </div>
            <div>
                <Label htmlFor="nomor_urut">Nomor Urut</Label>
                <Input
                    id="nomor_urut"
                    type="number"
                    value={form.nomor_urut}
                    onChange={(e) => {
                        const value = Math.min(
                            Math.max(+e.target.value || 1, 1),
                            99,
                        );
                        setForm({ ...form, nomor_urut: value });
                    }}
                />
            </div>
            <Button onClick={submit}>{submitLabel}</Button>
        </div>
    );
}
