<?php require_once dirname(__DIR__) . '/layout/erp_header.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?php echo $title; ?></h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $title; ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="mb-4">Funcionalidade em Desenvolvimento</h1>
                    <p class="lead">Estamos trabalhando para trazer esta funcionalidade para você em breve.</p>
                    <i class="fas fa-cogs fa-5x text-muted mt-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
