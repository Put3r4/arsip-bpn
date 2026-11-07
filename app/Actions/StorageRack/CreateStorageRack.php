<?php

namespace App\Actions\StorageRack;

use App\Models\StorageRack;

class CreateStorageRack
{
    /**
     * @param array{name: string} $data
     */
    public function execute(array $data): StorageRack
    {
        return StorageRack::create([
            'name' => trim($data['name']),
        ]);
    }
}
