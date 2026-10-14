/* CNIC format assistance; identity verification is not performed. */
(function () {
    'use strict';
    function format(value) {
        var digits = String(value).replace(/[^0-9]/g, '');
        if (digits.length > 13) return String(value); // Never silently truncate an invalid ID.
        return digits.slice(0, 5) + (digits.length > 5 ? '-' + digits.slice(5, 12) : '') + (digits.length > 12 ? '-' + digits.slice(12) : '');
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = format;
    if (typeof document === 'undefined') return;
    document.addEventListener('input', function (event) {
        var input = event.target;
        if (!input.matches('.usp-input-cnic')) return;
        var before = input.value;
        var position = input.selectionStart;
        var digitsBefore = before.slice(0, position).replace(/[^0-9]/g, '').length;
        // Reject letters instead of silently changing the submitted identity number.
        if (/[^0-9\-\s]/.test(before)) {
            input.setCustomValidity('Use 13 digits in the format 12345-1234567-1.');
            return;
        }
        input.value = format(before);
        var caret = 0, seen = 0;
        while (caret < input.value.length && seen < digitsBefore) {
            if (/[0-9]/.test(input.value[caret])) seen++;
            caret++;
        }
        input.setSelectionRange(caret, caret);
        input.setCustomValidity(input.value && !/^[0-9]{5}-[0-9]{7}-[0-9]$/.test(input.value) ? 'Use 13 digits in the format 12345-1234567-1.' : '');
    });
})();
