<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cat.php';

class CatController {
    private $catModel;

    public function __construct() {
        global $conn;
        $this->catModel = new Cat($conn);
    }

    public function getAllCats() {
        $cats = $this->catModel->getAllCats();
        echo json_encode($cats);
    }

    public function addCat() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['name'], $data['breed'], $data['gender'], $data['age'], $data['adopt'], $data['vaccination'], $data['adddate'])) {
            echo json_encode(["message" => "All fields are required"]);
            http_response_code(400);
            return;
        }

        $success = $this->catModel->addCat($data['name'], $data['breed'], $data['gender'], $data['age'], $data['adopt'], $data['vaccination'], $data['adddate']);
        echo json_encode(["message" => $success ? "Cat added successfully" : "Failed to add cat"]);
    }

    public function updateCat($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['name'], $data['breed'], $data['gender'], $data['age'], $data['adopt'], $data['vaccination'], $data['adddate'])) {
            echo json_encode(["message" => "All fields are required"]);
            http_response_code(400);
            return;
        }

        $success = $this->catModel->updateCat($id, $data['name'], $data['breed'], $data['gender'], $data['age'], $data['adopt'], $data['vaccination'], $data['adddate']);
        echo json_encode(["message" => $success ? "Cat updated successfully" : "Failed to update cat"]);
    }

    public function deleteCat($id) {
        $success = $this->catModel->deleteCat($id);
        echo json_encode(["message" => $success ? "Cat deleted successfully" : "Failed to delete cat"]);
    }
}
?>
