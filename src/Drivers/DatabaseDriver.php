<?php

namespace WahyuLingu\AutoWAFu\Drivers;

use Illuminate\Support\Collection;

class DatabaseDriver
{
    private string $filePath;

    private Collection $data;

    private $error;

    public function __construct($filePath = './data.json')
    {
        $this->filePath = $filePath;
        $this->initializeData();
    }

    private function initializeData()
    {
        if (! file_exists($this->filePath)) {
            $this->data = collect([]);
            $this->saveData();
        } else {
            $this->loadData();
        }
    }

    public function loadFromFile($filePath)
    {
        $this->filePath = $filePath;
        $this->loadData();
    }

    private function loadData()
    {
        try {
            $json = file_get_contents($this->filePath);
            $this->data = collect(json_decode($json, true));
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    private function saveData()
    {
        try {
            $json = json_encode($this->data->toArray(), JSON_PRETTY_PRINT);
            file_put_contents($this->filePath, $json);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    private function handleError(\Exception $e)
    {
        $this->error = $e->getMessage();
        throw new \Exception($this->error);
    }

    public function fetchAll()
    {
        return $this->data;
    }

    public function fetch($key)
    {
        return $this->data->get($key);
    }

    public function execute($key, $value)
    {
        $this->data->put($key, $value);
        $this->saveData();

        return true;
    }

    public function getError()
    {
        return $this->error;
    }

    public function updateContactStatus($key, $status)
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

    public function markAsWhatsApp($phoneNumber)
    {
        $updated = false;
        $this->data = $this->data->map(function ($item) use ($phoneNumber, &$updated) {
            if (isset($item['nomorHp']) && $item['nomorHp'] === $phoneNumber) {
                $item['isWhatsApp'] = true;
                $updated = true;
            }

            return $item;
        });

        if ($updated) {
            $this->saveData();
        }

        return $updated;
    }

    public function markAsContacted($phoneNumber)
    {
        return $this->updateStatusByPhoneNumber($phoneNumber, true);
    }

    public function updateStatusByPhoneNumber($phoneNumber, $status)
    {
        $updated = false;
        $this->data = $this->data->map(function ($item) use ($phoneNumber, $status, &$updated) {
            if (isset($item['nomorHp']) && $item['nomorHp'] === $phoneNumber) {
                $item['sudahDihubungi'] = $status;
                $updated = true;
            }

            return $item;
        });

        if ($updated) {
            $this->saveData();
        }

        return $updated;
    }

    public function chunkData($size): Collection
    {
        return $this->data->chunk($size);
    }

    public function processChunks($size, callable $callback)
    {
        $this->chunkData($size)->each($callback);
    }

    public function searchByPhoneNumber($phoneNumber)
    {
        return $this->data->firstWhere('nomorHp', $phoneNumber);
    }

    public function getFollowedUpRecords($status = true): Collection
    {
        return $this->data->filter(function ($item) use ($status) {
            return isset($item['sudahDihubungi']) && $item['sudahDihubungi'] === $status;
        });
    }

    public function getNotFollowedUpRecords()
    {
        return $this->getFollowedUpRecords(false);
    }

    public function count()
    {
        return $this->data->count();
    }
}
