<?php
/**
 * Chatbot API Endpoint
 * Handles chatbot queries and returns responses from database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'admin/includes/config.php';

$response = [
    'success' => false,
    'message' => '',
    'response' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = isset($input['message']) ? trim(strtolower($input['message'])) : '';
    
    if (empty($query)) {
        $response['message'] = 'Please enter a message.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Search for matching keywords in the database
        $sql = "SELECT * FROM chatbot_responses WHERE active = 1 ORDER BY id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $responses = $stmt->fetchAll();
        
        $bestMatch = null;
        $maxMatches = 0;
        
        foreach ($responses as $resp) {
            $keywords = array_map('trim', explode(',', strtolower($resp['keyword'])));
            $matches = 0;
            
            foreach ($keywords as $keyword) {
                if (strpos($query, $keyword) !== false) {
                    $matches++;
                }
            }
            
            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $bestMatch = $resp;
            }
        }
        
        if ($bestMatch) {
            $response['success'] = true;
            $response['response'] = $bestMatch['response'];
        } else {
            // Default response if no match found
            $response['success'] = true;
            $response['response'] = "I'm sorry, I didn't understand that. You can ask me about our services, contact information, pricing, or any other questions about AI-Solutions. How can I help you today?";
        }
        
    } catch (PDOException $e) {
        $response['message'] = 'Sorry, I encountered an error. Please try again later.';
        error_log("Chatbot error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
