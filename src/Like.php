<?php
namespace Src;
class Like {
  private $db;
  private $requestMethod;
  private $likeId;
  public function __construct($db, $requestMethod, $likeId)
  {
    $this->db = $db;
    $this->requestMethod = $requestMethod;
    $this->likeId = $likeId;
  }
  public function processLikeRequest()
  {
    switch ($this->requestMethod) {
      case 'GET':
        if ($this->likeId) {
          $response = $this->getLike($this->likeId);
        } else {
          $response = $this->getAllLikes();
        };
        break;
      case 'POST':
        $response = $this->createLike();
        break;
      case 'DELETE':
        $response = $this->deleteLike($this->likeId);
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
  private function getAllLikes()
  {
    $query = 'SELECT u.username as username, l.like_id, l.user_id, l.post_id, l.date_created, p.caption as caption FROM likes l LEFT JOIN users u ON l.user_id = u.user_id LEFT JOIN posts p on l.post_id = p.post_id ORDER BY l.date_created DESC';
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
  private function getLike($id)
  {
    $result = $this->find($id);
    if (! $result) {
        return $this->notFoundResponse();
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);
    return $response;
  }
  private function createLike()
  {
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    if (! $this->validatePost($input)) {
      return $this->unprocessableEntityResponse();
    }
    $query = 'INSERT INTO likes
        SET
          user_id = :user_id,
          post_id = :post_id';
    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array(
        'user_id' => $input['user_id'],
        'post_id' => $input['post_id']
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 201 Created';
    $response['body'] = json_encode(array('message' => 'Like Done'));
    return $response;
  }
  private function deleteLike($id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }
    $query = "
      DELETE FROM likes
      WHERE like_id = :id;
    ";
    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'Dislike Done!'));
    return $response;
  }
  public function find($id)
  {
    $query = "
    SELECT u.username as username, l.like_id, l.user_id, l.post_id, l.date_created, p.caption as caption FROM likes l LEFT JOIN users u ON l.user_id = u.user_id LEFT JOIN posts p on l.post_id = p.post_id WHERE l.like_id = :id";
    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $result = $statement->fetch(\PDO::FETCH_ASSOC);
      return $result;
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
  }
  private function validatePost($input)
  {
    if (! isset($input['user_id'])) {
      return false;
    }
    if (! isset($input['post_id'])) {
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