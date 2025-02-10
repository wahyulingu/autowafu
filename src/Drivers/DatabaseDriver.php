<?php

namespace WahyuLingu\AutoWAFu\Drivers;

use Illuminate\Support\Collection;

class DatabaseDriver
{
    private string $filePath;

    private Collection $data;

    private $error;

    /**
     * DatabaseDriyr constructor.
     *
     * @param  string  $filePath
     */
    public function __construct($filePath = './data.json')
    {
        $this->filePath = $filePath;
        $this->initializeData();
    }

    /**
     * Initialize data from the file or create a new collection.
     */
    private function initializeData()
    {
        if (! file_exists($this->filePath)) {
            $this->data = collect([]);
            $this->saveData();
        } else {
            $this->loadData();
        }
    }

    /**
     * Load data from a specified file.
     *
     * @param  string  $filePath
     */
    public function loadFromFile($filePath)
    {
        $this->filePath = $filePath;
        $this->loadData();
    }

    /**
     * Load data from the file.
     */
    private function loadData()
    {
        try {
            $json = file_get_contents($this->filePath);
            $this->data = collect(json_decode($json, true));
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Save data to the file.
     */
    private function saveData()
    {
        try {
            $json = json_encode($this->data->toArray(), JSON_PRETTY_PRINT);
            file_put_contents($this->filePath, $json);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle errors by setting the error message and throwing an exception.
     *
     * @throws \Exception
     */
    private function handleError(\Exception $e)
    {
        $this->error = $e->getMessage();
        throw new \Exception($this->error);
    }

    /**
     * Fetch all data.
     */
    public function fetchAll(): Collection
    {
        return $this->data;
    }

    /**
     * Fetch data by key.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function fetch($key)
    {
        return $this->data->get($key);
    }

    /**
     * Execute an update or insert operation.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     */
    public function execute($key, $value): bool
    {
        $this->data->put($key, $value);
        $this->saveData();

        return true;
    }

    /**
     * Get the last error message.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Update the contact status by key.
     *
     * @param  mixed  $key
     */
    public function updateContactStatus($key, bool $status): bool
    {
        if ($this->data->has($key)) {
            $contact = $this->data->get($key);
            $contact['sudahDihubungi'] = $status;
            $this->data->put($key, $contact);
            $this->saveData();

            return true;
        }

        return false;
    }

    /**
     * Mark a contact as not using WhatsApp by phone number.
     */
    public function markAsNotWhatsApp(string $phoneNumber): bool
    {
        return $this->updateStatusByPhoneNumber($phoneNumber, false, 'isWhatsApp');
    }

    /**
     * Check if a contact is marked as using WhatsApp by phone number.
     */
    public function isMarkedAsWhatsApp(string $phoneNumber): bool
    {
        $contact = $this->searchByPhoneNumber($phoneNumber);

        return isset($contact['isWhatsApp']) ? $contact['isWhatsApp'] : false;
    }

    /**
     * Mark a contact as not using WhatsApp by phone number.
     */
    public function markAsWhatsApp(string $phoneNumber): bool
    {
        return $this->updateStatusByPhoneNumber($phoneNumber, false, 'isWhatsApp');
    }

    /**
     * Mark a contact as contacted by phone number.
     */
    public function markAsContacted(string $phoneNumber): bool
    {
        return $this->updateStatusByPhoneNumber($phoneNumber, true, 'sudahDihubungi');
    }

    /**
     * Update a specific status by phone number.
     */
    private function updateStatusByPhoneNumber(string $phoneNumber, bool $status, string $statusKey): bool
    {
        $updated = false;
        $this->data = $this->data->map(function ($item) use ($phoneNumber, $status, $statusKey, &$updated) {
            if (isset($item['nomorHp']) && $item['nomorHp'] === $phoneNumber) {
                $item[$statusKey] = $status;
                $updated = true;
            }

            return $item;
        });

        if ($updated) {
            $this->saveData();
        }

        return $updated;
    }

    /**
     * Chunk the data into smaller collections.
     */
    public function chunkData(int $size): Collection
    {
        return $this->data->chunk($size);
    }

    /**
     * Process each chunk of data with a callback.
     */
    public function processChunks(int $size, callable $callback)
    {
        $this->chunkData($size)->each($callback);
    }

    /**
     * Search for a record by phone number.
     *
     * @return mixed
     */
    public function searchByPhoneNumber(string $phoneNumber)
    {
        return $this->data->firstWhere('nomorHp', $phoneNumber);
    }

    /**
     * Check if a contact is marked as not using WhatsApp by phone number.
     */
    public function isMarkedAsNotWhatsApp(string $phoneNumber): bool
    {
        $contact = $this->searchByPhoneNumber($phoneNumber);

        return isset($contact['isWhatsApp']) ? ! $contact['isWhatsApp'] : false;
    }

    public function getFollowedUpRecords(bool $status = true): Collection
    {
        return $this->data->filter(function ($item) use ($status) {
            return isset($item['sudahDihubungi']) && $item['sudahDihubungi'] === $status;
        });
    }

    /**
     * Get all records that have not been followed up.
     */
    public function getNotFollowedUpRecords(): Collection
    {
        return $this->getFollowedUpRecords(false);
    }

    /**
     * Count the number of records.
     */
    public function count(): int
    {
        return $this->data->count();
    }
}
