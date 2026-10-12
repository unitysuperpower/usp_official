/** Local country -> province -> city lookup. No visitor address is sent externally. */
(function() {
    'use strict';
    var requests = new Map();
    var labels = (window.uspAddressData || {}).i18n || {};
    function label(key, fallback) { return labels[key] || fallback; }
    function loadCountry(code) {
        if (!/^[A-Z]{2}$/.test(code)) return Promise.reject(new Error('Invalid country'));
        if (!requests.has(code)) {
            var base = (window.uspAddressData || {}).data_url || '';
            var pending = fetch(base + code + '.json', { credentials: 'same-origin' }).then(function(response) {
                if (!response.ok) throw new Error('Location data unavailable');
                return response.json();
            }).then(function(data) {
                if (data.country !== code || !Array.isArray(data.states)) throw new Error('Invalid location data');
                return data;
            }).catch(function(error) { requests.delete(code); throw error; });
            requests.set(code, pending);
        }
        return requests.get(code);
    }
    function init(root) {
        (root || document).querySelectorAll('.usp-address-control').forEach(function(control) {
            if (control.uspAddress) return;
            var country = control.querySelector('.usp-address-country');
            var state = control.querySelector('.usp-address-state');
            var city = control.querySelector('.usp-address-city');
            var stateOther = control.querySelector('.usp-address-state-other');
            var cityOther = control.querySelector('.usp-address-city-other');
            var status = control.querySelector('.usp-address-status');
            var retry = control.querySelector('.usp-address-retry');
            var required = control.dataset.required === 'true';
            var data = null;
            var generation = 0;
            var loading = false;
            function option(select, value, text) { select.add(new Option(text, value)); }
            function reset(select, text) { select.textContent = ''; option(select, '', text); select.value = ''; }
            function setRequired(input, value) { input.required = value; input.classList.toggle('usp-address-required', value); }
            function refresh() {
                var disabled = country.disabled;
                state.disabled = disabled || loading || !data;
                city.disabled = disabled || loading || !data || !state.value;
                stateOther.hidden = state.value !== '__other';
                cityOther.hidden = city.value !== '__other';
                stateOther.disabled = disabled || stateOther.hidden;
                cityOther.disabled = disabled || cityOther.hidden;
                setRequired(country, required);
                setRequired(state, required && !state.disabled);
                setRequired(city, required && !city.disabled);
                setRequired(stateOther, !stateOther.disabled);
                setRequired(cityOther, !cityOther.disabled);
                control.querySelectorAll('[data-address-part]').forEach(function(part) {
                    var shown = control.dataset['show' + (part.dataset.addressPart === 'street' ? 'Street' : 'Postal')] !== 'false';
                    part.hidden = !shown;
                    part.querySelectorAll('input').forEach(function(input) {
                        input.disabled = disabled || !shown;
                        setRequired(input, shown && required && input.autocomplete === 'address-line1');
                    });
                });
                if (country.uspCountryPicker) country.uspCountryPicker.sync();
            }
            function fillCities() {
                reset(city, label('select_city', 'Select a city or town'));
                cityOther.value = '';
                var region = data && data.states.find(function(row) { return row.id === state.value; });
                if (region) region.cities.forEach(function(name) { option(city, name, name); });
                if (state.value) option(city, '__other', label('other_city', 'City not listed? Enter manually'));
                if (state.value && (!region || !region.cities.length)) city.value = '__other';
                status.textContent = !state.value ? '' :
                    (region && region.cities.length ? label('city_help', 'Choose a city, or enter it manually if it is not listed.') :
                        label('city_manual', 'No city list is available for this region. Enter your city or town.'));
                refresh();
            }
            function changeCountry() {
                var code = country.value;
                var currentGeneration = ++generation;
                data = null;
                stateOther.value = cityOther.value = '';
                reset(state, code ? label('loading', 'Loading regions…') : label('country_first', 'Select a country first'));
                reset(city, label('state_first', 'Select a province first'));
                loading = !!code;
                control.dataset.loading = String(loading);
                retry.hidden = true;
                status.textContent = loading ? label('loading', 'Loading regions…') : '';
                refresh();
                if (!code) return Promise.resolve();
                return loadCountry(code).then(function(result) {
                    if (generation !== currentGeneration || country.value !== code) return;
                    data = result;
                    loading = false;
                    control.dataset.loading = 'false';
                    reset(state, label('select_state', 'Select a province, state, or region'));
                    data.states.forEach(function(region) { option(state, region.id, region.name); });
                    option(state, '__other', label('other_state', 'Region not listed? Enter manually'));
                    if (!data.states.length) {
                        state.value = '__other';
                        fillCities();
                        status.textContent = label('region_manual', 'No region list is available. Enter your region and city manually.');
                    } else status.textContent = label('state_help', 'Select a province or state to see its cities.');
                    refresh();
                }).catch(function() {
                    if (generation !== currentGeneration) return;
                    loading = false;
                    control.dataset.loading = 'false';
                    status.textContent = label('error', 'Locations could not be loaded. Please retry.');
                    retry.hidden = false;
                    reset(state, label('retry_hint', 'Retry loading regions'));
                    refresh();
                });
            }
            country.addEventListener('change', changeCountry);
            state.addEventListener('change', fillCities);
            city.addEventListener('change', refresh);
            retry.addEventListener('click', changeCountry);
            control.uspAddress = { refresh: refresh, reset: changeCountry };
            refresh();
            if (country.value) changeCountry();
        });
    }
    window.USPAddress = { init: init, loadCountry: loadCountry, refresh: function(root) {
        root.querySelectorAll('.usp-address-control').forEach(function(control) { if (control.uspAddress) control.uspAddress.refresh(); });
    } };
    document.addEventListener('reset', function(event) {
        setTimeout(function() {
            event.target.querySelectorAll('.usp-address-control').forEach(function(control) { if (control.uspAddress) control.uspAddress.reset(); });
        }, 0);
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function() { init(); });
    else init();
})();
