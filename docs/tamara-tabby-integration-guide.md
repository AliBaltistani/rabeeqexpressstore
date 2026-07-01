# Tamara + Tabby BNPL Integration Guide
**Stack:** Laravel 13 · Filament v5 · Vue.js (frontend) · API keys already provisioned

## 0. Coexisting with Stripe / PayPal / COD (already live, behind shared interface)

Stripe, PayPal, and COD already implement a shared `PaymentGatewayInterface` — Tamara and Tabby are **two more drivers on that same interface**, not a parallel system. This is additive only.

**Before writing any new code, agent must:**
1. Locate and read the existing `PaymentGatewayInterface` (or equivalently named contract) — do not invent a new one. §1 below names methods (`createSession`, `verifyWebhook`, `handleWebhook`) as a baseline only; if the real interface differs, conform to the real one and flag any gap (e.g. existing interface may not have a `verifyWebhook` step since Stripe/PayPal use SDK-level verification — that's fine, extend the interface rather than fork it).
2. Confirm how the existing factory/resolver picks a driver (likely `Order->payment_gateway` enum or similar) — add `tamara` and `tabby` as new enum cases there, following existing naming convention exactly.
3. Confirm whether existing gateways' webhook routes share a common prefix/group/middleware stack — match it for the new ones rather than creating a separate routing convention.
4. Confirm Filament's existing settings UI pattern for Stripe/PayPal keys (Settings page vs per-resource) and extend that same UI — do not create a second, differently-structured settings page. §4 below assumes no existing pattern; if one exists, follow it instead.

**Explicit non-goals:** no changes to Stripe/PayPal/COD business logic, no changes to existing order statuses/enums beyond adding new gateway values, no changes to existing webhook routes or controllers, no interface signature changes that would break existing drivers (only additive/optional methods).

**If the existing interface is thinner than what Tamara/Tabby need** (e.g. no built-in webhook signature step), extend it with an optional method with a default no-op implementation for old drivers, rather than special-casing Tamara/Tabby outside the interface.

## 1. Architecture

Both gateways follow the same pattern: server creates a checkout session → frontend redirects to gateway's hosted page → gateway redirects back → gateway sends a **webhook** (source of truth, not the redirect).

```
app/
  Services/Payments/
    Contracts/PaymentGatewayInterface.php   # createSession(), verifyWebhook(), handleWebhook()
    TamaraGateway.php
    TabbyGateway.php
    PaymentGatewayFactory.php               # EXISTING — just add tamara/tabby cases to its resolver
  Http/Controllers/Api/CheckoutController.php   # POST /api/checkout/{order}/pay -> returns checkout_url
  Http/Controllers/Webhooks/
    TamaraWebhookController.php
    TabbyWebhookController.php
  Models/Order.php   # add: payment_gateway, gateway_order_id, gateway_status, gateway_payload (json)
  Filament/Pages/PaymentGatewaySettings.php     # admin-managed keys/config (see §4)
```

Build one shared `PaymentGatewayInterface` — do not write divergent one-off integrations. Vue only ever calls your Laravel API and redirects via `window.location.href = checkout_url`; it never talks to Tamara/Tabby directly. Filament owns settings + order status visibility, not checkout.

## 2. Tamara

| | |
|---|---|
| Create session | `POST https://api.tamara.co/checkout` (sandbox: `api-sandbox.tamara.co`) |
| Auth | `Authorization: Bearer {api_token}` |
| Returns | `order_id`, `checkout_id`, `status`, `checkout_url` — store `order_id` |
| Confirm | After webhook status = `approved`, call back `POST /orders/{order_id}/authorise` — **commonly missed step, order is not finalized without it** |
| Currency | SAR |

Request body needs: `total_amount{amount,currency}`, `order_reference_id`, `order_number`, `items[]` (name, type, reference_id, sku, quantity, unit_price, total_amount), `consumer{first_name,last_name,phone_number,email}`, `country_code: "SA"`, `merchant_url{success,failure,cancel,notification}`.

Webhook verification: Tamara issues a **notification token** at registration — validate it against the incoming payload before trusting it.

## 3. Tabby

| | |
|---|---|
| Create session | `POST https://api.tabby.ai/api/v2/checkout` |
| Auth | `Authorization: Bearer {public_key}` |
| Returns | `id` (session), `configuration.available_products.installments[0].web_url` — redirect here |
| Get payment | `GET /api/v2/payments/{id}` (use `secret_key`) |
| Capture | `POST /api/v2/payments/{id}/captures` |
| Update ref | `PUT /api/v2/payments/{id}` — **only `reference_id` is accepted, other fields silently ignored** |
| Webhooks | manage via `/api/v1/webhooks` (register/list/remove) |

Request body: `payment{amount,currency,buyer{phone,email,name},order{reference_id,items[]}, shipping_address{city,address,zip}}`, `merchant_code`, `merchant_urls{success,cancel,failure}`.

Webhook verification: when registering the webhook, set a custom header name/value pair — Tabby echoes it back on each delivery; reject anything that doesn't match.

Optional package: `tabbyai/laravel` (Packagist) — reduces boilerplate if agent prefers it over raw `Http::` calls. No equivalent official Tamara SDK; use Laravel's HTTP client directly for Tamara.

## 4. Filament v5 — Admin-Managed Settings

Single settings page (`PaymentGatewaySettings extends Page`, or a `Settings` model + Filament resource — agent's choice) with two tabs/sections:

**Tamara:** environment (sandbox/live) toggle, API token, notification token, enabled toggle.
**Tabby:** environment toggle, public key, secret key, merchant code, webhook header name/value, enabled toggle.

Requirements:
- **Encrypt at rest** — cast key fields with Laravel's `encrypted` cast, never plain `text()` in DB.
- Values read at runtime via a config repository/service (not `env()`) so admin changes apply without redeploy.
- Toggle to enable/disable each gateway independently — checkout UI should only offer enabled gateways.
- A read-only "last webhook received at" + raw payload viewer per gateway, for support debugging.
- On the Order resource: gateway used, gateway's order/payment ID, status badge, manual "Sync status" action (calls gateway's GET endpoint), raw `gateway_payload` JSON viewer.

## 5. Non-negotiables

- HTTPS required everywhere, including local webhook testing (use ngrok).
- Webhook routes excluded from CSRF middleware, signature-verified inside the controller — never trust payload blindly.
- Webhook handling must be **idempotent** (gateways retry deliveries; don't double-fulfill).
- Order amount/currency must match what was sent at session-creation — both gateways re-verify and reject mismatches.
- Treat the **webhook**, not the browser redirect, as the fulfillment trigger.
