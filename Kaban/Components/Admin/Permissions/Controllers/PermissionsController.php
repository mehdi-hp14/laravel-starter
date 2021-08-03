<?php

namespace Kaban\Components\Admin\Permissions\Controllers;

use Illuminate\Http\Request;
use Kaban\Components\Admin\Permissions\Resources\GetAllPermissionsResource;
use Kaban\Core\Controllers\AdminBaseController;
use Kaban\Models\Permission;
use Kaban\Models\Role;
use Kaban\Models\User;

class PermissionsController extends AdminBaseController {

    public function store( Request $request ) {
//        dd( $request->all() );
        $request->validate( [ 'name' => 'required' ] );

        $permission              = new Permission();
        $permission->slug        = slugify( $request->name );
        $permission->name        = $request->name;
        $permission->description = $request->description;
        $permission->save();

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

        $posts = Permission::when( $request->search, function ( $q ) use ( $request ) {
            $q->orWhere( 'name', 'like', "%$request->search%" );
            $q->orWhere( 'description', 'like', "%$request->search%" );
        } )->orderBy( $sortBy, $sortType )->paginate( $request->itemsPerPage, [ '*' ], 'ascasc', $request->page );

        return GetAllPermissionsResource::collection( $posts );
    }

    public function destroy( $id ) {
        Permission::findOrFail( $id )->delete();

        return 'done';
    }

    public function Permission() {
        $dev_permission     = Permission::where( 'slug', 'create-tasks' )->first();
        $manager_permission = Permission::where( 'slug', 'edit-users' )->first();

        //RoleTableSeeder.php
        $dev_role       = new Role();
        $dev_role->slug = 'developer';
        $dev_role->name = 'Front-end Developer';
        $dev_role->save();
        $dev_role->permissions()->attach( $dev_permission );

        $manager_role       = new Role();
        $manager_role->slug = 'manager';
        $manager_role->name = 'Assistant Manager';
        $manager_role->save();
        $manager_role->permissions()->attach( $manager_permission );

        $dev_role     = Role::where( 'slug', 'developer' )->first();
        $manager_role = Role::where( 'slug', 'manager' )->first();

        $createTasks       = new Permission();
        $createTasks->slug = 'create-tasks';
        $createTasks->name = 'Create Tasks';
        $createTasks->save();
        $createTasks->roles()->attach( $dev_role );

        $editUsers       = new Permission();
        $editUsers->slug = 'edit-users';
        $editUsers->name = 'Edit Users';
        $editUsers->save();
        $editUsers->roles()->attach( $manager_role );

        $dev_role     = Role::where( 'slug', 'developer' )->first();
        $manager_role = Role::where( 'slug', 'manager' )->first();
        $dev_perm     = Permission::where( 'slug', 'create-tasks' )->first();
        $manager_perm = Permission::where( 'slug', 'edit-users' )->first();

        $developer           = new User();
        $developer->name     = 'Mahedi Hasan';
        $developer->email    = 'mahedi@gmail.com';
        $developer->password = bcrypt( 'secrettt' );
        $developer->save();
        $developer->roles()->attach( $dev_role );
        $developer->permissions()->attach( $dev_perm );

        $manager           = new User();
        $manager->name     = 'Hafizul Islam';
        $manager->email    = 'hafiz@gmail.com';
        $manager->password = bcrypt( 'secrettt' );
        $manager->save();
        $manager->roles()->attach( $manager_role );
        $manager->permissions()->attach( $manager_perm );


        return redirect()->back();
    }
}
