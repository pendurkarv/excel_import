<?php
ini_set('display_errors', 1);

header('Access-Contol-Allow-Origin: *');
header('Content-Type: application/json');

include_once '../models/user.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model = new app\models\User();

    $data = json_decode(file_get_contents("php://input"), true);
    if(empty($data)) {
        $data = $_POST;
    }
    
    $model->scenario = app\models\User::SCENARIO_LOGIN;
    $model->load($data);
    
    $token = $model->login();
    if(!$token) {
        http_response_code(400);
        echo json_encode(['errors' => $model->errors]);
        exit;
    }
    
    echo json_encode(['status' => 'success', 'token' => $token]);
} else {
    http_response_code(400);
    echo 'Invalid request';
}
