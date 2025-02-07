<?php

namespace WahyuLingu\AutoWAFu\Driver;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use WahyuLingu\Piuu\HumanizedActions;

class WhatsappDriver
{
    public function __construct(
        public readonly RemoteWebDriver $remoteDriver,
        protected readonly HumanizedActions $humanizedActions) {}

    public function open()
    {
        $this->remoteDriver->get('https://web.whatsapp.com');
    }

    public function quit()
    {
        $this->remoteDriver->quit();
    }

    public function searchContact($contactName)
    {
        $searchBox = $this->remoteDriver->findElement(WebDriverBy::xpath("//div[@contenteditable='true']"));

        $this->humanizedActions->sendKeysHumanizedWithoutErrors($contactName, fn ($char) => $searchBox->sendKeys($char));
        $this->humanizedActions->clickHumanized(fn () => $searchBox->sendKeys(WebDriverKeys::ENTER));
    }

    public function sendMessage($message)
    {
        $messageBox = $this->remoteDriver->findElement(WebDriverBy::xpath('//div[@contenteditable="true"][@data-tab="10"]'));

        $this->humanizedActions->sendKeysHumanized($message, fn ($char) => $messageBox->sendKeys($char));
        $this->humanizedActions->clickHumanized(fn () => $messageBox->sendKeys(WebDriverKeys::ENTER));
    }

    public function sendMessageWithoutErrors($message)
    {
        $messageBox = $this->remoteDriver->findElement(WebDriverBy::xpath('//div[@contenteditable="true"][@data-tab="10"]'));

        $this->humanizedActions->sendKeysHumanizedWithoutErrors($message, fn ($char) => $messageBox->sendKeys($char));
        $this->humanizedActions->clickHumanized(fn () => $messageBox->sendKeys(WebDriverKeys::ENTER));
    }

    public function sendMediaMessage($filePath, $caption = '')
    {
        $attachButton = $this->remoteDriver->findElement(WebDriverBy::cssSelector('span[data-icon="clip"]'));
        $this->humanizedActions->clickHumanized(fn () => $attachButton->click());

        $fileInput = $this->remoteDriver->findElement(WebDriverBy::cssSelector('input[type="file"]'));
        $fileInput->sendKeys($filePath);

        if ($caption) {
            $captionBox = $this->remoteDriver->findElement(WebDriverBy::cssSelector('div[contenteditable="true"]'));
            $this->humanizedActions->sendKeysHumanized($caption, fn ($char) => $captionBox->sendKeys($char));
        }

        $sendButton = $this->remoteDriver->findElement(WebDriverBy::cssSelector('span[data-icon="send"]'));
        $this->humanizedActions->clickHumanized(fn () => $sendButton->click());
    }

    /**
     * Memulai obrolan dengan nomor telepon yang diberikan.
     *
     * @param  string  $phoneNumber  Nomor telepon untuk memulai obrolan.
     * @param  string  $holderName  Nama kontak. Kontak ini digunakan untuk menahan nomor sebelum diklik, bukan kontak target untuk pesan.
     * @return bool Mengembalikan true jika obrolan berhasil dimulai, false sebaliknya.
     */
    public function startChatWithNumber($phoneNumber, string $holderName): bool
    {
        $this->searchContact($holderName);

        $this->humanizedActions->clickHumanized(fn () => $this->remoteDriver->findElement(WebDriverBy::xpath("//a[contains(text(), '".$phoneNumber."')]"))->click());
        $this->humanizedActions->delay(100000, 200000);

        try {
            $this->humanizedActions->clickHumanized(fn () => $this->remoteDriver->findElement(WebDriverBy::xpath("//li[.//span[contains(text(), '".$this->formatPhoneNumber($phoneNumber)."')]]"))->click());
        } catch (NoSuchElementException) {
            return false;
        }

        return true;
    }

    public function holdPhoneNumbers(string $holderName, array $phoneNumbers)
    {
        $this->searchContact($holderName);
        $this->sendMessage(implode(', ', $phoneNumbers));
    }

    public function formatPhoneNumber($phoneNumber)
    {
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        if (substr($phoneNumber, 0, 2) !== '62') {
            $phoneNumber = '62'.ltrim($phoneNumber, '0');
        }

        return '+62 '.substr($phoneNumber, 2, 3).'-'.substr($phoneNumber, 5, 4).'-'.substr($phoneNumber, 9);
    }
}
