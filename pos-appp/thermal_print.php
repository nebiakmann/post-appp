<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/printer_config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $printer = new ThermalPrinter();
    
    switch ($action) {
        case 'print_receipt':
            $orderId = (int)($_POST['order_id'] ?? 0);
            
            if ($orderId <= 0) {
                throw new Exception('Geçersiz sipariş numarası');
            }
            
            $orderDetails = get_order_details($orderId);
            if (empty($orderDetails['order'])) {
                throw new Exception('Sipariş bulunamadı');
            }
            
            // Sipariş verilerini düzenle
            $order = $orderDetails['order'];
            $order['items'] = $orderDetails['items'];
            
            if ($printer->printReceipt($order)) {
                echo json_encode(['success' => true, 'message' => 'Fiş yazdırıldı']);
            } else {
                throw new Exception('Yazıcıya gönderilemedi. Yazıcı ayarlarını kontrol edin.');
            }
            break;
            
        case 'test_print':
            if ($printer->printTestPage()) {
                echo json_encode(['success' => true, 'message' => 'Test sayfası yazdırıldı']);
            } else {
                throw new Exception('Test sayfası yazdırılamadı. Yazıcı ayarlarını kontrol edin.');
            }
            break;
            
        case 'get_printer_status':
            $config = $printer->getConfig();
            echo json_encode([
                'success' => true,
                'printer_type' => $config['printer_type'],
                'printer_name' => $config['printer_name'],
                'printer_ip' => $config['printer_ip'],
                'paper_width' => $config['paper_width']
            ]);
            break;
            
        default:
            throw new Exception('Geçersiz işlem');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

