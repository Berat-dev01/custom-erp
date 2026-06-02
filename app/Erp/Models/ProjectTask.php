<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class ProjectTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_project_tasks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'task_id');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'done'
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function markDone(): void
    {
        $this->update([
            'status'       => 'done',
            'completed_at' => Carbon::today(),
        ]);
    }
}
