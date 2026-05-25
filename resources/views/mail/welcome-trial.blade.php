<x-mail::message>
# Cirotik'e Hoş Geldiniz, {{ $user->name }}!

**{{ $trialDays }} günlük ücretsiz deneme süreniz başladı.**

Cirotik ile Trendyol mağazanızı kolayca yönetebilir, siparişlerinizi takip edebilir ve finansal raporlarınıza tek panelden erişebilirsiniz.

**Deneme sürenizde neler yapabilirsiniz:**

- Trendyol ürünlerinizi ve siparişlerinizi senkronize edin
- Müşteri sorularını tek yerden yanıtlayın
- Finansal analizlerinizi görüntüleyin

<x-mail::button :url="route('dashboard')">
Panele Git
</x-mail::button>

Sorunuz olursa bize yazabilirsiniz. Başarılı satışlar dileriz!

Cirotik Ekibi
</x-mail::message>
