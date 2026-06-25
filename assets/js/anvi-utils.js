// =========================================
// FUNÇÕES UTILITÁRIAS DE FORMATAÇÃO (MANTIDAS DO ORIGINAL)
// =========================================
function toDecimal(value, casas = 4) {
    if (value === undefined || value === null || value === '') return 0;
    const parsed = parseFloat(value.toString().replace(',', '.'));
    return isNaN(parsed) ? 0 : Number(parsed.toFixed(casas));
}

function money(value) {
    return toDecimal(value, 2);
}

function formatMoney(value) {
    return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function formatPercent(value) {
    return value.toFixed(2) + '%';
}

