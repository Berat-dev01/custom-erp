<?php
namespace App\Erp\Services\Expenses;
use App\Erp\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class ExpenseQuery {
    public const SORTS = ['expense_date', 'amount', 'title', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'       => $request->string('search')->toString(),
            'category'     => $request->string('category')->toString(),
            'date_from'    => $request->string('date_from')->toString(),
            'date_to'      => $request->string('date_to')->toString(),
            'sort'         => $request->string('sort', 'expense_date')->toString(),
            'direction'    => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'expense_date';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Expense::query()->with(['createdBy:id,name'])
            ->when($f['search'], fn($q,$s) => $q->where('title','like',"%$s%"))
            ->when($f['category'], fn($q,$v) => $q->where('category',$v))
            ->when($f['date_from'], fn($q,$v) => $q->whereDate('expense_date','>=',$v))
            ->when($f['date_to'],   fn($q,$v) => $q->whereDate('expense_date','<=',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
