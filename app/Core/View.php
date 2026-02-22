<?php

namespace App\Core;

class View
{
    /**
     * Renderiza um arquivo de view com um layout.
     *
     * @param string $view O nome do arquivo da view (ex: "home.index").
     * @param array $data Os dados a serem extraídos e disponibilizados para a view.
     */
    public static function render(string $view, array $data = []): void
    {
        $layout = $data['_layout'] ?? 'default';
        unset($data['_layout']);

        // Converte as chaves do array em variáveis
        extract($data);

        // Monta o caminho para o arquivo da view
        $viewFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';

        if (file_exists($viewFile)) {
            if (!defined('ERP_VIEW_RENDERING')) {
                define('ERP_VIEW_RENDERING', true);
            }
            ob_start();
            require $viewFile;
            $content = ob_get_clean();

            // Seleciona o layout correto
            $layoutDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR;

            if ($layout === 'erp') {
                $headerFile = $layoutDir . 'erp_header.php';
                $footerFile = $layoutDir . 'erp_footer.php';
            } elseif ($layout === 'portal') {
                $headerFile = $layoutDir . 'portal_header.php';
                $footerFile = $layoutDir . 'portal_footer.php';
            } elseif ($layout === 'portal_public') {
                $headerFile = $layoutDir . 'portal_public_header.php';
                $footerFile = $layoutDir . 'portal_public_footer.php';
            } else {
                $headerFile = $layoutDir . 'header.php';
                $footerFile = $layoutDir . 'footer.php';
            }

            if (file_exists($headerFile)) {
                require $headerFile;
            }

            echo $content;

            if (file_exists($footerFile)) {
                require $footerFile;
            }
        } else {
            // Lança uma exceção ou exibe um erro se a view não for encontrada
            http_response_code(500);
            echo "Erro: View '{$view}' não encontrada no caminho: {$viewFile}";
            exit;
        }
    }

    /**
     * Retorna o campo de input oculto com o token CSRF.
     * @return string
     */
    public static function csrfField(): string
    {
        $token = $_SESSION['csrf_token'] ?? '';
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
