<?php

namespace app\forms;

include_once '../vendor/autoload.php';
include_once '../models/excel.php';

use app\models\Excel;

class ExcelForm {

    public $files;
    public $errors = null;
    private $targetDir = '../files/';
    private $allowedExt = ['xlsx'];

    public function validate($files) {
        if (empty($files)) {
            $this->errors['files'] = 'No file to upload';
            return false;
        }

        $this->files = $files;
        return true;
    }

    public function upload() {
//        exit(var_dump($this->files['excel']));
//        echo '<pre>'; print_r($this->files['excel']); exit();
        if (!empty(array_filter($this->files['excel']['name']))) {
            foreach ($this->files['excel']['name'] as $key => $val) {
                // File upload path
                $originalFileName = basename($this->files['excel']['name'][$key]);
                $fileExt = pathinfo($originalFileName, PATHINFO_EXTENSION);
                $fileName = time() . rand(10, 1000) . '.' . $fileExt;
                $targetFilePath = $this->targetDir . $fileName;
                // Check whether file type is valid
                if (in_array($fileExt, $this->allowedExt)) {
                    // Upload file to server
                    if (move_uploaded_file($this->files['excel']["tmp_name"][$key], $targetFilePath)) {
                        # Make excel entry in database
                        $excel = new Excel();
                        $excel->name = $fileName;
                        $excel->originalName = $originalFileName;
                        $excel->path = $targetFilePath;
                        $excel->uploadedAt = date('Y-m-d H:i:s', time());
                        $excel->userId = 2;

                        # Create Database entry for uploaded file
                        if (!$excel->create()) {
                            $logTime = $this->log('error', json_encode(['errors' => $excel->errors, 'filename' => $this->files['excel']['name'][$key]]));
                            $this->errors['files'][] = $this->files['excel']['name'][$key] . ' - File failed to upload. Check the log at - ' . $logTime;
                            continue;
                        } 
                        $logTime = $this->log('excel', json_encode(['originamName' => $this->files['excel']['name'][$key], 'name' => $excel->name, 'message' => 'File uploaded successfully']));
                        
                        # IMport excel
                        if(!$excel->import()) {
                            $logTime = $this->log('import_error', json_encode(['errors' => $excel->errors, 'filename' => $this->files['excel']['name'][$key]]));
                            $this->errors['files'][] = $this->files['excel']['name'][$key] . ' - File failed to import. Check the log at - ' . $logTime;
                            continue;
                        }
                        $logTime = $this->log('excel', json_encode(['originamName' => $this->files['excel']['name'][$key], 'name' => $excel->name, 'message' => 'File uploaded successfully']));
                        # Read file data and insert into database;
                        
                    } else {
                        $this->errors['files'][] = $this->files['excel']['name'][$key] . ' - File failed to upload';
//                        $errorUpload .= $this->files['excel']['name'][$key] . ', ';
                    }
                } else {
                    $this->errors['files'][] = $this->files['excel']['name'][$key] . ' - File failed to upload';
//                    $errorUploadType .= $this->files['excel']['name'][$key] . ', ';
                }
            }
        } else {
            $this->errors['files'] = 'No files to upload';
        }

        if (!empty($this->errors)) {
            return false;
        }

        return true;
    }

    public function log($type, $jsonData) {
        $log_filename = $type . '_' . date('Ymd') . '.log';
        if (!file_exists('../logs')) {
            // create directory/folder uploads.
            mkdir('../logs', 0777, true);
        }
        $log_file = '../logs/' . $log_filename;
        $time = time('Y-m-d H:i:s');
        $log_msg = $time . ' | ' . $_SERVER['REQUEST_URI'] . ' | ' . __CLASS__ . ' | ' . $jsonData . "\n";
        file_put_contents($log_file, $log_msg, FILE_APPEND);
        return $time;
    }

}
