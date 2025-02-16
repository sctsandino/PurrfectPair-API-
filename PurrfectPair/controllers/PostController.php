<?php
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/User.php';

class PostController {
    private $post;
    private $user;

    public function __construct() {
        $this->post = new Post();
        $this->user = new User();
    }

    public function createPost($headers, $files, $data) {
        if (!isset($headers['Authorization'])) {
            return ["status" => false, "message" => "Authorization required."];
        }

        $token = str_replace("Bearer ", "", $headers['Authorization']);
        $userValidation = $this->user->validateToken($token);
        if (!$userValidation['status']) {
            return $userValidation;
        }

        $requiredFields = ['cat_name', 'cat_age', 'cat_breed'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ["status" => false, "message" => "$field is required."];
            }
        }

        if (!isset($files['cat_image'])) {
            return ["status" => false, "message" => "Cat image is required."];
        }

        $image = $files['cat_image'];
        $fileInfo = pathinfo($image['name']);
        $fileExtension = strtolower($fileInfo['extension']);

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            return ["status" => false, "message" => "Invalid image format. Allowed: jpg, jpeg, png."];
        }

        // Create uploads directory if not exists
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique file name
        $fileName = uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($image['tmp_name'], $filePath)) {
            return ["status" => false, "message" => "Failed to upload image."];
        }

        $user_id = $userValidation['user']['id'];
        return $this->post->createPost(
            $user_id,
            $fileName,
            $data['cat_name'],
            $data['cat_age'],
            $data['cat_breed']
        );
    }

    // Get user’s own posts (pending & approved)
    public function getUserPosts($headers) {
        if (!isset($headers['Authorization'])) {
            return ["status" => false, "message" => "Authorization required."];
        }

        $token = str_replace("Bearer ", "", $headers['Authorization']);
        $userValidation = $this->user->validateToken($token);
        if (!$userValidation['status']) {
            return $userValidation;
        }

        return $this->post->getUserPosts($userValidation['user']['id']);
    }

    // Get only approved posts for public view
    public function getPublicPosts() {
        return $this->post->getApprovedPosts();
    }

    // Get all pending posts (user-specific)
    public function getUserPendingPosts($headers) {
        if (!isset($headers['Authorization'])) {
            return ["status" => false, "message" => "Authorization required."];
        }

        $token = str_replace("Bearer ", "", $headers['Authorization']);
        $userValidation = $this->user->validateToken($token);
        if (!$userValidation['status']) {
            return $userValidation;
        }

        return $this->post->getPendingPosts($userValidation['user']['id']);
    }
}
?>
