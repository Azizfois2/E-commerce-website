<?php
/**
 * api/community-builds.php — Community Build Showcases
 *
 * GET  ?action=list&sort=newest|popular&page=1
 * GET  ?action=view&id=123
 * POST { action: "publish", title: "My Build", description: "...", config: {...} }
 * POST { action: "interact", showcase_id: 123, type: "upvote|favorite" }
 */
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/rate-limiter.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$clientId = $_SESSION['client_id'] ?? null;

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $sort = $_GET['sort'] ?? 'newest';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $orderBy = "s.created_at DESC";
        if ($sort === 'popular') {
            $orderBy = "s.view_count DESC, upvotes DESC, s.created_at DESC";
        }

        $query = "
            SELECT s.id, s.title, s.description, s.image_gallery, s.view_count, s.created_at,
                   c.nom as author_name,
                   (SELECT COUNT(*) FROM community_build_interactions i WHERE i.showcase_id = s.id AND i.interaction_type = 'upvote') as upvotes,
                   (SELECT COUNT(*) FROM community_build_interactions i WHERE i.showcase_id = s.id AND i.interaction_type = 'favorite') as favorites,
                   EXISTS(SELECT 1 FROM community_build_interactions i WHERE i.showcase_id = s.id AND i.client_id = ? AND i.interaction_type = 'upvote') as user_upvoted,
                   EXISTS(SELECT 1 FROM community_build_interactions i WHERE i.showcase_id = s.id AND i.client_id = ? AND i.interaction_type = 'favorite') as user_favorited
            FROM community_build_showcases s
            LEFT JOIN Client c ON c.id_client = s.client_id
            ORDER BY $orderBy
            LIMIT ? OFFSET ?
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(1, $clientId, PDO::PARAM_INT);
        $stmt->bindValue(2, $clientId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->bindValue(4, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $showcases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON safely
        foreach ($showcases as &$s) {
            $s['image_gallery'] = json_decode($s['image_gallery'], true) ?: [];
        }

        $countStmt = $pdo->query("SELECT COUNT(*) FROM community_build_showcases");
        $total = (int)$countStmt->fetchColumn();

        $response = [
            'success' => true,
            'showcases' => $showcases,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ];
        
        file_put_contents(__DIR__ . '/../scratch/cb_debug.log', date('Y-m-d H:i:s') . " - GET /api/community-builds.php?action=list&page=$page&sort=$sort - Returned count: " . count($showcases) . " - Total: $total\n", FILE_APPEND);

        echo json_encode($response);
        exit;
    }

    if ($action === 'view') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid ID']);
            exit;
        }

        // Increment view count
        $pdo->prepare("UPDATE community_build_showcases SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

        $query = "
            SELECT s.*, c.nom as author_name,
                   (SELECT COUNT(*) FROM community_build_interactions i WHERE i.showcase_id = s.id AND i.interaction_type = 'upvote') as upvotes,
                   (SELECT COUNT(*) FROM community_build_interactions i WHERE i.showcase_id = s.id AND i.interaction_type = 'favorite') as favorites
            FROM community_build_showcases s
            LEFT JOIN Client c ON c.id_client = s.client_id
            WHERE s.id = ?
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        $showcase = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$showcase) {
            http_response_code(404);
            echo json_encode(['error' => 'Showcase not found']);
            exit;
        }

        $showcase['config_json'] = json_decode($showcase['config_json'], true);
        $showcase['image_gallery'] = json_decode($showcase['image_gallery'], true);

        // Check if current user has interacted
        $userInteractions = ['upvote' => false, 'favorite' => false];
        if ($clientId) {
            $iStmt = $pdo->prepare("SELECT interaction_type FROM community_build_interactions WHERE showcase_id = ? AND client_id = ?");
            $iStmt->execute([$id, $clientId]);
            $interactions = $iStmt->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('upvote', $interactions)) $userInteractions['upvote'] = true;
            if (in_array('favorite', $interactions)) $userInteractions['favorite'] = true;
        }
        $showcase['user_interactions'] = $userInteractions;

        echo json_encode(['success' => true, 'showcase' => $showcase]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

if ($method === 'POST') {
    if (!$clientId) {
        http_response_code(401);
        echo json_encode(['error' => 'Must be logged in to participate in the community']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'publish') {
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $config = $input['config'] ?? null;
        $orderId = !empty($input['order_id']) ? (int)$input['order_id'] : null;

        if (empty($title) || empty($config) || !is_array($config)) {
            echo json_encode(['error' => 'Title and valid configuration are required']);
            exit;
        }

        $configJson = json_encode($config);
        $galleryJson = json_encode([]);

        $stmt = $pdo->prepare("
            INSERT INTO community_build_showcases (client_id, order_id, title, description, config_json, image_gallery)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$clientId, $orderId, $title, $description, $configJson, $galleryJson]);

        echo json_encode(['success' => true, 'message' => 'Your build has been published to the community!', 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'interact') {
        $showcaseId = (int)($input['showcase_id'] ?? 0);
        $type = $input['type'] ?? '';

        if ($showcaseId <= 0 || !in_array($type, ['upvote', 'favorite'])) {
            echo json_encode(['error' => 'Invalid interaction parameters']);
            exit;
        }

        // Check if interaction exists
        $stmt = $pdo->prepare("SELECT id FROM community_build_interactions WHERE showcase_id = ? AND client_id = ? AND interaction_type = ?");
        $stmt->execute([$showcaseId, $clientId, $type]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Remove interaction (toggle off)
            $pdo->prepare("DELETE FROM community_build_interactions WHERE id = ?")->execute([$exists['id']]);
            $status = 'removed';
        } else {
            // Add interaction (toggle on)
            $pdo->prepare("INSERT INTO community_build_interactions (showcase_id, client_id, interaction_type) VALUES (?, ?, ?)")
                ->execute([$showcaseId, $clientId, $type]);
            $status = 'added';
        }

        // Get updated counts
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM community_build_interactions WHERE showcase_id = ? AND interaction_type = ?");
        $cStmt->execute([$showcaseId, $type]);
        $count = (int)$cStmt->fetchColumn();

        echo json_encode(['success' => true, 'status' => $status, 'count' => $count]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}
