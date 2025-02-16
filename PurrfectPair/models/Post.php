<?php
require_once __DIR__ . '/../config/Database.php';

class Post {
    private $conn;
    private $table = "posts";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Function to upload an image and return its filename
    private function uploadImage($imageData, $user_id) {
        $uploadDir = __DIR__ . "/../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $imageName = "cat_" . $user_id . "_" . time() . ".jpg";
        $filePath = $uploadDir . $imageName;

        // Validate and save image
        if (@file_put_contents($filePath, base64_decode($imageData))) {
            return $imageName;
        }
        return false;
    }

    public function createPost($user_id, $cat_image, $cat_name, $cat_age, $cat_breed) {
        // Fetch user details
        $queryUser = "SELECT full_name, phone_number, email FROM users WHERE id = :user_id";
        $stmtUser = $this->conn->prepare($queryUser);
        $stmtUser->bindParam(":user_id", $user_id);
        $stmtUser->execute();
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ["status" => false, "message" => "User not found."];
        }

        // Check for duplicate pending post
        $queryDuplicate = "SELECT id FROM " . $this->table . " WHERE user_id = :user_id AND cat_name = :cat_name AND cat_age = :cat_age AND cat_breed = :cat_breed AND status = 'pending'";
        $stmtDuplicate = $this->conn->prepare($queryDuplicate);
        $stmtDuplicate->bindParam(":user_id", $user_id);
        $stmtDuplicate->bindParam(":cat_name", $cat_name);
        $stmtDuplicate->bindParam(":cat_age", $cat_age);
        $stmtDuplicate->bindParam(":cat_breed", $cat_breed);
        $stmtDuplicate->execute();

        if ($stmtDuplicate->rowCount() > 0) {
            return ["status" => false, "message" => "You already have a similar post pending approval."];
        }

        // Upload image
        $uploadedImage = $this->uploadImage($cat_image, $user_id);
        if (!$uploadedImage) {
            return ["status" => false, "message" => "Failed to upload image."];
        }

        // Insert post
        $query = "INSERT INTO " . $this->table . "
            (user_id, cat_image, cat_name, cat_age, cat_breed, status, created_at)
            VALUES (:user_id, :cat_image, :cat_name, :cat_age, :cat_breed, 'pending', NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":cat_image", $uploadedImage);
        $stmt->bindParam(":cat_name", $cat_name);
        $stmt->bindParam(":cat_age", $cat_age);
        $stmt->bindParam(":cat_breed", $cat_breed);

        if ($stmt->execute()) {
            return ["status" => true, "message" => "Post submitted for approval."];
        }
        return ["status" => false, "message" => "Failed to create post."];
    }

    public function getUserPosts($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingPosts() {
        $query = "SELECT * FROM " . $this->table . " WHERE status = 'pending' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPublicPosts() {
        $query = "SELECT * FROM " . $this->table . " WHERE status = 'approved' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePostStatus($post_id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status, updated_at = NOW() WHERE id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":post_id", $post_id);

        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return ["status" => true, "message" => "Post status updated to $status."];
        }
        return ["status" => false, "message" => "Post not found or already updated."];
    }
}
?>
