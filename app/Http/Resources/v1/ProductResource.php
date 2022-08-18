<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'barcode' => $this->barcode,
            'nama' => $this->nama,
            'merk' => $this->merk_id,
            'merk' => $this->whenLoaded('merk'),
            'satuan_id' => $this->satuan_id,
            'satuan' => $this->whenLoaded('satuan'),
            'harga_beli' => $this->harga_beli,
            'harga_jual_umum' => $this->harga_jual_umum,
            'harga_jual_resep' => $this->harga_jual_resep,
            'harga_jual_cust' => $this->harga_jual_cust,
            'stok_awal' => $this->stok_awal,
            'limit_stok' => $this->limit_stok,
            'rak_id' => $this->rak_id,
            'rak' => $this->whenLoaded('rak'),
            'kategori_id' => $this->kategori_id,
            'kategori' => $this->whenLoaded('kategori'),
            'expired' => $this->expired,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
