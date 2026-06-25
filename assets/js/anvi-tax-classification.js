function aplicarClassificacaoFiscalParaCalculos() {
    try {
        const tabelaClassificacao = document.getElementById('classificacaoFiscalTable');
        if (!tabelaClassificacao) return false;
        
        const linhas = tabelaClassificacao.querySelectorAll('tbody tr');
        if (linhas.length === 0) {
            console.log('Nenhuma classificação fiscal cadastrada. Usando valores padrão.');
            return false;
        }
        
        const primeiraLinha = linhas[0];
        const inputs = primeiraLinha.querySelectorAll('input');
        
        if (inputs.length >= 6) {
            const ipi = parseFloat(inputs[2]?.value) || 0;
            const icms = parseFloat(inputs[3]?.value) || 0;
            const pis = parseFloat(inputs[4]?.value) || 1.65;
            const cofins = parseFloat(inputs[5]?.value) || 7.6;
            
            const campoIPI = document.getElementById('aliquotaIPI');
            const campoICMS = document.getElementById('aliquotaICMS');
            const campoPIS = document.getElementById('aliquotaPIS');
            const campoCOFINS = document.getElementById('aliquotaCOFINS');
            
            let alterado = false;
            
            if (campoIPI && Math.abs(parseFloat(campoIPI.value) - ipi) > 0.01) {
                campoIPI.value = ipi;
                alterado = true;
            }
            
            if (campoICMS && Math.abs(parseFloat(campoICMS.value) - icms) > 0.01) {
                campoICMS.value = icms;
                alterado = true;
            }
            
            if (campoPIS && Math.abs(parseFloat(campoPIS.value) - pis) > 0.01) {
                campoPIS.value = pis;
                alterado = true;
            }
            
            if (campoCOFINS && Math.abs(parseFloat(campoCOFINS.value) - cofins) > 0.01) {
                campoCOFINS.value = cofins;
                alterado = true;
            }
            
            if (alterado) {
                console.log('✅ Classificação fiscal aplicada aos cálculos principais');
                mostrarNotificacao('Classificação fiscal aplicada com sucesso!', 'success');
                return true;
            }
        }
        
        return false;
    } catch (error) {
        console.error('Erro ao aplicar classificação fiscal:', error);
        return false;
    }
}

function aplicarClassificacaoFiscal() {
    if (aplicarClassificacaoFiscalParaCalculos()) {
        mostrarNotificacao('Classificação fiscal aplicada aos cálculos!', 'success');
        synchronizeCalculations();
    } else {
        mostrarNotificacao('Nenhuma classificação fiscal encontrada para aplicar.', 'warning');
    }
}

function adicionarSelecionadosParaClassificacaoFiscal() {
    const modalTable = document.getElementById('classificacaoFiscalModalTable');
    const targetTable = document.getElementById('classificacaoFiscalTable');
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos um NCM para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        const ncm = cells[1]?.textContent.trim() || '';
        const descricao = cells[2]?.textContent.trim() || '';
        const ipi = cells[3]?.textContent.trim().replace('%', '') || '0';
        const icms = cells[4]?.textContent.trim().replace('%', '') || '0';
        const pis = cells[5]?.textContent.trim().replace('%', '') || '1.65';
        const cofins = cells[6]?.textContent.trim().replace('%', '') || '7.6';
        
        for (let i = 0; i < 6; i++) {
            const cell = newRow.insertCell(i);
            
            if (i === 0) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = ncm;
                cell.appendChild(input);
            } else if (i === 1) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = descricao;
                cell.appendChild(input);
            } else if (i === 2) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm ipi-aliquota-input';
                input.step = '0.1';
                input.value = parseFloat(ipi) || 0;
                input.addEventListener('input', function() { 
                    const campoIPI = document.getElementById('aliquotaIPI');
                    if (campoIPI) {
                        campoIPI.value = this.value;
                    }
                    synchronizeCalculations(); 
                });
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm icms-aliquota-input';
                input.step = '0.1';
                input.value = parseFloat(icms) || 0;
                input.addEventListener('input', function() { 
                    const campoICMS = document.getElementById('aliquotaICMS');
                    if (campoICMS) {
                        campoICMS.value = this.value;
                    }
                    synchronizeCalculations(); 
                });
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm pis-aliquota-input';
                input.step = '0.01';
                input.value = parseFloat(pis) || 1.65;
                input.addEventListener('input', function() { 
                    const campoPIS = document.getElementById('aliquotaPIS');
                    if (campoPIS) {
                        campoPIS.value = this.value;
                    }
                    synchronizeCalculations(); 
                });
                cell.appendChild(input);
            } else if (i === 5) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm cofins-aliquota-input';
                input.step = '0.01';
                input.value = parseFloat(cofins) || 7.6;
                input.addEventListener('input', function() { 
                    const campoCOFINS = document.getElementById('aliquotaCOFINS');
                    if (campoCOFINS) {
                        campoCOFINS.value = this.value;
                    }
                    synchronizeCalculations(); 
                });
                cell.appendChild(input);
            }
        }
        
        const actionCell = newRow.insertCell(6);
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger remove-row';
        removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
        removeBtn.addEventListener('click', function() {
            this.closest('tr').remove();
            synchronizeCalculations();
        });
        actionCell.appendChild(removeBtn);
        
        checkbox.checked = false;
    });
    
    setTimeout(() => {
        aplicarClassificacaoFiscalParaCalculos();
    }, 100);
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('classificacaoFiscalModal'));
    if (modal) {
        modal.hide();
    }
    
    synchronizeCalculations();
}

