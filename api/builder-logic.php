<?php
/**
 * api/builder-logic.php — Hardware Constraint & Validation Engine
 *
 * POST { action: "validate", products: [1, 5, 12, ...] }
 */
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'validate') {
        $productIds = $input['products'] ?? [];
        if (empty($productIds) || !is_array($productIds)) {
            echo json_encode(['success' => true, 'wattage' => 0, 'warnings' => [], 'clearance_issues' => []]);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        // 1. Calculate Wattage
        $stmt = $pdo->prepare("SELECT SUM(watts_tdp) as total_tdp, SUM(peak_watts) as total_peak, MAX(recommended_min_psu_watts) as min_psu FROM parts_power_specifications WHERE product_id IN ($placeholders)");
        $stmt->execute($productIds);
        $power = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Chassis Clearance Specs
        $stmt = $pdo->prepare("SELECT product_id, type, max_gpu_length_mm, gpu_length_mm, max_cpu_cooler_height_mm, cooler_height_mm, max_radiator_size, radiator_size FROM chassis_clearance_specs WHERE product_id IN ($placeholders)");
        $stmt->execute($productIds);
        $clearances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $case = null;
        $gpu = null;
        $cooler = null;

        foreach ($clearances as $c) {
            if ($c['type'] === 'case') $case = $c;
            if ($c['type'] === 'gpu') $gpu = $c;
            if ($c['type'] === 'cooler' || $c['type'] === 'aio_radiator') $cooler = $c;
        }

        $clearanceIssues = [];
        if ($case && $gpu && $case['max_gpu_length_mm'] && $gpu['gpu_length_mm']) {
            if ($gpu['gpu_length_mm'] > $case['max_gpu_length_mm']) {
                $clearanceIssues[] = "GPU length ({$gpu['gpu_length_mm']}mm) exceeds Case max clearance ({$case['max_gpu_length_mm']}mm).";
            }
        }
        if ($case && $cooler && $case['max_cpu_cooler_height_mm'] && $cooler['cooler_height_mm']) {
            if ($cooler['cooler_height_mm'] > $case['max_cpu_cooler_height_mm']) {
                $clearanceIssues[] = "CPU Cooler height ({$cooler['cooler_height_mm']}mm) exceeds Case max clearance ({$case['max_cpu_cooler_height_mm']}mm).";
            }
        }

        // 3. Component Compatibility Rules
        $stmt = $pdo->prepare("SELECT * FROM component_compatibility_rules");
        $stmt->execute();
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // In a real scenario, we would map the selected products' attributes (socket, chipset) against these rules.
        // For this implementation, we return the global rules and let the frontend/backend flag warnings.
        $warnings = [];
        // (Mock implementation of rule matching)

        echo json_encode([
            'success' => true,
            'wattage' => [
                'total_tdp' => (int)$power['total_tdp'],
                'peak_watts' => (int)$power['total_peak'],
                'recommended_psu' => (int)$power['min_psu']
            ],
            'clearance_issues' => $clearanceIssues,
            'compatibility_rules_loaded' => count($rules)
        ]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
