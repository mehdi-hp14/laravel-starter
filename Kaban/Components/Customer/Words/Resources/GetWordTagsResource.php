<?php

namespace Kaban\Components\Admin\Words\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GetWordTagsResource extends JsonResource {

    public function toArray( $request ) {

        return [
            'text'  => $this->name,
            'value' => $this->id,
        ];
    }
}
