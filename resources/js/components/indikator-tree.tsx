import type { DragEndEvent } from '@dnd-kit/core';
import {
    DndContext,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { router, usePage } from '@inertiajs/react';
import { GripVertical, Plus } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

// ─────────────────────────────────────────────
// Types
// ─────────────────────────────────────────────
export interface SubSectionItem {
    id: number;
    nomor_urut: number;
    nama: string;
    deskripsi: string | null;
}

export interface SectionItem {
    id: number;
    nomor_urut: number;
    nama: string;
    deskripsi: string | null;
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

// ─────────────────────────────────────────────
// Helper: konversi index ke huruf (0→a, 1→b, dst)
// ─────────────────────────────────────────────
function toHuruf(idx: number): string {
    return String.fromCharCode(97 + idx);
}

// ─────────────────────────────────────────────
// SortableRow — wrapper drag handle untuk div (dialog list)
// ─────────────────────────────────────────────
function SortableRow({
    id,
    children,
}: {
    id: number;
    children: (props: {
        dragHandleProps: React.HTMLAttributes<HTMLButtonElement>;
    }) => React.ReactNode;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    return (
        <div
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
                opacity: isDragging ? 0.45 : 1,
                zIndex: isDragging ? 10 : undefined,
                position: isDragging ? 'relative' : undefined,
            }}
        >
            {children({ dragHandleProps: { ...attributes, ...listeners } })}
        </div>
    );
}

// ─────────────────────────────────────────────
// SortableTr — wrapper drag handle untuk <tr> (tabel)
// ─────────────────────────────────────────────
function SortableTr({
    id,
    children,
}: {
    id: number;
    children: (props: {
        dragHandleProps: React.HTMLAttributes<HTMLButtonElement>;
    }) => React.ReactNode;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    return (
        <tr
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
                opacity: isDragging ? 0.45 : 1,
            }}
            className="border-b last:border-0 hover:bg-muted/30"
        >
            {children({ dragHandleProps: { ...attributes, ...listeners } })}
        </tr>
    );
}

// ─────────────────────────────────────────────
// Form shared (nama + deskripsi opsional)
// ─────────────────────────────────────────────
function FormItem({
    form,
    setForm,
    onSubmit,
    submitLabel,
    namePlaceholder = 'Nama...',
}: {
    form: { nama: string; deskripsi: string };
    setForm: (v: { nama: string; deskripsi: string }) => void;
    onSubmit: () => void;
    submitLabel: string;
    namePlaceholder?: string;
}) {
    return (
        <div className="flex flex-col gap-3">
            <div>
                <Label htmlFor="form-nama">Nama</Label>
                <Input
                    id="form-nama"
                    value={form.nama}
                    placeholder={namePlaceholder}
                    onChange={(e) => setForm({ ...form, nama: e.target.value })}
                    autoFocus
                />
            </div>
            <div>
                <Label htmlFor="form-deskripsi">
                    Deskripsi{' '}
                    <span className="text-muted-foreground">(opsional)</span>
                </Label>
                <Textarea
                    id="form-deskripsi"
                    value={form.deskripsi}
                    placeholder="Deskripsi singkat..."
                    onChange={(e) =>
                        setForm({ ...form, deskripsi: e.target.value })
                    }
                    rows={2}
                />
            </div>
            <Button onClick={onSubmit} disabled={!form.nama.trim()}>
                {submitLabel}
            </Button>
        </div>
    );
}

// ─────────────────────────────────────────────
// Dialog Level 3 — Sub-indikator
// ─────────────────────────────────────────────
function DialogKelolaSub({
    open,
    onOpenChange,
    section,
    parentHuruf,
    basePath,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    section: SectionItem | null;
    parentHuruf: string;
    basePath: string;
}) {
    // Baca data sub-section langsung dari Inertia props (reaktif) — bukan dari prop stale
    const page = usePage<{ indikator: IndikatorItem[] }>();
    const liveSectionData = page.props.indikator
        .flatMap((i) => i.sections)
        .find((s) => s.id === section?.id);
    const liveSubSections =
        liveSectionData?.sub_sections ?? section?.sub_sections ?? [];

    // Override sementara untuk optimistic drag — di-clear setelah server respond
    const [dragOverride, setDragOverride] = useState<SubSectionItem[] | null>(
        null,
    );
    const items = dragOverride ?? liveSubSections;

    const [formOpen, setFormOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<SubSectionItem | null>(null);
    const [form, setForm] = useState({ nama: '', deskripsi: '' });

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;

        if (!over || active.id === over.id || !section) {
            return;
        }

        const oldIdx = items.findIndex((it) => it.id === active.id);
        const newIdx = items.findIndex((it) => it.id === over.id);
        const reordered = arrayMove(items, oldIdx, newIdx);
        setDragOverride(reordered);

        router.post(
            `${basePath}/reorder`,
            {
                level: 'subSection',
                itemId: section.id,
                ids: reordered.map((it) => it.id),
            },
            { preserveScroll: true, onSuccess: () => setDragOverride(null) },
        );
    }

    function bukaForm(target?: SubSectionItem) {
        setEditTarget(target ?? null);
        setForm({
            nama: target?.nama ?? '',
            deskripsi: target?.deskripsi ?? '',
        });
        setFormOpen(true);
    }

    function submit() {
        if (!section) {
            return;
        }

        if (editTarget) {
            router.put(`${basePath}/sub-section/${editTarget.id}`, form, {
                preserveScroll: true,
            });
        } else {
            router.post(`${basePath}/section/${section.id}/sub-section`, form, {
                preserveScroll: true,
            });
        }

        setFormOpen(false);
    }

    function hapus(id: number) {
        if (confirm('Hapus sub-indikator ini?')) {
            router.delete(`${basePath}/sub-section/${id}`, {
                preserveScroll: true,
            });
        }
    }

    if (!section) {
        return null;
    }

    return (
        <>
            <Dialog open={open} onOpenChange={onOpenChange}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Sub-indikator</DialogTitle>
                        <DialogDescription>
                            Rincian dari indikator{' '}
                            <strong>
                                {parentHuruf}. {section.nama}
                            </strong>
                        </DialogDescription>
                    </DialogHeader>

                    <div className="max-h-80 overflow-y-auto">
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragEnd={handleDragEnd}
                        >
                            <SortableContext
                                items={items.map((it) => it.id)}
                                strategy={verticalListSortingStrategy}
                            >
                                <div className="space-y-1">
                                    {items.length === 0 && (
                                        <p className="py-4 text-center text-sm text-muted-foreground">
                                            Belum ada sub-indikator.
                                        </p>
                                    )}
                                    {items.map((ss, idx) => (
                                        <SortableRow key={ss.id} id={ss.id}>
                                            {({ dragHandleProps }) => (
                                                <div className="flex items-center justify-between gap-2 rounded-md border p-2">
                                                    <div className="flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            className="cursor-grab text-muted-foreground/50 hover:text-muted-foreground active:cursor-grabbing"
                                                            {...dragHandleProps}
                                                        >
                                                            <GripVertical className="size-3.5" />
                                                        </button>
                                                        <div>
                                                            <span className="text-sm font-medium">
                                                                {parentHuruf}
                                                                {idx + 1}.{' '}
                                                                {ss.nama}
                                                            </span>
                                                            {ss.deskripsi && (
                                                                <p className="text-xs text-muted-foreground">
                                                                    {
                                                                        ss.deskripsi
                                                                    }
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="flex shrink-0 gap-1">
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                bukaForm(ss)
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-destructive"
                                                            onClick={() =>
                                                                hapus(ss.id)
                                                            }
                                                        >
                                                            Hapus
                                                        </Button>
                                                    </div>
                                                </div>
                                            )}
                                        </SortableRow>
                                    ))}
                                </div>
                            </SortableContext>
                        </DndContext>
                    </div>

                    <DialogFooter className="justify-start">
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => bukaForm()}
                        >
                            <Plus className="mr-1 size-3.5" />
                            Tambah Sub-indikator
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog form tambah/edit sub */}
            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>
                            {editTarget ? 'Edit' : 'Tambah'} Sub-indikator
                        </DialogTitle>
                    </DialogHeader>
                    <FormItem
                        form={form}
                        setForm={setForm}
                        onSubmit={submit}
                        submitLabel={editTarget ? 'Simpan' : 'Tambah'}
                        namePlaceholder={`cth: ${parentHuruf}1`}
                    />
                </DialogContent>
            </Dialog>
        </>
    );
}

// ─────────────────────────────────────────────
// Dialog Level 2 — Kelola Indikator Penilaian
// ─────────────────────────────────────────────
function DialogKelolaSeksi({
    open,
    onOpenChange,
    indikator,
    basePath,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    indikator: IndikatorItem | null;
    basePath: string;
}) {
    // Baca sections langsung dari Inertia props (reaktif)
    const page = usePage<{ indikator: IndikatorItem[] }>();
    const liveIndikator = page.props.indikator.find(
        (i) => i.id === indikator?.id,
    );
    const liveSections = liveIndikator?.sections ?? indikator?.sections ?? [];

    // Override sementara saat drag
    const [dragOverride, setDragOverride] = useState<SectionItem[] | null>(
        null,
    );
    const items = dragOverride ?? liveSections;

    const [formOpen, setFormOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<SectionItem | null>(null);
    const [form, setForm] = useState({ nama: '', deskripsi: '' });

    // Dialog Level 3
    const [subOpen, setSubOpen] = useState(false);
    const [subTarget, setSubTarget] = useState<SectionItem | null>(null);
    const [subParentHuruf, setSubParentHuruf] = useState('a');

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;

        if (!over || active.id === over.id || !indikator) {
            return;
        }

        const oldIdx = items.findIndex((it) => it.id === active.id);
        const newIdx = items.findIndex((it) => it.id === over.id);
        const reordered = arrayMove(items, oldIdx, newIdx);
        setDragOverride(reordered);

        router.post(
            `${basePath}/reorder`,
            {
                level: 'section',
                itemId: indikator.id,
                ids: reordered.map((it) => it.id),
            },
            { preserveScroll: true, onSuccess: () => setDragOverride(null) },
        );
    }

    function bukaForm(target?: SectionItem) {
        setEditTarget(target ?? null);
        setForm({
            nama: target?.nama ?? '',
            deskripsi: target?.deskripsi ?? '',
        });
        setFormOpen(true);
    }

    function submit() {
        if (!indikator) {
            return;
        }

        if (editTarget) {
            router.put(`${basePath}/section/${editTarget.id}`, form, {
                preserveScroll: true,
            });
        } else {
            router.post(`${basePath}/${indikator.id}/section`, form, {
                preserveScroll: true,
            });
        }

        setFormOpen(false);
    }

    function hapus(id: number) {
        if (
            confirm('Hapus indikator penilaian ini beserta sub-indikatornya?')
        ) {
            router.delete(`${basePath}/section/${id}`, {
                preserveScroll: true,
            });
        }
    }

    function bukaSub(s: SectionItem, idx: number) {
        setSubTarget(s);
        setSubParentHuruf(toHuruf(idx));
        setSubOpen(true);
    }

    if (!indikator) {
        return null;
    }

    return (
        <>
            <Dialog open={open} onOpenChange={onOpenChange}>
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Kelola Indikator Penilaian</DialogTitle>
                        <DialogDescription>
                            Tujuan Pembelajaran:{' '}
                            <strong>{indikator.nama}</strong>
                        </DialogDescription>
                    </DialogHeader>

                    <div className="max-h-96 overflow-y-auto">
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragEnd={handleDragEnd}
                        >
                            <SortableContext
                                items={items.map((it) => it.id)}
                                strategy={verticalListSortingStrategy}
                            >
                                <div className="space-y-1">
                                    {items.length === 0 && (
                                        <p className="py-6 text-center text-sm text-muted-foreground">
                                            Belum ada indikator penilaian.
                                        </p>
                                    )}
                                    {items.map((s, idx) => (
                                        <SortableRow key={s.id} id={s.id}>
                                            {({ dragHandleProps }) => (
                                                <div className="flex items-center justify-between gap-2 rounded-md border p-2">
                                                    <div className="flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            className="cursor-grab text-muted-foreground/50 hover:text-muted-foreground active:cursor-grabbing"
                                                            {...dragHandleProps}
                                                        >
                                                            <GripVertical className="size-3.5" />
                                                        </button>
                                                        <div>
                                                            <div className="flex items-center gap-1.5">
                                                                <Badge
                                                                    variant="outline"
                                                                    className="font-mono text-xs"
                                                                >
                                                                    {toHuruf(
                                                                        idx,
                                                                    )}
                                                                </Badge>
                                                                <span className="text-sm font-medium">
                                                                    {s.nama}
                                                                </span>
                                                            </div>
                                                            {s.deskripsi && (
                                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                                    {
                                                                        s.deskripsi
                                                                    }
                                                                </p>
                                                            )}
                                                            {s.sub_sections
                                                                .length > 0 && (
                                                                <p className="mt-0.5 text-xs text-muted-foreground/70">
                                                                    {
                                                                        s
                                                                            .sub_sections
                                                                            .length
                                                                    }{' '}
                                                                    sub-indikator
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="flex shrink-0 gap-1">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                bukaSub(s, idx)
                                                            }
                                                        >
                                                            Sub-indikator
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                bukaForm(s)
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-destructive"
                                                            onClick={() =>
                                                                hapus(s.id)
                                                            }
                                                        >
                                                            Hapus
                                                        </Button>
                                                    </div>
                                                </div>
                                            )}
                                        </SortableRow>
                                    ))}
                                </div>
                            </SortableContext>
                        </DndContext>
                    </div>

                    <DialogFooter className="justify-start">
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => bukaForm()}
                        >
                            <Plus className="mr-1 size-3.5" />
                            Tambah Indikator Penilaian
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog form tambah/edit section */}
            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>
                            {editTarget ? 'Edit' : 'Tambah'} Indikator Penilaian
                        </DialogTitle>
                    </DialogHeader>
                    <FormItem
                        form={form}
                        setForm={setForm}
                        onSubmit={submit}
                        submitLabel={editTarget ? 'Simpan' : 'Tambah'}
                        namePlaceholder="cth: Kedisiplinan"
                    />
                </DialogContent>
            </Dialog>

            {/* Dialog Level 3 — key memaksa remount saat section target berubah */}
            <DialogKelolaSub
                key={subTarget?.id}
                open={subOpen}
                onOpenChange={setSubOpen}
                section={subTarget}
                parentHuruf={subParentHuruf}
                basePath={basePath}
            />
        </>
    );
}

// ─────────────────────────────────────────────
// Komponen Utama — Tabel Level 1
// ─────────────────────────────────────────────
const PAGE_SIZE = 6;

export default function IndikatorTree({ indikator, basePath }: Props) {
    // Baca langsung dari Inertia props — reaktif setelah server response
    const page = usePage<{ indikator: IndikatorItem[] }>();
    const liveIndikator = page.props.indikator ?? indikator;

    // Override sementara saat drag
    const [dragOverride, setDragOverride] = useState<IndikatorItem[] | null>(
        null,
    );
    const items = dragOverride ?? liveIndikator;

    const [halaman, setHalaman] = useState(1);

    const [formOpen, setFormOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<IndikatorItem | null>(null);
    const [form, setForm] = useState({ nama: '', deskripsi: '' });

    // Dialog Level 2
    const [seksiOpen, setSeksiOpen] = useState(false);
    const [seksiTarget, setSeksiTarget] = useState<IndikatorItem | null>(null);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    // Pagination
    const totalHalaman = Math.ceil(items.length / PAGE_SIZE);
    const itemsHalaman = items.slice(
        (halaman - 1) * PAGE_SIZE,
        halaman * PAGE_SIZE,
    );

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const oldIdx = items.findIndex((it) => it.id === active.id);
        const newIdx = items.findIndex((it) => it.id === over.id);
        const reordered = arrayMove(items, oldIdx, newIdx);
        setDragOverride(reordered);

        router.post(
            `${basePath}/reorder`,
            { level: 'indikator', ids: reordered.map((it) => it.id) },
            { preserveScroll: true, onSuccess: () => setDragOverride(null) },
        );
    }

    function bukaForm(target?: IndikatorItem) {
        setEditTarget(target ?? null);
        setForm({
            nama: target?.nama ?? '',
            deskripsi: target?.deskripsi ?? '',
        });
        setFormOpen(true);
    }

    function submit() {
        if (editTarget) {
            router.put(`${basePath}/${editTarget.id}`, form);
        } else {
            router.post(basePath, form);
        }

        setFormOpen(false);
    }

    function hapus(id: number) {
        if (
            confirm('Hapus tujuan pembelajaran ini beserta semua indikatornya?')
        ) {
            router.delete(`${basePath}/${id}`);
        }
    }

    function bukaKelola(i: IndikatorItem) {
        setSeksiTarget(i);
        setSeksiOpen(true);
    }

    // Cari nomor urut global (bukan per-halaman) untuk tampilan No
    function nomorGlobal(id: number): number {
        return items.findIndex((it) => it.id === id) + 1;
    }

    return (
        <>
            {/* Header */}
            <div className="flex items-center justify-between">
                <p className="text-sm text-muted-foreground">
                    {items.length} tujuan pembelajaran —{' '}
                    <GripVertical className="inline size-3.5" /> untuk mengubah
                    urutan.
                </p>
                <Button size="sm" onClick={() => bukaForm()}>
                    <Plus className="mr-1 size-4" />
                    Tambah Penilaian
                </Button>
            </div>

            {/* Tabel */}
            <div className="overflow-hidden rounded-lg border">
                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCenter}
                    onDragEnd={handleDragEnd}
                >
                    <SortableContext
                        items={itemsHalaman.map((it) => it.id)}
                        strategy={verticalListSortingStrategy}
                    >
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="w-8 p-3"></th>
                                    <th className="w-8 p-3">No</th>
                                    <th className="p-3">Tujuan Pembelajaran</th>
                                    <th className="p-3">Indikator Penilaian</th>
                                    <th className="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {itemsHalaman.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="p-8 text-center text-muted-foreground"
                                        >
                                            Belum ada tujuan pembelajaran.
                                            Tambahkan yang pertama.
                                        </td>
                                    </tr>
                                )}
                                {itemsHalaman.map((i) => (
                                    <SortableTr key={i.id} id={i.id}>
                                        {({ dragHandleProps }) => (
                                            <>
                                                <td className="p-3">
                                                    <button
                                                        type="button"
                                                        className="cursor-grab text-muted-foreground/50 hover:text-muted-foreground active:cursor-grabbing"
                                                        {...dragHandleProps}
                                                    >
                                                        <GripVertical className="size-4" />
                                                    </button>
                                                </td>
                                                <td className="p-3 text-muted-foreground">
                                                    {nomorGlobal(i.id)}
                                                </td>
                                                <td className="p-3">
                                                    <div className="font-medium">
                                                        {i.nama}
                                                    </div>
                                                    {i.deskripsi && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {i.deskripsi}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="p-3">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            bukaKelola(i)
                                                        }
                                                    >
                                                        Kelola Indikator
                                                        {i.sections.length >
                                                            0 && (
                                                            <Badge
                                                                variant="secondary"
                                                                className="ml-1.5 text-xs"
                                                            >
                                                                {
                                                                    i.sections
                                                                        .length
                                                                }
                                                            </Badge>
                                                        )}
                                                    </Button>
                                                </td>
                                                <td className="p-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                bukaForm(i)
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-destructive"
                                                            onClick={() =>
                                                                hapus(i.id)
                                                            }
                                                        >
                                                            Hapus
                                                        </Button>
                                                    </div>
                                                </td>
                                            </>
                                        )}
                                    </SortableTr>
                                ))}
                            </tbody>
                        </table>
                    </SortableContext>
                </DndContext>
            </div>

            {/* Pagination */}
            {totalHalaman > 1 && (
                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Halaman {halaman} dari {totalHalaman}
                    </span>
                    <div className="flex gap-1">
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={halaman <= 1}
                            onClick={() => setHalaman(halaman - 1)}
                        >
                            Sebelumnya
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={halaman >= totalHalaman}
                            onClick={() => setHalaman(halaman + 1)}
                        >
                            Berikutnya
                        </Button>
                    </div>
                </div>
            )}

            {/* Dialog tambah/edit Level 1 */}
            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>
                            {editTarget
                                ? 'Edit Tujuan Pembelajaran'
                                : 'Tambah Penilaian'}
                        </DialogTitle>
                        <DialogDescription>
                            Level 1 — tujuan pembelajaran.
                        </DialogDescription>
                    </DialogHeader>
                    <FormItem
                        form={form}
                        setForm={setForm}
                        onSubmit={submit}
                        submitLabel={editTarget ? 'Simpan' : 'Tambah'}
                        namePlaceholder="cth: Sikap & Etika Profesional"
                    />
                </DialogContent>
            </Dialog>

            {/* Dialog Level 2 — key memaksa remount saat target berubah, reset state items */}
            <DialogKelolaSeksi
                key={seksiTarget?.id}
                open={seksiOpen}
                onOpenChange={setSeksiOpen}
                indikator={seksiTarget}
                basePath={basePath}
            />
        </>
    );
}
