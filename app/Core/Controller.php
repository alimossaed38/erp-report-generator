<?php

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        $data['__view'] = __DIR__ . '/../Views/' . $view . '.php';
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/layout.php';
    }

    protected function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
