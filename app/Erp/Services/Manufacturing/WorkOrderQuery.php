<?php
namespace App\Erp\Services\Manufacturing;
use App\Erp\Models\WorkOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class WorkOrderQuery {
    public const SORTS = ['planned_start', 'wo_number', 'planned_quantity', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'    => $request->string('search')->toString(),
            'status'    => $request->string('status')->toString(),
            'sort'      => $request->string('sort', 'created_at')->toString(),
            'direction' => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return WorkOrder::query()->with(['product:id,name', 'bom:id,product_id'])
            ->when($f['search'], fn($q,$s) => $q->where(fn($q) => $q->where('wo_number','like',"%$s%")->orWhereHas('product', fn($q) => $q->where('name','like',"%$s%"))))
            ->when($f['status'], fn($q,$v) => $q->where('status',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
