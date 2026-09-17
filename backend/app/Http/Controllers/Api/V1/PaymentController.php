<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    /**
     * Initialize a payment intent for a subscription or booking.
     * POST /api/v1/payments/initialize
     */
    public function initialize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', 'string', 'in:subscription,booking,order'],
            'plan_uuid' => ['required_if:purpose,subscription', 'nullable', 'string', 'exists:subscription_plans,uuid'],
            'booking_uuid' => ['required_if:purpose,booking,order', 'nullable', 'string', 'exists:bookings,uuid'],
            'channel' => ['nullable', 'string', 'in:card,bank_transfer,ussd,qr,wallet'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'callback_url' => ['nullable', 'string'],
        ]);

        $result = $this->paymentService->initialize($request->user(), $validated);

        return response()->json([
            'message' => 'Payment intent initialized.',
            'data' => $result,
        ], 201);
    }

    /**
     * Verify a payment reference server-side.
     * POST /api/v1/payments/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
        ]);

        $payment = $this->paymentService->verify($validated['reference']);

        return response()->json([
            'message' => $payment->isSuccessful() ? 'Payment verified successfully.' : 'Payment verification failed.',
            'data' => $this->paymentService->formatPayment($payment),
        ]);
    }

    /**
     * List current user's payment transaction history.
     * GET /api/v1/payments
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,processing,successful,failed,reversed,refunded'],
            'purpose' => ['nullable', 'string', 'in:subscription,booking,order'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = $this->paymentService->listUserPayments($request->user(), $validated);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Payment $p) => $this->paymentService->formatPayment($p)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get single payment receipt.
     * GET /api/v1/payments/{paymentUuid}
     */
    public function show(Request $request, string $paymentUuid): JsonResponse
    {
        $payment = $this->paymentService->getPaymentDetails($request->user(), $paymentUuid);

        return response()->json([
            'data' => $this->paymentService->formatPayment($payment),
        ]);
    }

    /**
     * Request a payment refund.
     * POST /api/v1/payments/{paymentUuid}/refund
     */
    public function refund(Request $request, string $paymentUuid): JsonResponse
    {
        $payment = $this->paymentService->getPaymentDetails($request->user(), $paymentUuid);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $refundedPayment = $this->paymentService->refund($payment, $validated['reason'] ?? null, $request->user());

        return response()->json([
            'message' => 'Payment refund processed successfully.',
            'data' => $this->paymentService->formatPayment($refundedPayment),
        ]);
    }

    /**
     * Gateway webhook endpoint.
     * POST /api/v1/payments/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->processWebhook($request);

            return response()->json([
                'status' => 'success',
                'reference' => $payment->reference,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'ignored_or_error',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
