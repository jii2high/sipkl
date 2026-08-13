import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface FilterOption {
    label: string;
    value: string;
}

interface SearchFilterBarProps {
    baseRoute: string;
    searchPlaceholder?: string;
    filters?: { key: string; label: string; options: FilterOption[] }[];
}

export default function SearchFilterBar({
    baseRoute,
    searchPlaceholder = 'Cari...',
    filters = [],
}: SearchFilterBarProps) {
    const params = new URLSearchParams(window.location.search);
    const [search, setSearch] = useState(params.get('search') ?? '');
    const [activeFilters, setActiveFilters] = useState<Record<string, string>>(
        () => {
            const result: Record<string, string> = {};
            filters.forEach((f) => {
                const v = params.get(f.key);

                if (v) {
                    result[f.key] = v;
                }
            });

            return result;
        },
    );

    // Simpan referensi timer debounce di luar state agar tidak re-render
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    function apply(
        overrideSearch?: string,
        overrideFilters?: Record<string, string>,
    ) {
        const q: Record<string, string> = {};
        const s = overrideSearch !== undefined ? overrideSearch : search;
        const f = overrideFilters !== undefined ? overrideFilters : activeFilters;

        if (s) {
            q.search = s;
        }

        Object.entries(f).forEach(([k, v]) => {
            if (v) {
                q[k] = v;
            }
        });

        router.get(baseRoute, q, { preserveState: true });
    }

    function debouncedApply(
        newSearch: string,
        currentFilters: Record<string, string>,
    ) {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            apply(newSearch, currentFilters);
        }, 300);
    }

    function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>) {
        const val = e.target.value;
        setSearch(val);
        debouncedApply(val, activeFilters);
    }

    function handleFilterChange(key: string, val: string) {
        const newFilters = { ...activeFilters, [key]: val };
        setActiveFilters(newFilters);
        // Filter select: langsung apply tanpa debounce (pilihan diskrit)
        apply(search, newFilters);
    }

    function reset() {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        setSearch('');
        setActiveFilters({});
        router.get(baseRoute, {}, { preserveState: true });
    }

    const hasFilters = search || Object.values(activeFilters).some(Boolean);

    return (
        <div className="flex flex-wrap items-end gap-2">
            <div className="relative">
                <Search className="absolute top-1/2 left-2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    className="pl-8"
                    placeholder={searchPlaceholder}
                    value={search}
                    onChange={handleSearchChange}
                />
            </div>
            {filters.map((f) => (
                <Select
                    key={f.key}
                    value={activeFilters[f.key] ?? ''}
                    onValueChange={(v) => handleFilterChange(f.key, v)}
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder={f.label} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Semua</SelectItem>
                        {f.options.map((o) => (
                            <SelectItem key={o.value} value={o.value}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ))}
            {hasFilters && (
                <Button variant="ghost" size="sm" onClick={reset}>
                    <X className="mr-1 size-4" /> Reset
                </Button>
            )}
        </div>
    );
}
