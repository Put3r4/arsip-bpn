<?php

namespace App\Actions\ServiceActivity;

use App\Models\ServiceActivity;

class CreateServiceActivity
{
    /**
     * @param array{name: string} $data
     */
    public function execute(array $data): ServiceActivity
    {
        return ServiceActivity::create([
            'name' => trim($data['name']),
        ]);
    }
}
