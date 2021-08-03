<?php

namespace Kaban\Components\Admin\Words\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GetSearchedWordsResource extends JsonResource {
//class GetAllWordsResource extends ResourceCollection {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray( $request ) {

        return [
            'text'  => $this->title,
            'media' => $this->media,
            'value' => $this->id,
        ];

        return parent::toArray( $request );
    }
}
