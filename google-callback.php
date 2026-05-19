<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    header('Location: login.php?error=google_auth_failed');
    exit();
}

function safeRedirectTarget(?string $target, string $fallback = 'index.html'): string
{
    $target = trim((string) $target);
    if ($target === '') {
        return $fallback;
    }

    if (
        preg_match('#^(https?://|//|javascript:)#i', $target) ||
        strpos($target, '..') !== false ||
        strpbrk($target, "\r\n") !== false
    ) {
        return $fallback;
    }

    return $target;
}

if (isset($_SESSION["client_id"])) {
    $next = safeRedirectTarget($_GET['next'] ?? $_GET['state'] ?? ($_SESSION['google_oauth_next'] ?? null));
    header("Location: $next");
    exit();
}

$client = new \Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");
$client->addScope("openid");

if (isset($_GET['code'])) {
    try {
        $state = $_GET['state'] ?? '';
        $expectedState = $_SESSION['google_oauth_state'] ?? '';
        if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
            throw new Exception('Invalid Google OAuth state');
        }

        $next = safeRedirectTarget($_SESSION['google_oauth_next'] ?? null);
        unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_next']);

        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            throw new Exception('Google Auth Error: ' . $token['error']);
        }

        if (empty($token['access_token']) || empty($token['id_token'])) {
            throw new Exception('Google Auth Error: missing token data');
        }
        
        $client->setAccessToken($token['access_token']);

        // Get profile info from the ID token payload
        // This avoids needing the massive google/apiclient-services package
        $payload = $client->verifyIdToken($token['id_token']);
        
        if (!$payload) {
            throw new Exception('Invalid ID token');
        }
        
        $email = $payload['email'];
        $name = $payload['name'];
        $google_id = $payload['sub']; // 'sub' is the unique Google ID
        
        $pdo = db();
        
        // Check if user exists by google_id
        $stmt = $pdo->prepare("SELECT * FROM Client WHERE google_id = ?");
        $stmt->execute([$google_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Check if user exists by email
            $stmt = $pdo->prepare("SELECT * FROM Client WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Update existing user with google_id
                $stmt = $pdo->prepare("UPDATE Client SET google_id = ?, email_verified = 1 WHERE id_client = ?");
                $stmt->execute([$google_id, $user['id_client']]);
            } else {
                // Create new user
                $random_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Client (nom, email, mot_de_passe, email_verified, google_id) VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([$name, $email, $random_pass, $google_id]);
                $new_id = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("SELECT * FROM Client WHERE id_client = ?");
                $stmt->execute([$new_id]);
                $user = $stmt->fetch();
            }
        }
        
        // Log in the user
        session_regenerate_id(true);
        $_SESSION["client_id"] = $user["id_client"];
        $_SESSION["client_nom"] = $user["nom"];
        $_SESSION["client_email"] = $user["email"];
        applyLoginSessionLifetime(false);
        
        header("Location: $next");
        exit();
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_next']);
        header("Location: login.php?error=google_auth_failed");
        exit();
    }
} else {
    // Generate auth URL and redirect
    $next = safeRedirectTarget($_GET['next'] ?? $_GET['state'] ?? 'index.html');
    $_SESSION['google_oauth_state'] = bin2hex(random_bytes(24));
    $_SESSION['google_oauth_next'] = $next;
    $client->setState($_SESSION['google_oauth_state']);

    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit();
}
