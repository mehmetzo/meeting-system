# 📅 Dijital Toplantı Katılım Sistemi

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![LDAP](https://img.shields.io/badge/LDAP-Active%20Directory-0078D4?style=for-the-badge&logo=microsoft&logoColor=white)

**QR kod tabanlı, kurumsal düzeyde dijital toplantı katılım yönetim sistemi.**

</div>

---

## 📋 İçindekiler

- [Özellikler](#-özellikler)
- [Gereksinimler](#-gereksinimler)
- [Kurulum](#-kurulum)
- [Kullanım](#-kullanım)
- [Admin Paneli](#-admin-paneli)
- [Ayarlar](#-ayarlar)
- [Güvenlik](#-güvenlik)
- [Proje Yapısı](#-proje-yapısı)

---

## ✨ Özellikler

### 📱 Katılım Ekranı
- 🔲 **QR Kod ile Katılım** — Uygulama indirmeye gerek yok, QR okutunca form açılır
- 👤 **Personel Girişi** — LDAP / Active Directory ile kimlik doğrulama
- 🙋 **Misafir Girişi** — Ad, kurum, unvan, e-posta ve telefon ile kayıt
- ✅ **Anlık Onay** — Kayıt tamamlandığında animasyonlu teşekkür ekranı
- 🔒 **Tamamlanmış Toplantı Koruması** — Tamamlanan toplantılara yeni kayıt engeli

### 🖥️ Admin Paneli
- 📊 **Gösterge Paneli** — Gerçek zamanlı istatistikler ve Chart.js grafikleri
- 📋 **Toplantı Yönetimi** — Oluşturma, listeleme, filtreleme, silme
- 🔲 **QR Kod Üretimi** — Her toplantı için otomatik QR kod ve yazdırma desteği
- 📄 **Raporlama** — Katılımcı listesi, personel/misafir ayrımı
- 📤 **Dışa Aktarma** — CSV ve PDF formatında toplantı bazlı rapor
- 📜 **Erişim Logları** — Tüm kullanıcı işlemlerinin kayıt altına alınması
- 🔐 **LDAP ile Admin Girişi** — Kurumsal AD hesaplarıyla yönetici erişimi

### ⚙️ Sistem Ayarları *(Admin Panelinden)*
- 🏥 **Kurum Bilgileri** — Kurum adı, logo, footer metni, tema rengi
- 🔐 **LDAP / Active Directory** — Kurumsal hesaplarla giriş ve grup bazlı yetkilendirme
- 👥 **Admin Kullanıcılar** — Çoklu yönetici desteği, rol yönetimi
- 🔑 **Şifre Değiştirme** — Admin şifresi güvenli değiştirme

---

## 📦 Gereksinimler

- [Docker](https://docs.docker.com/get-docker/) 20.10+
- [Docker Compose](https://docs.docker.com/compose/install/) v2+
- 512MB RAM (minimum)

---

🚀 Kurulum
1. Repoyu klonlayın
```bash
git clone https://github.com/mehmetzo/meeting-system.git
cd meeting-system
```
2. Klasör izinlerini ayarlayın
```bash
chmod -R 777 html/assets/img/
```
2. Container'ları başlatın
```bash
docker compose up -d --build
```
4. Admin girişi
```
URL      : http://SUNUCU_IP:6767/admin/login.php
Kullanıcı: admin
Şifre    : admin
```

🌐 Erişim Adresleri

| Sayfa | URL |
|-------|-----|
| 🏠 Karşılama | `http://SUNUCU_IP:6767/` |
| 🔧 Admin Paneli | `http://SUNUCU_IP:6767/admin/login.php` |
| 🗄️ Veritabanı (dış) | `SUNUCU_IP:3310` |

```

📱 Kullanım
Toplantı Akışı aşağıdaki gibi gerçekleşir.
- QR kodu toplantı salonuna asın veya ekrana yansıtın. Katılımcılar telefon kamerasıyla okutarak forma ulaşır.
- QR Okut → Personel / Misafir Seç → Formu Doldur → Kaydet → Onay Ekranı
- QR Okut → Personel → LDAP Kullanıcı Adı & Şifre → Kaydet
- QR Okut → Misafir → Ad Soyad, Kurum, Unvan, E-posta, Telefon → Kaydet
```
⚙️ Ayarlar

Tüm sistem ayarları **Admin Paneli → Ayarlar** üzerinden yapılır, herhangi bir dosya düzenlemeye gerek yok.

```
🔐 LDAP Ayarları
- Host      : Active Directory sunucu IP
- Port      : 389
- Base DN   : dc=domain,dc=local
- Domain    : domain.local
- Bind User : servis_hesabi
- Grup      : yetkili_grup (opsiyonel)
```
