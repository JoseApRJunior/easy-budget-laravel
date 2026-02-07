@props(['periods', 'currentPeriod', 'id' => 'periodSelect', 'onchange' => 'changePeriod()'])

<div {{ $attributes->merge(['class' => 'input-group input-group-sm w-auto shadow-sm border rounded-2 transition-all hover-shadow-sm']) }}
     style="overflow: hidden; background-color: var(--hover-bg); ">
    <span class="input-group-text border-0 text-muted px-2 pe-1" ">
        <i class="bi bi-calendar3"></i>
    </span>
    <select class="form-select border-0  text-dark cursor-pointer shadow-none ps-1"
            id="{{ $id }}"
            onchange="{{ $onchange }}"
            style="min-width: 160px;  outline: none !important;">
        @foreach ($periods as $key => $label)
            <option value="{{ $key }}" {{ $currentPeriod === $key ? 'selected' : '' }} class=" fw-normal">
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>

<style>
    /* Remove a linha azul de foco do Bootstrap */
    #{{ $id }}:focus {
        background-color: transparent !important;
        box-shadow: none !important;
        outline: none !important;
    }
    /* Garante que o container mude de borda no hover/foco se desejar, ou mantenha fixo */
    .input-group:focus-within {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.15) !important;
    }
</style>
