<?php
namespace App\Erp\Services\Assets;
use App\Erp\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class AssetQuery {
    public const SORTS = ['name', 'asset_code', 'purchase_date', 'current_value', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'      => $request->string('search')->toString(),
            'category_id' => $request->integer('category_id') ?: null,
            'status'      => $request->string('status')->toString(),
            'sort'        => $request->string('sort', 'created_at')->toString(),
            'direction'   => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Asset::query()->with(['category'])
            ->when($f['search'], fn($q,$s) => $q->where(fn($q) => $q->where('name','like',"%$s%")->orWhere('asset_code','like',"%$s%")))
            ->when($f['category_id'], fn($q,$v) => $q->where('category_id',$v))
            ->when($f['status'], fn($q,$v) => $q->where('status',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
