import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function ImportExportIndex() {
    const [guruFile, setGuruFile] = useState<File | null>(null);
    const [siswaFile, setSiswaFile] = useState<File | null>(null);

    function handleImport(
        event: React.FormEvent<HTMLFormElement>,
        type: 'guru' | 'siswa',
    ) {
        event.preventDefault();

        const file = type === 'guru' ? guruFile : siswaFile;

        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        router.post(`/hubin/import-export/${type}`, formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                event.currentTarget.reset();

                if (type === 'guru') {
                    setGuruFile(null);
                } else {
                    setSiswaFile(null);
                }
            },
        });
    }

    return (
        <>
            <Head title="Import / Export" />

            <div className="space-y-6 p-4">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold">
                        Import / Export Data
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Unduh template Excel atau impor data guru dan siswa
                        secara massal. Baris yang gagal akan dilewati dan tidak
                        disimpan.
                    </p>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr_minmax(320px,420px)]">
                    <div className="rounded-2xl border border-border bg-background p-6 shadow-sm">
                        <h2 className="text-lg font-semibold">Ekspor Data</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Unduh data akun atau template untuk impor guru dan
                            siswa.
                        </p>

                        <div className="mt-6 grid gap-3">
                            <Button asChild>
                                <a href="/hubin/import-export/akun">
                                    Unduh Semua Akun
                                </a>
                            </Button>
                            <Button variant="secondary" asChild>
                                <a href="/hubin/import-export/guru-template">
                                    Unduh Template Guru
                                </a>
                            </Button>
                            <Button variant="secondary" asChild>
                                <a href="/hubin/import-export/siswa-template">
                                    Unduh Template Siswa
                                </a>
                            </Button>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-border bg-background p-6 shadow-sm">
                        <h2 className="text-lg font-semibold">Impor Data</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Pilih file Excel dan unggah untuk menambahkan guru
                            atau siswa.
                        </p>

                        <div className="mt-6 space-y-6">
                            <form
                                onSubmit={(event) =>
                                    handleImport(event, 'guru')
                                }
                            >
                                <div className="space-y-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="guru-file">
                                            File Guru
                                        </Label>
                                        <Input
                                            id="guru-file"
                                            type="file"
                                            accept=".xlsx,.xls,.csv"
                                            onChange={(event) =>
                                                setGuruFile(
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                            required
                                        />
                                    </div>
                                    <Button type="submit">Impor Guru</Button>
                                </div>
                            </form>

                            <form
                                onSubmit={(event) =>
                                    handleImport(event, 'siswa')
                                }
                            >
                                <div className="space-y-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="siswa-file">
                                            File Siswa
                                        </Label>
                                        <Input
                                            id="siswa-file"
                                            type="file"
                                            accept=".xlsx,.xls,.csv"
                                            onChange={(event) =>
                                                setSiswaFile(
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                            required
                                        />
                                    </div>
                                    <Button type="submit">Impor Siswa</Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
