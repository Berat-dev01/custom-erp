<?php
namespace App\Erp\Services\Products;
use App\Erp\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class ProductQuery {
    public const SORTS = ['name', 'sku', 'sale_price', 'purchase_price', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'      => $request->string('search')->toString(),
            'category_id' => $request->integer('category_id') ?: null,
            'is_active'   => $request->string('is_active')->toString(),
            'sort'        => $request->string('sort', 'created_at')->toString(),
            'direction'   => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Product::query()->with(['category', 'unit'])
            ->when($f['search'], fn($q,$s) => $q->where(fn($q) => $q->where('name','like',"%$s%")->orWhere('sku','like',"%$s%")))
            ->when($f['category_id'], fn($q,$v) => $q->where('category_id', $v))
            ->when($f['is_active'] !== '', fn($q) => $q->where('is_active', $f['is_active'] === '1'))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
