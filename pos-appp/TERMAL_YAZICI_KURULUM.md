# 🖨️ Termal Yazıcı Kurulum Rehberi

## 📋 Desteklenen Yazıcılar

### ✅ **USB Termal Yazıcılar**
- **POS-80** (80mm kağıt)
- **POS-58** (58mm kağıt)
- **Epson TM-T20, TM-T82, TM-T88**
- **Star TSP100, TSP650**
- **Citizen CT-S310, CT-S400**

### ✅ **Ağ Termal Yazıcılar**
- **Epson TM-T88V** (Ethernet)
- **Star TSP143** (Ethernet)
- **Citizen CT-S310** (Ethernet)
- **Bixolon SRP-350** (Ethernet)

---

## 🔧 USB Yazıcı Kurulumu

### Adım 1: Yazıcı Sürücüsü Kurulumu
1. Yazıcıyı USB portuna bağlayın
2. Windows otomatik olarak tanıyacak
3. Eğer tanımazsa, üreticinin web sitesinden sürücü indirin
4. Sürücüyü kurun

### Adım 2: Windows Yazıcı Kurulumu
1. **Windows Ayarlar** → **Cihazlar** → **Yazıcılar ve tarayıcılar**
2. **"Yazıcı veya tarayıcı ekle"** tıklayın
3. **"Aradığım yazıcı listede yok"** seçin
4. **"Yerel yazıcı veya ağ yazıcısı"** seçin
5. **"Mevcut port kullan"** seçin
6. **"Yeni port"** → **"Local Port"** seçin
7. Port adı: `POS-80` (veya istediğiniz isim)
8. **"İleri"** tıklayın
9. **"Windows Update"** ile sürücü arayın
10. Yazıcı modelinizi seçin ve kurun

### Adım 3: POS Sisteminde Ayarlama
1. `http://localhost/pos-app/printer_management.php` adresine gidin
2. **"Yazıcı Türü"** → **"USB Yazıcı"** seçin
3. **"Windows Yazıcı Adı"** → Yazıcı adını girin (örn: POS-80)
4. **"Ayarları Kaydet"** tıklayın

---

## 🌐 Ağ Yazıcısı Kurulumu

### Adım 1: Yazıcı Ağ Ayarları
1. Yazıcıyı Ethernet kablosu ile router'a bağlayın
2. Yazıcının IP adresini öğrenin (yazıcı menüsünden)
3. Varsayılan IP genellikle: `192.168.1.100` veya `192.168.0.100`

### Adım 2: Windows Ağ Yazıcısı Kurulumu
1. **Windows Ayarlar** → **Cihazlar** → **Yazıcılar ve tarayıcılar**
2. **"Yazıcı veya tarayıcı ekle"** tıklayın
3. **"Aradığım yazıcı listede yok"** seçin
4. **"TCP/IP adresi veya ana bilgisayar adı ile yazıcı ekle"** seçin
5. **"Ana bilgisayar adı veya IP adresi"** → Yazıcı IP'sini girin
6. **"İleri"** tıklayın
7. Yazıcı modelini seçin ve kurun

### Adım 3: POS Sisteminde Ayarlama
1. `http://localhost/pos-app/printer_management.php` adresine gidin
2. **"Yazıcı Türü"** → **"Ağ Yazıcısı"** seçin
3. **"Yazıcı IP Adresi"** → Yazıcı IP'sini girin
4. **"Port"** → `9100` (varsayılan)
5. **"Ayarları Kaydet"** tıklayın

---

## 🧪 Yazıcı Testi

### Test Sayfası Yazdırma
1. `http://localhost/pos-app/printer_management.php` adresine gidin
2. **"Test Sayfası Yazdır"** butonuna tıklayın
3. Yazıcıdan test sayfası çıkmalı

### Sipariş Fişi Yazdırma
1. Bir sipariş oluşturun
2. Sipariş kaydedildikten sonra **"Termal Yazıcıdan Yazdır"** butonuna tıklayın
3. Fiş yazıcıdan çıkmalı

---

## ⚙️ Yazıcı Ayarları

### Kağıt Boyutu Seçimi
- **80mm**: Standart restoran fişi (48 karakter/satır)
- **58mm**: Küçük fiş (32 karakter/satır)

### Font Ayarları
- **Normal**: Standart boyut
- **Küçük**: Daha fazla bilgi sığar
- **Büyük**: Daha okunabilir

### Şirket Bilgileri
- **Şirket Adı**: Fiş başlığında görünür
- **Adres**: Şirket adresi
- **Telefon**: İletişim bilgisi
- **Vergi No**: Vergi numarası

---

## 🔧 Sorun Giderme

### Yazıcı Çalışmıyor
1. **USB Bağlantısı**: Kabloyu kontrol edin
2. **Ağ Bağlantısı**: IP adresini ping edin
3. **Sürücü**: Yazıcı sürücüsünü yeniden kurun
4. **Port**: Windows'ta port ayarlarını kontrol edin

### Fiş Düzgün Çıkmıyor
1. **Kağıt Boyutu**: Doğru kağıt genişliğini seçin
2. **Font Boyutu**: Font ayarını değiştirin
3. **Hizalama**: Yazıcı ayarlarını kontrol edin

### Ağ Yazıcısı Bağlanmıyor
1. **IP Adresi**: Doğru IP adresini girin
2. **Port**: 9100 portunu kontrol edin
3. **Firewall**: Windows Firewall'ı kontrol edin
4. **Router**: Ağ bağlantısını kontrol edin

### Windows Yazıcı Hatası
1. **Yazıcı Adı**: Doğru yazıcı adını girin
2. **Port**: Local port ayarlarını kontrol edin
3. **İzinler**: Yazıcı izinlerini kontrol edin

---

## 📱 Mobil Uyumluluk

### Android Uygulamaları
- **Star Print**: Star yazıcılar için
- **Epson ePOS-Print**: Epson yazıcılar için
- **Citizen Print**: Citizen yazıcılar için

### iOS Uygulamaları
- **Star Print**: Star yazıcılar için
- **Epson ePOS-Print**: Epson yazıcılar için

---

## 🎯 Önerilen Yazıcılar

### **Başlangıç Seviyesi**
- **Epson TM-T20**: USB, 80mm, uygun fiyat
- **Star TSP100**: USB, 80mm, güvenilir

### **Profesyonel Seviye**
- **Epson TM-T88V**: Ağ, 80mm, hızlı
- **Star TSP143**: Ağ, 80mm, dayanıklı

### **Mobil Uyumlu**
- **Epson TM-T88VI**: Bluetooth, 80mm
- **Star TSP650**: Bluetooth, 80mm

---

## 💡 İpuçları

### Yazıcı Bakımı
- Kağıt bittiğinde hemen değiştirin
- Yazıcı kafasını temizleyin
- Toz ve nemden koruyun

### Performans
- Hızlı yazdırma için ağ yazıcısı kullanın
- Büyük fişler için 80mm kağıt seçin
- Çok sık yazdırma yapıyorsanız profesyonel yazıcı alın

### Güvenlik
- Ağ yazıcısı kullanıyorsanız güvenli ağda çalıştırın
- Yazıcı şifrelerini değiştirin
- Düzenli olarak yazıcı loglarını kontrol edin

---

**Not**: Bu rehber genel kurulum adımlarını içerir. Spesifik yazıcı modelleri için üretici dokümantasyonunu kontrol edin.

