<?php
// Test direct POST
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'POST reçu directement',
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'input' => file_get_contents('php://input')
]);
