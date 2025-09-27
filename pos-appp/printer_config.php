<?php
/**
 * Termal Yazıcı Konfigürasyonu
 * ESC/POS komutları ve yazıcı ayarları
 */

class ThermalPrinter {
    private $config;
    
    public function __construct() {
        $this->config = [
            // Yazıcı ayarları
            'printer_name' => 'POS-80', // Windows yazıcı adı
            'printer_ip' => '192.168.1.100', // Ağ yazıcısı IP'si
            'printer_port' => 9100, // Ağ yazıcısı portu
            'printer_type' => 'usb', // usb, network, bluetooth
            
            // Fiş ayarları
            'paper_width' => 80, // 80mm veya 58mm
            'chars_per_line' => 48, // 80mm için 48, 58mm için 32
            'font_size' => 'normal', // small, normal, large
            
            // Şirket bilgileri
            'company_name' => 'Restoran POS',
            'company_address' => 'Adres Bilgisi',
            'company_phone' => 'Tel: 0212 123 45 67',
            'company_tax_number' => 'Vergi No: 1234567890',
            
            // Fiş ayarları
            'show_logo' => false,
            'show_barcode' => true,
            'show_qr_code' => false,
            'cut_paper' => true,
            'open_cash_drawer' => false,
        ];
    }
    
    /**
     * ESC/POS komutları
     */
    public function getEscPosCommands() {
        return [
            // Temel komutlar
            'INIT' => "\x1B\x40", // Yazıcıyı başlat
            'CUT' => "\x1D\x56\x00", // Kağıt kes
            'FEED' => "\x0A", // Satır besle
            'FEED_LINES' => "\x1B\x64", // N satır besle (parametre gerekli)
            
            // Font ayarları
            'FONT_NORMAL' => "\x1B\x4D\x00", // Normal font
            'FONT_SMALL' => "\x1B\x4D\x01", // Küçük font
            'FONT_LARGE' => "\x1B\x21\x30", // Büyük font (2x2)
            
            // Hizalama
            'ALIGN_LEFT' => "\x1B\x61\x00", // Sol hizalama
            'ALIGN_CENTER' => "\x1B\x61\x01", // Orta hizalama
            'ALIGN_RIGHT' => "\x1B\x61\x02", // Sağ hizalama
            
            // Stil
            'BOLD_ON' => "\x1B\x45\x01", // Kalın yazı aç
            'BOLD_OFF' => "\x1B\x45\x00", // Kalın yazı kapat
            'UNDERLINE_ON' => "\x1B\x2D\x01", // Alt çizgi aç
            'UNDERLINE_OFF' => "\x1B\x2D\x00", // Alt çizgi kapat
            
            // Çizgi çizme
            'LINE' => str_repeat('-', $this->config['chars_per_line']),
            'DOUBLE_LINE' => str_repeat('=', $this->config['chars_per_line']),
            
            // Barkod
            'BARCODE_CODE128' => "\x1D\x6B\x49", // Code128 barkod
            'BARCODE_EAN13' => "\x1D\x6B\x02", // EAN13 barkod
            
            // Kasa çekmecesi
            'OPEN_DRAWER' => "\x1B\x70\x00\x19\x19", // Kasa çekmecesini aç
        ];
    }
    
    /**
     * Fiş formatını oluştur
     */
    public function formatReceipt($order) {
        $commands = $this->getEscPosCommands();
        $output = '';
        
        // Yazıcıyı başlat
        $output .= $commands['INIT'];
        
        // Şirket başlığı
        $output .= $commands['ALIGN_CENTER'];
        $output .= $commands['FONT_LARGE'];
        $output .= $commands['BOLD_ON'];
        $output .= $this->config['company_name'] . "\n";
        $output .= $commands['BOLD_OFF'];
        $output .= $commands['FONT_NORMAL'];
        
        // Şirket bilgileri
        $output .= $this->config['company_address'] . "\n";
        $output .= $this->config['company_phone'] . "\n";
        $output .= $this->config['company_tax_number'] . "\n";
        
        // Çizgi
        $output .= $commands['LINE'] . "\n";
        
        // Sipariş bilgileri
        $output .= $commands['ALIGN_LEFT'];
        $output .= "Sipariş No: #" . $order['id'] . "\n";
        $output .= "Tarih: " . date('d.m.Y H:i', strtotime($order['date'])) . "\n";
        $output .= $commands['LINE'] . "\n";
        
        // Ürünler
        $output .= $commands['BOLD_ON'];
        $output .= sprintf("%-20s %3s %8s %8s\n", "Ürün", "Adet", "Birim", "Toplam");
        $output .= $commands['BOLD_OFF'];
        $output .= $commands['LINE'] . "\n";
        
        foreach ($order['items'] as $item) {
            $name = $this->truncateText($item['name'], 20);
            $output .= sprintf("%-20s %3d %8.2f %8.2f\n", 
                $name, 
                $item['quantity'], 
                $item['price'], 
                $item['total']
            );
        }
        
        // Çizgi
        $output .= $commands['LINE'] . "\n";
        
        // Hesaplamalar
        $output .= sprintf("%-20s %15.2f\n", "Ara Toplam:", $order['subtotal']);
        $output .= sprintf("%-20s %15.2f\n", "KDV:", $order['tax']);
        $output .= sprintf("%-20s %15.2f\n", "Servis:", $order['service_fee']);
        $output .= sprintf("%-20s %15.2f\n", "İndirim:", -$order['discount']);
        
        // Çift çizgi
        $output .= $commands['DOUBLE_LINE'] . "\n";
        
        // Toplam
        $output .= $commands['BOLD_ON'];
        $output .= $commands['FONT_LARGE'];
        $output .= sprintf("%-20s %15.2f\n", "TOPLAM:", $order['total']);
        $output .= $commands['FONT_NORMAL'];
        $output .= $commands['BOLD_OFF'];
        
        // Çizgi
        $output .= $commands['DOUBLE_LINE'] . "\n";
        
        // Not
        if (!empty($order['note'])) {
            $output .= "Not: " . $order['note'] . "\n";
            $output .= $commands['LINE'] . "\n";
        }
        
        // Teşekkür mesajı
        $output .= $commands['ALIGN_CENTER'];
        $output .= "Teşekkür ederiz!\n";
        $output .= "Tekrar bekleriz!\n";
        
        // Kağıt besleme ve kesme
        $output .= $commands['FEED'];
        $output .= $commands['FEED'];
        if ($this->config['cut_paper']) {
            $output .= $commands['CUT'];
        }
        
        return $output;
    }
    
    /**
     * Metni belirtilen uzunlukta kırp
     */
    private function truncateText($text, $length) {
        if (mb_strlen($text, 'UTF-8') > $length) {
            return mb_substr($text, 0, $length - 3, 'UTF-8') . '...';
        }
        return $text;
    }
    
    /**
     * USB yazıcıya gönder
     */
    public function printToUsb($data) {
        $printerName = $this->config['printer_name'];
        
        // Windows'ta yazıcıya gönder
        if (PHP_OS_FAMILY === 'Windows') {
            $tempFile = tempnam(sys_get_temp_dir(), 'pos_receipt_');
            file_put_contents($tempFile, $data);
            
            $command = "copy /b \"$tempFile\" \"\\\\localhost\\$printerName\"";
            exec($command, $output, $returnCode);
            
            unlink($tempFile);
            return $returnCode === 0;
        }
        
        return false;
    }
    
    /**
     * Ağ yazıcısına gönder
     */
    public function printToNetwork($data) {
        $ip = $this->config['printer_ip'];
        $port = $this->config['printer_port'];
        
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return false;
        }
        
        $result = socket_connect($socket, $ip, $port);
        if ($result === false) {
            socket_close($socket);
            return false;
        }
        
        socket_write($socket, $data, strlen($data));
        socket_close($socket);
        
        return true;
    }
    
    /**
     * Fiş yazdır
     */
    public function printReceipt($order) {
        $data = $this->formatReceipt($order);
        
        switch ($this->config['printer_type']) {
            case 'usb':
                return $this->printToUsb($data);
            case 'network':
                return $this->printToNetwork($data);
            default:
                return false;
        }
    }
    
    /**
     * Test sayfası yazdır
     */
    public function printTestPage() {
        $commands = $this->getEscPosCommands();
        $output = '';
        
        $output .= $commands['INIT'];
        $output .= $commands['ALIGN_CENTER'];
        $output .= $commands['FONT_LARGE'];
        $output .= $commands['BOLD_ON'];
        $output .= "YAZICI TEST SAYFASI\n";
        $output .= $commands['BOLD_OFF'];
        $output .= $commands['FONT_NORMAL'];
        $output .= $commands['LINE'] . "\n";
        
        $output .= "Tarih: " . date('d.m.Y H:i:s') . "\n";
        $output .= "Yazıcı: " . $this->config['printer_name'] . "\n";
        $output .= "Tip: " . $this->config['printer_type'] . "\n";
        $output .= $commands['LINE'] . "\n";
        
        $output .= "Normal yazı\n";
        $output .= $commands['BOLD_ON'] . "Kalın yazı\n" . $commands['BOLD_OFF'];
        $output .= $commands['UNDERLINE_ON'] . "Alt çizgili yazı\n" . $commands['UNDERLINE_OFF'];
        
        $output .= $commands['LINE'] . "\n";
        $output .= "Test başarılı!\n";
        $output .= $commands['FEED'];
        $output .= $commands['CUT'];
        
        switch ($this->config['printer_type']) {
            case 'usb':
                return $this->printToUsb($output);
            case 'network':
                return $this->printToNetwork($output);
            default:
                return false;
        }
    }
    
    /**
     * Konfigürasyonu güncelle
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
    }
    
    /**
     * Mevcut konfigürasyonu al
     */
    public function getConfig() {
        return $this->config;
    }
}
?>

