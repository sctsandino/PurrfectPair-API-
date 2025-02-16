<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function register($data) {
        if (!isset($data['email'], $data['full_name'], $data['phone_number'], $data['password'])) {
            return ["status" => false, "message" => "All fields are required."];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ["status" => false, "message" => "Invalid email format."];
        }

        if (!preg_match('/^09[0-9]{9}$|^\+639[0-9]{9}$/', $data['phone_number'])) {
            return ["status" => false, "message" => "Invalid phone number format."];
        }

        return $this->user->register($data['email'], $data['full_name'], $data['phone_number'], $data['password']);
    }

    public function login($data) {
        if (!isset($data['email'], $data['password'])) {
            return ["status" => false, "message" => "Email and password are required."];
        }

        return $this->user->login($data['email'], $data['password']);
    }

    public function logout($headers) {
        if (!isset($headers['Authorization'])) {
            return ["status" => false, "message" => "Authorization token is required."];
        }

        $token = $this->extractToken($headers['Authorization']);
        return $this->user->logout($token);
    }

    public function validateToken($headers) {
        if (!isset($headers['Authorization'])) {
            return ["status" => false, "message" => "Authorization token is required."];
        }

        $token = $this->extractToken($headers['Authorization']);
        return $this->user->validateToken($token);
    }

    // Helper function to extract the token
    private function extractToken($authHeader) {
        return str_replace("Bearer ", "", $authHeader);
    }
}
?>
