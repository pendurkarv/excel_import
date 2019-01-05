<?php
namespace app\models;
include_once '../vendor/autoload.php';
use Firebase\JWT\JWT;
include_once '../config/db.php';

class User {

    private $conn;
    private $table = 'users';
    
    // table columns
    public $id;
    
    public $name;
    public $username;
    private $password_hash;
    
    private $password;

    public $errors;
    public $scenario;
    
    const SCENARIO_LOGIN = 'login';
    const SCENARIO_REGISTER = 'register';


    public function __construct(){
        $database = new \app\Db();       
        $this->conn = $database->connect();
    }    
    
    /*
     * Loads data into model
     */
    public function load($data) {
        $this->name = !empty($data['name']) ? $data['name'] : null;
        $this->username = !empty($data['username']) ? $data['username'] : null;
        $this->password = !empty($data['password']) ? $data['password'] : null;

        return true;
    }

    /*
     * Validates data against set of rules
     */
    public function validate() {
        $flag = true;
        if ($this->scenario == 'register' && empty($this->name)) {
            $this->errors['name'] = 'Please provide name';
            $flag = false;
        }
        
        if (empty($this->username)) {
            $this->errors['username'] = 'Please provide username';
            $flag = false;
        }

        if (empty($this->password)) {
            $this->errors['password'] = 'Please provide password';
            $flag = false;
        }
        
        if(!$flag === false && $this->scenario === self::SCENARIO_LOGIN) {
            $this->errors = 'Invalid login credentials';
        }
        
        return $flag;
    }

    /*
     * Create a new user
     */
    public function create() {
        $statement = $this->conn->prepare("INSERT INTO {$this->table} (name, username, password_hash) VALUES (:name, :username, :password_hash)");
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        try {
            $res = $statement->execute([
                'name' => $this->name,
                'username' => $this->username,
                'password_hash' => $password_hash
            ]);
            return $res;
        } catch (\PDOException $ex) {
            if($ex->getCode() === '23000') {
                $this->errors['username'] = 'Username alredy taken';
                return false;
            }
        }
        
        return FALSE;
    }

    /*
     * Logs in user and return token if authenticated
     */
    public function login() {
        if(!$this->validate()) {
            return false;
        }
        $statement = $this->conn->prepare("SELECT id, username, password_hash FROM {$this->table} where username = :username");
        $statement->execute(['username' => $this->username]);
        $row = $statement->fetch();
        
        # If user is verified, return JWT
        if(password_verify($this->password, $row['password_hash'])) {
            return $this->generateToken();
        } 
        
        $this->errors = 'Invalid login credentials';
        return false;
    }
    
    
    public function generateToken() {
        $key = "secret";
        $token = array(
            "iss" => "http://localhost/gst/api",
            "aud" => "http://localhost/gst",
            "iat" => time(),
            "exp" => time() + 3600
        );

        $jwt = JWT::encode($token, $key);
        return $jwt;
    }
    
}
