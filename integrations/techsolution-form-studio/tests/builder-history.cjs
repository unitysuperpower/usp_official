// Run with: node tests/builder-history.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const controls = [
    {id:'tsfs-setting-btn-text', type:'text', value:'Submit'},
    {id:'tsfs-setting-limit-submissions', type:'checkbox', checked:false},
    {id:'tsfs-theme-primary-color', type:'text', value:'#4f46e5'},
    {id:'tsfs-theme-primary-picker', type:'color', value:'#4f46e5'}
];
const elements = Object.fromEntries(controls.map(control => [control.id,control]));
elements['tsfs-form-title-input'] = {value:'Contact', type:'text'};
const visible = {};
const textValues = {};
function $(selector) {
    const element = typeof selector === 'object' ? selector : elements[String(selector).replace(/^#/, '')];
    const item = {
        ready() {},
        each(callback) {
            if (String(selector).startsWith('#tsfs-panel-form-settings input[id]')) controls.forEach(control => callback.call(control));
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
const document = {getElementById(id) {return elements[id];}, documentElement: {classList: {add() {}}}};
const context = {jQuery:$, document, window:{tsfsBuilderData:{}}, setTimeout() {return 1;}, clearTimeout() {}};
const src = fs.readFileSync(process.env.TSFS_BUILDER_FILE || 'admin/assets/js/tsfs-builder.js','utf8')
    .replace(/\$\(document\)\.ready\(function\s*\(\)\s*\{/, 'window.testBuilder = TSFSBuilder;\n$(document).ready(function() {');
vm.runInNewContext(src,context);
const builder = context.window.testBuilder;
builder.fields = [{id:'a',type:'text',label:'Name'}];
builder.renderCanvas = builder.renderInspector = builder.selectField = builder.showToast = () => {};
let themeRefreshes = 0;
builder.applyThemeLive = () => {themeRefreshes++;};
builder.savedState = builder.getCurrentState();
builder.takeHistorySnapshot('Initial');
elements['tsfs-setting-btn-text'].value = 'Send';
elements['tsfs-setting-limit-submissions'].checked = true;
elements['tsfs-theme-primary-color'].value = '#123456';
elements['tsfs-theme-primary-picker'].value = '#123456';
builder.scheduleHistorySnapshot('Edit settings');
// Undo before the debounce timer fires must capture and undo the pending edit.
builder.undo();
assert.equal(elements['tsfs-setting-btn-text'].value,'Submit');
assert.equal(elements['tsfs-setting-limit-submissions'].checked,false);
assert.equal(elements['tsfs-theme-primary-color'].value,'#4f46e5');
assert.equal(elements['tsfs-theme-primary-picker'].value,'#4f46e5');
assert.equal(visible['#tsfs-limit-entries-wrap'],false);
assert.equal(textValues['#tsfs-canvas-submit-preview'],'Submit');
assert.equal(builder.pendingHistoryLabel,null);
assert.equal(builder.hasUnsavedChanges(),false);
builder.redo();
assert.equal(elements['tsfs-setting-btn-text'].value,'Send');
assert.equal(elements['tsfs-setting-limit-submissions'].checked,true);
assert.equal(elements['tsfs-theme-primary-color'].value,'#123456');
assert.equal(visible['#tsfs-limit-entries-wrap'],true);
assert.ok(themeRefreshes >= 2);
assert.equal(builder.hasUnsavedChanges(),true);
builder.undo();
const stackLength = builder.historyStack.length;
builder.takeHistorySnapshot('No change');
assert.equal(builder.historyStack.length,stackLength); // No-op snapshots preserve redo.
builder.redo();
assert.equal(elements['tsfs-setting-btn-text'].value,'Send');
builder.undo();
elements['tsfs-form-title-input'].value = 'New title';
builder.scheduleHistorySnapshot('Title');
builder.redo(); // A new pending edit invalidates the old redo branch.
assert.equal(elements['tsfs-form-title-input'].value,'New title');
assert.equal(builder.historyIndex,builder.historyStack.length - 1);
builder.undo();
assert.equal(elements['tsfs-form-title-input'].value,'Contact');
for (let i=0; i<40; i++) {
    elements['tsfs-setting-btn-text'].value = 'Submit ' + i;
    builder.takeHistorySnapshot('Edit button');
}
assert.equal(builder.historyStack.length,35);
assert.equal(builder.historyIndex,34);
console.log('Passed full settings undo/redo, pending edits, theme refresh, title restoration, redo branches, no-op snapshots, and history limits.');
