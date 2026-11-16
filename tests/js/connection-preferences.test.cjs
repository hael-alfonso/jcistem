const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const partial = fs.readFileSync(path.join(__dirname, '../../resources/views/shared/public-fonts.blade.php'), 'utf8');
const script = partial.match(/<script[^>]*>([\s\S]*?)<\/script>/)?.[1];
assert.ok(script, 'The shared font preference script must be present');

function run(connection) {
    const addedClasses = [];
    const links = [];
    const document = {
        currentScript: { dataset: { fontUrl: 'https://example.test/fonts.css' } },
        documentElement: { classList: { add: (name) => addedClasses.push(name) } },
        createElement: (tag) => ({ tag }),
        head: { appendChild: (link) => links.push(link) },
    };
    vm.runInNewContext(script, { navigator: { connection }, document });
    return { addedClasses, links };
}

test('slow or data-saver connections skip decorative web fonts', () => {
    for (const connection of [{ downlink: 0.8 }, { saveData: true }, { effectiveType: '2g' }]) {
        const result = run(connection);
        assert.deepEqual(result.addedClasses, ['data-saver']);
        assert.equal(result.links.length, 0);
    }
});

test('normal connections load fonts without blocking the initial render', () => {
    const result = run({ downlink: 5, effectiveType: '4g' });
    assert.deepEqual(result.addedClasses, []);
    assert.equal(result.links.length, 1);
    assert.equal(result.links[0].media, 'print');
    result.links[0].onload();
    assert.equal(result.links[0].media, 'all');
});
