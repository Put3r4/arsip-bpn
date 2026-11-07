<?php

namespace App\Actions\LandRightType;

use App\Models\LandRightType;

class UpdateLandRightType
{
    /**
     * @param array{name: string} $data
     */
    public function execute(LandRightType $landRightType, array $data): LandRightType
    {
        $landRightType->update([
            'name' => trim($data['name']),
        ]);

        return $landRightType;
    }
}
