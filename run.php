<?php

require 'vendor/autoload.php';

use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Container\Container;
use WahyuLingu\AutoWAFu\Helpers\Terminal;
use WahyuLingu\AutoWAFu\Main;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

Terminal::clear(fn () => Container::getInstance(), function (Container $container) {
    $container->singleton(RemoteWebDriver::class, function () {
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

        $mode = select(
            label: 'Pilih Mode Firefox',
            hint: 'Mode Headless tidak akan membuka jendela Firefox. Pastikan Anda sudah login.',
            options: [
                'normal' => 'Mode Normal',
                'headless' => 'Mode Headless',
            ]
        );

        $options = new FirefoxOptions;

        // Gunakan profil agar sesi login tersimpan
        $options->addArguments(['-profile', $sessionPath]);

        if ($mode === 'headless') {
            // Jika mode headless, tambahkan opsi berikut
            $options->addArguments([
                '-headless', // Headless mode
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

        $capabilities = DesiredCapabilities::firefox();
        $capabilities->setCapability(FirefoxOptions::CAPABILITY, $options);

        return Terminal::clear(fn () => spin(fn () => RemoteWebDriver::create($seleniumUrl, $capabilities), 'Menjalankan Firefox...'));
    });

    $container->make(Main::class)->run();
});
