<?php
/*
 * Convert array response in JSON and print with status code
 */

function respond($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
