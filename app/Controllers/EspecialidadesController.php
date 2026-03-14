<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Core\Audit\AuditLogger;
use App\Models\Especialidade;

class EspecialidadesController extends Controller
{
    private const BASE_ROUTE = '/especialidades';

    private Especialidade $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new Especialidade();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $filtros = [
                'pesquisa' => trim((string) ($_GET['q'] ?? '')),
            ];

            $especialidades = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('especialidades/index', [
                '_layout' => 'erp',
                'title' => 'Especialidades',
                'breadcrumb' => [
                    'Cadastros' => '#',
                    'Corpo Clínico' => '#',
                    0 => 'Especialidades',
                ],
                'especialidades' => $especialidades,
                'filtros' => $filtros,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar especialidades: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function create(): void
    {
        View::render('especialidades/create', [
            '_layout' => 'erp',
            'title' => 'Nova Especialidade',
            'breadcrumb' => [
                'Cadastros' => '#',
                'Corpo Clínico' => '#',
                'Especialidades' => self::BASE_ROUTE,
                0 => 'Nova Especialidade',
            ],
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $especialidade = trim(strip_tags((string) ($_POST['especialidade'] ?? '')));
            $subespecialidade = trim(strip_tags((string) ($_POST['subespecialidade'] ?? '')));
            $rqe = trim(strip_tags((string) ($_POST['rqe'] ?? '')));

            if ($especialidade === '') {
                header('Location: ' . self::BASE_ROUTE . '/create?error=missing_fields');
                exit();
            }

            $id = $this->model->create([
                'usuario_id' => $usuarioId,
                'especialidade' => $especialidade,
                'subespecialidade' => $subespecialidade !== '' ? $subespecialidade : null,
                'rqe' => $rqe !== '' ? $rqe : null,
            ]);

            if ($id) {
                AuditLogger::log('create_especialidade', [
                    'id' => $id,
                    'especialidade' => $especialidade,
                ]);
                header('Location: ' . self::BASE_ROUTE . '?success=created');
            } else {
                header('Location: ' . self::BASE_ROUTE . '/create?error=db_failure');
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao criar especialidade: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '/create?error=fatal');
        }
        exit();
    }
}
