<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CustomerStoreRequest;
use App\Customer;
use App\Oder;

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

    public function checkCustomer(Request $request){

        try{

            $customer = Customer::where("phone", $request->phone)->first();
            
            if($customer){
                $order = Order::where('customer_id', $customer->id)->where('status_id', '<', "5")->orderBy('id', 'desc')->first();
            }

            return response()->json(["success" => true, "customer" => $customer, "order" => $order]);

        }catch(\Exception $e){

            return response()->json(["success" => false, "msg" => "Error en el servidor"]);

        }

    }

}
