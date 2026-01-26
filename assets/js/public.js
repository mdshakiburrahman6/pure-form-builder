// document.addEventListener('DOMContentLoaded', function () {

//     function evaluateRules(rules, values) {
//         // OR groups
//         return rules.some(group => {
//             // AND rules inside group
//             return group.rules.every(rule => {

//                 const fieldValue = values[rule.field] || '';

//                 //  IMPORTANT: empty value = condition fail
//                 if (fieldValue === '') {
//                     return false;
//                 }

//                 if (rule.operator === 'is') {
//                     return fieldValue === rule.value;
//                 }

//                 if (rule.operator === 'is_not') {
//                     return fieldValue !== rule.value;
//                 }

//                 return false;
//             });
//         });
//     }

//     function getFormValues(form) {
//         const values = {};
//         const inputs = form.querySelectorAll('input, select, textarea');

//         inputs.forEach(el => {
//             if (el.type === 'radio') {
//                 if (el.checked) values[el.name] = el.value;
//             } else {
//                 values[el.name] = el.value;
//             }
//         });

//         return values;
//     }

//     document.querySelectorAll('.pfb-form').forEach(form => {

//         const conditionalFields = form.querySelectorAll('.pfb-field[data-rules]');

//         function updateVisibility() {
//             const values = getFormValues(form);

//             conditionalFields.forEach(field => {
//                 const rules = JSON.parse(field.dataset.rules);

//                 const shouldShow = evaluateRules(rules, values);

//                 field.style.display = shouldShow ? '' : 'none';
//             });
//         }

//         // initial hide
//         updateVisibility();

//         // listen changes
//         form.addEventListener('change', updateVisibility);
//         form.addEventListener('input', updateVisibility);
//     });

// });

document.addEventListener('DOMContentLoaded', function () {

    function evaluateRules(rules, values) {
        return rules.some(group =>
            group.rules.every(rule => {
                const v = values[rule.field] || '';
                if (v === '') return false;
                if (rule.operator === 'is') return v === rule.value;
                if (rule.operator === 'is_not') return v !== rule.value;
                return false;
            })
        );
    }

    function getValues(form) {
        const values = {};
        form.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.type === 'radio') {
                if (el.checked) values[el.name] = el.value;
            } else {
                values[el.name] = el.value;
            }
        });
        return values;
    }

    function init(form) {

        const conditionalFields = form.querySelectorAll('.pfb-field[data-rules]');
        if (!conditionalFields.length) return;

        function update() {
            const values = getValues(form);

            conditionalFields.forEach(field => {
                const rules = JSON.parse(field.dataset.rules);
                const show = evaluateRules(rules, values);

                field.style.display = show ? '' : 'none';

                // required / disabled sync
                field.querySelectorAll('input,select,textarea').forEach(el => {
                    if (!el.dataset.req) el.dataset.req = el.required ? '1' : '0';

                    if (show) {
                        el.disabled = false;
                        el.required = el.dataset.req === '1';
                    } else {
                        el.disabled = true;
                        el.required = false;
                    }
                });
            });
        }

        update(); // 🔥 THIS FIXES ADMIN EDIT ISSUE
        form.addEventListener('change', update);
        form.addEventListener('input', update);
    }

    document
        .querySelectorAll('.pfb-form, .pfb-admin-form')
        .forEach(init);
});


 
/**
 * 
 * Auto Remove Form Submit Success Message
 * 
 */
setTimeout(() => {
    const msg = document.querySelector('.pfb-success');
    if (msg) msg.remove();
}, 4000); // 4 seconds