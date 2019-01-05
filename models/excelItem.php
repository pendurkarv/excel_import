<?php

namespace app\models;

include '../vendor/autoload.php';

use Firebase\JWT\JWT;

include_once '../config/db.php';

class ExcelItem {

    public $table = 'excel_items';
    public $id = null;
    public $excelId;
    public $sheetId;
    public $columnIndex;
    public $value;
    public $errors;

    public function __construct() {
        $database = new \app\Db();
        $this->conn = $database->connect();
    }

    /*
     * Validates model data
     */

    public function validate() {
        $flag = true;
        $required = ['excelId', 'columnIndex', 'value'];
        foreach ($required as $property) {
            if (empty($this->$property)) {
                $this->errors[$property] = "`$property` is required";
                $flag = false;
            }
        }
        
//        $numeric = ['excelId', 'columnIndex'];
//        foreach ($required as $property) {
//            if (empty($this->$property)) {
//                $this->errors[$property] = "`$property` must be numeric";
//                $flag = false;
//            }
//        }

        return $flag;
    }

    /*
     * Get individual cell from the database
     */

    public function getCell($excelId, $sheetId, $columnIndex) {
        $statement = $this->conn->prepare("SELECT value FROM {$this->table} where excel_id = :excel_id AND sheet_id = :sheet_id AND column_index = :column_index");

        $statement->execute([
            'excel_id' => $excelId,
            'sheet_id' => $sheetId,
            'column_index' => $columnIndex,
        ]);

        $row = $statement->fetch();

        if (!empty($row)) {
            return $row['value'];
        }

        return false;
    }

    public function create() {
        if (!$this->validate()) {
            return false;
        }

        $statement = $this->conn->prepare("INSERT INTO {$this->table} (excel_id, sheet_id, column_index, value) VALUES (:excel_id, :sheet_id, :column_index, :value)");
        try {
            $res = $statement->execute([
                'excel_id' => $this->excelId,
                'sheet_id' => $this->sheetId,
                'column_index' => $this->columnIndex,
                'value' => $this->value
            ]);
            
            
        } catch (\PDOException $ex) {
            $this->errors = $ex->getMessage();
//            echo '<pre>';
//            print_r($this->errors);
//            exit;
            return false;
        }

        return true;
    }

}
