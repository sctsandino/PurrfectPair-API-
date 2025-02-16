<?php
require_once __DIR__ . '/../config/Database.php';

class Auth {
    private $conn;
    private $userTable = "users";
    private $adminTable = "admins";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // User login
    public function userLogin($email, $password) {
        return $this->login($email, $password, $this->userTable);
    }

    // Admin login
    public function adminLogin($email, $password) {
        return $this->login($email, $password, $this->adminTable, true);
    }

    // General login function (supports users and admins)
    private function login($email, $password, $table, $isAdmin = false) {
        $query = "SELECT id, email, full_name, password FROM $table WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+24 hours"));

            $updateTokenQuery = "UPDATE $table SET token = :token, token_expiry = :expiry WHERE id = :id";
            $stmt = $this->conn->prepare($updateTokenQuery);
            $stmt->execute([
                ":token" => $token,
                ":expiry" => $expiry,
                ":id" => $user["id"]
            ]);

            $response = [
                "status" => true,
                "message" => $isAdmin ? "Admin login successful." : "Login successful.",
                "user" => [
                    "id" => $user["id"],
                    "email" => $user["email"],
                    "full_name" => $user["full_name"] ?? null,
                    "token" => $token,
                    "token_expiry" => $expiry
                ]
            ];

            if ($isAdmin) {
                unset($response["user"]["full_name"]); // Admins don't need `full_name`
                $response["admin"] = $response["user"];
                unset($response["user"]);
            }

            return $response;
        }

        return ["status" => false, "message" => "Invalid email or password."];
    }

    // Validate user token
    public function validateUserToken($token) {
        return $this->validateToken($token, $this->userTable);
    }

    // Validate admin token
    public function validateAdminToken($token) {
        return $this->validateToken($token, $this->adminTable);
    }

    // Generic token validation function
    private function validateToken($token, $table) {
        $query = "SELECT id, email, token_expiry FROM $table WHERE token = :token LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $expiry = new DateTime($user["token_expiry"]);
            if ($expiry < new DateTime()) {
                $this->logout($token, $table);
                return ["status" => false, "message" => "Token expired. Please log in again."];
            }
            return ["status" => true, "user" => $user];
        }

        return ["status" => false, "message" => "Invalid or expired token."];
    }

    // Logout function (works for both users and admins)
    public function logout($token, $table) {
        $query = "UPDATE $table SET token = NULL, token_expiry = NULL WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
    }
}
?>
