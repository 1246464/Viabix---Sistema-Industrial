function adicionarLinhaManual(tableId) {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar linhas.');
        return;
    }

    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const newRow = tbody.insertRow();
    
    const headerRow = table.querySelector('thead tr');
    if (!headerRow) return;
    
    const headerCells = headerRow.querySelectorAll('th');
    const colCount = headerCells.length;
    
    for (let i = 0; i < colCount - 1; i++) {
        const cell = newRow.insertCell(i);
        const headerText = headerCells[i].textContent.toLowerCase();
        
        if (headerText.includes('quantidade') || headerText.includes('valor') || 
            headerText.includes('tempo') || headerText.includes('salário') ||
            headerText.includes('depreciação') || headerText.includes('rendimento') ||
            headerText.includes('potência') || headerText.includes('água') ||
            headerText.includes('set up') || headerText.includes('outros custos') ||
            headerText.includes('vida útil') || headerText.includes('alíq') ||
            headerText.includes('crédito') || headerText.includes('líquido')) {
            
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm';
            input.step = '0.01';
            input.value = '0';
            
            if (headerText.includes('quantidade')) input.classList.add('qty-input');
            if (headerText.includes('valor unit')) input.classList.add('unit-price-input');
            if (headerText.includes('valor total') || headerText.includes('custo/peça')) input.classList.add('total-price');
            if (headerText.includes('vida útil')) {
                input.classList.add('tooling-life-input');
                input.value = '100000';
            }
            if (headerText.includes('custo por peça')) input.classList.add('tooling-per-piece');
            if (headerText.includes('tempo')) input.classList.add('time-input');
            if (headerText.includes('potência')) input.classList.add('power-input');
            if (headerText.includes('preço kwh')) input.classList.add('energy-price-input');
            if (headerText.includes('água m³')) input.classList.add('water-input');
            if (headerText.includes('preço m³')) input.classList.add('water-price-input');
            if (headerText.includes('rendimento')) {
                input.classList.add('efficiency-input');
                input.value = '100';
            }
            if (headerText.includes('qtde peça/h')) {
                input.classList.add('output-input');
                input.value = '1';
            }
            if (headerText.includes('set up')) input.classList.add('setup-time-input');
            if (headerText.includes('depreciação')) input.classList.add('depreciation-input');
            if (headerText.includes('outros custos/hora')) input.classList.add('other-costs-input');
            if (headerText.includes('custo/hora calculado')) {
                input.classList.add('hourly-cost-input');
                input.readOnly = true;
            }
            if (headerText.includes('custo/peça')) {
                input.classList.add('process-total');
                input.readOnly = true;
            }
            if (headerText.includes('quantidade de embalagem')) input.classList.add('packaging-qty-input');
            if (headerText.includes('quantidade de peça por embalagem')) input.classList.add('qty-per-package-input');
            if (headerText.includes('valor mensal')) input.classList.add('custo-fixo-input');
            if (headerText.includes('percentual rateio')) {
                input.classList.add('rateio-percent-input');
                input.value = '100';
            }
            if (headerText.includes('valor rateado')) {
                input.classList.add('rateio-valor');
                input.readOnly = true;
            }
            if (headerText.includes('alíq. ipi')) input.classList.add('ipi-aliquota-input');
            if (headerText.includes('alíq. icms')) input.classList.add('icms-aliquota-input');
            if (headerText.includes('valor total c/ impostos')) {
                input.classList.add('gross-total');
                input.readOnly = true;
            }
            if (headerText.includes('valor crédito')) {
                input.classList.add('credit-value');
                input.readOnly = true;
            }
            if (headerText.includes('valor líquido')) {
                input.classList.add('total-price');
                input.readOnly = true;
            }
            
            input.addEventListener('input', synchronizeCalculations);
            cell.appendChild(input);
            
        } else if (headerText.includes('tipo de custo')) {
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm';
            
            const optionFixo = document.createElement('option');
            optionFixo.value = 'fixo';
            optionFixo.textContent = 'Custo Fixo';
            
            const optionVariavel = document.createElement('option');
            optionVariavel.value = 'variavel';
            optionVariavel.textContent = 'Custo Variável';
            
            select.appendChild(optionFixo);
            select.appendChild(optionVariavel);
            select.addEventListener('change', synchronizeCalculations);
            cell.appendChild(select);
            
        } else if (headerText.includes('descrição') || headerText.includes('processo') || 
                   headerText.includes('recurso') || headerText.includes('função') ||
                   headerText.includes('setor') || headerText.includes('centro de custo') ||
                   headerText.includes('código') || headerText.includes('unidade') ||
                   headerText.includes('tipo') || headerText.includes('certificação') ||
                   headerText.includes('número') || headerText.includes('validade') ||
                   headerText.includes('norma') || headerText.includes('versão') ||
                   headerText.includes('ncm') || headerText.includes('espessura')) {
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.value = '';
            cell.appendChild(input);
        }
    }
    
    const actionCell = newRow.insertCell(colCount - 1);
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm btn-danger remove-row';
    button.innerHTML = '<i class="fas fa-trash"></i>';
    button.addEventListener('click', function() {
        this.closest('tr').remove();
        synchronizeCalculations();
    });
    actionCell.appendChild(button);
    
    synchronizeCalculations();
}

function adicionarLinhaProcesso() {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar processos.');
        return;
    }

    const table = document.getElementById('processTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const newRow = tbody.insertRow();
    
    for (let i = 0; i < 14; i++) {
        const cell = newRow.insertCell(i);
        
        if (i === 0 || i === 1) {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.value = '';
            cell.appendChild(input);
        } else if (i === 13) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-danger remove-row';
            button.innerHTML = '<i class="fas fa-trash"></i>';
            button.addEventListener('click', function() {
                this.closest('tr').remove();
                updateProcessTotal();
                synchronizeCalculations();
            });
            cell.appendChild(button);
        } else if (i === 2) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm power-input';
            input.step = '0.1';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 3) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm energy-price-input';
            input.step = '0.01';
            input.value = '0.85';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 4) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm water-input';
            input.step = '0.01';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 5) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm water-price-input';
            input.step = '0.01';
            input.value = '8.50';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 6) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm efficiency-input';
            input.value = '100';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 7) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm output-input';
            input.step = '0.1';
            input.value = '1';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 8) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm setup-time-input';
            input.step = '0.1';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 9) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm depreciation-input';
            input.step = '0.01';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 10) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm other-costs-input';
            input.step = '0.01';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 11) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm hourly-cost-input';
            input.step = '0.01';
            input.value = '0';
            input.readOnly = true;
            cell.appendChild(input);
        } else if (i === 12) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm process-total';
            input.step = '0.01';
            input.value = '0';
            input.readOnly = true;
            cell.appendChild(input);
        }
    }
    
    const inputs = newRow.querySelectorAll('input');
    inputs.forEach(input => {
        if (!input.classList.contains('hourly-cost-input') && !input.classList.contains('process-total')) {
            input.addEventListener('input', function() {
                calculateHourlyCost(newRow);
                calculateProcessCost(newRow);
                updateProcessTotal();
                synchronizeCalculations();
            });
        }
    });
    
    synchronizeCalculations();
}

function adicionarLinhaFerramental(tableId) {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar ferramentais.');
        return;
    }

    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const newRow = tbody.insertRow();
    const headerRow = table.querySelector('thead tr');
    if (!headerRow) return;
    
    const headerCells = headerRow.querySelectorAll('th');
    const colCount = headerCells.length;
    
    for (let i = 0; i < colCount - 1; i++) {
        const cell = newRow.insertCell(i);
        const headerText = headerCells[i].textContent.toLowerCase();
        
        if (headerText.includes('código') || headerText.includes('descrição') || headerText.includes('unidade')) {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.value = '';
            cell.appendChild(input);
        } else if (headerText.includes('vida útil')) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm tooling-life-input';
            input.step = '1000';
            input.value = '100000';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (headerText.includes('quantidade')) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm qty-input';
            input.step = '1';
            input.value = '1';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (headerText.includes('valor unit')) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm unit-price-input';
            input.step = '0.01';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (headerText.includes('valor total')) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm total-price';
            input.step = '0.01';
            input.value = '0';
            input.readOnly = true;
            cell.appendChild(input);
        } else if (headerText.includes('custo/peça')) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm tooling-per-piece';
            input.step = '0.00001';
            input.value = '0';
            input.readOnly = true;
            cell.appendChild(input);
        }
    }
    
    const actionCell = newRow.insertCell(colCount - 1);
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm btn-danger remove-row';
    button.innerHTML = '<i class="fas fa-trash"></i>';
    button.addEventListener('click', function() {
        this.closest('tr').remove();
        synchronizeCalculations();
    });
    actionCell.appendChild(button);
    
    synchronizeCalculations();
}

function adicionarLinhaEmbalagem() {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar embalagens.');
        return;
    }

    const table = document.getElementById('embalagemTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const newRow = tbody.insertRow();
    
    for (let i = 0; i < 8; i++) {
        const cell = newRow.insertCell(i);
        
        if (i === 0 || i === 1 || i === 2) {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.value = '';
            cell.appendChild(input);
        } else if (i === 3) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm packaging-qty-input';
            input.step = '1';
            input.value = '1';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 4) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm qty-per-package-input';
            input.step = '1';
            input.value = '1';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 5) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm unit-price-input';
            input.step = '0.01';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 6) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm total-price';
            input.step = '0.01';
            input.value = '0';
            input.readOnly = true;
            cell.appendChild(input);
        } else if (i === 7) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-danger remove-row';
            button.innerHTML = '<i class="fas fa-trash"></i>';
            button.addEventListener('click', function() {
                this.closest('tr').remove();
                updateEmbalagemTotal();
                synchronizeCalculations();
            });
            cell.appendChild(button);
        }
    }
    
    synchronizeCalculations();
}

function adicionarLinhaMOD(tableId) {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar mão de obra.');
        return;
    }

    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const newRow = tbody.insertRow();
    
    for (let i = 0; i < 8; i++) {
        const cell = newRow.insertCell(i);
        
        if (i === 0 || i === 1 || i === 2) {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.value = '';
            cell.appendChild(input);
        } else if (i === 3) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm time-input';
            input.step = '0.1';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 4) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm qty-input';
            input.step = '1';
            input.value = '1';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 5) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm unit-price-input';
            input.step = '0.01';
            input.value = '0';
            input.addEventListener('input', function() { synchronizeCalculations(); });
            cell.appendChild(input);
        } else if (i === 6) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm total-price';
            input.step = '0.01';
            input.value = '0';
            input.readOnly = true;
            cell.appendChild(input);
        } else if (i === 7) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-danger remove-row';
            button.innerHTML = '<i class="fas fa-trash"></i>';
            button.addEventListener('click', function() {
                this.closest('tr').remove();
                synchronizeCalculations();
            });
            cell.appendChild(button);
        }
    }
    
    synchronizeCalculations();
}

function adicionarLinhaCustoIndireto() {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar custos indiretos.');
        return;
    }

    const table = document.getElementById('custosIndiretosTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const newRow = tbody.insertRow();
    
    for (let i = 0; i < 6; i++) {
        const cell = newRow.insertCell(i);
        
        if (i === 0) {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.value = '';
            cell.appendChild(input);
        } else if (i === 1) {
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm';
            
            const optionFixo = document.createElement('option');
            optionFixo.value = 'fixo';
            optionFixo.textContent = 'Custo Fixo';
            
            const optionVariavel = document.createElement('option');
            optionVariavel.value = 'variavel';
            optionVariavel.textContent = 'Custo Variável';
            
            select.appendChild(optionFixo);
            select.appendChild(optionVariavel);
            select.value = 'fixo';
            select.addEventListener('change', function() {
                updateCustosIndiretosTotalCorrigidoComBloqueio();
            });
            cell.appendChild(select);
        } else if (i === 2) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm custo-fixo-input';
            input.step = '0.01';
            input.value = '0';
            input.addEventListener('input', function() {
                updateCustosIndiretosTotalCorrigidoComBloqueio();
            });
            cell.appendChild(input);
        } else if (i === 3) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm rateio-percent-input';
            input.step = '0.1';
            input.value = '100';
            input.addEventListener('input', function() {
                updateCustosIndiretosTotalCorrigidoComBloqueio();
            });
            cell.appendChild(input);
        } else if (i === 4) {
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control form-control-sm rateio-valor';
            input.step = '0.01';
            input.value = '0';
            input.readOnly = true;
            cell.appendChild(input);
        } else if (i === 5) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-danger remove-row';
            button.innerHTML = '<i class="fas fa-trash"></i>';
            button.addEventListener('click', function() {
                this.closest('tr').remove();
                updateCustosIndiretosTotalCorrigidoComBloqueio();
            });
            cell.appendChild(button);
        }
    }
    
    updateCustosIndiretosTotalCorrigidoComBloqueio();
}

function adicionarSelecionadosParaTabela(modalTableId, targetTableId) {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar itens.');
        return;
    }

    const modalTable = document.getElementById(modalTableId);
    const targetTable = document.getElementById(targetTableId);
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos um item para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        if (targetTableId === 'homologacoesTable') {
            const certificacao = cells[1]?.textContent.trim() || '';
            const numero = cells[2]?.textContent.trim() || '';
            const validade = cells[3]?.textContent.trim() || '';
            
            const cell1 = newRow.insertCell(0);
            cell1.textContent = certificacao;
            
            const cell2 = newRow.insertCell(1);
            cell2.textContent = numero;
            
            const cell3 = newRow.insertCell(2);
            cell3.textContent = validade;
            
            const cell4 = newRow.insertCell(3);
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger remove-row';
            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
            removeBtn.addEventListener('click', function() {
                this.closest('tr').remove();
                synchronizeCalculations();
            });
            cell4.appendChild(removeBtn);
            
        } else {
            for (let i = 1; i < cells.length; i++) {
                const cell = newRow.insertCell(i - 1);
                const value = cells[i].textContent.trim();
                
                if (i === cells.length - 1) {
                    cell.textContent = value;
                } else {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control form-control-sm';
                    input.value = value;
                    cell.appendChild(input);
                }
            }
            
            const actionCell = newRow.insertCell();
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger remove-row';
            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
            removeBtn.addEventListener('click', function() {
                this.closest('tr').remove();
                synchronizeCalculations();
            });
            actionCell.appendChild(removeBtn);
        }
        
        checkbox.checked = false;
    });
    
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalTableId.replace('ModalTable', 'Modal')));
    if (modal) {
        modal.hide();
    }
    
    synchronizeCalculations();
}

function adicionarSelecionadosParaMateriais(modalTableId, targetTableId) {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar itens.');
        return;
    }

    const modalTable = document.getElementById(modalTableId);
    const targetTable = document.getElementById(targetTableId);
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos um item para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        const headers = targetTable.querySelectorAll('thead th');
        
        const valores = [];
        for (let i = 1; i < cells.length; i++) {
            valores.push(cells[i]?.textContent.trim() || '');
        }
        
        let valorIndex = 0;
        
        for (let i = 0; i < headers.length - 1; i++) {
            const cell = newRow.insertCell(i);
            const headerText = headers[i].textContent.toLowerCase();
            
            if (headerText.includes('tipo')) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = valores[valorIndex] || '';
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('código')) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = valores[valorIndex] || '';
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('descrição')) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = valores[valorIndex] || '';
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('ncm')) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = valores[valorIndex] || '';
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('espessura')) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = valores[valorIndex] || '';
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('unidade')) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = valores[valorIndex] || '';
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('quantidade')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm qty-input';
                input.step = '0.01';
                input.value = '1';
                input.addEventListener('input', function() { atualizarCalculoMaterial(newRow); });
                cell.appendChild(input);
            } else if (headerText.includes('valor unit')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm unit-price-input';
                input.step = '0.01';
                input.value = parseFloat(valores[valorIndex]?.replace('R$', '').trim()) || 0;
                input.addEventListener('input', function() { atualizarCalculoMaterial(newRow); });
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('alíq. ipi')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm ipi-aliquota-input';
                input.step = '0.1';
                input.value = parseFloat(valores[valorIndex]?.replace('%', '')) || 0;
                input.addEventListener('input', function() { atualizarCalculoMaterial(newRow); });
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('alíq. icms')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm icms-aliquota-input';
                input.step = '0.1';
                input.value = parseFloat(valores[valorIndex]?.replace('%', '')) || 0;
                input.addEventListener('input', function() { atualizarCalculoMaterial(newRow); });
                cell.appendChild(input);
                valorIndex++;
            } else if (headerText.includes('valor total c/ impostos')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm gross-total';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            } else if (headerText.includes('valor crédito')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm credit-value';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            } else if (headerText.includes('valor líquido')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm total-price';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            }
        }
        
        const actionCell = newRow.insertCell();
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger remove-row';
        removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
        removeBtn.addEventListener('click', function() {
            this.closest('tr').remove();
            synchronizeCalculations();
        });
        actionCell.appendChild(removeBtn);
        
        atualizarCalculoMaterial(newRow);
        
        checkbox.checked = false;
    });
    
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalTableId.replace('ModalTable', 'Modal')));
    if (modal) {
        modal.hide();
    }
    
    synchronizeCalculations();
}

function adicionarSelecionadosParaProcesso() {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar processos.');
        return;
    }

    const modalTable = document.getElementById('recursosModalTable');
    const targetTable = document.getElementById('processTable');
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos um recurso para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        const processo = cells[1]?.textContent.trim() || '';
        const recurso = cells[2]?.textContent.trim() || '';
        const potencia = cells[3]?.textContent.trim() || '0';
        const precoKwh = cells[4]?.textContent.trim() || '0.85';
        const agua = cells[5]?.textContent.trim() || '0';
        const precoAgua = cells[6]?.textContent.trim() || '8.50';
        const rendimento = cells[7]?.textContent.trim() || '100';
        const producaoHora = cells[8]?.textContent.trim() || '1';
        const setup = cells[9]?.textContent.trim() || '0';
        const depreciacao = cells[10]?.textContent.trim() || '0';
        const outrosCustos = cells[11]?.textContent.trim() || '0';
        
        for (let i = 0; i < 14; i++) {
            const cell = newRow.insertCell(i);
            
            if (i === 0) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = processo;
                cell.appendChild(input);
            } else if (i === 1) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = recurso;
                cell.appendChild(input);
            } else if (i === 2) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm power-input';
                input.step = '0.1';
                input.value = parseFloat(potencia) || 0;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm energy-price-input';
                input.step = '0.01';
                input.value = parseFloat(precoKwh) || 0.85;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm water-input';
                input.step = '0.01';
                input.value = parseFloat(agua) || 0;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 5) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm water-price-input';
                input.step = '0.01';
                input.value = parseFloat(precoAgua) || 8.50;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 6) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm efficiency-input';
                input.value = parseFloat(rendimento) || 100;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 7) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm output-input';
                input.step = '0.1';
                input.value = parseFloat(producaoHora) || 1;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 8) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm setup-time-input';
                input.step = '0.1';
                input.value = parseFloat(setup) || 0;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 9) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm depreciation-input';
                input.step = '0.01';
                input.value = parseFloat(depreciacao) || 0;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 10) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm other-costs-input';
                input.step = '0.01';
                input.value = parseFloat(outrosCustos) || 0;
                input.addEventListener('input', function() { atualizarProcesso(newRow); });
                cell.appendChild(input);
            } else if (i === 11) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm hourly-cost-input';
                input.step = '0.01';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            } else if (i === 12) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm process-total';
                input.step = '0.01';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            } else if (i === 13) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-danger remove-row';
                button.innerHTML = '<i class="fas fa-trash"></i>';
                button.addEventListener('click', function() {
                    this.closest('tr').remove();
                    updateProcessTotal();
                    synchronizeCalculations();
                });
                cell.appendChild(button);
            }
        }
        
        atualizarProcesso(newRow);
        
        checkbox.checked = false;
    });
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('recursosModal'));
    if (modal) {
        modal.hide();
    }
    
    updateProcessTotal();
    synchronizeCalculations();
}

function adicionarSelecionadosParaFerramental(modalTableId, targetTableId) {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar itens.');
        return;
    }

    const modalTable = document.getElementById(modalTableId);
    const targetTable = document.getElementById(targetTableId);
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos um item para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        const descricao = cells[1]?.textContent.trim() || '';
        const vidaUtil = cells[2]?.textContent.trim() || '100000';
        const valorUnit = cells[3]?.textContent.trim() || '0';
        
        for (let i = 0; i < 7; i++) {
            const cell = newRow.insertCell(i);
            
            if (i === 0) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = descricao;
                cell.appendChild(input);
            } else if (i === 1) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm tooling-life-input';
                input.step = '1000';
                input.value = vidaUtil;
                input.addEventListener('input', function() { atualizarCalculoFerramental(newRow); });
                cell.appendChild(input);
            } else if (i === 2) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm unit-price-input';
                input.step = '0.01';
                input.value = parseFloat(valorUnit) || 0;
                input.addEventListener('input', function() { atualizarCalculoFerramental(newRow); });
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm qty-input';
                input.step = '1';
                input.value = '1';
                input.addEventListener('input', function() { atualizarCalculoFerramental(newRow); });
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm total-price';
                input.step = '0.01';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            } else if (i === 5) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm tooling-per-piece';
                input.step = '0.00001';
                input.readOnly = true;
                input.value = '0';
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
        
        atualizarCalculoFerramental(newRow);
        
        checkbox.checked = false;
    });
    
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalTableId.replace('ModalTable', 'Modal')));
    if (modal) {
        modal.hide();
    }
    
    synchronizeCalculations();
}

function adicionarSelecionadosParaMateriaisFerramental() {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar itens.');
        return;
    }

    const modalTable = document.getElementById('materiaisFerramentalModalTable');
    const targetTableId = window.currentTargetTable || 'materiaisFerramentalTable';
    const targetTable = document.getElementById(targetTableId);
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos um item para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        const codigo = cells[1]?.textContent.trim() || '';
        const descricao = cells[2]?.textContent.trim() || '';
        const unidade = cells[3]?.textContent.trim() || '';
        const vidaUtil = cells[4]?.textContent.trim() || '100000';
        const valorUnit = cells[5]?.textContent.trim() || '0';
        
        for (let i = 0; i < 8; i++) {
            const cell = newRow.insertCell(i);
            
            if (i === 0) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = codigo;
                cell.appendChild(input);
            } else if (i === 1) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = descricao;
                cell.appendChild(input);
            } else if (i === 2) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = unidade;
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm tooling-life-input';
                input.step = '1000';
                input.value = vidaUtil;
                input.addEventListener('input', function() { atualizarCalculoFerramental(newRow); });
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm qty-input';
                input.step = '1';
                input.value = '1';
                input.addEventListener('input', function() { atualizarCalculoFerramental(newRow); });
                cell.appendChild(input);
            } else if (i === 5) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm unit-price-input';
                input.step = '0.01';
                input.value = parseFloat(valorUnit) || 0;
                input.addEventListener('input', function() { atualizarCalculoFerramental(newRow); });
                cell.appendChild(input);
            } else if (i === 6) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm total-price';
                input.step = '0.01';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            } else if (i === 7) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm tooling-per-piece';
                input.step = '0.00001';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            }
        }
        
        const actionCell = newRow.insertCell(8);
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger remove-row';
        removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
        removeBtn.addEventListener('click', function() {
            this.closest('tr').remove();
            synchronizeCalculations();
        });
        actionCell.appendChild(removeBtn);
        
        atualizarCalculoFerramental(newRow);
        
        checkbox.checked = false;
    });
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('materiaisFerramentalModal'));
    if (modal) {
        modal.hide();
    }
    
    synchronizeCalculations();
}

function adicionarSelecionadosParaCustosIndiretos() {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar itens.');
        return;
    }

    const modalTable = document.getElementById('custosIndiretosModalTable');
    const targetTable = document.getElementById('custosIndiretosTable');
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos um custo para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        const descricao = cells[1]?.textContent.trim() || '';
        const tipo = cells[2]?.textContent.trim() || 'fixo';
        const valorMensal = cells[3]?.textContent.trim() || '0';
        
        for (let i = 0; i < 5; i++) {
            const cell = newRow.insertCell(i);
            
            if (i === 0) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = descricao;
                cell.appendChild(input);
            } else if (i === 1) {
                const select = document.createElement('select');
                select.className = 'form-select form-select-sm';
                
                const optionFixo = document.createElement('option');
                optionFixo.value = 'fixo';
                optionFixo.textContent = 'Custo Fixo';
                
                const optionVariavel = document.createElement('option');
                optionVariavel.value = 'variavel';
                optionVariavel.textContent = 'Custo Variável';
                
                select.appendChild(optionFixo);
                select.appendChild(optionVariavel);
                select.value = tipo;
                select.addEventListener('change', function() {
                    updateCustosIndiretosTotalCorrigidoComBloqueio();
                });
                cell.appendChild(select);
            } else if (i === 2) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm custo-fixo-input';
                input.step = '0.01';
                input.value = parseFloat(valorMensal) || 0;
                input.addEventListener('input', function() {
                    updateCustosIndiretosTotalCorrigidoComBloqueio();
                });
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm rateio-percent-input';
                input.step = '0.1';
                input.value = '100';
                input.addEventListener('input', function() {
                    updateCustosIndiretosTotalCorrigidoComBloqueio();
                });
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm rateio-valor';
                input.step = '0.01';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            }
        }
        
        const actionCell = newRow.insertCell(5);
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger remove-row';
        removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
        removeBtn.addEventListener('click', function() {
            this.closest('tr').remove();
            updateCustosIndiretosTotalCorrigidoComBloqueio();
        });
        actionCell.appendChild(removeBtn);
        
        checkbox.checked = false;
    });
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('custosIndiretosModal'));
    if (modal) {
        modal.hide();
    }
    
    updateCustosIndiretosTotalCorrigidoComBloqueio();
}

function adicionarSelecionadosParaEmbalagem() {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar itens.');
        return;
    }

    const modalTable = document.getElementById('embalagemModalTable');
    const targetTable = document.getElementById('embalagemTable');
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos uma embalagem para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        const codigo = cells[1]?.textContent.trim() || '';
        const descricao = cells[2]?.textContent.trim() || '';
        const unidade = cells[3]?.textContent.trim() || '';
        const valorUnit = cells[4]?.textContent.trim() || '0';
        
        for (let i = 0; i < 7; i++) {
            const cell = newRow.insertCell(i);
            
            if (i === 0) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = codigo;
                cell.appendChild(input);
            } else if (i === 1) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = descricao;
                cell.appendChild(input);
            } else if (i === 2) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = unidade;
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm packaging-qty-input';
                input.step = '1';
                input.value = '1';
                input.addEventListener('input', function() { atualizarCalculoEmbalagem(newRow); });
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm qty-per-package-input';
                input.step = '1';
                input.value = '1';
                input.addEventListener('input', function() { atualizarCalculoEmbalagem(newRow); });
                cell.appendChild(input);
            } else if (i === 5) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm unit-price-input';
                input.step = '0.01';
                input.value = parseFloat(valorUnit) || 0;
                input.addEventListener('input', function() { atualizarCalculoEmbalagem(newRow); });
                cell.appendChild(input);
            } else if (i === 6) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm total-price';
                input.step = '0.01';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            }
        }
        
        const actionCell = newRow.insertCell(7);
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger remove-row';
        removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
        removeBtn.addEventListener('click', function() {
            this.closest('tr').remove();
            updateEmbalagemTotal();
            synchronizeCalculations();
        });
        actionCell.appendChild(removeBtn);
        
        atualizarCalculoEmbalagem(newRow);
        
        checkbox.checked = false;
    });
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('embalagemModal'));
    if (modal) {
        modal.hide();
    }
    
    updateEmbalagemTotal();
    synchronizeCalculations();
}

function adicionarSelecionadosParaMaoObra(modalTableId, targetTableId) {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem adicionar itens.');
        return;
    }

    const modalTable = document.getElementById(modalTableId);
    const targetTable = document.getElementById(targetTableId);
    
    if (!modalTable || !targetTable) return;
    
    const checkboxes = modalTable.querySelectorAll('tbody input[type="checkbox"].select-item:checked');
    if (checkboxes.length === 0) {
        alert('Selecione pelo menos um item para adicionar.');
        return;
    }
    
    const tbody = targetTable.querySelector('tbody');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        
        const newRow = tbody.insertRow();
        
        const funcao = cells[1]?.textContent.trim() || '';
        const setor = cells[2]?.textContent.trim() || '';
        const centroCusto = cells[3]?.textContent.trim() || '';
        const salarioHora = cells[4]?.textContent.trim() || '0';
        
        for (let i = 0; i < 7; i++) {
            const cell = newRow.insertCell(i);
            
            if (i === 0) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = funcao;
                cell.appendChild(input);
            } else if (i === 1) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = setor;
                cell.appendChild(input);
            } else if (i === 2) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = centroCusto;
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm time-input';
                input.step = '0.1';
                input.value = '0';
                input.addEventListener('input', function() { atualizarCalculoMaoObra(newRow); });
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm qty-input';
                input.step = '1';
                input.value = '1';
                input.addEventListener('input', function() { atualizarCalculoMaoObra(newRow); });
                cell.appendChild(input);
            } else if (i === 5) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm unit-price-input';
                input.step = '0.01';
                input.value = parseFloat(salarioHora) || 0;
                input.addEventListener('input', function() { atualizarCalculoMaoObra(newRow); });
                cell.appendChild(input);
            } else if (i === 6) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm total-price';
                input.step = '0.01';
                input.readOnly = true;
                input.value = '0';
                cell.appendChild(input);
            }
        }
        
        const actionCell = newRow.insertCell(7);
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger remove-row';
        removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
        removeBtn.addEventListener('click', function() {
            this.closest('tr').remove();
            synchronizeCalculations();
        });
        actionCell.appendChild(removeBtn);
        
        atualizarCalculoMaoObra(newRow);
        
        checkbox.checked = false;
    });
    
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalTableId.replace('ModalTable', 'Modal')));
    if (modal) {
        modal.hide();
    }
    
    synchronizeCalculations();
}

function atualizarCalculoMaterial(row) {
    const qtyInput = row.querySelector('.qty-input');
    const priceInput = row.querySelector('.unit-price-input');
    const ipiInput = row.querySelector('.ipi-aliquota-input');
    const icmsInput = row.querySelector('.icms-aliquota-input');
    const grossTotal = row.querySelector('.gross-total');
    const creditInput = row.querySelector('.credit-value');
    const totalInput = row.querySelector('.total-price');

    if (qtyInput && priceInput && totalInput) {
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const ipi = parseFloat(ipiInput?.value) || 0;
        const icms = parseFloat(icmsInput?.value) || 0;

        const valorBruto = qty * price;
        const valorIPI = valorBruto * (ipi / 100);
        
        const baseICMS = valorBruto / (1 - icms/100);
        const valorICMS = baseICMS * (icms/100);
        
        const valorTotal = valorBruto + valorIPI + valorICMS;

        if (grossTotal) grossTotal.value = valorTotal.toFixed(2);

        const isNaoCumulativo = document.getElementById('regimePisCofins')?.value === 'nao-cumulativo';
        
        const aliquotaPIS = parseFloat(document.getElementById('aliquotaPIS')?.value) || 1.65;
        const aliquotaCOFINS = parseFloat(document.getElementById('aliquotaCOFINS')?.value) || 7.6;
        
        let credito = 0;
        if (isNaoCumulativo) {
            const creditoIPI = valorIPI;
            const creditoICMS = valorICMS;
            const creditoPIS = valorBruto * (aliquotaPIS / 100);
            const creditoCOFINS = valorBruto * (aliquotaCOFINS / 100);
            credito = creditoIPI + creditoICMS + creditoPIS + creditoCOFINS;
        } else {
            const creditoIPI = valorIPI;
            const creditoICMS = valorICMS;
            credito = creditoIPI + creditoICMS;
        }

        if (creditInput) creditInput.value = credito.toFixed(2);
        totalInput.value = (valorTotal - credito).toFixed(2);
    }
}

function atualizarCalculoFerramental(row) {
    const lifeInput = row.querySelector('.tooling-life-input');
    const priceInput = row.querySelector('.unit-price-input');
    const qtyInput = row.querySelector('.qty-input');
    const totalInput = row.querySelector('.total-price');
    const perPieceInput = row.querySelector('.tooling-per-piece');

    if (lifeInput && priceInput && qtyInput && totalInput && perPieceInput) {
        const life = parseFloat(lifeInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const qty = parseFloat(qtyInput.value) || 1;
        const total = price * qty;
        totalInput.value = total.toFixed(2);

        if (life <= 0) {
            perPieceInput.value = '0.00000';
            lifeInput.classList.add('is-invalid');
        } else {
            perPieceInput.value = (total / life).toFixed(5);
            lifeInput.classList.remove('is-invalid');
        }
    }
}

function atualizarProcesso(row) {
    const power = parseFloat(row.querySelector('.power-input')?.value) || 0;
    const energyPrice = parseFloat(row.querySelector('.energy-price-input')?.value) || 0;
    const water = parseFloat(row.querySelector('.water-input')?.value) || 0;
    const waterPrice = parseFloat(row.querySelector('.water-price-input')?.value) || 0;
    const depreciationMonthly = parseFloat(row.querySelector('.depreciation-input')?.value) || 0;
    const otherCosts = parseFloat(row.querySelector('.other-costs-input')?.value) || 0;
    const efficiency = parseFloat(row.querySelector('.efficiency-input')?.value) || 100;
    const output = parseFloat(row.querySelector('.output-input')?.value) || 1;
    const setupTime = parseFloat(row.querySelector('.setup-time-input')?.value) || 0;
    
    const hoursPerMonth = parseFloat(document.getElementById('horasTrabalhadasMes')?.value) || 176;

    const energyCost = power * energyPrice;
    const waterCost = water * waterPrice;
    const depreciationHourly = hoursPerMonth > 0 ? depreciationMonthly / hoursPerMonth : 0;
    const totalHourlyCost = energyCost + waterCost + depreciationHourly + otherCosts;

    const hourlyCostInput = row.querySelector('.hourly-cost-input');
    if (hourlyCostInput) {
        hourlyCostInput.value = totalHourlyCost.toFixed(2);
    }

    const producaoEfetiva = output * (efficiency / 100);
    const operationalCostPerPiece = producaoEfetiva > 0 ? totalHourlyCost / producaoEfetiva : 0;
    const setupCostPerPiece = producaoEfetiva > 0 ? (totalHourlyCost * (setupTime / 60)) / producaoEfetiva : 0;
    const totalCost = operationalCostPerPiece + setupCostPerPiece;

    const totalInput = row.querySelector('.process-total');
    if (totalInput) {
        totalInput.value = totalCost.toFixed(4);
    }
}

function atualizarCalculoEmbalagem(row) {
    const packagingQty = parseFloat(row.querySelector('.packaging-qty-input')?.value) || 1;
    const qtyPerPackage = parseFloat(row.querySelector('.qty-per-package-input')?.value) || 1;
    const unitPrice = parseFloat(row.querySelector('.unit-price-input')?.value) || 0;
    const totalInput = row.querySelector('.total-price');

    if (qtyPerPackage > 0 && totalInput) {
        const totalPerPiece = (unitPrice * packagingQty) / qtyPerPackage;
        totalInput.value = totalPerPiece.toFixed(2);
    }
}

function sincronizarMargemLucro() {
    const margem = document.getElementById('margemLucroMarkup');
    const margemDup = document.getElementById('margemLucroMarkup_dup');
    if (margem && margemDup) {
        margem.addEventListener('input', function() {
            margemDup.value = this.value;
        });
        margemDup.value = margem.value;
    }
}

let calculationTimeout = null;

function synchronizeCalculations() {
    if (calculationTimeout) {
        clearTimeout(calculationTimeout);
    }
    calculationTimeout = setTimeout(() => {
        if (!window.rateioBloqueado) {
            updateAllCalculations();
        } else {
            updateCustosIndiretosTotalCorrigidoComBloqueio();
            validarConsistenciaGeral();
        }
    }, 150);
}

function setupModalButtons() {
    document.querySelectorAll('.export-excel').forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-table-id');
            exportToExcel(tableId);
        });
    });

    document.querySelectorAll('.import-excel').forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-table-id');
            importExcel(tableId);
        });
    });

    document.querySelectorAll('.save-database').forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-table-id');
            saveTableToLocalStorage(tableId);
        });
    });

    document.querySelectorAll('.load-database').forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-table-id');
            loadTableFromLocalStorage(tableId);
        });
    });

    document.querySelectorAll('.search-input').forEach(input => {
        input.addEventListener('input', function() {
            const tableId = this.getAttribute('data-table-id');
            filterModalTable(tableId, this.value);
        });
    });

    document.querySelectorAll('[data-bs-target="#materiaisFerramentalModal"]').forEach(button => {
        button.addEventListener('click', function() {
            const targetTable = this.getAttribute('data-target-table');
            if (targetTable) {
                window.currentTargetTable = targetTable;
            }
        });
    });
}

function filterModalTable(tableId, searchText) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchText.toLowerCase().trim();

    if (!searchText) {
        rows.forEach(row => {
            row.style.display = '';
        });
        return;
    }

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let rowText = '';

        cells.forEach((cell, index) => {
            if (index > 0) {
                rowText += ' ' + cell.textContent.toLowerCase();
            }
        });

        if (rowText.includes(searchLower)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterTable(tableId, searchText) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const rows = tbody.querySelectorAll('tr');
    const searchLower = searchText.toLowerCase().trim();

    if (!searchText) {
        rows.forEach(row => {
            row.style.display = '';
        });
        return;
    }

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let rowText = '';

        cells.forEach((cell, index) => {
            if (index < cells.length - 1) {
                rowText += ' ' + cell.textContent.toLowerCase();
            }
        });

        if (rowText.includes(searchLower)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function exportToExcel(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const workbook = XLSX.utils.book_new();
    const worksheet = XLSX.utils.table_to_sheet(table);
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Dados');

    const fileName = `${tableId}_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(workbook, fileName);
    
    mostrarNotificacao('Dados exportados com sucesso!', 'success');
}

function importExcel(tableId) {
    if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
        alert('Visitantes não podem importar dados.');
        return;
    }

    const fileInput = document.getElementById(`${tableId}File`);
    if (!fileInput) return;

    fileInput.click();

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

                const table = document.getElementById(tableId);
                if (!table) return;

                const tbody = table.querySelector('tbody');
                if (!tbody) return;

                tbody.innerHTML = '';

                let startRow = 0;
                if (jsonData.length > 0 && Array.isArray(jsonData[0])) {
                    const firstRow = jsonData[0];
                    const hasTextHeader = firstRow.some(cell => typeof cell === 'string' && cell.trim() !== '' && isNaN(parseFloat(cell)));
                    if (hasTextHeader) {
                        startRow = 1;
                    }
                }

                for (let i = startRow; i < jsonData.length; i++) {
                    const rowData = jsonData[i];
                    if (!Array.isArray(rowData) || rowData.length === 0) continue;

                    const newRow = tbody.insertRow();

                    const checkboxCell = newRow.insertCell(0);
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'select-item';
                    checkboxCell.appendChild(checkbox);

                    for (let j = 1; j < rowData.length + 1; j++) {
                        const cell = newRow.insertCell(j);
                        const value = rowData[j-1];

                        if (value !== null && value !== undefined) {
                            if (typeof value === 'number') {
                                cell.textContent = value.toString().replace('.', ',');
                            } else {
                                cell.textContent = value.toString().trim();
                            }
                        }
                    }

                    const expectedCols = table.querySelector('thead tr')?.querySelectorAll('th').length || rowData.length + 1;
                    for (let j = rowData.length + 1; j < expectedCols; j++) {
                        newRow.insertCell(j);
                    }
                }

                rebuildCheckboxes(tableId);

                mostrarNotificacao('Dados importados com sucesso!', 'success');

            } catch (error) {
                console.error('Erro ao importar Excel:', error);
                alert('Erro ao importar arquivo Excel. Verifique o formato do arquivo.');
            }
        };

        reader.readAsArrayBuffer(file);
    }, { once: true });
}

function saveTableToLocalStorage(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    const data = [];

    rows.forEach(row => {
        const rowData = {};
        const cells = row.querySelectorAll('td');

        cells.forEach((cell, index) => {
            const checkbox = cell.querySelector('input[type="checkbox"].select-item');
            const text = cell.textContent.trim();

            if (checkbox) {
                rowData[`col${index}`] = checkbox.checked;
            } else if (text) {
                rowData[`col${index}`] = text;
            }
        });

        if (Object.keys(rowData).length > 0) {
            data.push(rowData);
        }
    });

    localStorage.setItem(`${tableId}_data`, JSON.stringify(data));
    alert(`✅ Dados da tabela ${tableId} salvos com sucesso!`);
}

function loadTableFromLocalStorage(tableId) {
    const storedData = localStorage.getItem(`${tableId}_data`);
    if (!storedData) {
        alert(`Nenhum dado salvo encontrado para tabela ${tableId}`);
        return;
    }

    const data = JSON.parse(storedData);
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    data.forEach(rowData => {
        const newRow = tbody.insertRow();

        Object.keys(rowData).forEach((key, index) => {
            const cell = newRow.insertCell(index);
            const value = rowData[key];

            if (typeof value === 'boolean') {
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'select-item';
                checkbox.checked = value;
                cell.appendChild(checkbox);
            } else {
                cell.textContent = value;
            }
        });
    });

    setTimeout(() => {
        rebuildCheckboxes(tableId);
    }, 100);
    
    mostrarNotificacao('Dados carregados com sucesso!', 'success');
}

function rebuildCheckboxes(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');

    rows.forEach((row, index) => {
        const firstCell = row.querySelector('td:first-child');
        if (firstCell && !firstCell.querySelector('input[type="checkbox"].select-item')) {
            firstCell.innerHTML = '';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'select-item';

            firstCell.appendChild(checkbox);
        }
    });
}

