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
use function Laravel\Prompts\textarea;

class Main
{
    protected string $holderName = '';

    protected string $copyWriting = '';

    protected bool $shouldStop = false;

    public function __construct(
        protected readonly WhatsappDriver $whatsappDriver,
        protected readonly DatabaseDriver $databaseDriver,
        protected readonly HumanizedActions $humanizedActions,
        protected readonly Terminal $terminal,
        protected readonly Shell $shell) {}

    protected function setHolderName(string $holderName)
    {
        $this->holderName = $holderName;
    }

    protected function setCopywriting(string $copyWriting)
    {
        $this->copyWriting = $copyWriting;
    }

    protected function parseCopywriting(array $replacements)
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($replacements) {
            $tagName = $matches[1];

            return isset($replacements[$tagName]) ? $replacements[$tagName] : $matches[0];
        }, $this->copyWriting);
    }

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

        HelpersTerminal::clear(fn () => info("Mode Otomatis dipilih, menemukan {$notFollowedRecords->count()} dari {$this->databaseDriver->count()} record database yang belum difu"));

        $this->setCopywriting(textarea(
            label: 'Masukkan copywriting pesan:',
            placeholder: 'Contoh: Halo {nama}, bagaimana kabar Anda?',
            hint: 'Gunakan {nama} sebagai placeholder untuk nama kontak.',
            validate: fn (string $value) => empty($value) ? 'Copywriting tidak boleh kosong.' : null
        ));

        $this->setHolderName(text(
            label: 'Masukkan nama kontak untuk menahan kumpulan nomor:',
            placeholder: 'Contoh: John Doe',
            hint: 'Nomor WA dari record yang akan diproses dikirim ke kontak ini terlebih dahulu.',
            validate: fn (string $value) => empty($value) ? 'Nama kontak tidak boleh kosong.' : null
        ));

        $notFollowedRecords
            ->chunk(text(
                label: 'Segmentasi database:',
                placeholder: 'Contoh: 40',
                hint: 'Jumlah database yang akan diproses dalam satu waktu.',
                validate: fn (string $value) => match (true) {
                    ! is_numeric($value) => 'Input harus berupa angka.',
                    intval($value) <= 0 => 'Jumlah database harus lebih dari 0.',
                    default => null
                }
            ))

            ->each(function (Collection $data) {

                if ($this->shouldStop) {
                    return false;
                }

                $this->holdDatabase($this->holderName, $data);

                $this->sendMessage($data->first());

                $data->splice(1)->each(function (array $record) {
                    if ($this->shouldStop) {
                        return false;
                    }

                    $this->findHolder($this->holderName);
                    $this->humanizedActions->delay(400000, 600000);
                    $this->sendMessage($record);
                });

            });
    }

    protected function sendMessage(array $record)
    {
        return tap($this->whatsappDriver->startChatFromBubble($record['nomorHp']), function ($isWhatsApp) use ($record) {
            if ($isWhatsApp) {
                $this->databaseDriver->markAsWhatsApp($record['nomorHp']);

                $this->humanizedActions->delay(600000, 800000);
                $this->whatsappDriver->sendMessage($this->parseCopywriting($record));
                $this->databaseDriver->markAsContacted($record['nomorHp']);
            }
        });
    }

    protected function findHolder(string $holderName)
    {
        HelpersTerminal::spin(
            clear: true,
            message: "Mencari kontak holder {$holderName}...",
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
