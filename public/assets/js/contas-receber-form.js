/**
 * ERP InLaudo - Módulo de Contas a Receber
 * JavaScript específico para lógica de negócio do formulário de contas a receber
 * Versão: 1.1.0
 *
 * Correções aplicadas:
 *  1. Máscara monetária não interfere mais no type="number" — usa campo display separado
 *  2. Máscara de data removida — type="date" tem seletor nativo do navegador
 *  3. Submissão usa a action do próprio <form> (POST nativo), não mais /store inexistente
 *  4. Valor é sanitizado antes de enviar (float puro, sem R$ ou vírgulas)
 */
if (typeof ContasReceberForm === 'undefined') {
    class ContasReceberForm {
        constructor(container, options = {}) {
            this.container = typeof container === 'string'
                ? document.querySelector(container)
                : container;
            this.options = {
                contaId:     options.contaId     || null,
                isEdit:      options.isEdit      || false,
                activeTab:   options.activeTab   || 'geral',
                apiEndpoint: options.apiEndpoint || '/financeiro/contas-a-receber',
                ...options
            };
            this.formTabs     = null;
            this.isSubmitting = false;
            this.init();
        }

        init() {
            if (!this.container) {
                console.error('ContasReceberForm: Container não encontrado');
                return;
            }
            this.setupFormTabs();
            this.setupValorField();
            this.setupPaymentMethods();
            this.setupFormSubmission();
            this.setupValidation();
            this.setupAsaasIntegration();
        }

        // =====================================================================
        // ABAS
        // =====================================================================
        setupFormTabs() {
            const tabsContainer = this.container.querySelector('.form-tabs-container');
            if (tabsContainer && window.FormTabs) {
                this.formTabs = new window.FormTabs(tabsContainer, {
                    activeTab: this.options.activeTab,
                    saveState: true,
                    onTabChange: (index, tab) => this.onTabChange(index, tab)
                });
            }
        }

        // =====================================================================
        // CAMPO VALOR
        // Estratégia: cria um campo de texto visível para formatação amigável
        // e mantém o input[name="valor"] oculto com o float puro para o PHP.
        // Isso evita conflito com type="number" que rejeita "R$", vírgulas etc.
        // =====================================================================
        setupValorField() {
            const valorInput = this.container.querySelector('input[name="valor"]');
            if (!valorInput) return;

            // Cria o campo de exibição formatado
            const displayInput       = document.createElement('input');
            displayInput.type        = 'text';
            displayInput.id          = 'valor_display';
            displayInput.className   = valorInput.className;
            displayInput.placeholder = 'Ex.: 1.500,00';
            displayInput.autocomplete = 'off';
            displayInput.inputMode   = 'numeric';

            // O campo original vira hidden — o display assume o required
            valorInput.type = 'hidden';
            valorInput.removeAttribute('required');
            displayInput.required = true;

            valorInput.parentNode.insertBefore(displayInput, valorInput);

            // Preenche com valor existente (modo edição)
            const currentVal = parseFloat(valorInput.value);
            if (!isNaN(currentVal) && currentVal > 0) {
                displayInput.value = this._formatarMoeda(currentVal);
            }

            // Formata enquanto digita
            displayInput.addEventListener('input', () => {
                const raw = displayInput.value.replace(/\D/g, '');
                if (raw === '') {
                    displayInput.value = '';
                    valorInput.value   = '';
                    return;
                }
                const numeric      = parseFloat(raw) / 100;
                displayInput.value = this._formatarMoeda(numeric);
                valorInput.value   = numeric.toFixed(2); // float puro para o PHP
            });

            // Valida ao sair do campo
            displayInput.addEventListener('blur', () => {
                const v = parseFloat(valorInput.value);
                if (!valorInput.value || isNaN(v) || v <= 0) {
                    this.showFieldError(displayInput, 'Informe um valor maior que zero');
                } else {
                    this.clearFieldError(displayInput);
                }
            });
        }

        _formatarMoeda(value) {
            return value.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // =====================================================================
        // MEIOS DE PAGAMENTO
        // =====================================================================
        setupPaymentMethods() {
            const meioPagamentoSelect = this.container.querySelector('select[name="meio_pagamento"]');
            const asaasInfo           = this.container.querySelector('.asaas-integration-info');
            if (!meioPagamentoSelect || !asaasInfo) return;

            const updateVisibility = (value) => {
                const isDigital = ['boleto', 'cartao', 'pix'].includes(value);
                asaasInfo.style.display = isDigital ? 'block' : 'none';
                if (isDigital) this.showPaymentMethodInfo(value);
            };

            meioPagamentoSelect.addEventListener('change', (e) => updateVisibility(e.target.value));
            updateVisibility(meioPagamentoSelect.value);
        }

        showPaymentMethodInfo(method) {
            const messages = {
                boleto: 'O boleto será gerado automaticamente e enviado por e-mail ao cliente.',
                cartao: 'O link de pagamento com cartão de crédito será enviado ao cliente.',
                pix:    'O código PIX será gerado e enviado por e-mail ao cliente.'
            };
            const infoDiv = this.container.querySelector('.payment-method-info');
            if (infoDiv && messages[method]) {
                infoDiv.innerHTML = `
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>${messages[method]}</div>
                    </div>`;
            }
        }

        // =====================================================================
        // SUBMISSÃO
        // Usa a action do próprio <form> definida no PHP:
        //   Criar: POST /financeiro/contas-a-receber
        //   Editar: POST /financeiro/contas-a-receber/update/{id}
        // O controller redireciona (302) em caso de sucesso.
        // =====================================================================
        setupFormSubmission() {
            const form = this.container.querySelector('form');
            if (!form) return;
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitForm(form);
            });
        }

        async submitForm(form) {
            if (this.isSubmitting) return;

            // Valida campos obrigatórios visíveis
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            requiredFields.forEach(field => {
                if (!this.validateField(field)) isValid = false;
            });

            // Valida campo valor (oculto)
            const valorHidden  = form.querySelector('input[name="valor"]');
            const valorDisplay = form.querySelector('#valor_display');
            if (valorHidden && (valorHidden.value === '' || parseFloat(valorHidden.value) <= 0)) {
                if (valorDisplay) this.showFieldError(valorDisplay, 'Informe um valor maior que zero');
                isValid = false;
            }

            if (!isValid) {
                this.showAlert('Por favor, corrija os erros destacados antes de salvar.', 'error');
                return;
            }

            this.isSubmitting = true;
            this.setLoading(true);

            try {
                const formData = new FormData(form);

                // fetch segue o redirect automaticamente
                const response = await fetch(form.action, {
                    method:   'POST',
                    body:     formData,
                    redirect: 'follow'
                });

                // Sucesso: o controller redirecionou para a listagem
                if (response.redirected || response.ok) {
                    window.location.href = response.redirected
                        ? response.url
                        : this.options.apiEndpoint;
                    return;
                }

                // Tenta JSON de erro
                const ct = response.headers.get('content-type') || '';
                if (ct.includes('application/json')) {
                    const result = await response.json();
                    this.showAlert(result.message || 'Erro ao salvar. Tente novamente.', 'error');
                } else {
                    // Fallback: redireciona para listagem
                    window.location.href = this.options.apiEndpoint;
                }

            } catch (error) {
                console.error('[ContasReceberForm] Erro ao enviar:', error);
                this.showAlert('Erro de conexão. Tente novamente.', 'error');
            } finally {
                this.isSubmitting = false;
                this.setLoading(false);
            }
        }

        // =====================================================================
        // VALIDAÇÃO
        // =====================================================================
        setupValidation() {
            const requiredFields = this.container.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                field.addEventListener('blur', () => this.validateField(field));
            });
        }

        validateField(field) {
            const value = (field.value || '').trim();
            if (field.hasAttribute('required') && value === '') {
                this.showFieldError(field, 'Campo obrigatório');
                return false;
            }
            this.clearFieldError(field);
            return true;
        }

        showFieldError(field, message) {
            field.classList.add('is-invalid');
            let errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv           = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                field.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
        }

        clearFieldError(field) {
            field.classList.remove('is-invalid');
            const errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) errorDiv.remove();
        }

        // =====================================================================
        // ASAAS
        // =====================================================================
        setupAsaasIntegration() {
            const asaasStatus = this.container.querySelector('.asaas-status');
            if (!asaasStatus) return;
            fetch('/api/asaas/status', { method: 'GET' })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    const configured = data && data.configured;
                    asaasStatus.innerHTML = configured
                        ? '<span class="badge bg-success">Asaas Configurado</span>'
                        : '<span class="badge bg-warning">Asaas não configurado</span>';
                })
                .catch(() => {
                    asaasStatus.innerHTML = '<span class="badge bg-secondary">Status desconhecido</span>';
                });
        }

        // =====================================================================
        // UTILITÁRIOS
        // =====================================================================
        setLoading(loading) {
            const submitBtn = this.container.querySelector('button[type="submit"]');
            if (!submitBtn) return;
            if (loading) {
                submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
                submitBtn.disabled  = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
            } else {
                submitBtn.disabled  = false;
                submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Salvar';
            }
        }

        showAlert(message, type = 'info') {
            if (window.Swal) {
                Swal.fire({
                    icon:               type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
                    title:              type === 'error' ? 'Erro' : type === 'success' ? 'Sucesso' : 'Informação',
                    text:               message,
                    confirmButtonColor: '#00529B'
                });
            } else {
                alert(message);
            }
        }

        onTabChange(index, tab) {
            console.debug('[ContasReceberForm] Aba ativa:', tab.id);
        }

        destroy() {
            if (this.formTabs) this.formTabs.destroy();
        }
    }

    if (typeof window.ContasReceberForm === 'undefined') {
        window.ContasReceberForm = ContasReceberForm;
    }
}

// Inicialização automática
// O enterprise-form component usa:
//   class="contas-receber-form form-edit-mode" (quando edição)
//   data-is-edit="true|false"
//   data-client-id="{id}" (record_id)
document.addEventListener('DOMContentLoaded', function () {
    const formContainer = document.querySelector('.contas-receber-form');
    if (formContainer && !formContainer.contasReceberForm) {
        // Lê atributos gerados pelo enterprise-form component
        const isEdit  = formContainer.getAttribute('data-is-edit') === 'true'
                     || formContainer.classList.contains('form-edit-mode');
        const contaId = formContainer.getAttribute('data-client-id');
        formContainer.contasReceberForm = new ContasReceberForm(formContainer, {
            isEdit:  isEdit,
            contaId: contaId
        });
    }
});
