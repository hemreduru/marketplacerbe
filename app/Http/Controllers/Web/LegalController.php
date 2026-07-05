<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Yasal sayfalar (KVKK/aydınlatma, kullanım koşulları, mesafeli satış).
 *
 * NOT: İçerik taslaktır (yer tutucu). Gerçek bağlayıcı hukuki metin yayına
 * almadan önce hukuk danışmanı tarafından doldurulmalıdır (kod-dışı kalem).
 */
class LegalController extends Controller
{
    /** @var list<string> */
    private const PAGES = ['privacy', 'terms', 'distance-sales'];

    public function show(string $page): View
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        return view('legal.'.$page);
    }
}
