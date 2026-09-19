<?php
/**
 * includes/api_auth.php
 * KOMS API Authentication & Authorization Infrastructure
 * 
 * Provides:
 * - Cryptographically signed JWT/HMAC token issuance and verification
 * - Centralized CORS handling
 * - Standardized JSON response helpers
 * - Strict role-based access control & identity extraction
 * - Dojo-level data isolation helpers
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', getenv('JWT_SECRET') ?: 'koms_secret_hmac_key_2026_martial_arts_platform');
}
if (!defined('TOKEN_EXPIRY')) {
    define('TOKEN_EXPIRY', 60 * 60 * 24 * 7); // 7 days token validity
}

/**
 * Send standard CORS headers and handle preflight OPTIONS
 */
function handle_api_cors(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    $allowed_origins = [
        'http://localhost',
        'http://localhost:8080',
        'http://127.0.0.1',
        'http://127.0.0.1:8080',
        'https://koms-backend.onrender.com'
    ];

    if (in_array($origin, $allowed_origins, true) || APP_ENV === 'development') {
        header("Access-Control-Allow-Origin: " . ($origin !== '*' ? $origin : '*'));
    } else {
        header("Access-Control-Allow-Origin: https://koms-backend.onrender.com");
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json; charset=UTF-8");

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

/**
 * Standard Success Response Format
 */
function send_api_response($data = null, string $message = "Success", int $status_code = 200): void {
    http_response_code($status_code);
    echo json_encode([
        "success" => true,
        "message" => $message,
        "data" => $data,
        "errors" => []
    ], JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Standard Error Response Format
 */
function send_api_error(string $message = "An error occurred", $errors = [], int $status_code = 400): void {
    http_response_code($status_code);
    if (!is_array($errors)) {
        $errors = empty($errors) ? [] : [$errors];
    }
    echo json_encode([
        "success" => false,
        "message" => $message,
        "data" => null,
        "errors" => $errors
    ], JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Helper: Base64Url encode/decode
 */
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Create a cryptographically signed HMAC token for an authenticated user
 */
function create_api_token(array $user, int $expiry = TOKEN_EXPIRY): string {
    $header = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payloadData = [
        'sub' => (int)$user['id'],
        'member_id' => $user['member_id'] ?? null,
        'email' => $user['email'] ?? '',
        'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        'role' => $user['role'] ?? 'student',
        'dojo_id' => isset($user['dojo_id']) ? (int)$user['dojo_id'] : null,
        'iat' => time(),
        'exp' => time() + $expiry
    ];
    $payload = base64url_encode(json_encode($payloadData));
    $signature = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$signature";
}

/**
 * Verify HMAC token and return decoded claims or null
 */
function verify_api_token(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    list($header64, $payload64, $signature64) = $parts;
    $expectedSignature = base64url_encode(hash_hmac('sha256', "$header64.$payload64", JWT_SECRET, true));

    if (!hash_equals($expectedSignature, $signature64)) {
        return null;
    }

    $payload = json_decode(base64url_decode($payload64), true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null; // Expired or malformed
    }

    return $payload;
}

/**
 * Extract Bearer token from incoming request headers
 */
function get_bearer_token(): ?string {
    $authHeader = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = trim($value);
                break;
            }
        }
    }

    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }

    // Fallback: check query parameter ?token= for SSE/EventSource
    if (!empty($_GET['token'])) {
        return trim($_GET['token']);
    }

    return null;
}

/**
 * Resolve dojo_id for user based on role and active memberships
 */
function resolve_user_dojo_id(PDO $pdo, int $user_id, string $role): ?int {
    try {
        if ($role === 'master') {
            $stmt = $pdo->prepare("SELECT id FROM dojos WHERE master_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$user_id]);
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        } elseif ($role === 'student' || $role === 'senior') {
            $stmt = $pdo->prepare("SELECT dojo_id FROM dojo_memberships WHERE student_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        }
    } catch (Exception $e) {
        // Return null on error
    }
    return null;
}

/**
 * Authenticate API request.
 * Derives authenticated user identity from Bearer token, with session fallback for web-based AJAX.
 * If $required is true, aborts with HTTP 401 on authentication failure.
 */
function authenticate_api_request(PDO $pdo, bool $required = true): ?array {
    handle_api_cors();

    $token = get_bearer_token();
    if ($token) {
        $claims = verify_api_token($token);
        if ($claims && isset($claims['sub'])) {
            $stmt = $pdo->prepare("SELECT id, member_id, first_name, last_name, email, role, status FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$claims['sub']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['status'] === 'active') {
                $dojo_id = $claims['dojo_id'] ?? resolve_user_dojo_id($pdo, (int)$user['id'], $user['role']);
                return [
                    'user_id' => (int)$user['id'],
                    'member_id' => $user['member_id'],
                    'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'dojo_id' => $dojo_id,
                    'auth_type' => 'token'
                ];
            }
        }
    }

    // Web session fallback for same-origin AJAX calls
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, member_id, first_name, last_name, email, role, status FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['status'] === 'active') {
            $dojo_id = $_SESSION['dojo_id'] ?? resolve_user_dojo_id($pdo, (int)$user['id'], $user['role']);
            return [
                'user_id' => (int)$user['id'],
                'member_id' => $user['member_id'],
                'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                'email' => $user['email'],
                'role' => $user['role'],
                'dojo_id' => $dojo_id,
                'auth_type' => 'session'
            ];
        }
    }

    if ($required) {
        send_api_error("Authentication required. Please provide a valid Bearer token.", [], 401);
    }

    return null;
}

/**
 * Enforce authorized roles for the current API caller
 */
function require_api_role(array $caller, array $allowed_roles): void {
    if (!in_array($caller['role'], $allowed_roles, true)) {
        send_api_error("Access denied. Required role: " . implode(', ', $allowed_roles), [], 403);
    }
}

/**
 * Verify dojo isolation: ensures a student or master cannot access a different dojo's resources
 */
function assert_dojo_access(PDO $pdo, array $caller, int $target_student_id): void {
    // Super admin, grand master, and admin can access globally
    if (in_array($caller['role'], ['super_admin', 'grand_master', 'admin'], true)) {
        return;
    }

    // Student can only access their own record
    if ($caller['role'] === 'student') {
        if ($caller['user_id'] !== $target_student_id) {
            send_api_error("Access denied: You may only view your own records.", [], 403);
        }
        return;
    }

    // Master or Senior can only access students enrolled in their dojo
    if (in_array($caller['role'], ['master', 'senior'], true)) {
        $master_dojo_id = $caller['dojo_id'] ?? resolve_user_dojo_id($pdo, $caller['user_id'], $caller['role']);
        if (!$master_dojo_id) {
            send_api_error("Access denied: No active dojo associated with your account.", [], 403);
        }

        $stmt = $pdo->prepare("SELECT id FROM dojo_memberships WHERE student_id = ? AND dojo_id = ? LIMIT 1");
        $stmt->execute([$target_student_id, $master_dojo_id]);
        if (!$stmt->fetch()) {
            send_api_error("Access denied: Target student does not belong to your dojo.", [], 403);
        }
        return;
    }

    send_api_error("Access denied.", [], 403);
}
