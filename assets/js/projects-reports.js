// FUNÇÕES DE LOGO (manter as originais)
// ==============================================
function initLogo() {
    const logoImage = document.getElementById('logoImage');
    if (companyLogo) {
        logoImage.src = companyLogo;
        logoImage.style.display = 'block';
        logoImage.style.height = logoSize + 'px';
        
        const logoIcon = document.querySelector('.logo i');
        if (logoIcon) {
            logoIcon.style.fontSize = '1.8rem';
        }
    } else {
        logoImage.style.display = 'none';
    }
    
    const logoSizeControl = document.getElementById('logoSize');
    if (logoSizeControl) {
        logoSizeControl.value = logoSize;
        document.getElementById('logoSizeValue').textContent = logoSize + 'px';
        
        logoSizeControl.addEventListener('input', function() {
            document.getElementById('logoSizeValue').textContent = this.value + 'px';
            const preview = document.getElementById('logoPreview');
            if (preview && preview.src) {
                preview.style.height = this.value + 'px';
            }
        });
    }
}

function showLogoModal() {
    closeAllModals();
    document.getElementById('logoModal').style.display = 'block';
    
    const preview = document.getElementById('logoPreview');
    if (companyLogo) {
        preview.src = companyLogo;
        preview.style.height = logoSize + 'px';
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
    
    document.getElementById('logoFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('logoPreview');
                preview.src = event.target.result;
                preview.style.display = 'block';
                preview.style.height = document.getElementById('logoSize').value + 'px';
            };
            reader.readAsDataURL(file);
        }
    });
}

function saveLogo() {
    const fileInput = document.getElementById('logoFile');
    const preview = document.getElementById('logoPreview');
    const sizeControl = document.getElementById('logoSize');
    
    if (!fileInput.files[0] && !preview.src) {
        if (companyLogo) {
            logoSize = parseInt(sizeControl.value);
            localStorage.setItem('logoSize', logoSize);
            
            const logoImage = document.getElementById('logoImage');
            if (logoImage) {
                logoImage.style.height = logoSize + 'px';
            }
            
            alert('Tamanho do logotipo atualizado!');
            closeAllModals();
        } else {
            alert('Selecione um arquivo de imagem primeiro.');
        }
        return;
    }
    
    if (fileInput.files[0]) {
        const file = fileInput.files[0];
        const reader = new FileReader();
        
        reader.onload = function(event) {
            companyLogo = event.target.result;
            logoSize = parseInt(sizeControl.value);
            
            localStorage.setItem('companyLogo', companyLogo);
            localStorage.setItem('logoSize', logoSize);
            
            const logoImage = document.getElementById('logoImage');
            if (logoImage) {
                logoImage.src = companyLogo;
                logoImage.style.display = 'block';
                logoImage.style.height = logoSize + 'px';
            }
            
            const logoIcon = document.querySelector('.logo i');
            if (logoIcon) {
                logoIcon.style.fontSize = '1.8rem';
            }
            
            alert('Logotipo salvo com sucesso!');
            closeAllModals();
        };
        
        reader.readAsDataURL(file);
    } else if (preview.src) {
        companyLogo = preview.src;
        logoSize = parseInt(sizeControl.value);
        
        localStorage.setItem('companyLogo', companyLogo);
        localStorage.setItem('logoSize', logoSize);
        
        const logoImage = document.getElementById('logoImage');
        if (logoImage) {
            logoImage.src = companyLogo;
            logoImage.style.display = 'block';
            logoImage.style.height = logoSize + 'px';
        }
        
        alert('Logotipo atualizado!');
        closeAllModals();
    }
}

function removeLogo() {
    if (confirm('Tem certeza que deseja remover o logotipo?')) {
        companyLogo = null;
        localStorage.removeItem('companyLogo');
        localStorage.removeItem('logoSize');
        
        const logoImage = document.getElementById('logoImage');
        if (logoImage) {
            logoImage.style.display = 'none';
            logoImage.src = '';
        }
        
        const logoIcon = document.querySelector('.logo i');
        if (logoIcon) {
            logoIcon.style.fontSize = '2.2rem';
        }
        
        const preview = document.getElementById('logoPreview');
        if (preview) {
            preview.src = '';
            preview.style.display = 'none';
        }
        
        alert('Logotipo removido!');
        closeAllModals();
    }
}

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

// ==============================================
// FUNÇÕES DE PDF COMPLETAS (manter as originais, mas adaptar para MySQL)
// ==============================================
function showPdfOptions() {
    closeAllModals();
    document.getElementById('pdfOptionsModal').style.display = 'block';
}

function getPageSizeConfig() {
    const selectedSize = document.querySelector('input[name="pageSize"]:checked')?.value || 'a4-portrait';
    
    switch(selectedSize) {
        case 'a4-portrait':
            return { orientation: 'p', unit: 'mm', format: 'a4', width: 210, height: 297 };
        case 'a4-landscape':
            return { orientation: 'l', unit: 'mm', format: 'a4', width: 297, height: 210 };
        case 'a3-portrait':
            return { orientation: 'p', unit: 'mm', format: 'a3', width: 297, height: 420 };
        case 'a3-landscape':
            return { orientation: 'l', unit: 'mm', format: 'a3', width: 420, height: 297 };
        default:
            return { orientation: 'p', unit: 'mm', format: 'a4', width: 210, height: 297 };
    }
}

async function captureElementAsImage(element, options = {}) {
    if (!element) return null;
    
    // Clonar o elemento para não afetar o original
    const clone = element.cloneNode(true);
    clone.style.width = options.width || '1800px';
    clone.style.position = 'absolute';
    clone.style.left = '0';
    clone.style.top = '0';
    clone.style.visibility = 'visible';
    clone.style.background = 'white';
    
    const container = document.getElementById('ganttCaptureContainer');
    container.innerHTML = '';
    container.appendChild(clone);
    container.style.display = 'block';
    
    // Esperar um pouco para o CSS ser aplicado
    await new Promise(resolve => setTimeout(resolve, 200));
    
    try {
        const canvas = await html2canvas(clone, {
            scale: 2,
            backgroundColor: '#ffffff',
            allowTaint: false,
            useCORS: true,
            logging: false,
            windowWidth: parseInt(options.width) || 1800
        });
        
        return canvas.toDataURL('image/png');
    } catch (error) {
        console.error('Erro ao capturar elemento:', error);
        return null;
    } finally {
        container.style.display = 'none';
        container.innerHTML = '';
    }
}

async function captureCapabilityCharts(characteristicId) {
    const chartElementId = `${characteristicId}_chart`;
    const chartElement = document.getElementById(chartElementId);
    
    if (!chartElement) {
        console.warn(`Elemento do gráfico não encontrado para ID: ${chartElementId}`);
        return null;
    }
    
    const canvas = chartElement.querySelector('canvas');
    if (!canvas) {
        console.warn(`Canvas não encontrado dentro do elemento: ${chartElementId}`);
        return null;
    }
    
    try {
        const canvasClone = canvas.cloneNode(true);
        canvasClone.width = canvas.width;
        canvasClone.height = canvas.height;
        
        const ctx = canvasClone.getContext('2d');
        ctx.drawImage(canvas, 0, 0);
        
        return canvasClone.toDataURL('image/png');
    } catch (error) {
        console.error('Erro ao capturar canvas:', error);
        return await captureElementAsImage(canvas, { width: 1200 });
    }
}

async function generateCompletePDF() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado.');
        return;
    }
    
    const includeApqp = document.getElementById('includeApqp')?.checked ?? true;
    const includeGantt = document.getElementById('includeGantt')?.checked ?? true;
    const includeCapability = document.getElementById('includeCapability')?.checked ?? true;
    const includeTimeline = document.getElementById('includeTimeline')?.checked ?? true;
    
    const pageConfig = getPageSizeConfig();
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF(pageConfig.orientation, pageConfig.unit, pageConfig.format);
    
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    const contentWidth = pageWidth - 2 * margin;
    let yPos = margin;
    
    function checkPageHeight(neededHeight) {
        if (yPos + neededHeight > pageHeight - margin) {
            doc.addPage(pageConfig.orientation, pageConfig.format);
            yPos = margin;
            return true;
        }
        return false;
    }
    
    // Logotipo
    if (companyLogo) {
        try {
            const img = await loadImage(companyLogo);
            
            const maxWidth = 50;
            const maxHeight = 20;
            
            let logoWidth = img.width;
            let logoHeight = img.height;
            
            const ratio = logoWidth / logoHeight;
            
            if (logoWidth > maxWidth) {
                logoWidth = maxWidth;
                logoHeight = logoWidth / ratio;
            }
            
            if (logoHeight > maxHeight) {
                logoHeight = maxHeight;
                logoWidth = logoHeight * ratio;
            }
            
            doc.addImage(companyLogo, 'PNG', margin, yPos, logoWidth, logoHeight);
            yPos += logoHeight + 8;
        } catch (e) {
            console.warn('Erro ao adicionar logotipo:', e);
            yPos += 8;
        }
    } else {
        yPos += 8;
    }
    
    // Título
    doc.setFontSize(22);
    doc.setTextColor(46, 125, 50);
    doc.text("RELATÓRIO COMPLETO DO PROJETO", pageWidth / 2, yPos, { align: 'center' });
    yPos += 15;
    
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text(`Projeto: ${project.projectName} (ID: ${project.id})`, margin, yPos);
    yPos += 8;
    doc.setFontSize(10);
    doc.text(`Cliente: ${project.cliente || '-'} | Líder: ${project.projectLeader || '-'} | Status: ${project.status}`, margin, yPos);
    yPos += 8;
    doc.text(`Código: ${project.codigo || '-'} | ANVI: ${project.anviNumber || '-'} | Modelo: ${project.modelo || '-'} | Processo: ${project.processo || '-'} | Fase: ${project.fase || '-'}`, margin, yPos);
    yPos += 12;
    
    doc.setDrawColor(46, 125, 50);
    doc.setLineWidth(0.5);
    doc.line(margin, yPos, pageWidth - margin, yPos);
    yPos += 10;
    
    // Progresso
    checkPageHeight(50);
    const progressData = calculateWeightedProjectProgress(project);
    
    doc.setFillColor(240, 248, 240);
    doc.roundedRect(margin, yPos, contentWidth, 40, 3, 3, 'F');
    
    doc.setFontSize(12);
    doc.setTextColor(33, 33, 33);
    doc.setFont(undefined, 'bold');
    doc.text("PROGRESSO DO PROJETO", margin + 10, yPos + 8);
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text(`Progresso Ponderado: ${progressData.progress.toFixed(1)}% (Concluído: ${progressData.completedWeight} | Total: ${progressData.totalWeight})`, margin + 10, yPos + 16);
    
    // Barra de progresso
    doc.setFillColor(200, 200, 200);
    doc.roundedRect(margin + 10, yPos + 22, 120, 8, 2, 2, 'F');
    doc.setFillColor(76, 175, 80);
    doc.roundedRect(margin + 10, yPos + 22, (progressData.progress / 100) * 120, 8, 2, 2, 'F');
    
    yPos += 45;
    
    // CAPTURAR GRÁFICO DE GANTT COMO IMAGEM
    if (includeGantt) {
        checkPageHeight(20);
        
        doc.setFillColor(46, 125, 50);
        doc.setTextColor(255, 255, 255);
        doc.rect(margin, yPos, contentWidth, 10, 'F');
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text("GRÁFICO DE GANTT", margin + 5, yPos + 7);
        yPos += 18;
        
        // Capturar o Gantt como imagem
        const ganttElement = document.getElementById('ganttContainer');
        if (ganttElement) {
            // Ajustar largura para captura
            const ganttWidth = pageConfig.orientation === 'l' ? 2500 : 1800;
            const ganttImage = await captureElementAsImage(ganttElement, { width: ganttWidth });
            
            if (ganttImage) {
                // Calcular altura da imagem no PDF mantendo proporção
                const img = await loadImage(ganttImage);
                const imgWidth = contentWidth;
                const imgHeight = (img.height * imgWidth) / img.width;
                
                checkPageHeight(imgHeight + 10);
                
                doc.addImage(ganttImage, 'PNG', margin, yPos, imgWidth, imgHeight);
                yPos += imgHeight + 10;
            } else {
                doc.setFontSize(10);
                doc.setTextColor(100, 100, 100);
                doc.text("Não foi possível gerar a imagem do Gantt.", margin, yPos);
                yPos += 10;
            }
        }
    }
    
    // SEÇÃO DE CAPABILIDADE
    if (includeCapability && project.capability && project.capability.characteristics && project.capability.characteristics.length > 0) {
        checkPageHeight(20);
        
        doc.setFillColor(46, 125, 50);
        doc.setTextColor(255, 255, 255);
        doc.rect(margin, yPos, contentWidth, 10, 'F');
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text("ESTUDO DE CAPABILIDADE", margin + 5, yPos + 7);
        yPos += 15;
        
        doc.setFontSize(10);
        doc.setTextColor(0, 0, 0);
        doc.setFont(undefined, 'normal');
        doc.text(`Data do Estudo: ${project.capability.studyDate ? formatDateBR(project.capability.studyDate) : '-'}`, margin, yPos);
        yPos += 8;
        
        // Para cada característica, mostrar resumo e capturar gráfico
        const characteristicsToShow = project.capability.characteristics;
        
        for (const [index, char] of characteristicsToShow.entries()) {
            checkPageHeight(80);
            
            doc.setFillColor(250, 250, 250);
            doc.rect(margin, yPos, contentWidth, 30, 'F');
            
            doc.setFontSize(11);
            doc.setFont(undefined, 'bold');
            doc.text(`${index + 1}. ${char.name || `Característica ${index + 1}`}`, margin + 5, yPos + 6);
            
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.text(`Tipo: ${char.type === 'cc' ? 'CC' : 'SC'} | LIE: ${char.lie?.toFixed(3) || '-'} | LSE: ${char.lse?.toFixed(3) || '-'} | Média: ${char.stats?.mean?.toFixed(3) || '-'} | Desvio: ${char.stats?.stdDev?.toFixed(3) || '-'}`, margin + 5, yPos + 13);
            doc.text(`Cp: ${char.stats?.cp?.toFixed(3) || '-'} | Cpk: ${char.stats?.cpk?.toFixed(3) || '-'} | Nível Sigma: ${char.stats?.sigmaLevel?.toFixed(2) || '-'}`, margin + 5, yPos + 20);
            
            yPos += 35;
            
            // Capturar gráfico da característica - Usando o ID salvo
            if (char.id) {
                const chartImage = await captureCapabilityCharts(char.id);
                
                if (chartImage) {
                    checkPageHeight(120);
                    
                    const img = await loadImage(chartImage);
                    const imgWidth = contentWidth;
                    const imgHeight = (img.height * imgWidth) / img.width;
                    
                    doc.addImage(chartImage, 'PNG', margin, yPos, imgWidth, imgHeight);
                    yPos += imgHeight + 10;
                } else {
                    // Se não encontrar o elemento do gráfico, mostrar apenas texto
                    doc.setFontSize(9);
                    doc.setTextColor(100, 100, 100);
                    doc.text("(Gráfico não disponível para esta característica)", margin, yPos);
                    yPos += 8;
                }
            } else {
                doc.setFontSize(9);
                doc.setTextColor(100, 100, 100);
                doc.text("(ID da característica não encontrado)", margin, yPos);
                yPos += 8;
            }
        }
        
        // Resumo geral
        checkPageHeight(50);
        
        const totalChars = project.capability.characteristics.length;
        const capableChars = project.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
        const avgCpk = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
        const avgSigma = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.sigmaLevel || 0), 0) / totalChars;
        const avgPpm = calculateDPMO(avgSigma).toFixed(0);
        
        doc.setFillColor(240, 248, 240);
        doc.roundedRect(margin, yPos, contentWidth, 35, 3, 3, 'F');
        
        doc.setFontSize(11);
        doc.setTextColor(33, 33, 33);
        doc.setFont(undefined, 'bold');
        doc.text("RESUMO GERAL DA CAPABILIDADE", margin + 10, yPos + 8);
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'normal');
        doc.text(`Total de Características: ${totalChars} | Capazes: ${capableChars} (${((capableChars/totalChars)*100).toFixed(1)}%) | Cpk Médio: ${avgCpk.toFixed(3)}`, margin + 10, yPos + 15);
        doc.text(`Nível Sigma Médio: ${avgSigma.toFixed(2)} (${avgPpm} ppm)`, margin + 10, yPos + 22);
        
        yPos += 40;
    }
    
    // LINHA DO TEMPO DAS TAREFAS (como texto)
    if (includeTimeline) {
        checkPageHeight(40 + 120);
        
        doc.setFillColor(46, 125, 50);
        doc.setTextColor(255, 255, 255);
        doc.rect(margin, yPos, contentWidth, 10, 'F');
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text("LINHA DO TEMPO DAS TAREFAS", margin + 5, yPos + 7);
        yPos += 18;
        
        const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
        const taskNames = {
            'kom': 'KOM', 'ferramental': 'Ferramental', 'cadBomFt': 'CAD+BOM+FT', 
            'tryout': 'Try-out', 'entrega': 'Entrega', 'psw': 'PSW', 'handover': 'Handover'
        };
        
        taskKeys.forEach(key => {
            checkPageHeight(25);
            const task = project.tasks?.[key];
            if (!task) return;
            
            doc.setFillColor(250, 250, 250);
            doc.rect(margin, yPos, contentWidth, 20, 'F');
            
            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0);
            doc.setFont(undefined, 'bold');
            doc.text(taskNames[key], margin + 5, yPos + 6);
            
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.text(`Planejado: ${formatDateBR(task.planned) || '-'} | Início: ${formatDateBR(task.start) || '-'} | Conclusão: ${formatDateBR(task.executed) || '-'} | Duração: ${task.duration || getDefaultDuration(key)} dias`, margin + 5, yPos + 12);
            
            const taskStatus = calculateTaskStatus(task, project.status);
            const statusColors = {
                'Concluído': '#4caf50',
                'Em Andamento': '#2196f3',
                'Atrasado': '#f44336',
                'No Prazo': '#4caf50',
                'Pendente': '#ff9800',
                'Cancelado': '#9e9e9e',
                'Em Espera': '#ff9800'
            };
            
            doc.setFillColor(statusColors[taskStatus] || '#999');
            doc.rect(pageWidth - margin - 30, yPos + 5, 25, 8, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(8);
            doc.text(taskStatus, pageWidth - margin - 20, yPos + 10);
            
            yPos += 25;
        });
        
        yPos += 5;
    }
    
    // ANÁLISE APQP
    if (includeApqp) {
        checkPageHeight(20);
        
        doc.setFillColor(46, 125, 50);
        doc.setTextColor(255, 255, 255);
        doc.rect(margin, yPos, contentWidth, 10, 'F');
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text("ANÁLISE APQP - FASES 1 A 5", margin + 5, yPos + 7);
        yPos += 18;
        
        const phaseKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
        const phaseNames = {
            'kom': 'FASE 1 - Planejamento (KOM)',
            'ferramental': 'FASE 2 - Desenvolvimento do Produto (Ferramental)',
            'cadBomFt': 'FASE 2/3 - Desenvolvimento (CAD+BOM+FT)',
            'tryout': 'FASE 3/4 - Desenvolvimento do Processo (Try-out)',
            'entrega': 'FASE 4 - Validação (Entrega)',
            'psw': 'FASE 4 - Validação (PSW)',
            'handover': 'FASE 4/5 - Validação e Retroalimentação (Handover)'
        };
        
        phaseKeys.forEach(phase => {
            const phaseData = project.apqp?.[phase];
            const questions = APQP_QUESTIONS[phase] || [];
            
            checkPageHeight(questions.length * 15 + 40);
            
            // Cabeçalho da fase
            doc.setFillColor(33, 33, 33);
            doc.rect(margin, yPos, contentWidth, 12, 'F');
            
            doc.setFontSize(11);
            doc.setTextColor(255, 255, 255);
            doc.setFont(undefined, 'bold');
            doc.text(phaseNames[phase], margin + 5, yPos + 5);
            
            // Resumo da fase
            const totalQ = questions.length;
            let answeredQ = 0;
            if (phaseData && phaseData.answers) {
                answeredQ = Object.keys(phaseData.answers).length;
            }
            const percentComplete = totalQ > 0 ? (answeredQ / totalQ) * 100 : 0;
            
            doc.setFontSize(8);
            doc.text(`${answeredQ}/${totalQ} respondidas (${percentComplete.toFixed(0)}%)`, pageWidth - margin - 40, yPos + 5);
            
            yPos += 15;
            
            if (!phaseData || !phaseData.answers || Object.keys(phaseData.answers).length === 0) {
                doc.setFontSize(9);
                doc.setTextColor(100, 100, 100);
                doc.setFont(undefined, 'italic');
                doc.text("Nenhuma resposta registrada para esta fase.", margin + 10, yPos);
                yPos += 8;
            } else {
                // Listar perguntas com respostas
                questions.forEach((q, idx) => {
                    const answer = phaseData.answers[q.id];
                    
                    checkPageHeight(12);
                    
                    doc.setFillColor(250, 250, 250);
                    doc.rect(margin, yPos, contentWidth, 10, 'F');
                    
                    doc.setFontSize(8);
                    doc.setTextColor(33, 33, 33);
                    doc.setFont(undefined, 'normal');
                    
                    // Quebrar a pergunta em várias linhas se necessário
                    const questionLines = doc.splitTextToSize(`${idx + 1}. ${q.question}`, contentWidth - 80);
                    doc.text(questionLines, margin + 5, yPos + 3);
                    
                    if (answer) {
                        let answerText = answer.answer === 'sim' ? 'Sim' : (answer.answer === 'nao' ? 'Não' : 'N/A');
                        let answerColor = answer.answer === 'sim' ? '#4caf50' : (answer.answer === 'nao' ? '#f44336' : '#ff9800');
                        
                        doc.setTextColor(255, 255, 255);
                        doc.setFillColor(answerColor);
                        doc.rect(pageWidth - margin - 25, yPos + 2, 20, 6, 'F');
                        doc.setFontSize(7);
                        doc.text(answerText, pageWidth - margin - 18, yPos + 6);
                        
                        if (answer.observations) {
                            doc.setFontSize(6);
                            doc.setTextColor(150, 150, 150);
                            doc.text(`Obs: ${answer.observations}`, margin + 5, yPos + 8);
                        }
                    } else {
                        doc.setTextColor(150, 150, 150);
                        doc.setFontSize(7);
                        doc.text("Não respondida", pageWidth - margin - 20, yPos + 6);
                    }
                    
                    yPos += questionLines.length * 4 + 10;
                });
            }
            
            yPos += 5;
        });
    }
    
    // Rodapé
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(`Gerado em: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}`, margin, pageHeight - 5);
        doc.text(`Página ${i} de ${totalPages}`, pageWidth - margin, pageHeight - 5, { align: 'right' });
    }
    
    const sizeName = pageConfig.orientation === 'p' ? 'retrato' : 'paisagem';
    const fileName = `projeto_${project.projectName.replace(/[^a-z0-9]/gi, '_')}_${project.id}_${sizeName}.pdf`;
    doc.save(fileName);
    
    document.getElementById('pdfOptionsModal').style.display = 'none';
}

// ==============================================
// FUNÇÕES DE HANDOVER (manter as originais, usando nova função de progresso)
// ==============================================
function generateHandoverReport() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado.');
        return;
    }
    
    closeAllModals();
    document.getElementById('handoverReportTitle').textContent = `Relatório Handover - ${project.projectName}`;
    
    const content = document.getElementById('handoverReportContent');
    
    const progressData = calculateWeightedProjectProgress(project);
    
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    const taskNames = {
        'kom': 'KOM',
        'ferramental': 'Ferramental',
        'cadBomFt': 'CAD+BOM+FT',
        'tryout': 'Try-out',
        'entrega': 'Entrega',
        'psw': 'PSW',
        'handover': 'Handover'
    };
    
    // Calcular métricas
    const totalTasks = taskKeys.length;
    const completedTasks = taskKeys.filter(key => {
        const task = project.tasks?.[key];
        return task && task.executed;
    }).length;
    
    const delayedTasks = taskKeys.filter(key => {
        const task = project.tasks?.[key];
        if (!task || task.executed) return false;
        const taskStatus = calculateTaskStatus(task, project.status);
        return taskStatus === 'Atrasado';
    }).length;
    
    const apqpCompleted = taskKeys.filter(key => {
        const status = getApqpStatus(project, key);
        return status.status === 'completed';
    }).length;
    
    let capabilitySummary = '';
    if (project.capability && project.capability.characteristics && project.capability.characteristics.length > 0) {
        const totalChars = project.capability.characteristics.length;
        const capableChars = project.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
        const avgCpk = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
        const avgSigma = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.sigmaLevel || 0), 0) / totalChars;
        const avgPpm = calculateDPMO(avgSigma).toFixed(0);
        capabilitySummary = `${capableChars}/${totalChars} características capazes | Cpk médio: ${avgCpk.toFixed(2)} | Nível Sigma: ${avgSigma.toFixed(2)} (${avgPpm} ppm)`;
    } else {
        capabilitySummary = 'Não realizado';
    }
    
    const handoverTask = project.tasks?.handover;
    const handoverStatus = handoverTask ? calculateTaskStatus(handoverTask, project.status) : 'Pendente';
    
    let handoverHTML = `
        <div class="handover-report-section">
            <h3><i class="fas fa-info-circle"></i> Informações Gerais do Projeto</h3>
            <div class="handover-report-grid">
                <div class="handover-report-item">
                    <div class="handover-report-label">Cliente</div>
                    <div class="handover-report-value">${project.cliente || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Projeto</div>
                    <div class="handover-report-value">${project.projectName || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Código</div>
                    <div class="handover-report-value">${project.codigo || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">ANVI</div>
                    <div class="handover-report-value">${project.anviNumber || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Líder</div>
                    <div class="handover-report-value">${project.projectLeader || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Segmento</div>
                    <div class="handover-report-value">${project.segmento || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Modelo</div>
                    <div class="handover-report-value">${project.modelo || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Processo</div>
                    <div class="handover-report-value">${project.processo || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Fase</div>
                    <div class="handover-report-value">${project.fase || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Status do Projeto</div>
                    <div class="handover-report-value"><span class="status status-${project.status.toLowerCase().replace(/\s/g, '-')}">${project.status}</span></div>
                </div>
            </div>
        </div>
        
        <div class="handover-report-section">
            <h3><i class="fas fa-chart-pie"></i> Métricas do Projeto</h3>
            <div class="handover-metrics-grid">
                <div class="handover-metric-card">
                    <div class="handover-metric-label">Progresso Ponderado</div>
                    <div class="handover-metric-value">${progressData.progress.toFixed(1)}%</div>
                    <div class="handover-metric-label">(${progressData.completedWeight}/${progressData.totalWeight})</div>
                </div>
                <div class="handover-metric-card">
                    <div class="handover-metric-label">Tarefas Concluídas</div>
                    <div class="handover-metric-value">${completedTasks}/${totalTasks}</div>
                </div>
                <div class="handover-metric-card">
                    <div class="handover-metric-label">Tarefas Atrasadas</div>
                    <div class="handover-metric-value">${delayedTasks}</div>
                </div>
                <div class="handover-metric-card">
                    <div class="handover-metric-label">APQP Completo</div>
                    <div class="handover-metric-value">${apqpCompleted}/${totalTasks}</div>
                </div>
                <div class="handover-metric-card">
                    <div class="handover-metric-label">Capabilidade</div>
                    <div class="handover-metric-value" style="font-size: 1.2rem;">${capabilitySummary}</div>
                </div>
            </div>
        </div>
        
        <div class="handover-report-section">
            <h3><i class="fas fa-tasks"></i> Status das Tarefas</h3>
            <div class="handover-task-status">
    `;
    
    taskKeys.forEach(key => {
        const task = project.tasks?.[key];
        if (!task) return;
        
        const taskStatus = calculateTaskStatus(task, project.status);
        const apqpStatus = getApqpStatus(project, key);
        
        let apqpText = 'APQP: ';
        if (apqpStatus.status === 'completed') apqpText += '✓ Completo';
        else if (apqpStatus.status === 'partial') apqpText += `⚠ Parcial (${apqpStatus.answered}/${apqpStatus.total})`;
        else apqpText += '✗ Não iniciado';
        
        handoverHTML += `
            <div class="handover-task-card">
                <div class="handover-task-header">
                    <span class="handover-task-name">${taskNames[key]}</span>
                    <span class="status status-${taskStatus.toLowerCase().replace(/\s/g, '-')}">${taskStatus}</span>
                </div>
                <div class="handover-task-dates">
                    ${task.planned ? `<div><strong>Planejado:</strong> ${formatDateBR(task.planned)}</div>` : ''}
                    ${task.start ? `<div><strong>Início:</strong> ${formatDateBR(task.start)}</div>` : ''}
                    ${task.executed ? `<div><strong>Conclusão:</strong> ${formatDateBR(task.executed)}</div>` : ''}
                    <div><strong>Duração:</strong> ${task.duration || getDefaultDuration(key)} dias</div>
                </div>
                <div class="handover-apqp-summary">
                    <h4>${apqpText}</h4>
                </div>
            </div>
        `;
    });
    
    handoverHTML += `
            </div>
        </div>
        
        <div class="handover-report-section">
            <h3><i class="fas fa-clipboard-list"></i> Observações para Transferência</h3>
            <div class="handover-observations">
                <textarea id="handoverObservations" placeholder="Adicione observações importantes para o handover do projeto...">${project.observacoes || ''}</textarea>
            </div>
        </div>
        
        <div class="handover-report-actions">
            <button class="btn btn-primary" onclick="printHandoverReport()">
                <i class="fas fa-print"></i> Imprimir Relatório
            </button>
            <button class="btn btn-success" onclick="generateHandoverReportPDF()">
                <i class="fas fa-file-pdf"></i> Gerar PDF
            </button>
        </div>
    `;
    
    content.innerHTML = handoverHTML;
    document.getElementById('handoverReportModal').style.display = 'block';
}

function printHandoverReport() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado.');
        return;
    }

    const printContent = document.getElementById('handoverReportContent').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Relatório Handover - ${project.projectName}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .handover-report-section { margin-bottom: 25px; }
                    .handover-report-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
                    .handover-report-item { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
                    .handover-report-label { font-weight: bold; color: #666; }
                    .handover-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
                    .handover-metric-card { border: 1px solid #ddd; padding: 15px; text-align: center; border-radius: 5px; }
                    .handover-metric-value { font-size: 24px; font-weight: bold; color: #2e7d32; }
                    .handover-task-status { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
                    .handover-task-card { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
                    .status { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 12px; }
                    .status-concluido { background: #4caf50; color: white; }
                    .status-em-andamento { background: #2196f3; color: white; }
                    .status-atrasado { background: #f44336; color: white; }
                    .status-no-prazo { background: #4caf50; color: white; }
                    .status-pendente { background: #ff9800; color: white; }
                    button, .handover-report-actions, .modal .close { display: none !important; }
                </style>
            </head>
            <body>
                <h1>Relatório Handover - ${project.projectName}</h1>
                <p>Gerado em: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}</p>
                ${printContent}
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

async function generateHandoverReportPDF() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado.');
        return;
    }
    
    const observations = document.getElementById('handoverObservations')?.value || '';
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    let yPos = margin;
    
    function checkPageHeight(neededHeight) {
        if (yPos + neededHeight > pageHeight - margin) {
            doc.addPage();
            yPos = margin;
            return true;
        }
        return false;
    }
    
    // Logotipo
    if (companyLogo) {
        try {
            const img = await loadImage(companyLogo);
            
            const maxWidth = 40;
            const maxHeight = 15;
            
            let logoWidth = img.width;
            let logoHeight = img.height;
            
            const ratio = logoWidth / logoHeight;
            
            if (logoWidth > maxWidth) {
                logoWidth = maxWidth;
                logoHeight = logoWidth / ratio;
            }
            
            if (logoHeight > maxHeight) {
                logoHeight = maxHeight;
                logoWidth = logoHeight * ratio;
            }
            
            doc.addImage(companyLogo, 'PNG', margin, yPos, logoWidth, logoHeight);
            yPos += logoHeight + 5;
        } catch (e) {
            console.warn('Erro ao adicionar logotipo:', e);
            yPos += 5;
        }
    } else {
        yPos += 5;
    }
    
    // Título
    doc.setFontSize(20);
    doc.setTextColor(46, 125, 50);
    doc.text("RELATÓRIO HANDOVER", pageWidth / 2, yPos, { align: 'center' });
    yPos += 15;
    
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text(`Projeto: ${project.projectName} (ID: ${project.id})`, margin, yPos);
    yPos += 8;
    doc.setFontSize(10);
    doc.text(`Cliente: ${project.cliente || '-'} | Líder: ${project.projectLeader || '-'} | Data: ${new Date().toLocaleDateString('pt-BR')}`, margin, yPos);
    yPos += 10;
    
    doc.setDrawColor(46, 125, 50);
    doc.setLineWidth(0.5);
    doc.line(margin, yPos, pageWidth - margin, yPos);
    yPos += 10;
    
    // Informações Gerais
    checkPageHeight(50);
    doc.setFillColor(240, 248, 240);
    doc.roundedRect(margin, yPos, pageWidth - 2 * margin, 40, 3, 3, 'F');
    
    doc.setFontSize(12);
    doc.setTextColor(33, 33, 33);
    doc.setFont(undefined, 'bold');
    doc.text("INFORMAÇÕES GERAIS", margin + 10, yPos + 8);
    
    doc.setFontSize(9);
    doc.setFont(undefined, 'normal');
    doc.text(`Código: ${project.codigo || '-'} | ANVI: ${project.anviNumber || '-'} | Modelo: ${project.modelo || '-'} | Processo: ${project.processo || '-'}`, margin + 10, yPos + 15);
    doc.text(`Segmento: ${project.segmento || '-'} | Fase: ${project.fase || '-'} | Status: ${project.status}`, margin + 10, yPos + 22);
    doc.text(`Observações: ${project.observacoes || 'Nenhuma observação'}`, margin + 10, yPos + 29);
    
    yPos += 45;
    
    // Métricas
    checkPageHeight(60);
    const progressData = calculateWeightedProjectProgress(project);
    
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    const totalTasks = taskKeys.length;
    const completedTasks = taskKeys.filter(key => {
        const task = project.tasks?.[key];
        return task && task.executed;
    }).length;
    
    const delayedTasks = taskKeys.filter(key => {
        const task = project.tasks?.[key];
        if (!task || task.executed) return false;
        const taskStatus = calculateTaskStatus(task, project.status);
        return taskStatus === 'Atrasado';
    }).length;
    
    const apqpCompleted = taskKeys.filter(key => {
        const status = getApqpStatus(project, key);
        return status.status === 'completed';
    }).length;
    
    let capabilityText = 'Não realizado';
    if (project.capability && project.capability.characteristics && project.capability.characteristics.length > 0) {
        const totalChars = project.capability.characteristics.length;
        const capableChars = project.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
        const avgCpk = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
        const avgSigma = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.sigmaLevel || 0), 0) / totalChars;
        const avgPpm = calculateDPMO(avgSigma).toFixed(0);
        capabilityText = `${capableChars}/${totalChars} capazes | Cpk médio: ${avgCpk.toFixed(2)} | Sigma: ${avgSigma.toFixed(2)} (${avgPpm} ppm)`;
    }
    
    doc.setFillColor(46, 125, 50);
    doc.setTextColor(255, 255, 255);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 10, 'F');
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text("MÉTRICAS DO PROJETO", margin + 5, yPos + 7);
    yPos += 15;
    
    const metrics = [
        { label: 'Progresso Ponderado', value: `${progressData.progress.toFixed(1)}%` },
        { label: 'Tarefas Concluídas', value: `${completedTasks}/${totalTasks}` },
        { label: 'Tarefas Atrasadas', value: delayedTasks },
        { label: 'APQP Completo', value: `${apqpCompleted}/${totalTasks}` },
        { label: 'Capabilidade', value: capabilityText }
    ];
    
    let xPos = margin;
    const colWidth = (pageWidth - 2 * margin) / 3;
    
    metrics.forEach((metric, index) => {
        if (index % 3 === 0 && index > 0) {
            yPos += 25;
            xPos = margin;
        }
        
        doc.setFillColor(250, 250, 250);
        doc.rect(xPos, yPos, colWidth - 5, 20, 'F');
        doc.setDrawColor(200, 200, 200);
        doc.rect(xPos, yPos, colWidth - 5, 20);
        
        doc.setFontSize(8);
        doc.setTextColor(100, 100, 100);
        doc.setFont(undefined, 'normal');
        doc.text(metric.label, xPos + 5, yPos + 5);
        
        doc.setFontSize(12);
        doc.setTextColor(46, 125, 50);
        doc.setFont(undefined, 'bold');
        doc.text(metric.value, xPos + 5, yPos + 15);
        
        xPos += colWidth;
    });
    
    yPos += 30;
    
    // Tarefas
    checkPageHeight(40 + taskKeys.length * 15);
    doc.setFillColor(46, 125, 50);
    doc.setTextColor(255, 255, 255);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 10, 'F');
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text("STATUS DAS TAREFAS", margin + 5, yPos + 7);
    yPos += 15;
    
    const taskNames = {
        'kom': 'KOM', 'ferramental': 'Ferramental', 'cadBomFt': 'CAD+BOM+FT', 
        'tryout': 'Try-out', 'entrega': 'Entrega', 'psw': 'PSW', 'handover': 'Handover'
    };
    
    taskKeys.forEach(key => {
        const task = project.tasks?.[key];
        if (!task) return;
        
        checkPageHeight(15);
        
        const taskStatus = calculateTaskStatus(task, project.status);
        const apqpStatus = getApqpStatus(project, key);
        
        doc.setFillColor(250, 250, 250);
        doc.rect(margin, yPos, pageWidth - 2 * margin, 12, 'F');
        
        doc.setFontSize(9);
        doc.setTextColor(0, 0, 0);
        doc.setFont(undefined, 'bold');
        doc.text(taskNames[key], margin + 5, yPos + 4);
        
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text(`Planejado: ${formatDateBR(task.planned) || '-'} | Conclusão: ${formatDateBR(task.executed) || '-'} | APQP: ${apqpStatus.answered}/${apqpStatus.total}`, margin + 35, yPos + 4);
        
        const statusColors = {
            'Concluído': '#4caf50',
            'Em Andamento': '#2196f3',
            'Atrasado': '#f44336',
            'No Prazo': '#4caf50',
            'Pendente': '#ff9800',
            'Cancelado': '#9e9e9e',
            'Em Espera': '#ff9800'
        };
        
        doc.setFillColor(statusColors[taskStatus] || '#999');
        doc.rect(pageWidth - margin - 25, yPos + 2, 20, 6, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(7);
        doc.text(taskStatus, pageWidth - margin - 18, yPos + 6);
        
        yPos += 15;
    });
    
    // Observações
    checkPageHeight(40);
    doc.setFillColor(46, 125, 50);
    doc.setTextColor(255, 255, 255);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 10, 'F');
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text("OBSERVAÇÕES PARA TRANSFERÊNCIA", margin + 5, yPos + 7);
    yPos += 15;
    
    doc.setFillColor(250, 250, 250);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 30, 'F');
    doc.setDrawColor(200, 200, 200);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 30);
    
    doc.setFontSize(9);
    doc.setTextColor(33, 33, 33);
    doc.setFont(undefined, 'normal');
    
    const obsLines = doc.splitTextToSize(observations || project.observacoes || 'Nenhuma observação registrada.', pageWidth - 2 * margin - 10);
    doc.text(obsLines, margin + 5, yPos + 5);
    
    yPos += 35;
    
    // Rodapé
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(`Gerado em: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}`, margin, pageHeight - 5);
        doc.text(`Página ${i} de ${totalPages}`, pageWidth - margin, pageHeight - 5, { align: 'right' });
    }
    
    const fileName = `handover_${project.projectName.replace(/[^a-z0-9]/gi, '_')}_${project.id}.pdf`;
    doc.save(fileName);
}

// ==============================================

