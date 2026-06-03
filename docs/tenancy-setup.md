## Multi-tenancy setup (GymSaathi)

### Composer

Install required packages:

```bash
composer require spatie/laravel-multitenancy lab404/laravel-impersonate
php artisan migrate
```

### DNS + SSL

- Wildcard DNS: `*.gymsaathi.in` → VPS IP
- Wildcard SSL (DNS-01): use Cloudflare DNS for easiest automation

### URLs

- Landlord:
  - `https://gymsaathi.in/register`
  - `https://gymsaathi.in/superadmin`
- Tenant:
  - `https://{gym-slug}.gymsaathi.in/admin`

