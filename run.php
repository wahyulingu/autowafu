<?php

require 'vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Container\Container;
use WahyuLingu\AutoWAFu\Main;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

$seleniumUrl = text(
    label: 'Masukkan URL Selenium Server:',
    placeholder: 'Contoh: http://localhost:4444/wd/hub',
    default: 'http://localhost:4444/wd/hub',
    hint: 'Ini adalah URL dari Selenium Server Anda.',
    validate: fn (string $value) => match (true) {
        strlen($value) < 10 => 'URL harus minimal 10 karakter.',
        strlen($value) > 255 => 'URL tidak boleh lebih dari 255 karakter.',
        ! filter_var($value, FILTER_VALIDATE_URL) => 'URL tidak valid.',
        default => null
    }
);

$sessionPath = text(
    label: 'Masukkan path untuk menyimpan sesi Selenium:',
    placeholder: 'Contoh: /path/to/session',
    default: './session',
    hint: 'Ini adalah path di mana sesi Selenium akan disimpan.',
    validate: fn (string $value) => match (true) {
        strlen($value) < 5 => 'Path harus minimal 5 karakter.',
        strlen($value) > 255 => 'Path tidak boleh lebih dari 255 karakter.',
        default => null
    }
);

$headless = confirm(
    label: 'Apakah Anda ingin menjalankan dalam mode headless?',
    default: false,
    hint: 'Mode headless tidak akan membuka jendela browser. Pastikan Anda sudah login sebelumnya.',
    yes: 'Ya',
    no: 'Tidak'
);

$container = new Container;

$container->bind(RemoteWebDriver::class, function () use ($seleniumUrl, $sessionPath, $headless) {
    $options = new ChromeOptions;

    // Gunakan user-data-dir agar sesi login tersimpan
    $options->addArguments(['--user-data-dir='.$sessionPath]);

    if ($headless) {
        // Jika mode headless, tambahkan opsi berikut
        $options->addArguments([
            '--headless=new', // Headless mode
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
        ]);
    } else {
        // Jika mode normal, jalankan dalam ukuran penuh
        $options->addArguments(['--start-maximized']);
    }

    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

    return RemoteWebDriver::create($seleniumUrl, $capabilities);
});

info('Memulai sesi...');

$container->make(Main::class)->run();
