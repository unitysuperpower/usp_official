const fs = require('fs');
const parser = require('@babel/parser');
const traverse = require('@babel/traverse').default;
const generate = require('@babel/generator').default;
const t = require('@babel/types');
const pro = new Set(['country','file','range','rating','consent','address','admission','intl_tel','payment','page_break','signature','calculation']);
const ast = parser.parse(fs.readFileSync(process.argv[2], 'utf8'));
function fieldType(n) { return t.isIdentifier(n, {name:'type'}) || t.isMemberExpression(n) && t.isIdentifier(n.property, {name:'type'}); }
function evaluate(n) {
 if (t.isBinaryExpression(n) && ['===','==','!==','!='].includes(n.operator)) {
  const pair = fieldType(n.left) && t.isStringLiteral(n.right) ? n.right : fieldType(n.right) && t.isStringLiteral(n.left) ? n.left : null;
  if (pair && pro.has(pair.value)) return ['!==','!='].includes(n.operator);
 }
 if (t.isLogicalExpression(n)) {
  const a=evaluate(n.left), b=evaluate(n.right);
  if (n.operator==='&&') { if (a===false || b===false) return false; if (a===true && b===true) return true; }
  if (n.operator==='||') { if (a===true || b===true) return true; if (a===false && b===false) return false; }
 }
 return undefined;
}
traverse(ast, {
 IfStatement: {exit(path) { const value=evaluate(path.node.test); if(value===false) { if(path.node.alternate) path.replaceWith(path.node.alternate); else path.remove(); } else if(value===true) path.replaceWith(path.node.consequent); }},
 ObjectProperty(path) {
  const name=t.isIdentifier(path.node.key)?path.node.key.name:t.isStringLiteral(path.node.key)?path.node.key.value:'';
  if(pro.has(name)) path.remove();
 }
});
fs.writeFileSync(process.argv[3],generate(ast,{comments:false}).code+'\n');
