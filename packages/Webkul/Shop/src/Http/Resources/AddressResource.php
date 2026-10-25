<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'address_type' => $this->address_type,
            'parent_address_id' => $this->parent_address_id,
            'customer_id' => $this->customer_id,
            'cart_id' => $this->cart_id,
            'order_id' => $this->order_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'company_name' => $this->company_name,
            /* Split on any newline style; PHP_EOL differs by OS and browsers submit \r\n. */
            'address' => preg_split('/\r\n|\r|\n/', (string) $this->address),
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postcode' => $this->postcode,
            'email' => $this->email,
            'phone' => $this->phone,
            'vat_id' => $this->vat_id,
            'default_address' => $this->default_address,
            'use_for_shipping' => $this->use_for_shipping,
            'additional' => $this->additional,
        ];
    }
}
