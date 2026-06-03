<?php

namespace App\Console\Commands;

use App\Models\Gym;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateGym extends Command
{
    protected $signature = 'gym:create {--name=} {--email=} {--plan=trial}';

    protected $description = 'Create a gym tenant (landlord)';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?? '');
        $email = (string) ($this->option('email') ?? '');
        $plan = (string) ($this->option('plan') ?? 'trial');

        if (! filled($name) || ! filled($email)) {
            $this->error('Missing --name or --email.');

            return self::FAILURE;
        }

        $slugBase = Str::slug($name);
        $slug = $slugBase;
        $i = 1;
        while (Gym::query()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $slugBase.'-'.$i;
        }

        $gym = Gym::query()->create([
            'name' => $name,
            'slug' => $slug,
            'owner_email' => $email,
            'plan' => $plan,
            'status' => $plan === 'trial' ? 'trial' : 'active',
            'trial_ends_at' => $plan === 'trial' ? now()->addDays(14) : null,
            'database' => 'default',
        ]);

        $this->info("Gym created: {$gym->name} ({$gym->slug}.{$this->baseDomain()})");

        return self::SUCCESS;
    }

    private function baseDomain(): string
    {
        return (string) config('app.base_domain', 'gymsaathi.in');
    }
}
