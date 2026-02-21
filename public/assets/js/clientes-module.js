/**
 * Módulo de Clientes - Frontend Business Logic
 * Gerencia máscaras, consulta de CNPJ e comportamento do formulário.
 */

$(document).ready(function () {
    const $tipoSelect = $('#tipo_cliente');
    const $inputDoc = $('#cpf_cnpj');
    const $labelDoc = $('#label_documento');
    const $btnConsulta = $('#btn_consulta');
    const $statusConsulta = $('#status_consulta');

    // Inicializa Máscaras
    function applyMasks() {
        if ($tipoSelect.val() === 'PJ') {
            $inputDoc.mask('00.000.000/0000-00');
            $labelDoc.text('CNPJ');
            $inputDoc.attr('placeholder', '00.000.000/0000-00');
            $btnConsulta.show();
        } else {
            $inputDoc.mask('000.000.000-00');
            $labelDoc.text('CPF');
            $inputDoc.attr('placeholder', '000.000.000-00');
            $btnConsulta.hide();
        }
    }

    $tipoSelect.on('change', applyMasks);
    applyMasks(); // Initial call

    $('#telefone, #modal_telefone').mask('(00) 0000-0000');
    $('#celular, #modal_celular').mask('(00) 00000-0000');
    $('#cep').mask('00000-000');

    // Máscara dinâmica para o valor do contato
    const $tipoContatoSel = $('#tipo_contato_sel');
    const $valorContato = $('#valor_contato');

    function updateValorMask() {
        $valorContato.unmask();
        const tipo = $tipoContatoSel.val();
        if (tipo === 'Celular') {
            $valorContato.mask('(00) 00000-0000');
            $valorContato.attr('placeholder', '(00) 00000-0000');
        } else if (tipo === 'Email') {
            $valorContato.attr('placeholder', 'exemplo@email.com');
        } else {
            $valorContato.mask('(00) 0000-0000');
            $valorContato.attr('placeholder', '(00) 0000-0000');
        }
    }

    $tipoContatoSel.on('change', updateValorMask);
    updateValorMask();

    // Lógica de Gestão de Contatos (AJAX)
    const $btnSalvarContato = $('#btnSalvarContato');
    const $formAddContato = $('#formAddContato');

    $btnSalvarContato.on('click', function () {
        const formData = $formAddContato.serialize();

        $btnSalvarContato.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Salvando...');

        $.ajax({
            url: '/clientes/add-contato',
            method: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    location.reload();
                }
            },
            error: function (xhr) {
                const err = xhr.responseJSON ? xhr.responseJSON.error : 'Erro desconhecido';
                Swal.fire('Erro', 'Não foi possível salvar o contato: ' + err, 'error');
            },
            complete: function () {
                $btnSalvarContato.prop('disabled', false).html('Salvar Contato');
            }
        });
    });

    window.removerContato = function (id) {
        Swal.fire({
            title: 'Remover contato?',
            text: "Esta ação não pode ser desfeita.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, remover',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('/clientes/remove-contato', { id: id }, function (response) {
                    if (response.success) {
                        $(`#contato-${id}`).fadeOut(300, function () {
                            $(this).remove();
                            if ($('#tabelaContatos tbody tr').length === 0) {
                                $('#tabelaContatos tbody').append('<tr class="empty-row text-center"><td colspan="4" class="py-5 text-muted small">Nenhum contato cadastrado.</td></tr>');
                            }
                        });
                    }
                });
            }
        });
    }

    // Toggle de cor do card no foco
    $('.form-control, .form-select').on('focus', function () {
        $(this).closest('.col-md-6, .col-md-4, .col-md-8, .col-md-3, .col-md-2, .col-md-5').find('.form-label').addClass('text-primary fw-bold text-uppercase small');
    }).on('blur', function () {
        $(this).closest('.col-md-6, .col-md-4, .col-md-8, .col-md-3, .col-md-2, .col-md-5').find('.form-label').removeClass('text-primary fw-bold text-uppercase small');
    });
});
