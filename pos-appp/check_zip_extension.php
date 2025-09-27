<?php
/**
 * ZIP Uzantısı Kontrol ve Çözüm Dosyası
 */

echo "<h2>PHP ZIP Uzantısı Kontrolü</h2>";

// 1. Mevcut uzantıları listele
echo "<h3>1. Yüklü PHP Uzantıları</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<ul>";
foreach ($extensions as $ext) {
    $highlight = ($ext === 'zip') ? ' style="background-color: #d4edda; font-weight: bold;"' : '';
    echo "<li{$highlight}>" . strtoupper($ext) . "</li>";
}
echo "</ul>";

// 2. ZIP uzantısı kontrolü
echo "<h3>2. ZIP Uzantısı Durumu</h3>";
if (extension_loaded('zip')) {
    echo "<p style='color: green; font-weight: bold;'>✓ ZIP uzantısı yüklü ve çalışıyor!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ ZIP uzantısı yüklü değil!</p>";
}

// 3. PHP konfigürasyon bilgileri
echo "<h3>3. PHP Konfigürasyon Bilgileri</h3>";
echo "<ul>";
echo "<li><strong>PHP Versiyonu:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>PHP SAPI:</strong> " . php_sapi_name() . "</li>";
echo "<li><strong>PHP.ini Dosyası:</strong> " . php_ini_loaded_file() . "</li>";
echo "<li><strong>Ek PHP.ini Dosyaları:</strong> " . (php_ini_scanned_files() ?: 'Yok') . "</li>";
echo "</ul>";

// 4. ZIP uzantısı için çözüm önerileri
echo "<h3>4. ZIP Uzantısını Etkinleştirme Çözümleri</h3>";

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "<h4>Windows (XAMPP/WAMP) için:</h4>";
    echo "<ol>";
    echo "<li><strong>XAMPP için:</strong><br>";
    echo "   - XAMPP Control Panel'i açın<br>";
    echo "   - Apache'nin yanındaki 'Config' butonuna tıklayın<br>";
    echo "   - 'PHP (php.ini)' seçin<br>";
    echo "   - Dosyada <code>;extension=zip</code> satırını bulun<br>";
    echo "   - Başındaki <code>;</code> işaretini kaldırın: <code>extension=zip</code><br>";
    echo "   - Dosyayı kaydedin ve Apache'yi yeniden başlatın</li>";
    
    echo "<li><strong>WAMP için:</strong><br>";
    echo "   - WAMP ikonuna sağ tıklayın<br>";
    echo "   - PHP > PHP Extensions > zip seçin<br>";
    echo "   - Apache'yi yeniden başlatın</li>";
    
    echo "<li><strong>Manuel olarak:</strong><br>";
    echo "   - php.ini dosyasını açın<br>";
    echo "   - <code>extension=zip</code> satırını ekleyin veya uncomment yapın<br>";
    echo "   - Web sunucusunu yeniden başlatın</li>";
    echo "</ol>";
} else {
    echo "<h4>Linux için:</h4>";
    echo "<ol>";
    echo "<li><strong>Ubuntu/Debian:</strong><br>";
    echo "   <code>sudo apt-get install php-zip</code></li>";
    echo "<li><strong>CentOS/RHEL:</strong><br>";
    echo "   <code>sudo yum install php-zip</code></li>";
    echo "<li><strong>Manuel derleme:</strong><br>";
    echo "   <code>--with-zip</code> parametresi ile PHP'yi yeniden derleyin</li>";
    echo "</ol>";
}

// 5. Alternatif çözüm - ZIP olmadan yedekleme
echo "<h3>5. Geçici Çözüm - ZIP Olmadan Yedekleme</h3>";
echo "<p>ZIP uzantısı yoksa, dosyaları tek tek kopyalayarak yedekleme yapabiliriz.</p>";

// 6. Test butonu
echo "<h3>6. Test</h3>";
echo "<p><a href='?test_zip=1' class='btn btn-primary'>ZIP Uzantısını Test Et</a></p>";

if (isset($_GET['test_zip'])) {
    echo "<h4>Test Sonucu:</h4>";
    if (extension_loaded('zip')) {
        echo "<p style='color: green;'>✓ ZIP uzantısı çalışıyor!</p>";
        
        // Basit ZIP testi
        $testFile = 'test_zip_' . time() . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($testFile, ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('test.txt', 'Bu bir test dosyasıdır.');
            $zip->close();
            
            if (file_exists($testFile)) {
                echo "<p style='color: green;'>✓ ZIP dosyası oluşturma testi başarılı!</p>";
                unlink($testFile); // Test dosyasını sil
            } else {
                echo "<p style='color: red;'>✗ ZIP dosyası oluşturulamadı!</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ ZIP dosyası açılamadı!</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ ZIP uzantısı hala yüklü değil!</p>";
    }
}

echo "<hr>";
echo "<p><a href='backup_system.php'>← Yedekleme Sistemi</a> | <a href='index.php'>← Ana Sayfa</a></p>";
?>
