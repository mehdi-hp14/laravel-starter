<?php

namespace Kaban\Components\Admin\Roles\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Kaban\General\Enums\EPostStatus;

class GetRoleEditItemResource extends JsonResource {

    public function toArray( $request ) {

//        print_r( $this->permissions->map( function ( $permission ) {
//            return [ [ 'value' => $permission->id, 'text' => $permission->name ] ];
//        } )->toArray() );
//        dd( 2 );

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'permissions'     => $this->permissions->pluck( 'id' ),
            'permissionItems' => $this->permissions->map( function ( $permission ) {
                return  [ 'value' => $permission->id, 'text' => $permission->name ] ;
            } ),
        ];
    }
}
