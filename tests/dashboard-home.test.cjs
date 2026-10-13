const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const elements = new Map();
function element(id) {
    if (!elements.has(id)) elements.set(id, { value: '', textContent: '-', attributes: {}, setAttribute(k, v) { this.attributes[k] = v; } });
    return elements.get(id);
}
const requests = [];
let renders = 0;
const context = {
    console: { error() {} }, window: { location: {} },
    document: { getElementById: element, addEventListener() {} },
    ViabixApiCore: { getDashboardStats(filters) { return new Promise((resolve, reject) => requests.push({ filters, resolve, reject })); } }
};
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../assets/js/dashboard-home.js'), 'utf8'), context);
context.renderizarInsightsOperacionais = () => renders++;
const result = total => ({ _ok: true, success: true, anvis: total });
(async () => {
    const first = context.carregarEstatisticas();
    assert.equal(context.carregarEstatisticas(), first, 'Cliques iguais compartilham a carga');
    assert.equal(requests.length, 1);
    element('dashboardClientFilter').value = 'Novo cliente';
    const second = context.carregarEstatisticas();
    requests[1].resolve(result(20));
    await second;
    requests[0].resolve(result(10));
    await first;
    assert.equal(element('totalAnvis').textContent, 20, 'Resposta antiga não sobrescreve filtros atuais');
    assert.equal(renders, 1);
    const failure = context.carregarEstatisticas();
    requests[2].reject(new Error('offline'));
    await failure;
    assert.equal(element('totalAnvis').textContent, 20, 'Falha preserva os indicadores');
    assert.match(element('dashboardLoadStatus').textContent, /Não foi possível/);
    assert.equal(element('insightsGrid').attributes['aria-busy'], 'false');
    const invalid = context.carregarEstatisticas();
    requests[3].resolve({ _ok: true, success: false });
    await invalid;
    assert.equal(renders, 1, 'Erro da API não é tratado como sucesso');
    const retry = context.carregarEstatisticas();
    requests[4].resolve(result(0));
    await retry;
    assert.equal(element('totalAnvis').textContent, 0);
    assert.equal(renders, 2);
    const expired = context.carregarEstatisticas();
    requests[5].resolve({ _ok: false, _httpStatus: 401 });
    await expired;
    assert.equal(context.window.location.href, 'login.html');
    console.log('OK: deduplicação, filtros concorrentes, falhas, nova tentativa e sessão expirada.');
})().catch(error => { console.error(error); process.exitCode = 1; });
