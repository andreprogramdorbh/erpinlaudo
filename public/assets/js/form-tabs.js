/**
 * ERP InLaudo - Sistema de Abas para Formulários
 * JavaScript modular para controle de abas reutilizáveis
 * Versão: 1.0.0
 */

// Proteção de namespace - evita redeclaração (sem criar bindings globais)
if (!window.FormTabs) {
    window.FormTabs = class FormTabs {
        constructor(container, options = {}) {
            this.container = typeof container === 'string' ? document.querySelector(container) : container;
            this.options = {
                activeTab: options.activeTab || 0,
                onTabChange: options.onTabChange || null,
                onTabLocked: options.onTabLocked || null,
                allowLockedTabs: options.allowLockedTabs || false,
                saveState: options.saveState || false,
                animationDuration: options.animationDuration || 300,
                ...options
            };

            this.tabs = [];
            this.panels = [];
            this.activeTabIndex = this.options.activeTab;
            this.isAnimating = false;

            this.init();
        }

        init() {
            if (!this.container) {
                console.error('FormTabs: Container não encontrado');
                return;
            }

            this.findElements();
            this.bindEvents();
            this.activateTab(this.activeTabIndex);
            this.restoreState();
        }

        activateTab(index) {
            if (index < 0 || index >= this.tabs.length) {
                index = 0;
            }

            this.tabs.forEach((tab) => {
                tab.button.classList.remove('active');
                if (tab.panel && tab.panel.classList) {
                    tab.panel.classList.remove('active');
                }
            });

            const newTab = this.tabs[index];
            if (!newTab) return;

            newTab.button.classList.add('active');
            if (newTab.panel) {
                newTab.panel.classList.add('active');
            }

            this.activeTabIndex = index;

            if (this.options.saveState) {
                this.saveState();
            }
        }

        findElements() {
            // Encontra as abas
            const tabButtons = this.container.querySelectorAll('.form-tab-button');
            tabButtons.forEach((button, index) => {
                this.tabs.push({
                    button: button,
                    index: index,
                    id: button.getAttribute('data-tab') || `tab-${index}`,
                    locked: button.hasAttribute('data-locked') || button.disabled,
                    panel: null
                });
            });

            // Encontra os painéis
            const panels = this.container.querySelectorAll('.form-panel');
            panels.forEach((panel, index) => {
                if (this.tabs[index]) {
                    this.tabs[index].panel = panel;
                    this.panels.push(panel);
                }
            });
        }

        bindEvents() {
            this.tabs.forEach((tab, index) => {
                tab.button.addEventListener('click', (e) => {
                    e.preventDefault();

                    if (tab.locked && !this.options.allowLockedTabs) {
                        this.handleLockedTab(tab);
                        return;
                    }

                    if (!this.isAnimating && index !== this.activeTabIndex) {
                        this.switchToTab(index);
                    }
                });
            });

            // Suporte a navegação por teclado
            this.container.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.navigateWithKeyboard(e.key === 'ArrowRight' ? 1 : -1);
                }
            });
        }

        switchToTab(index) {
            if (index < 0 || index >= this.tabs.length) return;

            const currentTab = this.tabs[this.activeTabIndex];
            const newTab = this.tabs[index];

            // Verifica se a nova aba está bloqueada
            if (newTab.locked && !this.options.allowLockedTabs) {
                this.handleLockedTab(newTab);
                return;
            }

            this.isAnimating = true;

            // Callback antes da mudança
            if (this.options.onTabChange) {
                const canChange = this.options.onTabChange(this.activeTabIndex, index, currentTab, newTab);
                if (canChange === false) {
                    this.isAnimating = false;
                    return;
                }
            }

            // Remove classe active da aba atual
            if (currentTab.button) currentTab.button.classList.remove('active');
            if (currentTab.panel && currentTab.panel.classList) currentTab.panel.classList.remove('active');

            // Adiciona classe active na nova aba
            if (newTab.button) newTab.button.classList.add('active');
            if (newTab.panel && newTab.panel.classList) newTab.panel.classList.add('active');

            // Atualiza índice
            this.activeTabIndex = index;

            // Salva estado
            if (this.options.saveState) {
                this.saveState();
            }

            // Foco no primeiro campo do novo painel
            this.focusFirstInput(newTab.panel);

            // Animação
            setTimeout(() => {
                this.isAnimating = false;
            }, this.options.animationDuration);
        }

        navigateWithKeyboard(direction) {
            let newIndex = this.activeTabIndex + direction;

            // Encontra a próxima aba não bloqueada
            while (newIndex >= 0 && newIndex < this.tabs.length) {
                const tab = this.tabs[newIndex];
                if (!tab.locked || this.options.allowLockedTabs) {
                    this.switchToTab(newIndex);
                    break;
                }
                newIndex += direction;
            }
        }

        handleLockedTab(tab) {
            // Feedback visual
            tab.button.style.animation = 'shake 0.3s';
            setTimeout(() => {
                tab.button.style.animation = '';
            }, 300);

            // Callback
            if (this.options.onTabLocked) {
                this.options.onTabLocked(tab);
            }

            // Mensagem padrão
            this.showLockedMessage(tab);
        }

        showLockedMessage(tab) {
            const message = tab.button.getAttribute('data-locked-message') ||
                'Esta aba está bloqueada. Complete as etapas anteriores para desbloquear.';

            // Remove mensagens anteriores
            this.removeMessages();

            // Cria mensagem
            const messageEl = document.createElement('div');
            messageEl.className = 'form-message warning';
            messageEl.innerHTML = `
            <i class="fas fa-lock"></i>
            <span>${message}</span>
        `;

            // Insere antes do conteúdo
            const content = this.container.querySelector('.form-content');
            if (content) {
                content.insertBefore(messageEl, content.firstChild);

                // Remove automaticamente após 5 segundos
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

        focusFirstInput(panel) {
            if (!panel) return;

            const firstInput = panel.querySelector('input, select, textarea, button');
            if (firstInput && firstInput.type !== 'hidden') {
                setTimeout(() => {
                    firstInput.focus();
                }, 100);
            }
        }

        lockTab(index, message = '') {
            if (this.tabs[index]) {
                this.tabs[index].locked = true;
                this.tabs[index].button.disabled = true;
                this.tabs[index].button.setAttribute('data-locked', 'true');

                if (message) {
                    this.tabs[index].button.setAttribute('data-locked-message', message);
                }

                // Adiciona ícone de cadeado
                if (!this.tabs[index].button.querySelector('.lock-icon')) {
                    const lockIcon = document.createElement('i');
                    lockIcon.className = 'fas fa-lock lock-icon';
                    lockIcon.style.marginLeft = 'auto';
                    this.tabs[index].button.appendChild(lockIcon);
                }
            }
        }

        unlockTab(index) {
            if (this.tabs[index]) {
                this.tabs[index].locked = false;
                this.tabs[index].button.disabled = false;
                this.tabs[index].button.removeAttribute('data-locked');
                this.tabs[index].button.removeAttribute('data-locked-message');

                // Remove ícone de cadeado
                const lockIcon = this.tabs[index].button.querySelector('.lock-icon');
                if (lockIcon) {
                    lockIcon.remove();
                }
            }
        }

        getActiveTab() {
            return this.tabs[this.activeTabIndex];
        }

        getActiveTabIndex() {
            return this.activeTabIndex;
        }

        getTab(index) {
            return this.tabs[index];
        }

        getTotalTabs() {
            return this.tabs.length;
        }

        saveState() {
            const state = {
                activeTab: this.activeTabIndex,
                url: window.location.pathname
            };

            localStorage.setItem('formTabsState', JSON.stringify(state));
        }

        restoreState() {
            if (!this.options.saveState) return;

            try {
                const saved = localStorage.getItem('formTabsState');
                if (saved) {
                    const state = JSON.parse(saved);

                    // Verifica se estamos na mesma página
                    if (state.url === window.location.pathname) {
                        this.switchToTab(state.activeTab);
                    }
                }
            } catch (e) {
                console.warn('FormTabs: Erro ao restaurar estado', e);
            }
        }

        destroy() {
            // Remove eventos
            this.tabs.forEach(tab => {
                tab.button.removeEventListener('click', () => { });
            });

            // Limpa referências
            this.tabs = [];
            this.panels = [];
            this.container = null;
        }
    };

    // Animação de shake para abas bloqueadas (injetar apenas uma vez)
    if (!document.getElementById('form-tabs-shake-style')) {
        const style = document.createElement('style');
        style.id = 'form-tabs-shake-style';
        style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    `;
        document.head.appendChild(style);
    }

    // Inicialização automática (registrar apenas uma vez)
    if (!window.__formTabsAutoInit) {
        window.__formTabsAutoInit = true;
        document.addEventListener('DOMContentLoaded', () => {
            const containers = document.querySelectorAll('.form-tabs-container:not(.form-tabs-initialized)');

            containers.forEach((container) => {
                if (container.formTabs) {
                    container.classList.add('form-tabs-initialized');
                    return;
                }

                const options = {
                    activeTab: parseInt(container.getAttribute('data-active-tab')) || 0,
                    saveState: container.getAttribute('data-save-state') === 'true',
                    allowLockedTabs: container.getAttribute('data-allow-locked') === 'true'
                };

                container.formTabs = new window.FormTabs(container, options);
                container.classList.add('form-tabs-initialized');
            });
        });
    }

    // Helpers globais (não sobrescrever se já existir)
    if (!window.lockFormTab) {
        window.lockFormTab = (container, index, message) => {
            const containerEl = typeof container === 'string' ? document.querySelector(container) : container;
            if (containerEl && containerEl.formTabs) {
                containerEl.formTabs.lockTab(index, message);
            }
        };
    }

    if (!window.unlockFormTab) {
        window.unlockFormTab = (container, index) => {
            const containerEl = typeof container === 'string' ? document.querySelector(container) : container;
            if (containerEl && containerEl.formTabs) {
                containerEl.formTabs.unlockTab(index);
            }
        };
    }

    if (!window.switchToFormTab) {
        window.switchToFormTab = (container, index) => {
            const containerEl = typeof container === 'string' ? document.querySelector(container) : container;
            if (containerEl && containerEl.formTabs) {
                containerEl.formTabs.switchToTab(index);
            }
        };
    }
} // if (!window.FormTabs)
