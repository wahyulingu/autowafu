# AutoWAFu

AutoWAFu adalah proyek PHP yang digunakan untuk melakukan follow-up database melalui WhatsApp secara otomatis menggunakan Selenium WebDriver.

## Fitur Utama

- Mengirim pesan WhatsApp secara otomatis.
- Menggunakan Selenium WebDriver untuk mengontrol browser.
- Mendukung interaksi melalui terminal dengan Laravel Prompts.

## Prasyarat

Sebelum menjalankan proyek ini, pastikan Anda telah menginstal:

- PHP (versi 8.0 atau lebih baru)
- Composer
- Selenium WebDriver
- Chrome atau Firefox (beserta WebDriver-nya)

## Instalasi

```sh
git clone https://github.com/wahyulingu/autowafu.git
cd autowafu
composer install
```

Pastikan WebDriver telah berjalan (contoh untuk Chrome):

```sh
chromedriver --port=4444
```

## Cara Menjalankan

Untuk menjalankan aplikasi, gunakan perintah berikut:

```sh
composer start
```

atau secara manual dengan:

```sh
php run.php
```

## Lisensi

Proyek ini berada di bawah lisensi MIT.

## Kontributor

- Wahyu ([wahyulingu@gmail.com](mailto:wahyulingu@gmail.com))

## Catatan Tambahan

Pastikan Selenium WebDriver berjalan sebelum menjalankan skrip untuk menghindari error. Anda juga dapat menyesuaikan script agar sesuai dengan kebutuhan follow-up database yang lebih spesifik.
