<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $table = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function register($email, $full_name, $phone_number, $password) {
        if ($this->emailExists($email)) {
            return ["status" => false, "message" => "Email already registered."];
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO {$this->table} (email, full_name, phone_number, password)
                VALUES (:email, :full_name, :phone_number, :password)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":full_name", $full_name);
        $stmt->bindParam(":phone_number", $phone_number);
        $stmt->bindParam(":password", $hashed_password);

        return $stmt->execute()
            ? ["status" => true, "message" => "Registration successful."]
            : ["status" => false, "message" => "Registration failed."];
    }

    public function login($email, $password) {
        $query = "SELECT id, email, full_name, password FROM {$this->table} WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user["password"])) {
            return ["status" => false, "message" => "Invalid email or password."];
        }

        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+24 hours"));

        // Store the token
        $updateQuery = "UPDATE {$this->table} SET token = :token, token_expiry = :expiry WHERE id = :id";
        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expiry", $expiry);
        $stmt->bindParam(":id", $user["id"]);
        $stmt->execute();

        return [
            "status" => true,
            "message" => "Login successful.",
            "user" => [
                "id" => $user["id"],
                "email" => $user["email"],
                "full_name" => $user["full_name"],
                "token" => $token,
                "token_expiry" => $expiry
            ]
        ];
    }

    public function logout($token) {
        $query = "UPDATE {$this->table} SET token = NULL, token_expiry = NULL WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);

        return ($stmt->execute() && $stmt->rowCount() > 0)
            ? ["status" => true, "message" => "Logout successful."]
            : ["status" => false, "message" => "Invalid token or already logged out."];
    }

    public function validateToken($token) {
        $query = "SELECT id, email, full_name, token_expiry FROM {$this->table} WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ["status" => false, "message" => "Invalid or expired token."];
        }

        // Check token expiry
        if (strtotime($user["token_expiry"]) < time()) {
            $this->logout($token);  // Invalidate expired token
            return ["status" => false, "message" => "Token expired. Please log in again."];
        }

        return ["status" => true, "user" => $user];
    }

    private function emailExists($email) {
        $query = "SELECT 1 FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }
}
?>
