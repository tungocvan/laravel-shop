<?php

namespace Tests\Feature\Admission;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdmissionReceiptQrTrackingSignatureTest extends TestCase
{
    public function test_receipt_route_ignores_only_known_tracking_parameters(): void
    {
        $receiptRoute = Route::getRoutes()->getByName('admission.search.receipt');

        $this->assertNotNull($receiptRoute);
        $this->assertContains(
            'signed:zarsrc,utm_source,utm_medium,utm_campaign,gidzl',
            $receiptRoute->gatherMiddleware()
        );

        // Exercise the production route's actual middleware without needing
        // student data or bypassing the approved-application controller check.
        Route::get('/__tests/admission/receipt-signature-probe', fn () => response('ok'))
            ->middleware($receiptRoute->gatherMiddleware())
            ->name('admission.receipt-signature-probe');

        $url = URL::signedRoute('admission.receipt-signature-probe');

        $this->get($url)->assertOk()->assertSee('ok');
        $this->get($url.'&zarsrc=qr&utm_source=scanner&utm_medium=share&utm_campaign=receipt&gidzl=tracking')
            ->assertOk()->assertSee('ok');

        $this->get($url.'&unexpected=changed')->assertForbidden();

        $tampered = preg_replace('/signature=[^&]+/', 'signature='.str_repeat('0', 64), $url);
        $this->assertNotSame($url, $tampered);
        $this->get($tampered)->assertForbidden();

        $unsigned = preg_replace('/[?&]signature=[^&]+/', '', $url);
        $this->get($unsigned)->assertForbidden();
    }
}
