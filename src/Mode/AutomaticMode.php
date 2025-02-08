<?php

namespace WahyuLingu\AutoWAFu\Mode;

use Illuminate\Support\Collection;
use Laravel\Prompts\Terminal;
use WahyuLingu\AutoWAFu\Driver\DatabaseDriver;
use WahyuLingu\AutoWAFu\Driver\WhatsappDriver;
use WahyuLingu\Piuu\HumanizedActions;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class AutomaticMode
{
    protected bool $shouldStop = false;

    public function __construct(
        protected readonly WhatsappDriver $whatsappDriver,
        protected readonly DatabaseDriver $databaseDriver,
        protected readonly HumanizedActions $humanizedActions,
        protected readonly Terminal $terminal) {}

    public function stop(): void
    {
        $this->shouldStop = true;
        alert('Proses dihentikan oleh pengguna.');
    }

    public function run()
    {
        $this->terminal->setTty('-echoctl');

        pcntl_signal(SIGINT, function () {

            $this->stop();
            $this->whatsappDriver->stopTyping();
        });

        pcntl_async_signals(true);

        $chunkSize = text(
            label: 'Masukkan jumlah database per segmen:',
            placeholder: 'Contoh: 10',
            default: '10',
            hint: 'Ini adalah jumlah database per segmen.',
            validate: fn (string $value) => match (true) {
                ! is_numeric($value) => 'Input harus berupa angka.',
                intval($value) <= 0 => 'Jumlah database per segmen harus lebih dari 0.',
                default => null
            }
        );

        $holderName = text(
            label: 'Masukkan nama kontak untuk menahan kumpulan nomor:',
            placeholder: 'Contoh: John Doe',
            hint: 'Nama kontak tidak boleh kosong.',
            validate: fn (string $value) => empty($value) ? 'Nama kontak tidak boleh kosong.' : null
        );

        info('Membaca database...');
        info("Memecah database menjadi {$chunkSize} record persegment");
        $this->databaseDriver->processChunks($chunkSize, function (Collection $data) use ($holderName) {

            if ($this->shouldStop) {
                return false;
            }

            spin(
                message: 'Menampung record ke dalam obrolan untuk mempermudah pembukaan chat...',
                callback: fn () => $this->humanizedActions->clickHumanized(function () use ($data, $holderName) {
                    $this->whatsappDriver->holdPhoneNumbers($holderName, $data->pluck('nomorHp')->toArray());
                }));
        });
    }
}
