<?php
namespace App\Erp\Services\Finance;
use App\Erp\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class InvoiceQuery {
    public const SORTS = ['invoice_number', 'issue_date', 'due_date', 'total', 'created_at'];
    public function paginate(Request $request): LengthAwarePaginator {
        return $this->base($request)->paginate($this->perPage($request))->withQueryString();
    }
    public function filters(Request $request): array {
        return [
            'search'    => $request->string('search')->toString(),
            'status'    => $request->string('status')->toString(),
            'sort'      => $request->string('sort', 'created_at')->toString(),
            'direction' => $request->string('direction', 'desc')->toString(),
        ];
    }
    private function base(Request $request): Builder {
        $f = $this->filters($request);
        $sort = in_array($f['sort'], self::SORTS, true) ? $f['sort'] : 'created_at';
        $dir  = $f['direction'] === 'asc' ? 'asc' : 'desc';
        return Invoice::query()->with(['invoiceable'])
            ->when($f['search'], fn($q,$s) => $q->where('invoice_number','like',"%$s%"))
            ->when($f['status'], fn($q,$v) => $q->where('status',$v))
            ->orderBy($sort, $dir)->orderBy('id','desc');
    }
    private function perPage(Request $request): int { return min(max(1,$request->integer('per_page',20)),100); }
}
