<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;

/**
 * Controller de diagnóstico temporário.
 * REMOVER APÓS IDENTIFICAR O PROBLEMA DE UPLOAD.
 */
class DiagnosticoController extends Controller
{
    public function uploadInfo(): void
    {
        // Só admin pode acessar
        if (!Auth::check()) {
            http_response_code(403);
            echo 'Acesso negado';
            exit();
        }

        $usuarioId = Auth::user()->id;
        $basePath  = BASE_PATH;

        $dirs = [
            'storage'                      => $basePath . '/storage',
            'storage/uploads'              => $basePath . '/storage/uploads',
            'storage/uploads/contas_pagar' => $basePath . '/storage/uploads/contas_pagar',
            'storage/uploads/contas_receber' => $basePath . '/storage/uploads/contas_receber',
            'storage/uploads/notas_fiscais_anexos' => $basePath . '/storage/uploads/notas_fiscais_anexos',
            'storage/logs'                 => $basePath . '/storage/logs',
        ];

        $info = [
            'BASE_PATH'          => $basePath,
            'PHP_VERSION'        => PHP_VERSION,
            'upload_max_filesize'=> ini_get('upload_max_filesize'),
            'post_max_size'      => ini_get('post_max_size'),
            'file_uploads'       => ini_get('file_uploads'),
            'upload_tmp_dir'     => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            'tmp_dir_writable'   => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) ? 'SIM' : 'NAO',
            'usuario_id_sessao'  => $usuarioId,
            'directories'        => [],
        ];

        foreach ($dirs as $label => $path) {
            $info['directories'][$label] = [
                'path'     => $path,
                'exists'   => is_dir($path) ? 'SIM' : 'NAO',
                'writable' => (is_dir($path) && is_writable($path)) ? 'SIM' : 'NAO',
                'perms'    => is_dir($path) ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A',
            ];
        }

        // Testa criação de arquivo
        $testDir  = $basePath . '/storage/uploads/notas_fiscais_anexos/' . $usuarioId . '/test_diag';
        $testFile = $testDir . '/test_' . time() . '.txt';
        $mkdirOk  = false;
        $writeOk  = false;
        $mkdirErr = '';

        if (!is_dir($testDir)) {
            $mkdirOk = @mkdir($testDir, 0755, true);
            if (!$mkdirOk) {
                $err = error_get_last();
                $mkdirErr = $err['message'] ?? 'erro desconhecido';
            }
        } else {
            $mkdirOk = true;
        }

        if ($mkdirOk) {
            $writeOk = (file_put_contents($testFile, 'teste') !== false);
            if ($writeOk) {
                @unlink($testFile);
                @rmdir($testDir);
            }
        }

        $info['test_mkdir']       = $mkdirOk ? 'OK' : 'FALHOU';
        $info['test_mkdir_error'] = $mkdirErr;
        $info['test_write']       = $writeOk ? 'OK' : 'FALHOU';

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
}
