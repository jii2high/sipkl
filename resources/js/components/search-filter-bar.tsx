import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

export type SearchFilterBarFilter = {
    name: string;
    value: string | null;
    options: { value: string; label: string }[];
};

export type SearchFilterBarProps = {
    search?: string;
    filters?: SearchFilterBarFilter[];
    route: string;
};

export default function SearchFilterBar({
    search = '',
    filters = [],
    route,
}: SearchFilterBarProps) {
    const [query, setQuery] = useState(search);
    const [selectedFilters, setSelectedFilters] = useState<
        Record<string, string | null>
    >(
        Object.fromEntries(
            filters.map((filter) => [filter.name, filter.value ?? '']),
        ),
    );

    function handleSearch(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const queryParams = { search: query } as Record<
            string,
            string | undefined
        >;

        filters.forEach((filter) => {
            const value = selectedFilters[filter.name];

            if (value) {
                queryParams[filter.name] = value;
            }
        });

        router.get(route, queryParams, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function updateFilter(name: string, value: string) {
        setSelectedFilters((previous) => ({
            ...previous,
            [name]: value,
        }));
    }

    return (
        <form
            onSubmit={handleSearch}
            className="space-y-4 rounded-xl border border-border bg-background p-4 shadow-sm sm:flex sm:items-end sm:space-y-0 sm:space-x-4"
        >
            <div className="grid flex-1 gap-2">
                <Label htmlFor="search">Search</Label>
                <div className="flex gap-2">
                    <Input
                        id="search"
                        value={query}
                        onChange={(event) =>
                            setQuery(event.currentTarget.value)
                        }
                        placeholder="Search..."
                        className="min-w-0 flex-1"
                    />
                    <Button type="submit" className="min-w-[120px]">
                        Search
                    </Button>
                </div>
            </div>

            {filters.length > 0 && (
                <div className="grid gap-2 sm:min-w-[240px]">
                    <Label htmlFor="filters">Filters</Label>
                    <div className="grid gap-2">
                        {filters.map((filter) => (
                            <Select
                                key={filter.name}
                                value={selectedFilters[filter.name] ?? ''}
                                onValueChange={(value) =>
                                    updateFilter(filter.name, value)
                                }
                            >
                                <SelectTrigger
                                    id={filter.name}
                                    className="w-full"
                                >
                                    <SelectValue placeholder={filter.name} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All</SelectItem>
                                    {filter.options.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ))}
                    </div>
                </div>
            )}
        </form>
    );
}
