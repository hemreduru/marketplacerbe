<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pazaryeri credential sırlarını rest'te şifreler (launch gate v1).
 *
 * api_key/api_secret varchar(255) idi; Laravel encrypted payload (~240+ karakter)
 * bu sınıra sığmaz → TEXT'e genişletilir. additional_credentials zaten longtext
 * (webhook_secret gibi sırlar da tutuyor) → şifreli array olarak saklanır.
 *
 * Mevcut plaintext değerler tek seferlik şifrelenir; migration idempotenttir
 * (zaten şifreli değerler yeniden şifrelenmez).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $columns = ['api_key', 'api_secret', 'additional_credentials'];

    public function up(): void
    {
        // Şifreli değerler varchar(255)'e sığmaz — TEXT'e genişlet.
        Schema::table('user_marketplace_credentials', function (Blueprint $table) {
            $table->text('api_key')->change();
            $table->text('api_secret')->change();
        });

        foreach (DB::table('user_marketplace_credentials')->get() as $row) {
            $updates = [];

            foreach ($this->columns as $column) {
                $value = $row->{$column};

                if ($value === null || $value === '' || $this->isEncrypted($value)) {
                    continue;
                }

                $updates[$column] = Crypt::encryptString($value);
            }

            if ($updates !== []) {
                DB::table('user_marketplace_credentials')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('user_marketplace_credentials')->get() as $row) {
            $updates = [];

            foreach ($this->columns as $column) {
                $value = $row->{$column};

                if ($value === null || $value === '') {
                    continue;
                }

                try {
                    $updates[$column] = Crypt::decryptString($value);
                } catch (Throwable) {
                    // Zaten plaintext — dokunma.
                }
            }

            if ($updates !== []) {
                DB::table('user_marketplace_credentials')->where('id', $row->id)->update($updates);
            }
        }

        Schema::table('user_marketplace_credentials', function (Blueprint $table) {
            $table->string('api_key')->change();
            $table->string('api_secret')->change();
        });
    }

    /**
     * Değer geçerli bir Laravel şifreli payload'ı mı (idempotency kontrolü).
     */
    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
