# Running the test suite

Tests run against an **isolated database** (`my_bagisto_store_test`), never the
live store DB. This matters: the suite previously failed in bulk because it read
live data — admin-saved Razorpay keys, an active cart price rule, disabled
carriers — that leaked into assertions. `phpunit.xml` pins `DB_DATABASE` to the
test database so that can't happen.

## One-time provisioning

```bash
# create the database (MySQL)
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS my_bagisto_store_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# migrate + seed the clean Bagisto baseline (no admin overrides)
DB_DATABASE=my_bagisto_store_test php artisan migrate:fresh --seed --force
```

The suite uses `DatabaseTransactions`, so every test rolls back its writes — you
only provision once. Re-run the seed command after a migration is added.

## Running

```bash
vendor/bin/pest                              # everything
vendor/bin/pest --filter=CheckoutTest        # one file/group
vendor/bin/pest --filter=Razorpay            # one package
```

## Carrier config in tests

The store ships via Shiprocket and disables the flat-rate / free carriers in
production config. The stock checkout tests assert against those carriers, so
`tests/Pest.php` enables them for the Shop test scope only (via `config()` in a
`beforeEach`). Production config is untouched.

## Known remaining failures (pre-existing, not Urbanflaky code)

- `CheckoutTest > it should store the payment method for guest user`
- `CheckoutTest > it should store the payment method for customer`

Both assert `cart.billing_address.address == explode("\n", $address->address)`.
The faker-generated address round-trips with an escaped newline, so the stored
value does not split on a real newline. This is stock-Bagisto test brittleness
tied to faker output — it fails identically on any database and is unrelated to
the application code. Left as-is rather than editing vendor test assertions.

## CI note

A CI job should create + migrate + seed `my_bagisto_store_test`, then run
`vendor/bin/pest`. Add `composer audit` as a separate blocking step.
