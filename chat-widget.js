/**
 * Viabix — Widget de Suporte por IA
 * Inclua este script antes do </body> em qualquer página.
 * Detecta automaticamente o caminho correto para a API.
 */
(function () {
    'use strict';

    // Detecta se está em subpasta (ex: /Controle_de_projetos/) ou raiz
    const basePath = (function () {
        const path = window.location.pathname;
        if (path.includes('/Controle_de_projetos/') || path.includes('/api/')) {
            return '../api/chat_suporte.php';
        }
        return 'api/chat_suporte.php';
    })();

    const QUICK_REPLIES = [
        'Como funciona o trial?',
        'Quais são os planos?',
        'O que é a ANVI?',
        'Tem app Android?',
        'Como criar minha conta?',
    ];

    let history = [];
    let isOpen = false;
    let isLoading = false;

    // ---- CSS ----
    const css = `
        #vbx-chat-btn {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 9999;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1B5E20, #2e7d32);
            color: #fff;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 28px rgba(27,94,32,0.42);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        #vbx-chat-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 36px rgba(27,94,32,0.55);
        }
        #vbx-chat-btn .vbx-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            background: #f08a24;
            border-radius: 50%;
            border: 2px solid #fff;
            display: none;
        }
        #vbx-chat-panel {
            position: fixed;
            bottom: 96px;
            right: 28px;
            z-index: 9998;
            width: 360px;
            max-width: calc(100vw - 40px);
            height: 520px;
            max-height: calc(100vh - 130px);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 24px 80px rgba(8,29,26,0.22);
            display: none;
            flex-direction: column;
            overflow: hidden;
            font-family: 'Sora', -apple-system, sans-serif;
            border: 1px solid rgba(31,90,73,0.1);
        }
        #vbx-chat-panel.open { display: flex; animation: vbxSlideIn 0.22s ease; }
        @keyframes vbxSlideIn {
            from { opacity: 0; transform: translateY(16px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .vbx-header {
            background: linear-gradient(135deg, #1B5E20, #2e7d32);
            color: #fff;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .vbx-header-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .vbx-header-info { flex: 1; }
        .vbx-header-name { font-weight: 700; font-size: 0.95rem; }
        .vbx-header-status { font-size: 0.78rem; opacity: 0.85; margin-top: 2px; }
        .vbx-header-close {
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            line-height: 1;
            transition: color 0.15s;
        }
        .vbx-header-close:hover { color: #fff; }
        .vbx-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scroll-behavior: smooth;
        }
        .vbx-messages::-webkit-scrollbar { width: 4px; }
        .vbx-messages::-webkit-scrollbar-track { background: transparent; }
        .vbx-messages::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 4px; }
        .vbx-msg {
            max-width: 84%;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 0.88rem;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .vbx-msg.bot {
            background: #f0ece3;
            color: #16211c;
            border-bottom-left-radius: 4px;
            align-self: flex-start;
        }
        .vbx-msg.user {
            background: linear-gradient(135deg, #1B5E20, #2e7d32);
            color: #fff;
            border-bottom-right-radius: 4px;
            align-self: flex-end;
        }
        .vbx-msg.error {
            background: #fff0f0;
            color: #c0392b;
            border: 1px solid #fcc;
            align-self: flex-start;
        }
        .vbx-typing {
            display: flex;
            gap: 5px;
            padding: 12px 14px;
            background: #f0ece3;
            border-radius: 14px;
            border-bottom-left-radius: 4px;
            align-self: flex-start;
        }
        .vbx-typing span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #1f5a49;
            opacity: 0.4;
            animation: vbxBounce 1.2s infinite;
        }
        .vbx-typing span:nth-child(2) { animation-delay: 0.2s; }
        .vbx-typing span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes vbxBounce {
            0%, 80%, 100% { opacity: 0.4; transform: translateY(0); }
            40%           { opacity: 1;   transform: translateY(-5px); }
        }
        .vbx-quick {
            padding: 8px 16px 12px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            flex-shrink: 0;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        .vbx-quick button {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
            border: 1.5px solid #1f5a49;
            background: transparent;
            color: #1f5a49;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
            font-family: inherit;
        }
        .vbx-quick button:hover {
            background: #1f5a49;
            color: #fff;
        }
        .vbx-footer {
            padding: 10px 12px 14px;
            display: flex;
            gap: 8px;
            border-top: 1px solid rgba(0,0,0,0.06);
            flex-shrink: 0;
        }
        #vbx-input {
            flex: 1;
            border: 1.5px solid #ddd;
            border-radius: 12px;
            padding: 9px 13px;
            font-size: 0.88rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
            resize: none;
            height: 40px;
            line-height: 1.4;
        }
        #vbx-input:focus { border-color: #1f5a49; }
        #vbx-send {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1B5E20, #2e7d32);
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: opacity 0.15s;
        }
        #vbx-send:disabled { opacity: 0.4; cursor: not-allowed; }
    `;

    // ---- Injetar CSS ----
    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    // ---- HTML ----
    const html = `
        <button id="vbx-chat-btn" title="Suporte Viabix" aria-label="Abrir chat de suporte">
            💬
            <span class="vbx-badge" id="vbx-badge"></span>
        </button>
        <div id="vbx-chat-panel" role="dialog" aria-label="Chat de suporte Viabix">
            <div class="vbx-header">
                <div class="vbx-header-icon">🏭</div>
                <div class="vbx-header-info">
                    <div class="vbx-header-name">Suporte Viabix</div>
                    <div class="vbx-header-status">● Online — resposta em segundos</div>
                </div>
                <button class="vbx-header-close" id="vbx-close" aria-label="Fechar chat">✕</button>
            </div>
            <div class="vbx-messages" id="vbx-messages"></div>
            <div class="vbx-quick" id="vbx-quick"></div>
            <div class="vbx-footer">
                <input type="text" id="vbx-input" placeholder="Digite sua dúvida..." maxlength="500" autocomplete="off">
                <button id="vbx-send" aria-label="Enviar mensagem">➤</button>
            </div>
        </div>
    `;

    const container = document.createElement('div');
    container.innerHTML = html;
    document.body.appendChild(container);

    // ---- Elementos ----
    const btn = document.getElementById('vbx-chat-btn');
    const panel = document.getElementById('vbx-chat-panel');
    const messagesEl = document.getElementById('vbx-messages');
    const quickEl = document.getElementById('vbx-quick');
    const input = document.getElementById('vbx-input');
    const sendBtn = document.getElementById('vbx-send');
    const badge = document.getElementById('vbx-badge');
    const closeBtn = document.getElementById('vbx-close');

    // ---- Funções ----
    function togglePanel() {
        isOpen = !isOpen;
        panel.classList.toggle('open', isOpen);
        btn.innerHTML = isOpen ? '✕ <span class="vbx-badge" id="vbx-badge"></span>' : '💬 <span class="vbx-badge" id="vbx-badge" style="display:none"></span>';
        if (isOpen) {
            if (messagesEl.children.length === 0) showWelcome();
            input.focus();
        }
    }

    function showWelcome() {
        addMessage('bot', 'Olá! 👋 Sou o assistente do **Viabix**. Posso te ajudar com dúvidas sobre planos, funcionalidades, trial e muito mais.\n\nComo posso te ajudar?');
        renderQuickReplies();
    }

    function addMessage(type, text) {
        const el = document.createElement('div');
        el.className = `vbx-msg ${type}`;
        el.textContent = text;
        messagesEl.appendChild(el);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return el;
    }

    function showTyping() {
        const el = document.createElement('div');
        el.className = 'vbx-typing';
        el.id = 'vbx-typing';
        el.innerHTML = '<span></span><span></span><span></span>';
        messagesEl.appendChild(el);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function removeTyping() {
        const el = document.getElementById('vbx-typing');
        if (el) el.remove();
    }

    function renderQuickReplies() {
        quickEl.innerHTML = QUICK_REPLIES.map(q =>
            `<button onclick="window.vbxSendQuick('${q.replace(/'/g, "\\'")}')">${q}</button>`
        ).join('');
    }

    function hideQuickReplies() {
        quickEl.innerHTML = '';
    }

    async function sendMessage(text) {
        if (!text || isLoading) return;
        isLoading = true;
        sendBtn.disabled = true;
        hideQuickReplies();

        addMessage('user', text);
        history.push({ role: 'user', content: text });

        showTyping();

        try {
            const res = await fetch(basePath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, history: history.slice(0, -1) }),
            });

            removeTyping();

            const data = await res.json();

            if (data.error) {
                addMessage('error', data.error);
            } else {
                addMessage('bot', data.reply);
                history.push({ role: 'assistant', content: data.reply });
            }
        } catch (e) {
            removeTyping();
            addMessage('error', 'Erro de conexão. Verifique sua internet e tente novamente.');
        }

        isLoading = false;
        sendBtn.disabled = false;
        input.focus();
    }

    // Expor função global para os quick replies
    window.vbxSendQuick = function (text) { sendMessage(text); };

    // ---- Eventos ----
    btn.addEventListener('click', togglePanel);
    closeBtn.addEventListener('click', togglePanel);

    sendBtn.addEventListener('click', () => {
        const text = input.value.trim();
        input.value = '';
        sendMessage(text);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const text = input.value.trim();
            input.value = '';
            sendMessage(text);
        }
    });

    // Mostrar badge após 3s se ainda não abriu (convida o usuário)
    setTimeout(() => {
        if (!isOpen) {
            badge.style.display = 'block';
        }
    }, 3000);

})();
