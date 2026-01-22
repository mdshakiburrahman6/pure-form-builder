function pfbApplyConditionalLogic() {

    document.querySelectorAll('.pfb-field').forEach(field => {

        const rulesData = field.dataset.rules;
        if (!rulesData) return;

        let rules;
        try {
            rules = JSON.parse(rulesData);
        } catch (e) {
            return;
        }

        if (!rules.show_if) return;

        const trigger = document.querySelector(
            `[name="${rules.show_if.field}"]`
        );

        if (!trigger) return;

        if (
            rules.show_if.values &&
            rules.show_if.values.includes(trigger.value)
        ) {
            field.style.display = 'block';
        } else {
            field.style.display = 'none';
        }

    });
}

/* Run on page load */
document.addEventListener('DOMContentLoaded', function () {
    pfbApplyConditionalLogic();
});

/* Run on change */
document.addEventListener('change', function () {
    pfbApplyConditionalLogic();
});
