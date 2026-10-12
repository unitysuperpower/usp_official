// Run with: node tests/builder-outline.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const $ = () => ({ready() {}});
const context = {jQuery: $, document: {}, setTimeout, clearTimeout, window: {uspBuilderData: {
    field_types: {country: {label: 'Country / Nationality'}, email: {label: 'Email Address'}}
}}};
const source = fs.readFileSync('admin/assets/js/usp-builder.js', 'utf8')
    .replace('    $(document).ready(function() {', '    window.testBuilder = USPBuilder;\n    $(document).ready(function() {');
vm.runInNewContext(source, context);
const builder = context.window.testBuilder;
builder.fields = [
    {id:'a',label:'Your name',name:'full_name',type:'text'},
    {id:'b',label:'Where are you from?',name:'citizenship',type:'country'},
    {id:'c',label:'Contact',name:'reply_address',type:'email'}
];
function ids(query) {return Array.from(builder.getOutlineItems(query), item => item.field.id);}
assert.deepEqual(ids('  YOUR NAME  '), ['a']);
assert.deepEqual(ids('citizenship'), ['b']);
assert.deepEqual(ids('nationality'), ['b']);
assert.deepEqual(ids('email'), ['c']);
assert.deepEqual(ids('unknown'), []);
assert.equal(builder.getOutlineItems('nationality')[0].position, 2);
// After moving a field, filtered positions must refer to its new place in the form.
builder.renderCanvas = () => {};
builder.selectField = () => {};
builder.takeHistorySnapshot = () => {};
builder.moveField('b', -1);
assert.equal(builder.getOutlineItems('citizenship')[0].position, 1);
assert.deepEqual(ids(''), ['b','a','c']);
builder.fields[0].label = 'Updated nationality';
assert.deepEqual(ids('Updated'), ['b']);
assert.deepEqual(ids('Where are you'), []);
builder.fields = [];
assert.deepEqual(ids(''), []);
assert.equal(builder.escapeAttribute('Name "quoted" <value>'), 'Name &quot;quoted&quot; &lt;value&gt;');
console.log('Passed outline searches, field positions after reordering, label updates, empty forms, and quoted label checks.');
