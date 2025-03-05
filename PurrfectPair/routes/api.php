<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$authController = new AuthController();

$router->post('/register', function() use ($authController) {
    $authController->register();
});

$router->post('/login', function() use ($authController) {
    $authController->login();
});

$router->post('/change-password', function() use ($authController) {
    $authController->changePassword();
});
?>
