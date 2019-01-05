<?php

ini_set('display_errors', 1);

header('Access-Contol-Allow-Origin: *');
header('Content-Type: application/json');

include_once '../forms/excelForm.php';
include_once '../models/user.php';
include_once '../models/excel.php';
include_once '../models/excelItem.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $excel = new \app\models\Excel();
            if (!$excel->find($_GET['id'])) {
                http_response_code(404);
                echo json_encode(['message' => 'Record not found']);
                exit;
            }

            if (!empty($_GET['action'])) {
                if ($_GET['action'] == 'download') {
                    $excel->download();
                } else if ($_GET['action'] == 'import') {
                    $excel->import();
                } else if ($_GET['action'] == 'fetch-cell') {
                    $sheetId = !empty($_GET['sheet_id']) ? $_GET['sheet_id'] : null;
                    $columnIndex = !empty($_GET['column_index']) ? $_GET['column_index'] : null;

                    $value = $excel->getCell($sheetId, $columnIndex);
                    if (!$value) {
                        http_response_code(404);
                        echo json_encode(['message' => 'Record not found']);
                        exit;
                    }

                    http_response_code(200);
                    echo json_encode(['value' => $value]);
                    exit;
                }
            }
            http_response_code(200);
            echo json_encode($excel->show());
        }
        exit;
    case 'POST':
        if (!authUser()) {
            http_response_code(403);
            echo json_encode(['message' => 'Unauthorized access']);
            exit;
        }

        $excelForm = new \app\forms\ExcelForm();

        if (!$excelForm->validate($_FILES)) {
            http_response_code(400);
            echo json_encode(['message' => 'No file to upload']);
            exit;
        }

        if ($excelForm->upload()) {
            echo json_encode(['message' => 'Files uploaded']);
        } else {
            http_response_code(400);
            echo json_encode(['errors' => $excelForm->errors]);
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

/*
 * Upload files on server
 */

function uploadFiles() {
//    if()
}

/*
 * Authenticate user against header `Authorization: Bearer <token>` 
 */

function authUser() {
    $headers = apache_request_headers();

    if (!empty($headers['Authorization'])) {
        $authHeader = trim($headers['Authorization']);
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {

            try {
                $decoded = \Firebase\JWT\JWT::decode($matches[1], 'secret', array('HS256'));
                return true;
            } catch (Exception $ex) {
                http_response_code(403);
                if ($ex instanceof Firebase\JWT\SignatureInvalidException) {
                    echo json_encode(['message' => 'Invalid token']);
                } else if ($ex instanceof Firebase\JWT\ExpiredException) {
                    echo json_encode(['message' => 'Token Expired']);
                }
                exit;
            }
        }
    }

    return false;
}
