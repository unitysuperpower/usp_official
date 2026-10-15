/** Country picker: progressive enhancement of the real submission select. */
(function() {
    'use strict';
    var nextId = 0;
    var openPicker = null;
    function normalize(value) {
        var text = String(value).toLowerCase().trim();
        return text.normalize ? text.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : text;
    }
    function filterOptions(options, query) {
        query = normalize(query);
        return options.filter(function(option) {
            return !query || (option.value && normalize(option.text).indexOf(query) !== -1);
        });
    }
    function init(root) {
        (root || document).querySelectorAll('.tsfs-country-select').forEach(function(select) {
            if (select.tsfsCountryPicker) return;
            var control = select.closest('.tsfs-country-control');
            if (!control) return;
            var labels = window.tsfsCountryData || {};
            var id = 'tsfs-country-picker-' + (++nextId);
            var options = Array.from(select.options).map(function(option) {
                return { value: option.value, text: option.text };
            });
            var fieldLabel = select.getAttribute('aria-label') ||
                (select.labels && select.labels[0] ? select.labels[0].textContent.trim() : 'Country');
            var trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'tsfs-country-trigger';
            if (select.getAttribute('style')) trigger.setAttribute('style', select.getAttribute('style'));
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.setAttribute('aria-controls', id + '-list');
            var text = document.createElement('span');
            text.className = 'tsfs-country-trigger-text';
            trigger.appendChild(text);
            var chevron = document.createElement('span');
            chevron.className = 'tsfs-country-chevron';
            chevron.setAttribute('aria-hidden', 'true');
            trigger.appendChild(chevron);
            var panel = document.createElement('div');
            panel.className = 'tsfs-country-dropdown';
            panel.hidden = true;
            var list = document.createElement('div');
            list.className = 'tsfs-country-options';
            list.id = id + '-list';
            list.setAttribute('role', 'listbox');
            list.setAttribute('aria-label', fieldLabel);
            panel.appendChild(list);
            // Search is part of the dropdown, below the scrollable country list.
            var searchWrap = document.createElement('div');
            searchWrap.className = 'tsfs-country-dropdown-search';
            var search = document.createElement('input');
            search.type = 'search';
            search.className = 'tsfs-country-search';
            search.placeholder = labels.search || 'Search countries…';
            search.setAttribute('aria-label', labels.search || 'Search countries');
            search.setAttribute('role', 'combobox');
            search.setAttribute('aria-autocomplete', 'list');
            search.setAttribute('aria-controls', list.id);
            search.setAttribute('aria-expanded', 'false');
            search.autocomplete = 'off';
            searchWrap.appendChild(search);
            var status = document.createElement('span');
            status.className = 'tsfs-country-search-status';
            status.setAttribute('role', 'status');
            searchWrap.appendChild(status);
            panel.appendChild(searchWrap);
            control.appendChild(trigger);
            control.appendChild(panel);
            select.hidden = true;
            select.classList.add('tsfs-country-native');
            select.tabIndex = -1;
            var buttons = [];
            var activeIndex = -1;
            function sync() {
                var option = select.options[select.selectedIndex] || options[0];
                text.textContent = option ? option.text : '';
                trigger.setAttribute('aria-label', fieldLabel + ': ' + text.textContent);
                trigger.setAttribute('aria-required', String(select.required));
                trigger.disabled = select.disabled;
                trigger.classList.toggle('is-placeholder', !select.value);
            }
            function activate(index) {
                activeIndex = index;
                buttons.forEach(function(button, i) { button.classList.toggle('is-active', i === index); });
                if (buttons[index]) {
                    search.setAttribute('aria-activedescendant', buttons[index].id);
                    buttons[index].scrollIntoView({ block: 'nearest' });
                } else search.removeAttribute('aria-activedescendant');
            }
            function choose(value) {
                select.value = value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                trigger.removeAttribute('aria-invalid');
                close(true);
            }
            function render() {
                list.textContent = '';
                buttons = [];
                var filtered = filterOptions(options, search.value);
                filtered.forEach(function(option, index) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'tsfs-country-option';
                    button.id = id + '-option-' + index;
                    button.tabIndex = -1;
                    button.setAttribute('role', 'option');
                    button.setAttribute('aria-selected', String(option.value === select.value));
                    button.textContent = option.text;
                    button.addEventListener('click', function() { choose(option.value); });
                    list.appendChild(button);
                    buttons.push(button);
                });
                if (!filtered.length) {
                    var empty = document.createElement('p');
                    empty.className = 'tsfs-country-empty';
                    empty.textContent = labels.empty || 'No matching countries. Try another name.';
                    list.appendChild(empty);
                }
                status.textContent = search.value ?
                    (labels.matches || '%d countries found').replace('%d', filtered.filter(function(o) { return o.value; }).length) : '';
                var selectedIndex = filtered.findIndex(function(option) { return option.value === select.value; });
                activate(selectedIndex >= 0 ? selectedIndex : (buttons.length ? 0 : -1));
            }
            function close(returnFocus) {
                panel.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                search.setAttribute('aria-expanded', 'false');
                control.classList.remove('is-open');
                if (openPicker === picker) openPicker = null;
                if (returnFocus) trigger.focus();
            }
            function open() {
                if (select.disabled) return;
                if (openPicker && openPicker !== picker) openPicker.close(false);
                sync();
                search.value = '';
                panel.hidden = false;
                control.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                search.setAttribute('aria-expanded', 'true');
                openPicker = picker;
                render();
                search.focus({ preventScroll: true });
            }
            var picker = { close: close, sync: sync, control: control };
            select.tsfsCountryPicker = picker;
            trigger.addEventListener('click', function() { panel.hidden ? open() : close(false); });
            trigger.addEventListener('keydown', function(event) {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); open(); }
            });
            search.addEventListener('input', render);
            search.addEventListener('keydown', function(event) {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (buttons.length) activate((activeIndex + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length);
                } else if (event.key === 'Enter') {
                    event.preventDefault();
                    if (buttons[activeIndex]) buttons[activeIndex].click();
                } else if (event.key === 'Escape') {
                    event.preventDefault(); event.stopPropagation(); close(true);
                } else if (event.key === 'Tab') close(false);
            });
            Array.from(select.labels || []).forEach(function(label) {
                label.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    open();
                });
            });
            select.addEventListener('change', sync);
            select.addEventListener('invalid', function(event) {
                event.preventDefault();
                trigger.setAttribute('aria-invalid', 'true');
                open();
            });
            sync();
        });
    }
    window.TSFSCountry = { init: init, filterOptions: filterOptions };
    document.addEventListener('click', function(event) {
        if (openPicker && !openPicker.control.contains(event.target)) openPicker.close(false);
    });
    document.addEventListener('focusin', function(event) {
        if (openPicker && !openPicker.control.contains(event.target)) openPicker.close(false);
    });
    document.addEventListener('reset', function(event) {
        setTimeout(function() {
            event.target.querySelectorAll('.tsfs-country-select').forEach(function(select) {
                if (select.tsfsCountryPicker) { select.tsfsCountryPicker.close(false); select.tsfsCountryPicker.sync(); }
            });
        }, 0);
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function() { init(); });
    else init();
})();
