<?php

namespace WahyuLingu\AutoWAFu;

use Illuminate\Support\Collection;
use Laravel\Prompts\Terminal;
use Psy\Shell;
use WahyuLingu\AutoWAFu\Drivers\DatabaseDriver;
use WahyuLingu\AutoWAFu\Drivers\WhatsappDriver;
use WahyuLingu\AutoWAFu\Helpers\Terminal as HelpersTerminal;
use WahyuLingu\Piuu\HumanizedActions;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class Main
{
    protected bool $shouldStop = false;

    public function __construct(
        protected readonly WhatsappDriver $whatsappDriver,
        protected readonly DatabaseDriver $databaseDriver,
        protected readonly HumanizedActions $humanizedActions,
        protected readonly Terminal $terminal,
        protected readonly Shell $shell) {}

    public function run()
    {
        HelpersTerminal::clear(fn () => spin(fn () => $this->whatsappDriver->open(), 'Membuka WhatsApp Web...'));

        HelpersTerminal::clear(function () {

            $mode = select('Pilih Mode Operasi:',
                options: [
                    'auto' => 'Mode Otomatis Penuh',
                    'shell' => 'Mode Shell Interaktif',
                ],

                hint: 'Mode Shell Interaktif memungkinkan interaksi manual.');

            if ($mode == 'shell') {
                $this->startInteractiveShell();
            } else {
                $this->runAutomaticMode();
            }
        });

        $this->quit();
    }

    protected function stop()
    {
        $this->shouldStop = true;
        $this->whatsappDriver->stopTyping();
    }

    protected function quit()
    {
        $this->whatsappDriver->quit();
    }

    private function startInteractiveShell()
    {
        HelpersTerminal::clear(fn () => info('Masuk ke mode interaktif. Anda bisa mengontrol WhatsApp Web di shell!'));

        $this->shell->setScopeVariables(['whatsapp' => $this->whatsappDriver]);

        $this->shell->run();
    }

    private function runAutomaticMode()
    {
        $notFollowedRecords = $this->databaseDriver->getNotFollowedUpRecords();
        info("Mode Otomatis dipilih, menemukan {$notFollowedRecords->count()} dari {$this->databaseDriver->count()} record database yang belum difu");

        $chunkSize = text(
            label: 'Segmentasi database:',
            placeholder: 'Contoh: 40',
            hint: 'Jumlah database yang akan diproses dalam satu waktu.',
            validate: fn (string $value) => match (true) {
                ! is_numeric($value) => 'Input harus berupa angka.',
                intval($value) <= 0 => 'Jumlah database harus lebih dari 0.',
                default => null
            }
        );

        $holderName = text(
            label: 'Masukkan nama kontak untuk menahan kumpulan nomor:',
            placeholder: 'Contoh: John Doe',
            hint: 'Nomor WA dari record yang akan diproses dikirin ke kontak ini terlebih dahulu untuk membuka obrolan.',
            validate: fn (string $value) => empty($value) ? 'Nama kontak tidak boleh kosong.' : null
        );

        $notFollowedRecords->chunkData($chunkSize, function (Collection $data) use ($holderName) {

            if ($this->shouldStop) {
                return false;
            }

            $this->holdDatabase($holderName, $data);
            $data->each(function (array $record) {
                //
            });
        });
    }

    protected function findHolder(string $holderName)
    {
        HelpersTerminal::spin(
            clear: true,
            message: 'Mencari kontak holder...',
            callback: fn () => $this->whatsappDriver->searchContact($holderName));
    }

    protected function holdDatabase(string $holderName, Collection $data)
    {
        HelpersTerminal::spin(
            clear: true,
            message: 'Menampung record ke dalam obrolan untuk mempermudah pembukaan chat...',
            callback: fn () => $this->humanizedActions->clickHumanized(function () use ($data, $holderName) {
                $this->whatsappDriver->holdPhoneNumbers($holderName, $data->pluck('nomorHp')->toArray());
            }));
    }
}
