<?php

namespace WahyuLingu\AutoWAFu\Drivers;

use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverWait;
use WahyuLingu\Piuu\HumanizedActions;

class WhatsappDriver
{
    public function __construct(
        public readonly RemoteWebDriver $remoteDriver,
        protected readonly HumanizedActions $humanizedActions
    ) {}

    public function open()
    {
        $this->remoteDriver->get('https://web.whatsapp.com');
    }

    public function stopTyping(): void
    {
        $this->humanizedActions->stopTyping();
    }

    public function quit()
    {
        $this->remoteDriver->quit();
    }

    public function searchContact($contactName)
    {
        $searchBox = $this->remoteDriver->findElement(WebDriverBy::xpath("//div[@contenteditable='true']"));
        $this->humanizedActions->clickHumanized(fn () => $searchBox->clear());
        $this->humanizedActions->sendKeysHumanizedWithoutErrors($contactName, fn ($char) => $searchBox->sendKeys($char));
        $this->humanizedActions->clickHumanized(fn () => $searchBox->sendKeys(WebDriverKeys::ENTER));
    }

    public function sendMessage($message)
    {
        try {
            $wait = new WebDriverWait($this->remoteDriver, 10);
            $messageBox = $wait->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::xpath('//div[@contenteditable="true"][@data-tab="10"]')
            ));

            $this->humanizedActions->sendKeysHumanized($message, fn ($char) => $messageBox->sendKeys($char));
            $this->humanizedActions->clickHumanized(fn () => $messageBox->sendKeys(WebDriverKeys::ENTER));
        } catch (StaleElementReferenceException) {
            $this->sendMessage($message); // Coba lagi jika elemen hilang
        }
    }

    public function sendMessageWithoutTypo($message, ?callable $callback = null)
    {
        try {
            $messageBox = $this->remoteDriver->findElement(WebDriverBy::xpath('//div[@contenteditable="true"][@data-tab="10"]'));
            $this->humanizedActions->sendKeysHumanizedWithoutErrors($message, fn ($char) => $messageBox->sendKeys($char), $callback);
            $this->humanizedActions->clickHumanized(fn () => $messageBox->sendKeys(WebDriverKeys::ENTER));
        } catch (StaleElementReferenceException) {
            $this->sendMessageWithoutTypo($message, $callback); // Coba lagi jika elemen hilang
        }
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

    public function startChatFromBubble($phoneNumber): bool
    {
        $wait = new WebDriverWait($this->remoteDriver, 5);

        try {
            $phoneNumberElement = $wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::xpath("//a[contains(text(), '".$phoneNumber."')]")
                )
            );
        } catch (ElementClickInterceptedException) {
            $copyButton = $wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::xpath("//li[.//span[contains(text(), 'Salin nomor telepon')]]")
                )
            );

            $this->humanizedActions->clickHumanized(fn () => $copyButton->click());

            return $this->humanizedActions->clickHumanized(fn () => $this->startChatFromBubble($phoneNumber));
        }

        $this->humanizedActions->clickHumanized(fn () => $phoneNumberElement->click());

        try {
            $chatButton = $wait->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::xpath("//li[.//span[contains(text(), '".$this->formatPhoneNumber($phoneNumber)."')]]")
                )
            );
            $this->humanizedActions->clickHumanized(fn () => $chatButton->click());
        } catch (TimeoutException) {

            return false;
        }

        return true;
    }

    public function holdPhoneNumbers(string $holderName, array $phoneNumbers)
    {
        $this->searchContact($holderName);
        $this->sendMessageWithoutTypo(implode(', ', $phoneNumbers));
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
