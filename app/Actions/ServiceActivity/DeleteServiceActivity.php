<?php

namespace App\Actions\ServiceActivity;

use App\Models\ServiceActivity;
use DomainException;

class DeleteServiceActivity
{
    public function execute(ServiceActivity $serviceActivity): void
    {
        if ($serviceActivity->loans()->exists()) {
            throw new DomainException('Kegiatan pelayanan tidak dapat dihapus karena masih digunakan oleh transaksi peminjaman.');
        }

        $serviceActivity->delete();
    }
}
