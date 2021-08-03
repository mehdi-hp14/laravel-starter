<?php

namespace Kaban\Components\Admin\Roles\Controllers;

use Illuminate\Http\Request;
use Kaban\Components\Admin\Roles\Resources\GetAllRolesResource;
use Kaban\Components\Admin\Roles\Resources\GetRoleEditItemResource;
use Kaban\Components\Admin\Roles\Resources\GetRolePermissionsResource;
use Kaban\Core\Controllers\AdminBaseController;
use Kaban\Models\Permission;
use Kaban\Models\Role;
use Kaban\Models\User;

class RolesController extends AdminBaseController {

    public function edit( $id ) {
        $post = Role::with( 'permissions' )->findOrFail( $id );

        return new GetRoleEditItemResource( $post );
    }

    public function store( Request $request ) {

        $request->validate( [ 'name' => 'required', 'permissions' => 'required' ] );
        $form = json_decode( $request->form );

        $role       = new Role();
        $role->slug = slugify( $request->name );
        $role->name = $request->name;
        $role->save();

        $tagIds = $role->syncPermissions( collect( $request->permissions  )->pluck( 'value' )->toArray() );

        return 1;
    }

    public function all( Request $request ) {
        $sortType = 'DESC';
        $sortBy   = 'id';

        if ( ! empty( $request->sortBy[0] ) && in_array( $request->sortBy[0], [ 'id', 'title' ] ) ) {
            $sortBy = $request->sortBy[0];
        }
        if ( empty( $request->sortDesc[0] ) ) {
            $sortType = 'ASC';
        }

        $posts = Role::when( $request->search, function ( $q ) use ( $request ) {
            $q->orWhere( 'name', 'like', "%$request->search%" );
            $q->orWhere( 'description', 'like', "%$request->search%" );
        } )->orderBy( $sortBy, $sortType )->paginate( $request->itemsPerPage, [ '*' ], 'ascasc', $request->page );

        return GetAllRolesResource::collection( $posts );
    }

    public function destroy( $id ) {
        Role::findOrFail( $id )->delete();

        return 'done';
    }

    public function update( $id, Request $request ) {
        $role = Role::findOrFail( $id );
        $role->update( [
            'name' => $request->name,
            'slug' => slugify( $request->name ),
        ] );
//        print_r( $request->permissions );
//        dd( 2 );

        $role->syncPermissions( $request->permissions );

    }

    public function searchPermissions( Request $request ) {
        $tags = Permission::when( $request->val, function ( $q ) use ( $request ) {
            $q->where( 'name', 'like', "%$request->val%" );
        } )->paginate( 10 );

        return GetRolePermissionsResource::collection( $tags );
    }
}
