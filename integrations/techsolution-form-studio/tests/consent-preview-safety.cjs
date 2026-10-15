const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),parser=require('@babel/parser');
const source=fs.readFileSync(__dirname+'/../admin/assets/js/tsfs-builder.js','utf8');
const context={window:{location:{href:'https://customer.example/wp-admin/'}},URL};
function visit(node){
 if(!node||typeof node!=='object')return;
 if(node.type==='FunctionDeclaration'&&['safeTermsUrl','escapeMarkup'].includes(node.id.name))vm.runInNewContext(source.slice(node.start,node.end),context);
 Object.values(node).forEach(value=>Array.isArray(value)?value.forEach(visit):value&&typeof value==='object'&&visit(value));
}
visit(parser.parse(source));
assert.equal(context.safeTermsUrl('javascript:alert(1)'),'');
assert.equal(context.safeTermsUrl('data:text/html,test'),'');
assert.equal(context.safeTermsUrl('/privacy'),'https://customer.example/privacy');
assert.equal(context.safeTermsUrl('https://example.org/terms'),'https://example.org/terms');
assert.equal(context.escapeMarkup('</textarea><img src=x onerror="alert(1)">'), '&lt;/textarea&gt;&lt;img src=x onerror=&quot;alert(1)&quot;&gt;');
console.log('Passed consent preview text escaping and safe link protocols.');
