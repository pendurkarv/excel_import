<?php
namespace app\models;
include '../vendor/autoload.php';
use Firebase\JWT\JWT;
include_once '../config/db.php';

class Excel{

    // Connection instance
    private $conn;
    // table name
    private $table = "excels";

    // table columns
    public $id = null;
    public $name;
    public $originalName;
    public $path;
    public $userId;
    public $uploadedAt;
    
    public $errors;

    public function __construct(){
        $database = new \app\Db();       
        $this->conn = $database->connect();
    }

    /*
     * Loads data into model
     */
    public function load($data) {
        $this->id = !empty($data['id']) ? $data['id'] : null;
        $this->name = !empty($data['name']) ? $data['name'] : null;
        $this->originalName = !empty($data['originalName']) ? $data['originalName'] : null;
        $this->path = !empty($data['path']) ? $data['path'] : null;
        $this->userId = !empty($data['userId']) ? $data['userId'] : null;
        $this->uploadedAt = !empty($data['uploadedAt']) ? $data['uploadedAt'] : null;

        return true;
    }    
    
    /*
     * Validates model data
     */
    public function validate() {
        $flag = true;
        $required = ['name', 'originalName', 'name', 'path', 'userId', 'uploadedAt'];
        foreach ($required as $property) {
            if(empty($this->$property)) {
                $this->errors[$property] = "`$property` is required";
                $flag = false;
            }
        }
        
        return $flag;
    }

    public function create() {
        if(!$this->validate()) { return false; }
        
        $statement = $this->conn->prepare("INSERT INTO {$this->table} (name, original_name, path, user_id, uploaded_at) VALUES (:name, :original_name, :path, :user_id, :uploaded_at)");
        try {
            $res = $statement->execute([
                'name' => $this->name,
                'original_name' => $this->originalName,
                'path' => $this->path,
                'user_id' => $this->userId,
                'uploaded_at' => $this->uploadedAt
            ]);
            $this->id = $this->conn->lastInsertId();
        } catch (\PDOException $ex) {
            if($ex->getCode() === '23000') {
                $this->errors['name'] = 'File with same name already exists';
                return false;
            }
            $this->errors = $ex->getMessage();
           
            return false;
        }
        
        return true;
    }
    
    public function import() {
        $filePath = '../files/' . $this->name;
        
        if(!file_exists($filePath)) {
            return false;
        }
        
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($filePath);
        $sheetCount = $spreadsheet->getSheetCount();
//        exit(vaR_dump($sheetCount));
        
        for ($i = 0; $i < $sheetCount; $i++) {
            $sheet = $spreadsheet->getSheet($i);
//            $rows = $sheet->getHighestRow();
            $cols = $sheet->getCoordinates();
//            $sheetData = $sheet->toArray(null, true, true, true);

            foreach($cols as $cellIndex) {
                $excelItem = new ExcelItem();
                $excelItem->excelId = $this->id;
                $excelItem->sheetId = $i + 1;
                $excelItem->columnIndex = $cellIndex;
                $excelItem->value = $sheet->getCell($cellIndex)->getCalculatedValue();
                
                if(!$excelItem->create()) {
                    $this->errors['import'][] = "Import failed - ExcelID - {$excelItem->excelId}, SheetID - {$excelItem->sheetId}, ColumnIndex - {$cellIndex}";
                }
            }
        }        
 
        if(!empty($this->errors)) {
            return false;
        }
        
        return true;
    }
    
    public function getCell($sheetId, $columnIndex) {
        $excelItem = new ExcelItem();
        $cellValue = $excelItem->getCell($this->id, $sheetId, $columnIndex);
        return $cellValue;
    }
    
    /*
     * Show details of excel file
     */
    public function show() {
        return [
            'name' => $this->name,
            'original_name' => $this->originalName,
            'path' => $this->path,
            'user_id' => $this->userId,
            'uploaded_at' => $this->uploadedAt,
            'download_url' =>  $this->getDownloadLink()
        ];
    }
    
    /*
     * Create excel download link
     */
    public function getDownloadLink() {
        return $_SERVER['HTTP_HOST'] . '/gst/api/excels.php?action=download&id=' . $this->id;
    }
    
    /*
     * Download Excel file
     */
    public function download() {
        $filePath = '../files/' . $this->name;
        if(file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($filePath) . "\""); 
            flush();
            readfile($filePath);    
            exit;
        } else {
            return false;
        }
        
    }
    
    /*
     * Find single object by ID and return
     */
    public function find($id) {
        $statement = $this->conn->prepare("SELECT * FROM {$this->table} where id = :id");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if(!$row) {
            return false;
        }
        
        $data = [
            'id' => $id,
            'name' => $row['name'],
            'originalName' => $row['original_name'],
            'path' => $row['path'],
            'userId' => $row['user_id'],
            'uploadedAt' => $row['uploaded_at']
        ];
        $this->load($data);
        
        return true;
    }
    
}