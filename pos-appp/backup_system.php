<?php
require_once __DIR__ . '/config.php';

/**
 * Otomatik Yedekleme Sistemi
 * Bu dosya günlük, haftalık ve aylık yedekleme işlemlerini yapar
 */

class BackupSystem {
    private $backupPath;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    
    public function __construct() {
        $this->backupPath = getConfig('BACKUP_PATH', __DIR__ . '/backups/');
        $this->dbHost = getConfig('DB_HOST', '127.0.0.1');
        $this->dbName = getConfig('DB_NAME', 'pos_app');
        $this->dbUser = getConfig('DB_USER', 'root');
        $this->dbPass = getConfig('DB_PASS', '');
        
        // Yedek klasörünü oluştur
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }
    
    /**
     * Veritabanı yedeği al
     */
    public function createDatabaseBackup($type = 'daily') {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "pos_backup_{$type}_{$timestamp}.sql";
        $filepath = $this->backupPath . $filename;
        
        // Windows için mysqldump yolu kontrolü
        $mysqldump = $this->findMysqldump();
        if (!$mysqldump) {
            return [
                'success' => false,
                'error' => 'mysqldump bulunamadı. Lütfen MySQL yüklü olduğundan emin olun.'
            ];
        }
        
        // MySQL dump komutu
        $command = sprintf(
            '"%s" -h %s -u %s -p%s %s > %s',
            $mysqldump,
            escapeshellarg($this->dbHost),
            escapeshellarg($this->dbUser),
            escapeshellarg($this->dbPass),
            escapeshellarg($this->dbName),
            escapeshellarg($filepath)
        );
        
        // Komutu çalıştır
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'date' => date('Y-m-d H:i:s')
            ];
        } else {
            $errorMsg = 'Yedekleme başarısız: ' . implode("\n", $output) . ' (Return code: ' . $returnCode . ')';
            logError('Database backup failed', [
                'command' => $command,
                'output' => $output,
                'return_code' => $returnCode,
                'filepath' => $filepath
            ]);
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }
    }
    
    /**
     * mysqldump yolunu bul
     */
    private function findMysqldump() {
        // Windows için yaygın yollar
        $possiblePaths = [
            'mysqldump', // PATH'de varsa
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files (x86)\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
            'C:\\Program Files (x86)\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
        ];
        
        foreach ($possiblePaths as $path) {
            if ($path === 'mysqldump') {
                // PATH'de kontrol et
                exec('where mysqldump 2>nul', $output, $returnCode);
                if ($returnCode === 0 && !empty($output)) {
                    return trim($output[0]);
                }
            } else {
                // Tam yol kontrol et
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Dosya yedeği al (PHP dosyaları)
     */
    public function createFileBackup($type = 'daily') {
        $timestamp = date('Y-m-d_H-i-s');
        
        // Yedeklenecek dosyalar
        $files = [
            'index.php',
            'orders.php',
            'report.php',
            'report_detail.php',
            'db.php',
            'config.php',
            'advanced_reports.php',
            'system_status.php',
            'test_connection.php'
        ];
        
        // ZIP uzantısı kontrolü
        if (extension_loaded('zip')) {
            return $this->createZipBackup($files, $type, $timestamp);
        } else {
            // ZIP yoksa alternatif yöntem kullan
            return $this->createTarBackup($files, $type, $timestamp);
        }
    }
    
    /**
     * ZIP ile yedekleme
     */
    private function createZipBackup($files, $type, $timestamp) {
        $filename = "pos_files_{$type}_{$timestamp}.zip";
        $filepath = $this->backupPath . $filename;
        
        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, $file);
                }
            }
            $zip->close();
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'date' => date('Y-m-d H:i:s'),
                'method' => 'ZIP'
            ];
        } else {
            $errorMsg = 'ZIP dosyası oluşturulamadı: ' . $zip->getStatusString();
            logError('ZIP backup failed', [
                'filepath' => $filepath,
                'zip_status' => $zip->getStatusString(),
                'files' => $files
            ]);
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }
    }
    
    /**
     * TAR ile yedekleme (ZIP alternatifi)
     */
    private function createTarBackup($files, $type, $timestamp) {
        $filename = "pos_files_{$type}_{$timestamp}.tar";
        $filepath = $this->backupPath . $filename;
        
        // TAR komutu ile yedekleme
        $tarCommand = $this->findTarCommand();
        if (!$tarCommand) {
            // TAR da yoksa basit kopyalama yöntemi
            return $this->createCopyBackup($files, $type, $timestamp);
        }
        
        // Geçici klasör oluştur
        $tempDir = $this->backupPath . 'temp_' . $timestamp . '/';
        if (!mkdir($tempDir, 0755, true)) {
            return [
                'success' => false,
                'error' => 'Geçici klasör oluşturulamadı'
            ];
        }
        
        try {
            // Dosyaları geçici klasöre kopyala
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $destFile = $tempDir . basename($file);
                    copy($file, $destFile);
                }
            }
            
            // TAR komutu çalıştır
            $command = sprintf(
                '"%s" -cf "%s" -C "%s" .',
                $tarCommand,
                escapeshellarg($filepath),
                escapeshellarg($tempDir)
            );
            
            exec($command, $output, $returnCode);
            
            // Geçici klasörü temizle
            $this->removeDirectory($tempDir);
            
            if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                return [
                    'success' => true,
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => filesize($filepath),
                    'date' => date('Y-m-d H:i:s'),
                    'method' => 'TAR'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'TAR yedekleme başarısız: ' . implode("\n", $output)
                ];
            }
        } catch (Exception $e) {
            // Geçici klasörü temizle
            $this->removeDirectory($tempDir);
            return [
                'success' => false,
                'error' => 'TAR yedekleme hatası: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Basit kopyalama ile yedekleme
     */
    private function createCopyBackup($files, $type, $timestamp) {
        $backupDir = $this->backupPath . "pos_files_{$type}_{$timestamp}/";
        
        if (!mkdir($backupDir, 0755, true)) {
            return [
                'success' => false,
                'error' => 'Yedek klasörü oluşturulamadı'
            ];
        }
        
        $copiedFiles = 0;
        $totalSize = 0;
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $destFile = $backupDir . basename($file);
                if (copy($file, $destFile)) {
                    $copiedFiles++;
                    $totalSize += filesize($destFile);
                }
            }
        }
        
        if ($copiedFiles > 0) {
            return [
                'success' => true,
                'filename' => "pos_files_{$type}_{$timestamp}/",
                'filepath' => $backupDir,
                'size' => $totalSize,
                'date' => date('Y-m-d H:i:s'),
                'method' => 'COPY',
                'files_count' => $copiedFiles
            ];
        } else {
            rmdir($backupDir);
            return [
                'success' => false,
                'error' => 'Hiçbir dosya kopyalanamadı'
            ];
        }
    }
    
    /**
     * TAR komutunu bul
     */
    private function findTarCommand() {
        $possiblePaths = [
            'tar', // PATH'de varsa
            'C:\\Program Files\\Git\\usr\\bin\\tar.exe',
            'C:\\Program Files (x86)\\Git\\usr\\bin\\tar.exe',
            'C:\\cygwin\\bin\\tar.exe',
            'C:\\msys64\\usr\\bin\\tar.exe'
        ];
        
        foreach ($possiblePaths as $path) {
            if ($path === 'tar') {
                // PATH'de kontrol et
                exec('where tar 2>nul', $output, $returnCode);
                if ($returnCode === 0 && !empty($output)) {
                    return trim($output[0]);
                }
            } else {
                // Tam yol kontrol et
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Klasörü recursive sil
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    /**
     * Eski yedekleri temizle
     */
    public function cleanupOldBackups($days = 30) {
        $files = glob($this->backupPath . '*.sql');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < time() - ($days * 24 * 60 * 60)) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Yedek listesi
     */
    public function getBackupList() {
        $files = glob($this->backupPath . '*');
        $backups = [];
        
        foreach ($files as $file) {
            $isDir = is_dir($file);
            $extension = $isDir ? 'folder' : pathinfo($file, PATHINFO_EXTENSION);
            
            $backups[] = [
                'filename' => basename($file),
                'size' => $isDir ? $this->getDirectorySize($file) : filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'type' => $extension,
                'is_directory' => $isDir,
                'method' => $this->getBackupMethod($file)
            ];
        }
        
        // Tarihe göre sırala (yeni önce)
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backups;
    }
    
    /**
     * Klasör boyutunu hesapla
     */
    private function getDirectorySize($dir) {
        $size = 0;
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                $size += is_dir($file) ? $this->getDirectorySize($file) : filesize($file);
            }
        }
        return $size;
    }
    
    /**
     * Yedekleme yöntemini belirle
     */
    private function getBackupMethod($file) {
        $filename = basename($file);
        if (strpos($filename, 'pos_backup_') === 0) {
            return 'DATABASE';
        } elseif (strpos($filename, 'pos_files_') === 0) {
            if (is_dir($file)) {
                return 'COPY';
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                return 'ZIP';
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'tar') {
                return 'TAR';
            }
        }
        return 'UNKNOWN';
    }
    
    /**
     * Yedek indir
     */
    public function downloadBackup($filename) {
        $filepath = $this->backupPath . $filename;
        
        if (file_exists($filepath)) {
            if (is_dir($filepath)) {
                // Klasör ise ZIP olarak paketle
                return $this->downloadDirectoryAsZip($filepath, $filename);
            } else {
                // Dosya ise direkt indir
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Klasörü ZIP olarak indir
     */
    private function downloadDirectoryAsZip($dirPath, $dirName) {
        if (!extension_loaded('zip')) {
            return false;
        }
        
        $zipFilename = $dirName . '.zip';
        $zipFilepath = $this->backupPath . 'temp_' . time() . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($zipFilepath, ZipArchive::CREATE) === TRUE) {
            $this->addDirectoryToZip($zip, $dirPath, $dirName);
            $zip->close();
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
            header('Content-Length: ' . filesize($zipFilepath));
            readfile($zipFilepath);
            unlink($zipFilepath); // Geçici dosyayı sil
            exit;
        }
        
        return false;
    }
    
    /**
     * Klasörü ZIP'e ekle
     */
    private function addDirectoryToZip($zip, $dirPath, $zipDirName) {
        $files = glob($dirPath . '/*');
        foreach ($files as $file) {
            $relativePath = $zipDirName . '/' . basename($file);
            if (is_dir($file)) {
                $this->addDirectoryToZip($zip, $file, $relativePath);
            } else {
                $zip->addFile($file, $relativePath);
            }
        }
    }
    
    /**
     * Otomatik yedekleme (cron job için)
     */
    public function autoBackup() {
        $results = [];
        
        // Günlük veritabanı yedeği
        $results['database'] = $this->createDatabaseBackup('daily');
        
        // Haftalık dosya yedeği (sadece pazartesi)
        if (date('N') == 1) {
            $results['files'] = $this->createFileBackup('weekly');
        }
        
        // Aylık yedekleme (ayın 1'i)
        if (date('j') == 1) {
            $results['monthly'] = $this->createDatabaseBackup('monthly');
        }
        
        // Eski yedekleri temizle
        $results['cleanup'] = $this->cleanupOldBackups();
        
        return $results;
    }
}

// Web arayüzü
if (isset($_GET['action'])) {
    $backup = new BackupSystem();
    
    switch ($_GET['action']) {
        case 'create_db':
            $result = $backup->createDatabaseBackup('manual');
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            
        case 'create_files':
            $result = $backup->createFileBackup('manual');
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            
        case 'download':
            $filename = $_GET['file'] ?? '';
            if ($backup->downloadBackup($filename)) {
                exit;
            } else {
                http_response_code(404);
                echo 'Dosya bulunamadı';
                exit;
            }
            
        case 'cleanup':
            $deleted = $backup->cleanupOldBackups();
            header('Content-Type: application/json');
            echo json_encode(['deleted' => $deleted]);
            exit;
    }
}

$backup = new BackupSystem();
$backups = $backup->getBackupList();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yedekleme Sistemi - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { background: rgba(255, 255, 255, 0.95); border-radius: 15px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .backup-item { border-left: 4px solid #28a745; }
        .backup-item.sql { border-left-color: #007bff; }
        .backup-item.zip { border-left-color: #ffc107; }
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
                <a class="btn btn-primary" href="advanced_reports.php">Gelişmiş Rapor</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-database me-2"></i>Yedekleme Sistemi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <button class="btn btn-primary w-100" onclick="createBackup('db')">
                                    <i class="fas fa-database me-2"></i>
                                    Veritabanı Yedeği Al
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-warning w-100" onclick="createBackup('files')">
                                    <i class="fas fa-file-archive me-2"></i>
                                    Dosya Yedeği Al
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Dosya Adı</th>
                                        <th>Boyut</th>
                                        <th>Tarih</th>
                                        <th>Tür</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup_item): ?>
                                    <tr class="backup-item <?php echo $backup_item['type']; ?>">
                                        <td><strong><?php echo htmlspecialchars($backup_item['filename'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><?php echo formatBytes($backup_item['size']); ?></td>
                                        <td><?php echo $backup_item['date']; ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'secondary';
                                            $badgeText = strtoupper($backup_item['type']);
                                            
                                            switch ($backup_item['method']) {
                                                case 'DATABASE':
                                                    $badgeClass = 'primary';
                                                    $badgeText = 'VERİTABANI';
                                                    break;
                                                case 'ZIP':
                                                    $badgeClass = 'success';
                                                    $badgeText = 'ZIP';
                                                    break;
                                                case 'TAR':
                                                    $badgeClass = 'info';
                                                    $badgeText = 'TAR';
                                                    break;
                                                case 'COPY':
                                                    $badgeClass = 'warning';
                                                    $badgeText = 'KOPYA';
                                                    break;
                                                default:
                                                    $badgeClass = 'secondary';
                                                    $badgeText = strtoupper($backup_item['type']);
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo $badgeText; ?>
                                            </span>
                                            <?php if ($backup_item['is_directory']): ?>
                                                <i class="fas fa-folder ms-1"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?action=download&file=<?php echo urlencode($backup_item['filename']); ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Yedekleme Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-clock me-2"></i>Otomatik Yedekleme</h6>
                            <ul class="mb-0">
                                <li><strong>Günlük:</strong> Veritabanı yedeği</li>
                                <li><strong>Haftalık:</strong> Dosya yedeği (Pazartesi)</li>
                                <li><strong>Aylık:</strong> Tam yedekleme (1. gün)</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-success">
                            <h6><i class="fas fa-tools me-2"></i>Yedekleme Yöntemleri</h6>
                            <ul class="mb-0">
                                <li><strong>ZIP:</strong> En iyi sıkıştırma (önerilen)</li>
                                <li><strong>TAR:</strong> ZIP alternatifi</li>
                                <li><strong>KOPYA:</strong> Basit klasör kopyalama</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-trash me-2"></i>Temizlik</h6>
                            <p class="mb-0">30 günden eski yedekler otomatik silinir.</p>
                        </div>
                        
                        <button class="btn btn-outline-danger w-100" onclick="cleanupBackups()">
                            <i class="fas fa-broom me-2"></i>
                            Eski Yedekleri Temizle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function createBackup(type) {
            const button = event.target;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Yedekleniyor...';
            button.disabled = true;
            
            fetch(`?action=create_${type}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Yedekleme başarılı!\nDosya: ' + data.filename + '\nBoyut: ' + formatBytes(data.size));
                        location.reload();
                    } else {
                        alert('Yedekleme hatası: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Backup error:', error);
                    alert('Hata: ' + error.message);
                })
                .finally(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }
        
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        function cleanupBackups() {
            if (confirm('Eski yedekleri silmek istediğinize emin misiniz?')) {
                fetch('?action=cleanup')
                    .then(response => response.json())
                    .then(data => {
                        alert(data.deleted + ' dosya silindi.');
                        location.reload();
                    });
            }
        }
    </script>
</body>
</html>

<?php
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>

