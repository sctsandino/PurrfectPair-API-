<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../config/Database.php';

class AuthController {
    
    private $user;

    public function __construct() {
        global $conn;
        $this->user = new User($conn);
    }

    public function register() {
        file_put_contents('php://stdout', "DEBUG: Function reached\n");
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['fullname'], $data['email'], $data['contactNumber'], $data['facebookName'], $data['homeAddress'], $data['password'], $data['confirmPassword'])) {
                sendResponse(400, "All fields are required.");
                return;
            }

            // Trim inputs
            $fullname = trim($data['fullname']);
            $email = trim($data['email']);
            $contactNumber = trim($data['contactNumber']);
            $facebookName = trim($data['facebookName']);
            $homeAddress = trim($data['homeAddress']);
            $password = $data['password'];
            $confirmPassword = $data['confirmPassword'];

            if ($password !== $confirmPassword) {
                sendResponse(400, "Passwords do not match.");
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, "@gmail.com")) {
                sendResponse(400, "Invalid email format. Must end with '@gmail.com'.");
                return;
            }

            if (strlen($password) < 8) {
                sendResponse(400, "Password must be at least 8 characters long.");
                return;
            }

            $success = $this->user->register($fullname, $email, $contactNumber, $facebookName, $homeAddress, $password);
            if ($success) {
                sendResponse(201, "Registration successful.");
            } else {
                sendResponse(500, "Registration failed.");
            }
        } catch (Exception $e) {
            sendResponse(500, "Server error: " . $e->getMessage());
        }
    }

    public function login() {
        file_put_contents('php://stdout', "DEBUG: Function reached\n");
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['email'], $data['password'])) {
                sendResponse(400, "Email and password are required.");
                return;
            }

            $email = trim($data['email']);
            $password = $data['password'];

            // Additional validations based on the Android client-side logic:
            if (strpos($email, ' ') !== false) {
                sendResponse(400, "Email cannot contain spaces.");
                return;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, "@gmail.com")) {
                sendResponse(400, "Invalid Email.");
                return;
            }

            $user = $this->user->login($email, $password);
            if ($user) {
                sendResponse(200, "Login successful.", ["user" => $user]);
            } else {
                sendResponse(401, "Invalid credentials.");
            }
        } catch (Exception $e) {
            sendResponse(500, "Server error: " . $e->getMessage());
        }
    }

    public function changePassword() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (!isset($data['email'], $data['old_password'], $data['new_password'])) {
                sendResponse(400, "Email, old password, and new password are required.");
                return;
            }

            $email = trim($data['email']);
            $oldPassword = $data['old_password'];
            $newPassword = $data['new_password'];

            // Get user by email
            $user = $this->user->getUserByEmail($email);
            if (!$user) {
                sendResponse(404, "User not found.");
                return;
            }

            // Verify old password
            if (!password_verify($oldPassword, $user['password'])) {
                sendResponse(401, "Incorrect old password.");
                return;
            }

            // Validate new password
            if (strlen($newPassword) < 8) {
                sendResponse(400, "New password must be at least 8 characters long.");
                return;
            }

            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update the password
            if ($this->user->updatePassword($email, $hashedPassword)) {
                sendResponse(200, "Password updated successfully.");
            } else {
                sendResponse(500, "Failed to update password.");
            }
        } catch (Exception $e) {
            sendResponse(500, "Server error: " . $e->getMessage());
        }
    }
}
?>
