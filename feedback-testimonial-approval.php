<?php
require_once 'admin-helpers.php';

$pdo = db();
adminEnsureAdminSuiteTables($pdo);

$token = (string) ($_GET['token'] ?? '');
$result = adminAcceptFeedbackApproval($pdo, $token);
$isSuccess = (bool) ($result['success'] ?? false);
$message = (string) ($result['message'] ?? 'This approval link could not be processed.');
$title = $isSuccess ? 'Feedback Approved' : 'Approval Link Problem';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= adminH($title) ?> | Maroc PC</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #050607;
            --panel: #0d1015;
            --border: rgba(0, 245, 212, 0.22);
            --text: #f4f7fb;
            --muted: #96a0b3;
            --cyan: #00f5d4;
            --danger: #ff4d6d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at top, rgba(0,245,212,0.12), transparent 34rem),
                var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .approval-card {
            width: min(100%, 560px);
            border: 1px solid var(--border);
            border-radius: 18px;
            background: rgba(13, 16, 21, 0.92);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.4);
            padding: 34px;
            text-align: center;
        }

        .approval-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 22px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: <?= $isSuccess ? 'rgba(0,245,212,0.12)' : 'rgba(255,77,109,0.1)' ?>;
            color: <?= $isSuccess ? 'var(--cyan)' : 'var(--danger)' ?>;
            border: 1px solid <?= $isSuccess ? 'rgba(0,245,212,0.35)' : 'rgba(255,77,109,0.35)' ?>;
            font-size: 34px;
            font-weight: 800;
        }

        h1 {
            margin: 0 0 14px;
            font-size: clamp(2rem, 5vw, 3.2rem);
            line-height: 1;
            letter-spacing: 0;
        }

        p {
            margin: 0 auto 26px;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
            max-width: 42rem;
        }

        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 22px;
            border-radius: 10px;
            background: var(--cyan);
            color: #00110f;
            text-decoration: none;
            font-weight: 800;
        }
    </style>
</head>
<body>
    <main class="approval-card">
        <div class="approval-icon" aria-hidden="true"><?= $isSuccess ? '✓' : '!' ?></div>
        <h1><?= adminH($title) ?></h1>
        <p><?= adminH($message) ?></p>
        <a href="<?= adminH(APP_URL . 'index.php#testimonials') ?>">Return to Maroc PC</a>
    </main>
</body>
</html>
