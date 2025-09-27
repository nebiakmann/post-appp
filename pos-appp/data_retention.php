<?php
require_once __DIR__ . '/config.php';

/**
 * Veri Saklama Politikaları
 * Bu dosya veri saklama kurallarını yönetir
 */

class DataRetention {
    private $pdo;
    
    public function __construct() {
        $this->pdo = db();
    }
    
    /**
     * Veri saklama politikalarını uygula
     */
    public function applyRetentionPolicies() {
        $results = [];
        
        // 1 yıldan eski siparişleri arşivle
        $results['archived_orders'] = $this->archiveOldOrders(365);
        
        // 2 yıldan eski siparişleri sil
        $results['deleted_orders'] = $this->deleteVeryOldOrders(730);
        
        // Boş menü öğelerini temizle
        $results['cleaned_menu'] = $this->cleanEmptyMenuItems();
        
        // Geçici dosyaları temizle
        $results['cleaned_temp'] = $this->cleanTempFiles();
        
        return $results;
    }
    
    /**
     * Eski siparişleri arşivle
     */
    private function archiveOldOrders($days) {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            // Arşiv tablosu oluştur (yoksa)
            if (!$this->createArchiveTable()) {
                return 0; // Tablo oluşturulamadıysa 0 döndür
            }
            
            // Eski siparişleri arşivle
            $sql = "
                INSERT INTO orders_archive 
                SELECT *, NOW() as archived_at 
                FROM orders 
                WHERE date < ? AND id NOT IN (SELECT id FROM orders_archive)
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cutoffDate]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            logError("Archive orders error", [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate
            ]);
            return 0;
        }
    }
    
    /**
     * Çok eski siparişleri sil
     */
    private function deleteVeryOldOrders($days) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Önce sipariş öğelerini sil
        $sql = "
            DELETE oi FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.date < ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cutoffDate]);
        $deletedItems = $stmt->rowCount();
        
        // Sonra siparişleri sil
        $sql = "DELETE FROM orders WHERE date < ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cutoffDate]);
        $deletedOrders = $stmt->rowCount();
        
        return ['orders' => $deletedOrders, 'items' => $deletedItems];
    }
    
    /**
     * Boş menü öğelerini temizle
     */
    private function cleanEmptyMenuItems() {
        // Hiç sipariş edilmemiş menü öğelerini sil
        $sql = "
            DELETE FROM menu 
            WHERE id NOT IN (
                SELECT DISTINCT menu_id FROM order_items
            ) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Geçici dosyaları temizle
     */
    private function cleanTempFiles() {
        $tempDir = __DIR__ . '/temp/';
        $deleted = 0;
        
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < time() - 3600) { // 1 saat
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Arşiv tablosu oluştur
     */
    private function createArchiveTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS orders_archive (
                    id INT UNSIGNED,
                    date DATETIME,
                    subtotal DECIMAL(10,2),
                    tax DECIMAL(10,2),
                    service_fee DECIMAL(10,2),
                    discount DECIMAL(10,2),
                    total DECIMAL(10,2),
                    note VARCHAR(255),
                    created_at TIMESTAMP,
                    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_archived_date (date),
                    INDEX idx_archived_at (archived_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            // Tablo zaten varsa veya başka bir hata varsa logla
            logError("Archive table creation error", [
                'error' => $e->getMessage(),
                'sql' => $sql
            ]);
            return false;
        }
    }
    
    /**
     * Veri istatistikleri
     */
    public function getDataStatistics() {
        $stats = [];
        
        // Toplam sipariş sayısı
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM orders");
        $stats['total_orders'] = $stmt->fetch()['count'];
        
        // Arşivlenmiş sipariş sayısı
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM orders_archive");
        $stats['archived_orders'] = $stmt->fetch()['count'];
        
        // En eski sipariş tarihi
        $stmt = $this->pdo->query("SELECT MIN(date) as oldest FROM orders");
        $stats['oldest_order'] = $stmt->fetch()['oldest'];
        
        // En yeni sipariş tarihi
        $stmt = $this->pdo->query("SELECT MAX(date) as newest FROM orders");
        $stats['newest_order'] = $stmt->fetch()['newest'];
        
        // Toplam veri boyutu
        $stmt = $this->pdo->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $stats['database_size_mb'] = $stmt->fetch()['size_mb'];
        
        return $stats;
    }
    
    /**
     * Veri saklama ayarları
     */
    public function getRetentionSettings() {
        return [
            'archive_after_days' => 365,      // 1 yıl sonra arşivle
            'delete_after_days' => 730,       // 2 yıl sonra sil
            'clean_menu_after_days' => 30,    // 30 gün sonra boş menü öğelerini sil
            'clean_temp_after_hours' => 1,    // 1 saat sonra geçici dosyaları sil
            'backup_retention_days' => 30     // 30 gün yedek sakla
        ];
    }
}

// Web arayüzü
if (isset($_GET['action'])) {
    $retention = new DataRetention();
    
    switch ($_GET['action']) {
        case 'apply_policies':
            $results = $retention->applyRetentionPolicies();
            header('Content-Type: application/json');
            echo json_encode($results);
            exit;
            
        case 'get_stats':
            $stats = $retention->getDataStatistics();
            header('Content-Type: application/json');
            echo json_encode($stats);
            exit;
    }
}

$retention = new DataRetention();
$stats = $retention->getDataStatistics();
$settings = $retention->getRetentionSettings();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Veri Saklama - POS Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { background: rgba(255, 255, 255, 0.95); border-radius: 15px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .stat-card { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
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
                <a class="btn btn-primary me-2" href="advanced_reports.php">Gelişmiş Rapor</a>
                <a class="btn btn-info" href="backup_system.php">Yedekleme</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- İstatistikler -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                        <h4><?php echo number_format($stats['total_orders']); ?></h4>
                        <p class="mb-0">Aktif Sipariş</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-archive fa-2x mb-2"></i>
                        <h4><?php echo number_format($stats['archived_orders']); ?></h4>
                        <p class="mb-0">Arşivlenmiş</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-database fa-2x mb-2"></i>
                        <h4><?php echo $stats['database_size_mb']; ?> MB</h4>
                        <p class="mb-0">Veritabanı Boyutu</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <h4><?php echo $stats['oldest_order'] ? date('d.m.Y', strtotime($stats['oldest_order'])) : 'Yok'; ?></h4>
                        <p class="mb-0">En Eski Sipariş</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs me-2"></i>Veri Saklama Politikaları</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Otomatik Veri Yönetimi</h6>
                            <p class="mb-0">Sistem otomatik olarak veri saklama politikalarını uygular ve eski verileri temizler.</p>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6><i class="fas fa-archive me-2"></i>Arşivleme</h6>
                                        <p class="mb-2"><?php echo $settings['archive_after_days']; ?> gün sonra siparişler arşivlenir</p>
                                        <small class="text-muted">Arşivlenen veriler silinmez, sadece ayrı tabloya taşınır</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h6><i class="fas fa-trash me-2"></i>Silme</h6>
                                        <p class="mb-2"><?php echo $settings['delete_after_days']; ?> gün sonra siparişler silinir</p>
                                        <small class="text-muted">Bu işlem geri alınamaz, dikkatli olun</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <h6><i class="fas fa-utensils me-2"></i>Menü Temizliği</h6>
                                        <p class="mb-2"><?php echo $settings['clean_menu_after_days']; ?> gün sonra boş menü öğeleri silinir</p>
                                        <small class="text-muted">Hiç sipariş edilmemiş ürünler temizlenir</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h6><i class="fas fa-file me-2"></i>Geçici Dosyalar</h6>
                                        <p class="mb-2"><?php echo $settings['clean_temp_after_hours']; ?> saat sonra geçici dosyalar silinir</p>
                                        <small class="text-muted">Sistem performansı için gerekli</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button class="btn btn-primary me-2" onclick="applyPolicies()">
                                <i class="fas fa-play me-2"></i>
                                Politikaları Şimdi Uygula
                            </button>
                            <button class="btn btn-outline-secondary" onclick="refreshStats()">
                                <i class="fas fa-refresh me-2"></i>
                                İstatistikleri Yenile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>Veri Dağılımı</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Aktif Siparişler</span>
                                <span><?php echo number_format($stats['total_orders']); ?></span>
                            </div>
                            <div class="progress mt-1">
                                <div class="progress-bar bg-primary" style="width: <?php echo $stats['total_orders'] > 0 ? 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Arşivlenmiş</span>
                                <span><?php echo number_format($stats['archived_orders']); ?></span>
                            </div>
                            <div class="progress mt-1">
                                <div class="progress-bar bg-info" style="width: <?php echo $stats['total_orders'] > 0 ? ($stats['archived_orders'] / ($stats['total_orders'] + $stats['archived_orders'])) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Önemli</h6>
                            <p class="mb-0">Veri saklama politikaları otomatik çalışır. Manuel müdahale gerekmez.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function applyPolicies() {
            if (confirm('Veri saklama politikalarını şimdi uygulamak istediğinize emin misiniz?')) {
                fetch('?action=apply_policies')
                    .then(response => response.json())
                    .then(data => {
                        alert('Politikalar uygulandı!\n' + 
                              'Arşivlenen siparişler: ' + data.archived_orders + '\n' +
                              'Silinen siparişler: ' + data.deleted_orders.orders + '\n' +
                              'Temizlenen menü öğeleri: ' + data.cleaned_menu + '\n' +
                              'Temizlenen geçici dosyalar: ' + data.cleaned_temp);
                        location.reload();
                    })
                    .catch(error => {
                        alert('Hata: ' + error);
                    });
            }
        }
        
        function refreshStats() {
            location.reload();
        }
    </script>
</body>
</html>

