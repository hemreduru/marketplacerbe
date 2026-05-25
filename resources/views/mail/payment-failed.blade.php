<x-mail::message>
# Ödeme İşlemi Başarısız

Merhaba {{ $user->name }},

Abonelik ödemeni gerçekleştiremedik. Lütfen ödeme bilgilerinizi kontrol ederek tekrar deneyin.

<x-mail::button :url="route('subscription.select')" color="red">
Ödemeyi Tekrar Dene
</x-mail::button>

3 gün içinde ödeme gerçekleşmezse hesabınız askıya alınacaktır.

Cirotik Ekibi
</x-mail::message>
