<form>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="timezone" class="form-label">Fuso Horário</label>
            <select class="form-select" id="timezone">
                <option value="America/Sao_Paulo">Brasília (GMT-3)</option>
                <option value="America/Manaus">Manaus (GMT-4)</option>
                <option value="America/Belem">Belém (GMT-3)</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="language" class="form-label">Idioma</label>
            <select class="form-select" id="language">
                <option value="pt_BR">Português (Brasil)</option>
                <option value="en_US">English (US)</option>
                <option value="es_ES">Español</option>
            </select>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-2"></i>Salvar Alterações
        </button>
    </div>
</form>
