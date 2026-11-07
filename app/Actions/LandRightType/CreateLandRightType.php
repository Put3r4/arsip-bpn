<?php

namespace App\Actions\LandRightType;

use App\Models\LandRightType;

class CreateLandRightType
{
    /**
     * @param array{name: string} $data
     */
    public function execute(array $data): LandRightType
    {
        return LandRightType::create([
            'name' => trim($data['name']),
        ]);
    }
}
