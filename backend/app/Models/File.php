<?php

namespace App\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('files')]
#[Fillable([
    'storage_disk',
    'storage_path',
    'original_name',
    'mime_type',
    'extension',
    'size_bytes',
    'sha256',
    'classification',
    'scan_status',
    'retention_policy_id',
    'expires_at',
    'deleted_at',
])]
#[Hidden(['storage_disk', 'storage_path'])]
class File extends DomainModel
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'size_bytes' => 'integer',
            'uploaded_by' => 'integer',
            'retention_policy_id' => 'integer',
            'expires_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    /** @return BelongsTo<RetentionPolicy, $this> */
    public function retentionPolicy(): BelongsTo
    {
        return $this->belongsTo(RetentionPolicy::class, 'retention_policy_id', 'id');
    }

    /** @return HasMany<Institution, $this> */
    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class, 'logo_file_id', 'id');
    }

    /** @return HasMany<StudentGuardian, $this> */
    public function studentGuardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class, 'verification_evidence_file_id', 'id');
    }

    /** @return HasMany<EmployeePosition, $this> */
    public function employeePositions(): HasMany
    {
        return $this->hasMany(EmployeePosition::class, 'appointment_file_id', 'id');
    }

    /** @return HasMany<HomeroomAssignment, $this> */
    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'appointment_file_id', 'id');
    }

    /** @return HasMany<EmployeeAttendance, $this> */
    public function employeeAttendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class, 'evidence_file_id', 'id');
    }

    /** @return HasMany<EmployeeAdditionalDuty, $this> */
    public function employeeAdditionalDuties(): HasMany
    {
        return $this->hasMany(EmployeeAdditionalDuty::class, 'appointment_file_id', 'id');
    }

    /** @return HasMany<QuestionFile, $this> */
    public function questionFiles(): HasMany
    {
        return $this->hasMany(QuestionFile::class, 'file_id', 'id');
    }

    /** @return HasMany<ExamPackageFile, $this> */
    public function examPackageFiles(): HasMany
    {
        return $this->hasMany(ExamPackageFile::class, 'file_id', 'id');
    }

    /** @return HasMany<BkFile, $this> */
    public function bkFiles(): HasMany
    {
        return $this->hasMany(BkFile::class, 'file_id', 'id');
    }

    /** @return HasMany<DocumentFile, $this> */
    public function documentFiles(): HasMany
    {
        return $this->hasMany(DocumentFile::class, 'file_id', 'id');
    }

    /** @return HasMany<ContentFile, $this> */
    public function contentFiles(): HasMany
    {
        return $this->hasMany(ContentFile::class, 'file_id', 'id');
    }

    /** @return HasMany<PublicationAsset, $this> */
    public function sourceFilePublicationAssets(): HasMany
    {
        return $this->hasMany(PublicationAsset::class, 'source_file_id', 'id');
    }

    /** @return HasMany<PublicationAsset, $this> */
    public function publicFilePublicationAssets(): HasMany
    {
        return $this->hasMany(PublicationAsset::class, 'public_file_id', 'id');
    }

    /** @return HasMany<PortalAnnouncementFile, $this> */
    public function portalAnnouncementFiles(): HasMany
    {
        return $this->hasMany(PortalAnnouncementFile::class, 'file_id', 'id');
    }

    /** @return HasMany<ReportJob, $this> */
    public function reportJobs(): HasMany
    {
        return $this->hasMany(ReportJob::class, 'output_file_id', 'id');
    }

    /** @return HasMany<ImportBatche, $this> */
    public function sourceFileImportBatches(): HasMany
    {
        return $this->hasMany(ImportBatche::class, 'source_file_id', 'id');
    }

    /** @return HasMany<ImportBatche, $this> */
    public function errorReportFileImportBatches(): HasMany
    {
        return $this->hasMany(ImportBatche::class, 'error_report_file_id', 'id');
    }

    /** @return HasMany<RestoreRun, $this> */
    public function restoreRuns(): HasMany
    {
        return $this->hasMany(RestoreRun::class, 'evidence_file_id', 'id');
    }

    /** @return HasOne<EmployeeAttendanceCapture, $this> */
    public function employeeAttendanceCapture(): HasOne
    {
        return $this->hasOne(EmployeeAttendanceCapture::class, 'photo_file_id', 'id');
    }

    /** @return HasMany<DataSubjectRequest, $this> */
    public function dataSubjectRequests(): HasMany
    {
        return $this->hasMany(DataSubjectRequest::class, 'verification_evidence_file_id', 'id');
    }
}
