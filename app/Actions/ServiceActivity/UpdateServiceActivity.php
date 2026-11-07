<?php

namespace App\Actions\ServiceActivity;

use App\Models\ServiceActivity;

class UpdateServiceActivity
{
    /**
     * @param array{name: string} $data
     */
    public function execute(ServiceActivity $serviceActivity, array $data): ServiceActivity
    {
        $serviceActivity->update([
            'name' => trim($data['name']),
        ]);

        return $serviceActivity;
    }
}
