<?php
require "../start.php";
use Src\Post;
use Src\User;
use Src\Like;
use Src\Comment;

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        // may also be using PUT, PATCH, HEAD etc
        header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");         
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

header("Content-Type: application/json; charset=UTF-8; multipart/form-data;");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers,Access-Control-Allow-Methods, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

$requestMethod = $_SERVER["REQUEST_METHOD"];

// all of our endpoints start with /post or /posts
if ($uri[1] == 'posts') {
    $postId = null;
    if (isset($uri[2])) {
        $postId = (int) $uri[2];
    }
    // pass the request method and post ID to the Post and process the HTTP request:
    $controller = new Post($dbConnection, $requestMethod, $postId);
    $controller->processRequest();
}
// all of our endpoints start with /user or /users
elseif ($uri[1] == 'users') {
    $userId = null;
    if (isset($uri[2])) {
        $userId = (int) $uri[2];
    }
    // pass the request method and post ID to the Post and process the HTTP request:
    $userController = new User($dbConnection, $requestMethod, $userId);
    $userController->processUserRequest();
}
elseif ($uri[1] == 'login') {
    $userId = null;
    if (isset($uri[2])) {
        header("HTTP/1.1 404 Not Found");
        exit();
    }
    // pass the request method and post ID to the Post and process the HTTP request:
    $loginController = new User($dbConnection, $requestMethod, $userId);
    $loginController->processLoginRequest();
}
elseif ($uri[1] == 'likes') {
    $likeId = null;
    if (isset($uri[2])) {
        $likeId = (int) $uri[2];
    }
    // pass the request method and post ID to the Post and process the HTTP request:
    $likeController = new Like($dbConnection, $requestMethod, $likeId);
    $likeController->processLikeRequest();
}

elseif ($uri[1] == 'comments') {
    $commentId = null;
    if (isset($uri[2])) {
        $commentId = (int) $uri[2];
    }
    // pass the request method and post ID to the Post and process the HTTP request:
    $commentController = new Comment($dbConnection, $requestMethod, $commentId);
    $commentController->processCommentRequest();
}
// everything else results in a 404 Not Found
else{
    header("HTTP/1.1 404 Not Found");
    exit();
}