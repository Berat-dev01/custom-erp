<?php

namespace App\Erp\Http\Controllers\Api;

use App\Erp\Http\Resources\EmployeeResource;
use App\Erp\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class EmployeeApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('erp.employees.view');

        $perPage = min((int) $request->get('per_page', config('erp.api.default_per_page', 20)), config('erp.api.max_per_page', 100));

        $query = Employee::with(['department', 'position'])
            ->when($request->get('status'),        fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('department_id'), fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->get('search'),        fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%")
                  ->orWhere('employee_number', 'like', "%{$v}%");
            }))
            ->orderBy('last_name')
            ->orderBy('first_name');

        return EmployeeResource::collection($query->paginate($perPage));
    }

    public function show(Employee $employee): EmployeeResource
    {
        Gate::authorize('erp.employees.view');

        $employee->loadMissing(['department', 'position']);

        return new EmployeeResource($employee);
    }
}
