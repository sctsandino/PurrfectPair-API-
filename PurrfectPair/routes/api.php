<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CatController.php';

$authController = new AuthController();
$catController = new CatController();

// Auth routes
$router->post('/register', function() use ($authController) {
    $authController->register();
});

$router->post('/login', function() use ($authController) {
    $authController->login();
});

$router->post('/change-password', function() use ($authController) {
    $authController->changePassword();
});

// Cat routes
$router->get('/cats', function() use ($catController) {
    $catController->getAllCats();
});

$router->post('/cats', function() use ($catController) {
    $catController->addCat();
});

$router->put('/cats/{id}', function($id) use ($catController) {
    $catController->updateCat($id);
});

$router->delete('/cats/{id}', function($id) use ($catController) {
    $catController->deleteCat($id);
});
?>
