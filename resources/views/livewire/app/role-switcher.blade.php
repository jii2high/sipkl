@if (count($roles) > 1)
    <div class="role-switcher">
        <label for="active-role">Peran aktif</label>
        <select id="active-role" wire:change="switch($event.target.value)">
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(session('active_role') === $role)>{{ $role }}</option>
            @endforeach
        </select>
    </div>
@endif

<style>
    .role-switcher { display: inline-flex; align-items: center; gap: 8px; }
    .role-switcher label { font-size: 0.8rem; color: #64748b; }
    .role-switcher select { min-height: 36px; border: 1px solid #dfe6ef; border-radius: 8px; padding: 0 8px; }
</style>
