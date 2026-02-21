/**
 * ERP InLaudo - Módulo de Clientes
 * JavaScript específico para lógica de negócio do formulário de clientes
 * Integração com BrasilAPI, máscaras e gestão de contatos
 * Versão: 2.0.0 - Compatível com novo sistema de abas
 */

// Proteção de namespace - evita redeclaração (sem criar bindings globais)
if (!window.ClientesForm) {
    window.ClientesForm = class ClientesForm {
        constructor(container, options = {}) {
            this.container = typeof container === 'string' ? document.querySelector(container) : container;
            this.options = {
                clientId: options.clientId || null,
                isEdit: options.isEdit || false,
                activeTab: options.activeTab || 'geral',
                apiEndpoint: options.apiEndpoint || '/clientes',
                ...options
            };

            this.formTabs = null;
            this.contatos = [];
            this.isSubmitting = false;

            this.init();
        }

        init() {
            if (!this.container) {
                console.error('ClientesForm: Container não encontrado');
                return;
            }

            this.setupFormTabs();
            this.setupMasks();
            this.setupCnpjSearch();
            this.setupContactManagement();
            this.setupFormSubmission();
            this.setupValidation();

            // Se for edição e há ID do cliente, desbloqueia aba de contatos
            if (this.options.isEdit && this.options.clientId) {
                setTimeout(() => {
                    this.unlockContactsTab();
                }, 500);
            }
        }

        setupFormTabs() {
            // Inicializa o sistema de abas
            const tabsContainer = this.container.querySelector('.form-tabs-container');
            if (tabsContainer && window.FormTabs) {
                this.formTabs = new window.FormTabs(tabsContainer, {
                    activeTab: this.options.activeTab === 'contatos' ? 1 : 0,
                    saveState: true,
                    onTabChange: (oldIndex, newIndex, oldTab, newTab) => {
                        // Validação antes de mudar de aba
                        if (oldIndex === 0 && newIndex === 1) {
                            return this.validateGeneralTab();
                        }
                        return true;
                    },
                    onTabLocked: (tab) => {
                        console.log('Aba bloqueada:', tab);
                    }
                });
            }
        }

        setupMasks() {
            const $cpfCnpj = $('#cpf_cnpj');
            const $tipo = $('#tipo');
            const $cep = $('#cep');
            const $phones = $('input[name*="telefone"], input[name*="celular"], #valor_contato');

            const applyCpfCnpjMask = () => {
                const tipo = $tipo.val();
                $cpfCnpj.unmask();

                if (tipo === 'PF') {
                    $cpfCnpj.mask('000.000.000-00', { placeholder: "000.000.000-00" });
                    // Change label and placeholder for CPF
                    $('label[for="cpf_cnpj"]').text('CPF');
                    $cpfCnpj.attr('placeholder', '000.000.000-00');
                    // Hide search button for CPF
                    $('#btn_consulta').hide();
                    // Hide nome_fantasia field for PF
                    $('#nome_fantasia').closest('.form-group').hide();
                    // Change razao_social label
                    $('label[for="razao_social"]').text('Nome Completo');
                    $('label[for="razao_social"]').addClass('required');
                } else {
                    $cpfCnpj.mask('00.000.000/0000-00', { placeholder: "00.000.000/0000-00" });
                    // Change label and placeholder for CNPJ
                    $('label[for="cpf_cnpj"]').text('CNPJ');
                    $cpfCnpj.attr('placeholder', '00.000.000/0000-00');
                    // Show search button for CNPJ
                    $('#btn_consulta').show();
                    // Show nome_fantasia field for PJ
                    $('#nome_fantasia').closest('.form-group').show();
                    // Change razao_social label
                    $('label[for="razao_social"]').text('Razão Social');
                    $('label[for="razao_social"]').addClass('required');
                }
            };

            if ($cpfCnpj.length && $tipo.length) {
                applyCpfCnpjMask();
                $tipo.on('change', () => {
                    $cpfCnpj.val('');
                    applyCpfCnpjMask();
                });
            }

            if ($cep.length) {
                $cep.mask('00000-000');
            }

            if ($phones.length) {
                const SPMaskBehavior = function (val) {
                    return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
                };
                const spOptions = {
                    onKeyPress: function (val, e, field, options) {
                        field.mask(SPMaskBehavior.apply({}, arguments), options);
                    }
                };
                $phones.mask(SPMaskBehavior, spOptions);
            }
        }

        setupCnpjSearch() {
            const $btnConsulta = $('#btn_consulta');
            const $cpfCnpj = $('#cpf_cnpj');

            if (!$btnConsulta.length || !$cpfCnpj.length) return;

            $btnConsulta.on('click', () => {
                this.searchCnpj();
            });

            $cpfCnpj.on('blur', () => {
                const cnpj = $cpfCnpj.val().replace(/\D/g, '');
                if (cnpj.length === 14) {
                    this.searchCnpj();
                }
            });
        }

        searchCnpj() {
            const $cpfCnpj = $('#cpf_cnpj');
            const $btnConsulta = $('#btn_consulta');
            const cnpj = $cpfCnpj.val().replace(/\D/g, '');

            if (cnpj.length !== 14) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    text: 'Por favor, digite um CNPJ completo para consulta.',
                    confirmButtonColor: '#00529B'
                });
                return;
            }

            // Loading state
            const originalHtml = $btnConsulta.html();
            $btnConsulta.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1"></span> Consultando...');

            fetch(`/clientes/buscar-cnpj?cnpj=${cnpj}`)
                .then(response => {
                    // Trata diferentes códigos de status HTTP
                    if (!response.ok) {
                        if (response.status === 400) {
                            throw new Error('CNPJ inválido. Verifique o formato e os dígitos verificadores.');
                        } else if (response.status === 404) {
                            throw new Error('CNPJ não encontrado na base de dados da Receita Federal.');
                        } else if (response.status === 500) {
                            throw new Error('Erro interno no servidor. Tente novamente em alguns minutos.');
                        } else {
                            throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
                        }
                    }
                    return response.json().then(err => { throw err; });
                })
                .then(data => {
                    if (data.erro) throw new Error(data.erro);
                    
                    this.fillCnpjData(data);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: 'Dados do CNPJ importados com sucesso!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                })
                .catch(error => {
                    console.error('Erro na consulta CNPJ:', error);

                    Swal.fire({
                        icon: 'error',
                        title: 'Falha na Consulta',
                        text: error.erro || 'Não foi possível localizar os dados deste CNPJ. Verifique a numeração ou preencha manualmente.',
                        confirmButtonColor: '#00529B'
                    });
                })
                .finally(() => {
                    $btnConsulta.prop('disabled', false).html(originalHtml);
                });
        }

        fillCnpjData(data) {
            // Dados já vêm mapeados do Controller (BrasilAPI → Banco)
            const fields = {
                'razao_social': data.razao_social,
                'nome_fantasia': data.nome_fantasia,
                'email': data.email,
                'cep': data.cep,
                'endereco': data.endereco,        // Já mapeado no Controller
                'numero': data.numero,
                'complemento': data.complemento,
                'bairro': data.bairro,
                'cidade': data.cidade,          // Já mapeado no Controller
                'estado': data.estado,
                'telefone': data.telefone,
                'cnae_principal': data.cnae_principal,
                'descricao_cnae': data.descricao_cnae
            };

            Object.entries(fields).forEach(([field, value]) => {
                const input = document.getElementById(field);
                if (input && value) {
                    input.value = value;
                    // Dispara evento para validar se necessário
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('blur', { bubbles: true }));
                }
            });

            // Feedback visual de sucesso
            this.showSuccessFeedback();
        }

        showSuccessFeedback() {
            // Adiciona classe de sucesso aos campos preenchidos
            const filledInputs = document.querySelectorAll('#clienteFormGeral input[value]:not([value=""])');
            filledInputs.forEach(input => {
                input.classList.add('is-valid');
                setTimeout(() => input.classList.remove('is-valid'), 3000);
            });
        }

        setupContactManagement() {
            // Botão de adicionar contato
            const btnAddContato = document.getElementById('btnSalvarContato');
            if (btnAddContato) {
                btnAddContato.addEventListener('click', () => {
                    this.addContact();
                });
            }

            // Máscara dinâmica para valor do contato
            const tipoContatoSelect = document.getElementById('tipo_contato_sel');
            const valorContatoInput = document.getElementById('valor_contato');

            if (tipoContatoSelect && valorContatoInput) {
                tipoContatoSelect.addEventListener('change', () => {
                    this.updateContactMask(tipoContatoSelect.value, valorContatoInput);
                });

                // Inicializa máscara
                this.updateContactMask(tipoContatoSelect.value, valorContatoInput);
            }
        }

        updateContactMask(tipo, input) {
            if (!input) return;

            // Remove máscaras existentes
            input.value = input.value.replace(/\D/g, '');

            switch (tipo) {
                case 'Celular':
                    input.placeholder = '(00) 00000-0000';
                    break;
                case 'Email':
                    input.placeholder = 'exemplo@email.com';
                    break;
                case 'Comercial':
                case 'Residencial':
                    input.placeholder = '(00) 0000-0000';
                    break;
                default:
                    input.placeholder = 'Digite o valor';
            }
        }

        addContact() {
            const form = document.getElementById('formAddContato');
            const btn = document.getElementById('btnSalvarContato');

            if (!form || !btn) return;

            const formData = new FormData(form);
            const clienteId = formData.get('cliente_id');

            if (!clienteId) {
                this.showMessage('Cliente não identificado. Salve os dados gerais primeiro.', 'error');
                return;
            }

            // Loading state
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';

            fetch('/clientes/add-contato', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fecha modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalContato'));
                        if (modal) modal.hide();

                        // Recarrega a página para mostrar novo contato
                        window.location.reload();
                    } else {
                        throw new Error(data.error || 'Erro ao salvar contato');
                    }
                })
                .catch(error => {
                    console.error('Erro ao adicionar contato:', error);
                    this.showMessage('Erro ao adicionar contato: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = 'Salvar Contato';

                    // Limpa formulário
                    form.reset();
                });
        }

        removeContact(contactId) {
            if (!confirm('Tem certeza que deseja remover este contato?')) {
                return;
            }

            fetch('/clientes/remove-contato', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${contactId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove linha da tabela
                        const row = document.getElementById(`contato-${contactId}`);
                        if (row) {
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(-20px)';

                            setTimeout(() => {
                                row.remove();

                                // Verifica se não há mais contatos
                                const tbody = document.querySelector('#tabelaContatos tbody');
                                if (tbody && tbody.children.length === 0) {
                                    tbody.innerHTML = `
                                    <tr class="empty-row text-center">
                                        <td colspan="4" class="py-5 text-muted small">
                                            Nenhum contato cadastrado para este cliente.
                                        </td>
                                    </tr>
                                `;
                                }
                            }, 300);
                        }

                        this.showMessage('Contato removido com sucesso!', 'success');
                    } else {
                        throw new Error(data.error || 'Erro ao remover contato');
                    }
                })
                .catch(error => {
                    console.error('Erro ao remover contato:', error);
                    this.showMessage('Erro ao remover contato: ' + error.message, 'error');
                });
        }

        setupFormSubmission() {
            const form = document.getElementById('clienteFormGeral');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitForm(form);
            });
        }

        submitForm(form) {
            if (this.isSubmitting) return;

            this.isSubmitting = true;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');

            // Loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
            }

            const action = this.options.isEdit ?
                `/clientes/update/${this.options.clientId}` :
                '/clientes/store';

            fetch(action, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    // Verifica se é redirect ou JSON
                    if (response.redirected) {
                        window.location.href = response.url;
                        return;
                    }

                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch {
                            // Se não for JSON, provavelmente é redirect
                            window.location.reload();
                            return null;
                        }
                    });
                })
                .then(data => {
                    if (!data) return; // Redirect handled

                    if (data.success) {
                        this.showMessage('Dados salvos com sucesso!', 'success');

                        // Se for novo cliente, redireciona para edição com aba de contatos
                        if (!this.options.isEdit && data.client_id) {
                            setTimeout(() => {
                                window.location.href = `/clientes/edit/${data.client_id}?tab=contatos`;
                            }, 1500);
                        } else {
                            // Se for edição, desbloqueia aba de contatos
                            this.unlockContactsTab();
                        }
                    } else {
                        throw new Error(data.error || 'Erro ao salvar dados');
                    }
                })
                .catch(error => {
                    console.error('Erro no envio:', error);
                    this.showMessage('Erro ao salvar: ' + error.message, 'error');
                })
                .finally(() => {
                    this.isSubmitting = false;

                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = this.options.isEdit ?
                            '<i class="fas fa-save me-2"></i>Salvar e Continuar' :
                            '<i class="fas fa-save me-2"></i>Salvar e Próxima Etapa';
                    }
                });
        }

        setupValidation() {
            // Validação de CPF/CNPJ
            const cpfCnpjInput = document.getElementById('cpf_cnpj');
            if (cpfCnpjInput) {
                cpfCnpjInput.addEventListener('blur', () => {
                    this.validateCpfCnpj(cpfCnpjInput);
                });
            }

            // Validação de email
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('blur', () => {
                    this.validateEmail(emailInput);
                });
            }
        }

        validateCpfCnpj(input) {
            const value = input.value.replace(/\D/g, '');
            const isValid = value.length === 11 || value.length === 14;

            this.toggleValidation(input, isValid);

            if (!isValid) {
                this.showFieldError(input, 'CPF ou CNPJ incompleto');
            } else {
                this.hideFieldError(input);
            }

            return isValid;
        }

        validateEmail(input) {
            const value = input.value.trim();
            const isValid = !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

            this.toggleValidation(input, isValid);

            if (!isValid) {
                this.showFieldError(input, 'Email inválido');
            } else {
                this.hideFieldError(input);
            }

            return isValid;
        }

        validateGeneralTab() {
            const cpfCnpjInput = document.getElementById('cpf_cnpj');
            const razaoSocialInput = document.getElementById('razao_social');

            let isValid = true;

            if (!this.validateCpfCnpj(cpfCnpjInput)) {
                isValid = false;
            }

            if (!razaoSocialInput || !razaoSocialInput.value.trim()) {
                this.showFieldError(razaoSocialInput, 'Razão Social é obrigatória');
                isValid = false;
            }

            if (!isValid) {
                this.showMessage('Preencha os campos obrigatórios antes de continuar', 'error');
            }

            return isValid;
        }

        toggleValidation(input, isValid) {
            if (isValid) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
        }

        showFieldError(input, message) {
            // Remove mensagens anteriores
            this.hideFieldError(input);

            // Adiciona classe de erro
            input.classList.add('is-invalid');

            // Cria mensagem de erro
            const feedback = document.createElement('div');
            feedback.className = 'form-feedback invalid';
            feedback.textContent = message;

            // Insere após o input
            input.parentNode.insertBefore(feedback, input.nextSibling);
        }

        hideFieldError(input) {
            input.classList.remove('is-invalid');

            // Remove mensagens de erro
            const feedback = input.parentNode.querySelector('.form-feedback.invalid');
            if (feedback) {
                feedback.remove();
            }
        }

        unlockContactsTab() {
            if (this.formTabs) {
                this.formTabs.unlockTab(1);
            }
        }

        showMessage(message, type = 'info') {
            // Remove mensagens anteriores
            this.removeMessages();

            // Cria mensagem
            const messageEl = document.createElement('div');
            messageEl.className = `form-message ${type}`;
            messageEl.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;

            // Insere no topo do formulário
            const content = this.container.querySelector('.form-content');
            if (content) {
                content.insertBefore(messageEl, content.firstChild);

                // Remove automaticamente
                setTimeout(() => {
                    if (messageEl.parentNode) {
                        messageEl.remove();
                    }
                }, 5000);
            }
        }

        removeMessages() {
            const messages = this.container.querySelectorAll('.form-message');
            messages.forEach(msg => msg.remove());
        }

        destroy() {
            if (this.formTabs) {
                this.formTabs.destroy();
            }

            this.removeMessages();
            this.container = null;
        }
    };

    // Função global para remover contatos (compatibilidade com código existente)
    window.removerContato = function (id) {
        const form = window.clientesFormInstance;
        if (form) {
            form.removeContact(id);
        }
    };

    // Inicialização automática
    document.addEventListener('DOMContentLoaded', () => {
        // Verifica se estamos na página de clientes
        const formContainer = document.querySelector('.form-container');
        if (formContainer) {
            // Extrai configurações da página
            const isEdit = formContainer.hasAttribute('data-is-edit');
            const clientId = formContainer.getAttribute('data-client-id');
            const activeTab = new URLSearchParams(window.location.search).get('tab') || 'geral';

            // Inicializa o formulário
            window.clientesFormInstance = new window.ClientesForm(formContainer, {
                isEdit: isEdit,
                clientId: clientId,
                activeTab: activeTab
            });
        }
    });

    // Fecha a proteção de namespace
} // if (!window.ClientesForm)
