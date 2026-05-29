<?php

namespace Database\Seeders;

use App\Models\ShippingCarrier;
use Illuminate\Database\Seeder;

class ShippingCarrierSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            [
                'name'                  => 'SMSA Express',
                'code'                  => 'SMSA',
                'tracking_url_template' => 'https://www.smsaexpress.com/tracking?tracknumbers={tracking_number}',
                'sort_order'            => 1,
            ],
            [
                'name'                  => 'Aramex',
                'code'                  => 'Aramex',
                'tracking_url_template' => 'https://www.aramex.com/track/results?ShipmentNumber={tracking_number}',
                'sort_order'            => 2,
            ],
            [
                'name'                  => 'DHL',
                'code'                  => 'DHL',
                'tracking_url_template' => 'https://www.dhl.com/en/express/tracking.html?AWB={tracking_number}',
                'sort_order'            => 3,
            ],
            [
                'name'                  => 'FedEx',
                'code'                  => 'FedEx',
                'tracking_url_template' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_number}',
                'sort_order'            => 4,
            ],
            [
                'name'                  => 'UPS',
                'code'                  => 'UPS',
                'tracking_url_template' => 'https://www.ups.com/track?tracknum={tracking_number}',
                'sort_order'            => 5,
            ],
            [
                'name'                  => 'USPS',
                'code'                  => 'USPS',
                'tracking_url_template' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_number}',
                'sort_order'            => 6,
            ],
            [
                'name'                  => 'J&T Express',
                'code'                  => 'J&T',
                'tracking_url_template' => 'https://www.jtexpress.sa/trajectoryQuery?waybillNo={tracking_number}',
                'sort_order'            => 7,
            ],
            [
                'name'                  => 'Saudi Post (SPL)',
                'code'                  => 'Saudi Post',
                'tracking_url_template' => 'https://tracking.spl.com.sa/tracking?lang=en&trackId={tracking_number}',
                'sort_order'            => 8,
            ],
        ];

        foreach ($carriers as $carrier) {
            ShippingCarrier::updateOrCreate(
                ['code' => $carrier['code']],
                $carrier,
            );
        }
    }
}
