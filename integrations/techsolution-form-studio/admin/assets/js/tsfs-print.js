/* Print-ready submissions report: print and close buttons, and open the print dialog on load. */
(function () {
    'use strict';
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-tsfs-print]')) {
            window.print();
        } else if (event.target.closest('[data-tsfs-close]')) {
            window.close();
        }
    });
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 300);
    });
})();
