<?php
namespace App\Erp\Services\Manufacturing;
use App\Erp\Models\Bom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class BomQuery {
    public const SORTS = ['version', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'     => $request->string('search')->toString(),
            'product_id' => $request->integer('product_id') ?: null,
            'is_active'  => $request->string('is_active')->toString(),
            'sort'       => $request->string('sort', 'created_at')->toString(),
            'direction'  => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Bom::query()->with(['product:id,name,sku'])->withCount('components')
            ->when($f['search'], fn($q,$s) => $q->whereHas('product', fn($q) => $q->where('name','like',"%$s%")->orWhere('sku','like',"%$s%")))
            ->when($f['product_id'], fn($q,$v) => $q->where('product_id',$v))
            ->when($f['is_active'] !== '', fn($q) => $q->where('is_active', $f['is_active'] === '1'))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
