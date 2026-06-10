<?php
namespace App\Erp\Services\Positions;
use App\Erp\Models\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class PositionQuery {
    public const SORTS = ['title', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'        => $request->string('search')->toString(),
            'department_id' => $request->integer('department_id') ?: null,
            'sort'          => $request->string('sort', 'title')->toString(),
            'direction'     => $request->string('direction', 'asc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'title';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Position::query()->with(['department:id,name'])->withCount('employees')
            ->when($f['search'], fn($q,$s) => $q->where('title','like',"%$s%"))
            ->when($f['department_id'], fn($q,$v) => $q->where('department_id',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
