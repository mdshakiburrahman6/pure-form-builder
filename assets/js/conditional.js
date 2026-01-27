document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('pfb-field-type');
    const fieldsetRows = document.querySelectorAll('.pfb-fieldset-only');

    function toggleFieldsetOptions() {
        const val = typeSelect.value;
        fieldsetRows.forEach(row => {
            row.style.display = (val === 'fieldset') ? 'table-row' : 'none';
        });
    }

    toggleFieldsetOptions();
    typeSelect.addEventListener('change', toggleFieldsetOptions);
});