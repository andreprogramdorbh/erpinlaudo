/**
 * ERP InLaudo - Global Error Logger
 * Captura erros de JavaScript e reporta para o backend para diagnóstico.
 */
(function () {
    function reportError(errorData) {
        // Evita reportar o mesmo erro repetidamente em loop curto
        const errorKey = errorData.message + errorData.line + errorData.column;
        const lastReport = window._lastErrorReported || {};
        const now = Date.now();

        if (lastReport.key === errorKey && (now - lastReport.time < 5000)) {
            return;
        }

        window._lastErrorReported = { key: errorKey, time: now };

        // Envia para o backend
        fetch('/api/log/error', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(errorData)
        }).catch(err => {
            // Silencioso se falhar o log (evita recursão)
            console.warn('Falha ao reportar erro ao servidor');
        });
    }

    // Captura erros síncronos e assíncronos comuns
    window.onerror = function (message, url, line, column, error) {
        reportError({
            message: message,
            url: url,
            line: line,
            column: column,
            stack: error ? error.stack : 'N/A',
            type: 'UncaughtException'
        });
        return false; // Permite que o erro continue aparecendo no console
    };

    // Captura promessas rejeitadas e não tratadas
    window.onunhandledrejection = function (event) {
        reportError({
            message: event.reason ? event.reason.message || event.reason.toString() : 'Unhandled Rejection',
            url: window.location.href,
            stack: event.reason ? event.reason.stack : 'N/A',
            type: 'UnhandledRejection'
        });
    };

    console.log('ERP Global Error Logger inicializado.');
})();
