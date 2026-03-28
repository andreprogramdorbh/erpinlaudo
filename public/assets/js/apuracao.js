/**
 * apuracao.js — Módulo de Apuração Prestador / Cliente
 * Lógica de filtros, confirmação de faturamento e exclusão.
 */
(function () {
    'use strict';

    // Confirmação de faturamento
    window.confirmarFaturamento = function (url, tipo, nome, valor) {
        const msg = 'Faturar apuração de ' + tipo + ' para <strong>' + nome + '</strong>?<br>' +
                    'Valor: <strong>R$ ' + valor + '</strong><br><br>' +
                    'Será gerada uma ' + (tipo === 'Prestador' ? 'Conta a Pagar' : 'Conta a Receber') + ' no financeiro.';
        if (window.Swal) {
            Swal.fire({
                title: 'Confirmar Faturamento',
                html: msg,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00529B',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check me-1"></i> Faturar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.isConfirmed) window.location.href = url;
            });
        } else {
            if (confirm('Faturar apuração para ' + nome + '?\nValor: R$ ' + valor)) {
                window.location.href = url;
            }
        }
    };

    // Confirmação de exclusão
    window.confirmarExclusaoApuracao = function (url, msg) {
        if (window.Swal) {
            Swal.fire({
                title: 'Confirmar exclusão',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.isConfirmed) window.location.href = url;
            });
        } else {
            if (confirm(msg)) window.location.href = url;
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Flatpickr em campos de data
        if (window.flatpickr) {
            flatpickr('.flatpickr-date', {
                locale: 'pt',
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        }
    });
})();
