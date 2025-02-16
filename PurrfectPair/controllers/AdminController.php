<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Auth.php';

class AdminController {
    private $conn;
    private $postModel;
    private $auth;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->postModel = new Post();
        $this->auth = new Auth();
    }

    // Admin login
    public function adminLogin($data) {
        if (!isset($data['email']) || !isset($data['password'])) {
            return ["status" => false, "message" => "Email and password are required."];
        }
        return $this->auth->adminLogin($data['email'], $data['password']);
    }

    // Admin logout
    public function adminLogout($headers) {
        if (!isset($headers['Authorization'])) {
            return ["status" => false, "message" => "Authorization required."];
        }

        $token = str_replace("Bearer ", "", $headers['Authorization']);
        $this->auth->logout($token, "admins");
        return ["status" => true, "message" => "Admin logged out successfully."];
    }

    // Validate admin authentication
    private function isAdminAuthenticated($headers) {
        if (!isset($headers['Authorization'])) {
            return ["status" => false, "message" => "Authorization required."];
        }

        $token = str_replace("Bearer ", "", $headers['Authorization']);
        $adminValidation = $this->auth->validateAdminToken($token);

        if (!$adminValidation['status']) {
            return $adminValidation;
        }

        return ["status" => true];
    }

    // Update post status (approve/reject)
    public function updatePostStatus($headers, $data) {
        $authCheck = $this->isAdminAuthenticated($headers);
        if (!$authCheck['status']) {
            return $authCheck;
        }

        if (!isset($data['post_id']) || !isset($data['status'])) {
            return ["status" => false, "message" => "Post ID and status are required."];
        }

        return $this->postModel->updatePostStatus($data['post_id'], $data['status']);
    }

    // Fetch all pending posts (Requires admin authentication)
    public function getPendingPosts($headers) {
        $authCheck = $this->isAdminAuthenticated($headers);
        if (!$authCheck['status']) {
            return $authCheck;
        }

        return $this->postModel->getPendingPosts();
    }
}

// Handle API Requests
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$input = json_decode(file_get_contents("php://input"), true) ?? [];
$admin = new AdminController();
$response = ["status" => false, "message" => "Invalid request"];

if ($method === "POST" && isset($_GET['action'])) {
    if ($_GET['action'] === "admin_login") {
        $response = $admin->adminLogin($input);
    } elseif ($_GET['action'] === "admin_logout") {
        $response = $admin->adminLogout($headers);
    } elseif ($_GET['action'] === "update_post_status") {
        $response = $admin->updatePostStatus($headers, $input);
    }
} elseif ($method === "GET" && $_GET['action'] === "pending_posts") {
    $response = $admin->getPendingPosts($headers);
}

// Output JSON response
echo json_encode($response);
?>
