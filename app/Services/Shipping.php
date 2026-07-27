<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ShippingServiceException;
use App\Models\City;
use App\Models\Province;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Shipping
{
    private ?string $param = null;

    /** @var array<string, int|string>|null */
    private ?array $query = null;

    /** @var array<int, array{id:string, name:string}> */
    private array $couriers = [
        ['id' => 'leopards', 'name' => 'Leopards Courier'],
        ['id' => 'tcs', 'name' => 'TCS'],
        ['id' => 'mnp', 'name' => 'M&P'],
        ['id' => 'blueex', 'name' => 'BlueEx'],
        ['id' => 'pakistan-post', 'name' => 'Pakistan Post'],
        ['id' => 'trax', 'name' => 'Trax'],
    ];

    public function province(?int $id = null): self
    {
        $this->param = 'province';
        $this->query = $id === null ? null : ['id' => $id];

        return $this;
    }

    public function city(?int $id = null): self
    {
        $this->param = 'city';
        $this->query = $id === null ? null : ['id' => $id];

        return $this;
    }

    public function fromProvince(int $id): self
    {
        $this->query ??= [];
        $this->query['province'] = $id;

        return $this;
    }

    public function cost(?int $origin, ?int $destination, ?int $weight, ?string $courier): self
    {
        $this->param = 'cost';
        $this->query = [
            'origin' => max(0, (int) $origin),
            'destination' => max(0, (int) $destination),
            'weight' => max(1, (int) $weight),
            'courier' => strtolower(trim((string) $courier)),
        ];

        return $this;
    }

    /** @return array<int, array{id:string, name:string}> */
    public function couriers(): array
    {
        return $this->couriers;
    }

    /** @return array<int, array<string, mixed>> */
    public function get(): array
    {
        $supportReference = (string) Str::uuid();

        try {
            return match ($this->param) {
                'province' => $this->provinceResult(),
                'city' => $this->cityResult(),
                'cost' => $this->costResult(),
                default => throw new \LogicException('A shipping resource must be selected before get().'),
            };
        } catch (ShippingServiceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Pakistan shipping rate calculation failed.', [
                'support_reference' => $supportReference,
                'resource' => $this->param,
                'query' => $this->query,
                'exception' => $exception::class,
                'error_code' => 'PAKISTAN_SHIPPING_RATE_FAILED',
            ]);

            throw new ShippingServiceException($supportReference);
        } finally {
            $this->param = null;
            $this->query = null;
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function provinceResult(): array
    {
        $query = Province::query()->orderBy('name');

        if (isset($this->query['id'])) {
            $query->where('provider_id', (int) $this->query['id']);
        }

        return $query->get()->map(fn (Province $province): array => [
            'province_id' => $province->provider_id,
            'province' => $province->name,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function cityResult(): array
    {
        $query = City::query()->with('province')->orderBy('name');

        if (isset($this->query['id'])) {
            $query->where('provider_id', (int) $this->query['id']);
        }

        if (isset($this->query['province'])) {
            $query->whereHas(
                'province',
                fn ($provinceQuery) => $provinceQuery->where('provider_id', (int) $this->query['province']),
            );
        }

        return $query->get()->map(fn (City $city): array => [
            'city_id' => $city->provider_id,
            'city_name' => $city->name,
            'type' => $city->type,
            'postal_code' => $city->postal_code,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function costResult(): array
    {
        $courier = (string) ($this->query['courier'] ?? '');
        $supported = collect($this->couriers)->firstWhere('id', $courier);

        if ($supported === null) {
            throw new \InvalidArgumentException('The selected courier is not supported.');
        }

        $destination = City::query()
            ->where('provider_id', (int) ($this->query['destination'] ?? 0))
            ->with('province')
            ->firstOrFail();

        $weightInGrams = max(1, (int) ($this->query['weight'] ?? 1));
        $kilograms = max(1, (int) ceil($weightInGrams / 1000));
        $zoneSurcharge = $this->zoneSurcharge($destination->province?->name);
        $pricing = $this->pricingFor($courier);
        $value = $pricing['base'] + (($kilograms - 1) * $pricing['per_kg']) + $zoneSurcharge;

        return [[
            'code' => $courier,
            'name' => $supported['name'],
            'costs' => [[
                'service' => $pricing['service'],
                'description' => 'Domestic delivery across Pakistan',
                'cost' => [[
                    'value' => $value,
                    'etd' => $pricing['etd'],
                    'note' => $destination->name.', '.$destination->province?->name,
                ]],
            ]],
        ]];
    }

    /** @return array{base:int, per_kg:int, service:string, etd:string} */
    private function pricingFor(string $courier): array
    {
        return match ($courier) {
            'tcs' => ['base' => 320, 'per_kg' => 170, 'service' => 'Overnight', 'etd' => '1-2'],
            'mnp' => ['base' => 280, 'per_kg' => 150, 'service' => 'Express', 'etd' => '2-3'],
            'blueex' => ['base' => 260, 'per_kg' => 145, 'service' => 'BlueEx Express', 'etd' => '2-4'],
            'pakistan-post' => ['base' => 180, 'per_kg' => 110, 'service' => 'UMS', 'etd' => '3-6'],
            'trax' => ['base' => 250, 'per_kg' => 140, 'service' => 'Standard', 'etd' => '2-4'],
            default => ['base' => 240, 'per_kg' => 135, 'service' => 'Leopards Overnight', 'etd' => '2-4'],
        };
    }

    private function zoneSurcharge(?string $province): int
    {
        return match (Str::lower((string) $province)) {
            'balochistan', 'gilgit baltistan', 'azad kashmir' => 180,
            'khyber pakhtunkhwa' => 90,
            default => 0,
        };
    }
}
