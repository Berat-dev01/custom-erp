<?php

namespace App\Erp\Services\Suppliers;

use App\Erp\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SupplierQuery
{
    public const SORTS = ['name', 'email', 'payment_terms_days', 'created_at'];

    public function paginate(Request $request): LengthAwarePaginator
    {
        return $this->base($request)
            ->paginate($this->perPage($request))
            ->withQueryString();
    }

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        return [
            'search'    => $request->string('search')->toString(),
            'status'    => $request->string('status')->toString(),
            'sort'      => $request->string('sort', 'created_at')->toString(),
            'direction' => $request->string('direction', 'desc')->toString(),
        ];
    }

    private function base(Request $request): Builder
    {
        $filters   = $this->filters($request);
        $sort      = in_array($filters['sort'], self::SORTS, true) ? $filters['sort'] : 'created_at';
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        return Supplier::query()
            ->when($filters['search'], fn (Builder $q, string $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('tax_number', 'like', "%{$s}%")
            ))
            ->when($filters['status'], fn (Builder $q, string $v) => $q->where('status', $v))
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc');
    }

    private function perPage(Request $request): int
    {
        return min(max(1, $request->integer('per_page', 20)), 100);
    }
}
