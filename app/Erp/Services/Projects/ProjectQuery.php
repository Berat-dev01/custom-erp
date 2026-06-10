<?php
namespace App\Erp\Services\Projects;
use App\Erp\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class ProjectQuery {
    public const SORTS = ['name', 'start_date', 'end_date', 'budget', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'      => $request->string('search')->toString(),
            'status'      => $request->string('status')->toString(),
            'customer_id' => $request->integer('customer_id') ?: null,
            'sort'        => $request->string('sort', 'created_at')->toString(),
            'direction'   => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Project::query()->with(['customer:id,name', 'manager:id,first_name,last_name'])->withCount('tasks')
            ->when($f['search'], fn($q,$s) => $q->where('name','like',"%$s%"))
            ->when($f['status'], fn($q,$v) => $q->where('status',$v))
            ->when($f['customer_id'], fn($q,$v) => $q->where('customer_id',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
