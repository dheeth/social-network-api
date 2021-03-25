<?php
namespace Src;
class Comment  {
  private $db;
  private $requestMethod;
  private $commentId;
  public function __construct($db, $requestMethod, $commentId)
  {
    $this->db = $db;
    $this->requestMethod = $requestMethod;
    $this->commentId = $commentId;
  }
  public function processCommentRequest()
  {
    switch ($this->requestMethod) {
      case 'GET':
        if ($this->commentId) {
          $response = $this->getComments($this->commentId);
        } else {
          $response = $this->getAllComments();
        };
        break;
      case 'POST':
        $response = $this->createComment();
        break;
      case 'DELETE':
        $response = $this->deleteComment($this->commentId);
        break;
        case 'PUT':
            $response = $this->updateComment($this->commentId); 
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
  private function getAllComments()
  {
    $query = 'SELECT u.username as username, c.comment_id, c.user_id, c.post_id, c.date_created, c.comment, p.caption as caption FROM 
    comments c LEFT JOIN users u ON c.user_id = u.user_id LEFT JOIN posts p on c.post_id = p.post_id ORDER BY c.date_created DESC';
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
  private function getComments($id)
  {
    $result = $this->findforpost($id);
    if (! $result) {
        return $this->notFoundResponse();
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);
    return $response;
  }
  private function createComment()
  {
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    if (! $this->validatePost($input)) {
      return $this->unprocessableEntityResponse();
    }
    $query = 'INSERT INTO comments
        SET
          user_id = :user_id,
          post_id = :post_id,
          comment=   :comment';
    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array(
        'user_id' => $input['user_id'],
        'post_id' => $input['post_id'],
        'comment' => $input['comment'],
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 201 Created';
    $response['body'] = json_encode(array('message' => 'Comment Done'));
    return $response;
  }
  private function deleteComment($id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }
    $query = "
      DELETE FROM comments
      WHERE comment_id = :id;
    ";
    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'Comment Deleted!'));
    return $response;
  }

  private function updateComment($id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    if (! $this->validatePost($input)) {
      return $this->unprocessableEntityResponse();
    }

    $statement = "
      UPDATE comments
      SET
        comment = :comment
      WHERE comment_id = :id;
    ";

    try {
      $statement = $this->db->prepare($statement);
      $statement->execute(array(
        'id' => (int) $id,
        'comment' => $input['comment']
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'Comment Updated!'));
    return $response;
  }

  
  public function find($id)
  {
    $query =  'SELECT u.username as username, c.comment_id, c.user_id, c.post_id, c.date_created, c.comment, p.caption as caption FROM 
    comments c LEFT JOIN users u ON c.user_id = u.user_id LEFT JOIN posts p on c.post_id = p.post_id WHERE c.comment_id = :id ORDER BY c.date_created DESC';
    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $result = $statement->fetch(\PDO::FETCH_ASSOC);
      return $result;
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
  }

  public function findforpost($id)
  {
    $query =  'SELECT u.username as username, c.comment_id, c.user_id, c.post_id, c.date_created, c.comment, p.caption as caption FROM 
    comments c LEFT JOIN users u ON c.user_id = u.user_id LEFT JOIN posts p on c.post_id = p.post_id WHERE p.post_id = :id ORDER BY c.date_created DESC';
    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
      return $result;
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
  }
 

  private function validatePost($input)
  {
    
    if (! isset($input['comment'])) {
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
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode([],JSON_FORCE_OBJECT);
    return $response;
  }
}