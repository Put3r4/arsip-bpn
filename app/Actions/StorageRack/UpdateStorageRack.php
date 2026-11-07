<?php

namespace App\Actions\StorageRack;

use App\Models\StorageRack;

class UpdateStorageRack
{
    /**
     * @param array{name: string} $data
     */
    public function execute(StorageRack $storageRack, array $data): StorageRack
    {
        $storageRack->update([
            'name' => trim($data['name']),
        ]);

        return $storageRack;
    }
}
