<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $image = $_FILES['image'];
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($image['name']);
    
    // Create uploads directory if it does not exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Move uploaded file to the target directory
    if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
        // Respond with the URL of the uploaded image
        echo json_encode(['url' => $uploadFile]);
    } else {
        // Error handling
        http_response_code(400);
        echo json_encode(['error' => 'Failed to upload image']);
    }
}
?>
