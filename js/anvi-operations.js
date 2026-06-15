(function (global) {
    'use strict';

    function mount(deps) {
        const api = deps.api || global.ViabixApiCore;
        const state = {
            id: null,
            versao: 1,
            bloqueada: false,
            timerBloqueio: null,
            ultimaVerificacao: null
        };

        function notify(message, type) {
            if (typeof deps.mostrarNotificacao === 'function') {
                deps.mostrarNotificacao(message, type);
            }
        }

        function currentUser() {
            return typeof deps.getUsuarioAtual === 'function' ? deps.getUsuarioAtual() : null;
        }

        function buildAnviPayload() {
            const anviNumber = document.getElementById('anviNumber').value;
            const revisaoANVI = document.getElementById('revisaoANVI').value;
            const anviData = deps.capturarTodosDados();

            anviData.id = `${anviNumber}_${revisaoANVI}`;
            anviData.numero = anviNumber;
            anviData.revisao = revisaoANVI;
            anviData.cliente = document.getElementById('client').value;
            anviData.projeto = document.getElementById('project').value;
            anviData.produto = document.getElementById('productDescription').value;
            anviData.volumeMensal = document.getElementById('monthlyVolume').value;
            anviData.dataANVI = document.getElementById('dataANVI').value;
            anviData.status = document.getElementById('statusAprovacao').value;
            anviData.versao = state.versao;

            return { anviData, anviNumber, revisaoANVI };
        }

        function ensureCanEdit(actionLabel) {
            const user = currentUser();
            if (!user) {
                alert(actionLabel === 'salvar' ? 'Voce precisa estar logado para salvar.' : 'Voce precisa estar logado.');
                return false;
            }

            if (user.nivel === 'visitante') {
                alert(actionLabel === 'excluir' ? 'Visitantes nao podem excluir ANVIs.' : 'Visitantes nao podem salvar ANVIs.');
                return false;
            }

            return true;
        }

        async function salvarANVI() {
            if (!ensureCanEdit('salvar')) return false;

            if (typeof deps.getRateioBloqueado === 'function' && deps.getRateioBloqueado()) {
                alert('Nao e possivel salvar com percentuais de rateio > 100%. Ajuste os custos indiretos antes de salvar.');
                return false;
            }

            const anviNumber = document.getElementById('anviNumber').value;
            const revisaoANVI = document.getElementById('revisaoANVI').value;

            if (!anviNumber || !revisaoANVI) {
                alert('Por favor, preencha o No ANVI e a Revisao antes de salvar.');
                return false;
            }

            const { anviData } = buildAnviPayload();

            try {
                const result = await api.saveAnvi(anviData);

                if (result._httpStatus === 409) {
                    if (result.duplicate) {
                        const confirmacao = confirm(
                            `${result.message}\n\n` +
                            `Cliente: ${result.existing.cliente || 'N/A'}\n\n` +
                            `Deseja SUBSTITUIR o registro existente?\n\n` +
                            `Clique OK para SUBSTITUIR\n` +
                            `Clique Cancelar para NAO salvar`
                        );

                        if (confirmacao) {
                            anviData.force = true;
                            return await salvarANVIForcado(anviData);
                        } else {
                            notify('Salvamento cancelado pelo usuario', 'info');
                        }
                        return false;
                    }

                    state.versao = result.versao_atual;
                    const acao = await mostrarModalConflito(result);

                    if (acao === 'sobrescrever') {
                        anviData.versao = result.versao_atual;
                        return await salvarANVIForcado(anviData);
                    } else if (acao === 'recarregar') {
                        await abrirANVI(anviData.id);
                        notify('Versao mais recente carregada. Revise suas alteracoes.', 'info');
                    }
                    return false;
                } else if (result.success) {
                    state.versao = result.versao || state.versao + 1;
                    carregarANVIs();
                    notify(`ANVI ${anviNumber} Rev. ${revisaoANVI} salva com sucesso!`, 'success');

                    if (typeof deps.verificarVinculoComProjeto === 'function') {
                        await deps.verificarVinculoComProjeto();
                    }
                    return true;
                } else {
                    const detalheErro = result.error_id ? `\nCódigo do erro: ${result.error_id}` : '';
                    alert((result.message || 'Erro ao salvar ANVI') + detalheErro);
                    return false;
                }
            } catch (e) {
                console.error('Erro ao salvar ANVI:', e);
                alert('Erro de conexao com o servidor');
                return false;
            }
        }

        async function salvarANVIForcado(anviData) {
            try {
                const result = await api.saveAnvi(anviData);

                if (result.success) {
                    state.versao = result.versao || state.versao;
                    carregarANVIs();
                    notify(`ANVI ${anviData.numero} Rev. ${anviData.revisao} salva com sucesso!`, 'success');
                    return true;
                } else {
                    const detalheErro = result.error_id ? `\nCódigo do erro: ${result.error_id}` : '';
                    alert('Erro ao salvar: ' + (result.message || 'Desconhecido') + detalheErro);
                    return false;
                }
            } catch (e) {
                console.error('Erro ao salvar ANVI:', e);
                alert('Erro de conexao com o servidor');
                return false;
            }
        }

        async function carregarANVIs() {
            try {
                const db = await api.listAnvis();

                if (!db._ok) {
                    throw new Error(`Erro ao carregar ANVIs (${db._httpStatus}): ${db.message || 'Resposta invalida'}`);
                }

                renderDirectoryTable(db);
                renderMasterTable(db);
            } catch (e) {
                console.error('Erro ao carregar ANVIs:', e);
                notify(`Erro ao carregar ANVIs: ${e.message}`, 'error');
            }
        }

        function renderDirectoryTable(db) {
            const dirANVI = document.getElementById('dirANVI');
            if (!dirANVI) return;

            const anvis = Object.values(db);
            if (anvis.length === 0) {
                dirANVI.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2 d-block"></i>
                            Nenhuma ANVI salva ainda. Preencha os dados e clique em "Salvar ANVI".
                        </td>
                    </tr>`;
                return;
            }

            dirANVI.innerHTML = anvis.map(anvi => `
                <tr>
                    <td>${anvi.numero || ''}</td>
                    <td>${anvi.revisao || ''}</td>
                    <td>${anvi.cliente || ''}</td>
                    <td>${anvi.projeto || ''}</td>
                    <td>${anvi.dataANVI || ''}</td>
                    <td>
                        <button class="btn btn-sm btn-primary me-1" onclick="abrirANVI('${anvi.id}')">
                            <i class="fas fa-folder-open"></i> Abrir
                        </button>
                        <button class="btn btn-sm btn-success me-1" onclick="gerarPDFANVI('${anvi.id}')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="excluirANVI('${anvi.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`).join('');
        }

        function renderMasterTable(db) {
            const masterANVI = document.getElementById('masterANVI');
            if (!masterANVI) return;

            const anvis = Object.values(db);
            if (anvis.length === 0) {
                masterANVI.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Nenhuma ANVI disponivel na lista mestra.
                        </td>
                    </tr>`;
                return;
            }

            masterANVI.innerHTML = anvis.map(anvi => {
                const dataCriacao = anvi.timestamps && anvi.timestamps.dataCriacao ? new Date(anvi.timestamps.dataCriacao) : new Date();
                const dataFormatada = dataCriacao.toLocaleDateString('pt-BR');
                const badgeClass = anvi.status === 'aprovada' ? 'bg-success' :
                    anvi.status === 'aprovada-condicional' ? 'bg-warning' :
                    anvi.status === 'em-andamento' ? 'bg-info' : 'bg-secondary';

                return `
                    <tr>
                        <td>${anvi.numero || ''}</td>
                        <td>${anvi.revisao || ''}</td>
                        <td>${anvi.cliente || ''}</td>
                        <td>${anvi.projeto || ''}</td>
                        <td>${anvi.produto || ''}</td>
                        <td><span class="badge ${badgeClass}">${anvi.status || 'N/A'}</span></td>
                        <td>${dataFormatada}</td>
                    </tr>`;
            }).join('');
        }

        async function abrirANVI(anviId) {
            if (state.id && state.id !== anviId) {
                await desbloquearANVI(state.id);
            }

            try {
                const anvi = await api.getAnviById(anviId);
                if (!anvi._ok) throw new Error('Erro ao carregar ANVI');

                const bloqueado = await bloquearANVI(anviId);
                const user = currentUser();

                if (!bloqueado && user && user.nivel !== 'visitante') {
                    await abrirANVISomenteLeitura(anviId);
                    return;
                }

                aplicarAnviNaTela(anvi);

                state.id = anviId;
                state.versao = anvi.versao || 1;

                const dadosTab = document.getElementById('dados-tab');
                if (dadosTab) dadosTab.click();

                setTimeout(() => {
                    deps.updateAllCalculations();
                    deps.validarConsistenciaGeral();
                }, 500);

                notify(`ANVI ${anvi.numero} Rev. ${anvi.revisao} carregada com sucesso!`, 'success');

                if (typeof deps.verificarVinculoComProjeto === 'function') {
                    setTimeout(() => deps.verificarVinculoComProjeto(), 600);
                }
            } catch (e) {
                console.error('Erro ao abrir ANVI:', e);
                alert('Erro ao carregar ANVI do servidor');
            }
        }

        function aplicarAnviNaTela(anvi) {
            if (Array.isArray(anvi.desenhos)) {
                deps.setDesenhos(anvi.desenhos);
            } else {
                deps.setDesenhos([]);
            }

            deps.restaurarTodosDados(anvi);

            document.getElementById('anviNumber').value = anvi.numero || '';
            document.getElementById('revisaoANVI').value = anvi.revisao || '';
            document.getElementById('client').value = anvi.cliente || '';
            document.getElementById('project').value = anvi.projeto || '';
            document.getElementById('productDescription').value = anvi.produto || '';
            document.getElementById('monthlyVolume').value = anvi.volume_mensal || anvi.volumeMensal || 1000;
            document.getElementById('dataANVI').value = anvi.data_anvi || anvi.dataANVI || '';
            document.getElementById('statusAprovacao').value = anvi.status || '';
        }

        async function abrirANVISomenteLeitura(id) {
            try {
                const anvi = await api.getAnviById(id);
                if (!anvi._ok) throw new Error('Erro ao carregar ANVI');

                deps.restaurarTodosDados(anvi);
                document.querySelectorAll('input, select, textarea, button').forEach(el => {
                    if (!el.classList.contains('btn-outline-primary') &&
                        !el.classList.contains('btn-info') &&
                        !el.classList.contains('btn-warning')) {
                        el.disabled = true;
                    }
                });

                notify('Modo somente leitura ativado', 'info');
            } catch (e) {
                console.error('Erro ao abrir ANVI:', e);
            }
        }

        async function excluirANVI(anviId) {
            if (!ensureCanEdit('excluir')) return;

            if (!confirm('Tem certeza que deseja excluir esta ANVI? Esta acao nao pode ser desfeita.')) {
                return;
            }

            try {
                const result = await api.deleteAnviById(anviId);

                if (result.success) {
                    carregarANVIs();
                    notify('ANVI excluida com sucesso!', 'success');
                } else {
                    alert(result.message || 'Erro ao excluir ANVI');
                }
            } catch (e) {
                console.error('Erro ao excluir ANVI:', e);
                alert('Erro de conexao com o servidor');
            }
        }

        async function limparBloqueiosAntigos() {
            try {
                const result = await api.updateAnviLock(null, 'limpar_bloqueios');
                if (result._ok) {
                    console.log('Bloqueios antigos limpos');
                }
            } catch (e) {
                console.error('Erro ao limpar bloqueios:', e);
            }
        }

        async function bloquearANVI(id) {
            if (!id) return false;

            try {
                const data = await api.updateAnviLock(id, 'bloquear');

                if (data.success) {
                    state.id = id;
                    state.bloqueada = true;

                    if (state.timerBloqueio) {
                        clearInterval(state.timerBloqueio);
                    }

                    state.timerBloqueio = setInterval(() => {
                        renovarBloqueioANVI(id);
                    }, 5 * 60 * 1000);

                    return true;
                }

                notify(data.message || 'ANVI bloqueada por outro usuario', 'warning');
                if (data.bloqueado_por) {
                    mostrarModalBloqueio(data.bloqueado_por, data.bloqueado_em);
                }
                return false;
            } catch (e) {
                console.error('Erro ao bloquear ANVI:', e);
                return false;
            }
        }

        async function renovarBloqueioANVI(id) {
            if (!id || !state.bloqueada) return;

            try {
                await api.updateAnviLock(id, 'bloquear');
            } catch (e) {
                console.error('Erro ao renovar bloqueio:', e);
            }
        }

        async function desbloquearANVI(id) {
            if (!id || !state.bloqueada) return;

            if (state.timerBloqueio) {
                clearInterval(state.timerBloqueio);
                state.timerBloqueio = null;
            }

            try {
                await api.updateAnviLock(id, 'desbloquear');
            } catch (e) {
                console.error('Erro ao desbloquear ANVI:', e);
            } finally {
                state.id = null;
                state.bloqueada = false;
            }
        }

        function mostrarModalConflito(dadosConflito) {
            return new Promise((resolve) => {
                const modalHtml = `
                    <div class="modal fade" id="conflitoModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header" style="background: #b71c1c; color: white;">
                                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>CONFLITO DETECTADO</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>${dadosConflito.message}</strong></p>
                                    <p>Outro usuario salvou alteracoes enquanto voce editava esta ANVI.</p>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Suas alteracoes podem sobrescrever as do outro usuario.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-info" id="btnRecarregar">
                                        <i class="fas fa-sync-alt me-2"></i>Recarregar versao atual
                                    </button>
                                    <button type="button" class="btn btn-danger" id="btnSobrescrever">
                                        <i class="fas fa-exclamation-circle me-2"></i>Sobrescrever mesmo assim
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>`;

                const modalExistente = document.getElementById('conflitoModal');
                if (modalExistente) modalExistente.remove();

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                const modalElement = document.getElementById('conflitoModal');
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });

                document.getElementById('btnRecarregar').addEventListener('click', () => {
                    modal.hide();
                    resolve('recarregar');
                });

                document.getElementById('btnSobrescrever').addEventListener('click', () => {
                    modal.hide();
                    resolve('sobrescrever');
                });

                modal.show();

                modalElement.addEventListener('hidden.bs.modal', function () {
                    this.remove();
                    resolve('cancelar');
                });
            });
        }

        function mostrarModalBloqueio(usuario, data) {
            const dataFormatada = data ? new Date(data).toLocaleString('pt-BR') : 'agora';
            const modalExistente = document.getElementById('bloqueioModal');
            if (modalExistente) modalExistente.remove();

            const modalHtml = `
                <div class="modal fade" id="bloqueioModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header" style="background: #ff8f00; color: white;">
                                <h5 class="modal-title"><i class="fas fa-lock me-2"></i>ANVI BLOQUEADA</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>${usuario}</strong> esta editando esta ANVI desde ${dataFormatada}.</p>
                                <p>Voce pode abrir no modo somente leitura ou aguardar.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="abrirANVISomenteLeitura('${state.id}')">
                                    <i class="fas fa-eye me-2"></i>Abrir somente leitura
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            new bootstrap.Modal(document.getElementById('bloqueioModal')).show();
        }

        return {
            salvarANVI,
            salvarANVIForcado,
            carregarANVIs,
            abrirANVI,
            abrirANVISomenteLeitura,
            excluirANVI,
            limparBloqueiosAntigos,
            bloquearANVI,
            renovarBloqueioANVI,
            desbloquearANVI,
            mostrarModalConflito,
            mostrarModalBloqueio,
            getState() {
                return state;
            }
        };
    }

    global.ViabixAnviOperations = { mount };
})(window);
