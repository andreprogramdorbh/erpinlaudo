<?php

namespace App\Core;

/**
 * Classe Form para renderização de componentes de formulário reutilizáveis.
 */
class Form
{
    /**
     * Renderiza um componente de formulário.
     *
     * @param string $component Nome do componente (arquivo em app/Views/components/form/)
     * @param array $data Dados para o componente
     */
    public static function render(string $component, array $data = []): void
    {
        extract($data);
        $viewPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'form' . DIRECTORY_SEPARATOR . $component . '.php';

        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<!-- Componente de formulário não encontrado: {$component} -->";
        }
    }

    public static function start(string $id, string $action = '', string $method = 'POST', string $class = ''): void
    {
        self::render('start', compact('id', 'action', 'method', 'class'));
    }

    public static function end(): void
    {
        self::render('end');
    }

    public static function cardStart(string $title, string $icon = ''): void
    {
        self::render('card-start', compact('title', 'icon'));
    }

    public static function cardEnd(): void
    {
        self::render('card-end');
    }

    public static function rowStart(): void
    {
        self::render('row-start');
    }

    public static function rowEnd(): void
    {
        self::render('row-end');
    }

    public static function colStart(string $col = '12', string $class = ''): void
    {
        self::render('col-start', compact('col', 'class'));
    }

    public static function colEnd(): void
    {
        self::render('col-end');
    }

    public static function input(string $name, string $label, string $type = 'text', $value = '', array $attr = []): void
    {
        self::render('input', array_merge(compact('name', 'label', 'type', 'value'), $attr));
    }

    public static function select(string $name, string $label, array $options = [], $value = '', array $attr = []): void
    {
        self::render('select', array_merge(compact('name', 'label', 'options', 'value'), $attr));
    }

    public static function textarea(string $name, string $label, $value = '', array $attr = []): void
    {
        self::render('textarea', array_merge(compact('name', 'label', 'value'), $attr));
    }

    public static function button(string $text, string $type = 'submit', string $class = 'btn-primary', string $icon = '', array $attr = []): void
    {
        self::render('button', array_merge(compact('text', 'type', 'class', 'icon'), $attr));
    }
}
