<?php
function sendResponse($statusCode, $message, $data = null) {
    header("Content-Type: application/json");
    http_response_code($statusCode);

    $response = ["message" => $message];
    if ($data !== null) {
        $response["data"] = $data;
    }

    echo json_encode($response, JSON_PRETTY_PRINT);
    flush();
    exit();
}
?>
