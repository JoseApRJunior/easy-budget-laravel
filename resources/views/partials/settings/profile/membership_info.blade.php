<div class="col-12">
    <div class="mb-3">
        <label class="small text-muted">Membro desde</label>
        <p class="mb-0">{{ auth()->user()->created_at->format( 'd/m/Y' ) }}</p>
    </div>
</div>
