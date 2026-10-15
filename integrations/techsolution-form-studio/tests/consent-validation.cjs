const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const parser = require('@babel/parser');
const source = fs.readFileSync(__dirname + '/../public/assets/js/tsfs-public.js', 'utf8');
let target;
function visit(node) {
    if (!node || typeof node !== 'object') return;
    if (node.type === 'FunctionDeclaration' && node.id.name === 'validateContainerFields') target = node;
    Object.values(node).forEach(value => {
        if (Array.isArray(value)) value.forEach(visit);
        else if (value && typeof value === 'object') visit(value);
    });
}
visit(parser.parse(source));
assert.ok(target, 'Production validator must exist');
const context = {window: {}, $: value => value, markFieldError: () => {}, TSFSForms: {validators: []}, t: (key, fallback) => fallback};
vm.runInNewContext(source.slice(target.start, target.end), context);
function run(required, checked, disabled = false) {
    const wrap = {
        data: () => 'privacy',
        hasClass: name => name === 'tsfs-field-consent',
        find: selector => ({
            length: selector === '.tsfs-required-mark' ? Number(required) : 1,
            not() { return this; },
            first() { return this; },
            prop: () => disabled,
            is: () => checked,
            val: () => undefined // Consent intentionally has no text input.
        })
    };
    return context.validateContainerFields({find: () => ({each: callback => callback.call(wrap)})});
}
assert.equal(run(true, true), false, 'Checked required consent must pass');
assert.equal(run(true, false), true, 'Unchecked required consent must fail');
assert.equal(run(false, false), false, 'Optional consent must stay optional');
assert.equal(run(true, false, true), false, 'Disabled conditional consent must not block the browser');
console.log('Passed production consent validator: checked, unchecked, optional and disabled.');
