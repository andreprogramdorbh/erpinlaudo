<?php
use App\Core\Form;
require_once dirname(__DIR__) . '/layout/erp_header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">
                        <?php echo $cliente ? 'Editar Cliente' : 'Novo Cliente'; ?>
                    </h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="/clientes">Clientes</a></li>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo $cliente ? 'Editar' : 'Novo'; ?>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <?php Form::start('formCliente'); ?>
        <?php Form::rowStart(); ?>
        <?php Form::colStart('8'); ?>

        <!-- Dados Básicos -->
        <?php Form::cardStart('Dados Básicos'); ?>
        <?php Form::rowStart(); ?>
        <?php Form::colStart('4'); ?>
        <?php Form::select('tipo', 'Tipo de Cliente', [
            'PJ' => 'Pessoa Jurídica (PJ)',
            'PF' => 'Pessoa Física (PF)'
        ], $cliente->tipo ?? 'PJ', ['required' => true]); ?>
        <?php Form::colEnd(); ?>

        <?php Form::colStart('8'); ?>
        <?php Form::input('cpf_cnpj', 'CPF/CNPJ', 'text', $cliente->cpf_cnpj ?? '', [
            'required' => true,
            'placeholder' => '00.000.000/0000-00',
            'append' => '<button class="btn btn-outline-primary" type="button" id="btnBuscarCnpj"><i class="fas fa-search"></i> Buscar</button>'
        ]); ?>
        <?php Form::colEnd(); ?>
        <?php Form::rowEnd(); ?>

        <?php Form::rowStart(); ?>
        <?php Form::colStart('12'); ?>
        <?php Form::input('razao_social', 'Razão Social / Nome Completo', 'text', $cliente->razao_social ?? '', ['required' => true]); ?>
        <?php Form::colEnd(); ?>
        <?php Form::rowEnd(); ?>

        <?php Form::rowStart(); ?>
        <?php Form::colStart('6'); ?>
        <?php Form::input('nome_fantasia', 'Nome Fantasia / Apelido', 'text', $cliente->nome_fantasia ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('6'); ?>
        <?php Form::input('email', 'E-mail Principal', 'email', $cliente->email ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::rowEnd(); ?>

        <?php Form::rowStart(); ?>
        <?php Form::colStart('6'); ?>
        <?php Form::input('website', 'Website', 'url', $cliente->website ?? '', ['placeholder' => 'https://']); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('3'); ?>
        <?php Form::input('telefone', 'Telefone', 'text', $cliente->telefone ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('3'); ?>
        <?php Form::input('celular', 'Celular', 'text', $cliente->celular ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::rowEnd(); ?>
        <?php Form::cardEnd(); ?>

        <!-- Endereço -->
        <?php Form::cardStart('Endereço'); ?>
        <?php Form::rowStart(); ?>
        <?php Form::colStart('3'); ?>
        <?php Form::input('cep', 'CEP', 'text', $cliente->cep ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('7'); ?>
        <?php Form::input('endereco', 'Logradouro', 'text', $cliente->endereco ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('2'); ?>
        <?php Form::input('numero', 'Número', 'text', $cliente->numero ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::rowEnd(); ?>

        <?php Form::rowStart(); ?>
        <?php Form::colStart('4'); ?>
        <?php Form::input('complemento', 'Complemento', 'text', $cliente->complemento ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('3'); ?>
        <?php Form::input('bairro', 'Bairro', 'text', $cliente->bairro ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('3'); ?>
        <?php Form::input('cidade', 'Cidade', 'text', $cliente->cidade ?? ''); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('2'); ?>
        <?php Form::input('estado', 'Estado (UF)', 'text', $cliente->estado ?? '', ['maxlength' => 2]); ?>
        <?php Form::colEnd(); ?>
        <?php Form::rowEnd(); ?>
        <?php Form::cardEnd(); ?>

        <!-- Redes Sociais -->
        <?php Form::cardStart('Redes Sociais'); ?>
        <?php Form::rowStart(); ?>
        <?php Form::colStart('4'); ?>
        <?php Form::input('instagram', 'Instagram', 'text', $cliente->instagram ?? '', [
            'placeholder' => '@usuario',
            'prepend' => '<i class="fab fa-instagram"></i>'
        ]); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('4'); ?>
        <?php Form::input('tiktok', 'TikTok', 'text', $cliente->tiktok ?? '', [
            'placeholder' => '@usuario',
            'prepend' => '<i class="fab fa-tiktok"></i>'
        ]); ?>
        <?php Form::colEnd(); ?>
        <?php Form::colStart('4'); ?>
        <?php Form::input('facebook', 'Facebook', 'text', $cliente->facebook ?? '', [
            'placeholder' => 'link da página',
            'prepend' => '<i class="fab fa-facebook"></i>'
        ]); ?>
        <?php Form::colEnd(); ?>
        <?php Form::rowEnd(); ?>
        <?php Form::cardEnd(); ?>

        <!-- Contatos -->
        <?php
        $btnAdicionar = '<button type="button" class="btn btn-sm btn-success" id="btnAdicionarContato"><i class="fas fa-plus"></i> Adicionar Contato</button>';
        Form::cardStart('Contatos', 'fas fa-users');
        ?>
        <div class="table-responsive">
            <table class="table table-bordered" id="tabelaContatos">
                <thead>
                    <tr>
                        <th>Nome <span class="text-danger">*</span></th>
                        <th>Cargo/Depto</th>
                        <th>E-mail</th>
                        <th>Celular</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="listaContatos">
                    <?php if (!empty($contatos)): ?>
                        <?php foreach ($contatos as $index => $contato): ?>
                            <tr class="contato-row">
                                <td><?php Form::input("contatos[{$index}][nome]", '', 'text', $contato->nome, ['class' => 'form-control-sm', 'required' => true]); ?>
                                </td>
                                <td><?php Form::input("contatos[{$index}][cargo]", '', 'text', $contato->cargo, ['class' => 'form-control-sm', 'placeholder' => 'Cargo']); ?>
                                </td>
                                <td><?php Form::input("contatos[{$index}][email]", '', 'email', $contato->email, ['class' => 'form-control-sm']); ?>
                                </td>
                                <td><?php Form::input("contatos[{$index}][celular]", '', 'text', $contato->celular, ['class' => 'form-control-sm']); ?>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-danger btnRemoverContato"><i
                                            class="fas fa-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif (!$cliente): ?>
                        <tr class="contato-row">
                            <td><?php Form::input("contatos[0][nome]", '', 'text', '', ['class' => 'form-control-sm', 'required' => true]); ?>
                            </td>
                            <td><?php Form::input("contatos[0][cargo]", '', 'text', '', ['class' => 'form-control-sm', 'placeholder' => 'Cargo']); ?>
                            </td>
                            <td><?php Form::input("contatos[0][email]", '', 'email', '', ['class' => 'form-control-sm']); ?>
                            </td>
                            <td><?php Form::input("contatos[0][celular]", '', 'text', '', ['class' => 'form-control-sm']); ?>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-danger btnRemoverContato"><i
                                        class="fas fa-trash"></i></button></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php Form::cardEnd(); ?>
        <?php Form::colEnd(); ?>

        <?php Form::colStart('4'); ?>
        <?php Form::cardStart('Configurações'); ?>
        <?php if ($cliente): ?>
            <?php Form::select('status', 'Status', [
                'ativo' => 'Ativo',
                'inativo' => 'Inativo',
                'suspenso' => 'Suspenso'
            ], $cliente->status); ?>
        <?php endif; ?>

        <?php Form::input('cnae_principal', 'CNAE Principal', 'text', $cliente->cnae_principal ?? '', ['readonly' => true]); ?>

        <?php Form::textarea('descricao_cnae', 'Descrição CNAE', $cliente->descricao_cnae ?? '', ['readonly' => true, 'rows' => 3]); ?>

        <hr>

        <div class="d-grid gap-2">
            <?php Form::button($cliente ? 'Salvar Alterações' : 'Cadastrar Cliente', 'submit', 'btn-primary btn-block', 'fas fa-save'); ?>
            <a href="/clientes" class="btn btn-secondary btn-block">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
        <?php Form::cardEnd(); ?>
        <?php Form::colEnd(); ?>
        <?php Form::rowEnd(); ?>
        <?php Form::end(); ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>