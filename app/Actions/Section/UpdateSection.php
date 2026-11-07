<?php

namespace App\Actions\Section;

use App\Models\Section;

class UpdateSection
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Section $section, array $data): Section
    {
        $section->update([
            'name' => $data['name'],
        ]);

        return $section;
    }
}
