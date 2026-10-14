// Run with: node tests/builder-history.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const controls = [
    {id:'usp-setting-btn-text', type:'text', value:'Submit'},
    {id:'usp-setting-limit-submissions', type:'checkbox', checked:false},
    {id:'usp-theme-primary-color', type:'text', value:'#4f46e5'},
    {id:'usp-theme-primary-picker', type:'color', value:'#4f46e5'}
];
const elements = Object.fromEntries(controls.map(control => [control.id,control]));
elements['usp-form-title-input'] = {value:'Contact', type:'text'};
const visible = {};
const textValues = {};
function $(selector) {
    const element = typeof selector === 'object' ? selector : elements[String(selector).replace(/^#/, '')];
    const item = {
        ready() {},
        each(callback) {
            if (String(selector).startsWith('#usp-panel-form-settings input[id]')) controls.forEach(control => callback.call(control));
            return item;
        },
        val(value) { if (arguments.length) { if (element) element.value = value; return item; } return element ? element.value : ''; },
        is(test) { return test === ':checkbox' ? element?.type === 'checkbox' : !!element?.checked; },
        prop(key,value) { if (element) element[key] = value; return item; },
        attr() {return item;}, data() {return 'theme-style';}, filter() {return item;}, trigger() {return item;},
        text(value) {textValues[selector] = value; return item;}, stop() {return item;},
        toggle(value) {visible[selector] = value; return item;}, toggleClass() {return item;}
    };
    return item;
}
const document = {getElementById(id) {return elements[id];}};
const context = {jQuery:$, document, window:{uspBuilderData:{}}, setTimeout() {return 1;}, clearTimeout() {}};
const src = fs.readFileSync(process.env.USP_BUILDER_FILE || 'admin/assets/js/usp-builder.js','utf8')
    .replace(/\$\(document\)\.ready\(function\s*\(\)\s*\{/, 'window.testBuilder = USPBuilder;\n$(document).ready(function() {');
vm.runInNewContext(src,context);
const builder = context.window.testBuilder;
builder.fields = [{id:'a',type:'text',label:'Name'}];
builder.renderCanvas = builder.renderInspector = builder.selectField = builder.showToast = () => {};
let themeRefreshes = 0;
builder.applyThemeLive = () => {themeRefreshes++;};
builder.savedState = builder.getCurrentState();
builder.takeHistorySnapshot('Initial');
elements['usp-setting-btn-text'].value = 'Send';
elements['usp-setting-limit-submissions'].checked = true;
elements['usp-theme-primary-color'].value = '#123456';
elements['usp-theme-primary-picker'].value = '#123456';
builder.scheduleHistorySnapshot('Edit settings');
// Undo before the debounce timer fires must capture and undo the pending edit.
builder.undo();
assert.equal(elements['usp-setting-btn-text'].value,'Submit');
assert.equal(elements['usp-setting-limit-submissions'].checked,false);
assert.equal(elements['usp-theme-primary-color'].value,'#4f46e5');
assert.equal(elements['usp-theme-primary-picker'].value,'#4f46e5');
assert.equal(visible['#usp-limit-entries-wrap'],false);
assert.equal(textValues['#usp-canvas-submit-preview'],'Submit');
assert.equal(builder.pendingHistoryLabel,null);
assert.equal(builder.hasUnsavedChanges(),false);
builder.redo();
assert.equal(elements['usp-setting-btn-text'].value,'Send');
assert.equal(elements['usp-setting-limit-submissions'].checked,true);
assert.equal(elements['usp-theme-primary-color'].value,'#123456');
assert.equal(visible['#usp-limit-entries-wrap'],true);
assert.ok(themeRefreshes >= 2);
assert.equal(builder.hasUnsavedChanges(),true);
builder.undo();
const stackLength = builder.historyStack.length;
builder.takeHistorySnapshot('No change');
assert.equal(builder.historyStack.length,stackLength); // No-op snapshots preserve redo.
builder.redo();
assert.equal(elements['usp-setting-btn-text'].value,'Send');
builder.undo();
elements['usp-form-title-input'].value = 'New title';
builder.scheduleHistorySnapshot('Title');
builder.redo(); // A new pending edit invalidates the old redo branch.
assert.equal(elements['usp-form-title-input'].value,'New title');
assert.equal(builder.historyIndex,builder.historyStack.length - 1);
builder.undo();
assert.equal(elements['usp-form-title-input'].value,'Contact');
for (let i=0; i<40; i++) {
    elements['usp-setting-btn-text'].value = 'Submit ' + i;
    builder.takeHistorySnapshot('Edit button');
}
assert.equal(builder.historyStack.length,35);
assert.equal(builder.historyIndex,34);
console.log('Passed full settings undo/redo, pending edits, theme refresh, title restoration, redo branches, no-op snapshots, and history limits.');
