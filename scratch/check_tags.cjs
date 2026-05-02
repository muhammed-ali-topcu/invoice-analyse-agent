
const fs = require('fs');
const content = fs.readFileSync('resources/js/pages/Invoices/Show.vue', 'utf8');

// Strip script section
const templateMatch = content.match(/<template>([\s\S]*)<\/template>/);
if (!templateMatch) {
    console.log('No template section found');
    process.exit(1);
}
const templateContent = templateMatch[1];
const startLine = content.substring(0, templateMatch.index).split('\n').length + 1;

const stack = [];
const regex = /<(\/?)([a-zA-Z0-9:-]+)([^>]*)>/g;
let match;

const voidElements = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);

while ((match = regex.exec(templateContent)) !== null) {
    const isClose = match[1] === '/';
    const tagName = match[2];
    const attributes = match[3];
    const isSelfClosing = attributes.trim().endsWith('/') || voidElements.has(tagName.toLowerCase());
    const line = templateContent.substring(0, match.index).split('\n').length + startLine - 1;

    if (isClose) {
        if (stack.length === 0) {
            console.log(`Extra closing tag </${tagName}> at line ${line}`);
        } else {
            const last = stack.pop();
            if (last.tagName !== tagName) {
                console.log(`Mismatched closing tag </${tagName}> at line ${line}, expected </${last.tagName}> (opened at line ${last.line})`);
            }
        }
    } else {
        if (!isSelfClosing) {
            stack.push({ tagName, line });
        }
    }
}

if (stack.length > 0) {
    console.log('Unclosed tags:');
    stack.forEach(tag => console.log(`Line ${tag.line}: <${tag.tagName}>`));
} else {
    console.log('All tags balanced.');
}
