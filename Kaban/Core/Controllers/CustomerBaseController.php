<?php


namespace Kaban\Core\Controllers;


use App\Http\Controllers\Controller;

class CustomerBaseController extends Controller {

    public function index() {
        return view( 'CustomerBase::index' );
    }
}
