<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/CatController.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/AltoRouter.php';


$router = new AltoRouter();
$db = new Database();
$authController = new AuthController($db);
$catController = new CatController($db);

// Auth routes
$router->map('POST', '/register', function() use ($authController) {
    $authController->register();
});

$router->map('POST', '/login', function() use ($authController) {
    $authController->login();
});

$router->map('POST', '/change-password', function() use ($authController) {
    $authController->changePassword();
});

// Cat routes
$router->map('GET', '/cats', function() use ($catController) {
    $catController->getAllCats();
});

$router->map('POST', '/cats', function() use ($catController) {
    $catController->addCat();
});

$router->map('PUT', '/cats/{id}', function($id) use ($catController) {
    $catController->updateCat($id);
});

$router->map('DELETE', '/cats/{id}', function($id) use ($catController) {
    $catController->deleteCat($id);
});
?>
