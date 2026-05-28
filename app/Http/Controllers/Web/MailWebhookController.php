<?php

namespace App\Http\Controllers\Web;

use App\Models\MailSuppression;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MailWebhookController
{
    public function bounce(Request $request): Response
    {
        $message = $request->input('Message');
        $data = json_decode((string) $message, true);

        if ($data && ($data['notificationType'] ?? '') === 'Bounce') {
            $recipients = $data['bounce']['bouncedRecipients'] ?? [];
            foreach ($recipients as $r) {
                MailSuppression::updateOrCreate(
                    ['email' => $r['emailAddress']],
                    ['reason' => 'bounce', 'raw' => $data],
                );
            }
        }

        return response()->noContent();
    }

    public function complaint(Request $request): Response
    {
        $message = $request->input('Message');
        $data = json_decode((string) $message, true);

        if ($data && ($data['notificationType'] ?? '') === 'Complaint') {
            $recipients = $data['complaint']['complainedRecipients'] ?? [];
            foreach ($recipients as $r) {
                MailSuppression::updateOrCreate(
                    ['email' => $r['emailAddress']],
                    ['reason' => 'complaint', 'raw' => $data],
                );
            }
        }

        return response()->noContent();
    }
}
