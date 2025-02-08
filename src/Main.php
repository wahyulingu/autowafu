<?php

namespace WahyuLingu\AutoWAFu;

use Psy\Shell;
use WahyuLingu\AutoWAFu\Driver\DatabaseDriver;
use WahyuLingu\AutoWAFu\Driver\WhatsappDriver;
use WahyuLingu\AutoWAFu\Mode\AutomaticMode;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;

class Main
{
    public function __construct(
        protected readonly WhatsappDriver $whatsappDriver,
        protected readonly DatabaseDriver $databaseDriver,
        protected readonly AutomaticMode $automaticMode) {}

    public function run()
    {
        $this->whatsappDriver->open();

        if (confirm('Apakah Anda ingin masuk ke mode interaktif? (Jika tidak, mode otomatis akan dijalankan)')) {
            $this->startInteractiveShell();
        } else {
            info('Mode interaktif dibatalkan.');
            $this->runAutomaticMode();
        }

        $this->quit();
    }

    private function quit()
    {
        $this->whatsappDriver->quit();

        info('Sesi selesai. Browser ditutup.');
    }

    private function startInteractiveShell()
    {
        $vars = [
            'whatsapp' => $this->whatsappDriver,
        ];

        info('Masuk ke mode interaktif. Anda bisa mengontrol Selenium whatsappDriver di shell!');

        $shell = new Shell;
        $shell->setScopeVariables($vars);
        $shell->run();
    }

    private function runAutomaticMode()
    {
        info('Memasuki mode otomatis...');

        $this->automaticMode->run();

    }
}
