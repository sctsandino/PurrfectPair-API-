<?php
require_once __DIR__ . '/../config/Database.php';

class Cat {
    private $conn;
    private $table = "cats";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllCats() {
        $sql = "SELECT * FROM " . $this->table;
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addCat($name, $breed, $gender, $age, $adopt, $vaccination, $adddate) {
        $sql = "INSERT INTO " . $this->table . " (name, breed, gender, age, adopt, vaccination, adddate) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssss", $name, $breed, $gender, $age, $adopt, $vaccination, $adddate);
        return $stmt->execute();
    }

    public function updateCat($id, $name, $breed, $gender, $age, $adopt, $vaccination, $adddate) {
        $sql = "UPDATE " . $this->table . " SET 
                name = ?, breed = ?, gender = ?, age = ?, adopt = ?, vaccination = ?, adddate = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssi", $name, $breed, $gender, $age, $adopt, $vaccination, $adddate, $id);
        return $stmt->execute();
    }

    public function deleteCat($id) {
        $sql = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
