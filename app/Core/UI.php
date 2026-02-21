<?php

namespace App\Core;

/**
 * Classe UI para renderização de componentes de interface reutilizáveis.
 */
class UI
{
    /**
     * Renderiza um componente de UI.
     *
     * @param string $component Nome do componente (arquivo em app/Views/components/ui/)
     * @param array $data Dados para o componente
     */
    public static function render(string $component, array $data = []): void
    {
        extract($data);
        $viewPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . $component . '.php';

        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<!-- Componente de UI não encontrado: {$component} -->";
        }
    }

    public static function header(string $title, array $breadcrumb = [], array $actions = []): void
    {
        self::render('header', compact('title', 'breadcrumb', 'actions'));
    }

    public static function statCard(string $title, string $value, string $icon, string $gradient = 'primary', string $footer = ''): void
    {
        self::render('stat-card', compact('title', 'value', 'icon', 'gradient', 'footer'));
    }

    public static function card(string $content, string $title = '', array $options = []): void
    {
        self::render('card', array_merge(['content' => $content, 'title' => $title], $options));
    }

    public static function sectionHeader(string $title, string $subtitle = '', array $actions = []): void
    {
        self::render('section-header', compact('title', 'subtitle', 'actions'));
    }

    public static function alert(string $message, string $type = 'info', string $title = ''): void
    {
        self::render('alert', compact('message', 'type', 'title'));
    }
}
