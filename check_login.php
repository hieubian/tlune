<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$response = [
    'loggedin' => false
];

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $response['loggedin'] = true;
    $response['username'] = $_SESSION['username'];
}

echo json_encode($response);
?>