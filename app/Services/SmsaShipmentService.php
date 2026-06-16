<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

final class SmsaShipmentService
{
    private ?SoapClient $client = null;
    private string $passKey;
    private string $wsdlUrl;

    public function __construct()
    {
        $this->passKey = setting('shipping.smsa_pass_key') ?? config('services.smsa.pass_key') ?? '';
        $this->wsdlUrl = setting('shipping.smsa_wsdl_url') ?? config('services.smsa.wsdl_url') ?? 'https://track.smsaexpress.com/SELOAPI/ServiceSELO.svc?wsdl';
    }

    /**
     * Book a new SMSA shipment for an order.
     *
     * @return array{success: bool, awb: ?string, tracking_number: ?string, error: ?string}
     */
    public function bookShipment(Order $order): array
    {
        if (empty($this->passKey)) {
            return [
                'success'         => false,
                'awb'             => null,
                'tracking_number' => null,
                'error'           => 'SMSA credentials not configured. Set SMSA_PASS_KEY in .env',
            ];
        }

        $shippingAddr = $order->shippingAddress;
        if (!$shippingAddr) {
            return [
                'success'         => false,
                'awb'             => null,
                'tracking_number' => null,
                'error'           => 'Order has no shipping address.',
            ];
        }

        try {
            $client = $this->getClient();

            $params = [
                'passKey'       => $this->passKey,
                'refNo'         => $order->order_number,
                'sentDate'      => now()->format('Y-m-d'),
                'idNo'          => '',
                'cName'         => $shippingAddr->first_name . ' ' . $shippingAddr->last_name,
                'cntry'         => $shippingAddr->country ?? 'SA',
                'cCity'         => $shippingAddr->city ?? '',
                'cZip'          => $shippingAddr->postal_code ?? '',
                'cPOBox'        => '',
                'cTel'          => $shippingAddr->phone ?? '',
                'cTel2'         => '',
                'cCell'         => $shippingAddr->phone ?? '',
                'cAddr1'        => $shippingAddr->address_line_1 ?? '',
                'cAddr2'        => $shippingAddr->address_line_2 ?? '',
                'shipType'      => 'DLV', // Delivery
                'PCs'           => $order->items->sum('quantity'),
                'cEmail'        => $order->user?->email ?? $order->guest_email ?? '',
                'carrValue'     => '',
                'carrCurr'      => '',
                'codAmt'        => $order->payment_method === 'cod' ? (float) $order->total : 0,
                'weight'        => 1,
                'itemDesc'      => 'Raqeeb Express Store  Order ' . $order->order_number,
            ];

            $result = $client->addShipment($params);

            $awb = $result->addShipmentResult ?? null;

            if ($awb && !str_starts_with((string) $awb, 'Failed')) {
                // Update order with tracking info
                $order->update([
                    'tracking_number'  => $awb,
                    'shipping_status'  => 'booked',
                ]);

                return [
                    'success'         => true,
                    'awb'             => $awb,
                    'tracking_number' => $awb,
                    'error'           => null,
                ];
            }

            return [
                'success'         => false,
                'awb'             => null,
                'tracking_number' => null,
                'error'           => 'SMSA returned: ' . ($awb ?? 'empty response'),
            ];
        } catch (SoapFault $e) {
            Log::error('SMSA bookShipment failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return [
                'success'         => false,
                'awb'             => null,
                'tracking_number' => null,
                'error'           => 'SMSA SOAP error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Download shipment PDF label.
     *
     * @return array{success: bool, pdf_base64: ?string, error: ?string}
     */
    public function getShipmentPdf(string $awb): array
    {
        if (empty($this->passKey)) {
            return ['success' => false, 'pdf_base64' => null, 'error' => 'SMSA credentials not configured.'];
        }

        try {
            $client = $this->getClient();
            $result = $client->getPDF([
                'passKey' => $this->passKey,
                'awbNo'   => $awb,
            ]);

            $pdfData = $result->getPDFResult ?? null;

            if ($pdfData) {
                return ['success' => true, 'pdf_base64' => $pdfData, 'error' => null];
            }

            return ['success' => false, 'pdf_base64' => null, 'error' => 'No PDF data returned from SMSA.'];
        } catch (SoapFault $e) {
            Log::error('SMSA getPDF failed', ['awb' => $awb, 'error' => $e->getMessage()]);
            return ['success' => false, 'pdf_base64' => null, 'error' => 'SMSA SOAP error: ' . $e->getMessage()];
        }
    }

    /**
     * Get tracking status from SMSA.
     *
     * @return array{success: bool, status: ?string, details: array, error: ?string}
     */
    public function getTrackingStatus(string $awb): array
    {
        if (empty($this->passKey)) {
            return ['success' => false, 'status' => null, 'details' => [], 'error' => 'SMSA credentials not configured.'];
        }

        try {
            $client = $this->getClient();
            $result = $client->getTracking([
                'passKey' => $this->passKey,
                'awbNo'   => $awb,
            ]);

            $tracking = $result->getTrackingResult ?? null;

            return [
                'success' => true,
                'status'  => is_string($tracking) ? $tracking : 'unknown',
                'details' => is_object($tracking) ? (array) $tracking : [],
                'error'   => null,
            ];
        } catch (SoapFault $e) {
            Log::error('SMSA getTracking failed', ['awb' => $awb, 'error' => $e->getMessage()]);
            return ['success' => false, 'status' => null, 'details' => [], 'error' => $e->getMessage()];
        }
    }

    private function getClient(): SoapClient
    {
        if (!$this->client) {
            $this->client = new SoapClient($this->wsdlUrl, [
                'trace'      => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);
        }
        return $this->client;
    }
}
