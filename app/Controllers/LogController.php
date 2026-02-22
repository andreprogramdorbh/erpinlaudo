<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;

class LogController extends Controller
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * Recebe um erro do frontend via POST e grava no log
     */
    public function saveClientError()
    {
        // Aceita apenas JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            return;
        }

        $message = $data['message'] ?? 'Unknown JS Error';
        $context = [
            'stack' => $data['stack'] ?? 'N/A',
            'url' => $data['url'] ?? 'N/A',
            'line' => $data['line'] ?? 'N/A',
            'column' => $data['column'] ?? 'N/A',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->logger->clientError($message, $context);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
