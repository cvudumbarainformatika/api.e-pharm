<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'reff' => $this->reff,
            'faktur' => $this->faktur,
            'tanggal' => $this->tanggal,
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'total' => $this->total,
            'ongkir' => $this->ongkir,
            'potongan' => $this->potongan,
            'bayar' => $this->bayar,
            'kembali' => $this->kembali,
            'tempo' => $this->tempo,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user'),
            'kasir_id' => $this->kasir_id,
            'kasir' => $this->whenLoaded('kasir'),
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier'),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer'),
            'dokter_id' => $this->dokter_id,
            'dokter' => $this->whenLoaded('dokter'),
            'detail_transaction' => $this->whenLoaded('detail_transaction'),
            'beban_transaction' => $this->whenLoaded('beban_transaction'),
            'penerimaan_transaction' => $this->whenLoaded('penerimaan_transaction'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
