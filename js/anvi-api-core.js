(function (global) {
    'use strict';

    const cache = new Map();
    const inflight = new Map();
    let csrfToken = null;

    function decoratePayload(data, response) {
        if (typeof data !== 'object' || data === null) {
            data = { success: false, message: 'Resposta inválida do servidor' };
        }

        if (!('success' in data)) {
            data.success = response.ok;
        }

        if (!('message' in data) && data.error) {
            data.message = data.error;
        }

        if (!('message' in data)) {
            data.message = response.ok ? 'OK' : (response.statusText || 'Erro na requisição');
        }

        if (data.csrf_token) {
            csrfToken = data.csrf_token;
        }

        Object.defineProperty(data, '_httpStatus', {
            value: response.status,
            enumerable: false
        });
        Object.defineProperty(data, '_ok', {
            value: response.ok,
            enumerable: false
        });

        return data;
    }

    function getCached(cacheKey) {
        if (!cacheKey || !cache.has(cacheKey)) return null;

        const entry = cache.get(cacheKey);
        if (entry.expiresAt <= Date.now()) {
            cache.delete(cacheKey);
            return null;
        }

        return entry.data;
    }

    function setCached(cacheKey, ttlMs, data) {
        if (!cacheKey || !ttlMs) return;

        cache.set(cacheKey, {
            data,
            expiresAt: Date.now() + ttlMs
        });
    }

    function clearCache(prefix) {
        if (!prefix) {
            cache.clear();
            return;
        }

        Array.from(cache.keys()).forEach((key) => {
            if (key === prefix || key.startsWith(prefix + ':')) {
                cache.delete(key);
            }
        });
    }

    async function request(url, options) {
        const config = options ? { ...options } : {};
        const cacheKey = config.cacheKey || null;
        const ttlMs = Number(config.ttlMs || 0);
        const force = config.force === true;
        const dedupe = config.dedupe !== false;

        delete config.cacheKey;
        delete config.ttlMs;
        delete config.force;
        delete config.dedupe;

        config.credentials = config.credentials || 'include';
        config.method = config.method || 'GET';
        config.headers = config.headers || {};

        const method = String(config.method).toUpperCase();
        const isMutating = ['POST', 'PUT', 'DELETE', 'PATCH'].includes(method);
        if (isMutating && !csrfToken) {
            await request('api/check_session.php', { force: true, ttlMs: 0, dedupe: false });
        }

        if (isMutating && csrfToken) {
            config.headers = { ...config.headers, 'X-CSRF-Token': csrfToken };
        }

        const canCache = method === 'GET' && cacheKey && ttlMs > 0;
        const requestKey = cacheKey || `${method}:${url}`;

        if (canCache && !force) {
            const cached = getCached(cacheKey);
            if (cached) return cached;
        }

        if (dedupe && inflight.has(requestKey)) {
            return inflight.get(requestKey);
        }

        if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
            config.headers = { ...(config.headers || {}), 'Content-Type': 'application/json' };
            config.body = JSON.stringify(config.body);
        }

        const promise = (async () => {
            const response = await fetch(url, config);
            const text = await response.text();

            let data = {};
            if (text) {
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    data = { success: false, message: text || 'Resposta não processável pelo servidor' };
                }
            }

            data = decoratePayload(data, response);

            if (canCache && response.ok) {
                setCached(cacheKey, ttlMs, data);
            }

            return data;
        })();

        if (dedupe) {
            inflight.set(requestKey, promise);
        }

        try {
            return await promise;
        } finally {
            inflight.delete(requestKey);
        }
    }

    function isAuthenticatedSession(payload) {
        return Boolean(payload && (
            payload.logado === true ||
            payload.logged === true ||
            payload.authenticated === true ||
            (payload.success === true && payload.user)
        ));
    }

    const api = {
        request,
        clearCache,
        isAuthenticatedSession,
        checkSession(options = {}) {
            return request('api/check_session.php', {
                cacheKey: 'session',
                ttlMs: options.ttlMs ?? 30000,
                force: options.force === true
            });
        },
        getDashboardStats(filters = {}, options = {}) {
            const params = new URLSearchParams();
            Object.entries(filters || {}).forEach(([key, value]) => {
                if (value !== undefined && value !== null && String(value).trim() !== '') {
                    params.set(key, String(value).trim());
                }
            });

            const query = params.toString();
            const url = query ? `api/estatisticas.php?${query}` : 'api/estatisticas.php';
            const cacheKey = query ? `dashboard:stats:${query}` : 'dashboard:stats';

            return request(url, {
                cacheKey,
                ttlMs: options.ttlMs ?? 15000,
                force: options.force === true
            });
        },
        async login(login, senha) {
            clearCache();
            if (!csrfToken) {
                await api.checkSession({ force: true, ttlMs: 0 });
            }
            return request('api/login.php', {
                method: 'POST',
                body: { login: login, senha: senha, _csrf_token: csrfToken }
            });
        },
        logout() {
            clearCache();
            return request('api/logout.php', { method: 'POST' });
        },
        listUsers() {
            return request('api/usuarios.php');
        },
        getUserById(id) {
            return request('api/usuarios.php?id=' + encodeURIComponent(id));
        },
        saveUser(payload) {
            clearCache('dashboard');
            const method = payload && payload.id ? 'PUT' : 'POST';
            return request('api/usuarios.php', { method: method, body: payload });
        },
        deleteUserById(id) {
            clearCache('dashboard');
            return request('api/usuarios.php', {
                method: 'DELETE',
                body: { id: id }
            });
        },
        listAnvis() {
            return request('api/anvi.php');
        },
        getAnviById(id) {
            return request('api/anvi.php?id=' + encodeURIComponent(id));
        },
        saveAnvi(payload) {
            clearCache('dashboard');
            return request('api/anvi.php', {
                method: 'POST',
                body: payload
            });
        },
        deleteAnviById(id) {
            clearCache('dashboard');
            return request('api/anvi.php', {
                method: 'DELETE',
                body: { id: id }
            });
        },
        updateAnviLock(id, acao) {
            const payload = id ? { id: id, acao: acao } : { acao: acao };
            return request('api/anvi.php', {
                method: 'PUT',
                body: payload
            });
        },
        getPublicStats(options = {}) {
            return request('api/estatisticas_publicas.php', {
                cacheKey: 'public:stats',
                ttlMs: options.ttlMs ?? 300000,
                force: options.force === true
            });
        },
        getSubscriptionCurrent(options = {}) {
            return request('api/subscription_current.php', {
                cacheKey: 'billing:subscription',
                ttlMs: options.ttlMs ?? 60000,
                force: options.force === true
            });
        },
        getBillingInvoices(limit = 20, options = {}) {
            return request('api/billing_invoices.php?limit=' + encodeURIComponent(limit), {
                cacheKey: `billing:invoices:${limit}`,
                ttlMs: options.ttlMs ?? 60000,
                force: options.force === true
            });
        },
        createCheckout(payload) {
            clearCache('billing');
            return request('api/checkout_create.php', {
                method: 'POST',
                body: payload
            });
        }
    };

    global.ViabixApiCore = api;
})(window);
