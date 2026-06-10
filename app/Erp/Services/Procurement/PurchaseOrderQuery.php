<?php
namespace App\Erp\Services\Procurement;
use App\Erp\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class PurchaseOrderQuery {
    public const SORTS = ['po_number', 'order_date', 'total', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'      => $request->string('search')->toString(),
            'status'      => $request->string('status')->toString(),
            'supplier_id' => $request->integer('supplier_id') ?: null,
            'sort'        => $request->string('sort', 'created_at')->toString(),
            'direction'   => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return PurchaseOrder::query()->with(['supplier:id,name'])
            ->when($f['search'], fn($q,$s) => $q->where(fn($q) => $q->where('po_number','like',"%$s%")->orWhereHas('supplier', fn($q) => $q->where('name','like',"%$s%"))))
            ->when($f['status'], fn($q,$v) => $q->where('status',$v))
            ->when($f['supplier_id'], fn($q,$v) => $q->where('supplier_id',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
