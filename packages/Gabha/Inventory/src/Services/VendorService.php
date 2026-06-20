<?php

declare(strict_types=1);

namespace Gabha\Inventory\Services;

use Gabha\Inventory\Exceptions\VendorHasPurchasesException;
use Gabha\Inventory\Models\Vendor;
use Gabha\Inventory\Repositories\VendorRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Business-logic layer for vendors.
 *
 * Keeps controllers thin by owning orchestration concerns: transactional
 * boundaries, domain events and the delete guard. Persistence is delegated to
 * {@see VendorRepository}; HTTP/validation concerns stay in the controller and
 * form requests.
 */
class VendorService
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected VendorRepository $vendorRepository) {}

    /**
     * Persist a new vendor inside a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vendor
    {
        return DB::transaction(function () use ($data) {
            Event::dispatch('inventory.vendor.create.before');

            $vendor = $this->vendorRepository->create($data);

            Event::dispatch('inventory.vendor.create.after', $vendor);

            return $vendor;
        });
    }

    /**
     * Update an existing vendor inside a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, int $id): Vendor
    {
        return DB::transaction(function () use ($data, $id) {
            Event::dispatch('inventory.vendor.update.before', $id);

            $vendor = $this->vendorRepository->update($data, $id);

            Event::dispatch('inventory.vendor.update.after', $vendor);

            return $vendor;
        });
    }

    /**
     * Delete a vendor, enforcing the "no purchase records" business rule.
     *
     * @throws VendorHasPurchasesException when the vendor still has purchases.
     */
    public function delete(int $id): void
    {
        if ($this->vendorRepository->hasPurchases($id)) {
            throw new VendorHasPurchasesException;
        }

        DB::transaction(function () use ($id) {
            Event::dispatch('inventory.vendor.delete.before', $id);

            $this->vendorRepository->delete($id);

            Event::dispatch('inventory.vendor.delete.after', $id);
        });
    }
}
