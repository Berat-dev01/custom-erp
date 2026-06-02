<?php

namespace App\Erp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_work_orders';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'planned_start'     => 'date',
            'planned_end'       => 'date',
            'actual_start'      => 'date',
            'actual_end'        => 'date',
            'planned_quantity'  => 'decimal:3',
            'produced_quantity' => 'decimal:3',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(WorkOrderConsumption::class, 'work_order_id');
    }

    public function isDraft(): bool   { return $this->status === 'draft'; }
    public function isReleased(): bool { return $this->status === 'released'; }
    public function isActive(): bool  { return in_array($this->status, ['released', 'in_progress']); }
}
