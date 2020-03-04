<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CustomerStoreRequest;
use App\Customer;

class CustomerController extends Controller
{
    
    public function store(CustomerStoreRequest $request){

        try{

            $customer = new Customer;
            $customer->name = $request->name;
            $customer->phone = $request->phone;
            $customer->save();

            return response()->json(["success" => true]);

        }catch(\Exception $e){

        }

    }

}
