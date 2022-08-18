<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'kontak' => $this->kontak,
            'saldo_awal_piutang' => $this->saldo_awal_piutang,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
