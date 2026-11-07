<?php

namespace App\Actions\Section;

use App\Models\Section;

class CreateSection
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): Section
    {
        return Section::create([
            'name' => $data['name'],
        ]);
    }
}
