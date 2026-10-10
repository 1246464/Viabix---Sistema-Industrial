(function () {
    'use strict';

    const STORAGE_KEY = 'viabix_cookie_notice_v1';
    const POLICY_VERSION = 1;
    const scriptUrl = document.currentScript && document.currentScript.src
        ? document.currentScript.src
        : window.location.href;
    const policyUrl = new URL('../../politica-de-cookies.html', scriptUrl).href;

    function readAcknowledgement() {
        try {
            const value = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
            return value && value.version === POLICY_VERSION ? value : null;
        } catch (_) {
            return null;
        }
    }

    function saveAcknowledgement() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                version: POLICY_VERSION,
                acknowledgedAt: new Date().toISOString()
            }));
        } catch (_) {
            // O aviso continua funcional mesmo quando o navegador bloqueia armazenamento local.
        }
    }

    function styles() {
        const style = document.createElement('style');
        style.textContent = `
            .viabix-cookie-banner{position:fixed;z-index:100000;left:16px;right:16px;bottom:16px;max-width:760px;margin:auto;background:#fff;color:#17352a;border:1px solid #c9ddd2;border-radius:16px;box-shadow:0 16px 45px rgba(0,0,0,.22);padding:18px;font:14px/1.5 system-ui,-apple-system,Segoe UI,sans-serif}
            .viabix-cookie-banner[hidden],.viabix-cookie-dialog[hidden]{display:none!important}
            .viabix-cookie-banner h2,.viabix-cookie-dialog h2{font-size:18px;margin:0 0 8px;color:#165a38}
            .viabix-cookie-banner p,.viabix-cookie-dialog p{margin:0 0 12px}
            .viabix-cookie-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
            .viabix-cookie-button{border:1px solid #165a38;border-radius:9px;padding:9px 14px;background:#fff;color:#165a38;font-weight:700;cursor:pointer;text-decoration:none}
            .viabix-cookie-button.primary{background:#165a38;color:#fff}
            .viabix-cookie-manage{position:fixed;z-index:99998;left:12px;bottom:12px;border:1px solid #165a38;border-radius:999px;background:#fff;color:#165a38;padding:7px 11px;font:600 12px system-ui;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.15)}
            .viabix-cookie-dialog{position:fixed;z-index:100001;inset:0;background:rgba(0,0,0,.5);display:grid;place-items:center;padding:16px}
            .viabix-cookie-panel{max-width:620px;max-height:85vh;overflow:auto;background:#fff;color:#17352a;border-radius:16px;padding:22px;box-shadow:0 18px 55px rgba(0,0,0,.3);font:14px/1.5 system-ui,-apple-system,Segoe UI,sans-serif}
            .viabix-cookie-category{padding:12px 0;border-top:1px solid #dbe8e1}
            .viabix-cookie-category strong{display:block;color:#165a38;margin-bottom:4px}
            @media(max-width:540px){.viabix-cookie-banner{left:8px;right:8px;bottom:8px}.viabix-cookie-actions{align-items:stretch}.viabix-cookie-button{text-align:center;flex:1 1 100%}}
        `;
        document.head.appendChild(style);
    }

    function buildUi() {
        styles();

        const banner = document.createElement('section');
        banner.className = 'viabix-cookie-banner';
        banner.setAttribute('role', 'region');
        banner.setAttribute('aria-label', 'Aviso sobre cookies');
        banner.innerHTML = `
            <h2>Privacidade e cookies</h2>
            <p>Usamos cookies estritamente necessários para sessão e segurança, além de armazenamento local para recursos solicitados, como preferências e rascunhos. Não identificamos cookies de publicidade ou análise comportamental neste site.</p>
            <div class="viabix-cookie-actions">
                <button class="viabix-cookie-button primary" type="button" data-cookie-ack>Entendi</button>
                <button class="viabix-cookie-button" type="button" data-cookie-details>Ver detalhes</button>
                <a class="viabix-cookie-button" href="${policyUrl}">Política de Cookies</a>
            </div>
        `;

        const manage = document.createElement('button');
        manage.className = 'viabix-cookie-manage';
        manage.type = 'button';
        manage.textContent = 'Cookies';
        manage.setAttribute('aria-label', 'Consultar informações sobre cookies');

        const dialog = document.createElement('div');
        dialog.className = 'viabix-cookie-dialog';
        dialog.hidden = true;
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-labelledby', 'viabix-cookie-title');
        dialog.innerHTML = `
            <div class="viabix-cookie-panel">
                <h2 id="viabix-cookie-title">Como usamos cookies</h2>
                <div class="viabix-cookie-category">
                    <strong>Necessários — sempre ativos</strong>
                    Mantêm autenticação, sessão, proteção contra requisições indevidas e outras funções essenciais. Desativá-los no navegador pode impedir o login e o salvamento seguro.
                </div>
                <div class="viabix-cookie-category">
                    <strong>Armazenamento local de funcionalidades</strong>
                    Guarda no próprio navegador preferências, rascunhos e recursos escolhidos pelo usuário. Essas informações não são usadas para publicidade comportamental.
                </div>
                <div class="viabix-cookie-category">
                    <strong>Publicidade e análise comportamental</strong>
                    Não identificamos esse tipo de cookie na implementação atual.
                </div>
                <p>Você pode apagar cookies e dados locais nas configurações do navegador. Cookies de sessão são removidos no logout ou ao final do prazo configurado.</p>
                <div class="viabix-cookie-actions">
                    <button class="viabix-cookie-button primary" type="button" data-cookie-close>Fechar</button>
                    <a class="viabix-cookie-button" href="${policyUrl}">Ler política completa</a>
                </div>
            </div>
        `;

        function openDetails() {
            dialog.hidden = false;
            dialog.querySelector('[data-cookie-close]').focus();
        }

        function closeDetails() {
            dialog.hidden = true;
            manage.focus();
        }

        banner.querySelector('[data-cookie-ack]').addEventListener('click', function () {
            saveAcknowledgement();
            banner.hidden = true;
        });
        banner.querySelector('[data-cookie-details]').addEventListener('click', openDetails);
        manage.addEventListener('click', openDetails);
        dialog.querySelector('[data-cookie-close]').addEventListener('click', closeDetails);
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) closeDetails();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !dialog.hidden) closeDetails();
        });

        banner.hidden = Boolean(readAcknowledgement());
        document.body.append(banner, manage, dialog);
        window.ViabixCookies = { open: openDetails, policyUrl: policyUrl };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildUi, { once: true });
    } else {
        buildUi();
    }
})();
