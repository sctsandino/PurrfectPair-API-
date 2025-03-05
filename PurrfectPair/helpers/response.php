<?php
function sendResponse($status, $message, $data = []) {
    http_response_code($status);
    echo json_encode(["message" => $message, "data" => $data]);
    exit;
}
?>
