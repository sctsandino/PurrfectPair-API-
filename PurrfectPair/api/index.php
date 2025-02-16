<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/PostController.php';
require_once __DIR__ . '/../controllers/AdminController.php';

$auth = new AuthController();
$postController = new PostController();
$adminController = new AdminController();
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$endpoint = $_GET['endpoint'] ?? '';

if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];

    if ($endpoint === "register") {
        echo json_encode($auth->register($data));
    } elseif ($endpoint === "login") {
        echo json_encode($auth->login($data));
    } elseif ($endpoint === "logout") {
        echo json_encode($auth->logout($headers));
    } elseif ($endpoint === "create_post") {
        echo json_encode($postController->createPost($headers, $_FILES, $_POST));
    } elseif ($endpoint === "update_post_status") {
        echo json_encode($adminController->updatePostStatus($headers, $data)); // Pass headers for admin auth
    } else {
        echo json_encode(["status" => false, "message" => "Invalid endpoint."]);
    }
}

if ($method === "GET") {
    if ($endpoint === "validate") {
        echo json_encode($auth->validateToken($headers));
    } elseif ($endpoint === "user_posts") {
        echo json_encode($postController->getUserPosts($headers));
    } elseif ($endpoint === "user_pending_posts") {
        echo json_encode($postController->getUserPendingPosts($headers));
    } elseif ($endpoint === "public_posts") {
        echo json_encode($postController->getPublicPosts());
    } elseif ($endpoint === "pending_posts") {
        echo json_encode($postController->getPendingPosts());
    } else {
        echo json_encode(["status" => false, "message" => "Invalid request."]);
    }
}
?>
