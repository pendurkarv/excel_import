<?php

header('Access-Contol-Allow-Origin: *');
header('Content-Type: application/json');

include_once '../models/user.php';
include_once '../utils/helpers.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        echo 'GET Method';
//        $model = new app\models\User();
//        echo $model->all();
        break;
    case 'POST':
        $model = new app\models\User();
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data)) { $data = $_POST; }
        
        # If request is register perform registration
        if ($_GET['action'] === 'register') {
            $model->scenario = app\models\User::SCENARIO_REGISTER;
            $model->load($data);
            if ($model->validate() && $model->create()) {
                respond(['message' => 'User created succssfully'], 201);
            } else {
                respond(['errors' => $model->errors], 400);
            }
        } else if ($_GET['action'] === 'login') {
            $model->scenario = app\models\User::SCENARIO_LOGIN;
            $model->load($data);

            $token = $model->login();
            if(!$token) {
                respond(['errors' => $model->errors], 400);
            }
            
            respond(['status' => 'success', 'token' => $token], 200);
        }

        break;
    case 'PATCH':
        echo 'PATCH Method';
        break;
    case 'DELETE':
        echo 'DELETE Method';
        break;
    default:
        http_response_code(400);
        echo 'Invalid request';       
}

respond(['message' => 'Invalid request'], 400);
