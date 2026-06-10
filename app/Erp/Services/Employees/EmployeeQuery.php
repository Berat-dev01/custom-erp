<?php
namespace App\Erp\Services\Employees;
use App\Erp\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class EmployeeQuery {
    public const SORTS = ['last_name', 'first_name', 'hire_date', 'employee_number', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'        => $request->string('search')->toString(),
            'department_id' => $request->integer('department_id') ?: null,
            'status'        => $request->string('status')->toString(),
            'sort'          => $request->string('sort', 'last_name')->toString(),
            'direction'     => $request->string('direction', 'asc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'last_name';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Employee::query()->with(['department', 'position'])
            ->when($f['search'], fn($q,$s) => $q->where(fn($q) => $q->where('first_name','like',"%$s%")->orWhere('last_name','like',"%$s%")->orWhere('email','like',"%$s%")->orWhere('employee_number','like',"%$s%")))
            ->when($f['department_id'], fn($q,$v) => $q->where('department_id', $v))
            ->when($f['status'], fn($q,$v) => $q->where('status', $v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
