<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Order;
use App\Services\Brt\ShipmentService;
use App\Services\OrderBrtFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function __construct(
        private readonly ShipmentService $shipment,
        private readonly OrderBrtFulfillmentService $fulfillment,
    ) {}

    /**
     * orders() — Lista ordini admin con filtri avanzati.
     *
     * Parametri GET supportati:
     *   search       stringa  — cerca per ID, nome, cognome, email, brt_parcel_id
     *   status       stringa  — singolo stato OPPURE lista separata da virgola (es. "pending,processing")
     *   date_from    date     — filtra ordini creati dal giorno X (YYYY-MM-DD)
     *   date_to      date     — filtra ordini creati fino al giorno X (YYYY-MM-DD)
     *   amount_min   float    — importo minimo in euro (converte in cents: * 100)
     *   amount_max   float    — importo massimo in euro (converte in cents: * 100)
     *   services     stringa  — nomi servizi separati da virgola (filtra su packages.service.name)
     *   sort_by      stringa  — campo di ordinamento: created_at | total | status (default: created_at)
     *   sort_dir     stringa  — direzione: asc | desc (default: desc)
     *   per_page     intero   — risultati per pagina: 25 | 50 | 100 (default: 25)
     */
    public function orders(Request $request): JsonResponse
    {
        // Ordinamento sicuro: whitelist campi consentiti
        $allowedSortFields = ['created_at', 'total', 'status', 'id'];
        $sortBy  = in_array($request->input('sort_by'), $allowedSortFields, true)
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        // Per_page: solo 25, 50, 100 per sicurezza
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true)
            ? (int) $request->input('per_page')
            : 25;

        $query = Order::with([
            'user:id,name,surname,email,role,user_type',
            'packages.originAddress',
            'packages.destinationAddress',
            'packages.service',
            'transactions',
        ]);

        // Ordinamento per colonna (il campo "total" mappa su "subtotal" nel DB)
        if ($sortBy === 'total') {
            $query->orderBy('subtotal', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        // Filtro stato: singolo valore o lista separata da virgola
        if ($request->filled('status')) {
            $statuses = array_filter(array_map('trim', explode(',', $request->input('status'))));
            if (count($statuses) === 1) {
                $query->where('status', $statuses[0]);
            } elseif (count($statuses) > 1) {
                $query->whereIn('status', $statuses);
            }
        }

        // Ricerca per ID ordine, nome/cognome/email utente o codice BRT
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('brt_parcel_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('surname', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro date (created_at)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Filtro importo (amount in euro, DB in cents via subtotal)
        // subtotal e' un intero in centesimi (MyMoney convention)
        if ($request->filled('amount_min')) {
            $minCents = (int) round((float) $request->input('amount_min') * 100);
            $query->where('subtotal', '>=', $minCents);
        }
        if ($request->filled('amount_max')) {
            $maxCents = (int) round((float) $request->input('amount_max') * 100);
            $query->where('subtotal', '<=', $maxCents);
        }

        // Filtro servizi (nomi separati da virgola, es. "Express,Internazionale")
        if ($request->filled('services')) {
            $serviceNames = array_filter(array_map('trim', explode(',', $request->input('services'))));
            if (!empty($serviceNames)) {
                $query->whereHas('packages.service', function ($sq) use ($serviceNames) {
                    $sq->whereIn('name', $serviceNames);
                });
            }
        }

        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    // Cambia lo stato di un ordine (es. da "pending" a "completed")
    public function updateOrderStatus(\App\Http\Requests\UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        $oldStatus = $order->status;
        $order->update(['status' => $data['status']]);

        // Invia notifica email all'utente sul cambio di stato
        if ($oldStatus !== $data['status']) {
            event(new \App\Events\ShipmentStatusChanged($order, $oldStatus, $data['status']));
        }

        return response()->json([
            'success' => true,
            'message' => "Stato ordine aggiornato da '{$oldStatus}' a '{$data['status']}'.",
            'data' => $order->fresh(),
        ]);
    }

    // Mostra la lista delle spedizioni BRT (ordini che hanno un'etichetta BRT)
    // Supporta filtro per stato e ricerca
    public function shipments(Request $request): JsonResponse
    {
        // Escludiamo brt_label_base64 dalla query per performance (e' un campo molto grande)
        $query = Order::with('user:id,name,surname,email')
            ->select([
                'id',
                'user_id',
                'status',
                'subtotal',
                'brt_parcel_id',
                'brt_numeric_sender_reference',
                'brt_tracking_url',
                'brt_pudo_id',
                'brt_departure_depot',
                'brt_arrival_depot',
                'is_cod',
                'cod_amount',
                'pickup_status',
                'bordero_status',
                'documents_status',
                'execution_error',
                'created_at',
                'updated_at',
            ])
            ->whereNotNull('brt_parcel_id')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('brt_parcel_id', 'like', "%{$search}%")
                  ->orWhere('brt_numeric_sender_reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('surname', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $shipments = $query->paginate(20);

        return response()->json($shipments);
    }

    // Aggiorna o rimuove il punto PUDO associato a un ordine
    public function updateOrderPudo(\App\Http\Requests\UpdateOrderPudoRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        $order->update(['brt_pudo_id' => $data['pudo_id']]);

        return response()->json([
            'success' => true,
            'message' => $data['pudo_id']
                ? "Punto PUDO '{$data['pudo_id']}' impostato per ordine #{$order->id}."
                : "Punto PUDO rimosso dall'ordine #{$order->id}.",
            'data' => $order->fresh(),
        ]);
    }

    /**
     * regenerateLabel -- Rigenera manualmente l'etichetta BRT per un ordine.
     *
     * PERCHE': La generazione automatica puo' fallire (errore BRT, indirizzo non valido).
     *   L'admin puo' ritentare manualmente dopo aver corretto i dati.
     * COME LEGGERLO: 1) Verifica configurazione BRT  2) Prepara opzioni (contrassegno, PUDO)
     *   3) Chiama BrtClient.createShipment()  4) Salva campi brt_*  5) Invia email etichetta
     * COME MODIFICARLO: Per passare opzioni extra a BRT, aggiungerle nell'array $options.
     * COSA EVITARE: Non chiamare su ordini gia' con etichetta senza prima eliminare la vecchia.
     */
    public function regenerateLabel(Order $order): JsonResponse
    {
        if (!config('services.brt.client_id')) {
            return response()->json([
                'success' => false,
                'message' => 'BRT non configurato. Verifica le credenziali nel file .env.',
            ], 422);
        }

        $result = $this->shipment->createShipment($order, $this->fulfillment->buildAutomaticShipmentOptions($order));

        if ($result['success']) {
            $order = $this->fulfillment->finalizeSuccessfulShipment(
                $order,
                $result,
                [],
                'Post-elaborazione documenti fallita dopo rigenerazione admin',
                'Failed to complete shipment documents flow after admin label regeneration'
            );

            return response()->json([
                'success' => true,
                'message' => 'Etichetta BRT rigenerata con successo.',
                'data' => [
                    'parcel_id' => $result['parcel_id'],
                    'tracking_url' => $result['tracking_url'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Generazione etichetta BRT fallita: ' . ($result['error'] ?? 'Errore sconosciuto'),
        ], 422);
    }
}
