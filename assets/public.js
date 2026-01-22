document.addEventListener('DOMContentLoaded', function () {

    function evaluateRules(rules, values) {
        // OR groups
        return rules.some(group => {
            // AND rules inside group
            return group.rules.every(rule => {

                const fieldValue = values[rule.field] || '';

                //  IMPORTANT: empty value = condition fail
                if (fieldValue === '') {
                    return false;
                }

                if (rule.operator === 'is') {
                    return fieldValue === rule.value;
                }

                if (rule.operator === 'is_not') {
                    return fieldValue !== rule.value;
                }

                return false;
            });
        });
    }

    function getFormValues(form) {
        const values = {};
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(el => {
            if (el.type === 'radio') {
                if (el.checked) values[el.name] = el.value;
            } else {
                values[el.name] = el.value;
            }
        });

        return values;
    }

    document.querySelectorAll('.pfb-form').forEach(form => {

        const conditionalFields = form.querySelectorAll('.pfb-field[data-rules]');

        function updateVisibility() {
            const values = getFormValues(form);

            conditionalFields.forEach(field => {
                const rules = JSON.parse(field.dataset.rules);

                const shouldShow = evaluateRules(rules, values);

                field.style.display = shouldShow ? '' : 'none';
            });
        }

        // initial hide
        updateVisibility();

        // listen changes
        form.addEventListener('change', updateVisibility);
        form.addEventListener('input', updateVisibility);
    });

});
