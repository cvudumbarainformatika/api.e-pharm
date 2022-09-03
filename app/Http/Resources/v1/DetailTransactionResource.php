<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'transaction_id' => $this->transaction_id,
            'transaksi' => $this->whenLoaded('transaction'),
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product'),
            'qty' => $this->qty,
            'expired' => $this->expired,
            'harga' => $this->harga,
            'sub_total' => $this->sub_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
