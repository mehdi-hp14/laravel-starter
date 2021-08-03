<?php

namespace Kaban\Components\Admin\Words\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Kaban\General\Enums\EWordStatus;

class GetWordEditItemResource extends JsonResource {

    public function toArray( $request ) {
        if ( $theWorder = $this->theWorder ) {
            $theWorder = $theWorder->only( 'id', 'url', 'title' );
        }
        if ( $theBackdrop = $this->theBackdrop ) {
            $theBackdrop = $theBackdrop->only( 'id', 'url', 'title' );
        }

        return [
            'title'         => $this->title,
            'categories'    => $this->category->id,
            'categoryItems' => [ [ 'value' => $this->category->id, 'text' => $this->category->title ] ],
            'excerpt'       => $this->excerpt,
            'id'            => $this->id,
            'content'       => $this->content,
            'status'        => [ 'value' => $this->status, 'text' => EWordStatus::farsi( $this->status ) ],
            'statusItems'   => EWordStatus::vuetifyTransFlip( 'admin.status_' ),
            'tags'          => $this->tags->pluck( 'name' ),
            'poster'        => null,
            'posterImg'     => $theWorder,
            'backdrop'      => null,
            'backdropImg'   => $theBackdrop,
        ];
    }
}
