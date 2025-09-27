<?php
require_once __DIR__ . '/config.php';

$status = checkSystemStatus();
$info = getSystemInfo();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Durumu - <?php echo getConfig('SITE_NAME', 'POS Sistemi'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .status-card { background: rgba(255, 255, 255, 0.95); border-radius: 15px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="status-card p-4">
                    <h2 class="text-center mb-4">
                        <i class="fas fa-heartbeat me-2"></i>
                        Sistem Durumu
                    </h2>
                    
                    <!-- Genel Durum -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert <?php echo $status['database'] && $status['writable'] && $status['php_version'] ? 'alert-success' : 'alert-warning'; ?>">
                                <h5 class="mb-0">
                                    <i class="fas fa-<?php echo $status['database'] && $status['writable'] && $status['php_version'] ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                    Genel Durum: <?php echo $status['database'] && $status['writable'] && $status['php_version'] ? 'Çalışıyor' : 'Sorun Var'; ?>
                                </h5>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detaylı Durum -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-database me-2"></i>
                                        Veritabanı Bağlantısı
                                    </h6>
                                    <p class="card-text">
                                        <i class="fas fa-<?php echo $status['database'] ? 'check' : 'times'; ?> me-2 <?php echo $status['database'] ? 'status-ok' : 'status-error'; ?>"></i>
                                        <?php echo $status['database'] ? 'Bağlantı başarılı' : 'Bağlantı hatası'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-folder-open me-2"></i>
                                        Dosya İzinleri
                                    </h6>
                                    <p class="card-text">
                                        <i class="fas fa-<?php echo $status['writable'] ? 'check' : 'times'; ?> me-2 <?php echo $status['writable'] ? 'status-ok' : 'status-error'; ?>"></i>
                                        <?php echo $status['writable'] ? 'Yazma izni var' : 'Yazma izni yok'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fab fa-php me-2"></i>
                                        PHP Versiyonu
                                    </h6>
                                    <p class="card-text">
                                        <i class="fas fa-<?php echo $status['php_version'] ? 'check' : 'times'; ?> me-2 <?php echo $status['php_version'] ? 'status-ok' : 'status-error'; ?>"></i>
                                        <?php echo $info['php_version']; ?> 
                                        <?php echo $status['php_version'] ? '(Uygun)' : '(7.4+ gerekli)'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-cogs me-2"></i>
                                        PHP Uzantıları
                                    </h6>
                                    <p class="card-text">
                                        <?php foreach ($status['extensions'] as $ext => $loaded): ?>
                                            <i class="fas fa-<?php echo $loaded ? 'check' : 'times'; ?> me-1 <?php echo $loaded ? 'status-ok' : 'status-error'; ?>"></i>
                                            <?php echo strtoupper($ext); ?>
                                            <br>
                                        <?php endforeach; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sistem Bilgileri -->
                    <div class="mt-4">
                        <h5><i class="fas fa-info-circle me-2"></i>Sistem Bilgileri</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Site URL:</strong></td>
                                    <td><?php echo $info['site_url']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>MySQL Versiyonu:</strong></td>
                                    <td><?php echo $info['mysql_version']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Sunucu Yazılımı:</strong></td>
                                    <td><?php echo $info['server_software']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Zaman Dilimi:</strong></td>
                                    <td><?php echo $info['timezone']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Debug Modu:</strong></td>
                                    <td><?php echo $info['debug_mode'] ? 'Açık' : 'Kapalı'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Eylemler -->
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary me-2">
                            <i class="fas fa-home me-1"></i>
                            Ana Sayfa
                        </a>
                        <a href="test_connection.php" class="btn btn-secondary me-2">
                            <i class="fas fa-plug me-1"></i>
                            Bağlantı Testi
                        </a>
                        <button onclick="location.reload()" class="btn btn-outline-primary">
                            <i class="fas fa-refresh me-1"></i>
                            Yenile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

