<?php

namespace Tests\Unit\Models;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\ArchiveDocument;
use App\Models\District;
use App\Models\DocumentLoan;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\Section;
use App\Models\ServiceActivity;
use App\Models\StorageRack;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentRelationsAndEnumsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_roles_enum_and_helpers(): void
    {
        $this->assertSame('super_admin', UserRole::SUPER_ADMIN->value);
        $this->assertSame('archivist', UserRole::ARCHIVIST->value);
        $this->assertSame('employee', UserRole::EMPLOYEE->value);

        $superAdmin = User::factory()->superAdmin()->create();
        $archivist = User::factory()->archivist()->create();
        $employee = User::factory()->employee()->create();

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->canManageArchives());

        $this->assertTrue($archivist->isArchivist());
        $this->assertTrue($archivist->canManageArchives());
        $this->assertFalse($archivist->isSuperAdmin());

        $this->assertTrue($employee->isEmployee());
        $this->assertFalse($employee->canManageArchives());
    }

    public function test_document_status_enum(): void
    {
        $this->assertSame('available', DocumentStatus::AVAILABLE->value);
        $this->assertSame('booked', DocumentStatus::BOOKED->value);
        $this->assertSame('borrowed', DocumentStatus::BORROWED->value);
        $this->assertSame('lost', DocumentStatus::LOST->value);
        $this->assertSame('invalid', DocumentStatus::INVALID->value);

        $this->assertSame('Tersedia', DocumentStatus::AVAILABLE->label());
        $this->assertSame('Booking', DocumentStatus::BOOKED->label());
    }

    public function test_loan_status_enum(): void
    {
        $this->assertSame('pending', LoanStatus::PENDING->value);
        $this->assertSame('borrowed', LoanStatus::BORROWED->value);
        $this->assertSame('returned', LoanStatus::RETURNED->value);
        $this->assertSame('cancelled', LoanStatus::CANCELLED->value);

        $this->assertSame('Menunggu Persetujuan', LoanStatus::PENDING->label());
        $this->assertSame('Sedang Dipinjam', LoanStatus::BORROWED->label());
    }

    public function test_eloquent_relationships_for_archive_document(): void
    {
        $district = District::factory()->create();
        $village = Village::factory()->create(['district_id' => $district->id]);
        $docType = DocumentType::factory()->create();
        $rightType = LandRightType::factory()->create();
        $rack = StorageRack::factory()->create();

        $document = ArchiveDocument::factory()->create([
            'district_id' => $district->id,
            'village_id' => $village->id,
            'document_type_id' => $docType->id,
            'land_right_type_id' => $rightType->id,
            'storage_rack_id' => $rack->id,
            'status' => DocumentStatus::AVAILABLE,
        ]);

        $this->assertInstanceOf(District::class, $document->district);
        $this->assertSame($district->id, $document->district->id);

        $this->assertInstanceOf(Village::class, $document->village);
        $this->assertSame($village->id, $document->village->id);

        $this->assertInstanceOf(DocumentType::class, $document->documentType);
        $this->assertSame($docType->id, $document->documentType->id);

        $this->assertInstanceOf(LandRightType::class, $document->landRightType);
        $this->assertSame($rightType->id, $document->landRightType->id);

        $this->assertInstanceOf(StorageRack::class, $document->storageRack);
        $this->assertSame($rack->id, $document->storageRack->id);
    }

    public function test_eloquent_relationships_for_document_loan(): void
    {
        $district = District::factory()->create();
        $village = Village::factory()->create(['district_id' => $district->id]);
        $document = ArchiveDocument::factory()->create([
            'district_id' => $district->id,
            'village_id' => $village->id,
        ]);

        $borrower = User::factory()->employee()->create();
        $approver = User::factory()->archivist()->create();
        $receiver = User::factory()->archivist()->create();
        $activity = ServiceActivity::factory()->create();

        $loan = DocumentLoan::factory()->create([
            'archive_document_id' => $document->id,
            'borrower_id' => $borrower->id,
            'service_activity_id' => $activity->id,
            'approved_by' => $approver->id,
            'received_by' => $receiver->id,
            'status' => LoanStatus::BORROWED,
        ]);

        $this->assertInstanceOf(ArchiveDocument::class, $loan->archiveDocument);
        $this->assertSame($document->id, $loan->archiveDocument->id);

        $this->assertInstanceOf(User::class, $loan->borrower);
        $this->assertSame($borrower->id, $loan->borrower->id);

        $this->assertInstanceOf(User::class, $loan->approver);
        $this->assertSame($approver->id, $loan->approver->id);

        $this->assertInstanceOf(User::class, $loan->receiver);
        $this->assertSame($receiver->id, $loan->receiver->id);

        $this->assertInstanceOf(ServiceActivity::class, $loan->serviceActivity);
        $this->assertSame($activity->id, $loan->serviceActivity->id);
    }

    public function test_archive_document_soft_deletes(): void
    {
        $district = District::factory()->create();
        $village = Village::factory()->create(['district_id' => $district->id]);
        $document = ArchiveDocument::factory()->create([
            'district_id' => $district->id,
            'village_id' => $village->id,
        ]);

        $docId = $document->id;
        $document->delete();

        $this->assertSoftDeleted('archive_documents', ['id' => $docId]);
        $this->assertNull(ArchiveDocument::find($docId));
        $this->assertNotNull(ArchiveDocument::withTrashed()->find($docId));
    }
}
