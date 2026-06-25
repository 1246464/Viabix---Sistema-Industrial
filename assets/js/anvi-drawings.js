// =========================================
// FUNÇÕES PARA A ABA DE DESENHOS (MANTIDAS DO ORIGINAL)
// =========================================
let desenhosANVI = [];
const uploadDesenhoInput = document.getElementById('uploadDesenho');
const galeriaDesenhos = document.getElementById('galeriaDesenhos');
let modalIndex = 0;

function salvarDesenhosNoEstado() {
    console.log(`Estado dos desenhos atualizado. Total: ${desenhosANVI.length}`);
}

function renderizarDesenhos() {
    if (!galeriaDesenhos) return;

    if (desenhosANVI.length === 0) {
        galeriaDesenhos.innerHTML = `<p class="text-muted text-center py-4" id="galeria-placeholder"><i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block"></i>Nenhum desenho anexado. Use o campo acima para adicionar.</p>`;
        return;
    }

    let galeriaHTML = '';
    desenhosANVI.forEach((src, index) => {
        galeriaHTML += `
            <div class="desenho-item" data-index="${index}">
                <img src="${src}" alt="Desenho ${index + 1}" onclick="abrirModal(${index})" style="cursor: pointer;">
                <div class="desenho-actions">
                    <button class="btn-view" onclick="abrirModal(${index})" title="Visualizar">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn-delete" onclick="removerDesenho(${index})" title="Excluir">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                </div>
            </div>
        `;
    });
    galeriaDesenhos.innerHTML = galeriaHTML;
}

window.abrirModal = function(index) {
    const modal = document.getElementById('desenhoModal');
    const modalImg = document.getElementById('desenhoModalImage');
    const captionText = document.getElementById('desenhoModalCaption');
    const counter = document.getElementById('desenhoCounter');
    
    if (!modal || !modalImg || !captionText || !counter) return;
    
    modalIndex = index;
    modalImg.src = desenhosANVI[index];
    captionText.innerHTML = `Desenho ${index + 1} de ${desenhosANVI.length}`;
    counter.innerHTML = `${index + 1} / ${desenhosANVI.length}`;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

window.fecharModal = function() {
    const modal = document.getElementById('desenhoModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

window.proximoDesenho = function() {
    if (desenhosANVI.length === 0) return;
    modalIndex = (modalIndex + 1) % desenhosANVI.length;
    abrirModal(modalIndex);
}

window.desenhoAnterior = function() {
    if (desenhosANVI.length === 0) return;
    modalIndex = (modalIndex - 1 + desenhosANVI.length) % desenhosANVI.length;
    abrirModal(modalIndex);
}

window.removerDesenho = function(index) {
    if (index >= 0 && index < desenhosANVI.length) {
        desenhosANVI.splice(index, 1);
        salvarDesenhosNoEstado();
        renderizarDesenhos();
        
        const modal = document.getElementById('desenhoModal');
        if (modal && modal.style.display === 'block') {
            if (desenhosANVI.length === 0) {
                fecharModal();
            } else {
                if (modalIndex >= desenhosANVI.length) {
                    modalIndex = desenhosANVI.length - 1;
                } else if (index < modalIndex) {
                    modalIndex--;
                }
                abrirModal(modalIndex);
            }
        }
        
        mostrarNotificacao('Desenho removido.', 'info');
    }
}

function handleDesenhoUpload(event) {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    for (let file of files) {
        if (!file.type.startsWith('image/')) {
            mostrarNotificacao(`O arquivo "${file.name}" não é uma imagem e foi ignorado.`, 'warning');
            continue;
        }

        const reader = new FileReader();

        reader.onload = function(ev) {
            desenhosANVI.push(ev.target.result);
            salvarDesenhosNoEstado();
            renderizarDesenhos();
        };

        reader.onerror = function() {
            mostrarNotificacao(`Erro ao ler o arquivo "${file.name}".`, 'error');
        };

        reader.readAsDataURL(file);
    }

    event.target.value = '';
}

if (uploadDesenhoInput) {
    uploadDesenhoInput.addEventListener('change', handleDesenhoUpload);
}

renderizarDesenhos();

