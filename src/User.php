<?php
namespace Src;
use Firebase\JWT\JWT;

class User {
  private $db;
  private $requestMethod;
  private $userId;

  public function __construct($db, $requestMethod, $userId)
  {
    $this->db = $db;
    $this->requestMethod = $requestMethod;
    $this->userId = $userId;
  }

  public function processUserRequest()
  {
    switch ($this->requestMethod) {
      case 'GET':
        if ($this->userId) {
          $response = $this->getUser($this->userId);
        } else {
          $response = $this->getAllUsers();
        };
        break;
      case 'POST':
        $response = $this->createUser();
        break;
      case 'PUT':
        $response = $this->updateUser($this->userId);
        break;
      case 'DELETE':
        $response = $this->deleteUser($this->userId);
        break;
      default:
        $response = $this->notFoundResponse();
        break;
    }
    header($response['status_code_header']);
    if ($response['body']) {
        echo $response['body'];
    }
  }

  public function processLoginRequest() {
    switch ($this->requestMethod) {
      case 'POST':
        $response = $this->loginUser();
        break;
      default:
        $response = $this->notFoundResponse();
        break;
    }
    header($response['status_code_header']);
    if ($response['body']) {
        echo $response['body'];
    }
  }

  private function getAllUsers()
  {
    $query = 'SELECT c.country_name as country_name, u.user_id, u.username, u.email, u.password, u.date_created FROM users u LEFT JOIN countries c ON u.country = c.country_id ORDER BY u.date_created DESC';

    try {
      $statement = $this->db->query($query);
      $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }

    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);
    return $response;
  }

  private function getUser($id)
  {
    $result = $this->find($id);
    if (! $result) {
        return $this->notFoundResponse();
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);
    return $response;
  }

  private function createUser()
  {
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    if (! $this->validateUser($input)) {
      return $this->unprocessableEntityResponse();
    }
    $check = 'SELECT email from users where email = :email';
    $run = $this->db->prepare($check);
    $run->execute(array('email'=>$input['email']));
    $num=$run->rowCount();
    if($num<=0){

    

    $query = 'INSERT INTO users
        SET
          username = :username,
          email = :email,
          password = :password';

    try {
      $statement = $this->db->prepare($query);
      $password = $input['password'];
      $password_hash = password_hash($password, PASSWORD_BCRYPT);
      $statement->execute(array(
        'username' => $input['username'],
        'email'  => $input['email'],
        'password' => $password_hash
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }


    $response['status_code_header'] = 'HTTP/1.1 201 Created';
    $response['body'] = json_encode(array('message' => 'User Created'));
    return $response;
  }else{
    $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
    $response['body'] = json_encode(array('message' => 'email already exist'));
    return $response;
  }
}
  
  private function updateUser($id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    if (! $this->validateUserUpdate($input)) {
      return $this->unprocessableEntityResponse();
    }

    $statement = "
      UPDATE users
      SET
        password = :password
      WHERE user_id = :id;
    ";

    try {
      $statement = $this->db->prepare($statement);
      $statement->execute(array(
        'id' => (int) $id,
        'password' => $input['password']
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'User Updated!'));
    return $response;
  }

  private function deleteUser($id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }

    $query = "
      DELETE FROM users
      WHERE user_id = :id;
    ";

    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'User Deleted!'));
    return $response;
  }

  private function loginUser() {
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    $password = $input['password'];
    $query = "
      SELECT * from users
      WHERE email = :email
    ";
    $statement = $this->db->prepare($query);
    $statement->execute(array(
      'email' => $input['email'],
  ));
    $num = $statement->rowCount();
    if ($num > 0) {
      $result = $statement->fetch(\PDO::FETCH_ASSOC);
      $userId = $result['user_id'];
      $username = $result['username'];
      $email = $result['email'];

      if(password_verify($password, $result['password'])) {
        $secret_key = "Dheeth";
        $issuer_claim = "localhost"; // this can be the servername
        $audience_claim = "public";
        $issuedat_claim = time(); // issued at
        $notbefore_claim = $issuedat_claim + 10; //not before in seconds
        $expire_claim = $issuedat_claim + 600; // expire time in seconds
        $token = array(
            "iss" => $issuer_claim,
            "aud" => $audience_claim,
            "iat" => $issuedat_claim,
            "nbf" => $notbefore_claim,
            "exp" => $expire_claim,
            "data" => array(
                "id" => $userId,
                "username" => $username,
                "email" => $email
        ));
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $jwt = JWT::encode($token, $secret_key);
        $response['body'] = json_encode(array(
          'message' => 'Login Successful',
          'jwt' => $jwt
          // 'email' => $email,
          // 'username' => $username,
          // 'expiry' => $expire_claim
        ));
        return $response;
      }
      else {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = json_encode(array('message' => 'Login failed! Password is incorrect'));
        return $response;
      }
    }
    else {
      $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
      $response['body'] = json_encode(array('message' => "User doesn't exist,Please Signup"));
      return $response;
     }

  }

  public function find($id)
  {
    $query = "
    SELECT c.country_name as country_name, u.user_id, u.username, u.email, u.password, u.date_created FROM users u LEFT JOIN countries c ON u.country = c.country_id WHERE u.user_id = :id";

    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $result = $statement->fetch(\PDO::FETCH_ASSOC);
      return $result;
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
  }

  private function validateUser($input)
  {
    if (! isset($input['username'])) {
      return false;
    }
    if (! isset($input['email'])) {
        return false;
    }
    if (! isset($input['password'])) {
        return false;
    }

    return true;
  }

  private function validateUserUpdate($input)
  {
    if (! isset($input['password'])) {
      return false;
    }

    return true;
  }

  private function unprocessableEntityResponse()
  {
    $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
    $response['body'] = json_encode([
      'error' => 'Invalid input'
    ]);
    return $response;
  }

  private function notFoundResponse()
  {
    $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
    $response['body'] = null;
    return $response;
  }
}