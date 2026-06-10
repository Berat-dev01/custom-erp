<?php
namespace App\Erp\Services\Manufacturing;
use App\Erp\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class StockMovementQuery {
    public const SORTS = ['movement_date', 'quantity', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'       => $request->string('search')->toString(),
            'type'         => $request->string('type')->toString(),
            'product_id'   => $request->integer('product_id') ?: null,
            'warehouse_id' => $request->integer('warehouse_id') ?: null,
            'sort'         => $request->string('sort', 'created_at')->toString(),
            'direction'    => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return StockMovement::query()->with(['product:id,name,sku', 'warehouse:id,name'])
            ->when($f['type'], fn($q,$v) => $q->where('type',$v))
            ->when($f['product_id'], fn($q,$v) => $q->where('product_id',$v))
            ->when($f['warehouse_id'], fn($q,$v) => $q->where('warehouse_id',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
