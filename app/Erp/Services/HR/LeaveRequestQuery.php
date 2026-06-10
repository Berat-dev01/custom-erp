<?php
namespace App\Erp\Services\HR;
use App\Erp\Models\LeaveRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class LeaveRequestQuery {
    public const SORTS = ['start_date', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'        => $request->string('search')->toString(),
            'status'        => $request->string('status')->toString(),
            'employee_id'   => $request->integer('employee_id') ?: null,
            'sort'          => $request->string('sort', 'created_at')->toString(),
            'direction'     => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return LeaveRequest::query()->with(['employee:id,first_name,last_name'])
            ->when($f['status'], fn($q,$v) => $q->where('status',$v))
            ->when($f['employee_id'], fn($q,$v) => $q->where('employee_id',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
