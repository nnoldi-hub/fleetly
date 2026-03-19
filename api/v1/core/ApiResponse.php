<?php
/**
 * API Response Helper
 * 
 * Clasă pentru formatarea răspunsurilor JSON standardizate.
 */
class ApiResponse {
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $code = 200, $meta = []) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'Error', $code = 400, $errors = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
        
        if ($errors !== null) {
            $response['error']['details'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send paginated response
     */
    public static function paginated($data, $page, $perPage, $total, $message = 'Success') {
        http_response_code(200);
        
        $totalPages = ceil($total / $perPage);
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => (int)$total,
                    'total_pages' => (int)$totalPages,
                    'has_more' => $page < $totalPages
                ]
            ]
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send 401 Unauthorized
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    /**
     * Send 403 Forbidden
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send 404 Not Found
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    /**
     * Send 422 Validation Error
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send 429 Too Many Requests
     */
    public static function tooManyRequests($message = 'Too many requests') {
        self::error($message, 429);
    }
}
