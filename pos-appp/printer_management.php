<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/printer_config.php';

$printer = new ThermalPrinter();
$message = '';
$error = '';

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_config') {
        $newConfig = [
            'printer_name' => $_POST['printer_name'] ?? '',
            'printer_ip' => $_POST['printer_ip'] ?? '',
            'printer_port' => (int)($_POST['printer_port'] ?? 9100),
            'printer_type' => $_POST['printer_type'] ?? 'usb',
            'paper_width' => (int)($_POST['paper_width'] ?? 80),
            'company_name' => $_POST['company_name'] ?? '',
            'company_address' => $_POST['company_address'] ?? '',
            'company_phone' => $_POST['company_phone'] ?? '',
            'company_tax_number' => $_POST['company_tax_number'] ?? '',
            'show_logo' => isset($_POST['show_logo']),
            'show_barcode' => isset($_POST['show_barcode']),
            'show_qr_code' => isset($_POST['show_qr_code']),
            'cut_paper' => isset($_POST['cut_paper']),
            'open_cash_drawer' => isset($_POST['open_cash_drawer']),
        ];
        
        $printer->updateConfig($newConfig);
        $message = 'Yazıcı ayarları güncellendi!';
    }
    
    if ($action === 'test_print') {
        if ($printer->printTestPage()) {
            $message = 'Test sayfası yazdırıldı!';
        } else {
            $error = 'Test sayfası yazdırılamadı! Yazıcı ayarlarını kontrol edin.';
        }
    }
    
    if ($action === 'print_receipt') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId > 0) {
            $orderDetails = get_order_details($orderId);
            if (!empty($orderDetails['order'])) {
                if ($printer->printReceipt($orderDetails)) {
                    $message = 'Fiş yazdırıldı!';
                } else {
                    $error = 'Fiş yazdırılamadı! Yazıcı ayarlarını kontrol edin.';
                }
            } else {
                $error = 'Sipariş bulunamadı!';
            }
        } else {
            $error = 'Geçersiz sipariş numarası!';
        }
    }
}

$config = $printer->getConfig();
$recentOrders = get_orders(null, null, 10); // Son 10 sipariş
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Termal Yazıcı Yönetimi - POS Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { background: rgba(255, 255, 255, 0.95); border-radius: 15px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .printer-status { border-left: 4px solid #28a745; }
        .printer-status.offline { border-left-color: #dc3545; }
        .config-section { border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-utensils me-2"></i>
                Restoran POS
            </a>
            <div class="d-flex">
                <a class="btn btn-outline-light me-2" href="index.php">Ana Sayfa</a>
                <a class="btn btn-warning me-2" href="report.php">Raporlar</a>
                <a class="btn btn-info me-2" href="advanced_reports.php">Gelişmiş Rapor</a>
                <a class="btn btn-secondary me-2" href="backup_system.php">Yedekleme</a>
                <a class="btn btn-primary" href="printer_management.php">Yazıcı</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo e($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Yazıcı Ayarları -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-print me-2"></i>Termal Yazıcı Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="update_config">
                            
                            <!-- Yazıcı Bağlantısı -->
                            <div class="config-section">
                                <h6><i class="fas fa-plug me-2"></i>Yazıcı Bağlantısı</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Yazıcı Türü</label>
                                        <select class="form-select" name="printer_type" id="printerType">
                                            <option value="usb" <?php echo $config['printer_type'] === 'usb' ? 'selected' : ''; ?>>USB Yazıcı</option>
                                            <option value="network" <?php echo $config['printer_type'] === 'network' ? 'selected' : ''; ?>>Ağ Yazıcısı</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="usbSettings" style="display: <?php echo $config['printer_type'] === 'usb' ? 'block' : 'none'; ?>">
                                        <label class="form-label">Windows Yazıcı Adı</label>
                                        <input type="text" class="form-control" name="printer_name" value="<?php echo e($config['printer_name']); ?>" placeholder="POS-80">
                                        <small class="form-text text-muted">Windows'ta yazıcı adını girin</small>
                                    </div>
                                    <div class="col-md-6" id="networkSettings" style="display: <?php echo $config['printer_type'] === 'network' ? 'block' : 'none'; ?>">
                                        <label class="form-label">Yazıcı IP Adresi</label>
                                        <input type="text" class="form-control" name="printer_ip" value="<?php echo e($config['printer_ip']); ?>" placeholder="192.168.1.100">
                                    </div>
                                    <div class="col-md-6" id="networkPort" style="display: <?php echo $config['printer_type'] === 'network' ? 'block' : 'none'; ?>">
                                        <label class="form-label">Port</label>
                                        <input type="number" class="form-control" name="printer_port" value="<?php echo $config['printer_port']; ?>" placeholder="9100">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Fiş Ayarları -->
                            <div class="config-section">
                                <h6><i class="fas fa-receipt me-2"></i>Fiş Ayarları</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Kağıt Genişliği</label>
                                        <select class="form-select" name="paper_width">
                                            <option value="80" <?php echo $config['paper_width'] === 80 ? 'selected' : ''; ?>>80mm (Standart)</option>
                                            <option value="58" <?php echo $config['paper_width'] === 58 ? 'selected' : ''; ?>>58mm (Küçük)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Font Boyutu</label>
                                        <select class="form-select" name="font_size">
                                            <option value="normal" <?php echo $config['font_size'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                            <option value="small" <?php echo $config['font_size'] === 'small' ? 'selected' : ''; ?>>Küçük</option>
                                            <option value="large" <?php echo $config['font_size'] === 'large' ? 'selected' : ''; ?>>Büyük</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Şirket Bilgileri -->
                            <div class="config-section">
                                <h6><i class="fas fa-building me-2"></i>Şirket Bilgileri</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Şirket Adı</label>
                                        <input type="text" class="form-control" name="company_name" value="<?php echo e($config['company_name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" class="form-control" name="company_phone" value="<?php echo e($config['company_phone']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Adres</label>
                                        <textarea class="form-control" name="company_address" rows="2"><?php echo e($config['company_address']); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Vergi Numarası</label>
                                        <input type="text" class="form-control" name="company_tax_number" value="<?php echo e($config['company_tax_number']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ekstra Ayarlar -->
                            <div class="config-section">
                                <h6><i class="fas fa-cog me-2"></i>Ekstra Ayarlar</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="show_logo" <?php echo $config['show_logo'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Logo Göster</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="show_barcode" <?php echo $config['show_barcode'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Barkod Göster</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cut_paper" <?php echo $config['cut_paper'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Kağıt Kes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="open_cash_drawer" <?php echo $config['open_cash_drawer'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Kasa Çekmecesini Aç</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Ayarları Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Test ve Yazdırma -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-vial me-2"></i>Test ve Yazdırma</h5>
                    </div>
                    <div class="card-body">
                        <!-- Test Sayfası -->
                        <div class="mb-4">
                            <h6>Yazıcı Testi</h6>
                            <p class="text-muted small">Yazıcının çalışıp çalışmadığını test edin.</p>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="test_print">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-print me-2"></i>
                                    Test Sayfası Yazdır
                                </button>
                            </form>
                        </div>
                        
                        <!-- Sipariş Yazdırma -->
                        <div class="mb-4">
                            <h6>Sipariş Yazdır</h6>
                            <p class="text-muted small">Son siparişlerden birini yazdırın.</p>
                            <form method="post">
                                <input type="hidden" name="action" value="print_receipt">
                                <div class="mb-3">
                                    <select class="form-select" name="order_id" required>
                                        <option value="">Sipariş Seçin</option>
                                        <?php foreach ($recentOrders as $order): ?>
                                        <option value="<?php echo $order['id']; ?>">
                                            #<?php echo $order['id']; ?> - 
                                            <?php echo date('d.m.Y H:i', strtotime($order['date'])); ?> - 
                                            <?php echo number_format($order['total'], 2, ',', '.'); ?> ₺
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-receipt me-2"></i>
                                    Fiş Yazdır
                                </button>
                            </form>
                        </div>
                        
                        <!-- Yazıcı Durumu -->
                        <div class="printer-status p-3">
                            <h6><i class="fas fa-info-circle me-2"></i>Yazıcı Durumu</h6>
                            <div class="d-flex justify-content-between">
                                <span>Tür:</span>
                                <span class="badge bg-<?php echo $config['printer_type'] === 'usb' ? 'primary' : 'info'; ?>">
                                    <?php echo strtoupper($config['printer_type']); ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Kağıt:</span>
                                <span><?php echo $config['paper_width']; ?>mm</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Font:</span>
                                <span><?php echo ucfirst($config['font_size']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Yazıcı türü değiştiğinde form alanlarını göster/gizle
        document.getElementById('printerType').addEventListener('change', function() {
            const usbSettings = document.getElementById('usbSettings');
            const networkSettings = document.getElementById('networkSettings');
            const networkPort = document.getElementById('networkPort');
            
            if (this.value === 'usb') {
                usbSettings.style.display = 'block';
                networkSettings.style.display = 'none';
                networkPort.style.display = 'none';
            } else {
                usbSettings.style.display = 'none';
                networkSettings.style.display = 'block';
                networkPort.style.display = 'block';
            }
        });
    </script>
</body>
</html>

