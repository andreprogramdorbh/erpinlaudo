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
            this.setupCepSearch();
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
                        return response.json().then(err => {
                            throw new Error(err.erro || `Erro HTTP ${response.status}`);
                        });
                    }
                    return response.json();
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

        // -----------------------------------------------------------------
        // Busca de CEP — dispara ao sair do campo (blur) ou ao clicar no botão
        // -----------------------------------------------------------------
        setupCepSearch() {
            const cepInput = document.getElementById('cep');
            if (!cepInput) return;

            // Cria o botão de busca ao lado do campo CEP
            const btnBuscarCep = document.createElement('button');
            btnBuscarCep.type = 'button';
            btnBuscarCep.id = 'btn_buscar_cep';
            btnBuscarCep.className = 'btn btn-outline-secondary btn-sm ms-2';
            btnBuscarCep.title = 'Buscar endereço pelo CEP';
            btnBuscarCep.innerHTML = '<i class="fas fa-search"></i> Buscar CEP';

            // Envolve o input em um grupo para posicionar o botão
            const wrapper = cepInput.parentNode;
            const inputGroup = document.createElement('div');
            inputGroup.className = 'd-flex align-items-center gap-2';
            wrapper.insertBefore(inputGroup, cepInput);
            inputGroup.appendChild(cepInput);
            inputGroup.appendChild(btnBuscarCep);

            // Evento: clique no botão
            btnBuscarCep.addEventListener('click', () => this.searchCep());

            // Evento: ao sair do campo com CEP completo
            cepInput.addEventListener('blur', () => {
                const cep = cepInput.value.replace(/\D/g, '');
                if (cep.length === 8) this.searchCep();
            });

            // Evento: ao digitar — dispara quando completa 8 dígitos
            cepInput.addEventListener('input', () => {
                const cep = cepInput.value.replace(/\D/g, '');
                if (cep.length === 8) this.searchCep();
            });
        }

        searchCep() {
            const cepInput = document.getElementById('cep');
            const btnBuscarCep = document.getElementById('btn_buscar_cep');
            if (!cepInput) return;

            const cep = cepInput.value.replace(/\D/g, '');
            if (cep.length !== 8) return;

            // Estado de carregamento
            const originalHtml = btnBuscarCep ? btnBuscarCep.innerHTML : '';
            if (btnBuscarCep) {
                btnBuscarCep.disabled = true;
                btnBuscarCep.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Buscando...';
            }

            fetch(`/clientes/buscar-cep?cep=${cep}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.erro || `Erro HTTP ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.erro) throw new Error(data.erro);
                    this.fillCepData(data);
                })
                .catch(error => {
                    console.error('Erro na busca de CEP:', error);
                    // Toast não-bloqueante para não atrapalhar o preenchimento manual
                    this.showToast('CEP não encontrado. Preencha o endereço manualmente.', 'warning');
                })
                .finally(() => {
                    if (btnBuscarCep) {
                        btnBuscarCep.disabled = false;
                        btnBuscarCep.innerHTML = originalHtml;
                    }
                });
        }

        fillCepData(data) {
            const campos = {
                'endereco':    data.endereco    || '',
                'complemento': data.complemento || '',
                'bairro':      data.bairro      || '',
                'cidade':      data.cidade      || '',
            };

            Object.entries(campos).forEach(([campo, valor]) => {
                const input = document.getElementById(campo);
                if (input) {
                    // Só preenche se o campo estiver vazio (não sobrescreve dados já digitados)
                    if (!input.value.trim() && valor) {
                        input.value = valor;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });

            // Seleciona o estado no <select>
            if (data.estado) {
                const estadoSelect = document.getElementById('estado');
                if (estadoSelect && !estadoSelect.value) {
                    estadoSelect.value = data.estado;
                    estadoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            this.showToast('Endereço preenchido automaticamente!', 'success');
        }

        showToast(message, type = 'info') {
            // Remove toasts anteriores para evitar empilhamento
            document.querySelectorAll('.erp-cep-toast').forEach(el => el.remove());

            const icons  = { success: 'check-circle', warning: 'exclamation-triangle', error: 'times-circle', info: 'info-circle' };
            const colors = { success: '#198754', warning: '#e6a817', error: '#dc3545', info: '#0dcaf0' };

            const toast = document.createElement('div');
            toast.className = 'erp-cep-toast';
            toast.style.cssText = [
                'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
                'background:#fff', 'border-radius:8px', 'padding:12px 18px',
                `border-left:4px solid ${colors[type] || colors.info}`,
                'box-shadow:0 4px 16px rgba(0,0,0,.15)',
                'display:flex', 'align-items:center', 'gap:10px',
                'font-size:14px', 'max-width:360px', 'transition:opacity .3s ease'
            ].join(';');
            toast.innerHTML = `<i class="fas fa-${icons[type] || icons.info}" style="color:${colors[type]}"></i><span>${message}</span>`;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        setupContactManagement() {
            // Botão Salvar Contato (novo + edição)
            const btnSalvar = document.getElementById('btnSalvarContato');
            if (btnSalvar) {
                btnSalvar.addEventListener('click', () => {
                    const contatoId = document.getElementById('contato_id')?.value;
                    if (contatoId && contatoId !== '0' && contatoId !== '') {
                        this.updateContact(parseInt(contatoId));
                    } else {
                        this.addContact();
                    }
                });
            }

            // Expor editContact globalmente para uso inline no PHP
            window.editContact = (id) => this.loadContactForEdit(id);
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

        // ---------------------------------------------------------------
        // Adicionar contato
        // ---------------------------------------------------------------
        addContact() {
            const form = document.getElementById('formAddContato');
            const btn  = document.getElementById('btnSalvarContato');
            if (!form || !btn) return;

            const nome = document.getElementById('nome_contato')?.value.trim();
            if (!nome) {
                this.showToast('O nome do contato é obrigatório.', 'warning');
                document.getElementById('nome_contato')?.focus();
                return;
            }

            const formData = new FormData(form);
            const clienteId = formData.get('cliente_id');
            if (!clienteId || clienteId === '0') {
                this.showToast('Salve os dados gerais do cliente antes de adicionar contatos.', 'warning');
                return;
            }

            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';

            fetch('/clientes/add-contato', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Erro ao salvar contato.');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalContato'));
                    if (modal) modal.hide();
                    window.location.reload();
                })
                .catch(err => {
                    console.error('[addContact]', err);
                    this.showToast('Erro ao adicionar contato: ' + err.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
        }

        // ---------------------------------------------------------------
        // Carregar contato para edição (preenche o modal)
        // ---------------------------------------------------------------
        loadContactForEdit(contactId) {
            fetch(`/clientes/get-contato?id=${contactId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Contato não encontrado.');

                    const c = data.contato;
                    document.getElementById('contato_id').value           = c.id;
                    document.getElementById('nome_contato').value         = c.nome         || '';
                    document.getElementById('departamento_contato').value = c.departamento || '';
                    document.getElementById('email_contato').value        = c.email        || '';
                    document.getElementById('celular_contato').value      = c.celular      || '';
                    document.getElementById('telefone_contato').value     = c.telefone     || '';
                    document.getElementById('observacoes_contato').value  = c.observacoes  || '';

                    document.getElementById('modalTitle').textContent = 'Editar Contato';
                    const btn = document.getElementById('btnSalvarContato');
                    if (btn) btn.innerHTML = '<i class="fas fa-save me-1"></i> Atualizar Contato';

                    const modal = new bootstrap.Modal(document.getElementById('modalContato'));
                    modal.show();
                })
                .catch(err => {
                    console.error('[loadContactForEdit]', err);
                    this.showToast('Não foi possível carregar os dados do contato.', 'error');
                });
        }

        // ---------------------------------------------------------------
        // Atualizar contato existente
        // ---------------------------------------------------------------
        updateContact(contactId) {
            const form = document.getElementById('formAddContato');
            const btn  = document.getElementById('btnSalvarContato');
            if (!form || !btn) return;

            const nome = document.getElementById('nome_contato')?.value.trim();
            if (!nome) {
                this.showToast('O nome do contato é obrigatório.', 'warning');
                document.getElementById('nome_contato')?.focus();
                return;
            }

            const formData = new FormData(form);
            formData.set('contato_id', contactId);

            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Atualizando...';

            fetch('/clientes/update-contato', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Erro ao atualizar contato.');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalContato'));
                    if (modal) modal.hide();
                    window.location.reload();
                })
                .catch(err => {
                    console.error('[updateContact]', err);
                    this.showToast('Erro ao atualizar contato: ' + err.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
        }

        // ---------------------------------------------------------------
        // Remover contato
        // ---------------------------------------------------------------
        removeContact(contactId) {
            if (!confirm('Tem certeza que deseja remover este contato?')) return;

            fetch('/clientes/remove-contato', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${contactId}`
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Erro ao remover contato.');

                    const row = document.getElementById(`contato-${contactId}`);
                    if (row) {
                        row.style.transition = 'opacity .3s, transform .3s';
                        row.style.opacity    = '0';
                        row.style.transform  = 'translateX(-20px)';
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.querySelector('#tabelaContatos tbody');
                            if (tbody && tbody.querySelectorAll('tr:not(.empty-row)').length === 0) {
                                tbody.innerHTML = `<tr class="empty-row">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <div class="empty-state">
                                            <i class="fas fa-address-book fa-3x mb-3"></i>
                                            <p class="mb-0">Nenhum contato cadastrado.</p>
                                        </div>
                                    </td></tr>`;
                            }
                        }, 300);
                    }
                    this.showToast('Contato removido com sucesso!', 'success');
                })
                .catch(err => {
                    console.error('[removeContact]', err);
                    this.showToast('Erro ao remover contato: ' + err.message, 'error');
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
                '/clientes';

            fetch(action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
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
            const isEdit = formContainer.getAttribute('data-is-edit') === 'true';
            const clientId = formContainer.getAttribute('data-client-id') || null;
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
