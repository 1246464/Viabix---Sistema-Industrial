const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const elements = new Map();
function element(id) {
    if (!elements.has(id)) elements.set(id, {
        value: '', textContent: '', innerHTML: '', style: {}, attributes: {}, listeners: {},
        classList: { add() {}, remove() {} },
        setAttribute(k,v) { this.attributes[k] = v; },
        addEventListener(k,fn) { this.listeners[k] = fn; },
        replaceChildren(fragment) { this.children = fragment.children; }
    });
    return elements.get(id);
}
const requests = [];
const charts = [];
let timers = 0;
class Chart {
    constructor(canvas, config) { this.data = config.data; this.updates = 0; charts.push(this); }
    update(mode) { assert.equal(mode, 'none'); this.updates++; }
    destroy() { throw new Error('Não recriar gráficos ao atualizar dados'); }
}
const context = {
    console: { error() {} }, AbortController, URLSearchParams, Chart,
    window: { Chart, location: { search: '' } },
    document: {
        getElementById: element, addEventListener() {},
        createElement() { return {}; },
        createDocumentFragment() { return { children: [], appendChild(child) { this.children.push(child); } }; }
    },
    setTimeout() { timers++; return timers; }, clearTimeout() {},
    fetch(url, options) { return new Promise((resolve, reject) => requests.push({ url, options, resolve, reject })); }
};
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../assets/js/dashboard-viabilidade.js'), 'utf8'), context);
const response = data => ({ ok: true, status: 200, json: async () => data });
(async () => {
    const portfolio = [{ numero: 'ANVI-1', score: 0, eficiencia: 0, payback: 0, risco: 100, margem: 0 }];
    context.renderizarGraficosPortfolio(portfolio, { score: 0, execucao: 0, prazo: 0, riscoScore: 0 });
    context.renderizarGraficosPortfolio(portfolio, { score: 100, execucao: 90, prazo: 80, riscoScore: 70 });
    assert.equal(charts.length, 2);
    assert.equal(charts[0].updates, 1);
    assert.deepEqual(Array.from(charts[0].data.datasets[0].data), [100,90,80,70]);
    assert.equal(context.normalizarPortfolio(portfolio)[0].score, 0);
    context.renderizarAnalise({ financeiro: { score: 50 }, qualidade: { score: 60 } });
    assert.match(element('analiseContainer').innerHTML, /Financeiro/);
    assert.match(element('analiseContainer').innerHTML, /Qualidade/);
    context.renderizarAnalise({});
    assert.equal(element('analiseContainer').innerHTML, '');
    const oldSuggestion = context.buscarSugestoesANVI('antiga');
    const newSuggestion = context.buscarSugestoesANVI('nova');
    assert.equal(requests[0].options.signal.aborted, true);
    requests[1].resolve(response({ success: true, data: [{ id: 'revision-2', numero: '123', nome: '123 Rev. 2' }] }));
    await newSuggestion;
    requests[0].resolve(response({ success: true, data: [{ id: 'old', nome: 'Antiga' }] }));
    await oldSuggestion;
    assert.equal(element('anviSuggestions').children[0].value, '123 Rev. 2');
    assert.equal(charts[0].updates, 1, 'Sugestões não alteram os gráficos');
    element('anviInput').value = '123 Rev. 2';
    element('anviInput').listeners.input({ target: element('anviInput') });
    assert.equal(timers, 0, 'Preservar revisão selecionada no autocomplete');
    for (const fn of ['renderizarDecisao', 'renderizarIndicadoresFinanceiros', 'renderizarComparativoRevisoes', 'atualizarInteligenciaComAnalise']) context[fn] = () => {};
    const first = context.carregarAnalise();
    assert.match(requests[2].url, /anvi_id=revision-2/);
    assert.equal(context.carregarAnalise(), first);
    assert.equal(requests.length, 3);
    element('anviInput').value = 'outra';
    const second = context.carregarAnalise();
    assert.equal(requests[2].options.signal.aborted, true);
    requests[3].resolve(response({ anvi: { id: 'outra', numero: 'Nova' } }));
    await second;
    requests[2].resolve(response({ anvi: { numero: 'Antiga' } }));
    await first;
    assert.equal(element('anviNumero').textContent, 'Nova');
    const failure = context.carregarAnalise();
    requests[4].reject(new Error('offline'));
    await failure;
    assert.equal(element('resultsContainer').style.display, 'block');
    assert.equal(element('resultsContainer').attributes['aria-busy'], 'false');
    assert.match(element('errorMessage').textContent, /análise anterior/);
    const expired = context.carregarAnalise();
    requests[5].resolve({ status: 401 });
    await expired;
    assert.equal(context.window.location.href, 'login.html');
    console.log('OK: gráficos reutilizados, scores, autocomplete, revisão, concorrência, falhas e sessão.');
})().catch(error => { console.error(error); process.exitCode = 1; });
