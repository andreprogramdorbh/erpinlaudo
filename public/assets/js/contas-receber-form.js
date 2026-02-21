/**
 * ERP InLaudo - Módulo de Contas a Receber
 * JavaScript específico para lógica de negócio do formulário de contas a receber
 * Integração com Asaas, máscaras e validações
 * Versão: 1.0.0
 */

// Proteção de namespace - evita redeclaração
if (typeof ContasReceberForm === 'undefined') {
    class ContasReceberForm {
        constructor(container, options = {}) {
            this.container = typeof container === 'string' ? document.querySelector(container) : container;
            this.options = {
                contaId: options.contaId || null,
                isEdit: options.isEdit || false,
                activeTab: options.activeTab || 'geral',
                apiEndpoint: options.apiEndpoint || '/financeiro/contas-a-receber',
                ...options
            };

            this.formTabs = null;
            this.isSubmitting = false;

            this.init();
        }

        init() {
            if (!this.container) {
                console.error('ContasReceberForm: Container não encontrado');
                return;
            }

            this.setupFormTabs();
            this.setupMasks();
            this.setupPaymentMethods();
            this.setupFormSubmission();
            this.setupValidation();
            this.setupAsaasIntegration();
        }

        setupFormTabs() {
            // Inicializa o sistema de abas
            const tabsContainer = this.container.querySelector('.form-tabs-container');
            if (tabsContainer && window.FormTabs) {
                this.formTabs = new window.FormTabs(tabsContainer, {
                    activeTab: this.options.activeTab,
                    saveState: true,
                    onTabChange: (index, tab) => {
                        this.onTabChange(index, tab);
                    }
                });
            }
        }

        setupMasks() {
            // Máscara para valores monetários
            const valorInputs = this.container.querySelectorAll('input[name="valor"]');
            valorInputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    value = (value / 100).toFixed(2);
                    e.target.value = 'R$ ' + value.replace('.', ',').replace(/(\d)(?=(\d{3})+,)/g, '$1.');
                });

                // Formatação inicial
                if (input.value) {
                    const event = new Event('input');
                    input.dispatchEvent(event);
                }
            });

            // Máscara para data de vencimento
            const dateInputs = this.container.querySelectorAll('input[name="data_vencimento"]');
            dateInputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                        if (value.length >= 5) {
                            value = value.substring(0, 5) + '/' + value.substring(4, 8);
                        }
                    }
                    e.target.value = value;
                });
            });
        }

        setupPaymentMethods() {
            const meioPagamentoSelect = this.container.querySelector('select[name="meio_pagamento"]');
            const asaasInfo = this.container.querySelector('.asaas-integration-info');
            
            if (meioPagamentoSelect && asaasInfo) {
                meioPagamentoSelect.addEventListener('change', (e) => {
                    const isDigital = ['boleto', 'cartao', 'pix'].includes(e.target.value);
                    asaasInfo.style.display = isDigital ? 'block' : 'none';
                    
                    // Mostra informações sobre integração
                    if (isDigital) {
                        this.showPaymentMethodInfo(e.target.value);
                    }
                });

                // Estado inicial
                const initialValue = meioPagamentoSelect.value;
                if (['boleto', 'cartao', 'pix'].includes(initialValue)) {
                    asaasInfo.style.display = 'block';
                    this.showPaymentMethodInfo(initialValue);
                }
            }
        }

        showPaymentMethodInfo(method) {
            const messages = {
                'boleto': 'O boleto será gerado automaticamente e enviado por e-mail ao cliente.',
                'cartao': 'O link de pagamento com cartão de crédito será enviado ao cliente.',
                'pix': 'O código PIX será gerado e enviado por e-mail ao cliente.'
            };

            const infoDiv = this.container.querySelector('.payment-method-info');
            if (infoDiv && messages[method]) {
                infoDiv.innerHTML = `
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>${messages[method]}</div>
                    </div>
                `;
            }
        }

        setupFormSubmission() {
            const form = this.container.querySelector('form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.submitForm();
                });
            }
        }

        setupValidation() {
            // Validação de campos obrigatórios
            const requiredFields = this.container.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                field.addEventListener('blur', () => {
                    this.validateField(field);
                });
            });

            // Validação de valor
            const valorField = this.container.querySelector('input[name="valor"]');
            if (valorField) {
                valorField.addEventListener('blur', () => {
                    const value = parseFloat(valorField.value.replace(/[R$\s.]/g, '').replace(',', '.'));
                    if (value <= 0) {
                        this.showFieldError(valorField, 'Valor deve ser maior que zero');
                    } else {
                        this.clearFieldError(valorField);
                    }
                });
            }
        }

        setupAsaasIntegration() {
            // Verifica se Asaas está configurado
            const asaasStatus = this.container.querySelector('.asaas-status');
            if (asaasStatus) {
                // Simulação - em produção, verificar via API
                const isConfigured = true; // Substituir por verificação real
                asaasStatus.innerHTML = isConfigured 
                    ? '<span class="badge bg-success">Asaas Configurado</span>'
                    : '<span class="badge bg-warning">Asaas não configurado</span>';
            }
        }

        validateField(field) {
            if (field.hasAttribute('required') && !field.value.trim()) {
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
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                field.parentNode.appendChild(errorDiv);
            }
            
            errorDiv.textContent = message;
        }

        clearFieldError(field) {
            field.classList.remove('is-invalid');
            const errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) {
                errorDiv.remove();
            }
        }

        async submitForm() {
            if (this.isSubmitting) return;

            // Validação geral
            const form = this.container.querySelector('form');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                this.showAlert('Por favor, corrija os erros destacados.', 'error');
                return;
            }

            this.isSubmitting = true;
            this.setLoading(true);

            try {
                const formData = new FormData(form);
                const response = await fetch(this.options.apiEndpoint + (this.options.isEdit ? '/update/' + this.options.contaId : '/store'), {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    this.showAlert('Conta a receber salva com sucesso!', 'success');
                    
                    // Redireciona após sucesso
                    setTimeout(() => {
                        window.location.href = this.options.apiEndpoint;
                    }, 1500);
                } else {
                    this.showAlert(result.message || 'Erro ao salvar conta a receber.', 'error');
                }
            } catch (error) {
                console.error('Erro ao enviar formulário:', error);
                this.showAlert('Erro de conexão. Tente novamente.', 'error');
            } finally {
                this.isSubmitting = false;
                this.setLoading(false);
            }
        }

        setLoading(loading) {
            const submitBtn = this.container.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = loading;
                submitBtn.innerHTML = loading 
                    ? '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...'
                    : submitBtn.getAttribute('data-original-text') || 'Salvar';
            }
        }

        showAlert(message, type = 'info') {
            if (window.Swal) {
                Swal.fire({
                    icon: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
                    title: type === 'error' ? 'Erro' : type === 'success' ? 'Sucesso' : 'Informação',
                    text: message,
                    confirmButtonColor: '#00529B'
                });
            } else {
                alert(message);
            }
        }

        onTabChange(index, tab) {
            // Lógica quando mudar de aba
            console.log('Mudou para aba:', tab.id);
        }

        destroy() {
            // Cleanup
            if (this.formTabs) {
                this.formTabs.destroy();
            }
        }
    }

    // Export para uso global (apenas se não foi redefinido)
    if (typeof window.ContasReceberForm === 'undefined') {
        window.ContasReceberForm = ContasReceberForm;
    }

    // Fecha a proteção de namespace
} // if (typeof ContasReceberForm === 'undefined')

// Inicialização automática
document.addEventListener('DOMContentLoaded', function() {
    const formContainer = document.querySelector('.contas-receber-form');
    if (formContainer && !formContainer.contasReceberForm) {
        // Verifica se é edição
        const isEdit = formContainer.classList.contains('edit-form');
        const contaId = formContainer.getAttribute('data-conta-id');
        
        formContainer.contasReceberForm = new ContasReceberForm(formContainer, {
            isEdit: isEdit,
            contaId: contaId
        });
    }
});
