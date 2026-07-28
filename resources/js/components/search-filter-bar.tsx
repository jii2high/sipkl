import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useState } from 'react';
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

    function apply() {
        const query: Record<string, string> = {};

        if (search) {
            query.search = search;
        }

        Object.entries(activeFilters).forEach(([k, v]) => {
            if (v) {
                query[k] = v;
            }
        });
        router.get(baseRoute, query, { preserveState: true });
    }

    function reset() {
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
                    onChange={(e) => setSearch(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && apply()}
                />
            </div>
            {filters.map((f) => (
                <Select
                    key={f.key}
                    value={activeFilters[f.key] ?? ''}
                    onValueChange={(v) =>
                        setActiveFilters({ ...activeFilters, [f.key]: v })
                    }
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
            <Button size="sm" onClick={apply}>
                Cari
            </Button>
            {hasFilters && (
                <Button variant="ghost" size="sm" onClick={reset}>
                    <X className="mr-1 size-4" /> Reset
                </Button>
            )}
        </div>
    );
}
