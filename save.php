<?php
// Security: This file should be protected or removed in production
// It allows writing arbitrary content to the filesystem

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $content = $_POST['content'] ?? '';
    
    if (empty($content)) {
        http_response_code(400);
        echo "Error: No content provided";
        exit;
    }

    // Decode the JSON content
    $contentArray = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo "Error: Invalid JSON content";
        exit;
    }

    // Convert the array to HTML (or handle it as needed)
    $htmlContent = '';

    if (isset($contentArray['ops']) && is_array($contentArray['ops'])) {
        foreach ($contentArray['ops'] as $op) {
            if (isset($op['insert']['image'])) {
                // Validate image URL to prevent SSRF
                $imageUrl = filter_var($op['insert']['image'], FILTER_SANITIZE_URL);
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $htmlContent .= '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '">';
                }
            } elseif (isset($op['insert'])) {
                $htmlContent .= htmlspecialchars($op['insert'], ENT_QUOTES, 'UTF-8');
            }
        }
    }

    // Use a secure, unique filename to prevent overwriting
    $filename = 'saved_content_' . date('Y-m-d_His') . '_' . uniqid() . '.html';
    $filepath = __DIR__ . '/' . basename($filename); // Prevent directory traversal
    
    // Limit file size (e.g., 1MB)
    if (strlen($htmlContent) > 1048576) {
        http_response_code(413);
        echo "Error: Content too large";
        exit;
    }

    // Save the content to a file
    if (file_put_contents($filepath, $htmlContent) !== false) {
        echo "Content saved successfully to: " . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    } else {
        http_response_code(500);
        echo "Error: Failed to save content";
    }
} else {
    http_response_code(405);
    echo "Error: Method not allowed";
}
?>
