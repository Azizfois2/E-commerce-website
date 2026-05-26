<?php
/**
 * Server-Side PDF Ticket Generator
 * Generates pickup tickets using FPDF library
 */

require_once __DIR__ . '/config.php';

// Simple FPDF-based PDF generator (no external dependencies needed)
class PickupTicketPDF {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function generate() {
        // Create PDF using basic PHP (no library needed for simple layout)
        $pdf = $this->createSimplePDF();
        return $pdf;
    }
    
    private function createSimplePDF() {
        // Use TCPDF if available, otherwise create HTML that can be printed
        if (class_exists('TCPDF')) {
            return $this->generateWithTCPDF();
        } else {
            return $this->generateHTMLForPrint();
        }
    }
    
    private function generateHTMLForPrint() {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Maroc PC Pickup Ticket</title>
    <style>
        @page { 
            size: A4; 
            margin: 0; 
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #fff;
            padding: 40px;
        }
        .ticket {
            max-width: 800px;
            margin: 0 auto;
            border: 3px solid #00f5d4;
            padding: 40px;
            background: #0f172a;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #00f5d4;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #00f5d4;
            font-size: 36px;
            letter-spacing: 4px;
            margin-bottom: 10px;
        }
        .header p {
            color: #94a3b8;
            font-size: 16px;
        }
        .content {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .section {
            flex: 1;
        }
        .section h3 {
            color: #00f5d4;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .section p {
            margin: 8px 0;
            font-size: 14px;
        }
        .label {
            color: #94a3b8;
        }
        .value {
            font-weight: bold;
            color: #fff;
        }
        .verification-code {
            background: rgba(0, 245, 212, 0.1);
            border: 2px dashed #00f5d4;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
        }
        .verification-code .code {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #00f5d4;
        }
        .store-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-left: 4px solid #00f5d4;
            margin-bottom: 30px;
        }
        .store-info h3 {
            color: #00f5d4;
            margin-bottom: 10px;
        }
        .store-info h4 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .store-info p {
            color: #cbd5e1;
            margin: 5px 0;
        }
        .items {
            margin-bottom: 30px;
        }
        .items h3 {
            color: #00f5d4;
            border-bottom: 1px solid rgba(0, 245, 212, 0.3);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
        }
        .item-name {
            color: #fff;
        }
        .item-price {
            color: #00f5d4;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 245, 212, 0.3);
            color: #94a3b8;
            font-size: 12px;
        }
        .footer p {
            margin: 5px 0;
        }
        @media print {
            body {
                background: #0f172a;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>MAROC PC</h1>
            <p>AUTHORIZED STORE PICKUP TICKET</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h3>ORDER DETAILS</h3>
                <p><span class="label">Order #:</span> <span class="value">' . htmlspecialchars($this->data['order_id']) . '</span></p>
                <p><span class="label">Customer:</span> <span class="value">' . htmlspecialchars($this->data['customer_name']) . '</span></p>
                <p><span class="label">Total:</span> <span class="value">' . htmlspecialchars($this->data['total']) . ' MAD</span></p>
                <p><span class="label">Date:</span> <span class="value">' . htmlspecialchars($this->data['date']) . '</span></p>
            </div>
            
            <div class="section">
                <h3>VERIFICATION CODE</h3>
                <div class="verification-code">
                    <div class="code">' . htmlspecialchars($this->data['verification_code']) . '</div>
                </div>
            </div>
        </div>
        
        <div class="store-info">
            <h3>📍 PICKUP LOCATION</h3>
            <h4>' . htmlspecialchars($this->data['store_name']) . '</h4>
            <p>' . htmlspecialchars($this->data['store_address']) . '</p>
            <p>' . htmlspecialchars($this->data['store_hours']) . '</p>
        </div>
        
        <div class="items">
            <h3>ITEMS TO COLLECT</h3>';
        
        foreach ($this->data['items'] as $item) {
            $html .= '<div class="item">
                <span class="item-name">' . htmlspecialchars($item['quantity']) . 'x ' . htmlspecialchars($item['name']) . '</span>
                <span class="item-price">' . htmlspecialchars($item['price']) . ' MAD</span>
            </div>';
        }
        
        $html .= '</div>
        
        <div class="footer">
            <p>Please bring this ticket and a valid ID to collect your order.</p>
            <p>For assistance, contact: support@marocpc.com | +212 XXX-XXXX</p>
        </div>
    </div>
    
    <script>
        // Auto-trigger print dialog
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>';
        
        return $html;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid input data');
        }
        
        // Validate required fields
        $required = ['order_id', 'customer_name', 'total', 'verification_code', 'store_name', 'store_address', 'store_hours', 'items'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Generate PDF
        $generator = new PickupTicketPDF($input);
        $html = $generator->generate();
        
        // Return HTML that can be printed to PDF
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => 'Ticket generated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle GET request - show test form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Pickup Ticket Generator</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #1e293b; color: #fff; }
        button { background: #00f5d4; color: #0f172a; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        button:hover { background: #00d4b8; }
        .result { margin-top: 20px; padding: 15px; background: #334155; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>🎫 Test Pickup Ticket Generator</h1>
    <p>Click the button below to generate a test ticket:</p>
    <button onclick="generateTestTicket()">Generate Test Ticket</button>
    <div class="result" id="result"></div>
    
    <script>
        async function generateTestTicket() {
            const data = {
                order_id: '#000123',
                customer_name: 'Test Customer',
                total: '5,999.00',
                date: new Date().toLocaleString(),
                verification_code: 'PICKUP-TEST-1234',
                store_name: 'Maroc PC - Casablanca Center',
                store_address: '123 Boulevard Mohammed V, Casablanca',
                store_hours: 'Mon-Sat 9:00 AM - 8:00 PM',
                items: [
                    { quantity: 1, name: 'AMD Ryzen 7 5800X', price: '2,999.00' },
                    { quantity: 1, name: 'NVIDIA RTX 3070', price: '3,000.00' }
                ]
            };
            
            try {
                const response = await fetch('generate-pickup-ticket-pdf.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Open HTML in new window for printing
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(result.html);
                    printWindow.document.close();
                    
                    document.getElementById('result').innerHTML = '<p style="color: #00e676;">✅ Ticket generated! A new window opened with the printable ticket.</p>';
                } else {
                    document.getElementById('result').innerHTML = '<p style="color: #ff3d5a;">❌ Error: ' + result.error + '</p>';
                }
            } catch (error) {
                document.getElementById('result').innerHTML = '<p style="color: #ff3d5a;">❌ Error: ' + error.message + '</p>';
            }
        }
    </script>
</body>
</html>
