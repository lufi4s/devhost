<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentProvider;
use App\Models\Subscription;
use Illuminate\Support\Str;

/**
 * Payment-provider abstraction.
 *
 * Concrete gateways (Paystack, Stripe, custom) plug in here. The control
 * plane never talks to a gateway directly — it calls into this service so a
 * gateway can be swapped or added without touching the rest of the app.
 *
 * The default implementation is gateway-agnostic: it creates invoices and
 * returns a "pay_url" when a provider is configured, and otherwise marks an
 * invoice payable via manual/offline settlement.
 */
class PaymentService
{
    public function availableProviders(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentProvider::where('is_active', true)->get();
    }

    public function configure(array $input): PaymentProvider
    {
        $rules = [
            'provider' => ['required', 'string', 'max:64', 'unique:payment_providers,provider'],
            'name' => ['required', 'string', 'max:120'],
            'config' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        $data = \Illuminate\Support\Facades\Validator::make($input, $rules)->validate();

        return PaymentProvider::create([
            'provider' => $data['provider'],
            'name' => $data['name'],
            'config' => $data['config'] ?? [],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Create an invoice for a subscription period.
     */
    public function createInvoice(Subscription $subscription): Invoice
    {
        $provider = PaymentProvider::where('is_active', true)->first();

        return Invoice::create([
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->generateInvoiceNumber($subscription),
            'amount' => $subscription->amount,
            'currency' => $subscription->plan->currency,
            'status' => 'pending',
            'payment_provider' => $provider?->provider,
            'provider_reference' => null,
            'due_date' => now()->addDays(7),
        ]);
    }

    /**
     * Mark an invoice as paid (e.g. after a webhook confirmation).
     */
    public function markPaid(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $invoice->refresh();
    }

    public function markFailed(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'failed']);

        return $invoice->refresh();
    }

    /**
     * Return a pay URL for an invoice, or null when offline/manual.
     */
    public function payUrl(Invoice $invoice): ?string
    {
        $provider = $invoice->subscription->plan
            ? PaymentProvider::where('is_active', true)->first()
            : null;

        // Concrete gateways would build a checkout URL here using their SDK.
        // For the abstraction, we return null and let the client settle manually.
        return null;
    }

    private function generateInvoiceNumber(Subscription $subscription): string
    {
        return 'INV-' . strtoupper(Str::random(8)) . '-' . now()->format('ym');
    }
}
