<?php
require_once __DIR__ . '/bootstrap.php';

if (XBOX_CLIENT_ID === '' || XBOX_CLIENT_SECRET === '') {
    header('Location: login.php?error=xbox_auth_failed');
    exit();
}

if (isset($_SESSION["client_id"])) {
    header("Location: index.html");
    exit();
}

if (isset($_GET['code'])) {
    try {
        $state = $_GET['state'] ?? '';
        $expectedState = $_SESSION['xbox_oauth_state'] ?? '';
        
        if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
            throw new Exception('Invalid Xbox OAuth state');
        }
        unset($_SESSION['xbox_oauth_state']);

        // 1. Exchange code for access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://login.microsoftonline.com/common/oauth2/v2.0/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => XBOX_CLIENT_ID,
            'client_secret' => XBOX_CLIENT_SECRET,
            'grant_type' => 'authorization_code',
            'code' => $_GET['code'],
            'redirect_uri' => XBOX_REDIRECT_URI,
            'scope' => 'openid profile email'
        ]));
        
        $response = curl_exec($ch);
        $tokenData = json_decode($response, true);
        curl_close($ch);

        if (!isset($tokenData['access_token'])) {
            throw new Exception('Xbox Auth Error: ' . ($tokenData['error_description'] ?? 'Missing access token'));
        }

        $accessToken = $tokenData['access_token'];

        // 2. Get user info from Microsoft Graph
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://graph.microsoft.com/v1.0/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $profile = json_decode($response, true);
        curl_close($ch);

        if (!isset($profile['id'])) {
            throw new Exception('Xbox Auth Error: Could not fetch profile');
        }

        $xbox_id = $profile['id'];
        $email = $profile['mail'] ?? $profile['userPrincipalName'] ?? null;
        $name = $profile['displayName'] ?? 'Xbox User';

        $pdo = db();
        
        // Check if user exists by xbox_id
        $stmt = $pdo->prepare("SELECT * FROM Client WHERE xbox_id = ?");
        $stmt->execute([$xbox_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            if ($email) {
                // Check if user exists by email
                $stmt = $pdo->prepare("SELECT * FROM Client WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Link existing account
                    $stmt = $pdo->prepare("UPDATE Client SET xbox_id = ?, email_verified = 1 WHERE id_client = ?");
                    $stmt->execute([$xbox_id, $user['id_client']]);
                }
            }

            if (!$user) {
                // Create new account
                $random_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Client (nom, email, mot_de_passe, email_verified, xbox_id) VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([$name, $email, $random_pass, $xbox_id]);
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
        
        if (function_exists('applyLoginSessionLifetime')) {
            applyLoginSessionLifetime(false);
        }
        
        header("Location: index.html");
        exit();
        
    } catch (Exception $e) {
        error_log('[XBOX AUTH ERROR] ' . $e->getMessage());
        header("Location: login.php?error=xbox_auth_failed");
        exit();
    }
} else {
    header("Location: xbox-login.php");
    exit();
}
