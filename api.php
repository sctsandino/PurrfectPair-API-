<?php
require_once __DIR__ . '/mobile/config.php';
header("Content-Type: application/json");

$response = [];

$requestUri = $_SERVER['REQUEST_URI']; // Example: /PurrfectPair-API-/api.php/login
$scriptName = $_SERVER['SCRIPT_NAME']; // Example: /PurrfectPair-API-/api.php

// Remove script name from request URI to get the endpoint
$endpoint = trim(str_replace($scriptName, '', $requestUri), '/');

if (empty($endpoint)) {
    $response['error'] = true;
    $response['message'] = "Request parameter is missing!";
    echo json_encode($response);
    exit;
}

file_put_contents("debug.log", "Extracted endpoint: " . $endpoint . PHP_EOL, FILE_APPEND);

file_put_contents("debug.log", "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . PHP_EOL, FILE_APPEND);



switch ($endpoint) {
    case 'login':
        login($conn);
        break;
    case 'register':
        register($conn);
        break;
    case 'getPosts':
        getPosts($conn);
        break;
    default:
        $response['error'] = true;
        $response['message'] = "Invalid API request: $endpoint";
        echo json_encode($response);
        break;
}

// Function to retrieve posts based on status
function getPosts($conn) {
    global $response;

    $status = $_GET['status'] ?? 'approved'; // Default to 'approved'

    try {
        // Prepare SQL query with correct named parameter
        $stmt = $conn->prepare("SELECT * FROM posts WHERE status = :status ORDER BY created_at DESC");
        $stmt->bindParam(":status", $status, PDO::PARAM_STR); // Bind parameter
        $stmt->execute();

        // Fetch all posts
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'error' => false,
            'message' => 'Posts retrieved successfully',
            'data' => $posts
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'error' => true,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}


function login($conn) {
    global $response;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        if (isset($data['email'], $data['password'])) {
            $email = htmlspecialchars(strip_tags($data['email']));
            $password = $data['password'];

            try {
                $query = $conn->prepare("SELECT id, fullname, email, contactNumber, facebookName, homeAddress, password FROM users WHERE email = ?");
                $query->execute([$email]);
                $user = $query->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    $response['error'] = false;
                    $response['message'] = "Login successful!";
                    $response['user'] = [
                        "id" => $user['id'],
                        "fullname" => $user['fullname'],
                        "email" => $user['email'],
                        "contactNumber" => $user['contactNumber'],
                        "facebookName" => $user['facebookName'],
                        "homeAddress" => $user['homeAddress']
                    ];
                } else {
                    $response['error'] = true;
                    $response['message'] = "Invalid email or password!";
                }
            } catch (PDOException $e) {
                $response['error'] = true;
                $response['message'] = "Database error: " . $e->getMessage();
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Missing email or password!";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Invalid request method!";
    }

    echo json_encode($response);
}

function register($conn) {
    global $response;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        if (isset($data['fullname'], $data['email'], $data['contactNumber'], $data['facebookName'], $data['homeAddress'], $data['password'])) {
            $fullname = htmlspecialchars(strip_tags($data['fullname']));
            $email = htmlspecialchars(strip_tags($data['email']));
            $contactNumber = htmlspecialchars(strip_tags($data['contactNumber']));
            $facebookName = htmlspecialchars(strip_tags($data['facebookName']));
            $homeAddress = htmlspecialchars(strip_tags($data['homeAddress']));
            $password = password_hash($data['password'], PASSWORD_BCRYPT);

            try {
                $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $checkEmail->execute([$email]);

                if ($checkEmail->rowCount() > 0) {
                    $response['error'] = true;
                    $response['message'] = "Email already exists!";
                } else {
                    $query = $conn->prepare("INSERT INTO users (fullname, email, contactNumber, facebookName, homeAddress, password) VALUES (?, ?, ?, ?, ?, ?)");

                    if ($query->execute([$fullname, $email, $contactNumber, $facebookName, $homeAddress, $password])) {
                        $response['error'] = false;
                        $response['message'] = "Registration successful!";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to register user.";
                    }
                }
            } catch (PDOException $e) {
                $response['error'] = true;
                $response['message'] = "Database error: " . $e->getMessage();
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Missing required fields!";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Invalid request method!";
    }

    echo json_encode($response);
}
