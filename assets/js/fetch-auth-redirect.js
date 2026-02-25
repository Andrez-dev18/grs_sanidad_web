/**
 * Redirige al login cuando cualquier fetch recibe 401.
 * Incluir en páginas bajo modules/planificacion/programas y modules/planificacion/cronograma.
 */
(function() {
    var loginUrl = '../../../login.php';
    var origFetch = window.fetch;
    if (!origFetch) return;
    window.fetch = function(url, opts) {
        return origFetch.apply(this, arguments).then(function(r) {
            if (r.status === 401) {
                if (window.top !== window.self) window.top.location.href = loginUrl;
                else window.location.href = loginUrl;
                return Promise.reject(new Error('Unauthorized'));
            }
            return r;
        });
    };
})();
