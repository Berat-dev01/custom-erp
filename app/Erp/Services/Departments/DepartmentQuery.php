<?php
namespace App\Erp\Services\Departments;
use App\Erp\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class DepartmentQuery {
    public const SORTS = ['name', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'    => $request->string('search')->toString(),
            'sort'      => $request->string('sort', 'name')->toString(),
            'direction' => $request->string('direction', 'asc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'name';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Department::query()->with(['manager:id,first_name,last_name'])->withCount('employees')
            ->when($f['search'], fn($q,$s) => $q->where('name','like',"%$s%"))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
