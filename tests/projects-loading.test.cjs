const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const pending = [];
let renders = 0;
let timer;
const context = {
    console: { log() {}, warn() {}, error() {} },
    document: { getElementById: () => null, addEventListener() {} },
    window: { location: { search: '' } }, projects: [{ id: 999 }], nextProjectId: 1,
    updateProjectsTable() { renders++; }, updateSummary() {},
    abrirProjetoInicialDaUrl() {},
    setTimeout(fn) { timer = fn; return 1; }, clearTimeout() {},
    URLSearchParams,
    $() { return { ajaxSend() { return this; }, ajaxComplete() { return this; } }; }
};
context.$.ajax = options => pending.push(options);
vm.createContext(context);
for (const file of ['projects-api.js', 'projects-control.js']) {
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../assets/js', file), 'utf8'), context);
}
context.autoUpdateTaskStatuses = () => {};
context.loadProjectsFromMySQL();
assert.equal(pending.length, 1);
assert.equal(pending[0].data.limit, 100);
pending.shift().success({ success: true, total_pages: 2, data: Array.from({ length: 100 }, (_, i) => ({ id: i + 1 })) });
assert.equal(context.projects[0].id, 999, 'Não publicar página parcial');
assert.equal(pending[0].data.page, 2);
pending.shift().success({ success: true, total_pages: 2, data: [{ id: 101, apqp: [] }] });
assert.equal(context.projects.length, 101, 'Carregar além do limite inicial');
assert.equal(renders, 1, 'Renderizar uma vez por carga completa');
assert.equal(context.nextProjectId, 102);
assert.equal(Array.isArray(context.projects[100].apqp), false);
context.loadProjectsFromMySQL();
pending.shift().success({ success: true, total_pages: 2, data: [{ id: 1 }] });
pending.shift().error({ status: 500 }, 'error', 'offline');
assert.equal(context.projects.length, 101, 'Preservar dados quando página seguinte falhar');
context.loadProjectsFromMySQL();
context.loadProjectsFromMySQL();
context.loadProjectsFromMySQL();
assert.equal(pending.length, 1, 'Não sobrepor cargas');
pending.shift().success({ success: true, total_pages: 1, data: [] });
assert.equal(pending.length, 1, 'Executar uma atualização pendente');
pending.shift().success({ success: true, total_pages: 1, data: [] });
assert.equal(pending.length, 0);
let projectLoads = 0;
let leaderLoads = 0;
context.loadProjectsFromMySQL = () => projectLoads++;
context.loadLeadersFromMySQL = () => leaderLoads++;
for (let i = 0; i < 100; i++) context.scheduleRealtimeRefresh(false);
context.scheduleRealtimeRefresh(true);
timer();
assert.equal(leaderLoads, 1, 'Agrupar eventos de líderes e projetos');
assert.equal(projectLoads, 0);
context.scheduleRealtimeRefresh(false);
timer();
assert.equal(projectLoads, 1);
console.log('OK: paginação, publicação atômica, falhas, concorrência e agrupamento SSE.');
