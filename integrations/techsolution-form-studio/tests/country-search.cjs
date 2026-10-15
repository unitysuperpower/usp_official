// Run with: node tests/country-search.cjs. Exercise the actual picker with a small DOM fixture.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
class Node {
    constructor(tag) { this.tag = tag; this.children = []; this.attributes = {}; this.listeners = {}; this.hidden = false; this.value = ''; this.className = ''; }
    get classList() { return {add: value => {this.className += ' ' + value;}, remove: value => {this.className = this.className.split(' ').filter(c => c !== value).join(' ');}, toggle: (value,on) => {this.classList.remove(value); if(on) this.classList.add(value);}}; }
    appendChild(child) {child.parent = this; this.children.push(child); return child;}
    set textContent(value) {this._text = String(value); this.children = [];}
    get textContent() {return this._text || this.children.map(c => c.textContent).join('');}
    setAttribute(key,value) {this.attributes[key] = String(value);}
    getAttribute(key) {return this.attributes[key] || null;}
    removeAttribute(key) {delete this.attributes[key];}
    addEventListener(type,callback) {(this.listeners[type] ||= []).push(callback);}
    dispatchEvent(event) {(this.listeners[event.type] || []).forEach(callback => callback(event));}
    click() {this.dispatchEvent({type:'click'});}
    focus() {document.activeElement = this;}
    scrollIntoView() {}
    contains(node) {return node === this || this.children.some(c => c.contains(node));}
    closest(selector) {return this.matches(selector) ? this : this.parent?.closest(selector);}
    matches(selector) {return this.className.split(' ').includes(selector.slice(1));}
    querySelectorAll(selector) {return this.children.flatMap(c => [...(c.matches(selector) ? [c] : []), ...c.querySelectorAll(selector)]);}
}
const document = new Node('document');
document.readyState = 'complete';
document.createElement = tag => new Node(tag);
const control = document.appendChild(new Node('div'));
control.className = 'tsfs-country-control';
const select = control.appendChild(new Node('select'));
select.className = 'tsfs-country-select';
select.required = true;
const label = new Node('label');
label.textContent = 'Nationality';
select.labels = [label];
select.options = [{value:'',text:'Select a country'}, {value:'Pakistan',text:'Pakistan'}, {value:'France',text:'France'}, {value:'Côte d’Ivoire',text:'Côte d’Ivoire'}];
select.value = 'Pakistan';
Object.defineProperty(select,'selectedIndex',{get() {return this.options.findIndex(option => option.value === this.value);}});
const window = {};
vm.runInNewContext(fs.readFileSync('assets/js/tsfs-country.js','utf8'), {document,window,Event,setTimeout: cb => cb()});
const get = name => control.querySelectorAll('.' + name)[0];
const trigger = get('tsfs-country-trigger');
const panel = get('tsfs-country-dropdown');
const search = get('tsfs-country-search');
const list = get('tsfs-country-options');
function query(value) {search.value = value; search.dispatchEvent({type:'input'});}
function key(value) {search.dispatchEvent({type:'keydown',key:value,preventDefault(){},stopPropagation(){}});}
assert.equal(select.hidden,true);
assert.equal(panel.hidden,true);
assert.equal(panel.children[0],list);
assert.equal(panel.children[1].children[0],search); // Search stays below the list, inside the panel.
trigger.click();
assert.equal(panel.hidden,false);
assert.equal(document.activeElement,search);
assert.equal(trigger.getAttribute('aria-expanded'),'true');
query(' fr ');
assert.equal(list.children.length,1);
assert.equal(list.children[0].textContent,'France');
assert.equal(select.value,'Pakistan'); // Searching must not replace an answer.
assert.equal(select.options.length,4); // Native submission choices stay intact.
key('Enter');
assert.equal(select.value,'France');
assert.equal(panel.hidden,true);
assert.equal(document.activeElement,trigger);
assert.equal(get('tsfs-country-trigger-text').textContent,'France');
trigger.click();
query('cote');
assert.equal(list.children[0].textContent,'Côte d’Ivoire');
query('zzzz');
assert.equal(list.children[0].className,'tsfs-country-empty');
key('Enter');
assert.equal(select.value,'France');
key('Escape');
assert.equal(panel.hidden,true);
trigger.click();
key('ArrowDown');
assert.ok(search.getAttribute('aria-activedescendant'));
key('Tab');
assert.equal(panel.hidden,true);
select.value = '';
let prevented = false;
select.dispatchEvent({type:'invalid',preventDefault(){prevented=true;}});
assert.equal(prevented,true);
assert.equal(panel.hidden,false);
assert.equal(trigger.getAttribute('aria-invalid'),'true');
list.children[1].click();
assert.equal(select.value,'Pakistan');
assert.equal(trigger.getAttribute('aria-invalid'),null);
trigger.click();
document.dispatchEvent({type:'click',target:new Node('outside')});
assert.equal(panel.hidden,true);
select.value = '';
document.dispatchEvent({type:'reset',target:document});
assert.equal(get('tsfs-country-trigger-text').textContent,'Select a country');
assert.equal(panel.hidden,true);
label.dispatchEvent({type:'click',preventDefault(){},stopPropagation(){}});
assert.equal(panel.hidden,false);
key('Escape');
const controlsBefore = control.children.length;
window.TSFSCountry.init(document);
assert.equal(control.children.length,controlsBefore); // Reinitialization is safe.
console.log('Passed dropdown placement, matching, selection, keyboard controls, required validation, outside click, reset, and fallback select preservation.');
