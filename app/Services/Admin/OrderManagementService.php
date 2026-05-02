<?php

namespace App\Services\Admin;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderManagementService
{
    private const ALLOWED_SORT = ['created_at', 'total', 'status', 'id'];

    private const ALLOWED_PER_PAGE = [25, 50, 100];

    private const ORDERS_RELATIONS = [
        'user:id,name,surname,email,role,user_type',
        'packages.originAddress',
        'packages.destinationAddress',
        'packages.service',
        'transactions',
    ];

    private const SHIPMENTS_COLUMNS = [
        'id', 'user_id', 'status', 'subtotal', 'brt_parcel_id',
        'brt_numeric_sender_reference', 'brt_tracking_url', 'brt_pudo_id',
        'brt_departure_depot', 'brt_arrival_depot', 'is_cod', 'cod_amount',
        'pickup_status', 'bordero_status', 'documents_status', 'execution_error',
        'created_at', 'updated_at',
    ];

    public function paginateOrders(Request $request): LengthAwarePaginator
    {
        $sortBy = in_array($request->input('sort_by'), self::ALLOWED_SORT, true) ? $request->input('sort_by') : 'created_at';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';
        $perPage = in_array((int) $request->input('per_page'), self::ALLOWED_PER_PAGE, true) ? (int) $request->input('per_page') : 25;

        $query = Order::with(self::ORDERS_RELATIONS)->orderBy($sortBy === 'total' ? 'subtotal' : $sortBy, $sortDir);

        $this->applyStatusFilter($query, $request->input('status'));
        $this->applyOrdersSearch($query, $request->input('search'));
        $this->applyDateRange($query, $request->input('date_from'), $request->input('date_to'));
        $this->applyAmountRange($query, $request->input('amount_min'), $request->input('amount_max'));
        $this->applyServicesFilter($query, $request->input('services'));

        return $query->paginate($perPage);
    }

    public function paginateShipments(Request $request): LengthAwarePaginator
    {
        $query = Order::with('user:id,name,surname,email')
            ->select(self::SHIPMENTS_COLUMNS)
            ->whereNotNull('brt_parcel_id')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $this->applyShipmentsSearch($query, $request->input('search'));

        return $query->paginate(20);
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }
        $statuses = array_filter(array_map('trim', explode(',', $status)));
        match (count($statuses)) {
            0 => null,
            1 => $query->where('status', $statuses[0]),
            default => $query->whereIn('status', $statuses),
        };
    }

    private function applyOrdersSearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }
        $query->where(function ($q) use ($search) {
            $q->where('id', $search)
                ->orWhere('brt_parcel_id', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
        });
    }

    private function applyShipmentsSearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }
        $query->where(function ($q) use ($search) {
            $q->where('brt_parcel_id', 'like', "%{$search}%")
                ->orWhere('brt_numeric_sender_reference', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
        });
    }

    private function applyDateRange(Builder $query, ?string $from, ?string $to): void
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
    }

    private function applyAmountRange(Builder $query, $min, $max): void
    {
        if ($min !== null && $min !== '') {
            $query->where('subtotal', '>=', (int) round((float) $min * 100));
        }
        if ($max !== null && $max !== '') {
            $query->where('subtotal', '<=', (int) round((float) $max * 100));
        }
    }

    private function applyServicesFilter(Builder $query, ?string $services): void
    {
        if (! $services) {
            return;
        }
        $names = array_filter(array_map('trim', explode(',', $services)));
        if ($names) {
            $query->whereHas('packages.service', fn ($sq) => $sq->whereIn('name', $names));
        }
    }
}
