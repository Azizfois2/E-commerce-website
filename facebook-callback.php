<?php
require_once __DIR__ . '/bootstrap.php';

if (FB_APP_ID === '' || FB_APP_SECRET === '') {
    header('Location: login.php?error=fb_auth_failed');
    exit();
}

function safeRedirectTarget(?string $target, string $fallback = 'index.html'): string
{
    $target = trim((string) $target);
    if ($target === '') return $fallback;
    if (preg_match('#^(https?://|//|javascript:)#i', $target) || strpos($target, '..') !== false || strpbrk($target, "\r\n") !== false) {
        return $fallback;
    }
    return $target;
}

if (isset($_SESSION["client_id"])) {
    header("Location: index.html");
    exit();
}

if (isset($_GET['error'])) {
    header('Location: login.php?error=fb_denied');
    exit();
}

if (isset($_GET['code'])) {
    try {
        $state = $_GET['state'] ?? '';
        $expectedState = $_SESSION['fb_oauth_state'] ?? '';
        
        if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
            throw new Exception('Invalid Facebook OAuth state');
        }
        unset($_SESSION['fb_oauth_state']);

        // 1. Exchange code for access token
        $tokenUrl = "https://graph.facebook.com/v25.0/oauth/access_token?" . http_build_query([
            'client_id' => FB_APP_ID,
            'redirect_uri' => FB_REDIRECT_URI,
            'client_secret' => FB_APP_SECRET,
            'code' => $_GET['code'],
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $tokenData = json_decode($response, true);
        curl_close($ch);

        if (!isset($tokenData['access_token'])) {
            throw new Exception('Facebook Auth Error: ' . ($tokenData['error']['message'] ?? 'Missing access token'));
        }

        $accessToken = $tokenData['access_token'];

        // 2. Get user profile info
        $profileUrl = "https://graph.facebook.com/me?" . http_build_query([
            'fields' => 'id,name,email',
            'access_token' => $accessToken
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $profileUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $profile = json_decode($response, true);
        curl_close($ch);

        if (!isset($profile['id'])) {
            throw new Exception('Facebook Auth Error: Could not fetch profile');
        }

        $fb_id = $profile['id'];
        $email = $profile['email'] ?? null;
        $name = $profile['name'] ?? 'Facebook User';

        $pdo = db();
        
        // Check if user exists by facebook_id
        $stmt = $pdo->prepare("SELECT * FROM Client WHERE facebook_id = ?");
        $stmt->execute([$fb_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            if ($email) {
                // Check if user exists by email
                $stmt = $pdo->prepare("SELECT * FROM Client WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Link existing account
                    $stmt = $pdo->prepare("UPDATE Client SET facebook_id = ?, email_verified = 1 WHERE id_client = ?");
                    $stmt->execute([$fb_id, $user['id_client']]);
                }
            }

            if (!$user) {
                // Create new account
                $random_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Client (nom, email, mot_de_passe, email_verified, facebook_id) VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([$name, $email, $random_pass, $fb_id]);
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
        
        // If applyLoginSessionLifetime function exists (from application.php)
        if (function_exists('applyLoginSessionLifetime')) {
            applyLoginSessionLifetime(false);
        }
        
        header("Location: index.html");
        exit();
        
    } catch (Exception $e) {
        error_log('[FB AUTH ERROR] ' . $e->getMessage());
        header("Location: login.php?error=fb_auth_failed");
        exit();
    }
} else {
    header("Location: facebook-login.php");
    exit();
}
