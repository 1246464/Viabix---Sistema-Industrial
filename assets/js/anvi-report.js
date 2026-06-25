async function gerarPDFCompleto(anviEspecifica = null) {
    try {
        const loadingToast = document.createElement('div');
        loadingToast.className = 'pdf-preview-card show';
        loadingToast.style.background = 'linear-gradient(145deg, #0a3d2e, #1a5a3a)';
        loadingToast.style.color = 'white';
        loadingToast.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><p class="mb-0">Gerando PDF profissional, aguarde...</p></div>';
        document.body.appendChild(loadingToast);

        const dados = anviEspecifica || capturarTodosDados();

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4'
        });
        doc.setFont('helvetica');

        const pageWidth = doc.internal.pageSize.width;
        const pageHeight = doc.internal.pageSize.height;
        const margin = 15;
        let yPos = margin;
        let pageNum = 1;

        function addHeader() {
            doc.setFillColor(10, 61, 46);
            doc.rect(0, 0, pageWidth, 12, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'bold');
            doc.text(`Viabix - ${dados.informacoesBasicas?.anviNumber || 'N/A'}`, margin, 8);
            doc.setFont('helvetica', 'normal');
            doc.text(`Página ${pageNum}`, pageWidth - margin - 10, 8);
        }

        function addFooter() {
            doc.setFontSize(7);
            doc.setTextColor(100, 100, 100);
            doc.text(`Documento gerado em ${new Date().toLocaleDateString('pt-BR')}`, margin, pageHeight - 8);
            doc.text('Sistema Viabix - Modelo Industrial 10/10', pageWidth - margin - 70, pageHeight - 8);
        }

        function checkSpace(neededHeight) {
            if (yPos + neededHeight > pageHeight - margin - 15) {
                addFooter();
                doc.addPage();
                pageNum++;
                yPos = margin + 5;
                addHeader();
                return true;
            }
            return false;
        }

        function addSectionTitle(texto) {
            checkSpace(10);
            doc.setFillColor(212, 160, 23);
            doc.rect(margin, yPos - 3, 5, 6, 'F');
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(10, 61, 46);
            doc.text(texto, margin + 10, yPos);
            yPos += 8;
            doc.setTextColor(60, 60, 60);
        }

        function addInfoLine(label, valor, x = margin, width = 60) {
            doc.setFontSize(9);
            doc.setFont('helvetica', 'bold');
            doc.text(label, x, yPos);
            doc.setFont('helvetica', 'normal');
            doc.text(valor, x + width, yPos);
            yPos += 5;
        }

        function addInfoLineDouble(label1, valor1, label2, valor2) {
            doc.setFontSize(9);
            doc.setFont('helvetica', 'bold');
            doc.text(label1, margin, yPos);
            doc.setFont('helvetica', 'normal');
            doc.text(valor1, margin + 55, yPos);

            doc.setFont('helvetica', 'bold');
            doc.text(label2, margin + 120, yPos);
            doc.setFont('helvetica', 'normal');
            doc.text(valor2, margin + 180, yPos);
            yPos += 5;
        }

        function drawTable(headers, data, colWidths) {
            const startX = margin;
            const cellHeight = 6;
            let currentY = yPos;

            const minColWidth = 15;
            const adjustedWidths = colWidths.map(w => Math.max(w, minColWidth));
            
            const totalWidth = pageWidth - margin * 2;
            const totalColWidth = adjustedWidths.reduce((a, b) => a + b, 0);
            
            let finalWidths = adjustedWidths;
            if (totalColWidth > totalWidth) {
                const scaleFactor = totalWidth / totalColWidth;
                finalWidths = adjustedWidths.map(w => w * scaleFactor);
            } else if (totalColWidth < totalWidth) {
                const extraSpace = totalWidth - totalColWidth;
                const descriptionColumns = [1, 2]; 
                const extraPerDescCol = extraSpace / descriptionColumns.length;
                
                finalWidths = [...adjustedWidths];
                descriptionColumns.forEach(idx => {
                    if (idx < finalWidths.length) {
                        finalWidths[idx] += extraPerDescCol;
                    }
                });
            }

            doc.setFillColor(10, 61, 46);
            doc.rect(startX, currentY, totalWidth, cellHeight, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(7);
            doc.setFont('helvetica', 'bold');

            let xPos = startX;
            headers.forEach((header, i) => {
                const headerText = header;
                if (headerText.length > 12) {
                    const words = headerText.split(' ');
                    let line1 = words.slice(0, Math.ceil(words.length/2)).join(' ');
                    let line2 = words.slice(Math.ceil(words.length/2)).join(' ');
                    doc.text(line1, xPos + 2, currentY + 3);
                    doc.text(line2, xPos + 2, currentY + 6);
                } else {
                    doc.text(headerText, xPos + 2, currentY + 4);
                }
                xPos += finalWidths[i];
            });

            currentY += cellHeight;

            doc.setTextColor(60, 60, 60);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(6.5);

            data.forEach((row, rowIndex) => {
                if (currentY + cellHeight > pageHeight - margin - 10) {
                    addFooter();
                    doc.addPage();
                    pageNum++;
                    currentY = margin + 5;
                    addHeader();

                    doc.setFillColor(10, 61, 46);
                    doc.rect(startX, currentY, totalWidth, cellHeight, 'F');
                    doc.setTextColor(255, 255, 255);
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(7);
                    
                    xPos = startX;
                    headers.forEach((header, i) => {
                        if (header.length > 12) {
                            const words = header.split(' ');
                            let line1 = words.slice(0, Math.ceil(words.length/2)).join(' ');
                            let line2 = words.slice(Math.ceil(words.length/2)).join(' ');
                            doc.text(line1, xPos + 2, currentY + 3);
                            doc.text(line2, xPos + 2, currentY + 6);
                        } else {
                            doc.text(header, xPos + 2, currentY + 4);
                        }
                        xPos += finalWidths[i];
                    });
                    
                    currentY += cellHeight;
                    doc.setTextColor(60, 60, 60);
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(6.5);
                }

                if (rowIndex % 2 === 0) {
                    doc.setFillColor(245, 245, 245);
                    doc.rect(startX, currentY, totalWidth, cellHeight, 'F');
                }

                xPos = startX;
                row.forEach((cell, i) => {
                    let text = cell.toString().trim();
                    
                    const maxLength = Math.floor(finalWidths[i] / 3.2);
                    if (text.length > maxLength) {
                        text = text.substring(0, maxLength - 3) + '...';
                    }
                    
                    doc.text(text, xPos + 2, currentY + 4);
                    xPos += finalWidths[i];
                });

                currentY += cellHeight;
            });

            yPos = currentY + 5;
        }

        // ==================== CAPA ====================
        doc.setFillColor(10, 61, 46);
        doc.rect(0, 0, pageWidth, pageHeight, 'F');

        doc.setTextColor(255, 255, 255);
        doc.setFontSize(36);
        doc.setFont('helvetica', 'bold');
        doc.text('VIABIX', pageWidth/2 - 40, 80);

        doc.setFontSize(14);
        doc.setFont('helvetica', 'normal');
        doc.text('ANÁLISE DE VIABILIDADE', pageWidth/2 - 45, 100);

        doc.setDrawColor(212, 160, 23);
        doc.setLineWidth(1);
        doc.line(margin, 110, pageWidth - margin, 110);

        doc.setFontSize(20);
        doc.setFont('helvetica', 'bold');
        doc.text('RELATÓRIO TÉCNICO', pageWidth/2 - 45, 140);

        doc.setFontSize(14);
        doc.setFont('helvetica', 'normal');
        doc.text(`Nº ${dados.informacoesBasicas?.anviNumber || 'N/A'}`, pageWidth/2 - 35, 170);
        doc.text(`Revisão: ${dados.informacoesBasicas?.revisaoANVI || '00'}`, pageWidth/2 - 25, 185);
        doc.text(`Data: ${dados.informacoesBasicas?.dataANVI || new Date().toLocaleDateString('pt-BR')}`, pageWidth/2 - 25, 200);

        doc.addPage();
        pageNum = 2;
        yPos = margin + 5;
        addHeader();

        // ==================== 1. DADOS DO PROJETO ====================
        addSectionTitle('1. DADOS DO PROJETO');

        addInfoLineDouble('Nº ANVI:', dados.informacoesBasicas?.anviNumber || 'N/A',
                      'Data da ANVI:', dados.informacoesBasicas?.dataANVI || 'N/A');
        addInfoLineDouble('Revisão:', dados.informacoesBasicas?.revisaoANVI || 'N/A',
                      'Base Econômica:', dados.informacoesBasicas?.lastUpdateDate || 'N/A');

        const status = dados.informacoesBasicas?.statusAprovacao || 'N/A';
        let statusDisplay = status;
        if (status === 'em-andamento') statusDisplay = 'Em Andamento';
        else if (status === 'aprovada') statusDisplay = 'Aprovada';
        else if (status === 'aprovada-condicional') statusDisplay = 'Aprovada Condicional';
        else if (status === 'declinada') statusDisplay = 'Declinada';

        addInfoLineDouble('Status:', statusDisplay, 'Cliente:', dados.informacoesBasicas?.client || 'N/A');
        addInfoLineDouble('Projeto/Veículo:', dados.informacoesBasicas?.project || 'N/A',
                      'Código Produto:', dados.informacoesBasicas?.codigo || 'N/A');

        doc.setFont('helvetica', 'bold');
        doc.text('Descrição do Produto:', margin, yPos);
        doc.setFont('helvetica', 'normal');
        doc.text(dados.informacoesBasicas?.productDescription || 'N/A', margin + 60, yPos);
        yPos += 5;

        addInfoLineDouble('Segmento:', dados.informacoesBasicas?.segment || 'N/A',
                      'Volume Mensal:', `${dados.informacoesBasicas?.monthlyVolume || '0'} peças`);
        addInfoLineDouble('Data do Desenho:', dados.informacoesBasicas?.desenhoDate || 'N/A',
                      'Revisão do Desenho:', dados.informacoesBasicas?.revisao || 'N/A');
        addInfoLineDouble('Tipo de Vidro:', dados.informacoesBasicas?.glassType || 'N/A',
                      'Geometria:', dados.informacoesBasicas?.geometry || 'N/A');
        addInfoLineDouble('Espessura (mm):', dados.informacoesBasicas?.thickness || 'N/A',
                      'Cor:', dados.informacoesBasicas?.glassColor || 'N/A');
        addInfoLineDouble('Largura (mm):', dados.informacoesBasicas?.width || '0',
                      'Altura (mm):', dados.informacoesBasicas?.height || '0');
        addInfoLineDouble('Área da Peça (m²):', (parseFloat(dados.informacoesBasicas?.area) || 0).toFixed(4),
                      'Área Comercial (m²):', (parseFloat(dados.informacoesBasicas?.commercialArea) || 0).toFixed(4));
        addInfoLineDouble('Resp. Técnica:', dados.informacoesBasicas?.responsavelTecnica || 'N/A',
                      'Resp. Comercial:', dados.informacoesBasicas?.responsavelComercial || 'N/A');
        addInfoLineDouble('Resp. Econômica:', dados.informacoesBasicas?.responsavelEconomica || 'N/A',
                      'Resp. Fiscal:', dados.informacoesBasicas?.responsavelFiscal || 'N/A');

        doc.setFont('helvetica', 'bold');
        doc.text('Observação Geral:', margin, yPos);
        yPos += 5;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        const obs = dados.informacoesBasicas?.observacaoGeral || 'N/A';
        const obsLines = doc.splitTextToSize(obs, pageWidth - margin * 2);
        obsLines.forEach(line => {
            doc.text(line, margin, yPos);
            yPos += 4;
        });
        yPos += 5;
        doc.setFontSize(9);

        // ==================== 2. MATÉRIA PRIMA ====================
        checkSpace(60);
        addSectionTitle('2. MATÉRIA PRIMA');

        const mpRows = dados.tabelas?.materiaPrima || [];
        if (mpRows.length === 0) {
            doc.text('Nenhuma matéria prima cadastrada.', margin, yPos);
            yPos += 5;
        } else {
            const headers = ['Tipo', 'Código', 'Descrição', 'NCM', 'Esp', 'Un', 'Qtd', 'R$ Unit', 'IPI%', 'ICMS%', 'R$ Liq'];
            const colWidths = [18, 22, 45, 22, 12, 12, 15, 20, 12, 12, 25];
            const data = [];

            mpRows.forEach(item => {
                data.push([
                    item.col0?.substring(0, 10) || '',
                    item.col1 || '',
                    item.col2?.substring(0, 20) || '',
                    item.col3 || '',
                    item.col4 || '',
                    item.col5 || '',
                    item.col6 || '0',
                    item.col7 || '0',
                    item.col8 || '0',
                    item.col9 || '0',
                    item.col12 || '0'
                ]);
            });

            drawTable(headers, data, colWidths);
        }
        yPos += 5;

        // ==================== 3. INSUMOS ====================
        checkSpace(60);
        addSectionTitle('3. INSUMOS');

        const insRows = dados.tabelas?.insumos || [];
        if (insRows.length === 0) {
            doc.text('Nenhum insumo cadastrado.', margin, yPos);
            yPos += 5;
        } else {
            const headers = ['Código', 'Descrição', 'NCM', 'Un', 'Qtd', 'R$ Unit', 'IPI%', 'ICMS%', 'R$ Liq'];
            const colWidths = [20, 50, 22, 12, 15, 22, 12, 12, 25];
            const data = [];

            insRows.forEach(item => {
                data.push([
                    item.col0 || '',
                    item.col1?.substring(0, 20) || '',
                    item.col2 || '',
                    item.col3 || '',
                    item.col4 || '0',
                    item.col5 || '0',
                    item.col6 || '0',
                    item.col7 || '0',
                    item.col10 || '0'
                ]);
            });

            drawTable(headers, data, colWidths);
        }
        yPos += 5;

        // ==================== 4. COMPONENTES ====================
        checkSpace(60);
        addSectionTitle('4. COMPONENTES');

        const compRows = dados.tabelas?.componentes || [];
        if (compRows.length === 0) {
            doc.text('Nenhum componente cadastrado.', margin, yPos);
            yPos += 5;
        } else {
            const headers = ['Código', 'Descrição', 'NCM', 'Un', 'Qtd', 'R$ Unit', 'IPI%', 'ICMS%', 'R$ Liq'];
            const colWidths = [20, 50, 22, 12, 15, 22, 12, 12, 25];
            const data = [];

            compRows.forEach(item => {
                data.push([
                    item.col0 || '',
                    item.col1?.substring(0, 20) || '',
                    item.col2 || '',
                    item.col3 || '',
                    item.col4 || '0',
                    item.col5 || '0',
                    item.col6 || '0',
                    item.col7 || '0',
                    item.col10 || '0'
                ]);
            });

            drawTable(headers, data, colWidths);
        }
        yPos += 5;

        // ==================== 5. FLUXO DE PRODUÇÃO ====================
        checkSpace(70);
        addSectionTitle('5. FLUXO DE PRODUÇÃO');

        const procRows = dados.tabelas?.processo || [];
        if (procRows.length === 0) {
            doc.text('Nenhum processo cadastrado.', margin, yPos);
            yPos += 5;
        } else {
            const headers = ['Processo', 'Recurso', 'KW', 'R$/kWh', 'Água', 'R$/m³', 'Rend%', 'Qtd/h', 'Setup', 'R$/peça'];
            const colWidths = [25, 25, 15, 18, 15, 18, 15, 18, 15, 22];
            const data = [];

            procRows.forEach(item => {
                data.push([
                    item.col0?.substring(0, 12) || '',
                    item.col1?.substring(0, 12) || '',
                    item.col2 || '0',
                    item.col3 || '0',
                    item.col4 || '0',
                    item.col5 || '0',
                    item.col6 || '0',
                    item.col7 || '0',
                    item.col8 || '0',
                    item.col12 || '0'
                ]);
            });

            drawTable(headers, data, colWidths);
        }
        yPos += 5;

        // ==================== 6. NORMAS ====================
        checkSpace(40);
        addSectionTitle('6. NORMAS APLICÁVEIS');

        const normasRows = dados.tabelas?.normas || [];
        if (normasRows.length === 0) {
            doc.text('Nenhuma norma cadastrada.', margin, yPos);
            yPos += 5;
        } else {
            normasRows.forEach(item => {
                doc.text(`• ${item.col0 || ''} - ${item.col1 || ''} (${item.col2 || ''})`, margin, yPos);
                yPos += 5;
            });
        }
        yPos += 5;

        // ==================== 7. CLASSIFICAÇÃO FISCAL ====================
        checkSpace(40);
        addSectionTitle('7. CLASSIFICAÇÃO FISCAL - NCM / ALÍQUOTAS');

        const classRows = dados.tabelas?.classificacaoFiscal || [];
        if (classRows.length === 0) {
            doc.text('Nenhuma classificação fiscal cadastrada.', margin, yPos);
            yPos += 5;
        } else {
            const headers = ['NCM', 'Descrição', 'IPI%', 'ICMS%', 'PIS%', 'COFINS%'];
            const colWidths = [35, 80, 20, 20, 20, 20];
            const data = [];

            classRows.forEach(item => {
                data.push([
                    item.col0 || '',
                    item.col1?.substring(0, 25) || '',
                    item.col2 || '0',
                    item.col3 || '0',
                    item.col4 || '0',
                    item.col5 || '0'
                ]);
            });

            drawTable(headers, data, colWidths);
        }
        yPos += 5;

        // ==================== 8. RESUMO FINANCEIRO ====================
        checkSpace(120);
        addSectionTitle('8. RESUMO FINANCEIRO EXECUTIVO');

        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(10, 61, 46);
        doc.text('CUSTOS DIRETOS (R$/peça)', margin, yPos);
        yPos += 6;

        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(60, 60, 60);

        const resumoMateriaPrima = document.getElementById('resumoMateriaPrima')?.textContent || 'R$ 0,00';
        const resumoInsumos = document.getElementById('resumoInsumos')?.textContent || 'R$ 0,00';
        const resumoComponentes = document.getElementById('resumoComponentes')?.textContent || 'R$ 0,00';
        const resumoEmbalagem = document.getElementById('resumoEmbalagem')?.textContent || 'R$ 0,00';
        const resumoFerramentalPorPeca = document.getElementById('resumoFerramentalPorPeca')?.textContent || 'R$ 0,00';
        const resumoTotalMateriaisDiretos = document.getElementById('resumoTotalMateriaisDiretos')?.textContent || 'R$ 0,00';
        const resumoMaoObraDireta = document.getElementById('resumoMaoObraDireta')?.textContent || 'R$ 0,00';
        const resumoMaoObraIndireta = document.getElementById('resumoMaoObraIndireta')?.textContent || 'R$ 0,00';
        const resumoProcesso = document.getElementById('resumoProcesso')?.textContent || 'R$ 0,00';
        const resumoCustosIndiretosFixos = document.getElementById('resumoCustosIndiretosFixos')?.textContent || 'R$ 0,00';
        const resumoCustosIndiretosVariaveis = document.getElementById('resumoCustosIndiretosVariaveis')?.textContent || 'R$ 0,00';
        const resumoCustoUnitario = document.getElementById('resumoCustoUnitario')?.textContent || 'R$ 0,00';

        addInfoLine('Matéria Prima:', resumoMateriaPrima, margin, 65);
        addInfoLine('Insumos:', resumoInsumos, margin, 65);
        addInfoLine('Componentes:', resumoComponentes, margin, 65);
        addInfoLine('Embalagem:', resumoEmbalagem, margin, 65);
        addInfoLine('Ferramental:', resumoFerramentalPorPeca, margin, 65);
        doc.setFont('helvetica', 'bold');
        addInfoLine('Total Materiais:', resumoTotalMateriaisDiretos, margin, 65);
        doc.setFont('helvetica', 'normal');

        const yPosRight = yPos - 35;
        doc.text('Mão de Obra Direta:', margin + 120, yPosRight);
        doc.text(resumoMaoObraDireta, margin + 190, yPosRight);
        doc.text('Mão de Obra Indireta:', margin + 120, yPosRight + 5);
        doc.text(resumoMaoObraIndireta, margin + 190, yPosRight + 5);
        doc.text('Processo:', margin + 120, yPosRight + 10);
        doc.text(resumoProcesso, margin + 190, yPosRight + 10);
        doc.text('Custos Fixos:', margin + 120, yPosRight + 15);
        doc.text(resumoCustosIndiretosFixos, margin + 190, yPosRight + 15);
        doc.text('Custos Variáveis:', margin + 120, yPosRight + 20);
        doc.text(resumoCustosIndiretosVariaveis, margin + 190, yPosRight + 20);
        doc.setFont('helvetica', 'bold');
        doc.text('CUSTO UNITÁRIO:', margin + 120, yPosRight + 25);
        doc.text(resumoCustoUnitario, margin + 190, yPosRight + 25);
        doc.setFont('helvetica', 'normal');

        yPos = yPosRight + 30;
        yPos += 5;

        checkSpace(40);
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(10, 61, 46);
        doc.text('INVESTIMENTOS E MÉTRICAS', margin, yPos);
        yPos += 6;

        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');

        const resumoFerramental = document.getElementById('resumoFerramental')?.textContent || 'R$ 0,00';
        const resumoMateriaisFerramental = document.getElementById('resumoMateriaisFerramental')?.textContent || 'R$ 0,00';
        const resumoTotalInvestimento = document.getElementById('resumoTotalInvestimento')?.textContent || 'R$ 0,00';
        const resumoVolumeMensal = document.getElementById('resumoVolumeMensal')?.textContent || '0 peças';
        const resumoFaturamentoMensal = document.getElementById('resumoFaturamentoMensal')?.textContent || 'R$ 0,00';
        const resumoVidaUtilMinima = document.getElementById('resumoVidaUtilMinima')?.textContent || '0 peças';

        addInfoLine('Investimento Ferramental:', resumoFerramental, margin, 75);
        addInfoLine('Materiais Ferramental:', resumoMateriaisFerramental, margin, 75);
        doc.setFont('helvetica', 'bold');
        addInfoLine('INVESTIMENTO TOTAL:', resumoTotalInvestimento, margin, 75);
        doc.setFont('helvetica', 'normal');
        addInfoLine('Volume Mensal:', resumoVolumeMensal, margin, 75);
        addInfoLine('Faturamento Mensal:', resumoFaturamentoMensal, margin, 75);
        addInfoLine('Vida Útil Mínima:', resumoVidaUtilMinima, margin, 75);
        yPos += 5;

        checkSpace(25);
        doc.setFillColor(255, 248, 225);
        doc.rect(margin, yPos - 3, pageWidth - margin * 2, 20, 'F');

        doc.setFontSize(10);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(10, 61, 46);
        doc.text('PONTO DE EQUILÍBRIO', margin + 2, yPos);
        yPos += 5;

        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(60, 60, 60);

        const resumoPontoEquilibrioQtd = document.getElementById('resumoPontoEquilibrioQtd')?.textContent || '0';
        const resumoPontoEquilibrioValor = document.getElementById('resumoPontoEquilibrioValor')?.textContent || 'R$ 0,00';
        const resumoMargemSegurancaPercent = document.getElementById('resumoMargemSegurancaPercent')?.textContent || '0%';

        doc.text('Quantidade:', margin + 5, yPos);
        doc.text(resumoPontoEquilibrioQtd, margin + 45, yPos);
        doc.text('Valor:', margin + 100, yPos);
        doc.text(resumoPontoEquilibrioValor, margin + 135, yPos);
        yPos += 5;
        doc.text('Margem de Segurança:', margin + 5, yPos);
        doc.text(resumoMargemSegurancaPercent, margin + 70, yPos);
        yPos += 7;

        checkSpace(40);
        doc.setFillColor(230, 243, 230);
        doc.rect(margin, yPos - 3, pageWidth - margin * 2, 25, 'F');

        doc.setFontSize(10);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(10, 61, 46);
        doc.text('MARKUP & IMPOSTOS', margin + 2, yPos);
        yPos += 5;

        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(60, 60, 60);

        const resumoMarkup = document.getElementById('resumoMarkup')?.textContent || '1,97';
        const resumoIPI = document.getElementById('resumoIPI')?.textContent || '0%';
        const resumoICMS = document.getElementById('resumoICMS')?.textContent || '0%';
        const resumoPIS = document.getElementById('resumoPIS')?.textContent || '0%';
        const resumoCOFINS = document.getElementById('resumoCOFINS')?.textContent || '0%';
        const resumoIRPJCSLLFaturamento = document.getElementById('resumoIRPJCSLLFaturamento')?.textContent || '0%';
        const resumoTotalImpostos = document.getElementById('resumoTotalImpostos')?.textContent || '0%';
        const resumoMargemLucroMarkup = document.getElementById('resumoMargemLucroMarkup')?.textContent || '0%';

        doc.text('Markup (fator):', margin + 5, yPos);
        doc.text(resumoMarkup, margin + 55, yPos);
        doc.text('IPI:', margin + 110, yPos);
        doc.text(resumoIPI, margin + 135, yPos);
        yPos += 5;

        doc.text('ICMS:', margin + 5, yPos);
        doc.text(resumoICMS, margin + 55, yPos);
        doc.text('PIS:', margin + 110, yPos);
        doc.text(resumoPIS, margin + 135, yPos);
        yPos += 5;

        doc.text('COFINS:', margin + 5, yPos);
        doc.text(resumoCOFINS, margin + 55, yPos);
        doc.text('IRPJ/CSLL:', margin + 110, yPos);
        doc.text(resumoIRPJCSLLFaturamento, margin + 160, yPos);
        yPos += 5;

        doc.text('Carga Tributária:', margin + 5, yPos);
        doc.text(resumoTotalImpostos, margin + 65, yPos);
        doc.text('Margem Lucro:', margin + 110, yPos);
        doc.text(resumoMargemLucroMarkup, margin + 165, yPos);
        yPos += 7;

        checkSpace(30);
        doc.setFillColor(212, 160, 23);
        doc.rect(margin, yPos - 3, 5, 8, 'F');
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(10, 61, 46);
        doc.text('PREÇO DE VENDA SUGERIDO', margin + 10, yPos);
        yPos += 8;

        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(60, 60, 60);

        const custoUnitarioBase = document.getElementById('custoUnitarioBase')?.textContent || 'R$ 0,00';
        const markupAtual = document.getElementById('markupAtual')?.textContent || '1,97';
        const precoBaseAtual = document.getElementById('precoBaseAtual')?.textContent || 'R$ 0,00';
        const ipiAtual = document.getElementById('ipiAtual')?.textContent || '0%';
        const precoSugeridoDestaque = document.getElementById('precoSugeridoDestaque')?.textContent || 'R$ 0,00';
        const lucroPorUnidade = document.getElementById('lucroPorUnidade')?.textContent || 'R$ 0,00';
        const lucroLiquidoUnidade = document.getElementById('lucroLiquidoUnidade')?.textContent || 'R$ 0,00';
        const roiCalculado = document.getElementById('roiCalculado')?.textContent || '0%';
        const paybackCalculado = document.getElementById('paybackCalculado')?.textContent || '0 meses';

        addInfoLine('Custo Unitário:', custoUnitarioBase, margin, 55);
        addInfoLine('Markup:', markupAtual, margin, 55);
        addInfoLine('Preço sem IPI:', precoBaseAtual, margin, 55);
        addInfoLine('IPI:', ipiAtual, margin, 55);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.text('Preço Final:', margin, yPos);
        doc.text(precoSugeridoDestaque, margin + 55, yPos);
        doc.text('por peça', margin + 110, yPos);
        yPos += 6;

        doc.setFontSize(9);
        doc.text('Lucro Operacional:', margin, yPos);
        doc.text(lucroPorUnidade, margin + 65, yPos);
        doc.text('Lucro Líquido:', margin + 120, yPos);
        doc.text(lucroLiquidoUnidade, margin + 170, yPos);
        yPos += 5;

        doc.text('ROI Anual:', margin, yPos);
        doc.text(roiCalculado, margin + 50, yPos);
        doc.text('Payback:', margin + 100, yPos);
        doc.text(paybackCalculado, margin + 145, yPos);
        yPos += 10;

        addFooter();

        const nomeArquivo = `Relatorio_${dados.informacoesBasicas?.anviNumber || 'ANVI'}_Rev_${dados.informacoesBasicas?.revisaoANVI || '00'}.pdf`;
        doc.save(nomeArquivo);

        document.body.removeChild(loadingToast);
        mostrarNotificacao('PDF gerado com sucesso!', 'success');

    } catch (error) {
        console.error('Erro ao gerar PDF:', error);
        alert('Erro ao gerar PDF: ' + error.message);
        const loadingToast = document.querySelector('.pdf-preview-card');
        if (loadingToast) document.body.removeChild(loadingToast);
    }
}

function gerarPDFANVI(anviId) {
    fetch(`api/anvi.php?id=${anviId}`, { credentials: 'include' })
        .then(response => response.json())
        .then(anvi => {
            gerarPDFCompleto(anvi);
        })
        .catch(e => {
            console.error('Erro ao carregar ANVI para PDF:', e);
            alert('Erro ao carregar ANVI');
        });
}

