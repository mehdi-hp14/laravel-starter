<?php

namespace Kaban\Components\Admin\Words\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GetAllWordsResource extends JsonResource {
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
            'title'    => $this->title,
            'category' => $this->category->title,
//            'content' => $this->content,
            'excerpt'  => $this->excerpt,
            'id'       => $this->id,
        ];

        return parent::toArray( $request );
    }
}
